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
    $_SESSION['ten_dang_nhap'] = 'SV001';
    $_SESSION['ho_ten']        = 'Nguyễn Thu Trang';
    $_SESSION['vai_tro']       = 'sinh_vien';
}
$sinh_vien_id_hien_tai = (int)$_SESSION['nguoi_dung_id'];

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
// 5. TRUY VẤN DỮ LIỆU TỪ CÁC BẢNG LIÊN KẾT (lop_hoc_phan, hoc_phan, hoc_ky, nguoi_dung, dang_ky_hoc)
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

// Tính toán chỗ trống và thống kê nhanh cho sinh viên
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
// 6. XUẤT FILE CSV VỚI TÊN CỘT TIẾNG VIỆT GỌN GÀNG, CHUẨN XÁC
// =========================================================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Danh_Sach_Lop_Hoc_Phan.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM cho Excel

    fputcsv($out, [
        'STT',
        'Mã lớp mở',
        'Mã học phần',
        'Tên học phần',
        'Số tín chỉ',
        'Giảng viên',
        'Học kỳ',
        'Lịch học',
        'Đã đăng ký',
        'Sĩ số tối đa',
        'Chỗ trống',
        'Trạng thái'
    ]);

    $stt = 1;
    foreach ($danh_sach_lop as $r) {
        $lich_hk = date('d/m/Y', strtotime($r['ngay_bat_dau'])) . ' - ' . date('d/m/Y', strtotime($r['ngay_ket_thuc']));
        $bi_khoa = ($r['trang_thai'] === 'khoa' || (int)$r['trang_thai_dang_ky'] === 0);
        $day_cho = ((int)$r['so_cho_con_lai'] <= 0);
        $ten_tt  = $bi_khoa ? 'Đã khóa' : ($day_cho ? 'Đủ sĩ số' : 'Đang mở');

        fputcsv($out, [
            $stt++,
            $r['ma_lop'],
            $r['ma_hoc_phan'],
            $r['ten_hoc_phan'],
            $r['so_tin_chi'],
            $r['ho_ten_giang_vien'],
            $r['ten_hoc_ky'],
            $lich_hk,
            $r['si_so_hien_tai'],
            $r['si_so_toi_da'],
            $r['so_cho_con_lai'],
            $ten_tt
        ]);
    }
    fclose($out);
    exit;
}

// =========================================================================
// 7. PHÂN TRANG & DỮ LIỆU DROPDOWN BỘ LỌC
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy: #0f2342;
            --navy-light: #163059;
            --blue: #2563eb;
            --blue-hover: #1d4ed8;
            --blue-soft: #eff6ff;
            --bg: #f4f7fb;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --success: #059669;
            --success-bg: #ecfdf5;
            --warning: #d97706;
            --warning-bg: #fffbeb;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ==================== TOP HEADER ==================== */
        .topbar {
            height: 68px;
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .brand-logo {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--blue);
            color: #fff;
            font-weight: 800;
            font-size: 18px;
            display: grid;
            place-items: center;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .brand-text h1 {
            font-size: 19px;
            font-weight: 800;
            color: var(--text);
            line-height: 1.1;
        }
        .brand-text span {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: 0.6px;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #dbeafe;
            color: var(--blue);
            font-weight: 700;
            font-size: 13px;
            display: grid;
            place-items: center;
        }
        .user-meta { line-height: 1.25; }
        .user-meta strong { display: block; font-size: 13.5px; color: var(--text); }
        .user-meta small { font-size: 12px; color: var(--muted); }

        /* ==================== BODY LAYOUT (SIDEBAR + CONTENT) ==================== */
        .app-body {
            display: flex;
            flex: 1;
            min-height: calc(100vh - 68px);
        }

        /* SIDEBAR ĐỒNG BỘ MẪU STUDENTHUB */
        .sidebar {
            width: 245px;
            background: var(--navy);
            color: #cbd5e1;
            padding: 22px 14px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .sidebar-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            padding: 0 12px;
            margin-bottom: 10px;
        }
        .nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 10px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.15s;
        }
        .nav-link i { width: 18px; text-align: center; font-size: 15px; }
        .nav-link:hover {
            background: var(--navy-light);
            color: #fff;
        }
        .nav-link.active {
            background: var(--blue);
            color: #fff;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }
        .sidebar-footer {
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.4;
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 26px 32px;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 22px;
            gap: 16px;
        }
        .page-header h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text);
        }
        .page-header p {
            font-size: 13.5px;
            color: var(--muted);
            margin-top: 4px;
        }
        .date-pill {
            background: var(--card);
            border: 1px solid var(--border);
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--muted);
        }

        /* 3 THẺ TỔNG QUAN CHO SINH VIÊN */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 22px;
        }
        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        }
        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 18px;
        }
        .stat-icon.blue { background: #eff6ff; color: var(--blue); }
        .stat-icon.green { background: #ecfdf5; color: var(--success); }
        .stat-icon.amber { background: #fffbeb; color: var(--warning); }
        .stat-info span {
            display: block;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .stat-info strong {
            font-size: 26px;
            font-weight: 800;
            color: var(--text);
        }

        /* KHUNG BẢNG & BỘ LỌC */
        .panel-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }
        .panel-top {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }
        .panel-title h3 {
            font-size: 17px;
            font-weight: 700;
            color: var(--text);
        }
        .panel-title p {
            font-size: 13px;
            color: var(--muted);
            margin-top: 2px;
        }

        /* BỘ LỌC TÌM KIẾM */
        .filter-bar {
            padding: 16px 24px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            display: grid;
            grid-template-columns: 2fr 1.2fr 1.3fr 1.1fr auto;
            gap: 10px;
            align-items: center;
        }
        .input-control {
            width: 100%;
            height: 38px;
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 0 12px;
            font-size: 13px;
            color: var(--text);
            background: #fff;
            outline: none;
        }
        .input-control:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .btn {
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
            border: 1px solid transparent;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .btn-blue { background: var(--blue); color: #fff; }
        .btn-blue:hover { background: var(--blue-hover); }
        .btn-outline { background: #fff; color: var(--text); border-color: var(--border); }
        .btn-outline:hover { border-color: var(--blue); color: var(--blue); }
        .btn-sm { height: 32px; padding: 0 11px; font-size: 12px; border-radius: 8px; }

        /* BẢNG HIỂN THỊ ĐỦ 6 TRƯỜNG THEO ĐỀ BÀI */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        thead { background: #f8fafc; border-bottom: 1px solid var(--border); }
        th {
            padding: 13px 16px;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            white-space: nowrap;
        }
        td {
            padding: 14px 16px;
            font-size: 13.5px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        tbody tr:hover { background: #f8fbff; }

        .code-pill {
            font-weight: 700;
            color: var(--blue);
            background: var(--blue-soft);
            padding: 4px 10px;
            border-radius: 7px;
            font-size: 12.5px;
            display: inline-block;
        }
        .course-name { font-weight: 600; color: var(--text); }
        .sub-text { font-size: 12px; color: var(--muted); margin-top: 2px; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-mo { background: var(--success-bg); color: var(--success); }
        .badge-day { background: var(--warning-bg); color: var(--warning); }
        .badge-khoa { background: var(--danger-bg); color: var(--danger); }

        .capacity-num { font-weight: 600; color: var(--text); font-size: 13px; }
        .progress {
            width: 85px;
            height: 5px;
            background: #e2e8f0;
            border-radius: 99px;
            overflow: hidden;
            margin-top: 5px;
        }
        .progress-bar { height: 100%; background: var(--blue); }

        .btn-registered { background: var(--success-bg); color: var(--success); border-color: #a7f3d0; }
        .btn-disabled { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; }

        /* PHÂN TRANG */
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
            border: 1px solid var(--border);
            border-radius: 7px;
            text-decoration: none;
            color: var(--text);
            font-size: 12.5px;
            font-weight: 600;
            background: #fff;
        }
        .page-item.active { background: var(--blue); color: #fff; border-color: var(--blue); }

        @media (max-width: 1024px) {
            .filter-bar { grid-template-columns: 1fr 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .sidebar { width: 200px; }
        }
        @media (max-width: 768px) {
            .app-body { flex-direction: column; }
            .sidebar { width: 100%; }
        }
    </style>
</head>
<body>

    <!-- ==================== HEADER TRÊN CÙNG ==================== -->
    <header class="topbar">
        <a href="danh_sach_lop_hoc_phan.php" class="brand">
            <div class="brand-logo">S</div>
            <div class="brand-text">
                <h1>StudentHub</h1>
                <span>CỔNG THÔNG TIN SINH VIÊN</span>
            </div>
        </a>

        <div class="user-profile">
            <div class="user-avatar">SV</div>
            <div class="user-meta">
                <strong>Sinh viên: <?= htmlspecialchars($_SESSION['ho_ten']) ?></strong>
                <small>Mã SV: <?= htmlspecialchars($_SESSION['ten_dang_nhap']) ?></small>
            </div>
        </div>
    </header>

    <!-- ==================== BODY: SIDEBAR + MAIN ==================== -->
    <div class="app-body">

        <!-- SIDEBAR LIÊN KẾT CHẶT CHẼ VỚI CÁC THÀNH VIÊN TRONG NHÓM -->
        <aside class="sidebar">
            <div>
                <div class="sidebar-label">MENU SINH VIÊN</div>
                <ul class="nav-list">
                    <!-- Thành viên 1 (Huyền): Danh sách lớp học phần -->
                    <li>
                        <a href="danh_sach_lop_hoc_phan.php" class="nav-link active">
                            <i class="fa-solid fa-table-list"></i> Danh sách lớp học phần
                        </a>
                    </li>
                    <!-- Thành viên 4 (KimAnh): Lịch sử đăng ký -->
                    <li>
                        <a href="lich_su_dang_ky.php" class="nav-link">
                            <i class="fa-solid fa-clock-rotate-left"></i> Lịch sử đăng ký
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-footer">
                <strong>StudentHub</strong><br>
                Hệ thống đăng ký học phần trực tuyến.
            </div>
        </aside>

        <!-- NỘI DUNG CHÍNH -->
        <main class="main-content">

            <div class="page-header">
                <div>
                    <h2>Danh sách lớp học phần</h2>
                    <p>Tra cứu các lớp học phần mở trong học kỳ, kiểm tra lịch học, sĩ số và chọn lớp đăng ký.</p>
                </div>
                <div class="date-pill">
                    <i class="fa-regular fa-calendar"></i> <?= date('d/m/Y') ?>
                </div>
            </div>

            <!-- 3 THẺ THỐNG KÊ NHANH CHO SINH VIÊN -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-book-open"></i></div>
                    <div class="stat-info">
                        <span>Tổng số lớp học phần</span>
                        <strong><?= $total_rows ?></strong>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="fa-regular fa-clock"></i></div>
                    <div class="stat-info">
                        <span>Lớp đang mở còn chỗ</span>
                        <strong><?= $so_lop_dang_mo ?></strong>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="stat-info">
                        <span>Lớp bạn đã đăng ký</span>
                        <strong><?= $so_lop_da_dk ?></strong>
                    </div>
                </div>
            </div>

            <!-- BẢNG DANH SÁCH & BỘ LỌC -->
            <section class="panel-card">
                <div class="panel-top">
                    <div class="panel-title">
                        <h3>Tra cứu lớp học phần</h3>
                        <p>Hiển thị mã lớp mở, tên học phần, giảng viên, học kỳ, lịch học và sĩ số</p>
                    </div>

                    <div>
                        <?php $csv_link = http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>
                        <a href="?<?= htmlspecialchars($csv_link) ?>" class="btn btn-outline">
                            <i class="fa-solid fa-file-excel" style="color:var(--success);"></i> Xuất Excel (CSV)
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
                        <button type="submit" class="btn btn-blue">
                            <i class="fa-solid fa-magnifying-glass"></i> Lọc
                        </button>
                        <a href="danh_sach_lop_hoc_phan.php" class="btn btn-outline" title="Đặt lại bộ lọc">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    </div>
                </form>

                <!-- BẢNG HIỂN THỊ ĐÚNG 6 CỘT NỘI DUNG + TRẠNG THÁI & THAO TÁC -->
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã lớp mở</th>
                                <th>Tên học phần</th>
                                <th>Giảng viên</th>
                                <th>Học kỳ</th>
                                <th>Lịch học</th>
                                <th>Sĩ số</th>
                                <th>Trạng thái</th>
                                <th style="text-align:right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lop_hien_thi)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:38px; color:var(--muted);">
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
                                        <!-- 1. Mã lớp mở -->
                                        <td>
                                            <span class="code-pill"><?= htmlspecialchars($row['ma_lop']) ?></span>
                                        </td>

                                        <!-- 2. Tên học phần -->
                                        <td>
                                            <div class="course-name"><?= htmlspecialchars($row['ten_hoc_phan']) ?></div>
                                            <div class="sub-text">Mã HP: <?= htmlspecialchars($row['ma_hoc_phan']) ?> • <?= (int)$row['so_tin_chi'] ?> tín chỉ</div>
                                        </td>

                                        <!-- 3. Giảng viên -->
                                        <td>
                                            <div style="font-weight:600;"><?= htmlspecialchars($row['ho_ten_giang_vien']) ?></div>
                                            <div class="sub-text">Mã GV: <?= htmlspecialchars($row['ma_giang_vien']) ?></div>
                                        </td>

                                        <!-- 4. Học kỳ -->
                                        <td><?= htmlspecialchars($row['ten_hoc_ky']) ?></td>

                                        <!-- 5. Lịch học -->
                                        <td>
                                            <div style="font-weight:500;"><i class="fa-regular fa-calendar-days" style="color:var(--blue);"></i> <?= $lich_hk ?></div>
                                            <?php if ($mo_dk && $dong_dk): ?>
                                                <div class="sub-text">
                                                    ĐK: <?= date('d/m/Y', strtotime($mo_dk)) ?> - <?= date('d/m/Y', strtotime($dong_dk)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- 6. Sĩ số -->
                                        <td>
                                            <div class="capacity-num">
                                                <?= (int)$row['si_so_hien_tai'] ?>/<?= (int)$row['si_so_toi_da'] ?>
                                                <span style="font-size:11.5px; font-weight:500; color:<?= $day_cho ? 'var(--danger)' : 'var(--muted)' ?>;">
                                                    (còn <?= $con_lai ?>)
                                                </span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar" style="width:<?= $ty_le ?>%; background:<?= $day_cho ? 'var(--danger)' : 'var(--blue)' ?>;"></div>
                                            </div>
                                        </td>

                                        <!-- 7. Trạng thái -->
                                        <td>
                                            <?php if ($bi_khoa): ?>
                                                <span class="badge badge-khoa">Đã khóa</span>
                                            <?php elseif ($day_cho): ?>
                                                <span class="badge badge-day">Đủ sĩ số</span>
                                            <?php else: ?>
                                                <span class="badge badge-mo">Đang mở</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- 8. Thao tác: Nối sang bài của Quỳnh (Chi tiết lớp) & Ly (Đăng ký học) -->
                                        <td style="text-align:right; white-space:nowrap;">
                                            <!-- Nút chuyển sang trang Chi tiết lớp của Thành viên 2 (Quỳnh) -->
                                            <a href="chi_tiet_lop_hoc_phan.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline" title="Xem chi tiết lớp học phần">
                                                Chi tiết
                                            </a>

                                            <!-- Nút chuyển sang trang Xử lý đăng ký của Thành viên 3 (Ly) -->
                                            <?php if ((int)$row['sv_da_dang_ky'] > 0): ?>
                                                <span class="btn btn-sm btn-registered">
                                                    <i class="fa-solid fa-check"></i> Đã ĐK
                                                </span>
                                            <?php elseif ($bi_khoa || $day_cho): ?>
                                                <span class="btn btn-sm btn-disabled">Khóa ĐK</span>
                                            <?php else: ?>
                                                <a href="dang_ky_hoc.php?lop_hoc_phan_id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-blue">
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
                    <span style="font-size:12.5px; color:var(--muted);">
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