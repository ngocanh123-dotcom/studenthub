<?php
session_start();

// Kiểm tra đăng nhập Admin
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

// Tạo CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$thongBao = '';
$loaiThongBao = 'success';
$loi = '';

// Lấy từ khóa tìm kiếm
$tuKhoa = trim($_GET['q'] ?? '');

// Biến lưu dữ liệu khi Sửa
$duLieuSua = [
    'id' => '',
    'ten_hoc_ky' => '',
    'ngay_bat_dau' => '',
    'ngay_ket_thuc' => '',
    'thoi_gian_mo_dk' => '',
    'thoi_gian_dong_dk' => '',
    'trang_thai_dang_ky' => 1
];

// XỬ LÝ THÊM, SỬA, XÓA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfNhan = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfNhan)) {
        $thongBao = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.';
        $loaiThongBao = 'error';
    } else {
        $hanhDong = $_POST['hanh_dong'] ?? '';

        try {
            if ($hanhDong === 'them' || $hanhDong === 'sua') {
                $id = (int) ($_POST['id'] ?? 0);
                $tenHocKy = trim($_POST['ten_hoc_ky'] ?? '');
                $ngayBatDau = trim($_POST['ngay_bat_dau'] ?? '');
                $ngayKetThuc = trim($_POST['ngay_ket_thuc'] ?? '');
                $thoiGianMo = trim($_POST['thoi_gian_mo_dk'] ?? '');
                $thoiGianDong = trim($_POST['thoi_gian_dong_dk'] ?? '');
                $trangThai = (int) ($_POST['trang_thai_dang_ky'] ?? 1);

                $duLieuSua = [
                    'id' => $id, 'ten_hoc_ky' => $tenHocKy, 'ngay_bat_dau' => $ngayBatDau,
                    'ngay_ket_thuc' => $ngayKetThuc, 'thoi_gian_mo_dk' => $thoiGianMo,
                    'thoi_gian_dong_dk' => $thoiGianDong, 'trang_thai_dang_ky' => $trangThai
                ];

                // Validate
                if ($tenHocKy === '') throw new RuntimeException('Vui lòng nhập tên học kỳ.');
                if ($ngayBatDau === '' || $ngayKetThuc === '') throw new RuntimeException('Vui lòng nhập ngày bắt đầu và kết thúc.');
                if (strtotime($ngayBatDau) > strtotime($ngayKetThuc)) throw new RuntimeException('Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày kết thúc.');
                if ($thoiGianMo !== '' && $thoiGianDong !== '' && strtotime($thoiGianMo) > strtotime($thoiGianDong)) throw new RuntimeException('Thời gian mở đăng ký phải trước thời gian đóng.');

                if ($hanhDong === 'them') {
                    $stmt = $conn->prepare("
                        INSERT INTO hoc_ky (ten_hoc_ky, ngay_bat_dau, ngay_ket_thuc, thoi_gian_mo_dk, thoi_gian_dong_dk, trang_thai_dang_ky) 
                        VALUES (:ten, :bat_dau, :ket_thuc, :mo_dk, :dong_dk, :trang_thai)
                    ");
                    $stmt->execute([
                        ':ten' => $tenHocKy, ':bat_dau' => $ngayBatDau, ':ket_thuc' => $ngayKetThuc,
                        ':mo_dk' => $thoiGianMo ?: null, ':dong_dk' => $thoiGianDong ?: null, ':trang_thai' => $trangThai
                    ]);
                    $_SESSION['semester_notice'] = 'Thêm học kỳ thành công.';
                } else {
                    if ($id <= 0) throw new RuntimeException('Học kỳ cần sửa không hợp lệ.');
                    $stmt = $conn->prepare("
                        UPDATE hoc_ky 
                        SET ten_hoc_ky = :ten, ngay_bat_dau = :bat_dau, ngay_ket_thuc = :ket_thuc, 
                            thoi_gian_mo_dk = :mo_dk, thoi_gian_dong_dk = :dong_dk, trang_thai_dang_ky = :trang_thai
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':ten' => $tenHocKy, ':bat_dau' => $ngayBatDau, ':ket_thuc' => $ngayKetThuc,
                        ':mo_dk' => $thoiGianMo ?: null, ':dong_dk' => $thoiGianDong ?: null, ':trang_thai' => $trangThai, ':id' => $id
                    ]);
                    $_SESSION['semester_notice'] = 'Cập nhật học kỳ thành công.';
                }
                
                $_SESSION['semester_notice_type'] = 'success';
                header('Location: hoc_ky.php');
                exit;
            }

            if ($hanhDong === 'xoa') {
                $idXoa = (int) ($_POST['id'] ?? 0);
                if ($idXoa <= 0) throw new RuntimeException('Mã học kỳ không hợp lệ.');

                // Kiểm tra xem có Lớp học phần nào thuộc học kỳ này không
                $stmt = $conn->prepare("SELECT COUNT(*) FROM lop_hoc_phan WHERE hoc_ky_id = :id");
                $stmt->execute([':id' => $idXoa]);
                if ((int) $stmt->fetchColumn() > 0) {
                    throw new RuntimeException('Không thể xóa! Đã có lớp học phần thuộc học kỳ này. Vui lòng xóa/chuyển các lớp đó trước.');
                }

                $stmt = $conn->prepare("DELETE FROM hoc_ky WHERE id = :id");
                $stmt->execute([':id' => $idXoa]);

                $_SESSION['semester_notice'] = 'Xóa học kỳ thành công.';
                $_SESSION['semester_notice_type'] = 'success';
                header('Location: hoc_ky.php');
                exit;
            }
        } catch (RuntimeException $e) {
            $thongBao = $e->getMessage();
            $loaiThongBao = 'error';
        } catch (PDOException $e) {
            $thongBao = 'Lỗi CSDL: ' . $e->getMessage();
            $loaiThongBao = 'error';
        }
    }
}

// Lấy thông báo (nếu có)
if (!empty($_SESSION['semester_notice'])) {
    $thongBao = $_SESSION['semester_notice'];
    $loaiThongBao = $_SESSION['semester_notice_type'] ?? 'success';
    unset($_SESSION['semester_notice'], $_SESSION['semester_notice_type']);
}

// Lấy dữ liệu nếu đang bấm nút Sửa
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['sua'])) {
    $idSua = (int) $_GET['sua'];
    if ($idSua > 0) {
        $stmt = $conn->prepare("SELECT * FROM hoc_ky WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $idSua]);
        $hocKySua = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($hocKySua) $duLieuSua = $hocKySua;
    }
}

// Lấy danh sách học kỳ (có tìm kiếm)
$sql = "SELECT * FROM hoc_ky WHERE 1=1";
$thamSo = [];
if ($tuKhoa !== '') {
    $sql .= " AND ten_hoc_ky LIKE :q";
    $thamSo[':q'] = '%' . $tuKhoa . '%';
}
$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($thamSo);
$danhSach = $stmt->fetchAll(PDO::FETCH_ASSOC);
$tongHocKy = count($danhSach);

function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý học kỳ | StudentHub</title>
    <link rel="stylesheet" href="../../../assets/css/quantrivien.css">
</head>
<body>
<div class="layout">
    <!-- SIDEBAR -->
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
            <a class="menu-link" href="index.php"><span class="menu-icon">⌂</span> Tổng quan</a>
            <a class="menu-link" href="tai_khoan.php"><span class="menu-icon">♙</span> Quản lý tài khoản</a>
            <a class="menu-link" href="hoc_phan.php"><span class="menu-icon">▤</span> Quản lý học phần</a>
            <a class="menu-link active" href="hoc_ky.php"><span class="menu-icon">▣</span> Quản lý học kỳ</a>
            <a class="menu-link" href="lop_hoc_phan.php"><span class="menu-icon">▦</span> Quản lý lớp học phần</a>
        </nav>
        <div class="sidebar-bottom">
            <a class="menu-link logout" href="../../../app/auth/logout.php"><span class="menu-icon">↪</span> Đăng xuất</a>
            <div class="sidebar-note"><span class="status-dot"></span> Hệ thống đang hoạt động</div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main">
        <header class="topbar">
            <div>
                <div class="breadcrumb">Quản trị hệ thống / Học kỳ</div>
                <h1>Quản lý học kỳ</h1>
                <p>Quản lý thời gian đăng ký và trạng thái các học kỳ trong hệ thống.</p>
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

            <section class="account-summary">
                <div>
                    <span class="summary-label">Tổng số học kỳ</span>
                    <strong><?= $tongHocKy ?></strong>
                    <p>Các học kỳ đang được quản lý.</p>
                </div>
                <div class="summary-icon">▣</div>
            </section>

            <!-- FORM THÊM/SỬA -->
            <section class="section">
                <div class="section-heading">
                    <div>
                        <h2><?= !empty($duLieuSua['id']) ? 'Cập nhật học kỳ' : 'Thêm học kỳ mới' ?></h2>
                        <p>Nhập thông tin thời gian và trạng thái đăng ký.</p>
                    </div>
                </div>

                <form method="POST" action="hoc_ky.php" class="account-form">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="hanh_dong" value="<?= !empty($duLieuSua['id']) ? 'sua' : 'them' ?>">
                    <input type="hidden" name="id" value="<?= e($duLieuSua['id']) ?>">

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="ten_hoc_ky">Tên học kỳ <span>*</span></label>
                            <input id="ten_hoc_ky" name="ten_hoc_ky" required maxlength="100" value="<?= e($duLieuSua['ten_hoc_ky']) ?>" placeholder="Ví dụ: Học kỳ 1 năm 2026-2027">
                        </div>
                        <div class="form-field">
                            <label for="ngay_bat_dau">Ngày bắt đầu <span>*</span></label>
                            <input type="date" id="ngay_bat_dau" name="ngay_bat_dau" required value="<?= e($duLieuSua['ngay_bat_dau']) ?>">
                        </div>
                        <div class="form-field">
                            <label for="ngay_ket_thuc">Ngày kết thúc <span>*</span></label>
                            <input type="date" id="ngay_ket_thuc" name="ngay_ket_thuc" required value="<?= e($duLieuSua['ngay_ket_thuc']) ?>">
                        </div>
                        <div class="form-field">
                            <label for="thoi_gian_mo_dk">Thời gian mở đăng ký</label>
                            <input type="datetime-local" id="thoi_gian_mo_dk" name="thoi_gian_mo_dk" value="<?= e($duLieuSua['thoi_gian_mo_dk'] ? date('Y-m-d\TH:i', strtotime($duLieuSua['thoi_gian_mo_dk'])) : '') ?>">
                        </div>
                        <div class="form-field">
                            <label for="thoi_gian_dong_dk">Thời gian đóng đăng ký</label>
                            <input type="datetime-local" id="thoi_gian_dong_dk" name="thoi_gian_dong_dk" value="<?= e($duLieuSua['thoi_gian_dong_dk'] ? date('Y-m-d\TH:i', strtotime($duLieuSua['thoi_gian_dong_dk'])) : '') ?>">
                        </div>
                        <div class="form-field">
                            <label for="trang_thai_dang_ky">Trạng thái đăng ký <span>*</span></label>
                            <select id="trang_thai_dang_ky" name="trang_thai_dang_ky" required>
                                <option value="1" <?= $duLieuSua['trang_thai_dang_ky'] == 1 ? 'selected' : '' ?>>Đang mở (Cho phép sinh viên ĐK)</option>
                                <option value="0" <?= $duLieuSua['trang_thai_dang_ky'] == 0 ? 'selected' : '' ?>>Đang đóng (Chặn sinh viên ĐK)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary"><?= !empty($duLieuSua['id']) ? 'Lưu thay đổi' : 'Thêm học kỳ' ?></button>
                        <?php if (!empty($duLieuSua['id'])): ?>
                            <a href="hoc_ky.php" class="btn-secondary">Hủy sửa</a>
                        <?php else: ?>
                            <button type="reset" class="btn-secondary">Nhập lại</button>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <!-- BẢNG DANH SÁCH -->
            <section class="section">
                <div class="section-heading">
                    <div>
                        <h2>Danh sách học kỳ</h2>
                        <p>Tra cứu và quản lý các học kỳ.</p>
                    </div>
                </div>

                <form method="GET" action="hoc_ky.php" class="account-filters">
                    <input type="search" name="q" value="<?= e($tuKhoa) ?>" placeholder="Tìm theo tên học kỳ...">
                    <button type="submit" class="btn-primary">Tìm kiếm</button>
                    <a href="hoc_ky.php" class="btn-secondary">Làm mới</a>
                </form>

                <div class="account-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên học kỳ</th>
                                <th>Thời gian diễn ra</th>
                                <th>Thời gian ĐK</th>
                                <th>Trạng thái ĐK</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($danhSach)): ?>
                                <tr><td colspan="6" class="empty">Chưa có học kỳ nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($danhSach as $hk): ?>
                                    <tr>
                                        <td><?= (int) $hk['id'] ?></td>
                                        <td><strong><?= e($hk['ten_hoc_ky']) ?></strong></td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($hk['ngay_bat_dau'])) ?> - 
                                            <?= date('d/m/Y', strtotime($hk['ngay_ket_thuc'])) ?>
                                        </td>
                                        <td>
                                            <?php if ($hk['thoi_gian_mo_dk'] && $hk['thoi_gian_dong_dk']): ?>
                                                Mở: <?= date('d/m/Y H:i', strtotime($hk['thoi_gian_mo_dk'])) ?><br>
                                                Đóng: <?= date('d/m/Y H:i', strtotime($hk['thoi_gian_dong_dk'])) ?>
                                            <?php else: ?>
                                                <span style="color:#64748b; font-style:italic;">Chưa thiết lập</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($hk['trang_thai_dang_ky'] == 1): ?>
                                                <span class="account-role" style="background:#dcfce7; color:#166534;">Đang mở</span>
                                            <?php else: ?>
                                                <span class="account-role" style="background:#fee2e2; color:#991b1b;">Đang đóng</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a class="btn-edit" href="hoc_ky.php?sua=<?= (int) $hk['id'] ?>">Sửa</a>
                                                <form method="POST" action="hoc_ky.php" class="delete-form" onsubmit="return confirm('Bạn có chắc muốn xóa học kỳ này? Thao tác không thể hoàn tác.');">
                                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="hanh_dong" value="xoa">
                                                    <input type="hidden" name="id" value="<?= (int) $hk['id'] ?>">
                                                    <button type="submit" class="btn-delete">Xóa</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <footer class="page-footer">StudentHub · Hệ thống quản lý học phần</footer>
        </div>
    </main>
</div>
</body>
</html>