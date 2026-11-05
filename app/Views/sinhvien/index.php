<?php
// app/Views/sinhvien/index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền sinh viên bảo mật
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'sinhvien') {
    header('Location: /studenthub/public/index.php');
    exit;
}

// Nếu view được truy cập mà chưa có dữ liệu từ Controller, tự động nạp controller để không bao giờ bị số 0
if (!isset($dashboardData) || !is_array($dashboardData) || !isset($dashboardData['summary'])) {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        $dbFile = __DIR__ . '/../../../config/database.php';
        if (file_exists($dbFile)) {
            require_once $dbFile;
        }
    }
    if (isset($pdo) && $pdo instanceof PDO) {
        require_once __DIR__ . '/../../controllers/SinhVienDashboardController.php';
        $dashboardController = new SinhVienDashboardController($pdo);
        $dashboardController->index();
        exit;
    }
}

if (!isset($dashboardError) || $dashboardError === null) {
    $dashboardError = '';
}

$hoTen = $_SESSION['ho_ten'] ?? $_SESSION['username'] ?? 'Sinh viên';
$maSinhVien = $_SESSION['username'] ?? '';
$hocKyHienTai = $dashboardData['semester'] ?? null;
$cacLopMo = $dashboardData['open_classes'] ?? [];
$soLopDangMo = $dashboardData['summary']['so_lop_dang_mo'] ?? count($cacLopMo);
$soLopDaDangKy = $dashboardData['summary']['so_lop_da_dang_ky'] ?? (is_array($dashboardData['registered_classes'] ?? null) ? count($dashboardData['registered_classes']) : ($dashboardData['registered_classes'] ?? 0));
$soTinChiDaDangKy = $dashboardData['summary']['tong_tin_chi_da_dk'] ?? ($dashboardData['registered_credits'] ?? 0);
$hanDangKy = $hocKyHienTai['thoi_gian_dong_dk'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Cổng thông tin sinh viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Ép trình duyệt luôn cập nhật CSS mới bằng chuỗi time() chống cache -->
    <link rel="stylesheet" href="/studenthub/assets/css/sinhvien.css?v=<?= time() ?>">
    <style>
        /* Đảm bảo thẻ <a> khi dùng class .btn-enroll-action hiển thị chuẩn nút bấm */
        a.btn-enroll-action {
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }
    </style>
</head>
<body class="sh-body">

<div class="sh-container">
    <!-- 1. SIDEBAR BÊN TRÁI (ĐÃ CÓ ĐẦY ĐỦ ĐĂNG KÝ HỌC PHẦN) -->
    <aside class="sh-sidebar">
        <div>
            <div class="sidebar-brand-box">
                <div class="brand-icon-s">S</div>
                <div class="brand-info">
                    <h2>StudentHub</h2>
                    <p>Cổng thông tin sinh viên</p>
                </div>
            </div>

            <div class="sidebar-category-title">CỔNG SINH VIÊN</div>
            <nav class="sidebar-menu-list">
                <a href="/studenthub/public/index.php?route=sinh_vien.php"
                   class="sh-nav-item active">
                    <svg width="18" height="18" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span>Tổng quan</span>
                </a>

                <a href="/studenthub/public/index.php?route=danh_sach_lop_hoc_phan.php"
                   class="sh-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    <span>Danh sách lớp học phần</span>
                </a>

                <a href="/studenthub/public/index.php?route=trang_dang_ky_hoc.php"
                   class="sh-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    <span>Đăng ký lớp học phần</span>
                </a>

                <a href="/studenthub/public/index.php?route=lich_su_dang_ky.php"
                   class="sh-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 14 14"/>
                    </svg>
                    <span>Lịch sử đăng ký</span>
                </a>

                <a href="/studenthub/public/index.php?route=doi_mat_khau.php"
                   class="sh-nav-item change-pass-link">
                    <span style="font-size: 16px; width: 18px; text-align: center;">🔑</span>
                    <span>Đổi mật khẩu</span>
                </a>

                <a href="/studenthub/public/index.php?action=logout"
                   class="sh-nav-item logout-link" title="Đăng xuất">
                    <svg width="18" height="18" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Đăng xuất</span>
                </a>
            </nav>
        </div>

        <div class="sidebar-semester-pill">
            <div class="semester-status-line">
                <span class="green-dot"></span>
                <span>Học kỳ 1 (2026 - 2027)</span>
            </div>
            <p>Cổng đăng ký tín chỉ đang mở cho sinh viên toàn trường.</p>
        </div>
    </aside>

    <!-- 2. NỘI DUNG CHÍNH BÊN PHẢI -->
    <main class="sh-main-content">
        <!-- Header tiêu đề -->
        <header class="sh-top-header">
            <div>
                <div class="top-breadcrumb">Trang chủ / Tổng quan</div>
                <h1>Tổng quan học tập</h1>
                <p class="header-subtext">Theo dõi tiến độ đăng ký học phần, số tín chỉ tích lũy và lịch học trong học kỳ.</p>
            </div>

            <div class="profile-pill-box">
                <div class="profile-avatar-circle">SV</div>
                <div class="profile-name-role">
                    <strong><?= htmlspecialchars($hoTen) ?></strong>
                    <span>Sinh viên • <?= htmlspecialchars($maSinhVien) ?></span>
                </div>
            </div>
        </header>

        <!-- BANNER XIN CHÀO MÀU XANH DƯƠNG (Nổi khối) -->
        <section class="hero-welcome-banner">
            <div>
                <div class="banner-subtag">STUDENTHUB STUDENT PORTAL</div>
                <h2>Xin chào, <?= htmlspecialchars($hoTen) ?>!</h2>
                <p>Chào mừng bạn đến với hệ thống quản lý và đăng ký học phần StudentHub. Hãy tra cứu danh sách các lớp đang mở và hoàn tất đăng ký tín chỉ đúng thời hạn.</p>
            </div>
            <div class="banner-sh-badge">SH</div>
        </section>

        <!-- KHỐI 4 THẺ SỐ LIỆU HỌC TẬP (KPI) -->
        <div class="section-meta-heading">
            <h3>Số liệu học tập</h3>
            <p>
                <?php if ($dashboardData === null): ?>
                    Không thể tải dữ liệu học tập.
                <?php elseif ($hocKyHienTai !== null): ?>
                    Tổng hợp dữ liệu đăng ký học phần của bạn trong
                    <?= htmlspecialchars($hocKyHienTai['ten_hoc_ky'], ENT_QUOTES, 'UTF-8') ?>.
                <?php else: ?>
                    Chưa có học kỳ nào được thiết lập trong hệ thống.
                <?php endif; ?>
            </p>
        </div>

        <div class="sh-metrics-grid">
            <!-- Thẻ 1 -->
            <div class="sh-metric-card">
                <div class="card-top-row">
                    <span>Lớp đang mở</span>
                    <div class="metric-circle-icon blue">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="metric-big-number">
                    <?= $dashboardData !== null ? number_format($soLopDangMo) : '—' ?>
                </div>
                <div class="metric-bottom-desc">Lớp học phần có thể đăng ký</div>
            </div>

            <!-- Thẻ 2 -->
            <div class="sh-metric-card">
                <div class="card-top-row">
                    <span>Lớp đã đăng ký</span>
                    <div class="metric-circle-icon green">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                </div>
                <div class="metric-big-number">
                    <?= $soLopDaDangKy !== null ? number_format($soLopDaDangKy) : '—' ?>
                </div>
                <div class="metric-bottom-desc">Lớp học phần trong học kỳ</div>
            </div>

            <!-- Thẻ 3 -->
            <div class="sh-metric-card">
                <div class="card-top-row">
                    <span>Tín chỉ tích lũy ĐK</span>
                    <div class="metric-circle-icon purple">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="metric-big-number">
                    <?= $soTinChiDaDangKy !== null ? number_format($soTinChiDaDangKy) : '—' ?>
                </div>
                <div class="metric-bottom-desc">Tín chỉ đã đăng ký kỳ này</div>
            </div>

            <!-- Thẻ 4 -->
            <div class="sh-metric-card">
                <div class="card-top-row">
                    <span>Học kỳ hiện tại</span>
                    <div class="metric-circle-icon amber">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                </div>
                <div class="metric-big-number <?= !empty($dashboardData['registration_open']) ? 'green-text' : '' ?>">
                    <?php if ($dashboardData === null): ?>
                        —
                    <?php elseif ($hocKyHienTai === null): ?>
                        Chưa có kỳ
                    <?php else: ?>
                        <?= $dashboardData['registration_open'] ? 'Đang mở ĐK' : 'Đã đóng ĐK' ?>
                    <?php endif; ?>
                </div>
                <div class="metric-bottom-desc">
                    Hạn đăng ký:
                    <?= $hanDangKy
                        ? htmlspecialchars(date('d/m/Y', strtotime($hanDangKy)), ENT_QUOTES, 'UTF-8')
                        : 'Chưa thiết lập' ?>
                </div>
            </div>
        </div>

        <?php if ($dashboardError !== ''): ?>
            <div class="error-message" role="alert">
                <?= htmlspecialchars($dashboardError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- BẢNG LỚP HỌC PHẦN ĐANG MỞ & HƯỚNG DẪN -->
        <div class="sh-lower-split">
            <div class="classes-table-panel">
                <div class="panel-header-flex">
                    <div>
                        <h3>Lớp học phần đang mở đăng ký</h3>
                        <p>
                            <?php if ($hocKyHienTai !== null): ?>
                                Các lớp còn chỗ và đang nhận đăng ký trong
                                <?= htmlspecialchars($hocKyHienTai['ten_hoc_ky'], ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                Các lớp học phần đang nhận đăng ký
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <table class="classes-data-table">
                    <thead>
                        <tr>
                            <th>MÃ LỚP MỞ</th>
                            <th>TÊN HỌC PHẦN</th>
                            <th>GIẢNG VIÊN</th>
                            <th>SĨ SỐ</th>
                            <th>THAO TÁC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($dashboardData === null): ?>
                            <tr>
                                <td colspan="5">Không thể tải dữ liệu lớp học phần.</td>
                            </tr>
                        <?php elseif ($cacLopMo === []): ?>
                            <tr>
                                <td colspan="5">Hiện không có lớp học phần phù hợp để đăng ký.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach (array_slice($cacLopMo, 0, 5) as $lop): ?>
                                <?php
                                $siSo = (int) $lop['si_so_hien_tai'];
                                $siSoToiDa = (int) $lop['si_so_toi_da'];
                                $conCho = max(0, $siSoToiDa - $siSo);
                                ?>
                                <tr>
                                    <td>
                                        <span class="class-code-tag">
                                            <?= htmlspecialchars($lop['ma_lop'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($lop['ten_hoc_phan'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <small style="color:#64748b;">
                                            Mã HP: <?= htmlspecialchars($lop['ma_hoc_phan'], ENT_QUOTES, 'UTF-8') ?>
                                            • <?= (int) $lop['so_tin_chi'] ?> tín chỉ
                                        </small>
                                    </td>
                                    <td><?= htmlspecialchars($lop['ten_giang_vien'] ?? 'Chưa phân công', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?= $siSo ?>/<?= $siSoToiDa ?>
                                        <span style="color:#059669; font-weight:700;">(còn <?= $conCho ?>)</span>
                                    </td>
                                    <td>
                                        <form
                                            method="POST"
                                            action="/studenthub/public/index.php?route=dang_ky_hoc.php"
                                            style="margin:0;"
                                        >
                                            <input type="hidden" name="lop_hoc_phan_id" value="<?= (int) $lop['id'] ?>">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                            <button type="submit" class="btn-enroll-action">+ Đăng ký</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cột Hướng dẫn quy trình -->
            <div class="guide-instructions-panel">
                <h3>Hướng dẫn đăng ký</h3>
                <p class="guide-sub">Quy trình đăng ký học phần trực tuyến trên StudentHub</p>

                <div class="steps-flow-list">
                    <div class="step-flow-item">
                        <div class="step-number-circle">1</div>
                        <div class="step-text-content">
                            <strong>Tra cứu lớp học phần mở</strong>
                            <p>Kiểm tra lịch học, giảng viên và số chỗ còn trống.</p>
                        </div>
                    </div>
                    <div class="step-flow-item">
                        <div class="step-number-circle">2</div>
                        <div class="step-text-content">
                            <strong>Xem chi tiết &amp; Chọn đăng ký</strong>
                            <p>Hệ thống tự động kiểm tra điều kiện trùng đăng ký và sĩ số.</p>
                        </div>
                    </div>
                    <div class="step-flow-item">
                        <div class="step-number-circle">3</div>
                        <div class="step-text-content">
                            <strong>Theo dõi lịch sử đăng ký</strong>
                            <p>Kiểm tra trạng thái hoặc hủy học phần trong thời hạn cho phép.</p>
                        </div>
                    </div>
                </div>

                <a href="/studenthub/public/index.php?route=danh_sach_lop_hoc_phan.php" class="btn-cta-all-classes">
                    <span>Đi tới Danh sách lớp học phần</span>
                </a>
            </div>
        </div>
    </main>
</div>

</body>
</html>
