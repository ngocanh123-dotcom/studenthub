<?php
session_start();

// Dữ liệu tài khoản demo đầy đủ cho các vai trò
$taiKhoanDemo = [
    'sv001' => ['password' => 'Sv@123', 'role' => 'sinhvien', 'tenVaiTro' => 'Sinh viên'],
    'gv001' => ['password' => 'Gv@123', 'role' => 'giangvien', 'tenVaiTro' => 'Giảng viên'],
    'admin' => ['password' => 'Admin@123', 'role' => 'quantri', 'tenVaiTro' => 'Quản trị viên'],
    'admin1' => ['password' => 'Admin@123', 'role' => 'quantri', 'tenVaiTro' => 'Quản trị viên 1'],
    'admin2' => ['password' => 'Admin@123', 'role' => 'quantri', 'tenVaiTro' => 'Quản trị viên 2'],
    'admin3' => ['password' => 'Admin@123', 'role' => 'quantri', 'tenVaiTro' => 'Quản trị viên 3']
];
<---! vai trò đăng nhập hợp lệ -->

$tenVaiTroMap = [
    'sinhvien' => 'Sinh viên',
    'giangvien' => 'Giảng viên',
    'quantri' => 'Quản trị viên'
];

$thongBao = '';
$loaiThongBao = '';
$loiUsername = '';
$loiPassword = '';
$username = '';
$role = 'sinhvien';

// Xử lý Validation phía Server (PHP)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (!isset($tenVaiTroMap[$role])) {
        $thongBao = 'Vui lòng chọn vai trò đăng nhập hợp lệ.';
        $loaiThongBao = 'error';
    } elseif ($username === '') {
        $loiUsername = 'Vui lòng nhập tên đăng nhập.';
        $thongBao = 'Vui lòng nhập tên đăng nhập.';
        $loaiThongBao = 'error';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $loiUsername = 'Tên đăng nhập gồm 3-30 ký tự, không chứa ký tự đặc biệt.';
        $thongBao = 'Tên đăng nhập không đúng định dạng.';
        $loaiThongBao = 'error';
    } elseif (trim($password) === '') {
        $loiPassword = 'Vui lòng nhập mật khẩu.';
        $thongBao = 'Vui lòng nhập mật khẩu.';
        $loaiThongBao = 'error';
    } elseif (strlen($password) < 6) {
        $loiPassword = 'Mật khẩu phải có ít nhất 6 ký tự.';
        $thongBao = 'Mật khẩu phải có ít nhất 6 ký tự.';
        $loaiThongBao = 'error';
    } elseif (!isset($taiKhoanDemo[$username])) {
        $loiUsername = 'Tài khoản không tồn tại trong hệ thống.';
        $thongBao = 'Tài khoản không tồn tại trong hệ thống.';
        $loaiThongBao = 'error';
    } elseif ($taiKhoanDemo[$username]['password'] !== $password) {
        $loiPassword = 'Mật khẩu không chính xác.';
        $thongBao = 'Mật khẩu không chính xác.';
        $loaiThongBao = 'error';
    } elseif ($taiKhoanDemo[$username]['role'] !== $role) {
        $thongBao = 'Sai vai trò! Tài khoản "' . htmlspecialchars($username) . '" không thuộc vai trò ' . $tenVaiTroMap[$role] . '.';
        $loaiThongBao = 'error';
    } else {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['tenVaiTro'] = $taiKhoanDemo[$username]['tenVaiTro'];

        $thongBao = 'Đăng nhập thành công! Xin chào ' . $taiKhoanDemo[$username]['tenVaiTro'] . '.';
        $loaiThongBao = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cổng Đăng Ký Học Phần & Quản Trị Học Vụ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="portal-layout">
    <!-- CỘT TRÁI: NHẬN DIỆN THƯƠNG HIỆU & THÔNG TIN HỆ THỐNG -->
    <aside class="portal-showcase">
        <header class="showcase-header">
            <div class="brand-badge" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="#60a5fa" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10L12 5 2 10l10 5 10-5z"/>
                    <path d="M6 12v5c3 2.5 9 2.5 12 0v-5"/>
                    <path d="M22 10v6"/>
                </svg>
            </div>
            <div class="brand-text">
                <h2>EDUPORTAL</h2>
                <span>Hệ Thống Đào Tạo Tín Chỉ</span>
            </div>
        </header>

        <section class="showcase-hero">
            <div class="semester-pill">
                <span class="pulse-dot"></span>
                <span>Học kỳ II (2026 - 2027) • Cổng đăng ký mở</span>
            </div>
            <h1>Cổng Đăng Ký Học Phần & <span class="gradient-text">Quản Trị Học Vụ</span></h1>
            <p>Hệ sinh thái học tập số hóa đồng bộ: hỗ trợ sinh viên tra cứu lộ trình đào tạo, đăng ký tín chỉ trực tuyến và quản trị dữ liệu học phần dành riêng cho giảng viên và nhà trường.</p>
            
            <!-- DANH SÁCH TÍNH NĂNG ĐỒNG BỘ TINH GỌN (THAY THẾ 2 THẺ HỌC PHẦN CŨ) -->
            <div class="feature-highlights">
                <div class="feature-item">
                    <div class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <div class="feature-content">
                        <strong>Đăng ký tín chỉ trực tuyến</strong>
                        <p>Thời gian thực, chống trùng lịch học và tự động cập nhật sức chứa phòng học.</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="feature-content">
                        <strong>Thời khóa biểu & Tiến độ tích lũy</strong>
                        <p>Tích hợp bảng điểm, kiểm soát điều kiện tiên quyết và cảnh báo học vụ chuẩn xác.</p>
                    </div>
                </div>
            </div>
        </section>

        <footer class="showcase-footer">
            <p>© 2026 Trung tâm Công nghệ Thông tin & Khảo thí Đào tạo</p>
        </footer>
    </aside>

    <!-- CỘT PHẢI: FORM ĐĂNG NHẬP -->
    <main class="portal-form-pane">
        <div class="auth-box">
            <header class="auth-header">
                <h2>Đăng nhập hệ thống</h2>
                <p>Nhập thông tin xác thực để bắt đầu phiên làm việc</p>
            </header>

            <form method="POST" action="index.php" id="loginForm" novalidate>
                <!-- CHỌN VAI TRÒ -->
                <span class="role-section-label">Chọn vai trò truy cập</span>
                <div class="role-bento-grid" role="radiogroup" aria-label="Vai trò đăng nhập">
                    <div class="role-tile">
                        <input type="radio" name="role" id="student" value="sinhvien" <?= $role === 'sinhvien' ? 'checked' : '' ?>>
                        <label for="student">
                            <span class="icon-holder">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 10L12 5 2 10l10 5 10-5z"/>
                                    <path d="M6 12v5c3 2.5 9 2.5 12 0v-5"/>
                                </svg>
                            </span>
                            <span class="title">Sinh viên</span>
                        </label>
                    </div>

                    <div class="role-tile">
                        <input type="radio" name="role" id="lecturer" value="giangvien" <?= $role === 'giangvien' ? 'checked' : '' ?>>
                        <label for="lecturer">
                            <span class="icon-holder">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="12" rx="2"/>
                                    <path d="M12 16v4"/>
                                    <path d="M8 20h8"/>
                                </svg>
                            </span>
                            <span class="title">Giảng viên</span>
                        </label>
                    </div>

                    <div class="role-tile">
                        <input type="radio" name="role" id="admin" value="quantri" <?= $role === 'quantri' ? 'checked' : '' ?>>
                        <label for="admin">
                            <span class="icon-holder">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    <path d="M9 12l2 2 4-4"/>
                                </svg>
                            </span>
                            <span class="title">Quản trị</span>
                        </label>
                    </div>
                </div>

                <!-- TÊN ĐĂNG NHẬP -->
                <div class="form-group">
                    <label for="username">Mã định danh / Tên đăng nhập</label>
                    <div class="input-shell">
                        <span class="field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <input id="username" name="username" type="text"
                               class="<?= $loiUsername !== '' ? 'invalid' : '' ?>"
                               placeholder="Ví dụ: sv001, gv001, admin..."
                               autocomplete="username" maxlength="60"
                               value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <small id="usernameError" class="field-error-msg"><?= htmlspecialchars($loiUsername, ENT_QUOTES, 'UTF-8') ?></small>
                </div>

                <!-- MẬT KHẨU -->
                <div class="form-group">
                    <label for="password">Mật khẩu truy cập</label>
                    <div class="input-shell">
                        <span class="field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="11" width="16" height="10" rx="2"/>
                                <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
                                <circle cx="12" cy="16" r="1"/>
                            </svg>
                        </span>
                        <input id="password" name="password" type="password"
                               class="<?= $loiPassword !== '' ? 'invalid' : '' ?>"
                               placeholder="Nhập mật khẩu..."
                               autocomplete="current-password" maxlength="128">
                        <button class="password-toggle-btn" type="button" id="togglePassword" title="Hiện mật khẩu" aria-label="Hiện mật khẩu">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <small id="passwordError" class="field-error-msg"><?= htmlspecialchars($loiPassword, ENT_QUOTES, 'UTF-8') ?></small>
                </div>

                <!-- TÙY CHỌN BỔ TRỢ -->
                <div class="form-actions-aux">
                    <label class="remember-check" for="remember">
                        <input type="checkbox" id="remember" name="remember">
                        <span>Ghi nhớ phiên đăng nhập</span>
                    </label>
                    <a href="#" class="forgot-password-link" id="forgotLink">Quên mật khẩu?</a>
                </div>

                <!-- THÔNG BÁO TRẠNG THÁI TOAST -->
                <div id="message" class="status-banner <?= htmlspecialchars($loaiThongBao, ENT_QUOTES, 'UTF-8') ?>" role="status" aria-live="polite">
                    <?= htmlspecialchars($thongBao, ENT_QUOTES, 'UTF-8') ?>
                </div>

                <!-- NÚT GỬI -->
                <button class="btn-auth-submit" type="submit" id="submitBtn">
                    <span>ĐĂNG NHẬP VÀO HỆ THỐNG</span>
                </button>
            </form>

            <p class="auth-footer-caption">Tài khoản được cấp tự động bởi Quản trị viên phòng Đào tạo</p>
        </div>
    </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>