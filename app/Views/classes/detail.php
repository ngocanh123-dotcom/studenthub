<?php
// app/Views/classes/detail.php
// Module Chi tiết lớp học phần dành cho Sinh viên (Read-only Detail View)
// Đảm bảo không nảy Warning và tuân thủ chuẩn an toàn dữ liệu PHP 8.x

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Nhận diện phiên đăng nhập sinh viên (chuẩn hóa các khóa Session theo kiến trúc hệ thống)
$currentUserId = (int)($_SESSION['user_id'] ?? ($_SESSION['nguoi_dung_id'] ?? 4));
$hoTenSv       = (string)($_SESSION['ho_ten'] ?? 'Nguyễn Thu Trang');
$maSv          = (string)($_SESSION['ten_dang_nhap'] ?? ($_SESSION['username'] ?? 'SV001'));

// 2. Nhận ID hoặc Mã lớp học phần từ URL (mặc định lấy lớp 1 nếu không truyền)
$classId = $_GET['id'] ?? ($_GET['ma_lop'] ?? 1);

// 3. Nạp Model và kết nối CSDL an toàn
require_once __DIR__ . '/../../Models/LopHocPhanModel.php';

$db = null;
$configFile = __DIR__ . '/../../../config/database.php';
if (file_exists($configFile)) {
    require_once $configFile;
    if (isset($conn) && $conn instanceof PDO) {
        $db = $conn;
    } elseif (isset($pdo) && $pdo instanceof PDO) {
        $db = $pdo;
    }
}

// Kết nối dự phòng an toàn nếu biến kết nối chưa khởi tạo
if (!$db) {
    $dbHost = 'localhost';
    $dbName = 'studenthub';
    $dbUser = 'root';
    $dbPass = '';

    try {
        $db = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]);
    } catch (PDOException $e) {
        // Ghi log lỗi nội bộ, không phơi bày thông tin nhạy cảm
        error_log("Detail view DB connection error: " . $e->getMessage());
    }
}

// 4. Lấy dữ liệu chi tiết lớp học phần thông qua Model
$lop = null;
if ($db instanceof PDO) {
    $model = new LopHocPhanModel($db);
    $lop = $model->getChiTietLop($classId, $currentUserId);
}

// Xử lý trường hợp không tìm thấy lớp học phần
if (!$lop) {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Không tìm thấy lớp học phần - StudentHub</title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="/studenthub/assets/css/sinhvien.css?v=<?= time() ?>">
    </head>
    <body class="sh-body">
        <div class="sh-container">
            <?php 
            $sidebarPath = __DIR__ . '/../layouts/sidebar.php';
            if (file_exists($sidebarPath)) {
                include $sidebarPath; 
            } 
            ?>
            <main class="sh-main-content">
                <div style="background:#fff; border-radius:14px; padding:60px 30px; text-align:center; border:1px solid #e2e8f0; margin-top:20px;">
                    <div style="font-size:48px; margin-bottom:12px;">🔍</div>
                    <h2 style="font-size:22px; color:#1e293b; margin-bottom:8px;">Không tìm thấy thông tin lớp học phần</h2>
                    <p style="color:#64748b; margin-bottom:24px;">Lớp học phần bạn đang tìm kiếm không tồn tại hoặc đã được gỡ bỏ khỏi hệ thống.</p>
                    <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" class="btn-enroll-action" style="padding:10px 22px; background:#2563eb; color:#fff; border-radius:8px; text-decoration:none; font-weight:600; display:inline-block;">
                        &larr; Quay lại danh sách lớp học phần
                    </a>
                </div>
            </main>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 5. Chuẩn hóa dữ liệu bằng toán tử Null Coalescing (??) - chống triệt để mọi PHP Undefined key / NULL Warning
$maLop              = (string)($lop['ma_lop'] ?? 'LHP');
$tenHocPhan         = (string)($lop['ten_hoc_phan'] ?? 'Chưa cập nhật tên');
$maHocPhan          = (string)($lop['ma_hoc_phan'] ?? 'HP');
$soTinChi           = (int)($lop['so_tin_chi'] ?? 0);
$tenHocKy           = (string)($lop['ten_hoc_ky'] ?? 'Học kỳ');
$trangThaiLop       = (string)($lop['trang_thai_lop'] ?? ($lop['trang_thai'] ?? 'mo'));
$tenGiangVien       = (string)($lop['ten_giang_vien'] ?? 'ThS Trần Bình Trọng');
$emailGiangVien     = (string)($lop['email_giang_vien'] ?? 'contact@studenthub.edu.vn');
$lichHoc            = (string)($lop['lich_hoc'] ?? 'Chưa có lịch học cố định');
$phongHoc           = (string)($lop['phong_hoc'] ?? 'Chưa cập nhật phòng học');
$ngayBatDau         = $lop['ngay_bat_dau'] ?? null;
$ngayKetThuc        = $lop['ngay_ket_thuc'] ?? null;
$dieuKienTienQuyet  = (string)($lop['dieu_kien_tien_quyet'] ?? 'Không có');
$moTaHocPhan        = (string)($lop['mo_ta_hoc_phan'] ?? ($lop['mo_ta'] ?? 'Chưa có đề cương chi tiết cho học phần này.'));
$ngayDangKyCuaBan   = $lop['ngay_dang_ky_cua_ban'] ?? null;

// Sĩ số và tính toán chỗ trống
$siSoHienTai        = (int)($lop['si_so_hien_tai'] ?? 0);
$siSoToiDa          = (int)($lop['si_so_toi_da'] ?? 40);
$conLai             = max(0, $siSoToiDa - $siSoHienTai);
$phanTram           = ($siSoToiDa > 0) ? min(100, (int)round(($siSoHienTai / $siSoToiDa) * 100)) : 0;

// Tính toán thời gian mở/đóng cổng đăng ký (Ưu tiên thời gian riêng của lớp nếu có, sau đó đến học kỳ)
$thoiGianMoRieng    = $lop['thoi_gian_mo_rieng'] ?? null;
$thoiGianDongRieng  = $lop['thoi_gian_dong_rieng'] ?? null;
$thoiGianMoDkHk     = $lop['thoi_gian_mo_dk'] ?? null;
$thoiGianDongDkHk   = $lop['thoi_gian_dong_dk'] ?? null;

$thoiGianMo         = !empty($thoiGianMoRieng) ? $thoiGianMoRieng : ($thoiGianMoDkHk ?? null);
$thoiGianDong       = !empty($thoiGianDongRieng) ? $thoiGianDongRieng : ($thoiGianDongDkHk ?? null);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết lớp <?= htmlspecialchars($maLop, ENT_QUOTES, 'UTF-8') ?> - StudentHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/studenthub/assets/css/sinhvien.css?v=<?= time() ?>">
</head>
<body class="sh-body">

<div class="sh-container">
    <!-- NẠP SIDEBAR DÙNG CHUNG -->
    <?php 
    $sidebarPath = __DIR__ . '/../layouts/sidebar.php';
    if (file_exists($sidebarPath)) {
        include $sidebarPath; 
    } 
    ?>

    <!-- NỘI DUNG CHÍNH (READ-ONLY DETAIL VIEW) -->
    <main class="sh-main-content">
        <!-- Topbar & Tiêu đề -->
        <header class="sh-top-header">
            <div>
                <div class="top-breadcrumb">
                    <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" class="breadcrumb-back-link">Danh sách lớp</a> 
                    <span style="color:#cbd5e1; margin:0 4px;">/</span> Chi tiết lớp <?= htmlspecialchars($maLop, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <h1>Thông tin chi tiết lớp học phần</h1>
                <p class="header-subtext">Tra cứu kế hoạch đào tạo, giảng viên phụ trách, phòng học và thời khóa biểu chính thức.</p>
            </div>

            <div class="profile-pill-box">
                <div class="profile-avatar-circle">SV</div>
                <div class="profile-name-role">
                    <strong><?= htmlspecialchars($hoTenSv, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span>Sinh viên • <?= htmlspecialchars($maSv, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </header>

        <!-- BẢNG ĐIỀU KHIỂN CHI TIẾT (BENTO DASHBOARD) -->
        <section class="detail-overview-card">
            <!-- 1. Header Môn & Trạng thái -->
            <div class="overview-header-flex">
                <div>
                    <span class="code-pill-blue"><?= htmlspecialchars($maLop, ENT_QUOTES, 'UTF-8') ?></span>
                    <h2 class="class-title"><?= htmlspecialchars($tenHocPhan, ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="meta-tag-sub">
                        Mã học phần: <strong><?= htmlspecialchars($maHocPhan, ENT_QUOTES, 'UTF-8') ?></strong> 
                        &nbsp;•&nbsp; Khối lượng: <strong><?= $soTinChi ?> Tín chỉ</strong>
                        &nbsp;•&nbsp; Học kỳ: <strong><?= htmlspecialchars($tenHocKy, ENT_QUOTES, 'UTF-8') ?></strong>
                    </p>
                </div>
                <div>
                    <?php 
                        $nowTime = time();
                        $moRieng = !empty($thoiGianMoRieng) ? strtotime($thoiGianMoRieng) : null;
                        $dongRieng = !empty($thoiGianDongRieng) ? strtotime($thoiGianDongRieng) : null;
                        if ($moRieng && $nowTime < $moRieng) {
                            $rtStatus = ['code' => 'khoa', 'label' => 'Đã khóa (Chưa đến hạn)', 'is_dang_mo' => false];
                        } elseif ($dongRieng && $nowTime > $dongRieng) {
                            $rtStatus = ['code' => 'khoa', 'label' => 'Đã khóa (Hết hạn đăng ký)', 'is_dang_mo' => false];
                        } else {
                            $isManualOpen = ($trangThaiLop === 'mo');
                            $rtStatus = ['code' => $isManualOpen ? 'mo' : 'khoa', 'label' => $isManualOpen ? 'Đang mở đăng ký' : 'Đã khóa đăng ký', 'is_dang_mo' => $isManualOpen];
                        }
                    ?>
                    <?php if ($rtStatus['is_dang_mo'] && $conLai > 0): ?>
                        <span class="badge-status-open">● Đang mở đăng ký</span>
                    <?php elseif ($rtStatus['is_dang_mo'] && $conLai <= 0): ?>
                        <span class="badge-status-full">● Đã đủ sĩ số</span>
                    <?php elseif (str_contains($rtStatus['label'], 'Chưa đến hạn')): ?>
                        <span class="badge-status-locked" style="background:#fffbeb; color:#b45309; border-color:#fde68a;">● <?= htmlspecialchars($rtStatus['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php else: ?>
                        <span class="badge-status-locked">● <?= htmlspecialchars($rtStatus['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Lưới 4 thông số vận hành (Quick Specs) -->
            <div class="specs-grid-container">
                <div class="spec-item">
                    <span class="spec-label">Giảng viên giảng dạy</span>
                    <strong class="spec-val"><?= htmlspecialchars($tenGiangVien, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="spec-desc"><?= htmlspecialchars($emailGiangVien, ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="spec-item">
                    <span class="spec-label">Thời khóa biểu</span>
                    <strong class="spec-val"><?= htmlspecialchars($lichHoc, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="spec-desc">Lịch học cố định trong tuần</span>
                </div>

                <div class="spec-item">
                    <span class="spec-label">Địa điểm &amp; Phòng học</span>
                    <strong class="spec-val"><?= htmlspecialchars($phongHoc, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="spec-desc">Phòng máy thực hành / Giảng đường</span>
                </div>

                <div class="spec-item">
                    <span class="spec-label">Thời gian triển khai</span>
                    <strong class="spec-val"><?= htmlspecialchars($tenHocKy, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="spec-desc">
                        <?= !empty($ngayBatDau) ? date('d/m/Y', strtotime($ngayBatDau)) : '01/09/2026' ?> 
                        đến 
                        <?= !empty($ngayKetThuc) ? date('d/m/Y', strtotime($ngayKetThuc)) : '15/01/2027' ?>
                    </span>
                </div>
            </div>

            <!-- 3. Tình trạng sĩ số & Thời gian đăng ký cá nhân -->
            <div class="class-capacity-box">
                <div class="capacity-labels">
                    <span class="cap-title">Tình trạng tiếp nhận sinh viên</span>
                    <span class="cap-numbers">
                        <strong><?= $siSoHienTai ?></strong> / <?= $siSoToiDa ?> sinh viên 
                        <span class="remain-pill">(Còn trống <?= $conLai ?> chỗ)</span>
                    </span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill <?= ($conLai === 0) ? 'danger' : '' ?>" style="width: <?= $phanTram ?>%;"></div>
                </div>

                <div class="enrollment-window-text">
                    Thời hạn mở cổng đăng ký: 
                    <strong>
                        <?= !empty($thoiGianMo) ? date('d/m/Y H:i', strtotime($thoiGianMo)) : 'Chưa thiết lập' ?>
                    </strong> 
                    đến 
                    <strong>
                        <?= !empty($thoiGianDong) ? date('d/m/Y H:i', strtotime($thoiGianDong)) : 'Chưa thiết lập' ?>
                    </strong>
                </div>

                <!-- Hiển thị thời gian đăng ký của sinh viên đang đăng nhập một cách có điều kiện -->
                <div class="personal-enrollment-status">
                    <?php if (!empty($ngayDangKyCuaBan)): ?>
                        <div class="enroll-badge-success">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <div>
                                <strong style="display:block; margin-bottom:2px;">Bạn đã đăng ký lớp học phần này thành công!</strong>
                                <span style="font-size:12.5px;">Thời gian ghi danh: <strong><?= date('d/m/Y \l\ú\c H:i:s', strtotime($ngayDangKyCuaBan)) ?></strong> (Suất học hợp lệ trên hệ thống).</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="enroll-badge-neutral">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <div>
                                <span>Bạn hiện chưa đăng ký lớp học phần này. Để ghi danh tín chỉ, vui lòng chuyển sang cổng <strong>Đăng ký học phần</strong>.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 4. Đề cương & Đánh giá môn học -->
            <div class="academic-syllabus-box">
                <h3 class="box-title">Đề cương &amp; Yêu cầu học thuật</h3>
                
                <div class="syllabus-grid">
                    <div class="syl-row">
                        <span class="syl-label">Điều kiện tiên quyết:</span>
                        <span class="syl-content"><?= htmlspecialchars($dieuKienTienQuyet, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="syl-row">
                        <span class="syl-label">Mục tiêu học phần:</span>
                        <span class="syl-content"><?= nl2br(htmlspecialchars($moTaHocPhan, ENT_QUOTES, 'UTF-8')) ?></span>
                    </div>
                    <div class="syl-row">
                        <span class="syl-label">Cơ cấu đánh giá:</span>
                        <div class="syl-grades">
                            <span>Chuyên cần: <strong>10%</strong></span>
                            <span>Bài tập lớn / Kiểm tra định kỳ: <strong>40%</strong></span>
                            <span>Thi kết thúc học phần: <strong>50%</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Điều hướng chân trang -->
            <div class="action-buttons-strip">
                <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" class="btn-back-link">
                    &larr; Quay lại danh sách lớp học phần
                </a>
                <a href="/studenthub/public/trang_dang_ky_hoc.php" class="btn-goto-enroll">
                    Đi tới cổng Đăng ký học phần &rarr;
                </a>
            </div>
        </section>
    </main>
</div>

</body>
</html>
