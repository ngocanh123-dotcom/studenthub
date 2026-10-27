<?php
// app/Views/classes/detail.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tự nạp dữ liệu nếu truy cập trực tiếp
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

// Cấu hình tiêu đề trang cho header.php
$breadcrumb = '<a href="/studenthub/app/Views/sinhvien/index.php" style="color:#64748b; text-decoration:none;">Trang chủ</a> / Lớp học phần / <span style="color:#2563eb; font-weight:600;">Lớp ' . htmlspecialchars($classInfo['ma_lop']) . '</span>';
$pageTitle = 'Thông tin chi tiết lớp học phần';
$pageSubtitle = 'Xem kế hoạch giảng dạy, thời khóa biểu và danh sách sinh viên đăng ký lớp.';

$siSoHienTai = (int)($classInfo['si_so_hien_tai'] ?? 0);
$siSoToiDa = (int)($classInfo['si_so_toi_da'] ?? 45);
$choConTrong = max(0, $siSoToiDa - $siSoHienTai);
$percentFull = $siSoToiDa > 0 ? min(100, round(($siSoHienTai / $siSoToiDa) * 100)) : 0;

// 1. GỌI HEADER CHUNG (Tự động kèm Sidebar chuẩn)
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- NỘI DUNG CHI TIẾT LỚP HỌC PHẦN -->
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

    <div class="action-buttons-strip">
        <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" class="btn-back-link">
            &larr; Quay lại danh sách lớp
        </a>
        <button type="button" class="btn-enroll-action" onclick="alert('Đăng ký lớp thành công!')">
            + Đăng ký vào lớp này
        </button>
    </div>
</div>

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
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Hợp lệ
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
// 2. GỌI FOOTER CHUNG
require_once __DIR__ . '/../layouts/footer.php';
?>