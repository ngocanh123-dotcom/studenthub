<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: dang_nhap.php');
    exit;
}

if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} elseif (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $db_host = 'localhost';
    $db_name = 'studenthub';
    $db_user = 'root';
    $db_pass = ''; // Dùng đúng mật khẩu MySQL của dự án

    try {
        $pdo = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
            $db_user,
            $db_pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e) {
        die("Lỗi kết nối CSDL studenthub bằng PDO: " . $e->getMessage());
    }
}
// 3. Thông tin sinh viên
$hoTen = $_SESSION['ho_ten'] ?? $_SESSION['username'] ?? 'Sinh viên';
$maSinhVien = $_SESSION['ma_sinh_vien']
    ?? $_SESSION['username']
    ?? '';

// 4. Thiết lập tiêu đề cho giao diện dùng chung
$pageTitle = 'Đăng ký học phần';
$breadcrumb = 'Trang chủ / Đăng ký học phần';
$pageSubtitle = 'Xem danh sách lớp học phần, kiểm tra sĩ số và lựa chọn lớp phù hợp với kế hoạch học tập của bạn.';

// 5. Hàm hiển thị dữ liệu an toàn
if (!function_exists('hienThi')) {
    function hienThi($value)
    {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

// 6. Khởi tạo dữ liệu
$danhSachLop = [];
$cacLopDaDangKy = [];
$loi = '';

try {
    // Lấy danh sách lớp và sĩ số hiện tại
    $sql = "
        SELECT
            lhp.id,
            lhp.ma_lop,
            lhp.trang_thai,
            lhp.si_so_toi_da,
            hp.ma_hoc_phan,
            hp.ten_hoc_phan,
            hp.so_tin_chi,
            hk.ten_hoc_ky,
            nd.ho_ten AS ten_giang_vien,
            COUNT(
                CASE
                    WHEN dh.trang_thai = 'dang_ky' THEN 1
                    ELSE NULL
                END
            ) AS si_so_hien_tai
        FROM lop_hoc_phan AS lhp
        INNER JOIN hoc_phan AS hp
            ON lhp.hoc_phan_id = hp.id
        INNER JOIN hoc_ky AS hk
            ON lhp.hoc_ky_id = hk.id
        LEFT JOIN nguoi_dung AS nd
            ON lhp.giang_vien_id = nd.id
        LEFT JOIN dang_ky_hoc AS dh
            ON lhp.id = dh.lop_hoc_phan_id
        GROUP BY
            lhp.id,
            lhp.ma_lop,
            lhp.trang_thai,
            lhp.si_so_toi_da,
            hp.ma_hoc_phan,
            hp.ten_hoc_phan,
            hp.so_tin_chi,
            hk.ten_hoc_ky,
            nd.ho_ten
        ORDER BY lhp.id DESC
    ";

    $stmt = $pdo->query($sql);
    $danhSachLop = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy các lớp sinh viên đã đăng ký
    $sqlDaDangKy = "
        SELECT lop_hoc_phan_id
        FROM dang_ky_hoc
        WHERE sinh_vien_id = :sinh_vien_id
          AND trang_thai = 'dang_ky'
    ";

    $stmtDaDangKy = $pdo->prepare($sqlDaDangKy);

    $stmtDaDangKy->execute([
        'sinh_vien_id' => (int) $_SESSION['user_id']
    ]);

    $cacLopDaDangKy = array_map(
        'intval',
        $stmtDaDangKy->fetchAll(PDO::FETCH_COLUMN)
    );

} catch (PDOException $e) {
    $loi = 'Không thể tải danh sách lớp học phần. Vui lòng kiểm tra kết nối cơ sở dữ liệu.';
}

// 7. Tính số liệu thống kê
$tongSoLop = count($danhSachLop);
$soLopMo = 0;
$soLopDaDangKy = 0;

foreach ($danhSachLop as $lop) {
    $dangMo = ($lop['trang_thai'] ?? '') === 'mo';

    $siSoHienTai = (int) ($lop['si_so_hien_tai'] ?? 0);
    $siSoToiDa = (int) ($lop['si_so_toi_da'] ?? 0);

    if ($dangMo && $siSoToiDa > 0 && $siSoHienTai < $siSoToiDa) {
        $soLopMo++;
    }

    if (in_array((int) $lop['id'], $cacLopDaDangKy, true)) {
        $soLopDaDangKy++;
    }
}

// 8. Nạp giao diện chung của nhóm
require_once __DIR__ . '/../app/Views/layouts/header.php';
?>

<style>


.registration-page {
    width: 100%;
}

.registration-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}

.registration-summary-card {
    background: #ffffff;
    border: 1px solid var(--border-light, #e2e8f0);
    border-radius: 16px;
    padding: 22px 24px;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
}

.registration-summary-card p {
    color: var(--text-muted, #64748b);
    font-size: 13px;
    margin-bottom: 12px;
}

.registration-summary-card h2 {
    color: var(--blue-active, #2563eb);
    font-size: 28px;
    font-weight: 800;
}

.registration-panel {
    background: #ffffff;
    border: 1px solid var(--border-light, #e2e8f0);
    border-radius: 16px;
    padding: 26px;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.02);
}

.registration-panel-heading {
    margin-bottom: 22px;
}

.registration-panel-heading h2 {
    color: var(--text-main, #0f172a);
    font-size: 19px;
    font-weight: 800;
    margin-bottom: 5px;
}

.registration-panel-heading p {
    color: var(--text-muted, #64748b);
    font-size: 13px;
    line-height: 1.6;
}

.registration-alert {
    padding: 15px 18px;
    border-radius: 10px;
    margin-bottom: 22px;
    font-size: 13.5px;
    line-height: 1.6;
}

.registration-alert-error {
    background: #fff1f2;
    border: 1px solid #fecdd3;
    color: #be123c;
}

.registration-class-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.registration-class-card {
    min-width: 0;
    background: #ffffff;
    border: 1px solid #dce6f2;
    border-radius: 14px;
    padding: 21px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.registration-class-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 5px 18px rgba(37, 99, 235, 0.07);
}

.registration-class-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 17px;
}

.registration-subject-code {
    display: inline-block;
    padding: 5px 10px;
    margin-bottom: 9px;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 800;
}

.registration-class-card h3 {
    color: #1e293b;
    font-size: 16px;
    line-height: 1.5;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.registration-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    padding: 6px 10px;
    border-radius: 99px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}

.registration-status-open {
    background: #e3f8ed;
    color: #047857;
}

.registration-status-closed {
    background: #fff0e5;
    color: #c2410c;
}

.registration-status-registered {
    background: #dcfce7;
    color: #15803d;
}

.registration-class-details {
    border-top: 1px solid #edf2f7;
    padding-top: 15px;
}

.registration-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 12px;
    font-size: 13px;
}

.registration-detail-row span {
    color: var(--text-muted, #64748b);
    flex-shrink: 0;
}

.registration-detail-row strong {
    color: #1e293b;
    text-align: right;
    overflow-wrap: anywhere;
}

.registration-capacity {
    margin-top: 17px;
}

.registration-capacity-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 9px;
    font-size: 12px;
    color: var(--text-muted, #64748b);
}

.registration-capacity-label strong {
    color: #334155;
    font-size: 12px;
}

.registration-progress {
    height: 8px;
    background: #e8edf5;
    border-radius: 99px;
    overflow: hidden;
}

.registration-progress-bar {
    height: 100%;
    background: #3b82f6;
    border-radius: 99px;
    transition: width 0.3s ease;
}

.registration-progress-bar.full {
    background: #ef4444;
}

.registration-card-footer {
    margin-top: 20px;
}

.registration-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 42px;
    padding: 11px 15px;
    border: none;
    border-radius: 9px;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 800;
    text-align: center;
    text-decoration: none;
    transition: background 0.2s, transform 0.2s;
}

.registration-button-primary {
    background: var(--blue-active, #2563eb);
    color: #ffffff;
    cursor: pointer;
}

.registration-button-primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.registration-button-registered {
    background: #e3f8ed;
    color: #047857;
    cursor: default;
}

.registration-button-disabled {
    background: #edf1f6;
    color: #8792a2;
    cursor: not-allowed;
}

.registration-empty-state {
    text-align: center;
    padding: 48px 20px;
    color: var(--text-muted, #64748b);
    line-height: 1.8;
}

.registration-empty-state h3 {
    color: #334155;
    font-size: 17px;
    margin-bottom: 8px;
}

.registration-footer-note {
    margin-top: 22px;
    color: #64748b;
    font-size: 12px;
    line-height: 1.7;
}

@media (max-width: 1200px) {
    .registration-class-list {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 760px) {
    .sh-main-content {
        padding: 22px 16px;
    }

    .sh-top-header {
        flex-direction: column;
        gap: 18px;
    }

    .registration-summary {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .registration-panel {
        padding: 18px;
    }

    .registration-class-card {
        padding: 17px;
    }
}

@media (max-width: 420px) {
    .registration-class-top {
        flex-direction: column;
    }
}
</style>

<div class="registration-page">

    <!-- THÔNG BÁO LỖI -->
    <?php if ($loi !== ''): ?>
        <div class="registration-alert registration-alert-error">
            <?= hienThi($loi) ?>
        </div>
    <?php endif; ?>

    <!-- THỐNG KÊ -->
    <section class="registration-summary">

        <article class="registration-summary-card">
            <p>Tổng số lớp học phần</p>
            <h2><?= $tongSoLop ?></h2>
        </article>

        <article class="registration-summary-card">
            <p>Lớp đang mở và còn chỗ</p>
            <h2><?= $soLopMo ?></h2>
        </article>

        <article class="registration-summary-card">
            <p>Lớp đã đăng ký</p>
            <h2><?= $soLopDaDangKy ?></h2>
        </article>

    </section>

    <!-- DANH SÁCH LỚP -->
    <section class="registration-panel">

        <div class="registration-panel-heading">
            <h2>Danh sách lớp học phần</h2>
            <p>
                Xem thông tin học phần, giảng viên, học kỳ và sĩ số
                trước khi đăng ký.
            </p>
        </div>

        <?php if (empty($danhSachLop)): ?>

            <div class="registration-empty-state">
                <h3>Chưa có lớp học phần</h3>
                <p>
                    Hệ thống hiện chưa có dữ liệu lớp học phần
                    hoặc chưa thể tải dữ liệu.
                </p>
            </div>

        <?php else: ?>

            <div class="registration-class-list">

                <?php foreach ($danhSachLop as $lop): ?>

                    <?php
                    $idLop = (int) $lop['id'];

                    $siSoHienTai = (int) ($lop['si_so_hien_tai'] ?? 0);
                    $siSoToiDa = (int) ($lop['si_so_toi_da'] ?? 0);

                    $tiLe = $siSoToiDa > 0
                        ? min(100, ($siSoHienTai / $siSoToiDa) * 100)
                        : 0;

                    $daDangKy = in_array(
                        $idLop,
                        $cacLopDaDangKy,
                        true
                    );

                    $dangMo = ($lop['trang_thai'] ?? '') === 'mo';

                    $conCho = $siSoToiDa > 0
                        && $siSoHienTai < $siSoToiDa;

                    $coTheDangKy = $dangMo && $conCho && !$daDangKy;
                    ?>

                    <article class="registration-class-card">

                        <div class="registration-class-top">

                            <div>
                                <span class="registration-subject-code">
                                    <?= hienThi($lop['ma_hoc_phan']) ?>
                                </span>

                                <h3>
                                    <?= hienThi($lop['ten_hoc_phan']) ?>
                                </h3>
                            </div>

                            <?php if ($daDangKy): ?>

                                <span class="registration-status registration-status-registered">
                                    Đã đăng ký
                                </span>

                            <?php elseif ($coTheDangKy): ?>

                                <span class="registration-status registration-status-open">
                                    Đang mở
                                </span>

                            <?php else: ?>

                                <span class="registration-status registration-status-closed">
                                    <?php
                                    if (!$dangMo) {
                                        echo 'Đã khóa';
                                    } elseif (!$conCho) {
                                        echo 'Đã đủ sĩ số';
                                    } else {
                                        echo 'Không khả dụng';
                                    }
                                    ?>
                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="registration-class-details">

                            <div class="registration-detail-row">
                                <span>Mã lớp</span>
                                <strong><?= hienThi($lop['ma_lop']) ?></strong>
                            </div>

                            <div class="registration-detail-row">
                                <span>Số tín chỉ</span>
                                <strong><?= hienThi($lop['so_tin_chi']) ?></strong>
                            </div>

                            <div class="registration-detail-row">
                                <span>Học kỳ</span>
                                <strong><?= hienThi($lop['ten_hoc_ky']) ?></strong>
                            </div>

                            <div class="registration-detail-row">
                                <span>Giảng viên</span>
                                <strong>
                                    <?= hienThi(
                                        $lop['ten_giang_vien'] ?: 'Chưa phân công'
                                    ) ?>
                                </strong>
                            </div>

                            <div class="registration-capacity">

                                <div class="registration-capacity-label">
                                    <span>Sĩ số lớp</span>
                                    <strong>
                                        <?= $siSoHienTai ?> / <?= $siSoToiDa ?>
                                    </strong>
                                </div>

                                <div class="registration-progress">
                                    <div
                                        class="registration-progress-bar <?= $conCho ? '' : 'full' ?>"
                                        style="width: <?= $tiLe ?>%;"
                                    ></div>
                                </div>

                            </div>

                        </div>

                        <div class="registration-card-footer">

                            <?php if ($daDangKy): ?>

                                <button
                                    type="button"
                                    class="registration-button registration-button-registered"
                                    disabled
                                >
                                    Bạn đã đăng ký lớp này
                                </button>

                            <?php elseif (!$dangMo): ?>

                                <button
                                    type="button"
                                    class="registration-button registration-button-disabled"
                                    disabled
                                >
                                    Lớp học phần đã khóa
                                </button>

                            <?php elseif (!$conCho): ?>

                                <button
                                    type="button"
                                    class="registration-button registration-button-disabled"
                                    disabled
                                >
                                    Lớp đã đủ sĩ số
                                </button>

                            <?php else: ?>

                                <a
                                    class="registration-button registration-button-primary"
                                    href="dang_ky_hoc.php?lop_hoc_phan_id=<?= $idLop ?>"
                                >
                                    + Đăng ký học phần
                                </a>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <p class="registration-footer-note">
            Lưu ý: Hãy kiểm tra mã lớp, học kỳ và sĩ số trước khi đăng ký.
            Hệ thống cần kiểm tra lại điều kiện đăng ký ở phía máy chủ
            khi bạn xác nhận đăng ký học phần.
        </p>

    </section>

</div>

<?php
// Kết thúc phần nội dung riêng của trang
?>

</main>
</div>

<script src="/studenthub/assets/js/main.js"></script>
</body>
</html>
