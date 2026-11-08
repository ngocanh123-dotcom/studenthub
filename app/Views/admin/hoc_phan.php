
<?php
session_start();

// KIỂM TRA ĐĂNG NHẬP ADMIN
if (empty($_SESSION['username'])) {
    header('Location: ../../../public/index.php');
    exit;
}

$vaiTroSession = strtolower(trim((string) ($_SESSION['role'] ?? '')));

if (!in_array($vaiTroSession, ['admin', 'quantri', 'quan_tri', 'quan_tri_vien'], true)) {
    header('Location: ../../../public/index.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

// Hàm hiển thị dữ liệu an toàn
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// TẠO CSRF TOKEN
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$thongBao = '';
$loaiThongBao = 'success';
$loi = '';

$tuKhoa = trim($_GET['q'] ?? '');

// CẤU HÌNH PHÂN TRANG
$soDongMoiTrang = 8;
$trangHienTai = max(1, (int) ($_GET['page'] ?? 1));

$duLieuSua = [
    'id' => '',
    'ma_hoc_phan' => '',
    'ten_hoc_phan' => '',
    'so_tin_chi' => '',
    'loai_hoc_phan' => 'dai_cuong',
    'mo_ta' => ''
];

// =====================================================
// XỬ LÝ THÊM, SỬA, XÓA
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfNhan = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfNhan) ||
        !hash_equals($_SESSION['csrf_token'], $csrfNhan)
    ) {
        $thongBao = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.';
        $loaiThongBao = 'error';
    } else {
        $hanhDong = $_POST['hanh_dong'] ?? '';

        try {
            // THÊM HOẶC SỬA HỌC PHẦN
            if ($hanhDong === 'them' || $hanhDong === 'sua') {
                $id = (int) ($_POST['id'] ?? 0);
                $maHocPhan = strtoupper(trim($_POST['ma_hoc_phan'] ?? ''));
                $tenHocPhan = trim($_POST['ten_hoc_phan'] ?? '');
                $soTinChiNhap = trim((string) ($_POST['so_tin_chi'] ?? ''));
                $loaiHocPhanNhap = trim((string)($_POST['loai_hoc_phan'] ?? 'dai_cuong'));
                $loaiHocPhanMap = [
                    'Đại cương' => 'dai_cuong',
                    'Cơ sở ngành' => 'co_so_nganh',
                    'Chuyên ngành' => 'chuyen_nganh',
                    'dai_cuong' => 'dai_cuong',
                    'co_so_nganh' => 'co_so_nganh',
                    'chuyen_nganh' => 'chuyen_nganh'
                ];
                if (!isset($loaiHocPhanMap[$loaiHocPhanNhap])) {
                    throw new RuntimeException('Loại học phần không hợp lệ.');
                }
                $loaiHocPhan = $loaiHocPhanMap[$loaiHocPhanNhap];

                $moTa = trim($_POST['mo_ta'] ?? '');

                $duLieuSua = [
                    'id' => $id,
                    'ma_hoc_phan' => $maHocPhan,
                    'ten_hoc_phan' => $tenHocPhan,
                    'so_tin_chi' => $soTinChiNhap,
                    'loai_hoc_phan' => $loaiHocPhan,
                    'mo_ta' => $moTa
                ];

                // Kiểm tra mã học phần
                if (!preg_match('/^[A-Z0-9_-]{2,30}$/', $maHocPhan)) {
                    throw new RuntimeException(
                        'Mã học phần phải có 2–30 ký tự chữ, số, dấu gạch ngang hoặc gạch dưới.'
                    );
                }

                // Kiểm tra tên học phần
                if ($tenHocPhan === '') {
                    throw new RuntimeException('Vui lòng nhập tên học phần.');
                }

                if (mb_strlen($tenHocPhan, 'UTF-8') > 150) {
                    throw new RuntimeException('Tên học phần không được vượt quá 150 ký tự.');
                }

                if (
                    $soTinChiNhap === '' ||
                    !ctype_digit($soTinChiNhap) ||
                    (int) $soTinChiNhap < 1 ||
                    (int) $soTinChiNhap > 10
                ) {
                    throw new RuntimeException('Số tín chỉ phải là số nguyên từ 1 đến 10.');
                }

                $soTinChi = (int) $soTinChiNhap;

                if ($hanhDong === 'sua' && $id <= 0) {
                    throw new RuntimeException('Học phần cần sửa không hợp lệ.');
                }

                // KIỂM TRA MÃ HỌC PHẦN TRÙNG
                $sqlTrung = "SELECT COUNT(*) FROM hoc_phan
                             WHERE ma_hoc_phan = :ma";

                $thamSoTrung = [':ma' => $maHocPhan];

                if ($hanhDong === 'sua') {
                    $sqlTrung .= " AND id <> :id";
                    $thamSoTrung[':id'] = $id;
                }

                $stmt = $pdo->prepare($sqlTrung);
                $stmt->execute($thamSoTrung);

                if ((int) $stmt->fetchColumn() > 0) {
                    throw new RuntimeException('Mã học phần đã tồn tại.');
                }

                // THÊM HỌC PHẦN
                if ($hanhDong === 'them') {
                    $stmt = $pdo->prepare(
                        "INSERT INTO hoc_phan
                            (
                                ma_hoc_phan,
                                ten_hoc_phan,
                                so_tin_chi,
                                loai_hoc_phan,
                                mo_ta
                            )
                         VALUES
                            (
                                :ma,
                                :ten,
                                :so_tin_chi,
                                :loai_hoc_phan,
                                :mo_ta
                            )"
                    );

                    $stmt->execute([
                        ':ma' => $maHocPhan,
                        ':ten' => $tenHocPhan,
                        ':so_tin_chi' => $soTinChi,
                        ':loai_hoc_phan' => $loaiHocPhan,
                        ':mo_ta' => $moTa !== ''
                            ? $moTa : null
                    ]);

                    $thongBao = 'Thêm học phần thành công.';
                } else {
                    // CẬP NHẬT HỌC PHẦN
                    $stmt = $pdo->prepare(
                        "UPDATE hoc_phan
                         SET ma_hoc_phan = :ma,
                             ten_hoc_phan = :ten,
                             so_tin_chi = :so_tin_chi,
                             loai_hoc_phan = :loai_hoc_phan,
                             mo_ta = :mo_ta
                         WHERE id = :id"
                    );

                    $stmt->execute([
                        ':ma' => $maHocPhan,
                        ':ten' => $tenHocPhan,
                        ':so_tin_chi' => $soTinChi,
                        ':loai_hoc_phan' => $loaiHocPhan,
                        ':mo_ta' => $moTa !== ''
                            ? $moTa : null,
                        ':id' => $id
                    ]);

                    $thongBao = 'Cập nhật học phần thành công.';
                }

                $_SESSION['course_notice'] = $thongBao;
                $_SESSION['course_notice_type'] = 'success';

                header('Location: hoc_phan.php');
                exit;
            }

            // XÓA HỌC PHẦN
            if ($hanhDong === 'xoa') {
                $idXoa = (int) ($_POST['id'] ?? 0);

                if ($idXoa <= 0) {
                    throw new RuntimeException('Mã học phần không hợp lệ.');
                }

                $stmt = $pdo->prepare(
                    "SELECT id FROM hoc_phan
                     WHERE id = :id
                     LIMIT 1"
                );

                $stmt->execute([':id' => $idXoa]);

                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                    throw new RuntimeException('Không tìm thấy học phần cần xóa.');
                }

                // Không xóa nếu lớp học phần đang sử dụng học phần này
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM lop_hoc_phan
                     WHERE hoc_phan_id = :id"
                );

                $stmt->execute([':id' => $idXoa]);

                if ((int) $stmt->fetchColumn() > 0) {
                    throw new RuntimeException(
                        'Không thể xóa học phần này vì đã có lớp học phần sử dụng. Hãy xử lý các lớp liên quan trước.'
                    );
                }

                $stmt = $pdo->prepare(
                    "DELETE FROM hoc_phan WHERE id = :id"
                );

                $stmt->execute([':id' => $idXoa]);

                $_SESSION['course_notice'] = 'Xóa học phần thành công.';
                $_SESSION['course_notice_type'] = 'success';

                header('Location: hoc_phan.php');
                exit;
            }
        } catch (RuntimeException $e) {
            $thongBao = $e->getMessage();
            $loaiThongBao = 'error';
        } catch (PDOException $e) {
            error_log('StudentHub Course: ' . $e->getMessage());

            $thongBao = 'Không thể xử lý học phần. Hãy kiểm tra cấu trúc CSDL và các lớp học phần liên quan.';
            $loaiThongBao = 'error';
        }
    }
}

// =====================================================
// LẤY THÔNG BÁO SAU CHUYỂN HƯỚNG
// =====================================================

if (!empty($_SESSION['course_notice'])) {
    $thongBao = $_SESSION['course_notice'];
    $loaiThongBao = $_SESSION['course_notice_type'] ?? 'success';

    unset($_SESSION['course_notice'], $_SESSION['course_notice_type']);
}

// =====================================================
// LẤY DỮ LIỆU HỌC PHẦN CẦN SỬA
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['sua'])) {
    $idSua = (int) $_GET['sua'];

    if ($idSua > 0) {
        try {
            $stmt = $pdo->prepare(
                "SELECT
                    id,
                    ma_hoc_phan,
                    ten_hoc_phan,
                    so_tin_chi,
                    loai_hoc_phan,
                    mo_ta
                 FROM hoc_phan
                 WHERE id = :id
                 LIMIT 1"
            );

            $stmt->execute([':id' => $idSua]);
            $hocPhanSua = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($hocPhanSua) {
                $duLieuSua = $hocPhanSua;
            } else {
                $thongBao = 'Không tìm thấy học phần cần sửa.';
                $loaiThongBao = 'error';
            }
        } catch (PDOException $e) {
            error_log('StudentHub Course Edit: ' . $e->getMessage());

            $thongBao = 'Không thể tải thông tin học phần.';
            $loaiThongBao = 'error';
        }
    }
}

// =====================================================
// THỐNG KÊ VÀ LẤY DANH SÁCH HỌC PHẦN
// =====================================================

$danhSach = [];
$tongHocPhan = 0;

try {
    $sql = "SELECT id, ma_hoc_phan, ten_hoc_phan, so_tin_chi
            FROM hoc_phan
            WHERE 1 = 1";

    $thamSo = [];
    $where = '';

    if ($tuKhoa !== '') {
        $where = " WHERE (
            ma_hoc_phan LIKE :q1
            OR ten_hoc_phan LIKE :q2
        )";

        $tim = '%' . $tuKhoa . '%';

        $thamSo[':q1'] = $tim;
        $thamSo[':q2'] = $tim;
    }

    // ĐẾM KẾT QUẢ TÌM KIẾM
    $sqlDem = "SELECT COUNT(*) FROM hoc_phan" . $where;

    $stmtDem = $pdo->prepare($sqlDem);
    $stmtDem->execute($thamSo);

    $tongKetQua = (int) $stmtDem->fetchColumn();

    // TÍNH SỐ TRANG
    $tongTrang = max(1, (int) ceil($tongKetQua / $soDongMoiTrang));
    $trangHienTai = min($trangHienTai, $tongTrang);

    $viTriBatDau = ($trangHienTai - 1) * $soDongMoiTrang;

    // LẤY DANH SÁCH
    $sql = "SELECT
                id,
                ma_hoc_phan,
                ten_hoc_phan,
                so_tin_chi,
                mo_ta
            FROM hoc_phan
            $where
            ORDER BY id ASC
            LIMIT :gioi_han OFFSET :vi_tri";

    $stmt = $pdo->prepare($sql);

    foreach ($thamSo as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }

    $stmt->bindValue(':gioi_han', $soDongMoiTrang, PDO::PARAM_INT);
    $stmt->bindValue(':vi_tri', $viTriBatDau, PDO::PARAM_INT);

    $stmt->execute();

    $danhSach = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('StudentHub Course List: ' . $e->getMessage());

    $loi = 'Không thể tải danh sách học phần. Hãy kiểm tra bảng hoc_phan trong CSDL.';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý học phần | StudentHub</title>

    <link rel="stylesheet" href="../../../assets/css/quantrivien.css">

    <style>
        .course-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            width: 100%;
            box-sizing: border-box;
            padding: 22px 26px;
            margin-bottom: 22px;
            background: #fff;
            border: 1px solid #e5edf6;
            border-radius: 14px;
            box-shadow: 0 3px 12px rgba(30, 60, 90, 0.05);
        }

        .course-summary-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            gap: 7px;
        }

        .course-summary-label {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.4;
        }

        .course-summary-number {
            display: block;
            color: #1769d2;
            font-size: 32px;
            font-weight: 700;
            line-height: 1.2;
        }

        .course-summary-description {
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.5;
        }

        .course-summary-icon {
            width: 58px;
            height: 58px;
            min-width: 58px;
            border-radius: 14px;
            background: #eaf3ff;
            color: #1769d2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 27px;
        }

        /* THANH TÌM KIẾM THEO GIAO DIỆN ACCOUNT-FILTERS */
        .account-filters {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            width: 100%;
            margin-bottom: 20px;
        }

        .account-filters input[type="search"] {
            flex: 1;
            min-width: 220px;
            max-width: 420px;
            height: 44px;
            box-sizing: border-box;
            padding: 10px 14px;
            border: 1px solid #dbe4ef;
            border-radius: 8px;
            background: #fff;
            color: #26374a;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .account-filters input[type="search"]:focus {
            border-color: #1769d2;
            box-shadow: 0 0 0 3px rgba(23, 105, 210, 0.10);
        }

        .account-filters input[type="search"]::placeholder {
            color: #94a3b8;
        }

        .account-filters .btn-primary,
        .account-filters .btn-secondary {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            white-space: nowrap;
        }

        /* CÁC NÚT THAO TÁC */
        .account-table {
            overflow-x: auto;
        }

        .account-table table {
            min-width: 760px;
        }

        .account-table th:last-child,
        .account-table td:last-child {
            min-width: 170px;
            width: 170px;
            white-space: nowrap;
        }

        .action-buttons {
            display: inline-flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-start;
            flex-wrap: nowrap !important;
            gap: 7px;
            white-space: nowrap;
        }

        .action-buttons form {
            display: inline-flex !important;
            align-items: center;
            flex: 0 0 auto;
            margin: 0 !important;
            padding: 0;
        }

        .action-buttons .btn-view,
        .action-buttons .btn-edit,
        .action-buttons .btn-delete {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            height: 34px;
            min-width: 36px;
            box-sizing: border-box;
            margin: 0;
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .btn-view {
            border: none;
            border-radius: 6px;
            padding: 0 10px;
            background: #eaf3ff;
            color: #1769d2;
            cursor: pointer;
            font-size: 16px;
            line-height: 1.2;
        }

        .btn-view:hover {
            background: #d6e8ff;
        }

        .action-buttons .btn-edit,
        .action-buttons .btn-delete {
            padding: 0 11px;
        }

        /* PHÂN TRANG */
        .course-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .course-pagination-info {
            color: #64748b;
            font-size: 13px;
        }

        .course-pagination-list {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .course-pagination a {
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dbe4ef;
            border-radius: 7px;
            background: #fff;
            color: #334155;
            text-decoration: none;
            font-size: 13px;
            box-sizing: border-box;
        }

        .course-pagination a:hover {
            border-color: #1769d2;
            color: #1769d2;
        }

        .course-pagination a.active {
            background: #1769d2;
            border-color: #1769d2;
            color: #fff;
        }

        .course-pagination a.disabled {
            pointer-events: none;
            opacity: 0.45;
        }

        /* MODAL CHI TIẾT */
        .course-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(15, 30, 50, 0.5);
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .course-modal.show {
            display: flex;
        }

        .course-modal-content {
            width: 100%;
            max-width: 520px;
            max-height: 85vh;
            overflow-y: auto;
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.18);
        }

        .course-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .course-modal-header h2 {
            margin: 0;
            color: #1769d2;
            font-size: 20px;
        }

        .course-modal-close {
            border: none;
            background: #f1f5f9;
            color: #334155;
            border-radius: 8px;
            width: 34px;
            height: 34px;
            font-size: 22px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .course-modal-close:hover {
            background: #e2e8f0;
        }

        .course-detail-row {
            padding: 13px 0;
            border-bottom: 1px solid #edf1f5;
        }

        .course-detail-row:last-child {
            border-bottom: none;
        }

        .course-detail-label {
            display: block;
            font-size: 13px;
            color: #718096;
            margin-bottom: 6px;
        }

        .course-detail-value {
            color: #26374a;
            font-size: 15px;
            font-weight: 500;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        @media (max-width: 600px) {
            .course-summary {
                padding: 18px;
            }

            .course-summary-number {
                font-size: 28px;
            }

            .account-filters {
                flex-direction: column;
                align-items: stretch;
            }

            .account-filters input[type="search"] {
                width: 100%;
                max-width: none;
            }

            .account-filters .btn-primary,
            .account-filters .btn-secondary {
                width: 100%;
            }

            .course-pagination {
                align-items: flex-start;
                flex-direction: column;
            }

            .course-modal-content {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <?php
    $activeMenu = 'hoc_phan';
    require __DIR__ . '/../layouts/admin_sidebar.php';
    ?>

    <main class="main">

        <header class="topbar">
            <div>
                <div class="breadcrumb">Quản trị hệ thống / Học phần</div>
                <h1>Quản lý học phần</h1>
                <p>Thêm, cập nhật và tra cứu danh sách học phần.</p>
            </div>

            <div class="admin-profile">
                <div class="avatar">AD</div>

                <div class="profile-info">
                    <strong>
                        <?= e($_SESSION['ho_ten'] ?? $_SESSION['username'] ?? 'Quản trị viên') ?>
                    </strong>
                    <span>Quản trị viên</span>
                </div>
            </div>
        </header>

        <div class="content">

            <?php if ($thongBao !== ''): ?>
                <div class="<?= $loaiThongBao === 'error' ? 'error-message' : 'account-notice' ?>">
                    <?= e($thongBao) ?>
                </div>
            <?php endif; ?>

            <?php if ($loi !== ''): ?>
                <div class="error-message">
                    <?= e($loi) ?>
                </div>
            <?php endif; ?>

            <!-- THỐNG KÊ -->
            <section class="course-summary">
                <div class="course-summary-info">
                    <span class="course-summary-label">
                        Tổng số học phần
                    </span>

                    <strong class="course-summary-number">
                        <?= number_format($tongHocPhan) ?>
                    </strong>

                    <span class="course-summary-description">
                        Học phần hiện có trong hệ thống StudentHub
                    </span>
                </div>

                <div class="course-summary-icon">
                    ▤
                </div>
            </section>

            <!-- FORM THÊM / SỬA -->
            <section class="section">

                <div class="section-heading">
                    <div>
                        <h2>
                            <?= !empty($duLieuSua['id']) ? 'Cập nhật học phần' : 'Thêm học phần mới' ?>
                        </h2>

                        <p>Nhập mã học phần, tên học phần và số tín chỉ.</p>
                    </div>
                </div>

                <form method="POST" action="hoc_phan.php" class="account-form">

                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                    <input
                        type="hidden"
                        name="hanh_dong"
                        value="<?= !empty($duLieuSua['id']) ? 'sua' : 'them' ?>"
                    >

                    <input type="hidden" name="id" value="<?= e($duLieuSua['id']) ?>">

                    <div class="form-grid">

                        <div class="form-field">
                            <label for="ma_hoc_phan">
                                Mã học phần <span>*</span>
                            </label>

                            <input
                                id="ma_hoc_phan"
                                name="ma_hoc_phan"
                                required
                                maxlength="30"
                                pattern="[A-Za-z0-9_-]{2,30}"
                                value="<?= e($duLieuSua['ma_hoc_phan']) ?>"
                                placeholder="Ví dụ: CT101"
                            >
                        </div>

                        <div class="form-field">
                            <label for="ten_hoc_phan">
                                Tên học phần <span>*</span>
                            </label>

                            <input
                                id="ten_hoc_phan"
                                name="ten_hoc_phan"
                                required
                                maxlength="150"
                                value="<?= e($duLieuSua['ten_hoc_phan']) ?>"
                                placeholder="Ví dụ: Lập trình Web"
                            >
                        </div>

                        <div class="form-field">
                            <label for="so_tin_chi">
                                Số tín chỉ <span>*</span>
                            </label>

                            <input
                                type="number"
                                id="so_tin_chi"
                                name="so_tin_chi"
                                required
                                min="1"
                                max="10"
                                step="1"
                                value="<?= e($duLieuSua['so_tin_chi']) ?>"
                                placeholder="Ví dụ: 3"
                            >
                        </div>

                        <div class="form-field">
                            <label for="loai_hoc_phan">Khối kiến thức (Loại học phần) <span>*</span></label>
                            <select id="loai_hoc_phan" name="loai_hoc_phan" required>
                                <?php
                                $valLoai = (string)($duLieuSua['loai_hoc_phan'] ?? 'dai_cuong');
                                ?>
                                <option value="dai_cuong" <?= ($valLoai === 'Đại cương' || $valLoai === 'dai_cuong' || empty($valLoai)) ? 'selected' : '' ?>>Đại cương</option>
                                <option value="co_so_nganh" <?= ($valLoai === 'Cơ sở ngành' || $valLoai === 'co_so_nganh') ? 'selected' : '' ?>>Cơ sở ngành</option>
                                <option value="chuyen_nganh" <?= ($valLoai === 'Chuyên ngành' || $valLoai === 'chuyen_nganh') ? 'selected' : '' ?>>Chuyên ngành</option>
                            </select>
                        </div>

                        <!-- BỔ SUNG: MÔ TẢ HỌC PHẦN -->
                        <div class="form-field" style="grid-column: 1 / -1;">
                            <label for="mo_ta">Mô tả và mục tiêu học phần</label>
                            <textarea
                                id="mo_ta"
                                name="mo_ta"
                                rows="4"
                                placeholder="Nhập mô tả, nội dung và mục tiêu của học phần..."
                                style="
                                    width: 100%;
                                    box-sizing: border-box;
                                    padding: 10px 12px;
                                    border: 1px solid #d1d5db;
                                    border-radius: 8px;
                                    background: #fff;
                                    font-family: inherit;
                                    font-size: 14px;
                                    line-height: 1.5;
                                    resize: vertical;
                                    outline: none;
                                "
                            ><?= e($duLieuSua['mo_ta'] ?? '') ?></textarea>
                        </div>

                    </div>

                    <div class="form-actions">

                        <button type="submit" class="btn-primary">
                            <?= !empty($duLieuSua['id']) ? 'Lưu thay đổi' : 'Thêm học phần' ?>
                        </button>

                        <?php if (!empty($duLieuSua['id'])): ?>
                            <a href="hoc_phan.php" class="btn-secondary">
                                Hủy sửa
                            </a>
                        <?php else: ?>
                            <button type="reset" class="btn-secondary">
                                Nhập lại
                            </button>
                        <?php endif; ?>

                    </div>

                </form>
            </section>

            <!-- DANH SÁCH HỌC PHẦN -->
            <section class="section">

                <div class="section-heading">
                    <div>
                        <h2>Danh sách học phần</h2>
                        <p>Tra cứu và quản lý các học phần hiện có.</p>
                    </div>
                </div>

                <!-- TÌM KIẾM GIỐNG CODE CŨ -->
                <form method="GET" action="hoc_phan.php" class="account-filters">

                    <input
                        type="search"
                        name="q"
                        value="<?= e($tuKhoa) ?>"
                        placeholder="Tìm theo mã hoặc tên học phần..."
                        autocomplete="off"
                    >

                    <button type="submit" class="btn-primary">
                        Tìm kiếm
                    </button>

                    <a href="hoc_phan.php" class="btn-secondary">
                        Làm mới
                    </a>

                </form>

                <!-- BẢNG DANH SÁCH -->
                <div class="account-table">

                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mã học phần</th>
                                <th>Tên học phần</th>
                                <th>Khối kiến thức</th>
                                <th>Số tín chỉ</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (empty($danhSach)): ?>

                            <tr>
                                <td colspan="6" class="empty">
                                    Chưa có học phần hoặc không tìm thấy kết quả.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($danhSach as $hp): ?>

                                <tr>
                                    <td><?= (int) $hp['id'] ?></td>
                                    <td><strong><?= e($hp['ma_hoc_phan']) ?></strong></td>
                                    <td><?= e($hp['ten_hoc_phan']) ?></td>
                                    <td>
                                        <?php
                                        $loaiHpRaw = (string)($hp['loai_hoc_phan'] ?? '');
                                        $loaiHpMap = [
                                            'dai_cuong'    => 'Đại cương',
                                            'co_so_nganh'  => 'Cơ sở ngành',
                                            'chuyen_nganh' => 'Chuyên ngành'
                                        ];
                                        $loaiHpText = $loaiHpMap[$loaiHpRaw] ?? ($loaiHpRaw !== '' ? $loaiHpRaw : 'Đại cương');
                                        ?>
                                        <span class="badge-knowledge" style="display:inline-block; padding: 3px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                            <?= e($loaiHpText) ?>
                                        </span>
                                    </td>
                                    <td><?= (int) $hp['so_tin_chi'] ?></td>
                                    <td>
                                        <div class="action-buttons">

                                            <!-- XEM CHI TIẾT -->
                                            <button
                                                type="button"
                                                class="btn-view"
                                                title="Xem chi tiết học phần"
                                                aria-label="Xem chi tiết học phần"
                                                onclick="xemChiTietHocPhan(this)"
                                                data-ma="<?= e($hp['ma_hoc_phan']) ?>"
                                                data-ten="<?= e($hp['ten_hoc_phan']) ?>"
                                                data-tinchi="<?= (int) $hp['so_tin_chi'] ?>"
                                                data-mota="<?= e($hp['mo_ta'] ?? '') ?>"
                                            >
                                                👁
                                            </button>

                                            <!-- SỬA -->
                                            <a
                                                class="btn-edit"
                                                href="hoc_phan.php?sua=<?= (int) $hp['id'] ?>"
                                            >
                                                Sửa
                                            </a>

                                            <!-- XÓA -->
                                            <form
                                                method="POST"
                                                action="hoc_phan.php"
                                                class="delete-form"
                                                onsubmit="return confirm('Bạn có chắc muốn xóa học phần này?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION['csrf_token']) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="hanh_dong"
                                                    value="xoa"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $hp['id'] ?>"
                                                >

                                                <button type="submit" class="btn-delete">
                                                    Xóa
                                                </button>
                                            </form>

                                        </div>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>
                    </table>

                </div>

                <!-- PHÂN TRANG -->
                <?php if ($tongTrang > 1): ?>

                    <div class="course-pagination">

                        <div class="course-pagination-info">
                            Hiển thị
                            <?= $tongKetQua > 0 ? $viTriBatDau + 1 : 0 ?>
                            –
                            <?= min($viTriBatDau + $soDongMoiTrang, $tongKetQua) ?>
                            trong tổng số
                            <?= number_format($tongKetQua) ?> kết quả
                        </div>

                        <div class="course-pagination-list">

                            <?php
                            $queryTrang = [];

                            if ($tuKhoa !== '') {
                                $queryTrang['q'] = $tuKhoa;
                            }
                            ?>

                            <a
                                href="?<?= e(http_build_query(array_merge(
                                    $queryTrang,
                                    ['page' => max(1, $trangHienTai - 1)]
                                ))) ?>"
                                class="<?= $trangHienTai <= 1 ? 'disabled' : '' ?>"
                            >
                                Trước
                            </a>

                            <?php for ($i = 1; $i <= $tongTrang; $i++): ?>

                                <a
                                    href="?<?= e(http_build_query(array_merge(
                                        $queryTrang,
                                        ['page' => $i]
                                    ))) ?>"
                                    class="<?= $i === $trangHienTai ? 'active' : '' ?>"
                                >
                                    <?= $i ?>
                                </a>

                            <?php endfor; ?>

                            <a
                                href="?<?= e(http_build_query(array_merge(
                                    $queryTrang,
                                    ['page' => min($tongTrang, $trangHienTai + 1)]
                                ))) ?>"
                                class="<?= $trangHienTai >= $tongTrang ? 'disabled' : '' ?>"
                            >
                                Sau
                            </a>

                        </div>

                    </div>

                <?php elseif ($tongKetQua > 0): ?>

                    <div class="course-pagination">
                        <div class="course-pagination-info">
                            Hiển thị <?= $tongKetQua ?> / <?= $tongKetQua ?> học phần
                        </div>
                    </div>

                <?php endif; ?>

            </section>

            <footer class="page-footer">
                StudentHub · Hệ thống quản lý học phần
            </footer>

        </div>
    </main>
</div>

<!-- MODAL XEM CHI TIẾT HỌC PHẦN -->
<div
    class="course-modal"
    id="courseDetailModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="courseDetailTitle"
>
    <div class="course-modal-content">

        <div class="course-modal-header">
            <h2 id="courseDetailTitle">
                Chi tiết học phần
            </h2>

            <button
                type="button"
                class="course-modal-close"
                onclick="dongChiTietHocPhan()"
                aria-label="Đóng"
            >
                &times;
            </button>
        </div>

        <div class="course-detail-row">
            <span class="course-detail-label">Mã học phần</span>
            <div class="course-detail-value" id="detailMa"></div>
        </div>

        <div class="course-detail-row">
            <span class="course-detail-label">Tên học phần</span>
            <div class="course-detail-value" id="detailTen"></div>
        </div>

        <div class="course-detail-row">
            <span class="course-detail-label">Số tín chỉ</span>
            <div class="course-detail-value" id="detailTinChi"></div>
        </div>

        <div class="course-detail-row">
            <span class="course-detail-label">
                Mô tả và mục tiêu học phần
            </span>

            <div class="course-detail-value" id="detailMoTa"></div>
        </div>

    </div>
</div>

<script>
// =====================================================
// XEM CHI TIẾT HỌC PHẦN
// =====================================================

function xemChiTietHocPhan(button) {
    const ma = button.dataset.ma || '';
    const ten = button.dataset.ten || '';
    const tinChi = button.dataset.tinchi || '0';
    const moTa = button.dataset.mota || '';

    document.getElementById('detailMa').textContent = ma;
    document.getElementById('detailTen').textContent = ten;
    document.getElementById('detailTinChi').textContent = tinChi + ' tín chỉ';

    document.getElementById('detailMoTa').textContent =
        moTa.trim() !== ''
            ? moTa
            : 'Chưa có mô tả cho học phần này.';

    document.getElementById('courseDetailModal').classList.add('show');
}

// =====================================================
// ĐÓNG MODAL
// =====================================================

function dongChiTietHocPhan() {
    document.getElementById('courseDetailModal').classList.remove('show');
}

// Bấm ra ngoài cửa sổ để đóng
document.getElementById('courseDetailModal').addEventListener('click', function (event) {
    if (event.target === this) {
        dongChiTietHocPhan();
    }
});

// Nhấn ESC để đóng
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        dongChiTietHocPhan();
    }
});
</script>

</body>
</html>
