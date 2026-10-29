<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý lớp học phần | StudentHub Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/studenthub/assets/css/quantrivien.css?v=<?= time() ?>">
    <style>
        /* CSS BỔ SUNG RIÊNG CHO MODULE QUẢN LÝ LỚP HỌC PHẦN */
        .class-code-badge {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        .badge-status-open {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
        }
        .badge-status-locked {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
        }
        .capacity-bar-wrap {
            width: 110px;
            height: 6px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 5px;
        }
        .capacity-bar-fill {
            height: 100%;
            background: #2563eb;
            border-radius: 10px;
        }
        .capacity-bar-fill.full {
            background: #ea580c;
        }
        .capacity-bar-fill.danger {
            background: #dc2626;
        }
        .toolbar-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .btn-toolbar-group {
            display: flex;
            gap: 10px;
        }
        .btn-create-class {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #2563eb;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-create-class:hover {
            background: #1d4ed8;
        }
        .btn-export-csv {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-export-csv:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }
        .btn-toggle-switch {
            border: none;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        .btn-toggle-switch.to-lock {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        .btn-toggle-switch.to-lock:hover {
            background: #fca5a5;
        }
        .btn-toggle-switch.to-open {
            background: #d1fae5;
            color: #047857;
            border: 1px solid #6ee7b7;
        }
        .btn-toggle-switch.to-open:hover {
            background: #a7f3d0;
        }
        .filter-grid-classes {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
            gap: 10px;
            background: #f8fafc;
            padding: 16px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        @media (max-width: 1200px) {
            .filter-grid-classes {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 650px) {
            .filter-grid-classes {
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

            <!-- LIÊN KẾT MODULE ĐANG KÍCH HOẠT -->
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

    <!-- KHU VỰC NỘI DUNG CHÍNH -->
    <main class="main">
        <!-- Topbar -->
        <header class="topbar">
            <div>
                <div class="breadcrumb">Trang chủ / Quản trị hệ thống / Quản lý lớp học phần</div>
                <h1>Quản lý danh sách lớp học phần</h1>
                <p>Khởi tạo lớp tín chỉ, phân công giảng viên, cấu hình sức chứa phòng học và điều phối cổng đăng ký.</p>
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

            <!-- THÔNG BÁO FLASH MESSAGE -->
            <?php if (!empty($notice)): ?>
                <div class="<?= ($noticeType === 'error') ? 'error-message' : 'account-notice' ?>" style="margin-bottom: 20px;">
                    <strong><?= ($noticeType === 'error') ? '⚠️ Cảnh báo: ' : '✓ Thành công: ' ?></strong>
                    <?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <!-- 1. BẢNG ĐIỀU KHIỂN THỐNG KÊ (QUICK STATS) -->
            <div class="stats" style="margin-top: 0; margin-bottom: 24px;">
                <div class="stat-card blue">
                    <div class="stat-top">
                        <span class="stat-label">TỔNG SỐ LỚP MỞ</span>
                        <div class="stat-icon">▣</div>
                    </div>
                    <h3><?= (int)($statistics['tong_lop'] ?? 0) ?></h3>
                    <p>Lớp học phần trên toàn hệ thống</p>
                </div>

                <div class="stat-card green">
                    <div class="stat-top">
                        <span class="stat-label">LỚP ĐANG MỞ ĐĂNG KÝ</span>
                        <div class="stat-icon">✓</div>
                    </div>
                    <h3><?= (int)($statistics['lop_dang_mo'] ?? 0) ?></h3>
                    <p>Sinh viên có thể đăng ký bình thường</p>
                </div>

                <div class="stat-card orange">
                    <div class="stat-top">
                        <span class="stat-label">LỚP ĐANG BỊ KHÓA</span>
                        <div class="stat-icon">✕</div>
                    </div>
                    <h3><?= (int)($statistics['lop_dang_khoa'] ?? 0) ?></h3>
                    <p>Cầu dao đóng: chặn thao tác đăng ký</p>
                </div>

                <div class="stat-card purple">
                    <div class="stat-top">
                        <span class="stat-label">LƯỢT SINH VIÊN GHI DANH</span>
                        <div class="stat-icon">♙</div>
                    </div>
                    <h3><?= (int)($statistics['tong_sv_ghi_danh'] ?? 0) ?></h3>
                    <p>Tổng số lượt đăng ký hợp lệ</p>
                </div>
            </div>

            <!-- 2. THANH CÔNG CỤ VÀ NÚT TÁC VỤ (TOOLBAR) -->
            <div class="toolbar-flex">
                <div class="section-heading" style="margin-bottom: 0;">
                    <h2>Danh sách lớp học phần đào tạo</h2>
                </div>
                <div class="btn-toolbar-group">
                    <a href="lop_hoc_phan.php?action=create" class="btn-create-class">
                        <span>+ Thêm lớp học phần mới</span>
                    </a>
                    <a href="lop_hoc_phan.php?action=export_csv&<?= http_build_query($filters) ?>" class="btn-export-csv" title="Xuất dữ liệu Excel UTF-8">
                        <span>⬇ Xuất danh sách (CSV)</span>
                    </a>
                </div>
            </div>

            <!-- 3. BỘ LỌC TÌM KIẾM ĐA TIÊU CHÍ (SEARCH & FILTERS) -->
            <form method="GET" action="lop_hoc_phan.php" class="filter-grid-classes">
                <input type="hidden" name="action" value="index">
                
                <div>
                    <input type="text" name="tu_khoa" 
                           value="<?= htmlspecialchars($filters['tu_khoa'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Tìm theo mã lớp, mã môn, tên môn..." 
                           style="width: 100%; min-height: 40px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>

                <div>
                    <select name="hoc_ky_id" style="width: 100%; min-height: 40px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px;">
                        <option value="">-- Tất cả học kỳ --</option>
                        <?php foreach ($hocKyList as $hk): ?>
                            <option value="<?= $hk['id'] ?>" <?= ((string)$filters['hoc_ky_id'] === (string)$hk['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($hk['ten_hoc_ky'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <select name="hoc_phan_id" style="width: 100%; min-height: 40px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px;">
                        <option value="">-- Tất cả môn học --</option>
                        <?php foreach ($hocPhanList as $hp): ?>
                            <option value="<?= $hp['id'] ?>" <?= ((string)$filters['hoc_phan_id'] === (string)$hp['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($hp['ma_hoc_phan'] . ' - ' . $hp['ten_hoc_phan'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <select name="giang_vien_id" style="width: 100%; min-height: 40px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px;">
                        <option value="">-- Tất cả giảng viên --</option>
                        <?php foreach ($giangVienList as $gv): ?>
                            <option value="<?= $gv['id'] ?>" <?= ((string)$filters['giang_vien_id'] === (string)$gv['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($gv['ho_ten'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <select name="trang_thai" style="width: 100%; min-height: 40px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px;">
                        <option value="">-- Trạng thái --</option>
                        <option value="mo" <?= ($filters['trang_thai'] === 'mo') ? 'selected' : '' ?>>Đang mở</option>
                        <option value="khoa" <?= ($filters['trang_thai'] === 'khoa') ? 'selected' : '' ?>>Đã khóa</option>
                    </select>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn-primary" style="min-height: 40px; padding: 0 16px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        Lọc
                    </button>
                    <?php if (!empty($filters['tu_khoa']) || !empty($filters['hoc_ky_id']) || !empty($filters['hoc_phan_id']) || !empty($filters['giang_vien_id']) || !empty($filters['trang_thai'])): ?>
                        <a href="lop_hoc_phan.php" class="btn-secondary" style="min-height: 40px; display: inline-flex; align-items: center; padding: 0 12px; border-radius: 8px; text-decoration: none; color: #64748b; background: #e2e8f0; font-size: 13px; font-weight: 600;">
                            Xóa lọc
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- 4. BẢNG DỮ LIỆU LỚP HỌC PHẦN (MAIN DATA TABLE) -->
            <div class="account-table">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 90px;">Mã lớp</th>
                            <th>Môn học &amp; Số TC</th>
                            <th>Học kỳ</th>
                            <th>Giảng viên</th>
                            <th>Lịch học &amp; Phòng</th>
                            <th style="width: 140px;">Sĩ số / Chỗ</th>
                            <th style="width: 130px; text-align: center;">Trạng thái</th>
                            <th style="width: 140px; text-align: center;">Cầu dao Mở/Khóa</th>
                            <th style="width: 120px; text-align: center;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($classes)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: #64748b;">
                                    Không tìm thấy lớp học phần nào phù hợp với điều kiện tìm kiếm.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($classes as $c): ?>
                                <?php 
                                    $siSoHienTai = (int)$c['si_so_hien_tai'];
                                    $siSoToiDa = (int)$c['si_so_toi_da'];
                                    $conLai = max(0, $siSoToiDa - $siSoHienTai);
                                    $percent = ($siSoToiDa > 0) ? min(100, round(($siSoHienTai / $siSoToiDa) * 100)) : 0;
                                    $isFull = ($conLai === 0);
                                    $isLocked = ($c['trang_thai'] === 'khoa');
                                ?>
                                <tr>
                                    <!-- Mã lớp -->
                                    <td>
                                        <span class="class-code-badge"><?= htmlspecialchars($c['ma_lop'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>

                                    <!-- Môn học -->
                                    <td>
                                        <strong><?= htmlspecialchars($c['ten_hoc_phan'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                                            Mã: <code><?= htmlspecialchars($c['ma_hoc_phan'], ENT_QUOTES, 'UTF-8') ?></code> • 
                                            <strong><?= (int)$c['so_tin_chi'] ?> Tín chỉ</strong>
                                        </div>
                                    </td>

                                    <!-- Học kỳ -->
                                    <td>
                                        <span style="font-size: 12.5px;"><?= htmlspecialchars($c['ten_hoc_ky'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>

                                    <!-- Giảng viên -->
                                    <td>
                                        <div style="font-weight: 600; color: #1e293b;">
                                            <?= htmlspecialchars($c['ten_giang_vien'] ?? 'Chưa phân công', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <?php if (!empty($c['email_giang_vien'])): ?>
                                            <div style="font-size: 11px; color: #94a3b8;"><?= htmlspecialchars($c['email_giang_vien'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Lịch học & Phòng -->
                                    <td>
                                        <div style="font-size: 12.5px; color: #334155; font-weight: 600;">
                                            <?= htmlspecialchars($c['lich_hoc'] ?? 'Chưa xếp', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div style="font-size: 11.5px; color: #64748b;">
                                            <?= htmlspecialchars($c['phong_hoc'] ?? 'Chưa xếp phòng', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <?php if (!empty($c['thoi_gian_mo_rieng'])): ?>
                                            <div style="font-size: 10.5px; color: #0284c7; margin-top: 2px;">
                                                Mở riêng: <?= date('d/m H:i', strtotime($c['thoi_gian_mo_rieng'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Sĩ số & Tiến độ -->
                                    <td>
                                        <div style="font-size: 13px;">
                                            <strong><?= $siSoHienTai ?></strong> / <?= $siSoToiDa ?>
                                            <?php if ($isFull): ?>
                                                <span style="color: #dc2626; font-size: 11px; font-weight: 700;">(Đã đầy)</span>
                                            <?php else: ?>
                                                <span style="color: #059669; font-size: 11px;">(Còn <?= $conLai ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="capacity-bar-wrap">
                                            <div class="capacity-bar-fill <?= $isFull ? 'danger' : ($percent >= 80 ? 'full' : '') ?>" 
                                                 style="width: <?= $percent ?>%;"></div>
                                        </div>
                                    </td>

                                    <!-- Huy hiệu Trạng thái -->
                                    <td style="text-align: center;">
                                        <?php if (!$isLocked): ?>
                                            <span class="badge-status-open">● Đang mở</span>
                                        <?php else: ?>
                                            <span class="badge-status-locked">● Đã khóa</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- CẦU DAO MỞ / KHÓA ĐĂNG KÝ (MỤC H DEMO) -->
                                    <td style="text-align: center;">
                                        <form method="POST" action="lop_hoc_phan.php?action=toggle" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                            
                                            <?php if (!$isLocked): ?>
                                                <button type="submit" class="btn-toggle-switch to-lock" 
                                                        title="Bấm để khóa lớp: sinh viên sẽ bị chặn ngay lập tức!"
                                                        onclick="return confirm('Bạn có chắc chắn muốn KHÓA đăng ký lớp <?= htmlspecialchars($c['ma_lop'], ENT_QUOTES, 'UTF-8') ?>? Sinh viên sẽ không thể đăng ký hoặc xem mở lớp này.');">
                                                    🔒 Khóa lớp
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn-toggle-switch to-open" 
                                                        title="Bấm để mở cổng đăng ký cho sinh viên"
                                                        onclick="return confirm('Bạn có chắc chắn muốn MỞ lại đăng ký cho lớp <?= htmlspecialchars($c['ma_lop'], ENT_QUOTES, 'UTF-8') ?>?');">
                                                    🔓 Mở lại
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>

                                    <!-- Hành động (Sửa, Xóa) -->
                                    <td style="text-align: center;">
                                        <a href="lop_hoc_phan.php?action=edit&id=<?= (int)$c['id'] ?>" class="btn-edit" title="Chỉnh sửa thông tin lớp">
                                            Sửa
                                        </a>

                                        <!-- Xóa có kiểm tra an toàn -->
                                        <form method="POST" action="lop_hoc_phan.php?action=delete" style="display: inline;" 
                                              onsubmit="return confirm('CẢNH BÁO: Bạn có chắc chắn muốn xóa lớp <?= htmlspecialchars($c['ma_lop'], ENT_QUOTES, 'UTF-8') ?>? Hệ thống sẽ chặn xóa nếu đã có sinh viên ghi danh.');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                            <button type="submit" class="btn-delete" title="Xóa lớp học phần">
                                                Xóa
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Chân trang quản trị -->
            <div class="page-footer">
                Hệ thống Quản lý khóa học &amp; Đăng ký học phần (StudentHub) • Phân hệ Quản trị viên
            </div>

        </div>
    </main>
</div>
</body>
</html>
