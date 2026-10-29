
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Chuyển về trang đăng ký và gửi kết quả xử lý.
 */
function quayLaiDanhSach($ketQua)
{
    header(
        'Location: trang_dang_ky_hoc.php?huy=' . urlencode($ketQua)
    );
    exit;
}

/**
 * Lấy ID sinh viên từ session.
 * Hỗ trợ hai tên session được sử dụng trong StudentHub.
 */
$sinhVienId = (int) (
    $_SESSION['nguoi_dung_id']
    ?? $_SESSION['user_id']
    ?? 0
);

/**
 * Kiểm tra đăng nhập.
 */
if ($sinhVienId <= 0) {
    quayLaiDanhSach('chua_dang_nhap');
}

/**
 * Kiểm tra quyền sinh viên.
 * Chấp nhận các cách viết vai trò thường gặp trong dự án.
 */
$vaiTro = strtolower(trim((string) (
    $_SESSION['role']
    ?? $_SESSION['vai_tro']
    ?? ''
)));

if (!in_array($vaiTro, ['sinhvien', 'sinh_vien'], true)) {
    quayLaiDanhSach('khong_co_quyen');
}

/**
 * Chỉ chấp nhận yêu cầu POST.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    quayLaiDanhSach('yeu_cau_khong_hop_le');
}

/**
 * Kiểm tra CSRF để bảo vệ thao tác hủy đăng ký.
 */
if (
    empty($_SESSION['csrf_token'])
    || !isset($_POST['csrf_token'])
    || !hash_equals(
        (string) $_SESSION['csrf_token'],
        (string) $_POST['csrf_token']
    )
) {
    quayLaiDanhSach('phien_khong_hop_le');
}

/**
 * Lấy ID lớp học phần từ biểu mẫu.
 */
$lopId = filter_input(
    INPUT_POST,
    'lop_hoc_phan_id',
    FILTER_VALIDATE_INT
);

if (!$lopId || $lopId <= 0) {
    quayLaiDanhSach('lop_khong_hop_le');
}

try {
    /**
     * Tìm file kết nối cơ sở dữ liệu của dự án.
     */
    $duongDanKetNoi = [
        __DIR__ . '/../config/database.php',
        __DIR__ . '/../config/Database.php',
        __DIR__ . '/../app/Config/database.php',
        __DIR__ . '/../config/db.php',
    ];

    foreach ($duongDanKetNoi as $file) {
        if (is_file($file)) {
            require_once $file;
            break;
        }
    }

    /**
     * Lấy kết nối PDO từ cấu hình hiện có.
     */
    $pdo = $pdo ?? $conn ?? $db ?? null;

    if (!$pdo instanceof PDO && class_exists('Database')) {
        $database = new Database();

        if (method_exists($database, 'connect')) {
            $pdo = $database->connect();
        }
    }

    /**
     * Nếu dự án chưa cung cấp kết nối, thử kết nối trực tiếp.
     */
    if (!$pdo instanceof PDO) {
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
    }

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    /**
     * Chỉ hủy đăng ký của sinh viên đang đăng nhập.
     * Không xóa bản ghi, chỉ đổi trạng thái thành da_huy.
     */
    $stmt = $pdo->prepare("
        UPDATE dang_ky_hoc
        SET trang_thai = 'da_huy'
        WHERE lop_hoc_phan_id = :lop_id
          AND sinh_vien_id = :sinh_vien_id
          AND trang_thai = 'dang_ky'
    ");

    $stmt->execute([
        'lop_id' => $lopId,
        'sinh_vien_id' => $sinhVienId,
    ]);

    if ($stmt->rowCount() > 0) {
        quayLaiDanhSach('thanh_cong');
    }

    quayLaiDanhSach('khong_tim_thay_dang_ky');

} catch (Throwable $e) {
    error_log(
        'StudentHub hủy đăng ký: ' . $e->getMessage()
    );

    quayLaiDanhSach('loi_he_thong');
}
