<?php
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function tenVaiTro($role)
{
    switch ($role) {
        case 'sinh_vien':
            return 'Sinh viên';

        case 'giang_vien':
            return 'Giảng viên';

        case 'admin':
            return 'Quản trị viên';

        default:
            return 'Chưa xác định';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý tài khoản | StudentHub</title>

    <link rel="stylesheet" href="../../../assets/css/quantrivien.css">
</head>

<body>

<div class="layout">

    <!-- THANH MENU BÊN TRÁI -->
    <?php
    $activeMenu = 'tai_khoan';
    require __DIR__ . '/../../Views/layouts/admin_sidebar.php';
    ?>

    <main class="main">

        <!-- THANH ĐẦU TRANG -->
        <header class="topbar">

            <div>
                <div class="breadcrumb">
                    Quản trị hệ thống / Tài khoản
                </div>

                <h1>Quản lý tài khoản</h1>

                <p>
                    Quản lý tài khoản sinh viên, giảng viên và quản trị viên.
                </p>
            </div>

            <div class="admin-profile">

                <div class="avatar">AD</div>

                <div class="profile-info">
                    <strong>
                        <?= e($_SESSION['ho_ten'] ?? $_SESSION['username'] ?? 'Quản trị viên') ?>
                    </strong>

                    <span>Quản trị viên</span>
                </div>

            </div>

        </header>

        <div class="content">

            <!-- THÔNG BÁO -->
            <?php if ($thongBao !== ''): ?>

                <div class="<?= $loaiThongBao === 'error' ? 'error-message' : 'account-notice' ?>">
                    <?= e($thongBao) ?>
                </div>

            <?php endif; ?>

            <?php if ($loi !== ''): ?>

                <div class="error-message">
                    <?= e($loi) ?>
                </div>

            <?php endif; ?>

            <!-- TỔNG TÀI KHOẢN -->
            <section class="account-summary">

                <div>
                    <span class="summary-label">
                        Tổng tài khoản trong hệ thống
                    </span>

                    <strong><?= $tongTaiKhoan ?></strong>

                    <p>
                        Danh sách tài khoản được lưu trong StudentHub.
                    </p>
                </div>

                <div class="summary-icon">♙</div>

            </section>

            <!-- FORM THÊM / SỬA -->
            <section class="section">

                <div class="section-heading">

                    <div>
                        <h2>
                            <?= !empty($duLieuSua['id'])
                                ? 'Cập nhật tài khoản'
                                : 'Thêm tài khoản mới' ?>
                        </h2>

                        <p>
                            Điền thông tin bên dưới để
                            <?= !empty($duLieuSua['id'])
                                ? 'cập nhật'
                                : 'tạo' ?>
                            tài khoản.
                        </p>
                    </div>

                </div>

                <form
                    method="POST"
                    action="tai_khoan.php"
                    class="account-form"
                    autocomplete="off"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION['csrf_token']) ?>"
                    >

                    <input
                        type="hidden"
                        name="hanh_dong"
                        value="<?= !empty($duLieuSua['id']) ? 'sua' : 'them' ?>"
                    >

                    <input
                        type="hidden"
                        name="id"
                        value="<?= e($duLieuSua['id']) ?>"
                    >

                    <div class="form-grid">

                        <!-- TÊN ĐĂNG NHẬP -->
                        <div class="form-field">

                            <label for="ten_dang_nhap">
                                Tên đăng nhập <span>*</span>
                            </label>

                            <input
                                id="ten_dang_nhap"
                                name="ten_dang_nhap"
                                required
                                maxlength="30"
                                pattern="[A-Za-z0-9_]{3,30}"
                                value="<?= e($duLieuSua['ten_dang_nhap']) ?>"
                                placeholder="Ví dụ: sv001"
                            >

                        </div>

                        <!-- HỌ TÊN -->
                        <div class="form-field">

                            <label for="ho_ten">
                                Họ và tên <span>*</span>
                            </label>

                            <input
                                id="ho_ten"
                                name="ho_ten"
                                required
                                maxlength="150"
                                value="<?= e($duLieuSua['ho_ten']) ?>"
                                placeholder="Nhập họ và tên"
                            >

                        </div>

                        <!-- EMAIL -->
                        <div class="form-field">

                            <label for="email">
                                Email
                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                maxlength="150"
                                value="<?= e($duLieuSua['email'] ?? '') ?>"
                                placeholder="example@email.com"
                            >

                        </div>

                        <!-- SỐ ĐIỆN THOẠI -->
                        <div class="form-field">

                            <label for="so_dien_thoai">
                                Số điện thoại
                            </label>

                            <input
                                id="so_dien_thoai"
                                name="so_dien_thoai"
                                type="tel"
                                inputmode="numeric"
                                maxlength="10"
                                pattern="[0-9]{10}"
                                title="Số điện thoại phải gồm đúng 10 chữ số."
                                value="<?= e($duLieuSua['so_dien_thoai'] ?? '') ?>"
                                placeholder="Nhập số điện thoại"
                            >

                        </div>

                        <!-- VAI TRÒ -->
                        <div class="form-field">

                            <label for="vai_tro">
                                Vai trò <span>*</span>
                            </label>

                            <select id="vai_tro" name="vai_tro" required>

                                <option
                                    value="sinh_vien"
                                    <?= $duLieuSua['vai_tro'] === 'sinh_vien' ? 'selected' : '' ?>
                                >
                                    Sinh viên
                                </option>

                                <option
                                    value="giang_vien"
                                    <?= $duLieuSua['vai_tro'] === 'giang_vien' ? 'selected' : '' ?>
                                >
                                    Giảng viên
                                </option>

                                <option
                                    value="admin"
                                    <?= $duLieuSua['vai_tro'] === 'admin' ? 'selected' : '' ?>
                                >
                                    Quản trị viên
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- NÚT THAO TÁC -->
                    <div class="form-actions">

                        <button type="submit" class="btn-primary">

                            <?= !empty($duLieuSua['id'])
                                ? 'Lưu thay đổi'
                                : 'Thêm tài khoản' ?>

                        </button>

                        <?php if (!empty($duLieuSua['id'])): ?>

                            <a href="tai_khoan.php" class="btn-secondary">
                                Hủy sửa
                            </a>

                        <?php else: ?>

                            <button type="reset" class="btn-secondary">
                                Nhập lại
                            </button>

                        <?php endif; ?>

                    </div>

                    <?php if (empty($duLieuSua['id'])): ?>

                        <p class="password-help">
                            Lưu ý: Tài khoản mới sẽ được cấp mật khẩu mặc định là 123456.
                        </p>

                    <?php endif; ?>

                </form>

            </section>

            <!-- DANH SÁCH TÀI KHOẢN -->
            <section class="section">

                <div class="section-heading">

                    <div>
                        <h2>Danh sách tài khoản</h2>

                        <p>
                            Tra cứu và quản lý các tài khoản đang có.
                        </p>
                    </div>

                </div>

                <!-- TÌM KIẾM VÀ LỌC -->
                <form
                    method="GET"
                    action="tai_khoan.php"
                    class="account-filters"
                >

                    <input
                        type="search"
                        name="q"
                        value="<?= e($tuKhoa) ?>"
                        placeholder="Tìm tên đăng nhập, họ tên, email..."
                    >

                    <select name="vai_tro" aria-label="Lọc theo vai trò">

                        <option value="">
                            Tất cả vai trò
                        </option>

                        <option
                            value="sinh_vien"
                            <?= $locVaiTro === 'sinh_vien' ? 'selected' : '' ?>
                        >
                            Sinh viên
                        </option>

                        <option
                            value="giang_vien"
                            <?= $locVaiTro === 'giang_vien' ? 'selected' : '' ?>
                        >
                            Giảng viên
                        </option>

                        <option
                            value="admin"
                            <?= $locVaiTro === 'admin' ? 'selected' : '' ?>
                        >
                            Quản trị viên
                        </option>

                    </select>

                    <button type="submit" class="btn-primary">
                        Tìm kiếm
                    </button>

                    <a href="tai_khoan.php" class="btn-secondary">
                        Làm mới
                    </a>

                </form>

                <!-- BẢNG TÀI KHOẢN -->
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
                                <td colspan="7" class="empty">
                                    Không tìm thấy tài khoản nào.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($danhSach as $tk): ?>

                                <tr>

                                    <td>
                                        <?= (int) $tk['id'] ?>
                                    </td>

                                    <td>
                                        <?= e($tk['ten_dang_nhap']) ?>
                                    </td>

                                    <td>
                                        <?= e($tk['ho_ten']) ?>
                                    </td>

                                    <td>
                                        <?= e($tk['email'] ?? '') ?>
                                    </td>

                                    <td>
                                        <?= e($tk['so_dien_thoai'] ?? '') ?>
                                    </td>

                                    <td>

                                        <span class="account-role role-<?= e($tk['vai_tro']) ?>">
                                            <?= e(tenVaiTro($tk['vai_tro'])) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <div class="action-buttons">

                                            <a
                                                class="btn-edit"
                                                href="tai_khoan.php?sua=<?= (int) $tk['id'] ?>"
                                            >
                                                Sửa
                                            </a>

                                            <?php if ($tk['ten_dang_nhap'] !== ($_SESSION['username'] ?? '')): ?>

                                                <form
                                                    method="POST"
                                                    action="tai_khoan.php"
                                                    class="delete-form"
                                                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài khoản này không?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e($_SESSION['csrf_token']) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="hanh_dong"
                                                        value="xoa"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= (int) $tk['id'] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn-delete"
                                                    >
                                                        Xóa
                                                    </button>

                                                </form>

                                            <?php else: ?>

                                                <span class="self-account">
                                                    Tài khoản của bạn
                                                </span>

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
