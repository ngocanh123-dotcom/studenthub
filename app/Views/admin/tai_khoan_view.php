<?php
/**
 * app/Views/admin/tai_khoan_view.php
 * Giao diện Quản lý tài khoản (Admin) - StudentHub
 * Kiến trúc: MVC Chuẩn công nghiệp - View sạch
 */

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('tenVaiTro')) {
    function tenVaiTro($role)
    {
        switch ($role) {
            case 'sinh_vien': return 'Sinh viên';
            case 'giang_vien': return 'Giảng viên';
            case 'admin': return 'Quản trị viên';
            default: return 'Chưa xác định';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản | StudentHub</title>

    <!-- CSS nằm trong /assets/css -->
    <link rel="stylesheet" href="/studenthub/assets/css/quantrivien.css">
</head>
<body>
<div class="layout">

    <!-- THANH MENU BÊN TRÁI -->
    <?php
    $activeMenu = 'tai_khoan';
    require __DIR__ . '/../layouts/admin_sidebar.php';
    ?>

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
            <?php if (!empty($thongBao)): ?>
                <div class="<?= ($loaiThongBao ?? 'success') === 'error' ? 'error-message' : 'account-notice' ?>">
                    <?= e($thongBao) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($loi)): ?>
                <div class="error-message"><?= e($loi) ?></div>
            <?php endif; ?>

            <section class="account-summary">
                <div>
                    <span class="summary-label">Tổng tài khoản trong hệ thống</span>
                    <strong><?= (int)($tongTaiKhoan ?? 0) ?></strong>
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

                <form method="POST" action="/studenthub/public/index.php?route=admin_tai_khoan.php" class="account-form" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="hanh_dong" value="<?= !empty($duLieuSua['id']) ? 'sua' : 'them' ?>">
                    <input type="hidden" name="id" value="<?= e($duLieuSua['id'] ?? '') ?>">

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="ten_dang_nhap">Tên đăng nhập <span>*</span></label>
                            <input
                                id="ten_dang_nhap"
                                name="ten_dang_nhap"
                                required
                                maxlength="30"
                                pattern="[A-Za-z0-9_]{3,30}"
                                value="<?= e($duLieuSua['ten_dang_nhap'] ?? '') ?>"
                                placeholder="Ví dụ: sv001"
                            >
                        </div>

                        <div class="form-field">
                            <label for="ho_ten">Họ và tên <span>*</span></label>
                            <input
                                id="ho_ten"
                                name="ho_ten"
                                type="text"
                                required
                                maxlength="150"
                                minlength="5"
                                value="<?= e($duLieuSua['ho_ten'] ?? '') ?>"
                                placeholder="Ví dụ: Nguyễn Thu Trang"
                                title="Vui lòng nhập đầy đủ họ và tên, tối thiểu 2 từ."
                            >
                            <small class="password-help">
                                Vui lòng nhập đầy đủ họ và tên, không nhập riêng tên hoặc họ.
                            </small>
                        </div>

                        <div class="form-field">
                            <label for="email">Email <?= empty($duLieuSua['id']) ? '<span>*</span>' : '' ?></label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                <?= empty($duLieuSua['id']) ? 'required' : '' ?>
                                maxlength="150"
                                value="<?= e($duLieuSua['email'] ?? '') ?>"
                                placeholder="example@email.com"
                            >
                        </div>

                        <div class="form-field">
                            <label for="so_dien_thoai">Số điện thoại <?= empty($duLieuSua['id']) ? '<span>*</span>' : '' ?></label>
                            <input
                                id="so_dien_thoai"
                                name="so_dien_thoai"
                                type="tel"
                                inputmode="numeric"
                                pattern="[0-9]{10}"
                                minlength="10"
                                maxlength="10"
                                <?= empty($duLieuSua['id']) ? 'required' : '' ?>
                                value="<?= e($duLieuSua['so_dien_thoai'] ?? '') ?>"
                                placeholder="Nhập đúng 10 chữ số"
                                title="Số điện thoại phải gồm đúng 10 chữ số."
                            >
                        </div>

                        <div class="form-field">
                            <label for="vai_tro">Vai trò <span>*</span></label>
                            <select id="vai_tro" name="vai_tro" required>
                                <option value="sinh_vien" <?= ($duLieuSua['vai_tro'] ?? '') === 'sinh_vien' ? 'selected' : '' ?>>Sinh viên</option>
                                <option value="giang_vien" <?= ($duLieuSua['vai_tro'] ?? '') === 'giang_vien' ? 'selected' : '' ?>>Giảng viên</option>
                                <option value="admin" <?= ($duLieuSua['vai_tro'] ?? '') === 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
                            </select>
                        </div>

                    </div>

                    <?php if (empty($duLieuSua['id'])): ?>
                        <p class="password-help">Mật khẩu ban đầu của tài khoản mới là <strong>123456</strong>.</p>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <?= !empty($duLieuSua['id']) ? 'Lưu thay đổi' : 'Thêm tài khoản' ?>
                        </button>

                        <?php if (!empty($duLieuSua['id'])): ?>
                            <a href="/studenthub/public/index.php?route=admin_tai_khoan.php" class="btn-secondary">Hủy sửa</a>
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

                <form method="GET" action="/studenthub/public/index.php" class="account-filters">
                    <input type="hidden" name="route" value="admin_tai_khoan.php">
                    <input
                        type="search"
                        name="q"
                        value="<?= e($tuKhoa ?? '') ?>"
                        placeholder="Tìm tên đăng nhập, họ tên, email..."
                    >

                    <select name="vai_tro" aria-label="Lọc theo vai trò">
                        <option value="">Tất cả vai trò</option>
                        <option value="sinh_vien" <?= ($locVaiTro ?? '') === 'sinh_vien' ? 'selected' : '' ?>>Sinh viên</option>
                        <option value="giang_vien" <?= ($locVaiTro ?? '') === 'giang_vien' ? 'selected' : '' ?>>Giảng viên</option>
                        <option value="admin" <?= ($locVaiTro ?? '') === 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
                    </select>

                    <button type="submit" class="btn-primary">Tìm kiếm</button>
                    <a href="/studenthub/public/index.php?route=admin_tai_khoan.php" class="btn-secondary">Làm mới</a>
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
                                            <a
                                                class="btn-edit"
                                                href="/studenthub/public/index.php?route=admin_tai_khoan.php&sua=<?= (int) $tk['id'] ?>"
                                            >
                                                Sửa
                                            </a>

                                            <?php if ($tk['ten_dang_nhap'] !== ($_SESSION['username'] ?? '')): ?>
                                                <form method="POST" action="/studenthub/public/index.php?route=admin_tai_khoan.php" class="delete-form">
                                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
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

<script src="/studenthub/assets/js/quantrivien.js"></script>
</body>
</html>
