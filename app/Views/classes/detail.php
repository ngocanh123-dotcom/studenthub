<?php
// app/Views/classes/detail.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CƠ CHẾ TỰ PHỤC HỒI (SELF-HYDRATION):
// Nếu người dùng truy cập trực tiếp file detail.php mà không qua Controller, tự động nạp Model để lấy dữ liệu
if (!isset($classInfo)) {
    $conn = null;
    if (file_exists(__DIR__ . '/../../../config/database.php')) {
        require_once __DIR__ . '/../../../config/database.php';
    }
    require_once __DIR__ . '/../../Models/ClassModel.php';
    $tempModel = new ClassModel($conn);
    $targetId = $_GET['id'] ?? 1;
    $classInfo = $tempModel->getClassDetail($targetId);
    $enrolledStudents = $tempModel->getEnrolledStudents($targetId);
}

$hoTen = $_SESSION['ho_ten'] ?? 'Nguyễn Thu Trang';
$maSinhVien = $_SESSION['username'] ?? 'SV001';

// Tính toán trạng thái sĩ số lớp
$siSoHienTai = (int)($classInfo['si_so_hien_tai'] ?? 0);
$siSoToiDa = (int)($classInfo['si_so_toi_da'] ?? 45);
$choConTrong = max(0, $siSoToiDa - $siSoHienTai);
$percentFull = $siSoToiDa > 0 ? min(100, round(($siSoHienTai / $siSoToiDa) * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Chi tiết lớp học phần <?= htmlspecialchars($classInfo['ma_lop']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/studenthub/assets/css/sinhvien.css">
</head>
<body class="sh-body">

<div class="sh-container">
    <!-- 1. SIDEBAR BÊN TRÁI (ĐỒNG BỘ 100% VỚI DASHBOARD) -->
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
                <a href="/studenthub/app/Views/sinhvien/index.php" class="sh-nav-item">
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

                <!-- Mục hiện tại: Sáng xanh Active -->
                <a href="#" class="sh-nav-item active">
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

                <!-- Nút Đăng xuất liên kết trực tiếp -->
                <a href="/studenthub/public/index.php?action=logout" class="sh-nav-item logout-link" title="Đăng xuất">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                <span><?= htmlspecialchars($classInfo['ten_hoc_ky']) ?></span>
            </div>
            <p>Cổng đăng ký tín chỉ đang mở cho sinh viên toàn trường.</p>
        </div>
    </aside>

    <!-- 2. KHÔNG GIAN NỘI DUNG CHÍNH (MAIN CONTENT) -->
    <main class="sh-main-content">
        <!-- HEADER TRÊN CÙNG -->
        <header class="sh-top-header">
            <div>
                <div class="top-breadcrumb">
                    <a href="/studenthub/app/Views/sinhvien/index.php" style="color:#64748b; text-decoration:none;">Trang chủ</a> / 
                    <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" style="color:#64748b; text-decoration:none;">Lớp học phần</a> / 
                    <span style="color:#2563eb; font-weight:600;">Lớp <?= htmlspecialchars($classInfo['ma_lop']) ?></span>
                </div>
                <h1>Thông tin chi tiết lớp học phần</h1>
                <p class="header-subtext">Xem kế hoạch giảng dạy, thời khóa biểu và danh sách sinh viên đăng ký lớp.</p>
            </div>

            <!-- Profile Pill góc phải -->
            <div class="profile-pill-box">
                <div class="profile-avatar-circle">SV</div>
                <div class="profile-name-role">
                    <strong><?= htmlspecialchars($hoTen) ?></strong>
                    <span>Sinh viên • <?= htmlspecialchars($maSinhVien) ?></span>
                </div>
            </div>
        </header>

        <!-- CARD 1: THÔNG TIN TỔNG QUAN LỚP HỌC PHẦN (HERO DETAIL) -->
        <div class="detail-overview-card">
            <div class="overview-header-flex">
                <div>
                    <span class="code-pill-blue"><?= htmlspecialchars($classInfo['ma_lop']) ?></span>
                    <h2 class="class-title"><?= htmlspecialchars($classInfo['ten_hoc_phan']) ?></h2>
                    <span class="meta-tag-sub">Mã học phần: <strong><?= htmlspecialchars($classInfo['ma_hoc_phan']) ?></strong> • Số tín chỉ: <strong><?= htmlspecialchars($classInfo['so_tin_chi']) ?> TC</strong></span>
                </div>
                <div class="overview-right-badge">
                    <span class="badge-open-live">
                        <span class="green-dot"></span>
                        Đang mở đăng ký
                    </span>
                </div>
            </div>

            <!-- LƯỚI THÔNG SỐ (SPECS GRID) -->
            <div class="specs-grid-container">
                <div class="spec-item">
                    <span class="spec-label">Giảng viên giảng dạy</span>
                    <strong class="spec-val"><?= htmlspecialchars($classInfo['ten_giang_vien']) ?></strong>
                    <small class="spec-desc"><?= htmlspecialchars($classInfo['email_giang_vien']) ?></small>
                </div>

                <div class="spec-item">
                    <span class="spec-label">Thời khóa biểu</span>
                    <strong class="spec-val"><?= htmlspecialchars($classInfo['lich_hoc']) ?></strong>
                    <small class="spec-desc">Ca học chính thức trong tuần</small>
                </div>

                <div class="spec-item">
                    <span class="spec-label">Địa điểm học</span>
                    <strong class="spec-val"><?= htmlspecialchars($classInfo['phong_hoc']) ?></strong>
                    <small class="spec-desc">Phòng máy thực hành</small>
                </div>

                <div class="spec-item">
                    <span class="spec-label">Học kỳ áp dụng</span>
                    <strong class="spec-val"><?= htmlspecialchars($classInfo['ten_hoc_ky']) ?></strong>
                    <small class="spec-desc">Năm học 2026 - 2027</small>
                </div>
            </div>

            <!-- THANH TIẾN TRÌNH SĨ SỐ (CAPACITY BAR) -->
            <div class="class-capacity-box">
                <div class="capacity-labels">
                    <span class="cap-title">Tình trạng chỗ ngồi (Sĩ số lớp)</span>
                    <span class="cap-numbers">
                        <strong><?= $siSoHienTai ?></strong> / <?= $siSoToiDa ?> sinh viên 
                        <span class="remain-pill">(Còn trống <?= $choConTrong ?> chỗ)</span>
                    </span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill <?= $percentFull >= 90 ? 'danger' : '' ?>" style="width: <?= $percentFull ?>%;"></div>
                </div>
            </div>

            <!-- HÀNG NÚT THAO TÁC NHANH -->
            <div class="action-buttons-strip">
                <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" class="btn-back-link">
                    &larr; Quay lại danh sách lớp
                </a>
                <button type="button" class="btn-enroll-action" onclick="alert('Đăng ký lớp thành công!')">
                    + Đăng ký vào lớp này
                </button>
            </div>
        </div>

        <!-- CARD 2: DANH SÁCH SINH VIÊN ĐÃ ĐĂNG KÝ -->
        <div class="classes-table-panel" style="margin-top: 24px;">
            <div class="panel-header-flex">
                <div>
                    <h3>Danh sách sinh viên đã đăng ký</h3>
                    <p>Cập nhật danh sách sinh viên ghi danh vào lớp tính đến thời điểm hiện tại</p>
                </div>
                <span class="badge-total-students"><?= count($enrolledStudents) ?> sinh viên ghi danh</span>
            </div>

            <?php if (!empty($enrolledStudents)): ?>
                <table class="classes-data-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">STT</th>
                            <th>MÃ SINH VIÊN</th>
                            <th>HỌ VÀ TÊN</th>
                            <th>LỚP HÀNH CHÍNH</th>
                            <th>EMAIL LIÊN HỆ</th>
                            <th>THỜI GIAN ĐĂNG KÝ</th>
                            <th>TRẠNG THÁI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrolledStudents as $idx => $sv): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><span class="class-code-tag"><?= htmlspecialchars($sv['ma_sinh_vien']) ?></span></td>
                                <td><strong><?= htmlspecialchars($sv['ho_ten']) ?></strong></td>
                                <td><?= htmlspecialchars($sv['lop_hanh_chinh']) ?></td>
                                <td style="color:#64748b;"><?= htmlspecialchars($sv['email']) ?></td>
                                <td style="font-size:12.5px; color:#64748b;"><?= date('d/m/Y H:i', strtotime($sv['ngay_dang_ky'])) ?></td>
                                <td>
                                    <span class="badge-enrolled-ok">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                        Hợp lệ
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #94a3b8;">
                    Chưa có sinh viên nào đăng ký vào lớp học phần này.
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>