
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sinhVienId = (int)($_SESSION['nguoi_dung_id'] ?? $_SESSION['user_id'] ?? 0);
// Tạo mã bảo mật cho thao tác hủy đăng ký
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

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
            getenv('STUDENTHUB_DB_PASS') ?: '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $e) {
        die('Không thể kết nối cơ sở dữ liệu StudentHub.');
    }
}

$hoTen = $_SESSION['ho_ten'] ?? $_SESSION['username'] ?? 'Sinh viên';
$maSinhVien = $_SESSION['ma_sinh_vien'] ?? $_SESSION['username'] ?? '';

$pageTitle = 'Đăng ký học phần';
$breadcrumb = 'Trang chủ / Đăng ký học phần';
$pageSubtitle = 'Tìm kiếm học phần, theo dõi lớp đã đăng ký và lựa chọn lớp phù hợp với kế hoạch học tập.';

if (!function_exists('hienThi')) {
    function hienThi($value) {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$danhSachLop = [];
$cacLopDaDangKy = [];
$loi = '';

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
            hp.ma_hoc_phan,
            hp.ten_hoc_phan,
            hp.so_tin_chi,
            hk.ten_hoc_ky,
            nd.ho_ten AS ten_giang_vien,
            (
                SELECT COUNT(*)
                FROM dang_ky_hoc dkh
                WHERE dkh.lop_hoc_phan_id = lhp.id
                  AND dkh.trang_thai = 'dang_ky'
            ) AS si_so_hien_tai
        FROM lop_hoc_phan lhp
        INNER JOIN hoc_phan hp ON lhp.hoc_phan_id = hp.id
        INNER JOIN hoc_ky hk ON lhp.hoc_ky_id = hk.id
        INNER JOIN nguoi_dung nd ON lhp.giang_vien_id = nd.id
        ORDER BY hp.ten_hoc_phan ASC, lhp.ma_lop ASC
    ";

    $danhSachLop = $pdo->query($sql)->fetchAll();

    if ($sinhVienId > 0) {
        $stmt = $pdo->prepare("
            SELECT lop_hoc_phan_id
            FROM dang_ky_hoc
            WHERE sinh_vien_id = :id
              AND trang_thai = 'dang_ky'
        ");
        $stmt->execute(['id' => $sinhVienId]);
        $cacLopDaDangKy = array_map(
            'intval',
            $stmt->fetchAll(PDO::FETCH_COLUMN)
        );
    }
} catch (PDOException $e) {
    error_log('StudentHub: ' . $e->getMessage());
    $loi = 'Không thể tải danh sách lớp học phần. Hãy kiểm tra lại cơ sở dữ liệu.';
}

$soLopMo = 0;
$soLopDaDangKy = 0;

foreach ($danhSachLop as $lop) {
    $id = (int)$lop['id'];
    $siSo = (int)($lop['si_so_hien_tai'] ?? 0);
    $toiDa = (int)($lop['si_so_toi_da'] ?? 0);

    if (
        ($lop['trang_thai'] ?? '') === 'mo'
        && $toiDa > 0
        && $siSo < $toiDa
    ) {
        $soLopMo++;
    }

    if (in_array($id, $cacLopDaDangKy, true)) {
        $soLopDaDangKy++;
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
        min-width: 760px;
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
</style>

<div class="registration-page">

    <?php if ($loi !== ''): ?>
        <div class="registration-alert"><?= hienThi($loi) ?></div>
    <?php endif; ?>

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
                <p>Học phần đã đăng ký</p>
                <h2><?= $soLopDaDangKy ?></h2>
            </div>
        </article>
    </section>

    <section class="registration-panel">
        <div class="registration-panel-heading">
            <div>
                <h2>Quản lý đăng ký học phần</h2>
                <p>Tìm kiếm học phần theo tên, mã lớp hoặc giảng viên.</p>
            </div>
        </div>

        <div class="registration-tools">
            <label class="registration-search">
                <span class="registration-search-icon">⌕</span>
                <input
                    type="search"
                    id="registrationSearch"
                    placeholder="Tìm tên học phần, mã học phần, giảng viên..."
                    autocomplete="off"
                >
            </label>

            <select id="registrationFilter" class="registration-filter">
                <option value="all">Tất cả trạng thái</option>
                <option value="open">Đang mở đăng ký</option>
                <option value="full">Đã đủ sĩ số</option>
                <option value="closed">Đã khóa</option>
                <option value="registered">Đã đăng ký</option>
            </select>
        </div>

        <div class="registration-section-title">
            <span class="registration-section-dot green"></span>
            Học phần đã đăng ký
            <span class="registration-count"><?= $soLopDaDangKy ?></span>
        </div>

        <div class="registration-table-wrap" style="margin-bottom:26px">
            <table class="registration-table">
                <thead>
                    <tr>
                        <th>HỌC PHẦN</th>
                        <th>MÃ LỚP</th>
                        <th>GIẢNG VIÊN</th>
                        <th>HỌC KỲ</th>
                        <th>SĨ SỐ</th>
                        <th>TRẠNG THÁI</th>
                        <th>THAO TÁC</th>
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
                        ($lop['ten_hoc_ky'] ?? ''),
                        'UTF-8'
                    );
                ?>
                    <tr class="registration-row"
                        data-status="registered"
                        data-search="<?= hienThi($tuKhoa) ?>">
                        <td>
                            <div class="registration-subject-name">
                                <span class="registration-subject-code"><?= hienThi($lop['ma_hoc_phan']) ?></span><br>
                                <?= hienThi($lop['ten_hoc_phan']) ?>
                            </div>
                            <div class="registration-muted"><?= (int)$lop['so_tin_chi'] ?> tín chỉ</div>
                        </td>
                        <td><?= hienThi($lop['ma_lop']) ?></td>
                        <td><?= hienThi($lop['ten_giang_vien'] ?: 'Chưa phân công') ?></td>
                        <td><?= hienThi($lop['ten_hoc_ky']) ?></td>
                        <td>
                            <div class="registration-capacity">
                                <div class="registration-capacity-label">
                                    <strong><?= $siSo ?>/<?= $toiDa ?></strong>
                                </div>
                                <div class="registration-progress">
                                    <div class="registration-progress-bar <?= ($toiDa > 0 && $siSo >= $toiDa) ? 'full' : '' ?>"
                                         style="width:<?= $tiLe ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="registration-status registration-status-registered">Đã đăng ký</span></td>
                        <td>
                         <form
    method="POST"
    action="huy_dang_ky_hoc.php"
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
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$coLopDaDangKy): ?>
                    <tr id="registeredEmpty">
                        <td colspan="7">
                            <div class="registration-empty-state">
                                <strong>Bạn chưa đăng ký học phần nào</strong>
                                Chọn một lớp trong danh sách bên dưới để bắt đầu đăng ký.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="registration-section-title">
            <span class="registration-section-dot"></span>
            Danh sách học phần
            <span class="registration-count"><?= count($danhSachLop) - $soLopDaDangKy ?></span>
        </div>

        <div class="registration-table-wrap">
            <table class="registration-table">
                <thead>
                    <tr>
                        <th>HỌC PHẦN</th>
                        <th>MÃ LỚP</th>
                        <th>GIẢNG VIÊN</th>
                        <th>HỌC KỲ</th>
                        <th>SĨ SỐ</th>
                        <th>TRẠNG THÁI</th>
                        <th>THAO TÁC</th>
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
                    $siSo = (int)($lop['si_so_hien_tai'] ?? 0);
                    $toiDa = (int)($lop['si_so_toi_da'] ?? 0);
                    $tiLe = $toiDa > 0 ? min(100, $siSo / $toiDa * 100) : 0;
                    $dangMo = ($lop['trang_thai'] ?? '') === 'mo';
                    $conCho = $toiDa > 0 && $siSo < $toiDa;
                    $coTheDangKy = $dangMo && $conCho;
                    $trangThaiLoc = $coTheDangKy ? 'open' : (!$dangMo ? 'closed' : 'full');

                    $tuKhoa = mb_strtolower(
                        ($lop['ten_hoc_phan'] ?? '') . ' ' .
                        ($lop['ma_hoc_phan'] ?? '') . ' ' .
                        ($lop['ma_lop'] ?? '') . ' ' .
                        ($lop['ten_giang_vien'] ?? '') . ' ' .
                        ($lop['ten_hoc_ky'] ?? ''),
                        'UTF-8'
                    );
                ?>
                    <tr class="registration-row"
                        data-status="<?= $trangThaiLoc ?>"
                        data-search="<?= hienThi($tuKhoa) ?>">
                        <td>
                            <div class="registration-subject-name">
                                <span class="registration-subject-code"><?= hienThi($lop['ma_hoc_phan']) ?></span><br>
                                <?= hienThi($lop['ten_hoc_phan']) ?>
                            </div>
                            <div class="registration-muted"><?= (int)$lop['so_tin_chi'] ?> tín chỉ</div>
                        </td>
                        <td><?= hienThi($lop['ma_lop']) ?></td>
                        <td><?= hienThi($lop['ten_giang_vien'] ?: 'Chưa phân công') ?></td>
                        <td><?= hienThi($lop['ten_hoc_ky']) ?></td>
                        <td>
                            <div class="registration-capacity">
                                <div class="registration-capacity-label">
                                    <strong><?= $siSo ?>/<?= $toiDa ?></strong>
                                </div>
                                <div class="registration-progress">
                                    <div class="registration-progress-bar <?= !$conCho ? 'full' : '' ?>"
                                         style="width:<?= $tiLe ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($coTheDangKy): ?>
                                <span class="registration-status registration-status-open">Đang mở</span>
                            <?php elseif (!$dangMo): ?>
                                <span class="registration-status registration-status-closed">Đã khóa</span>
                            <?php else: ?>
                                <span class="registration-status registration-status-full">Đủ sĩ số</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($sinhVienId <= 0): ?>
                                <a class="registration-button registration-button-primary"
                                   href="/studenthub/public/dang_nhap.php">Đăng nhập</a>
                            <?php elseif ($coTheDangKy): ?>
                                <a class="registration-button registration-button-primary"
                                   href="dang_ky_hoc.php?lop_hoc_phan_id=<?= $idLop ?>">
                                    + Đăng ký
                                </a>
                            <?php else: ?>
                                <button type="button" class="registration-button registration-button-disabled" disabled>
                                    Không khả dụng
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$coLopChuaDangKy): ?>
                    <tr id="availableEmpty">
                        <td colspan="7">
                            <div class="registration-empty-state">
                                <strong>Bạn đã đăng ký tất cả lớp hiện có</strong>
                                Hiện không còn lớp nào trong danh sách để đăng ký thêm.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p class="registration-footer-note">
            Lưu ý: Kiểm tra mã lớp, học kỳ và sĩ số trước khi đăng ký. Chỉ những lớp đang mở và còn chỗ mới có thể đăng ký.
        </p>
    </section>
</div>

<script>
(function () {
    const searchInput = document.getElementById('registrationSearch');
    const filterSelect = document.getElementById('registrationFilter');

    function locDanhSach() {
        const tuKhoa = (searchInput.value || '')
            .trim()
            .toLocaleLowerCase('vi');

        const trangThai = filterSelect.value;

        document.querySelectorAll('.registration-row').forEach(function (row) {
            const noiDung = (row.dataset.search || '')
                .toLocaleLowerCase('vi');

            const status = row.dataset.status || '';
            const khopTuKhoa = noiDung.includes(tuKhoa);

            const khopTrangThai =
                trangThai === 'all' ||
                status === trangThai;

            row.classList.toggle(
                'registration-hidden',
                !(khopTuKhoa && khopTrangThai)
            );
        });

        document.querySelectorAll('.registration-table').forEach(function (table) {
            const rows = table.querySelectorAll('tbody .registration-row');
            const empty = table.querySelector('tbody .registration-filter-empty');

            const conHang = Array.from(rows).some(function (row) {
                return !row.classList.contains('registration-hidden');
            });

            if (!conHang && rows.length > 0) {
                if (!empty) {
                    const tr = document.createElement('tr');
                    tr.className = 'registration-filter-empty';

                    tr.innerHTML =
                        '<td colspan="7">' +
                        '<div class="registration-empty-state">' +
                        '<strong>Không tìm thấy học phần phù hợp</strong>' +
                        'Thử thay đổi từ khóa tìm kiếm hoặc trạng thái lọc.' +
                        '</div></td>';

                    table.querySelector('tbody').appendChild(tr);
                }
            } else if (empty) {
                empty.remove();
            }
        });
    }

    searchInput.addEventListener('input', locDanhSach);
    filterSelect.addEventListener('change', locDanhSach);
})();

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
