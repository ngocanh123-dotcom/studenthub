<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// 1. KIỂM TRA ĐĂNG NHẬP
// =====================================================

$role = strtolower(trim((string)($_SESSION['role'] ?? ($_SESSION['vai_tro'] ?? ''))));

if (!in_array($role, ['sinhvien', 'sinh_vien'], true)) {
    header('Location: /studenthub/public/index.php');
    exit('Vui lòng đăng nhập tài khoản sinh viên.');
}

$sinhVienId = (int)($_SESSION['nguoi_dung_id'] ?? $_SESSION['user_id'] ?? 0);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

// =====================================================
// 2. KẾT NỐI CƠ SỞ DỮ LIỆU
// =====================================================

if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} elseif (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=studenthub;charset=utf8mb4',
            'root',
            getenv('STUDENTHUB_DB_PASS') ?: '03112006',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $e) {
        error_log('StudentHub DB: ' . $e->getMessage());
        die('Không thể kết nối cơ sở dữ liệu StudentHub.');
    }
}

// =====================================================
// 3. HÀM HỖ TRỢ
// =====================================================

if (!function_exists('hienThi')) {
    function hienThi($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Phân tích chuỗi lịch học.
 *
 * Ví dụ hỗ trợ:
 * - Thứ 2, tiết 1-3
 * - Thứ 3 và Thứ 5, tiết 1-3
 * - Thứ 2, Thứ 4, tiết 2 đến 5
 * - Chủ nhật, tiết 1-3
 *
 * Trả về danh sách thứ và tiết học.
 */
if (!function_exists('phanTichLichHoc')) {
    function phanTichLichHoc(string $lichHoc): ?array
    {
        $lichHoc = trim($lichHoc);

        if ($lichHoc === '') {
            return null;
        }

        preg_match_all('/Thứ\s*([2-7])/iu', $lichHoc, $ketQuaThu);

        $cacThu = [];

        foreach ($ketQuaThu[1] ?? [] as $soThu) {
            $cacThu[] = (int)$soThu - 1;
        }

        if (preg_match('/Chủ\s*nhật/iu', $lichHoc)) {
            $cacThu[] = 7;
        }

        $cacThu = array_values(array_unique($cacThu));

        $coTiet = preg_match(
            '/tiết\s*(\d+)\s*(?:-|–|—|đến)\s*(?:tiết\s*)?(\d+)/iu',
            $lichHoc,
            $ketQuaTiet
        );

        if (!$coTiet) {
            return null;
        }

        $tietBatDau = (int)$ketQuaTiet[1];
        $tietKetThuc = (int)$ketQuaTiet[2];

        if (
            empty($cacThu)
            || $tietBatDau < 1
            || $tietKetThuc < $tietBatDau
            || $tietKetThuc > 12
        ) {
            return null;
        }

        return [
            'thu' => $cacThu,
            'tiet_bat_dau' => $tietBatDau,
            'tiet_ket_thuc' => $tietKetThuc
        ];
    }
}

/**
 * Kiểm tra hai lịch học có trùng nhau hay không.
 */
if (!function_exists('kiemTraTrungLich')) {
    function kiemTraTrungLich(string $lichA, string $lichB): bool
    {
        $a = phanTichLichHoc($lichA);
        $b = phanTichLichHoc($lichB);

        if ($a === null || $b === null) {
            return false;
        }

        $trungThu = !empty(array_intersect($a['thu'], $b['thu']));

        $trungTiet = $a['tiet_bat_dau'] <= $b['tiet_ket_thuc']
            && $b['tiet_bat_dau'] <= $a['tiet_ket_thuc'];

        return $trungThu && $trungTiet;
    }
}

// =====================================================
// 4. THÔNG TIN SINH VIÊN VÀ TRANG
// =====================================================

$hoTen = $_SESSION['ho_ten'] ?? $_SESSION['username'] ?? 'Sinh viên';
$maSinhVien = $_SESSION['ma_sinh_vien'] ?? $_SESSION['username'] ?? '';

$pageTitle = 'Đăng ký lớp học phần';
$breadcrumb = 'Trang chủ / Đăng ký lớp học phần';
$pageSubtitle = 'Xem lịch học, phòng học và sĩ số để chọn lớp phù hợp. Hệ thống cảnh báo nếu lớp trùng lịch với lớp đã đăng ký.';

$danhSachHocKy = [];
$danhSachLopTatCa = [];
$danhSachLop = [];

$cacLopDaDangKy = [];
$cacHocPhanDaDangKy = [];
$lopDaDangKyTheoHocPhan = [];
$lichHocDaDangKy = [];

$loi = '';
$hocKyDangChon = 0;

$thongBaoHuy = (string)($_GET['huy'] ?? '');

if ($thongBaoHuy !== '') {
    $dsThongBaoHuy = [
        'thanh_cong' => 'Hủy đăng ký học phần thành công!',
        'da_het_thoi_han_huy' => 'Đã hết thời hạn hủy học phần. Cổng đăng ký của lớp hoặc học kỳ đã đóng.',
        'khong_tim_thay_dang_ky' => 'Không tìm thấy thông tin đăng ký để hủy.',
        'lop_khong_hop_le' => 'Lớp học phần không hợp lệ.',
        'loi_he_thong' => 'Lỗi hệ thống khi hủy đăng ký.',
    ];

    $loi = $dsThongBaoHuy[$thongBaoHuy] ?? 'Thông báo: ' . $thongBaoHuy;
}

// =====================================================
// 5. LẤY DANH SÁCH HỌC KỲ
// =====================================================

try {
    $stmtHocKy = $pdo->query("
        SELECT
            id,
            ten_hoc_ky,
            ngay_bat_dau,
            ngay_ket_thuc,
            thoi_gian_mo_dk,
            thoi_gian_dong_dk,
            trang_thai_dang_ky
        FROM hoc_ky
        ORDER BY ngay_bat_dau DESC, id DESC
    ");

    $danhSachHocKy = $stmtHocKy->fetchAll(PDO::FETCH_ASSOC);

    // Ưu tiên học kỳ do sinh viên chọn trên giao diện.
    $hocKyYeuCau = (int)($_GET['hoc_ky_id'] ?? 0);

    $hocKyHopLe = false;

    foreach ($danhSachHocKy as $hocKy) {
        if ((int)$hocKy['id'] === $hocKyYeuCau) {
            $hocKyHopLe = true;
            break;
        }
    }

    if ($hocKyHopLe) {
        $hocKyDangChon = $hocKyYeuCau;
    } elseif (!empty($danhSachHocKy)) {
        $homNay = new DateTimeImmutable('today');

        // Ưu tiên học kỳ có ngày bắt đầu và kết thúc bao gồm ngày hiện tại.
        foreach ($danhSachHocKy as $hocKy) {
            if (
                !empty($hocKy['ngay_bat_dau'])
                && !empty($hocKy['ngay_ket_thuc'])
            ) {
                $ngayDau = new DateTimeImmutable($hocKy['ngay_bat_dau']);
                $ngayCuoi = new DateTimeImmutable($hocKy['ngay_ket_thuc']);

                if ($homNay >= $ngayDau && $homNay <= $ngayCuoi) {
                    $hocKyDangChon = (int)$hocKy['id'];
                    break;
                }
            }
        }

        // Nếu không có học kỳ hiện tại, chọn học kỳ mới nhất.
        if ($hocKyDangChon === 0) {
            $hocKyDangChon = (int)$danhSachHocKy[0]['id'];
        }
    }

} catch (PDOException $e) {
    error_log('StudentHub hoc_ky: ' . $e->getMessage());
    $loi = 'Không thể tải danh sách học kỳ.';
}

// =====================================================
// 6. LẤY DANH SÁCH LỚP HỌC PHẦN
// =====================================================

try {
    $sql = "
        SELECT
            lhp.id,
            lhp.ma_lop,
            lhp.hoc_phan_id,
            lhp.hoc_ky_id,
            lhp.giang_vien_id,
            lhp.trang_thai,
            lhp.si_so_toi_da,
            lhp.lich_hoc,
            lhp.phong_hoc,
            lhp.thoi_gian_mo_rieng,
            lhp.thoi_gian_dong_rieng,

            hp.ma_hoc_phan,
            hp.ten_hoc_phan,
            hp.so_tin_chi,

            hk.ten_hoc_ky,
            hk.trang_thai_dang_ky,
            hk.thoi_gian_mo_dk,
            hk.thoi_gian_dong_dk,

            nd.ho_ten AS ten_giang_vien,

            (
                SELECT COUNT(*)
                FROM dang_ky_hoc dkh
                WHERE dkh.lop_hoc_phan_id = lhp.id
                  AND dkh.trang_thai = 'dang_ky'
            ) AS si_so_hien_tai

        FROM lop_hoc_phan lhp

        INNER JOIN hoc_phan hp
            ON lhp.hoc_phan_id = hp.id

        INNER JOIN hoc_ky hk
            ON lhp.hoc_ky_id = hk.id

        LEFT JOIN nguoi_dung nd
            ON lhp.giang_vien_id = nd.id

        ORDER BY hp.ten_hoc_phan ASC, lhp.ma_lop ASC
    ";

    $danhSachLopTatCa = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // Chỉ lấy lớp thuộc học kỳ đang được chọn.
    foreach ($danhSachLopTatCa as $lop) {
        if ((int)$lop['hoc_ky_id'] === $hocKyDangChon) {
            $danhSachLop[] = $lop;
        }
    }

    // =================================================
    // 7. LẤY CÁC LỚP SINH VIÊN ĐÃ ĐĂNG KÝ
    // =================================================

    if ($sinhVienId > 0) {
        $stmt = $pdo->prepare("
            SELECT
                dkh.lop_hoc_phan_id,
                lhp.hoc_phan_id,
                lhp.hoc_ky_id,
                lhp.ma_lop,
                lhp.lich_hoc

            FROM dang_ky_hoc dkh

            INNER JOIN lop_hoc_phan lhp
                ON lhp.id = dkh.lop_hoc_phan_id

            WHERE dkh.sinh_vien_id = :id
              AND dkh.trang_thai = 'dang_ky'
        ");

        $stmt->execute(['id' => $sinhVienId]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $registration) {

            // Chỉ lấy đăng ký thuộc học kỳ đang chọn.
            if ((int)$registration['hoc_ky_id'] !== $hocKyDangChon) {
                continue;
            }

            $idLopDaDangKy = (int)$registration['lop_hoc_phan_id'];
            $idHocPhan = (int)$registration['hoc_phan_id'];
            $idHocKy = (int)$registration['hoc_ky_id'];

            $cacLopDaDangKy[] = $idLopDaDangKy;
            $cacHocPhanDaDangKy[] = $idHocPhan;

            $lopDaDangKyTheoHocPhan[$idHocPhan] = $registration;

            $lichHocDaDangKy[$idHocKy][] = $registration;
        }
    }

} catch (PDOException $e) {
    error_log('StudentHub danh sach lop: ' . $e->getMessage());
    $loi = 'Không thể tải danh sách lớp học phần. Hãy kiểm tra lại cơ sở dữ liệu.';
}

// =====================================================
// 8. TÍNH SỐ LỚP ĐANG MỞ VÀ SỐ LỚP ĐÃ ĐĂNG KÝ
// =====================================================

$soLopMo = 0;
$soLopDaDangKy = 0;

foreach ($danhSachLop as $lop) {
    $id = (int)$lop['id'];
    $siSo = (int)($lop['si_so_hien_tai'] ?? 0);
    $toiDa = (int)($lop['si_so_toi_da'] ?? 0);

    $coHanRieng = !empty($lop['thoi_gian_mo_rieng'])
        || !empty($lop['thoi_gian_dong_rieng']);

    $now = new DateTimeImmutable();

    if ($coHanRieng) {
        $moDangKy = !empty($lop['thoi_gian_mo_rieng'])
            ? new DateTimeImmutable($lop['thoi_gian_mo_rieng'])
            : null;

        $dongDangKy = !empty($lop['thoi_gian_dong_rieng'])
            ? new DateTimeImmutable($lop['thoi_gian_dong_rieng'])
            : null;

        $trongHanDangKy = (!$moDangKy || $now >= $moDangKy)
            && (!$dongDangKy || $now <= $dongDangKy);

    } else {
        $moDangKy = !empty($lop['thoi_gian_mo_dk'])
            ? new DateTimeImmutable($lop['thoi_gian_mo_dk'])
            : null;

        $dongDangKy = !empty($lop['thoi_gian_dong_dk'])
            ? new DateTimeImmutable($lop['thoi_gian_dong_dk'])
            : null;

        $trongHanDangKy = (int)($lop['trang_thai_dang_ky'] ?? 0) === 1
            && (!$moDangKy || $now >= $moDangKy)
            && (!$dongDangKy || $now <= $dongDangKy);
    }

    if (
        ($lop['trang_thai'] ?? '') === 'mo'
        && $toiDa > 0
        && $siSo < $toiDa
        && $trongHanDangKy
        && !in_array((int)$lop['hoc_phan_id'], $cacHocPhanDaDangKy, true)
    ) {
        $soLopMo++;
    }

    if (in_array($id, $cacLopDaDangKy, true)) {
        $soLopDaDangKy++;
    }
}

// =====================================================
// 9. THỜI KHÓA BIỂU CỦA SINH VIÊN
// =====================================================

// Hai ngày này chỉ phục vụ việc xem thời khóa biểu.
// Không liên quan đến các cột trong cơ sở dữ liệu.

$ngayBatDauMacDinh = (new DateTimeImmutable('today'))->modify('monday this week');
$ngayKetThucMacDinh = $ngayBatDauMacDinh->modify('+6 days');

$giaTriTuNgay = (string)($_GET['tkb_tu_ngay'] ?? $ngayBatDauMacDinh->format('Y-m-d'));
$giaTriDenNgay = (string)($_GET['tkb_den_ngay'] ?? $ngayKetThucMacDinh->format('Y-m-d'));

$ngayBatDauTkb = DateTimeImmutable::createFromFormat('!Y-m-d', $giaTriTuNgay);
$ngayKetThucTkb = DateTimeImmutable::createFromFormat('!Y-m-d', $giaTriDenNgay);

if (!$ngayBatDauTkb || $ngayBatDauTkb->format('Y-m-d') !== $giaTriTuNgay) {
    $ngayBatDauTkb = $ngayBatDauMacDinh;
    $giaTriTuNgay = $ngayBatDauTkb->format('Y-m-d');
}

if (!$ngayKetThucTkb || $ngayKetThucTkb->format('Y-m-d') !== $giaTriDenNgay) {
    $ngayKetThucTkb = $ngayKetThucMacDinh;
    $giaTriDenNgay = $ngayKetThucTkb->format('Y-m-d');
}

if ($ngayKetThucTkb < $ngayBatDauTkb) {
    $ngayKetThucTkb = $ngayBatDauTkb;
    $giaTriDenNgay = $giaTriTuNgay;
}

$soNgayTrongKhoang = (int)$ngayBatDauTkb->diff($ngayKetThucTkb)->format('%a') + 1;

$ngayTrongKhoang = [];

for ($i = 0; $i < $soNgayTrongKhoang; $i++) {
    $ngayTrongKhoang[] = $ngayBatDauTkb->modify('+' . $i . ' days');
}

$tenThuTkb = [
    1 => 'Thứ 2',
    2 => 'Thứ 3',
    3 => 'Thứ 4',
    4 => 'Thứ 5',
    5 => 'Thứ 6',
    6 => 'Thứ 7',
    7 => 'Chủ nhật'
];

$thoiKhoaBieuTheoNgay = [];

foreach ($danhSachLop as $lopTkb) {

    if (!in_array((int)$lopTkb['id'], $cacLopDaDangKy, true)) {
        continue;
    }

    $chuoiLich = trim((string)($lopTkb['lich_hoc'] ?? ''));

    $lichPhanTich = phanTichLichHoc($chuoiLich);

    if ($lichPhanTich === null) {
        continue;
    }

    foreach ($ngayTrongKhoang as $ngay) {
        $maNgay = $ngay->format('Y-m-d');
        $thuTrongTuan = (int)$ngay->format('N');

        if (!in_array($thuTrongTuan, $lichPhanTich['thu'], true)) {
            continue;
        }

        $thoiKhoaBieuTheoNgay[$maNgay][] = [
            'ten_hoc_phan' => (string)($lopTkb['ten_hoc_phan'] ?? ''),
            'ma_hoc_phan' => (string)($lopTkb['ma_hoc_phan'] ?? ''),
            'ma_lop' => (string)($lopTkb['ma_lop'] ?? ''),
            'tiet_bat_dau' => $lichPhanTich['tiet_bat_dau'],
            'tiet_ket_thuc' => $lichPhanTich['tiet_ket_thuc'],
            'phong_hoc' => (string)($lopTkb['phong_hoc'] ?? ''),
            'ten_giang_vien' => (string)($lopTkb['ten_giang_vien'] ?? ''),
        ];
    }
}

// Lấy tên học kỳ đang xem.
$tenHocKyDangChon = 'Chưa có học kỳ';

foreach ($danhSachHocKy as $hocKy) {
    if ((int)$hocKy['id'] === $hocKyDangChon) {
        $tenHocKyDangChon = $hocKy['ten_hoc_ky'];
        break;
    }
}

require_once __DIR__ . '/../app/Views/layouts/header.php';
?>

<style>
.registration-page {
    width: 100%;
    color: #172b4d;
    padding-bottom: 30px;
}

.registration-page * {
    box-sizing: border-box;
}

.registration-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin: 4px 0 22px;
}

.registration-summary-card {
    display: flex;
    align-items: center;
    gap: 15px;
    min-width: 0;
    padding: 19px;
    background: #fff;
    border: 1px solid #e5edf7;
    border-radius: 15px;
    box-shadow: 0 3px 12px rgba(28, 65, 115, .035);
}

.registration-summary-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 46px;
    height: 46px;
    border-radius: 13px;
    background: #eaf2ff;
    color: #1769d2;
    font-size: 22px;
}

.registration-summary-card:nth-child(2) .registration-summary-icon {
    color: #07865c;
    background: #e7f8f0;
}

.registration-summary-card:nth-child(3) .registration-summary-icon {
    color: #9a65dc;
    background: #f2eaff;
}

.registration-summary-card p {
    margin: 0 0 6px;
    color: #718096;
    font-size: 12px;
    line-height: 1.5;
}

.registration-summary-card h2 {
    margin: 0;
    color: #172b4d;
    font-size: 25px;
    font-weight: 800;
}

.registration-panel {
    margin-bottom: 22px;
    padding: 23px;
    background: #fff;
    border: 1px solid #e1eaf5;
    border-radius: 16px;
    box-shadow: 0 3px 12px rgba(28, 65, 115, .035);
}

.registration-panel-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 18px;
}

.registration-panel-heading h2 {
    margin: 0 0 6px;
    color: #172b4d;
    font-size: 18px;
    font-weight: 800;
}

.registration-panel-heading p {
    margin: 0;
    color: #718096;
    font-size: 12.5px;
    line-height: 1.7;
}

.registration-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 29px;
    height: 27px;
    padding: 0 9px;
    border-radius: 8px;
    color: #1769d2;
    background: #eaf2ff;
    font-size: 12px;
    font-weight: 800;
}

.registration-tools {
    display: grid;
    grid-template-columns: minmax(200px, 1fr) 190px;
    gap: 12px;
    margin-bottom: 20px;
}

.registration-guide {
    margin: -4px 0 20px;
    padding: 12px 15px;
    color: #35547a;
    background: #f0f7ff;
    border: 1px solid #d5e7fb;
    border-radius: 10px;
    font-size: 12px;
    line-height: 1.7;
}

.registration-search {
    position: relative;
}

.registration-search input,
.registration-filter {
    width: 100%;
    min-height: 43px;
    padding: 10px 13px;
    color: #243b5a;
    background: #fff;
    border: 1px solid #dce5f0;
    border-radius: 10px;
    outline: none;
    font: inherit;
    font-size: 12.5px;
}

.registration-search input:focus,
.registration-filter:focus {
    border-color: #6ba4f7;
    box-shadow: 0 0 0 3px rgba(23, 105, 210, .08);
}

.registration-search input {
    padding-left: 38px;
}

.registration-search-icon {
    position: absolute;
    top: 50%;
    left: 13px;
    transform: translateY(-50%);
    color: #8293a9;
    font-size: 17px;
    pointer-events: none;
}

.registration-section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 13px;
    color: #263d5c;
    font-size: 14px;
    font-weight: 800;
}

.registration-section-dot {
    width: 9px;
    height: 9px;
    flex-shrink: 0;
    border-radius: 50%;
    background: #1769d2;
}

.registration-section-dot.green {
    background: #10a875;
}

.registration-table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #e7edf5;
    border-radius: 12px;
}

.registration-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 12px;
}

.registration-table th {
    padding: 13px 14px;
    color: #718096;
    background: #f7f9fc;
    border-bottom: 1px solid #e7edf5;
    font-size: 10.5px;
    font-weight: 800;
    white-space: nowrap;
}

.registration-table td {
    padding: 14px;
    border-bottom: 1px solid #edf1f6;
    vertical-align: middle;
}

.registration-table tbody tr:last-child td {
    border-bottom: none;
}

.registration-table tbody tr:hover {
    background: #fafcff;
}

.registration-subject-name {
    min-width: 160px;
    color: #243b5a;
    font-size: 12.5px;
    font-weight: 800;
    line-height: 1.6;
}

.registration-subject-code {
    display: inline-block;
    margin-bottom: 4px;
    color: #1769d2;
    font-size: 10.5px;
    font-weight: 800;
}

.registration-muted {
    color: #75869c;
    line-height: 1.6;
}

.registration-schedule {
    min-width: 190px;
    color: #243b5a;
    font-weight: 700;
    line-height: 1.6;
}

.registration-schedule-room {
    display: block;
    margin-top: 3px;
    color: #75869c;
    font-size: 11px;
    font-weight: 500;
}

.registration-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 9px;
    border-radius: 7px;
    font-size: 10.5px;
    font-weight: 800;
    white-space: nowrap;
}

.registration-status-open,
.registration-status-registered {
    color: #07805a;
    background: #e5f8ef;
}

.registration-status-full,
.registration-status-closed {
    color: #bd5a16;
    background: #fff1e5;
}

.registration-status-conflict {
    color: #b4233b;
    background: #fff1f2;
}

.registration-status-note {
    display: block;
    max-width: 190px;
    margin-top: 5px;
    color: #667991;
    font-size: 10.5px;
    font-weight: 500;
    line-height: 1.5;
    white-space: normal;
}

.registration-action-note {
    max-width: 190px;
    color: #667991;
    font-size: 11px;
    line-height: 1.55;
}

.registration-action-note strong {
    display: block;
    margin-bottom: 2px;
    color: #344b69;
    font-size: 11.5px;
}

.registration-action-note.is-blocked strong {
    color: #9a4f18;
}

.registration-action-note.is-conflict strong {
    color: #b4233b;
}

.registration-capacity {
    min-width: 95px;
}

.registration-capacity-label {
    display: flex;
    justify-content: space-between;
    gap: 7px;
    margin-bottom: 7px;
    color: #718096;
    font-size: 10.5px;
}

.registration-capacity-label strong {
    color: #344b69;
}

.registration-progress {
    height: 5px;
    overflow: hidden;
    background: #e8eef6;
    border-radius: 20px;
}

.registration-progress-bar {
    height: 100%;
    background: #3d88ed;
    border-radius: 20px;
}

.registration-progress-bar.full {
    background: #ed6767;
}

.registration-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 33px;
    padding: 8px 11px;
    border: 0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 10.5px;
    font-weight: 800;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: .2s;
}

.registration-button-primary {
    color: #fff;
    background: #1769d2;
}

.registration-button-primary:hover {
    color: #fff;
    background: #1054ad;
}

.registration-button-cancel {
    color: #d14343;
    background: #fff0f0;
}

.registration-button-cancel:hover {
    background: #ffe1e1;
}

.registration-button-disabled {
    color: #8290a2;
    background: #edf1f6;
    cursor: not-allowed;
}

.registration-empty-state {
    padding: 30px 15px;
    color: #8190a5;
    text-align: center;
    font-size: 12.5px;
    line-height: 1.8;
}

.registration-empty-state strong {
    display: block;
    margin-bottom: 5px;
    color: #405572;
    font-size: 14px;
}

.registration-alert {
    padding: 13px 16px;
    margin-bottom: 18px;
    color: #b4233b;
    background: #fff1f2;
    border: 1px solid #fecdd3;
    border-radius: 10px;
    font-size: 12.5px;
}

.registration-footer-note {
    margin: 16px 0 0;
    color: #8290a5;
    font-size: 11px;
    line-height: 1.8;
}

.registration-hidden {
    display: none !important;
}

/* Chọn học kỳ */
.registration-semester {
    display: flex;
    flex-direction: column;
    gap: 7px;
    max-width: 350px;
    margin-bottom: 20px;
}

.registration-semester label {
    color: #536780;
    font-size: 11.5px;
    font-weight: 800;
}

.registration-semester select {
    width: 100%;
    min-height: 43px;
    padding: 10px 13px;
    color: #243b5a;
    background: #fff;
    border: 1px solid #dce5f0;
    border-radius: 10px;
    outline: none;
    font: inherit;
    font-size: 12.5px;
}

.registration-semester select:focus {
    border-color: #6ba4f7;
    box-shadow: 0 0 0 3px rgba(23, 105, 210, .08);
}

@media (max-width: 1050px) {
    .registration-summary {
        gap: 10px;
    }

    .registration-summary-card {
        padding: 14px;
    }

    .registration-panel {
        padding: 18px;
    }

    .registration-table {
        min-width: 1100px;
    }
}

@media (max-width: 700px) {
    .registration-summary {
        grid-template-columns: 1fr;
    }

    .registration-tools {
        grid-template-columns: 1fr;
    }

    .registration-panel-heading {
        gap: 8px;
    }

    .registration-panel {
        padding: 15px;
    }
}

/* Thời khóa biểu */
.timetable-panel {
    margin-bottom: 22px;
    padding: 23px;
    background: #fff;
    border: 1px solid #e1eaf5;
    border-radius: 16px;
    box-shadow: 0 3px 12px rgba(28, 65, 115, .035);
}

.timetable-toolbar {
    display: flex;
    align-items: end;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
}

.timetable-date-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
}

.timetable-date-field input {
    min-height: 39px;
    padding: 8px 10px;
    color: #243b5a;
    border: 1px solid #dce5f0;
    border-radius: 9px;
    font: inherit;
}

.timetable-apply {
    min-height: 39px;
    padding: 8px 15px;
    color: #fff;
    background: #1769d2;
    border: 0;
    border-radius: 9px;
    font: inherit;
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
}

.timetable-range-note {
    margin: 0 0 14px;
    color: #718096;
    font-size: 11.5px;
    line-height: 1.6;
}

.timetable-scroll {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    overscroll-behavior-x: contain;
    scrollbar-width: thin;
    border: 1px solid #e5edf7;
    border-radius: 12px;
}

.timetable-grid {
    display: grid;
    grid-template-columns: 58px repeat(var(--day-count), 165px);
    grid-template-rows: 58px repeat(12, minmax(61px, auto));
    width: max-content;
    min-width: 100%;
    background: #fff;
}

.timetable-head {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    padding: 7px 4px;
    color: #294363;
    background: #f3f7fc;
    border-right: 1px solid #e5edf7;
    border-bottom: 1px solid #e5edf7;
    font-size: 11px;
    font-weight: 800;
}

.timetable-head small {
    color: #8293a9;
    font-size: 10px;
    font-weight: 600;
}

.timetable-time-head {
    color: #718096;
    font-size: 10px;
}

.timetable-period {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #718096;
    background: #f8fafd;
    border-right: 1px solid #e5edf7;
    border-bottom: 1px solid #e5edf7;
    font-size: 11px;
    font-weight: 800;
}

.timetable-empty-cell {
    min-height: 61px;
    border-right: 1px solid #edf1f6;
    border-bottom: 1px solid #edf1f6;
}

.timetable-class {
    z-index: 1;
    align-self: stretch;
    margin: 4px;
    padding: 8px;
    overflow: hidden;
    color: #174b8c;
    background: #eaf3ff;
    border: 1px solid #c9def9;
    border-left: 3px solid #3984df;
    border-radius: 8px;
    font-size: 10px;
    line-height: 1.45;
}

.timetable-class strong {
    display: block;
    margin-bottom: 3px;
    color: #174b8c;
    font-size: 10.5px;
}

.timetable-class span {
    display: block;
    margin-top: 2px;
    color: #54708f;
    overflow-wrap: anywhere;
}

.timetable-empty {
    padding: 28px 15px;
    color: #8190a5;
    text-align: center;
    background: #f8fafd;
    border: 1px dashed #d9e3ef;
    border-radius: 11px;
    font-size: 12px;
    line-height: 1.8;
}

.timetable-empty strong {
    color: #405572;
}

.timetable-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 13px;
    color: #718096;
    font-size: 10.5px;
}

.timetable-legend span::before {
    display: inline-block;
    width: 8px;
    height: 8px;
    margin-right: 6px;
    content: "";
    background: #3984df;
    border-radius: 2px;
}

@media (max-width: 700px) {
    .timetable-panel {
        padding: 15px;
    }
}
</style>

<div class="registration-page">

    <?php if ($loi !== ''): ?>
        <div class="registration-alert"><?= hienThi($loi) ?></div>
    <?php endif; ?>

    <!-- THỐNG KÊ -->
    <section class="registration-summary">

        <article class="registration-summary-card">
            <div class="registration-summary-icon">▤</div>
            <div>
                <p>Tổng số lớp học phần</p>
                <h2><?= count($danhSachLop) ?></h2>
            </div>
        </article>

        <article class="registration-summary-card">
            <div class="registration-summary-icon">✓</div>
            <div>
                <p>Lớp đang mở còn chỗ</p>
                <h2><?= $soLopMo ?></h2>
            </div>
        </article>

        <article class="registration-summary-card">
            <div class="registration-summary-icon">▣</div>
            <div>
                <p>Lớp học phần đã đăng ký</p>
                <h2><?= $soLopDaDangKy ?></h2>
            </div>
        </article>

    </section>

    <!-- THỜI KHÓA BIỂU -->
    <section class="timetable-panel">

        <div class="registration-panel-heading">
            <div>
                <h2>Thời khóa biểu của tôi</h2>
                <p>
                    Lịch được tự động cập nhật theo các lớp học phần bạn đang đăng ký
                    trong học kỳ <?= hienThi($tenHocKyDangChon) ?>.
                </p>
            </div>
        </div>

        <div class="timetable-toolbar">

            <label class="timetable-date-field">
                Từ ngày
                <input
                    type="date"
                    id="tkbTuNgay"
                    value="<?= hienThi($giaTriTuNgay) ?>"
                >
            </label>

            <label class="timetable-date-field">
                Đến ngày
                <input
                    type="date"
                    id="tkbDenNgay"
                    value="<?= hienThi($giaTriDenNgay) ?>"
                >
            </label>

            <button
                type="button"
                class="timetable-apply"
                id="apDungKhoangTkb"
            >
                Xem thời khóa biểu
            </button>

        </div>

        <p class="timetable-range-note">
            Khoảng đang xem:
            <strong><?= $ngayBatDauTkb->format('d/m/Y') ?></strong>
            đến
            <strong><?= $ngayKetThucTkb->format('d/m/Y') ?></strong>.
            Lịch học được hiển thị theo thứ và tiết học lặp hằng tuần.
            Khoảng ngày này chỉ dùng để lọc lịch hiển thị.
        </p>

        <?php if (empty($thoiKhoaBieuTheoNgay)): ?>

            <div class="timetable-empty">
                <strong>Chưa có lịch học để hiển thị</strong><br>

                <?php if ($soLopDaDangKy === 0): ?>
                    Bạn chưa đăng ký lớp học phần nào trong học kỳ này.
                    Sau khi đăng ký lớp, thời khóa biểu sẽ tự động xuất hiện tại đây.
                <?php else: ?>
                    Các lớp đã đăng ký chưa có dữ liệu lịch học hợp lệ
                    trong khoảng ngày đang chọn.
                <?php endif; ?>
            </div>

        <?php else: ?>

            <div class="timetable-scroll">

                <div
                    class="timetable-grid"
                    style="--day-count: <?= count($ngayTrongKhoang) ?>;"
                >

                    <div
                        class="timetable-head timetable-time-head"
                        style="grid-column:1;grid-row:1;"
                    >
                        TIẾT
                    </div>

                    <?php foreach ($ngayTrongKhoang as $viTriNgay => $ngay): ?>

                        <div
                            class="timetable-head"
                            style="grid-column:<?= $viTriNgay + 2 ?>;grid-row:1;"
                        >
                            <?= hienThi($tenThuTkb[(int)$ngay->format('N')]) ?>
                            <small><?= $ngay->format('d/m/Y') ?></small>
                        </div>

                    <?php endforeach; ?>

                    <?php for ($tiet = 1; $tiet <= 12; $tiet++): ?>

                        <div
                            class="timetable-period"
                            style="grid-column:1;grid-row:<?= $tiet + 1 ?>;"
                        >
                            <?= $tiet ?>
                        </div>

                        <?php foreach ($ngayTrongKhoang as $viTriNgay => $ngay): ?>

                            <div
                                class="timetable-empty-cell"
                                style="grid-column:<?= $viTriNgay + 2 ?>;grid-row:<?= $tiet + 1 ?>;"
                            ></div>

                        <?php endforeach; ?>

                    <?php endfor; ?>

                    <?php foreach ($ngayTrongKhoang as $viTriNgay => $ngay): ?>

                        <?php $maNgay = $ngay->format('Y-m-d'); ?>

                        <?php foreach ($thoiKhoaBieuTheoNgay[$maNgay] ?? [] as $monHoc): ?>

                            <article
                                class="timetable-class"
                                style="
                                    grid-column:<?= $viTriNgay + 2 ?>;
                                    grid-row:<?= $monHoc['tiet_bat_dau'] + 1 ?>
                                    / span <?= $monHoc['tiet_ket_thuc'] - $monHoc['tiet_bat_dau'] + 1 ?>;
                                "
                            >
                                <strong><?= hienThi($monHoc['ten_hoc_phan']) ?></strong>

                                <span>
                                    <?= hienThi($monHoc['ma_hoc_phan']) ?>
                                    ·
                                    <?= hienThi($monHoc['ma_lop']) ?>
                                </span>

                                <span>
                                    Tiết <?= $monHoc['tiet_bat_dau'] ?>–<?= $monHoc['tiet_ket_thuc'] ?>
                                </span>

                                <span>
                                    <?= hienThi($monHoc['phong_hoc'] ?: 'Chưa phân phòng') ?>
                                </span>

                                <span>
                                    <?= hienThi($monHoc['ten_giang_vien'] ?: 'Chưa phân công') ?>
                                </span>
                            </article>

                        <?php endforeach; ?>

                    <?php endforeach; ?>

                </div>
            </div>

            <div class="timetable-legend">
                <span>Lớp học phần đã đăng ký</span>
                <span>Kéo thanh cuộn ngang để xem các ngày tiếp theo</span>
            </div>

        <?php endif; ?>

    </section>

    <!-- DANH SÁCH ĐĂNG KÝ -->
    <section class="registration-panel">

        <div class="registration-panel-heading">
            <div>
                <h2>Đăng ký lớp học phần</h2>
                <p>
                    Mỗi dòng là một lớp cụ thể. Hãy xem lịch học và phòng học trước khi chọn lớp.
                </p>
            </div>
        </div>

        <!-- CHỌN HỌC KỲ -->
        <div class="registration-semester">

            <label for="chonHocKy">HỌC KỲ ĐANG XEM</label>

            <select id="chonHocKy">

                <?php if (empty($danhSachHocKy)): ?>

                    <option value="">Chưa có học kỳ</option>

                <?php else: ?>

                    <?php foreach ($danhSachHocKy as $hocKy): ?>

                        <option
                            value="<?= (int)$hocKy['id'] ?>"
                            <?= (int)$hocKy['id'] === $hocKyDangChon ? 'selected' : '' ?>
                        >
                            <?= hienThi($hocKy['ten_hoc_ky']) ?>
                        </option>

                    <?php endforeach; ?>

                <?php endif; ?>

            </select>

        </div>

        <div class="registration-tools">

            <label class="registration-search">
                <span class="registration-search-icon">⌕</span>

                <input
                    type="search"
                    id="registrationSearch"
                    placeholder="Tìm học phần, mã lớp, lịch học, phòng hoặc giảng viên..."
                    autocomplete="off"
                >
            </label>

           <select id="registrationFilter" class="registration-filter">
    <option value="all">Tất cả lớp học phần</option>
    <option value="open">Có thể đăng ký</option>
    <option value="registered">Đã đăng ký</option>
    <option value="unavailable">Không thể đăng ký</option>
    <option value="schedule_issue">Cần kiểm tra lịch</option>
</select>

        </div>

        <div class="registration-guide">
            <strong>Cách chọn lớp:</strong>
            Mỗi dòng là một lớp học phần riêng. So sánh lịch học và phòng trước khi bấm
            <strong>“Đăng ký lớp này”</strong>.
            Cột tình trạng cho biết lý do lớp có thể hoặc chưa thể đăng ký.
        </div>

        <!-- LỚP ĐÃ ĐĂNG KÝ -->
        <div class="registration-section-title">
            <span class="registration-section-dot green"></span>
            Lớp học phần đã đăng ký
            <span class="registration-count"><?= $soLopDaDangKy ?></span>
        </div>

        <div class="registration-table-wrap" style="margin-bottom:26px">

            <table class="registration-table">

                <thead>
                    <tr>
                        <th>HỌC PHẦN</th>
                        <th>MÃ LỚP</th>
                        <th>LỊCH HỌC / PHÒNG</th>
                        <th>GIẢNG VIÊN</th>
                        <th>HỌC KỲ</th>
                        <th>SĨ SỐ</th>
                        <th>TÌNH TRẠNG LỚP</th>
                        <th>BẠN CÓ THỂ LÀM GÌ?</th>
                    </tr>
                </thead>

                <tbody id="registeredList">

                <?php
                $coLopDaDangKy = false;

                foreach ($danhSachLop as $lop):

                    $idLop = (int)$lop['id'];

                    if (!in_array($idLop, $cacLopDaDangKy, true)) {
                        continue;
                    }

                    $coLopDaDangKy = true;

                    $siSo = (int)($lop['si_so_hien_tai'] ?? 0);
                    $toiDa = (int)($lop['si_so_toi_da'] ?? 0);
                    $tiLe = $toiDa > 0 ? min(100, $siSo / $toiDa * 100) : 0;

                    $tuKhoa = mb_strtolower(
                        ($lop['ten_hoc_phan'] ?? '') . ' ' .
                        ($lop['ma_hoc_phan'] ?? '') . ' ' .
                        ($lop['ma_lop'] ?? '') . ' ' .
                        ($lop['ten_giang_vien'] ?? '') . ' ' .
                        ($lop['ten_hoc_ky'] ?? '') . ' ' .
                        ($lop['lich_hoc'] ?? '') . ' ' .
                        ($lop['phong_hoc'] ?? ''),
                        'UTF-8'
                    );
                ?>

                    <tr
                        class="registration-row"
                        data-status="registered"
                        data-search="<?= hienThi($tuKhoa) ?>"
                    >

                        <td>
                            <div class="registration-subject-name">
                                <span class="registration-subject-code">
                                    <?= hienThi($lop['ma_hoc_phan']) ?>
                                </span>
                                <br>
                                <?= hienThi($lop['ten_hoc_phan']) ?>
                            </div>

                            <div class="registration-muted">
                                <?= (int)$lop['so_tin_chi'] ?> tín chỉ
                            </div>
                        </td>

                        <td><?= hienThi($lop['ma_lop']) ?></td>

                        <td class="registration-schedule">
                            <?= hienThi($lop['lich_hoc'] ?: 'Chưa xếp lịch') ?>

                            <span class="registration-schedule-room">
                                <?= hienThi($lop['phong_hoc'] ?: 'Chưa phân phòng') ?>
                            </span>
                        </td>

                        <td>
                            <?= hienThi($lop['ten_giang_vien'] ?: 'Chưa phân công') ?>
                        </td>

                        <td><?= hienThi($lop['ten_hoc_ky']) ?></td>

                        <td>
                            <div class="registration-capacity">

                                <div class="registration-capacity-label">
                                    <strong><?= $siSo ?>/<?= $toiDa ?></strong>
                                </div>

                                <div class="registration-progress">
                                    <div
                                        class="registration-progress-bar <?= ($toiDa > 0 && $siSo >= $toiDa) ? 'full' : '' ?>"
                                        style="width:<?= $tiLe ?>%"
                                    ></div>
                                </div>

                            </div>
                        </td>

                        <td>
                            <span class="registration-status registration-status-registered">
                                Đã đăng ký lớp này
                            </span>

                            <span class="registration-status-note">
                                Bạn đang có tên trong lớp học phần này.
                            </span>
                        </td>

                        <td>

                            <?php
                            $thoiGianDong = !empty($lop['thoi_gian_dong_rieng'])
                                ? $lop['thoi_gian_dong_rieng']
                                : ($lop['thoi_gian_dong_dk'] ?? null);

                            $tsDong = !empty($thoiGianDong)
                                ? strtotime($thoiGianDong)
                                : null;

                            $conTrongHan = ($tsDong !== null)
                                ? (time() <= $tsDong)
                                : true;
                            ?>

                            <?php if ($conTrongHan): ?>

                                <form
                                    method="POST"
                                    action="/studenthub/public/index.php?route=huy_dang_ky_hoc.php"
                                    onsubmit="return xacNhanHuyDangKy(this, <?= json_encode($lop['ten_hoc_phan'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)"
                                    style="margin:0;"
                                >

                                    <input
                                        type="hidden"
                                        name="lop_hoc_phan_id"
                                        value="<?= $idLop ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= hienThi($csrfToken) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="registration-button registration-button-cancel"
                                    >
                                        Hủy đăng ký
                                    </button>

                                </form>

                                <?php if ($tsDong !== null): ?>
                                    <div style="font-size:11px;color:#64748b;margin-top:4px;white-space:nowrap;">
                                        Hạn hủy: <?= date('d/m/Y H:i', $tsDong) ?>
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>

                                <button
                                    type="button"
                                    class="registration-button registration-button-disabled"
                                    disabled
                                >
                                    Đã hết hạn hủy
                                </button>

                                <div style="font-size:11px;color:#dc2626;margin-top:4px;font-weight:600;white-space:nowrap;">
                                    Liên hệ phòng đào tạo nếu cần hỗ trợ.
                                </div>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$coLopDaDangKy): ?>

                    <tr id="registeredEmpty">
                        <td colspan="8">
                            <div class="registration-empty-state">
                                <strong>Bạn chưa đăng ký lớp học phần nào</strong>
                                Chọn một lớp trong danh sách bên dưới để bắt đầu đăng ký.
                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <!-- LỚP CÓ THỂ ĐĂNG KÝ -->
        <div class="registration-section-title">
            <span class="registration-section-dot"></span>
            Lớp học phần có thể đăng ký
            <span class="registration-count">
                <?= count($danhSachLop) - $soLopDaDangKy ?>
            </span>
        </div>

        <div class="registration-table-wrap">

            <table class="registration-table">

                <thead>
                    <tr>
                        <th>HỌC PHẦN</th>
                        <th>MÃ LỚP</th>
                        <th>LỊCH HỌC / PHÒNG</th>
                        <th>GIẢNG VIÊN</th>
                        <th>HỌC KỲ</th>
                        <th>SĨ SỐ</th>
                        <th>TÌNH TRẠNG LỚP</th>
                        <th>BẠN CÓ THỂ LÀM GÌ?</th>
                    </tr>
                </thead>

                <tbody id="availableList">

                <?php
                $coLopChuaDangKy = false;

                foreach ($danhSachLop as $lop):

                    $idLop = (int)$lop['id'];

                    if (in_array($idLop, $cacLopDaDangKy, true)) {
                        continue;
                    }

                    $coLopChuaDangKy = true;

                    $idHocPhan = (int)$lop['hoc_phan_id'];

                    $daDangKyHocPhan = in_array(
                        $idHocPhan,
                        $cacHocPhanDaDangKy,
                        true
                    );

                    $lopDaChonCungHocPhan =
                        $lopDaDangKyTheoHocPhan[$idHocPhan] ?? null;

                    $lichHocMoi = (string)($lop['lich_hoc'] ?? '');

                    $lichHocPhanTich = phanTichLichHoc($lichHocMoi);

                    $lopTrungLich = null;
                    $lopLichChuaRo = null;

                    if ($lichHocPhanTich !== null) {

                        foreach ($lichHocDaDangKy[(int)$lop['hoc_ky_id']] ?? [] as $lopDaDangKy) {

                            $lichDaDangKy = (string)($lopDaDangKy['lich_hoc'] ?? '');

                            if (phanTichLichHoc($lichDaDangKy) === null) {
                                $lopLichChuaRo = $lopDaDangKy;
                                break;
                            }

                            if (kiemTraTrungLich($lichHocMoi, $lichDaDangKy)) {
                                $lopTrungLich = $lopDaDangKy;
                                break;
                            }
                        }
                    }

                    $siSo = (int)($lop['si_so_hien_tai'] ?? 0);
                    $toiDa = (int)($lop['si_so_toi_da'] ?? 0);

                    $tiLe = $toiDa > 0
                        ? min(100, $siSo / $toiDa * 100)
                        : 0;

                    $dangMo = ($lop['trang_thai'] ?? '') === 'mo';
                    $conCho = $toiDa > 0 && $siSo < $toiDa;

                    $now = new DateTimeImmutable();

                    $coHanRieng = !empty($lop['thoi_gian_mo_rieng'])
                        || !empty($lop['thoi_gian_dong_rieng']);

                    if ($coHanRieng) {

                        $moDangKy = !empty($lop['thoi_gian_mo_rieng'])
                            ? new DateTimeImmutable($lop['thoi_gian_mo_rieng'])
                            : null;

                        $dongDangKy = !empty($lop['thoi_gian_dong_rieng'])
                            ? new DateTimeImmutable($lop['thoi_gian_dong_rieng'])
                            : null;

                        $trongHanDangKy = (!$moDangKy || $now >= $moDangKy)
                            && (!$dongDangKy || $now <= $dongDangKy);

                    } else {

                        $moDangKy = !empty($lop['thoi_gian_mo_dk'])
                            ? new DateTimeImmutable($lop['thoi_gian_mo_dk'])
                            : null;

                        $dongDangKy = !empty($lop['thoi_gian_dong_dk'])
                            ? new DateTimeImmutable($lop['thoi_gian_dong_dk'])
                            : null;

                        $trongHanDangKy = (int)($lop['trang_thai_dang_ky'] ?? 0) === 1
                            && (!$moDangKy || $now >= $moDangKy)
                            && (!$dongDangKy || $now <= $dongDangKy);
                    }

                    $coTheDangKy = $dangMo
                        && $conCho
                        && $trongHanDangKy
                        && !$daDangKyHocPhan
                        && $lichHocPhanTich !== null
                        && $lopLichChuaRo === null
                        && $lopTrungLich === null;

                    $trangThaiLoc = $daDangKyHocPhan
                        ? 'selected_other'
                        : (
                            $lopTrungLich !== null
                                ? 'conflict'
                                : (
                                    $lopLichChuaRo !== null
                                        ? 'schedule_unknown'
                                        : (
                                            $lichHocPhanTich === null
                                                ? 'unscheduled'
                                                : (
                                                    $coTheDangKy
                                                        ? 'open'
                                                        : (
                                                            !$dangMo || !$trongHanDangKy
                                                                ? 'closed'
                                                                : 'full'
                                                        )
                                                )
                                        )
                                )
                        );

                    $tuKhoa = mb_strtolower(
                        ($lop['ten_hoc_phan'] ?? '') . ' ' .
                        ($lop['ma_hoc_phan'] ?? '') . ' ' .
                        ($lop['ma_lop'] ?? '') . ' ' .
                        ($lop['ten_giang_vien'] ?? '') . ' ' .
                        ($lop['ten_hoc_ky'] ?? '') . ' ' .
                        ($lop['lich_hoc'] ?? '') . ' ' .
                        ($lop['phong_hoc'] ?? ''),
                        'UTF-8'
                    );
                ?>

                    <tr
                        class="registration-row"
                        data-status="<?= $trangThaiLoc ?>"
                        data-search="<?= hienThi($tuKhoa) ?>"
                    >

                        <td>
                            <div class="registration-subject-name">
                                <span class="registration-subject-code">
                                    <?= hienThi($lop['ma_hoc_phan']) ?>
                                </span>
                                <br>
                                <?= hienThi($lop['ten_hoc_phan']) ?>
                            </div>

                            <div class="registration-muted">
                                <?= (int)$lop['so_tin_chi'] ?> tín chỉ
                            </div>
                        </td>

                        <td><?= hienThi($lop['ma_lop']) ?></td>

                        <td class="registration-schedule">
                            <?= hienThi($lop['lich_hoc'] ?: 'Chưa xếp lịch') ?>

                            <span class="registration-schedule-room">
                                <?= hienThi($lop['phong_hoc'] ?: 'Chưa phân phòng') ?>
                            </span>
                        </td>

                        <td>
                            <?= hienThi($lop['ten_giang_vien'] ?: 'Chưa phân công') ?>
                        </td>

                        <td><?= hienThi($lop['ten_hoc_ky']) ?></td>

                        <td>
                            <div class="registration-capacity">

                                <div class="registration-capacity-label">
                                    <strong><?= $siSo ?>/<?= $toiDa ?></strong>
                                </div>

                                <div class="registration-progress">
                                    <div
                                        class="registration-progress-bar <?= !$conCho ? 'full' : '' ?>"
                                        style="width:<?= $tiLe ?>%"
                                    ></div>
                                </div>

                            </div>
                        </td>

                        <td>

                            <?php if ($coTheDangKy): ?>

                                <span class="registration-status registration-status-open">
                                    Có thể đăng ký
                                </span>

                                <span class="registration-status-note">
                                    Còn chỗ, đúng hạn và không trùng lịch.
                                </span>

                            <?php elseif ($daDangKyHocPhan): ?>

                                <span class="registration-status registration-status-registered">
                                    Đã chọn lớp khác
                                </span>

                                <span class="registration-status-note">
                                    Bạn đã đăng ký lớp
                                    <?= hienThi($lopDaChonCungHocPhan['ma_lop'] ?? '') ?>
                                    của học phần này.
                                </span>

                            <?php elseif ($lopTrungLich !== null): ?>

                                <span class="registration-status registration-status-conflict">
                                    Trùng lịch
                                </span>

                                <span class="registration-status-note">
                                    Trùng với lớp
                                    <?= hienThi($lopTrungLich['ma_lop']) ?>:
                                    <?= hienThi($lopTrungLich['lich_hoc']) ?>.
                                </span>

                            <?php elseif ($lichHocPhanTich === null): ?>

                                <span class="registration-status registration-status-closed">
                                    Chưa có lịch hợp lệ
                                </span>

                                <span class="registration-status-note">
                                    Chưa thể kiểm tra trùng lịch. Liên hệ phòng đào tạo.
                                </span>

                            <?php elseif ($lopLichChuaRo !== null): ?>

                                <span class="registration-status registration-status-closed">
                                    Chưa xác định được lịch
                                </span>

                                <span class="registration-status-note">
                                    Lớp bạn đã đăng ký
                                    <?= hienThi($lopLichChuaRo['ma_lop']) ?>
                                    chưa có lịch hợp lệ.
                                </span>

                            <?php elseif (!$dangMo): ?>

                                <span class="registration-status registration-status-closed">
                                    Lớp đã khóa
                                </span>

                                <span class="registration-status-note">
                                    Lớp hiện không nhận đăng ký.
                                </span>

                            <?php elseif (!$trongHanDangKy): ?>

                                <span class="registration-status registration-status-closed">
                                    Ngoài thời gian đăng ký
                                </span>

                                <span class="registration-status-note">
                                    Hiện chưa mở hoặc đã hết hạn đăng ký.
                                </span>

                            <?php else: ?>

                                <span class="registration-status registration-status-full">
                                    Đã đủ chỗ
                                </span>

                                <span class="registration-status-note">
                                    Lớp đã đạt sĩ số tối đa.
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($sinhVienId <= 0): ?>

                                <a
                                    class="registration-button registration-button-primary"
                                    href="/studenthub/public/index.php"
                                >
                                    Đăng nhập
                                </a>

                            <?php elseif ($coTheDangKy): ?>

                                <form
                                    method="POST"
                                    action="/studenthub/public/index.php?route=dang_ky_hoc.php"
                                    style="margin:0;"
                                >

                                    <input
                                        type="hidden"
                                        name="lop_hoc_phan_id"
                                        value="<?= $idLop ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= hienThi($csrfToken) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="registration-button registration-button-primary"
                                    >
                                        Đăng ký lớp này
                                    </button>

                                </form>

                            <?php elseif ($daDangKyHocPhan): ?>

                                <div class="registration-action-note is-blocked">
                                    <strong>Không cần đăng ký thêm</strong>
                                    Bạn đã chọn lớp
                                    <?= hienThi($lopDaChonCungHocPhan['ma_lop'] ?? '') ?>
                                    cho học phần này.
                                </div>

                            <?php elseif ($lopTrungLich !== null): ?>

                                <div class="registration-action-note is-conflict">
                                    <strong>Hãy chọn lớp khác</strong>
                                    Lớp này trùng lịch với
                                    <?= hienThi($lopTrungLich['ma_lop']) ?>.
                                </div>

                            <?php elseif ($lopLichChuaRo !== null): ?>

                                <div class="registration-action-note is-blocked">
                                    <strong>Chưa thể đăng ký</strong>
                                    Cần cập nhật lịch lớp
                                    <?= hienThi($lopLichChuaRo['ma_lop']) ?>
                                    để kiểm tra xung đột.
                                </div>

                            <?php elseif ($lichHocPhanTich === null): ?>

                                <div class="registration-action-note is-blocked">
                                    <strong>Chưa thể đăng ký</strong>
                                    Lớp chưa được xếp lịch hợp lệ.
                                    Vui lòng liên hệ phòng đào tạo.
                                </div>

                            <?php elseif (!$dangMo): ?>

                                <div class="registration-action-note is-blocked">
                                    <strong>Chưa thể đăng ký</strong>
                                    Lớp đã bị khóa.
                                </div>

                            <?php elseif (!$trongHanDangKy): ?>

                                <div class="registration-action-note is-blocked">
                                    <strong>Chưa thể đăng ký</strong>
                                    Cổng đăng ký lớp hiện chưa mở hoặc đã hết hạn.
                                </div>

                            <?php else: ?>

                                <div class="registration-action-note is-blocked">
                                    <strong>Chưa thể đăng ký</strong>
                                    Lớp đã đủ sĩ số.
                                </div>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$coLopChuaDangKy): ?>

                    <tr id="availableEmpty">
                        <td colspan="8">
                            <div class="registration-empty-state">
                                <strong>Không còn lớp học phần phù hợp</strong>
                                Hiện không còn lớp nào trong học kỳ này để đăng ký thêm.
                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <p class="registration-footer-note">
            Lưu ý: Mỗi dòng là một lớp học phần. Hãy kiểm tra lịch học, phòng,
            học kỳ và sĩ số; hệ thống không cho đăng ký lớp trùng lịch hoặc
            trùng học phần đã chọn trong cùng học kỳ.
        </p>

    </section>

</div>

<script>
// =====================================================
// 1. TÌM KIẾM VÀ LỌC LỚP HỌC PHẦN
// =====================================================

// =====================================================
// 1. TÌM KIẾM VÀ LỌC LỚP HỌC PHẦN
// =====================================================

(function () {
    const searchInput = document.getElementById('registrationSearch');
    const filterSelect = document.getElementById('registrationFilter');

    if (!searchInput || !filterSelect) {
        return;
    }

    // Gộp các trạng thái chi tiết thành 5 nhóm lọc.
    function kiemTraTrangThai(status, trangThaiLoc) {

        if (trangThaiLoc === 'all') {
            return true;
        }

        if (trangThaiLoc === 'open') {
            return status === 'open';
        }

        if (trangThaiLoc === 'registered') {
            return status === 'registered';
        }

        if (trangThaiLoc === 'unavailable') {
            return [
                'full',
                'closed',
                'selected_other'
            ].includes(status);
        }

        if (trangThaiLoc === 'schedule_issue') {
            return [
                'conflict',
                'schedule_unknown',
                'unscheduled'
            ].includes(status);
        }

        return true;
    }

    function locDanhSach() {

        const tuKhoa = searchInput.value
            .trim()
            .toLocaleLowerCase('vi');

        const trangThai = filterSelect.value;

        document.querySelectorAll('.registration-table').forEach(function (table) {

            const tbody = table.querySelector('tbody');

            if (!tbody) return;

            const rows = tbody.querySelectorAll('.registration-row');

            let soDongHienThi = 0;

            rows.forEach(function (row) {

                const noiDung = (row.dataset.search || '')
                    .toLocaleLowerCase('vi');

                const status = row.dataset.status || '';

                const khopTuKhoa = noiDung.includes(tuKhoa);

                const khopTrangThai = kiemTraTrangThai(
                    status,
                    trangThai
                );

                const hienThi = khopTuKhoa && khopTrangThai;

                row.classList.toggle(
                    'registration-hidden',
                    !hienThi
                );

                if (hienThi) {
                    soDongHienThi++;
                }
            });

            // Thông báo khi không có kết quả phù hợp.
            let empty = tbody.querySelector(
                '.registration-filter-empty'
            );

            if (soDongHienThi === 0 && rows.length > 0) {

                if (!empty) {

                    empty = document.createElement('tr');
                    empty.className = 'registration-filter-empty';

                    empty.innerHTML = `
                        <td colspan="8">
                            <div class="registration-empty-state">
                                <strong>Không tìm thấy lớp học phần phù hợp</strong>
                                Thử thay đổi từ khóa tìm kiếm hoặc trạng thái lọc.
                            </div>
                        </td>
                    `;

                    tbody.appendChild(empty);
                }

            } else if (empty) {
                empty.remove();
            }
        });
    }

    searchInput.addEventListener('input', locDanhSach);
    filterSelect.addEventListener('change', locDanhSach);

})();



// =====================================================
// 2. CHỌN HỌC KỲ
// =====================================================

(function () {

    const chonHocKy = document.getElementById('chonHocKy');

    if (!chonHocKy) {
        return;
    }

    chonHocKy.addEventListener('change', function () {

        const hocKyId = chonHocKy.value;

        const url = new URL(window.location.href);

        if (hocKyId) {
            url.searchParams.set('hoc_ky_id', hocKyId);
        } else {
            url.searchParams.delete('hoc_ky_id');
        }

        // Giữ nguyên khoảng ngày thời khóa biểu khi đổi học kỳ.
        window.location.href = url.toString();
    });

})();


// =====================================================
// 3. LỌC THỜI KHÓA BIỂU THEO NGÀY
// =====================================================

(function () {

    const tuNgay = document.getElementById('tkbTuNgay');
    const denNgay = document.getElementById('tkbDenNgay');
    const nutApDung = document.getElementById('apDungKhoangTkb');

    if (!tuNgay || !denNgay || !nutApDung) {
        return;
    }

    tuNgay.addEventListener('change', function () {

        denNgay.min = tuNgay.value;

        if (denNgay.value && denNgay.value < tuNgay.value) {
            denNgay.value = tuNgay.value;
        }
    });

    denNgay.addEventListener('change', function () {

        tuNgay.max = denNgay.value;
    });

    nutApDung.addEventListener('click', function () {

        if (!tuNgay.value || !denNgay.value) {
            alert('Vui lòng chọn đầy đủ ngày bắt đầu và ngày kết thúc.');
            return;
        }

        if (denNgay.value < tuNgay.value) {
            alert('Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.');
            return;
        }

        const url = new URL(window.location.href);

        url.searchParams.set('tkb_tu_ngay', tuNgay.value);
        url.searchParams.set('tkb_den_ngay', denNgay.value);

        window.location.href = url.toString();
    });

})();


// =====================================================
// 4. XÁC NHẬN HỦY ĐĂNG KÝ
// =====================================================

function xacNhanHuyDangKy(form, tenHocPhan) {

    return confirm(
        'Bạn có chắc chắn muốn hủy đăng ký học phần "' +
        tenHocPhan +
        '" không?'
    );
}
</script>

</main>
</div>

<script src="/studenthub/assets/js/main.js"></script>
</body>
</html>