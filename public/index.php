<?php
/**
 * public/index.php
 * Cổng Đăng Nhập & Xác Thực Tập Trung - StudentHub
 * Kiến trúc: MVC - PHP 8.x - PDO Prepared Statements - Bảo mật Session
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. XỬ LÝ ĐĂNG XUẤT
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    header('Location: index.php');
    exit;
}

// Route every public module through this single web entry point.
$route = strtolower(trim((string) ($_GET['route'] ?? ''), '/'));
if ($route === 'dangky' || $route === 'dangky.php') {
    header('Location: /studenthub/public/index.php?route=trang_dang_ky_hoc.php');
    exit;
}
if ($route === 'danh_sach_lop_hoc_phan') {
    $route = 'danh_sach_lop_hoc_phan.php';
}

switch ($route) {
    case 'tong_quan_sv':
    case 'tong_quan_sv.php':
    case 'sinh_vien.php':
        require_once __DIR__ . '/../routes/sinh_vien.php';
        exit;
    case 'quen_mat_khau':
    case 'quen_mat_khau.php':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        $authController = new AuthController($pdo ?? null);
        $authController->quenMatKhau();
        exit;
}

$publicRoutes = [
    'tong_quan_sv' => __DIR__ . '/../routes/sinh_vien.php',
    'tong_quan_sv.php' => __DIR__ . '/../routes/sinh_vien.php',
    'quen_mat_khau' => __DIR__ . '/../app/Views/auth/quen_mat_khau.php',
    'quen_mat_khau.php' => __DIR__ . '/../app/Views/auth/quen_mat_khau.php',
    'admin_hoc_ky.php' => __DIR__ . '/../routes/admin_hoc_ky.php',
    'admin_in_danh_sach_lop.php' => __DIR__ . '/../routes/admin_in_danh_sach_lop.php',
    'admin_lop_hoc_phan.php' => __DIR__ . '/../routes/admin_lop_hoc_phan.php',
    'api_huy_dang_ky.php' => __DIR__ . '/../routes/api_huy_dang_ky.php',
    'chi_tiet_lop_hoc_phan.php' => __DIR__ . '/../routes/chi_tiet_lop_hoc_phan.php',
    'dang_ky_hoc.php' => __DIR__ . '/../routes/dang_ky_hoc.php',
    'dangky.php' => __DIR__ . '/../routes/dangky.php',
    'danh_sach_lop_hoc_phan.php' => __DIR__ . '/../routes/danh_sach_lop_hoc_phan.php',
    'doi_mat_khau.php' => __DIR__ . '/../routes/doi_mat_khau.php',
    'giang_vien.php' => __DIR__ . '/../routes/giang_vien.php',
    'giang_vien_lop_hoc_phan.php' => __DIR__ . '/../routes/giang_vien_lop_hoc_phan.php',
    'giang_vien_sinh_vien.php' => __DIR__ . '/../routes/giang_vien_sinh_vien.php',
    'huy_dang_ky_hoc.php' => __DIR__ . '/../routes/huy_dang_ky_hoc.php',
    'lich_su_dang_ky.php' => __DIR__ . '/../routes/lich_su_dang_ky.php',
    'logout.php' => __DIR__ . '/../routes/logout.php',
    'sinh_vien.php' => __DIR__ . '/../routes/sinh_vien.php',
    'trang_dang_ky_hoc.php' => __DIR__ . '/../routes/trang_dang_ky_hoc.php',
];

if ($route !== '') {
    if (!isset($publicRoutes[$route])) {
        http_response_code(404);
        exit('Không tìm thấy trang yêu cầu.');
    }

    require $publicRoutes[$route];
    exit;
}

// 2. KẾT NỐI DATABASE
$pdo = null;
$dbError = false;

$dbFile = __DIR__ . '/../config/database.php';
if (file_exists($dbFile)) {
    require_once $dbFile;
    if (isset($conn) && $conn instanceof PDO) {
        $pdo = $conn;
    }
}

// Kết nối dự phòng an toàn hỗ trợ cả pass '123456' và ''
if (!$pdo instanceof PDO) {
    $dbPassCandidates = ['123456', ''];
    foreach ($dbPassCandidates as $candidate) {
        try {
            $pdo = new PDO(
                "mysql:host=localhost;dbname=studenthub;charset=utf8mb4",
                "root",
                $candidate,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            $conn = $pdo;
            break;
        } catch (PDOException $e) {
            // Thử mật khẩu tiếp theo
        }
    }
}
if (!$pdo instanceof PDO) {
    $dbError = true;
}

if (!isset($conn) || !($conn instanceof PDO)) {
    $conn = $pdo;
}

// Ánh xạ vai trò hiển thị và vai trò CSDL
$tenVaiTroMap = [
    'sinhvien'  => 'Sinh viên',
    'giangvien' => 'Giảng viên',
    'quantri'   => 'Quản trị viên'
];

$vaiTroDatabase = [
    'sinhvien'  => 'sinh_vien',
    'giangvien' => 'giang_vien',
    'quantri'   => 'admin'
];

$roleAliases = [
    'sinh_vien'  => 'sinhvien',
    'sinhvien'   => 'sinhvien',
    'giang_vien' => 'giangvien',
    'giangvien'  => 'giangvien',
    'admin'      => 'quantri',
    'quantri'    => 'quantri'
];

$thongBao = '';
$loaiThongBao = '';
$loiGet = (string)($_GET['error'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $loiGet !== '') {
    $thongBaoGet = ['login_required' => 'Vui lòng đăng nhập để sử dụng chức năng này.', 'forbidden' => 'Bạn không có quyền truy cập khu vực này.'];
    if (isset($thongBaoGet[$loiGet])) {
        $thongBao = $thongBaoGet[$loiGet];
        $loaiThongBao = 'error';
    }
}
$username = '';
$role = 'sinhvien';

// 3. XỬ LÝ ĐĂNG NHẬP (AUTHENTICATION CONTROLLER)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? $_POST['ten_dang_nhap'] ?? '');
    $password = trim($_POST['password'] ?? $_POST['mat_khau'] ?? '');

    $authenticated = false;
    $taiKhoan = null;
    $role = 'sinhvien';

    if ($username === '' || $password === '') {
        $thongBao = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
        $loaiThongBao = 'error';
    } else {
        if (!$pdo instanceof PDO) {
            $thongBao = 'Không thể kết nối cơ sở dữ liệu. Vui lòng kiểm tra dịch vụ MySQL.';
            $loaiThongBao = 'error';
        } else {
            try {
                // 4.1. Truy vấn trực tiếp vào bảng nguoi_dung bằng PDO
                $stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE (LOWER(TRIM(ten_dang_nhap)) = LOWER(TRIM(:u)) OR LOWER(TRIM(email)) = LOWER(TRIM(:e))) LIMIT 1");
                $stmt->execute([':u' => $username, ':e' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                $loginSuccess = false;
                if ($user) {
                    $storedHash = (string)($user['mat_khau_hash'] ?? $user['mat_khau'] ?? '');

                    // Ưu tiên kiểm tra bằng hash chuẩn Bcrypt
                    if (!empty($storedHash) && password_verify($password, $storedHash)) {
                        $loginSuccess = true;
                    }
                    // Chỉ chấp nhận nếu mật khẩu trong DB chưa băm (dữ liệu ban đầu) và khớp đúng
                    elseif (!empty($storedHash) && !str_starts_with($storedHash, '$2y$') && ($password === $storedHash || md5($password) === $storedHash)) {
                        $loginSuccess = true;
                        // Tự động nâng cấp băm luôn vào CSDL
                        $newH = password_hash($password, PASSWORD_DEFAULT);
                        $pdo->prepare("UPDATE nguoi_dung SET mat_khau_hash = :h WHERE id = :id")->execute([':h' => $newH, ':id' => $user['id']]);
                        $user['mat_khau_hash'] = $newH;
                    }
                }

                if ($loginSuccess) {
                    $authenticated = true;
                    $taiKhoan = $user;
                } else {
                    $thongBao = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
                    $loaiThongBao = 'error';
                }
            } catch (PDOException $e) {
                error_log('Lỗi truy vấn đăng nhập: ' . $e->getMessage());
                $thongBao = 'Có lỗi xảy ra khi xử lý đăng nhập. Vui lòng thử lại sau.';
                $loaiThongBao = 'error';
            }
        }
    }

    // 4.2. ĐĂNG NHẬP THÀNH CÔNG: CHUẨN HÓA VÀ ĐỒNG BỘ SESSION
    if ($authenticated && $taiKhoan) {
        session_regenerate_id(true);

        // Kiểm tra tài khoản sử dụng mật khẩu mặc định (123456) để bật cờ đổi mật khẩu bắt buộc
        $isDefaultPass = ($password === '123456');
        if (!$isDefaultPass && !empty($taiKhoan['mat_khau_hash'])) {
            $isDefaultPass = password_verify('123456', $taiKhoan['mat_khau_hash']);
        }

        if ($isDefaultPass) {
            $_SESSION['must_change_password'] = true;
            $_SESSION['first_login_alert'] = 'Cảnh báo bảo mật quan trọng: Mật khẩu của bạn là mật khẩu mặc định (123456). Vui lòng đổi mật khẩu để bảo vệ thông tin cá nhân và đảm bảo không ai (kể cả Admin) biết mật khẩu của bạn!';
            setcookie('sh_must_change_password', '1', [
                'expires'  => 0,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) === 'on',
                'httponly' => false,
                'samesite' => 'Lax'
            ]);
        } else {
            unset($_SESSION['must_change_password'], $_SESSION['first_login_alert']);
            setcookie('sh_must_change_password', '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) === 'on',
                'httponly' => false,
                'samesite' => 'Lax'
            ]);
        }

        $dbRole = strtolower(trim((string)($taiKhoan['vai_tro'] ?? 'sinh_vien')));
        $roleMap = [
            'sinh_vien'  => 'sinhvien',
            'sinhvien'   => 'sinhvien',
            'giang_vien' => 'giangvien',
            'giangvien'  => 'giangvien',
            'admin'      => 'quantri',
            'quantri'    => 'quantri'
        ];
        $role = $roleMap[$dbRole] ?? 'sinhvien';
        $vaiTroDatabaseVal = [
            'sinhvien'  => 'sinh_vien',
            'giangvien' => 'giang_vien',
            'quantri'   => 'admin'
        ];
        $vaiTroChuan = $vaiTroDatabaseVal[$role] ?? 'sinh_vien';

        // Chuẩn hóa Session
        $_SESSION['user_id']       = (int)$taiKhoan['id'];
        $_SESSION['ten_dang_nhap'] = (string)$taiKhoan['ten_dang_nhap'];
        $_SESSION['ho_ten']        = (string)$taiKhoan['ho_ten'];
        $_SESSION['vai_tro']       = (string)$vaiTroChuan; // 'sinh_vien', 'giang_vien', 'admin'
        $_SESSION['nguoi_dung_id'] = (int)$taiKhoan['id'];
        $_SESSION['username']      = (string)$taiKhoan['ten_dang_nhap'];
        $_SESSION['role']          = (string)$role; // 'sinhvien', 'giangvien', 'quantri'
        $_SESSION['tenVaiTro']     = $tenVaiTroMap[$role] ?? 'Thành viên';
        $_SESSION['csrf_token']    = bin2hex(random_bytes(32));
        $_SESSION['nguoi_dung']    = [
            'id'            => (int)$taiKhoan['id'],
            'ten_dang_nhap' => (string)$taiKhoan['ten_dang_nhap'],
            'ho_ten'        => (string)$taiKhoan['ho_ten'],
            'vai_tro'       => (string)$vaiTroChuan,
            'email'         => (string)($taiKhoan['email'] ?? '')
        ];
        $trangDich = [
            'sinhvien'  => '/studenthub/public/index.php?route=tong_quan_sv',
            'giangvien' => '/studenthub/public/index.php?route=giang_vien.php',
            'quantri'   => '/studenthub/public/index.php?route=admin_lop_hoc_phan.php'
        ];

        session_write_close(); // Bắt buộc ghi hoàn tất session vào disk trước khi redirect
        header('Location: ' . $trangDich[$role]);
        exit(); // Bắt buộc có exit
        }
    }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cổng Đăng Ký Học Phần & Quản Trị Học Vụ - StudentHub</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css?v=<?= time() ?>">
    <style>
        /* Tùy chỉnh thông báo thành công cho form đăng nhập */
        .notification-banner.success {
            display: block !important;
            background: #ecfdf5 !important;
            color: #065f46 !important;
            border: 1px solid #a7f3d0 !important;
        }

    </style>
</head>

<body class="login-page">
<div class="login-split-layout">

    <!-- CỘT TRÁI: SHOWCASE & GIỚI THIỆU -->
    <aside class="showcase-pane">
        <div class="showcase-header">
            <div class="brand-badge-box">
                <svg viewBox="0 0 24 24" width="24" height="24"
                     fill="none" stroke="currentColor" stroke-width="2.2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10L12 5 2 10l10 5 10-5z"/>
                    <path d="M6 12v5c3 2.5 9 2.5 12 0v-5"/>
                    <path d="M22 10v6"/>
                </svg>
            </div>

            <div class="brand-text-wrap">
                <h2>StudentHub</h2>
                <span>HỆ THỐNG ĐÀO TẠO TÍN CHỈ</span>
            </div>
        </div>

        <div class="showcase-body">
            <h1 class="showcase-main-title">
                Cổng Đăng Ký Học Phần &amp; Quản Trị Học Vụ
            </h1>

            <p class="showcase-description">
                Hệ sinh thái học tập số hóa đồng bộ: hỗ trợ sinh viên tra cứu
                lộ trình đào tạo, đăng ký tín chỉ trực tuyến và quản trị dữ liệu
                học phần dành riêng cho giảng viên và nhà trường.
            </p>

            <div class="features-list">
                <div class="feature-card-item">
                    <div class="feature-icon-box">
                        <svg viewBox="0 0 24 24" width="20" height="20"
                             fill="none" stroke="currentColor" stroke-width="2.2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>

                    <div class="feature-content">
                        <strong>Đăng ký tín chỉ trực tuyến</strong>
                        <p>Thời gian thực, kiểm soát sĩ số phòng học và chống trùng lịch học thông minh.</p>
                    </div>
                </div>

                <div class="feature-card-item">
                    <div class="feature-icon-box">
                        <svg viewBox="0 0 24 24" width="20" height="20"
                             fill="none" stroke="currentColor" stroke-width="2.2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>

                    <div class="feature-content">
                        <strong>Thời khóa biểu &amp; Tiến độ học tập</strong>
                        <p>Tích hợp đề cương học phần, kiểm soát điều kiện tiên quyết và cảnh báo học vụ chuẩn xác.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="showcase-footer">
            &copy; 2026 Trung tâm Công nghệ Thông tin &amp; Khảo thí Đào tạo - StudentHub
        </div>
    </aside>

    <!-- CỘT PHẢI: FORM ĐĂNG NHẬP -->
    <main class="form-pane">
        <div class="auth-card-container">
            <div class="auth-card-header">
                <h2>Đăng nhập hệ thống</h2>
                <p>Nhập thông tin xác thực để bắt đầu phiên làm việc</p>
            </div>

            <form method="POST" action="index.php" id="authLoginForm" novalidate>
                <!-- Ẩn khối Chọn vai trò truy cập (Backend tự động nhận diện từ CSDL) -->
                <div style="display: none;" aria-hidden="true">
                <span class="bento-group-label">CHỌN VAI TRÒ TRUY CẬP</span>

                <div class="bento-tiles-grid" role="radiogroup">
                    <!-- SINH VIÊN -->
                    <div class="bento-tile-item">
                        <input type="radio" name="role" id="role_sinhvien"
                               value="sinhvien"
                            <?= $role === 'sinhvien' ? 'checked' : '' ?>>

                        <label for="role_sinhvien">
                            <span class="tile-icon-box">
                                <svg viewBox="0 0 24 24" width="20" height="20"
                                     fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 10L12 5 2 10l10 5 10-5z"/>
                                    <path d="M6 12v5c3 2.5 9 2.5 12 0v-5"/>
                                </svg>
                            </span>
                            <span class="tile-title">Sinh viên</span>
                        </label>
                    </div>

                    <!-- GIẢNG VIÊN -->
                    <div class="bento-tile-item">
                        <input type="radio" name="role" id="role_giangvien"
                               value="giangvien"
                            <?= $role === 'giangvien' ? 'checked' : '' ?>>

                        <label for="role_giangvien">
                            <span class="tile-icon-box">
                                <svg viewBox="0 0 24 24" width="20" height="20"
                                     fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                    <line x1="8" y1="21" x2="16" y2="21"/>
                                    <line x1="12" y1="17" x2="12" y2="21"/>
                                </svg>
                            </span>
                            <span class="tile-title">Giảng viên</span>
                        </label>
                    </div>

                    <!-- QUẢN TRỊ -->
                    <div class="bento-tile-item">
                        <input type="radio" name="role" id="role_quantri"
                               value="quantri"
                            <?= $role === 'quantri' ? 'checked' : '' ?>>

                        <label for="role_quantri">
                            <span class="tile-icon-box">
                                <svg viewBox="0 0 24 24" width="20" height="20"
                                     fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                            </span>
                            <span class="tile-title">Quản trị</span>
                        </label>
                    </div>
                </div>
                </div>

                <!-- TÊN ĐĂNG NHẬP -->
                <div class="field-control-group">
                    <label for="usernameInput">Mã định danh / Tên đăng nhập</label>
                    <div class="field-input-wrapper">
                        <span class="field-leading-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18"
                                 fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>

                        <input type="text" id="usernameInput" name="username"
                               value="<?= htmlspecialchars($username !== '' ? $username : 'sv001', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Ví dụ: sv001, gv001, admin..."
                               autocomplete="username" required>
                    </div>
                </div>

                <!-- MẬT KHẨU -->
                <div class="field-control-group">
                    <label for="passwordInput">Mật khẩu truy cập</label>
                    <div class="field-input-wrapper">
                        <span class="field-leading-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18"
                                 fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>

                        <input type="password" id="passwordInput" name="password"
                               placeholder="Nhập mật khẩu truy cập..."
                               autocomplete="current-password" required>

                        <button type="button" class="btn-eye-toggle"
                                id="btnTogglePassword" title="Hiện mật khẩu">
                            <svg viewBox="0 0 24 24" width="18" height="18"
                                 fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- GHI NHỚ VÀ QUÊN MẬT KHẨU -->
                <div class="aux-actions-row">
                    <label class="remember-label" for="rememberMeCheckbox">
                        <input type="checkbox" id="rememberMeCheckbox" name="remember">
                        <span>Ghi nhớ phiên đăng nhập</span>
                    </label>

                    <a href="/studenthub/public/index.php?route=quen_mat_khau" class="forgot-link">
                        Quên mật khẩu?
                    </a>
                </div>

                <!-- THÔNG BÁO LỖI HOẶC THÀNH CÔNG NẾU CÓ -->
                <?php if (!empty($thongBao)): ?>
                    <div class="notification-banner <?= $loaiThongBao === 'success' ? 'success' : 'error' ?>" id="statusBanner">
                        <?= htmlspecialchars($thongBao, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php else: ?>
                    <div class="notification-banner" id="statusBanner" style="display: none;"></div>
                <?php endif; ?>

                <!-- NÚT ĐĂNG NHẬP -->
                <button type="submit" class="btn-primary-auth" id="btnSubmitAuth">
                    <span>ĐĂNG NHẬP VÀO HỆ THỐNG</span>
                </button>
            </form>

            <div class="auth-card-footer">
                Hệ thống Quản lý khóa học và Đăng ký học phần • StudentHub 2026
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/login.js"></script>
</body>
</html>
