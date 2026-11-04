
<?php
session_start();

// Kiểm tra đăng nhập Admin
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

// Tự động kiểm tra và bổ sung 2 cột (nếu chưa có) vào bảng hoc_phan (Migration idempotent)
try {
    $pdo->exec("ALTER TABLE hoc_phan 
                ADD COLUMN IF NOT EXISTS dieu_kien_tien_quyet VARCHAR(255) NULL DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS mo_ta TEXT NULL DEFAULT NULL");
} catch (PDOException $e) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM hoc_phan")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('dieu_kien_tien_quyet', $cols, true)) {
            $pdo->exec("ALTER TABLE hoc_phan ADD COLUMN dieu_kien_tien_quyet VARCHAR(255) NULL DEFAULT NULL");
        }
        if (!in_array('mo_ta', $cols, true)) {
            $pdo->exec("ALTER TABLE hoc_phan ADD COLUMN mo_ta TEXT NULL DEFAULT NULL");
        }
    } catch (PDOException $e2) {
        error_log("StudentHub hoc_phan migration check error: " . $e2->getMessage());
    }
}

// Tạo CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$thongBao = '';
$loaiThongBao = 'success';
$loi = '';

$tuKhoa = trim($_GET['q'] ?? '');

$duLieuSua = [
    'id' => '',
    'ma_hoc_phan' => '',
    'ten_hoc_phan' => '',
    'so_tin_chi' => ''
];

// XỬ LÝ THÊM, SỬA, XÓA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfNhan = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfNhan)) {
        $thongBao = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.';
        $loaiThongBao = 'error';
    } else {
        $hanhDong = $_POST['hanh_dong'] ?? '';

        try {
            if ($hanhDong === 'them' || $hanhDong === 'sua') {
                $id = (int) ($_POST['id'] ?? 0);
                $maHocPhan = strtoupper(trim($_POST['ma_hoc_phan'] ?? ''));
                $tenHocPhan = trim($_POST['ten_hoc_phan'] ?? '');
                $soTinChiNhap = trim((string) ($_POST['so_tin_chi'] ?? ''));

                $duLieuSua = [
                    'id' => $id,
                    'ma_hoc_phan' => $maHocPhan,
                    'ten_hoc_phan' => $tenHocPhan,
                    'so_tin_chi' => $soTinChiNhap
                ];

                if (!preg_match('/^[A-Z0-9_-]{2,30}$/', $maHocPhan)) {
                    throw new RuntimeException(
                        'Mã học phần phải có 2–30 ký tự chữ, số, dấu gạch ngang hoặc gạch dưới.'
                    );
                }

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
                    throw new RuntimeException(
                        'Số tín chỉ phải là số nguyên từ 1 đến 10.'
                    );
                }

                $soTinChi = (int) $soTinChiNhap;

                if ($hanhDong === 'sua' && $id <= 0) {
                    throw new RuntimeException('Học phần cần sửa không hợp lệ.');
                }

                // Kiểm tra mã học phần trùng
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

                if ($hanhDong === 'them') {
                    // Đã bổ sung cột so_tin_chi
                    $stmt = $pdo->prepare(
                        "INSERT INTO hoc_phan
                            (ma_hoc_phan, ten_hoc_phan, so_tin_chi)
                         VALUES
                            (:ma, :ten, :so_tin_chi)"
                    );

                    $stmt->execute([
                        ':ma' => $maHocPhan,
                        ':ten' => $tenHocPhan,
                        ':so_tin_chi' => $soTinChi
                    ]);

                    $thongBao = 'Thêm học phần thành công.';
                } else {
                    // Cập nhật cả số tín chỉ
                    $stmt = $pdo->prepare(
                        "UPDATE hoc_phan
                         SET ma_hoc_phan = :ma,
                             ten_hoc_phan = :ten,
                             so_tin_chi = :so_tin_chi
                         WHERE id = :id"
                    );

                    $stmt->execute([
                        ':ma' => $maHocPhan,
                        ':ten' => $tenHocPhan,
                        ':so_tin_chi' => $soTinChi,
                        ':id' => $id
                    ]);

                    $thongBao = 'Cập nhật học phần thành công.';
                }

                $_SESSION['course_notice'] = $thongBao;
                $_SESSION['course_notice_type'] = 'success';

                header('Location: hoc_phan.php');
                exit;
            }

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

// Lấy thông báo sau chuyển hướng
if (!empty($_SESSION['course_notice'])) {
    $thongBao = $_SESSION['course_notice'];
    $loaiThongBao = $_SESSION['course_notice_type'] ?? 'success';

    unset($_SESSION['course_notice'], $_SESSION['course_notice_type']);
}

// Lấy dữ liệu học phần cần sửa
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['sua'])) {
    $idSua = (int) $_GET['sua'];

    if ($idSua > 0) {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, ma_hoc_phan, ten_hoc_phan, so_tin_chi
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

// Lấy danh sách học phần
$danhSach = [];
$tongHocPhan = 0;

try {
    $sql = "SELECT id, ma_hoc_phan, ten_hoc_phan, so_tin_chi
            FROM hoc_phan
            WHERE 1 = 1";

    $thamSo = [];

    if ($tuKhoa !== '') {
        $sql .= " AND (
                    ma_hoc_phan LIKE :q1
                    OR ten_hoc_phan LIKE :q2
                  )";

        $tim = '%' . $tuKhoa . '%';
        $thamSo[':q1'] = $tim;
        $thamSo[':q2'] = $tim;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($thamSo);
    $danhSach = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtTong = $pdo->query("SELECT COUNT(*) FROM hoc_phan");
    $tongHocPhan = (int) $stmtTong->fetchColumn();

} catch (PDOException $e) {
    error_log('StudentHub Course List: ' . $e->getMessage());

    $loi = 'Không thể tải danh sách học phần. Hãy kiểm tra bảng hoc_phan trong CSDL.';
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
        .col-12 { grid-column: 1 / -1; width: 100%; }
        .mt-3 { margin-top: 10px; }
        .form-label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #334155; }
        .form-control { display: block; width: 100%; min-width: 0; padding: 11px 13px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 14px; color: #172033; background: #fff; box-sizing: border-box; }
        .form-control:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
        .text-slate-400 { color: #94a3b8; font-size: 12.5px; display: block; margin-top: 5px; }
        .font-medium { font-weight: 600; }
        .text-slate-700 { color: #334155; }
    </style>
</head>
<body>
<div class="layout">

    <!-- THANH MENU BÊN TRÁI -->
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
                    <strong><?= e($_SESSION['ho_ten'] ?? $_SESSION['username'] ?? 'Quản trị viên') ?></strong>
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
                <div class="error-message"><?= e($loi) ?></div>
            <?php endif; ?>

            <section class="account-summary">
                <div>
                    <span class="summary-label">Tổng số học phần</span>
                    <strong><?= $tongHocPhan ?></strong>
                    <p>Danh sách học phần trong hệ thống StudentHub.</p>
                </div>
                <div class="summary-icon">▤</div>
            </section>

            <section class="section">
                <div class="section-heading">
                    <div>
                        <h2><?= !empty($duLieuSua['id']) ? 'Cập nhật học phần' : 'Thêm học phần mới' ?></h2>
                        <p>Nhập mã học phần, tên học phần và số tín chỉ.</p>
                    </div>
                </div>

                <form method="POST" action="hoc_phan.php" class="account-form">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="hanh_dong" value="<?= !empty($duLieuSua['id']) ? 'sua' : 'them' ?>">
                    <input type="hidden" name="id" value="<?= e($duLieuSua['id']) ?>">

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="ma_hoc_phan">Mã học phần <span>*</span></label>
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
                            <label for="ten_hoc_phan">Tên học phần <span>*</span></label>
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
                            <label for="so_tin_chi">Số tín chỉ <span>*</span></label>
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

                        <!-- 1. Điều kiện tiên quyết (Tùy chọn) -->
                        <div class="col-12 mt-3 form-field" style="grid-column: 1 / -1;">
                            <label class="form-label font-medium text-slate-700">Điều kiện tiên quyết</label>
                            <input type="text" name="dieu_kien_tien_quyet" class="form-control" value="<?= e($duLieuSua['dieu_kien_tien_quyet'] ?? '') ?>" placeholder="Ví dụ: WEB102 - Lập Trình Web Cơ Bản">
                            <small class="text-slate-400">Để trống nếu học phần không có môn tiên quyết bắt buộc.</small>
                        </div>

                        <!-- 2. Mô tả & Mục tiêu học phần (Tùy chọn) -->
                        <div class="col-12 mt-3 form-field" style="grid-column: 1 / -1;">
                            <label class="form-label font-medium text-slate-700">Mô tả &amp; Mục tiêu học phần</label>
                            <textarea name="mo_ta" rows="3" class="form-control" placeholder="Nhập mục tiêu học thuật, kiến thức trọng tâm của học phần..."><?= e($duLieuSua['mo_ta'] ?? '') ?></textarea>
                            <small class="text-slate-400">Để trống nếu học phần chưa có mô tả hoặc đề cương chi tiết.</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <?= !empty($duLieuSua['id']) ? 'Lưu thay đổi' : 'Thêm học phần' ?>
                        </button>

                        <?php if (!empty($duLieuSua['id'])): ?>
                            <a href="hoc_phan.php" class="btn-secondary">Hủy sửa</a>
                        <?php else: ?>
                            <button type="reset" class="btn-secondary">Nhập lại</button>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="section">
                <div class="section-heading">
                    <div>
                        <h2>Danh sách học phần</h2>
                        <p>Tra cứu và quản lý các học phần hiện có.</p>
                    </div>
                </div>

                <form method="GET" action="hoc_phan.php" class="account-filters">
                    <input
                        type="search"
                        name="q"
                        value="<?= e($tuKhoa) ?>"
                        placeholder="Tìm theo mã hoặc tên học phần..."
                    >

                    <button type="submit" class="btn-primary">Tìm kiếm</button>
                    <a href="hoc_phan.php" class="btn-secondary">Làm mới</a>
                </form>

                <div class="account-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mã học phần</th>
                                <th>Tên học phần</th>
                                <th>Số tín chỉ</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($danhSach)): ?>
                            <tr>
                                <td colspan="5" class="empty">
                                    Chưa có học phần hoặc không tìm thấy kết quả.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($danhSach as $hp): ?>
                                <tr>
                                    <td><?= (int) $hp['id'] ?></td>
                                    <td><strong><?= e($hp['ma_hoc_phan']) ?></strong></td>
                                    <td><?= e($hp['ten_hoc_phan']) ?></td>
                                    <td><?= (int) $hp['so_tin_chi'] ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a
                                                class="btn-edit"
                                                href="hoc_phan.php?sua=<?= (int) $hp['id'] ?>"
                                            >Sửa</a>

                                            <form
                                                method="POST"
                                                action="hoc_phan.php"
                                                class="delete-form"
                                                onsubmit="return confirm('Bạn có chắc muốn xóa học phần này?');"
                                            >
                                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="hanh_dong" value="xoa">
                                                <input type="hidden" name="id" value="<?= (int) $hp['id'] ?>">

                                                <button type="submit" class="btn-delete">Xóa</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <footer class="page-footer">
                StudentHub · Hệ thống quản lý học phần
            </footer>

        </div>
    </main>
</div>
</body>
</html>
