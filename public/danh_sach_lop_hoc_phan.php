<?php
session_start();

// =========================================================================
// 1. KẾT NỐI MYSQL BẰNG PDO
// =========================================================================
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} elseif (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $db_host = 'localhost';
    $db_name = 'studenthub';
    $db_user = 'root';
    $db_pass = '';

    try {
        $pdo = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
            $db_user,
            $db_pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        die("Lỗi kết nối CSDL studenthub bằng PDO: " . $e->getMessage());
    }
}

// =========================================================================
// 2. THÔNG TIN SINH VIÊN ĐANG ĐĂNG NHẬP
// =========================================================================
if (!isset($_SESSION['nguoi_dung_id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['nguoi_dung_id'] = 4;
    $_SESSION['username']      = 'SV001';
    $_SESSION['ho_ten']        = 'Nguyễn Thu Trang';
    $_SESSION['role']          = 'sinhvien';
}
$sinh_vien_id_hien_tai = (int)($_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 4);
$hoTen                 = $_SESSION['ho_ten'] ?? 'Nguyễn Thu Trang';
$maSinhVien            = $_SESSION['username'] ?? 'SV001';

// =========================================================================
// 3. NHẬN THAM SỐ TÌM KIẾM & BỘ LỌC
// =========================================================================
$tu_khoa       = trim($_GET['tu_khoa'] ?? '');
$hoc_ky_id     = isset($_GET['hoc_ky_id']) ? trim((string)$_GET['hoc_ky_id']) : '';
$giang_vien_id = isset($_GET['giang_vien_id']) ? trim((string)$_GET['giang_vien_id']) : '';

// =========================================================================
// 4. XÂY DỰNG ĐIỀU KIỆN LỌC (WHERE)
// =========================================================================
$where  = ["1=1"];
$params = [':sv_id' => $sinh_vien_id_hien_tai];

if ($tu_khoa !== '') {
    $where[] = "(lhp.ma_lop LIKE :kw1 OR hp.ma_hoc_phan LIKE :kw2 OR hp.ten_hoc_phan LIKE :kw3)";
    $params[':kw1'] = '%' . $tu_khoa . '%';
    $params[':kw2'] = '%' . $tu_khoa . '%';
    $params[':kw3'] = '%' . $tu_khoa . '%';
}

if ($hoc_ky_id !== '' && ctype_digit($hoc_ky_id)) {
    $where[] = "lhp.hoc_ky_id = :hoc_ky_id";
    $params[':hoc_ky_id'] = (int)$hoc_ky_id;
}

if ($giang_vien_id !== '' && ctype_digit($giang_vien_id)) {
    $where[] = "lhp.giang_vien_id = :giang_vien_id";
$params[':giang_vien_id'] = (int)$giang_vien_id;
}

$where_sql = implode(' AND ', $where);

// =========================================================================
// 5. TRUY VẤN DỮ LIỆU & GOM NHÓM THEO (HỌC PHẦN + HỌC KỲ)
// =========================================================================
$sql = "
    SELECT 
        lhp.id,
        lhp.ma_lop,
        lhp.hoc_phan_id,
        lhp.hoc_ky_id,
        lhp.giang_vien_id,
        lhp.si_so_toi_da,
        lhp.thoi_gian_mo_rieng,
        lhp.thoi_gian_dong_rieng,

        hp.ma_hoc_phan,
        hp.ten_hoc_phan,
        hp.so_tin_chi,

        hk.ten_hoc_ky,
        hk.ngay_bat_dau,
        hk.ngay_ket_thuc,
        hk.thoi_gian_mo_dk,
        hk.thoi_gian_dong_dk,

        nd.ten_dang_nhap AS ma_giang_vien,
        nd.ho_ten AS ho_ten_giang_vien,
        nd.email AS email_giang_vien,

        (
            SELECT COUNT(*) 
            FROM dang_ky_hoc dkh 
            WHERE dkh.lop_hoc_phan_id = lhp.id 
              AND dkh.trang_thai = 'dang_ky'
        ) AS si_so_hien_tai,

        (
            SELECT COUNT(*) 
            FROM dang_ky_hoc dkh_sv 
            WHERE dkh_sv.lop_hoc_phan_id = lhp.id 
              AND dkh_sv.sinh_vien_id = :sv_id 
              AND dkh_sv.trang_thai = 'dang_ky'
        ) AS sv_da_dang_ky

    FROM lop_hoc_phan lhp
    INNER JOIN hoc_phan hp ON lhp.hoc_phan_id = hp.id
    INNER JOIN hoc_ky hk ON lhp.hoc_ky_id = hk.id
    INNER JOIN nguoi_dung nd ON lhp.giang_vien_id = nd.id
    WHERE $where_sql
    ORDER BY lhp.hoc_ky_id ASC, hp.ma_hoc_phan ASC, lhp.ma_lop ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$danh_sach_goc = $stmt->fetchAll();

$nhom_hoc_phan = [];
$tong_so_lop   = 0;
$so_lop_da_dk  = 0;

foreach ($danh_sach_goc as $row) {
    $tong_so_lop++;

    if ((int)$row['sv_da_dang_ky'] > 0) {
        $so_lop_da_dk++;
    }

    $group_key = $row['hoc_ky_id'] . '_' . $row['hoc_phan_id'];
    if (!isset($nhom_hoc_phan[$group_key])) {
        $nhom_hoc_phan[$group_key] = [
            'hoc_phan_id'   => (int)$row['hoc_phan_id'],
            'hoc_ky_id'     => (int)$row['hoc_ky_id'],
            'ma_hoc_phan'   => $row['ma_hoc_phan'],
            'ten_hoc_phan'  => $row['ten_hoc_phan'],
            'so_tin_chi'    => (int)$row['so_tin_chi'],
            'ten_hoc_ky'    => $row['ten_hoc_ky'],
            'ds_lop'        => []
        ];
    }

    $nhom_hoc_phan[$group_key]['ds_lop'][] = $row;
}

$tong_so_mon = count($nhom_hoc_phan);

$ds_hoc_ky     = $pdo->query("SELECT id, ten_hoc_ky FROM hoc_ky ORDER BY id ASC")->fetchAll();
$ds_giang_vien = $pdo->query("SELECT id, ten_dang_nhap, ho_ten FROM nguoi_dung WHERE vai_tro = 'giang_vien' ORDER BY id ASC")->fetchAll();

$ten_hk_dang_chon = '';
foreach ($ds_hoc_ky as $hk_item) {
    if ($hoc_ky_id !== '' && (int)$hoc_ky_id === (int)$hk_item['id']) {
        $ten_hk_dang_chon = $hk_item['ten_hoc_ky'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách lớp học phần - StudentHub</title>
    <link rel="stylesheet" href="/studenthub/assets/css/sinhvien.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-bar {
            padding: 16px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            grid-template-columns: 2fr 1.3fr 1.3fr auto;
            gap: 12px;
            align-items: center;
        }
        .input-control {
            width: 100%;
            height: 40px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 0 14px;
            font-size: 13.5px;
            color: #0f172a;
            background: #fff;
            outline: none;
            transition: all 0.2s;
        }
        .input-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
        .btn-filter-custom {
            height: 40px;
            padding: 0 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #fff;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .btn-filter-custom:hover { background: #1d4ed8; }
        .btn-outline-custom {
            height: 40px;
            padding: 0 14px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #0f172a;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .btn-outline-custom:hover { border-color: #2563eb; color: #2563eb; }

        .active-filter-banner {
            padding: 10px 24px;
            background: #eff6ff;
            border-bottom: 1px solid #dbeafe;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #1e40af;
        }

        .stats-grid-custom {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }
        .stat-card-custom {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px 22px;
            display: flex;
align-items: center;
            gap: 16px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        }
        .stat-icon-custom {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 19px;
        }
        .stat-icon-custom.blue { background: #eff6ff; color: #2563eb; }
        .stat-icon-custom.indigo { background: #eef2ff; color: #4f46e5; }
        .stat-icon-custom.green { background: #ecfdf5; color: #059669; }
        .stat-info-custom span {
            display: block;
            font-size: 13px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .stat-info-custom strong {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
        }

        .panel-card-custom {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .panel-top-custom {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }

        .course-list-container {
            padding: 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .course-accordion-item {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        .course-accordion-item.open {
            border-color: #93c5fd;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.07);
        }
        .course-header-bar {
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            background: #ffffff;
            user-select: none;
            transition: background 0.15s;
            gap: 16px;
        }
        .course-header-bar:hover {
            background: #f8fafc;
        }
        .course-accordion-item.open .course-header-bar {
            background: #f0f7ff;
            border-bottom: 1px solid #dbeafe;
        }
        .course-main-info {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .course-code-badge {
            background: #2563eb;
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            padding: 6px 12px;
            border-radius: 8px;
            letter-spacing: 0.3px;
        }
        .course-title-group h4 {
            font-size: 16px;
            font-weight: 700;
color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .course-meta-tags {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 12.5px;
            color: #64748b;
            margin-top: 4px;
        }
        .semester-badge-inline {
            background: #eef2ff;
            color: #4f46e5;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
        }
        .course-right-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .class-count-pill {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 700;
        }
        .toggle-btn-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f1f5f9;
            color: #475569;
            display: grid;
            place-items: center;
            transition: transform 0.25s ease, background 0.2s;
        }
        .course-accordion-item.open .toggle-btn-circle {
            transform: rotate(180deg);
            background: #2563eb;
            color: #ffffff;
        }

        .course-classes-body {
            display: none;
            padding: 16px 20px;
            background: #f8fafc;
        }
        .course-accordion-item.open .course-classes-body {
            display: block;
        }
        .class-row-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: 1.5fr 1.8fr 1.2fr auto;
            align-items: center;
            gap: 16px;
            transition: all 0.15s;
        }
        .class-row-card:last-child {
            margin-bottom: 0;
        }
        .class-row-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }

        .class-col-lecturer {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .class-code-tag {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            font-weight: 800;
            font-size: 13px;
            padding: 6px 11px;
            border-radius: 8px;
            white-space: nowrap;
        }
        .lecturer-box {
            display: flex;
            flex-direction: column;
        }
        .lecturer-name {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
gap: 6px;
        }
        .lecturer-name i {
            color: #2563eb;
            font-size: 13px;
        }
        .lecturer-code {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .class-col-schedule {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .schedule-main {
            font-size: 13.5px;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .schedule-sub {
            font-size: 12px;
            color: #64748b;
        }

        .class-col-capacity {
            display: flex;
            flex-direction: column;
        }
        .capacity-text {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
        }

        .class-col-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        .btn-detail-main {
            height: 36px;
            padding: 0 16px;
            font-size: 13px;
            border-radius: 9px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #1e293b;
        }
        .btn-detail-main:hover {
            border-color: #2563eb;
            color: #2563eb;
            background: #f0f7ff;
            transform: translateY(-1px);
        }

        @media (max-width: 1024px) {
            .filter-bar { grid-template-columns: 1fr 1fr; }
            .stats-grid-custom { grid-template-columns: 1fr; }
            .class-row-card { grid-template-columns: 1fr; gap: 12px; }
            .class-col-actions { justify-content: flex-start; }
        }
    </style>
</head>
<body class="sh-body">

<div class="sh-container">
    <!-- 1. SIDEBAR -->
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
<<<<<<< HEAD
                    <span>Tổng quan</span>
=======
<span>Tổng quan</span>
>>>>>>> 0cd1de15a90e55912994249ab1f8b623fa34d3df
                </a>

                <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" class="sh-nav-item active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Danh sách lớp học phần</span>
                </a>

                <a href="/studenthub/public/trang_dang_ky_hoc.php" class="sh-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    <span>Đăng ký học phần</span>
                </a>

<<<<<<< HEAD
=======
                <a href="/studenthub/app/Views/lichsu/index.php" class="sh-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 14 14"></polyline>
                    </svg>
                    <span>Chi tiết lớp học phần</span>
                </a>

>>>>>>> 0cd1de15a90e55912994249ab1f8b623fa34d3df
                <a href="/studenthub/app/Views/lichsu/index.php" class="sh-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 14 14"></polyline>
                    </svg>
                    <span>Lịch sử đăng ký</span>
                </a>

<<<<<<< HEAD
                <!-- 5. Đổi mật khẩu -->
=======
>>>>>>> 0cd1de15a90e55912994249ab1f8b623fa34d3df
                <a href="javascript:void(0)" onclick="openChangePasswordModal()" class="sh-nav-item change-pass-link" style="color: #38bdf8;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                    </svg>
                    <span>Đổi mật khẩu</span>
                </a>

                <a href="/studenthub/public/index.php?action=logout" class="sh-nav-item logout-link">
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
<span>Học kỳ 1 (2026 - 2027)</span>
            </div>
            <p>Cổng đăng ký tín chỉ đang mở cho sinh viên toàn trường.</p>
        </div>
    </aside>
<script src="/studenthub/assets/js/change_password.js"></script>

    <!-- 2. NỘI DUNG CHÍNH -->
    <main class="sh-main-content">
        <header class="sh-top-header">
            <div>
                <div class="top-breadcrumb">Trang chủ / Danh sách lớp học phần</div>
                <h1>Danh sách lớp học phần theo môn</h1>
                <p class="header-subtext">Tra cứu các môn học phần mở theo từng học kỳ, xem danh sách lớp, giảng viên phụ trách, lịch học và xem chi tiết lớp.</p>
            </div>

            <div class="profile-pill-box">
                <div class="profile-avatar-circle">SV</div>
                <div class="profile-name-role">
                    <strong><?= htmlspecialchars($hoTen) ?></strong>
                    <span>Sinh viên • <?= htmlspecialchars($maSinhVien) ?></span>
                </div>
            </div>
        </header>

        <!-- 3 THẺ THỐNG KÊ NHANH -->
        <div class="stats-grid-custom">
            <div class="stat-card-custom">
                <div class="stat-icon-custom blue"><i class="fa-solid fa-layer-group"></i></div>
                <div class="stat-info-custom">
                    <span>Tổng số môn học phần</span>
                    <strong><?= $tong_so_mon ?></strong>
                </div>
            </div>
            <div class="stat-card-custom">
                <div class="stat-icon-custom indigo"><i class="fa-solid fa-chalkboard-user"></i></div>
                <div class="stat-info-custom">
                    <span>Tổng số lớp học phần</span>
                    <strong><?= $tong_so_lop ?></strong>
                </div>
            </div>
            <div class="stat-card-custom">
                <div class="stat-icon-custom green"><i class="fa-solid fa-circle-check"></i></div>
                <div class="stat-info-custom">
                    <span>Lớp bạn đã đăng ký</span>
                    <strong><?= $so_lop_da_dk ?></strong>
                </div>
            </div>
        </div>

        <!-- KHUNG CHỨA ACCORDION -->
        <section class="panel-card-custom">
            <div class="panel-top-custom">
                <div>
                    <h3 style="font-size: 17px; font-weight: 700; color: #0f172a;">Danh mục môn học phần</h3>
                    <p style="font-size: 13px; color: #64748b; margin-top: 2px;">Chọn học kỳ hoặc bấm vào từng môn để xem giảng viên giảng dạy, lịch học và chi tiết lớp</p>
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="button" onclick="toggleAllCourses(true)" class="btn-outline-custom" style="height:36px; font-size:12.5px;">
                        <i class="fa-solid fa-angles-down"></i> Mở tất cả lớp
                    </button>
<button type="button" onclick="toggleAllCourses(false)" class="btn-outline-custom" style="height:36px; font-size:12.5px;">
                        <i class="fa-solid fa-angles-up"></i> Thu gọn
                    </button>
                </div>
            </div>

            <!-- THANH TÌM KIẾM & BỘ LỌC -->
            <form method="GET" action="danh_sach_lop_hoc_phan.php" class="filter-bar">
                <input type="text" name="tu_khoa" class="input-control"
                       placeholder="Tìm mã HP, tên môn học (có thể để trống để lọc theo học kỳ)..."
                       value="<?= htmlspecialchars($tu_khoa) ?>">

                <select name="hoc_ky_id" class="input-control" onchange="this.form.submit()">
                    <option value="">-- Tất cả học kỳ --</option>
                    <?php foreach ($ds_hoc_ky as $hk): ?>
                        <option value="<?= (int)$hk['id'] ?>" <?= ($hoc_ky_id !== '' && (int)$hoc_ky_id === (int)$hk['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($hk['ten_hoc_ky']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="giang_vien_id" class="input-control" onchange="this.form.submit()">
                    <option value="">-- Tất cả giảng viên --</option>
                    <?php foreach ($ds_giang_vien as $gv): ?>
                        <option value="<?= (int)$gv['id'] ?>" <?= ($giang_vien_id !== '' && (int)$giang_vien_id === (int)$gv['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($gv['ho_ten']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div style="display:flex; gap:6px;">
                    <button type="submit" class="btn-filter-custom">
                        <i class="fa-solid fa-magnifying-glass"></i> Lọc
                    </button>
                    <a href="danh_sach_lop_hoc_phan.php" class="btn-outline-custom" title="Đặt lại bộ lọc">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>

            <!-- HIỂN THỊ TRẠNG THÁI ĐANG LỌC -->
            <?php if ($ten_hk_dang_chon !== '' || $tu_khoa !== '' || $giang_vien_id !== ''): ?>
                <div class="active-filter-banner">
                    <div>
                        <i class="fa-solid fa-filter"></i>
                        Đang lọc kết quả
                        <?php if ($ten_hk_dang_chon !== ''): ?>
                            thuộc <strong><?= htmlspecialchars($ten_hk_dang_chon) ?></strong>
                        <?php endif; ?>
                        <?php if ($tu_khoa !== ''): ?>
                            • Từ khóa: <strong>"<?= htmlspecialchars($tu_khoa) ?>"</strong>
                        <?php endif; ?>
(Tìm thấy <strong><?= $tong_so_mon ?></strong> môn học phần / <strong><?= $tong_so_lop ?></strong> lớp)
                    </div>
                    <a href="danh_sach_lop_hoc_phan.php" style="color:#1e40af; font-weight:600; text-decoration:none;">
                        <i class="fa-solid fa-xmark"></i> Xem tất cả
                    </a>
                </div>
            <?php endif; ?>

            <!-- DANH SÁCH CÁC MÔN HỌC (ACCORDION) -->
            <div class="course-list-container">
                <?php if (empty($nhom_hoc_phan)): ?>
                    <div style="text-align:center; padding:42px; color:#64748b;">
                        Không tìm thấy môn học phần nào mở trong học kỳ hoặc điều kiện lọc này.
                    </div>
                <?php else: ?>
                    <?php 
                    $index = 0;
                    foreach ($nhom_hoc_phan as $group_key => $mon): 
                        $index++;
                        $is_open = ($index === 1 || $hoc_ky_id !== '' || $tu_khoa !== '' || $giang_vien_id !== '');
                    ?>
                        <div class="course-accordion-item <?= $is_open ? 'open' : '' ?>">
                            <!-- HEADER MÔN HỌC -->
                            <div class="course-header-bar" onclick="toggleCourse(this)">
                                <div class="course-main-info">
                                    <span class="course-code-badge"><?= htmlspecialchars($mon['ma_hoc_phan']) ?></span>
                                    <div class="course-title-group">
                                        <h4>
                                            <?= htmlspecialchars($mon['ten_hoc_phan']) ?>
                                        </h4>
                                        <div class="course-meta-tags">
                                            <span><i class="fa-solid fa-graduation-cap"></i> <?= $mon['so_tin_chi'] ?> tín chỉ</span>
                                            <span>•</span>
                                            <span class="semester-badge-inline">
                                                <i class="fa-regular fa-calendar"></i> <?= htmlspecialchars($mon['ten_hoc_ky']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="course-right-actions">
                                    <span class="class-count-pill">
                                        <?= count($mon['ds_lop']) ?> lớp
                                    </span>
                                    <div class="toggle-btn-circle" title="Bấm để xem các lớp">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>
                                </div>
</div>

                            <!-- DANH SÁCH CÁC LỚP CON -->
                            <div class="course-classes-body">
                                <?php foreach ($mon['ds_lop'] as $lop): 
                                    $lich_hk = date('d/m/Y', strtotime($lop['ngay_bat_dau'])) . ' - ' . date('d/m/Y', strtotime($lop['ngay_ket_thuc']));
                                    $mo_dk   = $lop['thoi_gian_mo_rieng'] ?? $lop['thoi_gian_mo_dk'];
                                    $dong_dk = $lop['thoi_gian_dong_rieng'] ?? $lop['thoi_gian_dong_dk'];
                                ?>
                                    <div class="class-row-card">
                                        <!-- Cột 1: Mã lớp + Giảng viên phụ trách -->
                                        <div class="class-col-lecturer">
                                            <span class="class-code-tag"><?= htmlspecialchars($lop['ma_lop']) ?></span>
                                            <div class="lecturer-box">
                                                <span class="lecturer-name">
                                                    <i class="fa-solid fa-user-tie"></i>
                                                    <?= htmlspecialchars($lop['ho_ten_giang_vien']) ?>
                                                </span>
                                                <span class="lecturer-code">Mã GV: <?= htmlspecialchars($lop['ma_giang_vien']) ?></span>
                                            </div>
                                        </div>

                                        <!-- Cột 2: Lịch học -->
                                        <div class="class-col-schedule">
                                            <div class="schedule-main">
                                                <i class="fa-regular fa-calendar-days" style="color:#2563eb;"></i>
                                                <span>Lịch học: <?= $lich_hk ?></span>
                                            </div>
                                            <?php if ($mo_dk && $dong_dk): ?>
                                                <div class="schedule-sub">
                                                    <i class="fa-regular fa-clock"></i> ĐK: <?= date('d/m/Y', strtotime($mo_dk)) ?> - <?= date('d/m/Y', strtotime($dong_dk)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Cột 3: Sĩ số tổng -->
                                        <div class="class-col-capacity">
                                            <div class="capacity-text">
                                                Sĩ số: <?= (int)$lop['si_so_toi_da'] ?>
                                            </div>
                                        </div>
<!-- Cột 4: Nút Chi tiết (Truyền đúng ID của từng lớp) -->
                                        <div class="class-col-actions">
                                            <a href="/studenthub/app/Views/classes/detail.php?id=<?= (int)$lop['id'] ?>" class="btn-detail-main" title="Xem chi tiết lớp học phần">
                                                <i class="fa-regular fa-eye" style="color:#2563eb;"></i> Chi tiết
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<script>
    function toggleCourse(headerEl) {
        const item = headerEl.closest('.course-accordion-item');
        item.classList.toggle('open');
    }

    function toggleAllCourses(expand) {
        const items = document.querySelectorAll('.course-accordion-item');
        items.forEach(item => {
            if (expand) {
                item.classList.add('open');
            } else {
                item.classList.remove('open');
            }
        });
    }
</script>

</body>
</html>
