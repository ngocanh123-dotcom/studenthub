
<?php
session_start();

// Chỉ cho phép Admin truy cập.
if (empty($_SESSION['username'])) {
    header('Location: ../../../public/index.php');
    exit;
}

$vaiTroSession = strtolower(trim((string) ($_SESSION['role'] ?? '')));

if (!in_array($vaiTroSession, ['admin', 'quantri', 'quan_tri', 'quan_tri_vien'], true)) {
    header('Location: ../../../public/index.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$thongBao = '';
$loaiThongBao = 'success';
$loi = '';

$tuKhoa = trim($_GET['q'] ?? '');
$locVaiTro = trim($_GET['vai_tro'] ?? '');
$hanhDong = $_POST['hanh_dong'] ?? '';

$duLieuSua = [
    'id' => '',
    'ten_dang_nhap' => '',
    'ho_ten' => '',
    'email' => '',
    'so_dien_thoai' => '',
    'vai_tro' => 'sinh_vien'
];

// XỬ LÝ THÊM, SỬA, XÓA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfNhan = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfNhan)) {
        $thongBao = 'Phiên làm việc không hợp lệ. Hãy tải lại trang.';
        $loaiThongBao = 'error';
    } else {
        try {
            if ($hanhDong === 'them' || $hanhDong === 'sua') {
                $id = (int) ($_POST['id'] ?? 0);
                $tenDangNhap = trim($_POST['ten_dang_nhap'] ?? '');
                $hoTen = trim($_POST['ho_ten'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $soDienThoai = trim($_POST['so_dien_thoai'] ?? '');
                $vaiTro = trim($_POST['vai_tro'] ?? '');
                $matKhau = $_POST['mat_khau'] ?? '';

                $duLieuSua = [
                    'id' => $id,
                    'ten_dang_nhap' => $tenDangNhap,
                    'ho_ten' => $hoTen,
                    'email' => $email,
                    'so_dien_thoai' => $soDienThoai,
                    'vai_tro' => $vaiTro
                ];

                if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $tenDangNhap)) {
                    throw new RuntimeException('Tên đăng nhập phải gồm 3–30 ký tự chữ, số hoặc dấu gạch dưới.');
                }

                if ($hoTen === '') {
                    throw new RuntimeException('Vui lòng nhập họ và tên.');
                }

                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Địa chỉ email không hợp lệ.');
                }

                if ($soDienThoai !== '' && !preg_match('/^[0-9+\s().-]{8,20}$/', $soDienThoai)) {
                    throw new RuntimeException('Số điện thoại không hợp lệ.');
                }

                if (!in_array($vaiTro, ['sinh_vien', 'giang_vien', 'admin'], true)) {
                    throw new RuntimeException('Vui lòng chọn vai trò hợp lệ.');
                }

                if ($hanhDong === 'them' && strlen($matKhau) < 6) {
                    throw new RuntimeException('Mật khẩu phải có ít nhất 6 ký tự.');
                }

                if ($hanhDong === 'sua' && $id <= 0) {
                    throw new RuntimeException('Tài khoản cần sửa không hợp lệ.');
                }

                // Không cho phép đổi vai trò của tài khoản Admin đang đăng nhập.
                if (
                    $hanhDong === 'sua'
                    && $tenDangNhap === ($_SESSION['username'] ?? '')
                    && $vaiTro !== 'admin'
                ) {
                    throw new RuntimeException('Không thể đổi vai trò của tài khoản Admin đang đăng nhập.');
                }

                // Kiểm tra tên đăng nhập đã được sử dụng chưa.
                $sqlTrung = "SELECT COUNT(*) FROM nguoi_dung
                             WHERE ten_dang_nhap = :username";
                $thamSoTrung = [':username' => $tenDangNhap];

                if ($hanhDong === 'sua') {
                    $sqlTrung .= " AND id <> :id";
                    $thamSoTrung[':id'] = $id;
                }

                $stmtTrung = $conn->prepare($sqlTrung);
                $stmtTrung->execute($thamSoTrung);

                if ((int) $stmtTrung->fetchColumn() > 0) {
                    throw new RuntimeException('Tên đăng nhập này đã tồn tại.');
                }

                if ($hanhDong === 'them') {
                    $sql = "INSERT INTO nguoi_dung
                            (ten_dang_nhap, mat_khau_hash, ho_ten, email,
                             so_dien_thoai, vai_tro)
                            VALUES
                            (:username, :password, :fullname, :email,
                             :phone, :role)";

                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        ':username' => $tenDangNhap,
                        ':password' => password_hash($matKhau, PASSWORD_DEFAULT),
                        ':fullname' => $hoTen,
                        ':email' => $email !== '' ? $email : null,
                        ':phone' => $soDienThoai !== '' ? $soDienThoai : null,
                        ':role' => $vaiTro
                    ]);

                    $thongBao = 'Thêm tài khoản thành công.';
                } else {
                    if ($matKhau !== '' && strlen($matKhau) < 6) {
                        throw new RuntimeException('Mật khẩu mới phải có ít nhất 6 ký tự.');
                    }

                    $sql = "UPDATE nguoi_dung
                            SET ten_dang_nhap = :username,
                                ho_ten = :fullname,
                                email = :email,
                                so_dien_thoai = :phone,
                                vai_tro = :role";

                    $thamSo = [
                        ':username' => $tenDangNhap,
                        ':fullname' => $hoTen,
                        ':email' => $email !== '' ? $email : null,
                        ':phone' => $soDienThoai !== '' ? $soDienThoai : null,
                        ':role' => $vaiTro,
                        ':id' => $id
                    ];

                    // Chỉ đổi mật khẩu nếu người quản trị nhập mật khẩu mới.
                    if ($matKhau !== '') {
                        $sql .= ", mat_khau_hash = :password";
                        $thamSo[':password'] = password_hash($matKhau, PASSWORD_DEFAULT);
                    }

                    $sql .= " WHERE id = :id";

                    $stmt = $conn->prepare($sql);
                    $stmt->execute($thamSo);

                    // Cập nhật session nếu sửa tài khoản đang đăng nhập.
                    if ($tenDangNhap === ($_SESSION['username'] ?? '')) {
                        $_SESSION['username'] = $tenDangNhap;
                        $_SESSION['ho_ten'] = $hoTen;
                        $_SESSION['role'] = 'admin';
                        $_SESSION['tenVaiTro'] = 'Quản trị viên';
                    }

                    $thongBao = 'Cập nhật tài khoản thành công.';
                }

                // Chuyển hướng để tránh gửi lại biểu mẫu khi tải lại trang.
                $_SESSION['account_notice'] = $thongBao;
                $_SESSION['account_notice_type'] = 'success';

                header('Location: tai_khoan.php');
                exit;
            }

            if ($hanhDong === 'xoa') {
                $idXoa = (int) ($_POST['id'] ?? 0);

                if ($idXoa <= 0) {
                    throw new RuntimeException('Mã tài khoản không hợp lệ.');
                }

                // Không cho tự xóa tài khoản đang đăng nhập.
                $stmt = $conn->prepare(
                    "SELECT id, ten_dang_nhap, vai_tro
                     FROM nguoi_dung WHERE id = :id LIMIT 1"
                );
                $stmt->execute([':id' => $idXoa]);
                $taiKhoanXoa = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$taiKhoanXoa) {
                    throw new RuntimeException('Không tìm thấy tài khoản cần xóa.');
                }

                if ($taiKhoanXoa['ten_dang_nhap'] === ($_SESSION['username'] ?? '')) {
                    throw new RuntimeException('Bạn không thể xóa tài khoản đang đăng nhập.');
                }

                if (($taiKhoanXoa['vai_tro'] ?? '') === 'admin') {
                    $stmtDem = $conn->query(
                        "SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'admin'"
                    );

                    if ((int) $stmtDem->fetchColumn() <= 1) {
                        throw new RuntimeException('Không thể xóa Admin cuối cùng của hệ thống.');
                    }
                }

                $stmt = $conn->prepare(
                    "DELETE FROM nguoi_dung WHERE id = :id"
                );
                $stmt->execute([':id' => $idXoa]);

                $_SESSION['account_notice'] = 'Xóa tài khoản thành công.';
                $_SESSION['account_notice_type'] = 'success';

                header('Location: tai_khoan.php');
                exit;
            }

        } catch (RuntimeException $e) {
            $thongBao = $e->getMessage();
            $loaiThongBao = 'error';
        } catch (PDOException $e) {
            error_log('StudentHub Account: ' . $e->getMessage());

            $thongBao = 'Không thể xử lý tài khoản. Hãy kiểm tra dữ liệu và các ràng buộc trong CSDL.';
            $loaiThongBao = 'error';
        }
    }
}

// Lấy thông báo sau chuyển hướng.
if (!empty($_SESSION['account_notice'])) {
    $thongBao = $_SESSION['account_notice'];
    $loaiThongBao = $_SESSION['account_notice_type'] ?? 'success';

    unset($_SESSION['account_notice'], $_SESSION['account_notice_type']);
}

// Lấy tài khoản để sửa.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['sua'])) {
    $idSua = (int) $_GET['sua'];

    if ($idSua > 0) {
        try {
            $stmt = $conn->prepare(
                "SELECT id, ten_dang_nhap, ho_ten, email,
                        so_dien_thoai, vai_tro
                 FROM nguoi_dung WHERE id = :id LIMIT 1"
            );
            $stmt->execute([':id' => $idSua]);
            $taiKhoanSua = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($taiKhoanSua) {
                $duLieuSua = $taiKhoanSua;
            } else {
                $thongBao = 'Không tìm thấy tài khoản cần sửa.';
                $loaiThongBao = 'error';
            }
        } catch (PDOException $e) {
            error_log('StudentHub Account Edit: ' . $e->getMessage());
            $thongBao = 'Không thể tải thông tin tài khoản.';
            $loaiThongBao = 'error';
        }
    }
}

// Danh sách tài khoản, tìm kiếm và lọc vai trò.
$danhSach = [];
$tongTaiKhoan = 0;

try {
    $sql = "SELECT id, ten_dang_nhap, ho_ten, email, so_dien_thoai, vai_tro
            FROM nguoi_dung WHERE 1 = 1";
    $thamSoTim = [];

    if ($tuKhoa !== '') {
        $sql .= " AND (ten_dang_nhap LIKE :q1
                   OR ho_ten LIKE :q2 OR email LIKE :q3)";
        $tim = '%' . $tuKhoa . '%';
        $thamSoTim[':q1'] = $tim;
        $thamSoTim[':q2'] = $tim;
        $thamSoTim[':q3'] = $tim;
    }

    if (in_array($locVaiTro, ['sinh_vien', 'giang_vien', 'admin'], true)) {
        $sql .= " AND vai_tro = :role";
        $thamSoTim[':role'] = $locVaiTro;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($thamSoTim);
    $danhSach = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtTong = $conn->query("SELECT COUNT(*) FROM nguoi_dung");
    $tongTaiKhoan = (int) $stmtTong->fetchColumn();

} catch (PDOException $e) {
    error_log('StudentHub Account List: ' . $e->getMessage());
    $loi = 'Không thể tải danh sách tài khoản. Hãy kiểm tra cấu trúc bảng nguoi_dung.';
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function tenVaiTro($role)
{
    switch ($role) {
        case 'sinh_vien': return 'Sinh viên';
        case 'giang_vien': return 'Giảng viên';
        case 'admin': return 'Quản trị viên';
        default: return 'Chưa xác định';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản | StudentHub</title>

    <!-- CSS nằm trong app/assets/css -->
    <link rel="stylesheet" href="../../../assets/css/quantrivien.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">S</div>
            <div>
                <strong>StudentHub</strong>
                <small>Hệ thống quản lý học phần</small>
            </div>
        </div>

        <nav class="menu">
            <div class="menu-title">QUẢN TRỊ HỆ THỐNG</div>

            <a class="menu-link" href="index.php">
                <span class="menu-icon">⌂</span> Tổng quan
            </a>

            <a class="menu-link active" href="tai_khoan.php">
                <span class="menu-icon">♙</span> Quản lý tài khoản
            </a>

            <a class="menu-link" href="hoc_phan.php">
                <span class="menu-icon">▤</span> Quản lý học phần
            </a>

            <a class="menu-link" href="lop_hoc_phan.php">
                <span class="menu-icon">▦</span> Quản lý lớp học phần
            </a>

            <a class="menu-link" href="hoc_ky.php">
                <span class="menu-icon">▣</span> Quản lý học kỳ
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a class="menu-link logout" href="../../../app/auth/logout.php">
                <span class="menu-icon">↪</span> Đăng xuất
            </a>

            <div class="sidebar-note">
                <span class="status-dot"></span> Hệ thống đang hoạt động
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <div class="breadcrumb">Quản trị hệ thống / Tài khoản</div>
                <h1>Quản lý tài khoản</h1>
                <p>Quản lý tài khoản sinh viên, giảng viên và quản trị viên.</p>
            </div>

            <div class="admin-profile">
                <div class="avatar">AD</div>
                <div class="profile-info">
                    <strong><?= e($_SESSION['ho_ten'] ?? $_SESSION['username'] ?? 'Quản trị viên') ?></strong>
                    <span>Quản trị viên</span>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if ($thongBao !== ''): ?>
                <div class="<?= $loaiThongBao === 'error' ? 'error-message' : 'account-notice' ?>">
                    <?= e($thongBao) ?>
                </div>
            <?php endif; ?>

            <?php if ($loi !== ''): ?>
                <div class="error-message"><?= e($loi) ?></div>
            <?php endif; ?>

            <section class="account-summary">
                <div>
                    <span class="summary-label">Tổng tài khoản trong hệ thống</span>
                    <strong><?= $tongTaiKhoan ?></strong>
                    <p>Danh sách tài khoản được lưu trong StudentHub.</p>
                </div>
                <div class="summary-icon">♙</div>
            </section>

            <section class="section">
                <div class="section-heading">
                    <div>
                        <h2><?= !empty($duLieuSua['id']) ? 'Cập nhật tài khoản' : 'Thêm tài khoản mới' ?></h2>
                        <p>Điền thông tin bên dưới để <?= !empty($duLieuSua['id']) ? 'cập nhật' : 'tạo' ?> tài khoản.</p>
                    </div>
                </div>

                <form method="POST" action="tai_khoan.php" class="account-form" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="hanh_dong" value="<?= !empty($duLieuSua['id']) ? 'sua' : 'them' ?>">
                    <input type="hidden" name="id" value="<?= e($duLieuSua['id']) ?>">

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="ten_dang_nhap">Tên đăng nhập <span>*</span></label>
                            <input id="ten_dang_nhap" name="ten_dang_nhap"
                                   required maxlength="30"
                                   pattern="[A-Za-z0-9_]{3,30}"
                                   value="<?= e($duLieuSua['ten_dang_nhap']) ?>"
                                   placeholder="Ví dụ: sv001">
                        </div>

                        <div class="form-field">
                            <label for="ho_ten">Họ và tên <span>*</span></label>
                            <input id="ho_ten" name="ho_ten" required maxlength="150"
                                   value="<?= e($duLieuSua['ho_ten']) ?>"
                                   placeholder="Nhập họ và tên">
                        </div>

                        <div class="form-field">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" maxlength="150"
                                   value="<?= e($duLieuSua['email'] ?? '') ?>"
                                   placeholder="example@email.com">
                        </div>

                        <div class="form-field">
                            <label for="so_dien_thoai">Số điện thoại</label>
                            <input id="so_dien_thoai" name="so_dien_thoai" maxlength="20"
                                   value="<?= e($duLieuSua['so_dien_thoai'] ?? '') ?>"
                                   placeholder="Nhập số điện thoại">
                        </div>

                        <div class="form-field">
                            <label for="vai_tro">Vai trò <span>*</span></label>
                            <select id="vai_tro" name="vai_tro" required>
                                <option value="sinh_vien" <?= $duLieuSua['vai_tro'] === 'sinh_vien' ? 'selected' : '' ?>>Sinh viên</option>
                                <option value="giang_vien" <?= $duLieuSua['vai_tro'] === 'giang_vien' ? 'selected' : '' ?>>Giảng viên</option>
                                <option value="admin" <?= $duLieuSua['vai_tro'] === 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
                            </select>
                        </div>

                       
<div class="form-field">
    <label for="mat_khau">
        Mật khẩu
        <?php if (empty($duLieuSua['id'])): ?>
            <span>*</span>
        <?php else: ?>
            <small>(Để trống nếu không đổi)</small>
        <?php endif; ?>
    </label>

    <input
        id="mat_khau"
        name="mat_khau"
        type="password"
        <?= empty($duLieuSua['id']) ? 'required' : '' ?>
        minlength="6"
        maxlength="128"
        autocomplete="new-password"
        placeholder="<?= empty($duLieuSua['id'])
            ? 'Nhập mật khẩu, tối thiểu 6 ký tự'
            : 'Nhập mật khẩu mới nếu muốn đổi' ?>"
    >

    <?php if (empty($duLieuSua['id'])): ?>
        <small class="password-help">
            Mật khẩu này sẽ được cấp cho sinh viên khi đăng nhập.
        </small>
    <?php endif; ?>
</div>

                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <?= !empty($duLieuSua['id']) ? 'Lưu thay đổi' : 'Thêm tài khoản' ?>
                        </button>

                        <?php if (!empty($duLieuSua['id'])): ?>
                            <a href="tai_khoan.php" class="btn-secondary">Hủy sửa</a>
                        <?php else: ?>
                            <button type="reset" class="btn-secondary">Nhập lại</button>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="section">
                <div class="section-heading">
                    <div>
                        <h2>Danh sách tài khoản</h2>
                        <p>Tra cứu và quản lý các tài khoản đang có.</p>
                    </div>
                </div>

                <form method="GET" action="tai_khoan.php" class="account-filters">
                    <input type="search" name="q" value="<?= e($tuKhoa) ?>"
                           placeholder="Tìm tên đăng nhập, họ tên, email...">

                    <select name="vai_tro" aria-label="Lọc theo vai trò">
                        <option value="">Tất cả vai trò</option>
                        <option value="sinh_vien" <?= $locVaiTro === 'sinh_vien' ? 'selected' : '' ?>>Sinh viên</option>
                        <option value="giang_vien" <?= $locVaiTro === 'giang_vien' ? 'selected' : '' ?>>Giảng viên</option>
                        <option value="admin" <?= $locVaiTro === 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
                    </select>

                    <button type="submit" class="btn-primary">Tìm kiếm</button>
                    <a href="tai_khoan.php" class="btn-secondary">Làm mới</a>
                </form>

                <div class="table-wrap">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>Mã</th>
                                <th>Tên đăng nhập</th>
                                <th>Họ và tên</th>
                                <th>Email</th>
                                <th>Số điện thoại</th>
                                <th>Vai trò</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($danhSach)): ?>
                            <tr>
                                <td colspan="7" class="empty">Không tìm thấy tài khoản nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($danhSach as $tk): ?>
                                <tr>
                                    <td><?= (int) $tk['id'] ?></td>
                                    <td><?= e($tk['ten_dang_nhap']) ?></td>
                                    <td><?= e($tk['ho_ten']) ?></td>
                                    <td><?= e($tk['email'] ?? '') ?></td>
                                    <td><?= e($tk['so_dien_thoai'] ?? '') ?></td>
                                    <td>
                                        <span class="account-role role-<?= e($tk['vai_tro']) ?>">
                                            <?= e(tenVaiTro($tk['vai_tro'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn-edit" href="tai_khoan.php?sua=<?= (int) $tk['id'] ?>">
                                                Sửa
                                            </a>

                                            <?php if ($tk['ten_dang_nhap'] !== ($_SESSION['username'] ?? '')): ?>
                                                <form method="POST" action="tai_khoan.php" class="delete-form">
                                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="hanh_dong" value="xoa">
                                                    <input type="hidden" name="id" value="<?= (int) $tk['id'] ?>">
                                                    <button type="submit" class="btn-delete">Xóa</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="self-account">Tài khoản của bạn</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <footer class="page-footer">
                StudentHub · Hệ thống quản lý học phần
            </footer>
        </div>
    </main>
</div>
<script src="../../../assets/js/quantrivien.js"></script>
</body>
</html>
