<?php
// app/Views/admin/hoc_ky.php
function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý học kỳ | StudentHub</title>
    <link rel="stylesheet" href="/studenthub/assets/css/quantrivien.css">
    <style>
        /* Giữ nguyên toàn bộ CSS bạn đã có */
        .banner-highlight { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); border-radius: 16px; padding: 30px; color: white; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2); }
        .banner-highlight h2 { margin: 0 0 10px 0; font-size: 28px; font-weight: 800; color: #fff; }
        .banner-highlight p { margin: 0; font-size: 15px; color: #e0e7ff; }
        .section-card { background: #fff; border-radius: 16px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); margin-bottom: 30px; border: 1px solid #e2e8f0; }
        .section-card h3 { margin-top: 0; font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-field label { display: block; font-weight: 600; margin-bottom: 8px; color: #475569; font-size: 14px; }
        .form-field input, .form-field select { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 14px; transition: 0.2s; }
        .form-field input:focus, .form-field select:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .table-modern { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .table-modern th { padding: 15px; font-weight: 700; color: #475569; font-size: 13px; text-transform: uppercase; text-align: left; border-bottom: 2px solid #e2e8f0; }
        .table-modern td { padding: 15px; background: #fff; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        .table-modern tr td:first-child { border-left: 1px solid #e2e8f0; border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-modern tr td:last-child { border-right: 1px solid #e2e8f0; border-top-right-radius: 10px; border-bottom-right-radius: 10px; }
        .table-modern tbody tr:hover td { background: #f8fafc; }
        .btn-view-class { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-view-class:hover { background: #dbeafe; transform: translateY(-1px); }
        .btn-toggle-status { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; border: none; cursor: pointer; transition: 0.2s; }
        .btn-toggle-on { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .btn-toggle-off { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .detail-row { background: #f8fafc; }
        .detail-row td { padding: 0; border: none; }
        .detail-container { padding: 20px; border: 1px dashed #cbd5e1; border-radius: 12px; margin: 10px 0; background: #fff; }
        .detail-table { width: 100%; font-size: 13px; border-collapse: collapse; }
        .detail-table th, .detail-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; text-align: left; }
        .detail-table th { background: #f8fafc; color: #64748b; font-weight: 600; }
    </style>
</head>
<body>
<div class="layout">
    <!-- SỬ DỤNG SIDEBAR CHUNG -->
    <?php require_once __DIR__ . '/../layouts/admin_sidebar.php'; ?>

    <main class="main">
        <header class="topbar">
            <div><div class="breadcrumb">Quản trị hệ thống / Học kỳ</div><h1>Quản lý học kỳ</h1><p>Quản lý thời gian và trạng thái đăng ký tín chỉ.</p></div>
            <div class="admin-profile"><div class="avatar">AD</div><div class="profile-info"><strong><?= e($_SESSION['ho_ten'] ?? 'Quản trị viên') ?></strong><span>Quản trị viên</span></div></div>
        </header>

        <div class="content">
            <div class="banner-highlight">
                <div>
                    <h2>Hệ thống Học kỳ</h2>
                    <p>Hiện đang quản lý <strong><?= $tongHocKy ?></strong> học kỳ trong hệ thống đào tạo.</p>
                </div>
                <div style="font-size: 60px; opacity: 0.2; font-weight: 900;">SH</div>
            </div>

            <?php if ($thongBao !== ''): ?>
                <div class="<?= $loaiThongBao === 'error' ? 'error-message' : 'account-notice' ?>" style="margin-bottom: 20px;"><?= e($thongBao) ?></div>
            <?php endif; ?>

            <!-- FORM THÊM / SỬA -->
            <div class="section-card">
                <h3><?= !empty($duLieuSua['id']) ? '✏️ Cập nhật học kỳ' : '➕ Thêm học kỳ mới' ?></h3>
                <!-- LƯU Ý ĐỔI ACTION VỀ admin_hoc_ky.php -->
                <form method="POST" action="admin_hoc_ky.php">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="hanh_dong" value="<?= !empty($duLieuSua['id']) ? 'sua' : 'them' ?>">
                    <input type="hidden" name="id" value="<?= e($duLieuSua['id']) ?>">
                    
                    <div class="form-grid">
                        <div class="form-field"><label>Tên học kỳ *</label><input type="text" name="ten_hoc_ky" required placeholder="VD: Học kỳ 1 năm 2026-2027" value="<?= e($duLieuSua['ten_hoc_ky']) ?>"></div>
                        <div class="form-field"><label>Trạng thái Đăng ký *</label>
                            <select name="trang_thai_dang_ky">
                                <option value="1" <?= $duLieuSua['trang_thai_dang_ky'] == 1 ? 'selected' : '' ?>>🟢 Mở cổng đăng ký (Sinh viên được ĐK)</option>
                                <option value="0" <?= $duLieuSua['trang_thai_dang_ky'] == 0 ? 'selected' : '' ?>>🔴 Đóng cổng đăng ký (Chặn Sinh viên)</option>
                            </select>
                        </div>
                        <div class="form-field"><label>Ngày bắt đầu *</label><input type="date" name="ngay_bat_dau" required value="<?= e($duLieuSua['ngay_bat_dau']) ?>"></div>
                        <div class="form-field"><label>Ngày kết thúc *</label><input type="date" name="ngay_ket_thuc" required value="<?= e($duLieuSua['ngay_ket_thuc']) ?>"></div>
                        <div class="form-field"><label>Thời gian mở ĐK</label><input type="datetime-local" name="thoi_gian_mo_dk" value="<?= e($duLieuSua['thoi_gian_mo_dk'] ? date('Y-m-d\TH:i', strtotime($duLieuSua['thoi_gian_mo_dk'])) : '') ?>"></div>
                        <div class="form-field"><label>Thời gian đóng ĐK</label><input type="datetime-local" name="thoi_gian_dong_dk" value="<?= e($duLieuSua['thoi_gian_dong_dk'] ? date('Y-m-d\TH:i', strtotime($duLieuSua['thoi_gian_dong_dk'])) : '') ?>"></div>
                    </div>
                    
                    <div style="margin-top: 25px; display: flex; gap: 15px;">
                        <button type="submit" class="btn-primary" style="padding: 12px 25px; font-size: 15px; border-radius: 8px;">
                            <?= !empty($duLieuSua['id']) ? 'Lưu Thay Đổi' : 'Tạo Học Kỳ' ?>
                        </button>
                        <a href="admin_hoc_ky.php" class="btn-secondary" style="padding: 12px 25px; font-size: 15px; border-radius: 8px; text-decoration: none; background: #f1f5f9; color: #475569;">Hủy</a>
                    </div>
                </form>
            </div>

            <!-- DANH SÁCH HỌC KỲ -->
            <div class="section-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
                    <h3 style="margin: 0; border: none; padding: 0;">Danh sách Học kỳ</h3>
                    <form method="GET" action="admin_hoc_ky.php" style="display: flex; gap: 10px;">
                        <input type="search" name="q" value="<?= e($tuKhoa) ?>" placeholder="Tìm tên học kỳ..." style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none;">
                        <button type="submit" class="btn-primary" style="padding: 10px 15px; border-radius: 8px;">Tìm</button>
                    </form>
                </div>

                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên học kỳ</th>
                            <th>Thời gian diễn ra</th>
                            <th style="text-align: center;">Lớp đã mở</th>
                            <th>Cổng Đăng ký</th>
                            <th style="text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($danhSach)): ?>
                            <tr><td colspan="6" style="text-align: center; color: #64748b;">Chưa có dữ liệu học kỳ.</td></tr>
                        <?php else: ?>
                            <?php foreach ($danhSach as $hk): ?>
                                <tr>
                                    <td><strong>#<?= (int) $hk['id'] ?></strong></td>
                                    <td style="font-weight: 600; color: #0f172a;"><?= e($hk['ten_hoc_ky']) ?></td>
                                    <td>
                                        <div style="font-size: 13px;">BĐ: <?= date('d/m/Y', strtotime($hk['ngay_bat_dau'])) ?></div>
                                        <div style="font-size: 13px; color: #64748b;">KT: <?= date('d/m/Y', strtotime($hk['ngay_ket_thuc'])) ?></div>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; gap: 5px; justify-content: center;">
                                            <button type="button" class="btn-view-class" onclick="toggleDetail(<?= $hk['id'] ?>)">
                                                👁️ <?= (int) $hk['so_lop'] ?> lớp
                                            </button>
                                            <!-- Link In PDF trỏ về public -->
                                            <a href="/studenthub/public/admin_in_danh_sach_lop.php?hoc_ky_id=<?= $hk['id'] ?>" target="_blank" class="btn-view-class" style="background: #f1f5f9; color: #0f172a; border-color: #cbd5e1; text-decoration: none;" title="In danh sách lớp ra PDF">
                                                🖨️ In
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST" action="admin_hoc_ky.php" style="margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="hanh_dong" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= (int) $hk['id'] ?>">
                                            <button type="submit" class="btn-toggle-status <?= $hk['trang_thai_dang_ky'] == 1 ? 'btn-toggle-on' : 'btn-toggle-off' ?>">
                                                <?= $hk['trang_thai_dang_ky'] == 1 ? '🟢 Đang mở' : '🔴 Đang đóng' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="admin_hoc_ky.php?sua=<?= (int) $hk['id'] ?>" style="color: #2563eb; font-weight: 600; text-decoration: none; margin-right: 15px;">Sửa</a>
                                        <form method="POST" action="admin_hoc_ky.php" style="display:inline;" onsubmit="return confirm('Bạn chắc chắn muốn xóa?');">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="hanh_dong" value="xoa">
                                            <input type="hidden" name="id" value="<?= (int) $hk['id'] ?>">
                                            <button type="submit" style="background: none; border: none; color: #dc2626; font-weight: 600; cursor: pointer; font-family: inherit; font-size: 14px;">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                                
                                <tr id="detail-<?= $hk['id'] ?>" class="detail-row" style="display: none;">
                                    <td colspan="6">
                                        <div class="detail-container">
                                            <?php 
                                            // LẤY DỮ LIỆU TỪ MẢNG ĐÃ ĐƯỢC CONTROLLER TRUYỀN SANG
                                            $dsLop = $dsLopTheoHocKy[$hk['id']] ?? []; 
                                            ?>
                                            <?php if (empty($dsLop)): ?>
                                                <p style="color: #64748b; font-style: italic; text-align: center; margin: 0;">Học kỳ này chưa có lớp học phần nào được mở.</p>
                                            <?php else: ?>
                                                <table class="detail-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Mã Lớp</th>
                                                            <th>Tên Môn Học</th>
                                                            <th>Số TC</th>
                                                            <th>Giảng Viên</th>
                                                            <th>Sĩ Số Tối Đa</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($dsLop as $lop): ?>
                                                            <tr>
                                                                <td><span style="background: #eff6ff; color: #2563eb; padding: 2px 8px; border-radius: 4px; font-weight: 700;"><?= e($lop['ma_lop']) ?></span></td>
                                                                <td style="font-weight: 500;"><?= e($lop['ten_hoc_phan']) ?></td>
                                                                <td><?= $lop['so_tin_chi'] ?></td>
                                                                <td><?= e($lop['gv']) ?></td>
                                                                <td><?= $lop['si_so_toi_da'] ?> sinh viên</td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="/studenthub/assets/js/change_password.js"></script>
<script>
    function toggleDetail(id) {
        var row = document.getElementById('detail-' + id);
        if (row.style.display === 'none') {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    }
</script>
</body>
</html>