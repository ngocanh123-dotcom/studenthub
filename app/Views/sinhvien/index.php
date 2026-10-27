<?php
// app/Views/sinhvien/index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền sinh viên bảo mật
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'sinhvien') {
    header('Location: ../../../public/index.php');
    exit;
}

// Lấy thông tin tài khoản hiển thị chuẩn Hình 1 & 2
$hoTen = $_SESSION['ho_ten'] ?? 'Nguyễn Thu Trang';
$maSinhVien = $_SESSION['username'] ?? 'SV001';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Cổng thông tin sinh viên</title>
    <!-- Nhúng CSS độc lập chuẩn Hình 1 & 2 -->
    <link rel="stylesheet" href="/studenthub/assets/css/sinhvien.css">
</head>
<body class="sh-body">

<div class="sh-container">
    <!-- 1. CỘT SIDEBAR XANH ĐEN BÊN TRÁI (CHUẨN ẢNH 1 & ẢNH 2) -->
    <aside class="sh-sidebar">
        <div>
            <!-- Logo Thương hiệu -->
            <div class="sidebar-brand-box">
                <div class="brand-icon-s">S</div>
                <div class="brand-info">
                    <h2>StudentHub</h2>
                    <p>Cổng thông tin sinh viên</p>
                </div>
            </div>

            <!-- Danh mục Menu chức năng -->
            <div class="sidebar-category-title">CỔNG SINH VIÊN</div>
            <nav class="sidebar-menu-list">
                <a href="/studenthub/app/Views/sinhvien/index.php" class="sh-nav-item active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span>Tổng quan</span>
                </a>

                <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" class="sh-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Danh sách lớp học phần</span>
                </a>

                <a href="/studenthub/app/Views/classes/detail.php?id=1" class="sh-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>Chi tiết lớp học phần</span>
                </a>

                <a href="#" class="sh-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 14 14"></polyline>
                    </svg>
                    <span>Lịch sử đăng ký</span>
                </a>
                <!-- NÚT ĐĂNG XUẤT LIÊN KẾT TRỰC TIẾP VỚI CƠ CHẾ LOGOUT -->
                <a href="/studenthub/public/index.php?action=logout" class="sh-nav-item logout-link" title="Đăng xuất khỏi hệ thống">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Đăng xuất</span>
                </a>
            </nav>
        </div>

        <!-- Khối trạng thái học kỳ dưới đáy Sidebar (Chuẩn Ảnh 2) -->
        <div class="sidebar-semester-pill">
            <div class="semester-status-line">
                <span class="green-dot"></span>
                <span>Học kỳ 1 (2026 - 2027)</span>
            </div>
            <p>Cổng đăng ký tín chỉ đang mở cho sinh viên toàn trường.</p>
        </div>
    </aside>

    <!-- 2. KHU VỰC NỘI DUNG CHÍNH BÊN PHẢI -->
    <main class="sh-main-content">
        <!-- Header trên cùng: Tiêu đề trang & Thẻ cá nhân (Chuẩn Ảnh 1) -->
        <header class="sh-top-header">
            <div>
                <div class="top-breadcrumb">Trang chủ / Tổng quan</div>
                <h1>Tổng quan học tập</h1>
                <p class="header-subtext">Theo dõi tiến độ đăng ký học phần, số tín chỉ tích lũy và lịch học trong học kỳ.</p>
            </div>

            <!-- Thẻ hồ sơ cá nhân góc trên bên phải -->
            <div class="profile-pill-box">
                <div class="profile-avatar-circle">SV</div>
                <div class="profile-name-role">
                    <strong><?= htmlspecialchars($hoTen) ?></strong>
                    <span>Sinh viên • <?= htmlspecialchars($maSinhVien) ?></span>
                </div>
            </div>
        </header>

        <!-- BANNER XIN CHÀO MÀU XANH DƯƠNG (Chuẩn Ảnh 1) -->
        <section class="hero-welcome-banner">
            <div>
                <div class="banner-subtag">STUDENTHUB STUDENT PORTAL</div>
                <h2>Xin chào, <?= htmlspecialchars($hoTen) ?>!</h2>
                <p>Chào mừng bạn đến với hệ thống quản lý và đăng ký học phần StudentHub. Hãy tra cứu danh sách các lớp đang mở và hoàn tất đăng ký tín chỉ đúng thời hạn.</p>
            </div>
            <!-- Huy hiệu nghiêng SH -->
            <div class="banner-sh-badge">SH</div>
        </section>

        <!-- KHỐI SỐ LIỆU HỌC TẬP (Chuẩn 4 thẻ của Ảnh 1) -->
        <div class="section-meta-heading">
            <h3>Số liệu học tập</h3>
            <p>Tổng hợp dữ liệu đăng ký học phần của bạn trong Học kỳ 1 (2026 - 2027).</p>
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
                <div class="metric-big-number">6</div>
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
                <div class="metric-big-number">2</div>
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
                <div class="metric-big-number">6</div>
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
                <div class="metric-big-number green-text">Đang mở ĐK</div>
                <div class="metric-bottom-desc">Hạn đăng ký: 15/09/2026</div>
            </div>
        </div>

        <!-- BỐ CỤC 2 CỘT: BẢNG LỚP HỌC PHẦN & HƯỚNG DẪN ĐĂNG KÝ (Chuẩn Ảnh 2) -->
        <div class="sh-lower-split">
            <!-- CỘT TRÁI: BẢNG LỚP HỌC PHẦN ĐANG MỞ -->
            <div class="classes-table-panel">
                <div class="panel-header-flex">
                    <div>
                        <h3>Lớp học phần đang mở đăng ký</h3>
                        <p>Các lớp học phần tiêu biểu trong học kỳ hiện tại</p>
                    </div>
                    <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" class="btn-see-all-pill">Xem tất cả lớp &rarr;</a>
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
                        <!-- Dòng 1 -->
                        <tr>
                            <td><span class="class-code-tag">MAT101-01</span></td>
                            <td class="subject-info-cell">
                                <strong>Toán Cao Cấp A1</strong>
                                <small>Mã HP: MAT101 • 3 tín chỉ</small>
                            </td>
                            <td>ThS Trần Bình Trọng</td>
                            <td class="capacity-text">28/40 <span class="remain">(còn 12)</span></td>
                            <td><button class="btn-enroll-action">+ Đăng ký</button></td>
                        </tr>

                        <!-- Dòng 2 -->
                        <tr>
                            <td><span class="class-code-tag">WEB102-01</span></td>
                            <td class="subject-info-cell">
                                <strong>Lập Trình Web Cơ Bản</strong>
                                <small>Mã HP: WEB102 • 3 tín chỉ</small>
                            </td>
                            <td>TS Lê Hoàng Nam</td>
                            <td class="capacity-text">35/45 <span class="remain">(còn 10)</span></td>
                            <td>
                                <span class="badge-enrolled-ok">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Đã ĐK
                                </span>
                            </td>
                        </tr>

                        <!-- Dòng 3 -->
                        <tr>
                            <td><span class="class-code-tag">DBS201-02</span></td>
                            <td class="subject-info-cell">
                                <strong>Cơ Sở Dữ Liệu Quan Hệ</strong>
                                <small>Mã HP: DBS201 • 3 tín chỉ</small>
                            </td>
                            <td>ThS Nguyễn Minh Châu</td>
                            <td class="capacity-text">22/40 <span class="remain">(còn 18)</span></td>
                            <td><button class="btn-enroll-action">+ Đăng ký</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- CỘT PHẢI: KHỐI HƯỚNG DẪN ĐĂNG KÝ & NÚT ĐEN CTA (Chuẩn Ảnh 2) -->
            <div class="guide-instructions-panel">
                <h3>Hướng dẫn đăng ký</h3>
                <p class="guide-sub">Quy trình đăng ký học phần trực tuyến trên StudentHub</p>

                <div class="steps-flow-list">
                    <!-- Bước 1 -->
                    <div class="step-flow-item">
                        <div class="step-number-circle">1</div>
                        <div class="step-text-content">
                            <strong>Tra cứu lớp học phần mở</strong>
                            <p>Kiểm tra lịch học, giảng viên và số chỗ còn trống.</p>
                        </div>
                    </div>

                    <!-- Bước 2 -->
                    <div class="step-flow-item">
                        <div class="step-number-circle">2</div>
                        <div class="step-text-content">
                            <strong>Xem chi tiết &amp; Chọn đăng ký</strong>
                            <p>Hệ thống tự động kiểm tra điều kiện trùng đăng ký và sĩ số.</p>
                        </div>
                    </div>

                    <!-- Bước 3 -->
                    <div class="step-flow-item">
                        <div class="step-number-circle">3</div>
                        <div class="step-text-content">
                            <strong>Theo dõi lịch sử đăng ký</strong>
                            <p>Kiểm tra trạng thái hoặc hủy học phần trong thời hạn cho phép.</p>
                        </div>
                    </div>
                </div>

                <!-- Nút CTA màu đen bo góc chuẩn Ảnh 2 -->
                <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" class="btn-cta-all-classes">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <span>Đi tới Danh sách lớp học phần</span>
                </a>
            </div>
        </div>
    </main>
</div>

</body>
</html>