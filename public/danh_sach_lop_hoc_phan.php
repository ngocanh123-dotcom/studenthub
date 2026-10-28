<?php
session_start();

// =========================================================================
// 1. KẾT NỐI MYSQL BẰNG PDO (CHUẨN NHIỆM VỤ THÀNH VIÊN 1 - HUYỀN)
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
// 2. THÔNG TIN SINH VIÊN ĐANG ĐĂNG NHẬP (Mặc định SV001 - Nguyễn Thu Trang)
// =========================================================================
if (!isset($_SESSION['nguoi_dung_id'])) {
    $_SESSION['nguoi_dung_id'] = 4;
    $_SESSION['username']      = 'SV001';
    $_SESSION['ho_ten']        = 'Nguyễn Thu Trang';
    $_SESSION['role']          = 'sinhvien';
}
$sinh_vien_id_hien_tai = (int)$_SESSION['nguoi_dung_id'];
$hoTen                 = $_SESSION['ho_ten'] ?? 'Nguyễn Thu Trang';
$maSinhVien            = $_SESSION['username'] ?? 'SV001';

// =========================================================================
// 3. NHẬN CÁC THAM SỐ TÌM KIẾM & BỘ LỌC LỚP HỌC PHẦN
// =========================================================================
$tu_khoa       = trim($_GET['tu_khoa'] ?? '');
$hoc_ky_id     = trim($_GET['hoc_ky_id'] ?? '');
$giang_vien_id = trim($_GET['giang_vien_id'] ?? '');
$trang_thai    = trim($_GET['trang_thai'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 6;

// =========================================================================
// 4. XÂY DỰNG ĐIỀU KIỆN LỌC (WHERE) BẰNG PDO PREPARED STATEMENTS
// =========================================================================
$where  = ["1=1"];
$params = [':sv_id' => $sinh_vien_id_hien_tai];

if ($tu_khoa !== '') {
    $where[] = "(lhp.ma_lop LIKE :kw1 OR hp.ma_hoc_phan LIKE :kw2 OR hp.ten_hoc_phan LIKE :kw3)";
    $params[':kw1'] = '%' . $tu_khoa . '%';
    $params[':kw2'] = '%' . $tu_khoa . '%';
    $params[':kw3'] = '%' . $tu_khoa . '%';
}

if ($hoc_ky_id !== '') {
    $where[] = "lhp.hoc_ky_id = :hoc_ky_id";
    $params[':hoc_ky_id'] = (int)$hoc_ky_id;
}

if ($giang_vien_id !== '') {
$where[] = "lhp.giang_vien_id = :giang_vien_id";
    $params[':giang_vien_id'] = (int)$giang_vien_id;
}

if ($trang_thai === 'mo') {
    $where[] = "lhp.trang_thai = 'mo'";
} elseif ($trang_thai === 'khoa') {
    $where[] = "lhp.trang_thai = 'khoa'";
}

$where_sql = implode(' AND ', $where);

// =========================================================================
// 5. TRUY VẤN DỮ LIỆU TỪ CÁC BẢNG LIÊN KẾT
// =========================================================================
$sql = "
    SELECT 
        lhp.id,
        lhp.ma_lop,
        lhp.hoc_phan_id,
        lhp.hoc_ky_id,
        lhp.giang_vien_id,
        lhp.si_so_toi_da,
        lhp.trang_thai,
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
        hk.trang_thai_dang_ky,

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
    ORDER BY lhp.ma_lop ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$danh_sach_goc = $stmt->fetchAll();

$danh_sach_lop  = [];
$so_lop_dang_mo = 0;
$so_lop_da_dk   = 0;

foreach ($danh_sach_goc as $row) {
    $row['so_cho_con_lai'] = max(0, (int)$row['si_so_toi_da'] - (int)$row['si_so_hien_tai']);

    if ($trang_thai === 'con_cho' && ($row['trang_thai'] !== 'mo' || $row['trang_thai_dang_ky'] == 0 || $row['so_cho_con_lai'] <= 0)) {
        continue;
    }
    if ($trang_thai === 'du_si_so' && $row['so_cho_con_lai'] > 0) {
        continue;
    }

    if ($row['trang_thai'] === 'mo' && (int)$row['trang_thai_dang_ky'] === 1 && $row['so_cho_con_lai'] > 0) {
        $so_lop_dang_mo++;
    }
    if ((int)$row['sv_da_dang_ky'] > 0) {
        $so_lop_da_dk++;
    }

    $danh_sach_lop[] = $row;
}

// =========================================================================
// 6. XUẤT FILE CSV
// =========================================================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Danh_Sach_Lop_Hoc_Phan.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($out, [
        'STT', 'Mã lớp mở', 'Mã học phần', 'Tên học phần', 'Số tín chỉ',
        'Giảng viên', 'Học kỳ', 'Lịch học', 'Đã đăng ký', 'Sĩ số tối đa', 'Chỗ trống', 'Trạng thái'
    ]);

    $stt = 1;
    foreach ($danh_sach_lop as $r) {
        $lich_hk = date('d/m/Y', strtotime($r['ngay_bat_dau'])) . ' - ' . date('d/m/Y', strtotime($r['ngay_ket_thuc']));
        $bi_khoa = ($r['trang_thai'] === 'khoa' || (int)$r['trang_thai_dang_ky'] === 0);
        $day_cho = ((int)$r['so_cho_con_lai'] <= 0);
        $ten_tt  = $bi_khoa ? 'Đã khóa' : ($day_cho ? 'Đủ sĩ số' : 'Đang mở');

        fputcsv($out, [
            $stt++, $r['ma_lop'], $r['ma_hoc_phan'], $r['ten_hoc_phan'], $r['so_tin_chi'],
            $r['ho_ten_giang_vien'], $r['ten_hoc_ky'], $lich_hk, $r['si_so_hien_tai'],
            $r['si_so_toi_da'], $r['so_cho_con_lai'], $ten_tt
        ]);
    }
    fclose($out);
    exit;
}

// =========================================================================
// 7. PHÂN TRANG & DROPDOWN BỘ LỌC
// =========================================================================
$total_rows   = count($danh_sach_lop);
$total_pages  = max(1, (int)ceil($total_rows / $per_page));
$page         = min($page, $total_pages);
$offset       = ($page - 1) * $per_page;
$lop_hien_thi = array_slice($danh_sach_lop, $offset, $per_page);

$ds_hoc_ky     = $pdo->query("SELECT id, ten_hoc_ky, trang_thai_dang_ky FROM hoc_ky ORDER BY id ASC")->fetchAll();
$ds_giang_vien = $pdo->query("SELECT id, ten_dang_nhap, ho_ten FROM nguoi_dung WHERE vai_tro = 'giang_vien' ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách lớp học phần - StudentHub</title>
    <!-- Nhúng đúng tệp CSS chuẩn của sinh viên (sinhvien.css) để đồng bộ hoàn toàn -->
    <link rel="stylesheet" href="/studenthub/assets/css/sinhvien.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Bổ sung các style riêng cho bảng dữ liệu và bộ lọc khớp giao diện chung */
        .filter-bar {
            padding: 16px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            grid-template-columns: 2fr 1.2fr 1.3fr 1.1fr auto;
            gap: 10px;
            align-items: center;
        }
        .input-control {
            width: 100%;
            height: 38px;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 0 12px;
            font-size: 13px;
            color: #0f172a;
            background: #fff;
            outline: none;
        }
.input-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
        .btn-filter-custom {
            height: 38px;
            padding: 0 14px;
            border-radius: 9px;
            font-size: 13px;
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
            height: 38px;
            padding: 0 14px;
            border-radius: 9px;
            font-size: 13px;
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
        
        .stats-grid-custom {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 22px;
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
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 18px;
        }
        .stat-icon-custom.blue { background: #eff6ff; color: #2563eb; }
        .stat-icon-custom.green { background: #ecfdf5; color: #059669; }
        .stat-icon-custom.amber { background: #fffbeb; color: #d97706; }
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
        .table-wrap-custom { overflow-x: auto; }
        .table-wrap-custom table { width: 100%; border-collapse: collapse; text-align: left; }
        .table-wrap-custom thead { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .table-wrap-custom th {
            padding: 13px 16px;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            white-space: nowrap;
        }
        .table-wrap-custom td {
            padding: 14px 16px;
            font-size: 13.5px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .table-wrap-custom tbody tr:hover { background: #f8fbff; }

        .code-pill {
            font-weight: 700;
            color: #2563eb;
            background: #eff6ff;
            padding: 4px 10px;
            border-radius: 7px;
            font-size: 12.5px;
            display: inline-block;
        }
        .course-name { font-weight: 600; color: #0f172a; }
        .sub-text { font-size: 12px; color: #64748b; margin-top: 2px; }

        .badge-mo { background: #ecfdf5; color: #059669; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;}
        .badge-day { background: #fffbeb; color: #d97706; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;}
        .badge-khoa { background: #fef2f2; color: #dc2626; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;}

        .capacity-num { font-weight: 600; color: #0f172a; font-size: 13px; }
        .progress {
            width: 85px;
            height: 5px;
            background: #e2e8f0;
            border-radius: 99px;
            overflow: hidden;
            margin-top: 5px;
        }
        .progress-bar { height: 100%; background: #2563eb; }

        .btn-sm-action { height: 32px; padding: 0 11px; font-size: 12px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; cursor: pointer;}
        .btn-outline-sm { border: 1px solid #e2e8f0; background: #fff; color: #0f172a; }
        .btn-outline-sm:hover { border-color: #2563eb; color: #2563eb; }
        .btn-blue-sm { background: #2563eb; color: #fff; border: none; }
        .btn-blue-sm:hover { background: #1d4ed8; }
        .btn-registered-sm { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .btn-disabled-sm { background: #f1f5f9; color: #94a3b8; border: none; cursor: not-allowed; }

        .pagination-bar {
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
background: #fcfdff;
        }
        .pages { display: flex; gap: 6px; }
        .page-item {
            padding: 5px 11px;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            text-decoration: none;
            color: #0f172a;
            font-size: 12.5px;
            font-weight: 600;
            background: #fff;
        }
        .page-item.active { background: #2563eb; color: #fff; border-color: #2563eb; }

        @media (max-width: 1024px) {
            .filter-bar { grid-template-columns: 1fr 1fr; }
            .stats-grid-custom { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="sh-body">

<div class="sh-container">
    <!-- 1. CỘT SIDEBAR XANH ĐEN BÊN TRÁI (CHUẨN Y HỆT TRANG CHỦ SINH VIÊN) -->
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
                <a href="/studenthub/app/Views/sinhvien/index.php" class="sh-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span>Tổng quan</span>
                </a>

                <!-- Trang hiện tại được active -->
                <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" class="sh-nav-item active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Danh sách lớp học phần</span>
                </a>
                 <a href="#" class="sh-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    <span>Đăng ký học phần</span>
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

                <a href="/studenthub/app/Views/lichsu/index.php" class="sh-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 14 14"></polyline>
                    </svg>
                    <span>Lịch sử đăng ký</span>
                </a>

                <!-- NÚT ĐĂNG XUẤT -->
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

        <!-- Khối trạng thái học kỳ dưới đáy Sidebar -->
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
        <!-- Header trên cùng chuẩn trang chủ sinh viên -->
        <header class="sh-top-header">
            <div>
                <div class="top-breadcrumb">Trang chủ / Danh sách lớp học phần</div>
                <h1>Danh sách lớp học phần</h1>
                <p class="header-subtext">Tra cứu các lớp học phần mở trong học kỳ, kiểm tra lịch học, sĩ số và chọn lớp đăng ký.</p>
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
<!-- 3 THẺ THỐNG KÊ NHANH CHO SINH VIÊN -->
        <div class="stats-grid-custom">
            <div class="stat-card-custom">
                <div class="stat-icon-custom blue"><i class="fa-solid fa-book-open"></i></div>
                <div class="stat-info-custom">
                    <span>Tổng số lớp học phần</span>
                    <strong><?= $total_rows ?></strong>
                </div>
            </div>
            <div class="stat-card-custom">
                <div class="stat-icon-custom amber"><i class="fa-regular fa-clock"></i></div>
                <div class="stat-info-custom">
                    <span>Lớp đang mở còn chỗ</span>
                    <strong><?= $so_lop_dang_mo ?></strong>
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

        <!-- BẢNG DANH SÁCH & BỘ LỌC -->
        <section class="panel-card-custom">
            <div class="panel-top-custom">
                <div>
                    <h3 style="font-size: 17px; font-weight: 700; color: #0f172a;">Tra cứu lớp học phần</h3>
                    <p style="font-size: 13px; color: #64748b; margin-top: 2px;">Hiển thị mã lớp mở, tên học phần, giảng viên, học kỳ, lịch học và sĩ số</p>
                </div>

                <div>
                    <?php $csv_link = http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>
                    <a href="?<?= htmlspecialchars($csv_link) ?>" class="btn-outline-custom">
                        <i class="fa-solid fa-file-excel" style="color:#059669;"></i> Xuất Excel (CSV)
                    </a>
                </div>
            </div>

            <!-- THANH TÌM KIẾM & LỌC LỚP HỌC PHẦN -->
            <form method="GET" action="" class="filter-bar">
                <input type="text" name="tu_khoa" class="input-control"
                       placeholder="Tìm mã lớp mở, mã HP, tên học phần..."
                       value="<?= htmlspecialchars($tu_khoa) ?>">

                <select name="hoc_ky_id" class="input-control">
                    <option value="">-- Tất cả học kỳ --</option>
                    <?php foreach ($ds_hoc_ky as $hk): ?>
                        <option value="<?= $hk['id'] ?>" <?= $hoc_ky_id == $hk['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($hk['ten_hoc_ky']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="giang_vien_id" class="input-control">
                    <option value="">-- Tất cả giảng viên --</option>
<?php foreach ($ds_giang_vien as $gv): ?>
                        <option value="<?= $gv['id'] ?>" <?= $giang_vien_id == $gv['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($gv['ho_ten']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="trang_thai" class="input-control">
                    <option value="">-- Mọi trạng thái --</option>
                    <option value="mo" <?= $trang_thai === 'mo' ? 'selected' : '' ?>>Đang mở</option>
                    <option value="con_cho" <?= $trang_thai === 'con_cho' ? 'selected' : '' ?>>Còn chỗ trống</option>
                    <option value="du_si_so" <?= $trang_thai === 'du_si_so' ? 'selected' : '' ?>>Đã đủ sĩ số</option>
                    <option value="khoa" <?= $trang_thai === 'khoa' ? 'selected' : '' ?>>Đã khóa</option>
                </select>

                <div style="display:flex; gap:6px;">
                    <button type="submit" class="btn-filter-custom">
                        <i class="fa-solid fa-magnifying-glass"></i> Lọc
                    </button>
                    <a href="danh_sach_lop_hoc_phan.php" class="btn-outline-custom" title="Đặt lại bộ lọc" style="padding: 0 12px;">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>

            <!-- BẢNG DỮ LIỆU -->
            <div class="table-wrap-custom">
                <table>
                    <thead>
                        <tr>
                            <th>MÃ LỚP MỞ</th>
                            <th>TÊN HỌC PHẦN</th>
                            <th>GIẢNG VIÊN</th>
                            <th>HỌC KỲ</th>
                            <th>LỊCH HỌC</th>
                            <th>SĨ SỐ</th>
                            <th>TRẠNG THÁI</th>
                            <th style="text-align:right;">THAO TÁC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lop_hien_thi)): ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:38px; color:#64748b;">
                                    Không tìm thấy lớp học phần nào phù hợp với điều kiện lọc.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lop_hien_thi as $row):
                                $con_lai = (int)$row['so_cho_con_lai'];
                                $bi_khoa = ($row['trang_thai'] === 'khoa' || (int)$row['trang_thai_dang_ky'] === 0);
                                $day_cho = ($con_lai <= 0);
                                $ty_le   = $row['si_so_toi_da'] > 0 ? min(100, round(($row['si_so_hien_tai'] / $row['si_so_toi_da']) * 100)) : 0;
$lich_hk = date('d/m/Y', strtotime($row['ngay_bat_dau'])) . ' - ' . date('d/m/Y', strtotime($row['ngay_ket_thuc']));

                               
                                $mo_dk   = $row['thoi_gian_mo_rieng'] ?? $row['thoi_gian_mo_dk'];
                                $dong_dk = $row['thoi_gian_dong_rieng'] ?? $row['thoi_gian_dong_dk'];
                            ?>
                                <tr>
                                    <td>
                                        <span class="code-pill"><?= htmlspecialchars($row['ma_lop']) ?></span>
                                    </td>
                                    <td>
                                        <div class="course-name"><?= htmlspecialchars($row['ten_hoc_phan']) ?></div>
                                        <div class="sub-text">Mã HP: <?= htmlspecialchars($row['ma_hoc_phan']) ?> • <?= (int)$row['so_tin_chi'] ?> tín chỉ</div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;"><?= htmlspecialchars($row['ho_ten_giang_vien']) ?></div>
                                        <div class="sub-text">Mã GV: <?= htmlspecialchars($row['ma_giang_vien']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($row['ten_hoc_ky']) ?></td>
                                    <td>
                                        <div style="font-weight:500;"><i class="fa-regular fa-calendar-days" style="color:#2563eb;"></i> <?= $lich_hk ?></div>
                                        <?php if ($mo_dk && $dong_dk): ?>
                                            <div class="sub-text">
                                                ĐK: <?= date('d/m/Y', strtotime($mo_dk)) ?> - <?= date('d/m/Y', strtotime($dong_dk)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="capacity-num">
                                            <?= (int)$row['si_so_hien_tai'] ?>/<?= (int)$row['si_so_toi_da'] ?>
                                            <span style="font-size:11.5px; font-weight:500; color:<?= $day_cho ? '#dc2626' : '#64748b' ?>;">
                                                (còn <?= $con_lai ?>)
                                            </span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar" style="width:<?= $ty_le ?>%; background:<?= $day_cho ? '#dc2626' : '#2563eb' ?>;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($bi_khoa): ?>
<span class="badge-khoa"><span style="width:6px;height:6px;border-radius:50%;background:#dc2626;display:inline-block;"></span> Đã khóa</span>
                                        <?php elseif ($day_cho): ?>
                                            <span class="badge-day"><span style="width:6px;height:6px;border-radius:50%;background:#d97706;display:inline-block;"></span> Đủ sĩ số</span>
                                        <?php else: ?>
                                            <span class="badge-mo"><span style="width:6px;height:6px;border-radius:50%;background:#059669;display:inline-block;"></span> Đang mở</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right; white-space:nowrap;">
                                        <a href="chi_tiet_lop_hoc_phan.php?id=<?= (int)$row['id'] ?>" class="btn-sm-action btn-outline-sm" title="Xem chi tiết lớp học phần">
                                            Chi tiết
                                        </a>

                                        <?php if ((int)$row['sv_da_dang_ky'] > 0): ?>
                                            <span class="btn-sm-action btn-registered-sm">
                                                <i class="fa-solid fa-check"></i> Đã ĐK
                                            </span>
                                        <?php elseif ($bi_khoa || $day_cho): ?>
                                            <span class="btn-sm-action btn-disabled-sm">Khóa ĐK</span>
                                        <?php else: ?>
                                            <a href="dang_ky_hoc.php?lop_hoc_phan_id=<?= (int)$row['id'] ?>" class="btn-sm-action btn-blue-sm">
                                                + Đăng ký
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PHÂN TRANG -->
            <div class="pagination-bar">
                <span style="font-size:12.5px; color:#64748b;">
                    Trang <?= $page ?> / <?= $total_pages ?> (Tổng cộng <?= $total_rows ?> lớp học phần)
                </span>
                <div class="pages">
                    <?php for ($i = 1; $i <= $total_pages; $i++):
                        $p_link = http_build_query(array_merge($_GET, ['page' => $i]));
                    ?>
                        <a href="?<?= htmlspecialchars($p_link) ?>" class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
</section>

    </main>
</div>

</body>
</html>
