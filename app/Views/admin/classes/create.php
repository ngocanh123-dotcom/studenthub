<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm mới lớp học phần | StudentHub Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/studenthub/assets/css/quantrivien.css?v=<?= time() ?>">
    <style>
        .form-card-container {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.035);
        }
        .form-section-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e3a8a;
            margin: 20px 0 12px;
            padding-bottom: 6px;
            border-bottom: 1px dashed #cbd5e1;
        }
        .form-grid-2col {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px 24px;
        }
        .form-group-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group-item label {
            font-size: 13px;
            font-weight: 650;
            color: #334155;
        }
        .form-group-item label .required {
            color: #ef4444;
            font-weight: 700;
        }
        .form-group-item input,
        .form-group-item select {
            min-height: 44px;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            font-family: inherit;
            color: #172033;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group-item input:focus,
        .form-group-item select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
        .form-hint {
            font-size: 11.5px;
            color: #64748b;
        }
        .btn-submit-save {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit-save:hover {
            background: #1d4ed8;
        }
        .btn-cancel-back {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-cancel-back:hover {
            background: #e2e8f0;
        }
        @media (max-width: 768px) {
            .form-grid-2col {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
<div class="layout">

    <!-- THANH MENU BÊN TRÁI -->
    <aside class="sidebar">
        <a href="index.php" class="brand">
            <span class="brand-icon">S</span>
            <span>
                <strong>StudentHub</strong>
                <small>Quản trị hệ thống</small>
            </span>
        </a>

        <div class="menu-title">DANH MỤC QUẢN TRỊ</div>

        <nav class="menu">
            <a href="index.php" class="menu-link">
                <span class="menu-icon">⌂</span>
                <span>Tổng quan</span>
            </a>
            <a href="tai_khoan.php" class="menu-link">
                <span class="menu-icon">♙</span>
                <span>Quản lý tài khoản</span>
            </a>
            <a href="hoc_phan.php" class="menu-link">
                <span class="menu-icon">▤</span>
                <span>Quản lý học phần</span>
            </a>
            <a href="hoc_ky.php" class="menu-link">
                <span class="menu-icon">▦</span>
                <span>Quản lý học kỳ</span>
            </a>
            <a href="lop_hoc_phan.php" class="menu-link active">
                <span class="menu-icon">▣</span>
                <span>Quản lý lớp học phần</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="menu-title">TÀI KHOẢN</div>
            <a href="/studenthub/public/index.php?action=logout" class="menu-link logout">
                <span class="menu-icon">↪</span>
                <span>Đăng xuất</span>
            </a>
            <div class="sidebar-note">
                <span class="status-dot"></span>
                Phiên làm việc Quản trị viên
            </div>
        </div>
    </aside>

    <!-- KHU VỰC CHÍNH -->
    <main class="main">
        <header class="topbar">
            <div>
                <div class="breadcrumb">Trang chủ / Quản lý lớp học phần / Thêm mới</div>
                <h1>Khởi tạo lớp học phần mới</h1>
                <p>Khai báo mã lớp, liên kết chương trình đào tạo, phân công giảng viên phụ trách và thiết lập thời khóa biểu.</p>
            </div>

            <div class="admin-profile">
                <div class="avatar">QT</div>
                <div class="profile-info">
                    <strong><?= htmlspecialchars((string)$tenAdmin, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span>Quản trị viên Hệ thống</span>
                </div>
            </div>
        </header>

        <div class="content">

            <?php if (!empty($error)): ?>
                <div class="error-message" style="margin-bottom: 20px;">
                    <strong>⚠️ Lỗi nhập liệu: </strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="form-card-container">
                <form method="POST" action="lop_hoc_phan.php?action=store" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                    <!-- 1. THÔNG TIN CHUNG VÀ HỌC THUẬT -->
                    <div class="form-section-title" style="margin-top: 0;">1. THÔNG TIN HỌC PHẦN &amp; HỌC KỲ</div>
                    <div class="form-grid-2col">
                        <!-- Chọn Học phần -->
                        <div class="form-group-item">
                            <label for="hoc_phan_id">Học phần (Môn học) <span class="required">*</span></label>
                            <select name="hoc_phan_id" id="hoc_phan_id" required>
                                <option value="">-- Chọn môn học --</option>
                                <?php foreach ($hocPhanList as $hp): ?>
                                    <option value="<?= $hp['id'] ?>" <?= ((string)($oldData['hoc_phan_id'] ?? '') === (string)$hp['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($hp['ma_hoc_phan'] . ' - ' . $hp['ten_hoc_phan'] . ' (' . $hp['so_tin_chi'] . ' TC)', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-hint">Môn học sẽ được liên kết vào lớp tín chỉ</span>
                        </div>

                        <!-- Chọn Học kỳ -->
                        <div class="form-group-item">
                            <label for="hoc_ky_id">Học kỳ đào tạo <span class="required">*</span></label>
                            <select name="hoc_ky_id" id="hoc_ky_id" required>
                                <option value="">-- Chọn học kỳ --</option>
                                <?php foreach ($hocKyList as $hk): ?>
                                    <option value="<?= $hk['id'] ?>" <?= ((string)($oldData['hoc_ky_id'] ?? '') === (string)$hk['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($hk['ten_hoc_ky'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-hint">Khung thời gian tổ chức giảng dạy</span>
                        </div>
                    </div>

                    <!-- 2. ĐỊNH DANH LỚP VÀ PHÂN CÔNG -->
                    <div class="form-section-title">2. ĐỊNH DANH LỚP &amp; PHÂN CÔNG GIẢNG VIÊN</div>
                    <div class="form-grid-2col">
                        <!-- Mã lớp -->
                        <div class="form-group-item">
                            <label for="ma_lop">Mã lớp học phần <span class="required">*</span></label>
                            <input type="text" name="ma_lop" id="ma_lop" 
                                   value="<?= htmlspecialchars($oldData['ma_lop'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   placeholder="Ví dụ: LHP06, LHP-WEB-01..." required>
                            <span class="form-hint">Mã định danh duy nhất (không trùng lặp)</span>
                        </div>

                        <!-- Giảng viên phụ trách -->
                        <div class="form-group-item">
                            <label for="giang_vien_id">Giảng viên phụ trách</label>
                            <select name="giang_vien_id" id="giang_vien_id">
                                <option value="">-- Chưa phân công --</option>
                                <?php foreach ($giangVienList as $gv): ?>
                                    <option value="<?= $gv['id'] ?>" <?= ((string)($oldData['giang_vien_id'] ?? '') === (string)$gv['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($gv['ho_ten'] . ' (' . ($gv['email'] ?? $gv['ten_dang_nhap']) . ')', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-hint">Chỉ hiển thị các tài khoản có vai trò Giảng viên</span>
                        </div>
                    </div>

                    <!-- 3. VẬN HÀNH, SĨ SỐ VÀ ĐỊA ĐIỂM -->
                    <div class="form-section-title">3. VẬN HÀNH, SĨ SỐ &amp; ĐỊA ĐIỂM HỌC</div>
                    <div class="form-grid-2col">
                        <!-- Sĩ số tối đa -->
                        <div class="form-group-item">
                            <label for="si_so_toi_da">Sĩ số tối đa (Chỗ ngồi) <span class="required">*</span></label>
                            <input type="number" name="si_so_toi_da" id="si_so_toi_da" 
                                   value="<?= htmlspecialchars((string)($oldData['si_so_toi_da'] ?? 40), ENT_QUOTES, 'UTF-8') ?>" 
                                   min="1" max="500" required>
                            <span class="form-hint">Số lượng sinh viên tối đa được phép ghi danh</span>
                        </div>

                        <!-- Trạng thái ban đầu -->
                        <div class="form-group-item">
                            <label for="trang_thai">Trạng thái đăng ký <span class="required">*</span></label>
                            <select name="trang_thai" id="trang_thai" required>
                                <option value="mo" <?= (($oldData['trang_thai'] ?? 'mo') === 'mo') ? 'selected' : '' ?>>Đang mở (Cho phép sinh viên đăng ký)</option>
                                <option value="khoa" <?= (($oldData['trang_thai'] ?? '') === 'khoa') ? 'selected' : '' ?>>Đã khóa (Chặn đăng ký)</option>
                            </select>
                            <span class="form-hint">Cầu dao tiếp nhận ghi danh tín chỉ</span>
                        </div>

                        <!-- Thời khóa biểu -->
                        <div class="form-group-item">
                            <label for="lich_hoc">Thời khóa biểu / Lịch học</label>
                            <input type="text" name="lich_hoc" id="lich_hoc" 
                                   value="<?= htmlspecialchars($oldData['lich_hoc'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   placeholder="Ví dụ: Thứ 2 (Tiết 1 - 3)">
                            <span class="form-hint">Lịch học cố định hàng tuần</span>
                        </div>

                        <!-- Phòng học -->
                        <div class="form-group-item">
                            <label for="phong_hoc">Địa điểm / Phòng học</label>
                            <input type="text" name="phong_hoc" id="phong_hoc" 
                                   value="<?= htmlspecialchars($oldData['phong_hoc'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   placeholder="Ví dụ: P.302 - Nhà Giảng đường A1">
                            <span class="form-hint">Phòng học lý thuyết hoặc phòng máy thực hành</span>
                        </div>
                    </div>

                    <!-- 4. THỜI GIAN MỞ / ĐÓNG RIÊNG (NẾU CÓ) -->
                    <div class="form-section-title">4. KHUNG GIỜ MỞ ĐĂNG KÝ RIÊNG BIỆT (TÙY CHỌN)</div>
                    <div class="form-grid-2col">
                        <div class="form-group-item">
                            <label for="thoi_gian_mo_rieng">Thời gian mở riêng</label>
                            <input type="datetime-local" name="thoi_gian_mo_rieng" id="thoi_gian_mo_rieng" 
                                   value="<?= htmlspecialchars($oldData['thoi_gian_mo_rieng'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <span class="form-hint">Để trống nếu muốn áp dụng khung giờ chung của Học kỳ</span>
                        </div>

                        <div class="form-group-item">
                            <label for="thoi_gian_dong_rieng">Thời gian đóng riêng</label>
                            <input type="datetime-local" name="thoi_gian_dong_rieng" id="thoi_gian_dong_rieng" 
                                   value="<?= htmlspecialchars($oldData['thoi_gian_dong_rieng'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <span class="form-hint">Hạn chót đóng cổng đăng ký riêng cho lớp này</span>
                        </div>
                    </div>

                    <!-- NÚT THAO TÁC -->
                    <div style="display: flex; gap: 14px; margin-top: 32px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                        <button type="submit" class="btn-submit-save">
                            ✓ Lưu lớp học phần
                        </button>
                        <a href="lop_hoc_phan.php" class="btn-cancel-back">
                            &larr; Hủy bỏ &amp; Quay lại
                        </a>
                    </div>
                </form>
            </div>

            <div class="page-footer">
                Hệ thống Quản lý khóa học &amp; Đăng ký học phần (StudentHub) • Phân hệ Quản trị viên
            </div>
        </div>
    </main>
</div>
</body>
</html>
