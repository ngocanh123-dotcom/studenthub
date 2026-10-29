<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * [TC 4] Kiểm tra phiên đăng nhập ($_SESSION['user_id'] và $_SESSION['vai_tro'] === 'sinh_vien').
 * Nếu chưa đăng nhập hoặc không phải sinh viên, lập tức dừng xử lý và chuyển hướng về trang login.
 */
$sinhVienId = (int) (
    $_SESSION['user_id']
    ?? $_SESSION['nguoi_dung_id']
    ?? 0
);

$vaiTro = strtolower(trim((string) (
    $_SESSION['vai_tro']
    ?? $_SESSION['role']
    ?? ''
)));

if ($sinhVienId <= 0 || !in_array($vaiTro, ['sinh_vien', 'sinhvien'], true)) {
    header('Location: /studenthub/public/index.php');
    exit;
}

/**
 * [TC 8] Bảo mật CSRF Token: Kiểm tra mã CSRF trên mọi form gửi phương thức POST.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$_POST['csrf_token'])) {
        die("Yêu cầu không hợp lệ do mã bảo mật CSRF không chính xác.");
    }
}

/**
 * [TC 5] Kiểm soát dữ liệu: Sĩ số tối đa (si_so_toi_da) và số chỗ còn lại BẮT BUỘC phải được tính toán
 * trực tiếp từ CSDL. Tuyệt đối KHÔNG nhận bất kỳ tham số sĩ số nào từ $_GET hoặc $_POST.
 * Chỉ nhận duy nhất tham số định danh lớp học phần (lop_hoc_phan_id).
 */
$lopId = filter_input(INPUT_POST, 'lop_hoc_phan_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'lop_hoc_phan_id', FILTER_VALIDATE_INT);
$thongBao = '';
$loaiThongBao = 'error';

if (!$lopId || $lopId <= 0) {
    $thongBao = 'Mã lớp học phần không hợp lệ.';
} else {
    $pdo = null;

    try {
        // Tìm file kết nối database của dự án.
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

        // Lấy kết nối PDO hiện có.
        $pdo = $pdo ?? $conn ?? $db ?? null;

        if (!$pdo instanceof PDO && class_exists('Database')) {
            $database = new Database();

            if (method_exists($database, 'connect')) {
                $pdo = $database->connect();
            }
        }

        // Dự phòng nếu dự án chưa tạo kết nối PDO.
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

        $pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        // Bắt đầu giao dịch bảo vệ toàn vẹn dữ liệu
        $pdo->beginTransaction();

        // [TC 1 & TC 5]: Lấy thông tin lớp học phần trực tiếp từ CSDL (khóa dòng FOR UPDATE)
        $stmtLop = $pdo->prepare("
            SELECT lhp.id, lhp.ma_lop, lhp.hoc_phan_id, lhp.hoc_ky_id, lhp.trang_thai, 
                   lhp.si_so_toi_da, lhp.thoi_gian_mo_rieng, lhp.thoi_gian_dong_rieng,
                   hk.trang_thai_dang_ky, hk.thoi_gian_mo_dk, hk.thoi_gian_dong_dk
            FROM lop_hoc_phan lhp
            INNER JOIN hoc_ky hk ON hk.id = lhp.hoc_ky_id
            WHERE lhp.id = :lop_id
            LIMIT 1
            FOR UPDATE
        ");

        $stmtLop->execute([
            'lop_id' => $lopId
        ]);

        $lop = $stmtLop->fetch();

        if (!$lop) {
            throw new RuntimeException(
                'Không tìm thấy lớp học phần này.'
            );
        }

        // [TC 2]: Kiểm tra trùng lớp bằng câu truy vấn nghiệp vụ chuẩn:
        // SELECT COUNT(*) FROM dang_ky_hoc WHERE sinh_vien_id = :sv_id AND lop_hoc_phan_id = :lhp_id AND trang_thai = 'dang_ky'
        $stmtCheckTrung = $pdo->prepare("
            SELECT COUNT(*)
            FROM dang_ky_hoc
            WHERE sinh_vien_id = :sv_id
              AND lop_hoc_phan_id = :lhp_id
              AND trang_thai = 'dang_ky'
        ");

        $stmtCheckTrung->execute([
            'sv_id'  => $sinhVienId,
            'lhp_id' => $lopId
        ]);

        $daDangKyLopNay = (int) $stmtCheckTrung->fetchColumn();

        // Kiểm tra sinh viên đã đăng ký mã học phần này ở một lớp khác hay chưa
        $stmtTrungHocPhan = $pdo->prepare("
            SELECT dk.id, lhp.ma_lop
            FROM dang_ky_hoc AS dk
            INNER JOIN lop_hoc_phan AS lhp
                ON lhp.id = dk.lop_hoc_phan_id
            WHERE dk.sinh_vien_id = :sv_id
              AND lhp.hoc_phan_id = :hoc_phan_id
              AND dk.trang_thai = 'dang_ky'
              AND dk.lop_hoc_phan_id <> :lop_id
            LIMIT 1
        ");

        $stmtTrungHocPhan->execute([
            'sv_id'       => $sinhVienId,
            'hoc_phan_id' => $lop['hoc_phan_id'],
            'lop_id'      => $lopId
        ]);

        $lopTrungHocPhan = $stmtTrungHocPhan->fetch();

        // [TC 3 & TC 5]: Đếm sĩ số hiện tại của lớp trực tiếp từ CSDL
        $stmtSiSo = $pdo->prepare("
            SELECT COUNT(*)
            FROM dang_ky_hoc
            WHERE lop_hoc_phan_id = :lhp_id
              AND trang_thai = 'dang_ky'
        ");

        $stmtSiSo->execute([
            'lhp_id' => $lopId
        ]);

        $siSoHienTai = (int) $stmtSiSo->fetchColumn();
        $siSoToiDa = (int) $lop['si_so_toi_da'];

        // [TC 1]: Kiểm tra khung thời gian mở đăng ký
        $now = new DateTimeImmutable();
        $coHanRieng = !empty($lop['thoi_gian_mo_rieng']) || !empty($lop['thoi_gian_dong_rieng']);
        $trongHanDangKy = true;
        $lyDoHetHan = '';

        if ($coHanRieng) {
            $moRieng = !empty($lop['thoi_gian_mo_rieng']) ? new DateTimeImmutable($lop['thoi_gian_mo_rieng']) : null;
            $dongRieng = !empty($lop['thoi_gian_dong_rieng']) ? new DateTimeImmutable($lop['thoi_gian_dong_rieng']) : null;
            if (($moRieng && $now < $moRieng) || ($dongRieng && $now > $dongRieng)) {
                $trongHanDangKy = false;
                $lyDoHetHan = 'Hiện không nằm trong thời gian mở đăng ký riêng của lớp học phần này.';
            }
        } else {
            if ((int)$lop['trang_thai_dang_ky'] !== 1) {
                $trongHanDangKy = false;
                $lyDoHetHan = 'Cổng đăng ký của học kỳ hiện đang đóng.';
            } else {
                $moHK = !empty($lop['thoi_gian_mo_dk']) ? new DateTimeImmutable($lop['thoi_gian_mo_dk']) : null;
                $dongHK = !empty($lop['thoi_gian_dong_dk']) ? new DateTimeImmutable($lop['thoi_gian_dong_dk']) : null;
                if (($moHK && $now < $moHK) || ($dongHK && $now > $dongHK)) {
                    $trongHanDangKy = false;
                    $lyDoHetHan = 'Hiện không nằm trong khung thời gian đăng ký của học kỳ.';
                }
            }
        }

        // [TC 2]: Kiểm tra nếu đã đăng ký lớp này rồi
        if ($daDangKyLopNay > 0) {
            $thongBao = 'Bạn đã đăng ký lớp học phần này rồi.';

        } elseif ($lopTrungHocPhan) {
            $thongBao = 'Bạn đã đăng ký học phần này ở lớp '
                . $lopTrungHocPhan['ma_lop']
                . '. Bạn không thể đăng ký thêm lớp khác của cùng học phần.';

        } elseif (strtolower(trim((string) $lop['trang_thai'])) !== 'mo') {
            $thongBao = 'Lớp học phần hiện không mở đăng ký.';

        } elseif (!$trongHanDangKy) {
            $thongBao = $lyDoHetHan;

        // [TC 3]: Kiểm tra đầy sĩ số (si_so_hien_tai >= si_so_toi_da)
        } elseif ($siSoToiDa > 0 && $siSoHienTai >= $siSoToiDa) {
            $thongBao = 'Lớp học phần đã đủ sĩ số.';

        } else {
            // [TC 1]: Khi thỏa mãn toàn bộ điều kiện -> Đăng ký thành công!
            $stmtDangKyCu = $pdo->prepare("
                SELECT id
                FROM dang_ky_hoc
                WHERE sinh_vien_id = :sv_id
                  AND lop_hoc_phan_id = :lhp_id
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmtDangKyCu->execute([
                'sv_id'  => $sinhVienId,
                'lhp_id' => $lopId
            ]);
            $banGhiCu = $stmtDangKyCu->fetch();

            if ($banGhiCu) {
                // Khôi phục bản ghi nếu sinh viên từng hủy lớp này
                $stmt = $pdo->prepare("
                    UPDATE dang_ky_hoc
                    SET trang_thai = 'dang_ky',
                        ngay_dang_ky = NOW(),
                        ngay_huy = NULL
                    WHERE id = :id
                      AND sinh_vien_id = :sv_id
                      AND lop_hoc_phan_id = :lop_id
                ");

                $stmt->execute([
                    'id'     => $banGhiCu['id'],
                    'sv_id'  => $sinhVienId,
                    'lop_id' => $lopId
                ]);

            } else {
<<<<<<< HEAD
                if ($dangKyCu) {

                    $stmt = $pdo->prepare("
                        UPDATE dang_ky_hoc
                        SET trang_thai = 'dang_ky', 
                            ngay_dang_ky = NOW(), 
                            ngay_huy = NULL
                        WHERE id = :id
                          AND sinh_vien_id = :sv_id
                          AND lop_hoc_phan_id = :lop_id
                    ");

                    $stmt->execute([
                        'id' => $dangKyCu['id'],
                        'sv_id' => $sinhVienId,
                        'lop_id' => $lopId
                    ]);

                } else {
                    // Tạo bản ghi đăng ký mới.
                    $stmt = $pdo->prepare("
                        INSERT INTO dang_ky_hoc
                            (lop_hoc_phan_id, sinh_vien_id, trang_thai, ngay_dang_ky)
                        VALUES
                            (:lop_id, :sv_id, 'dang_ky', NOW())
                    ");

                    $stmt->execute([
                        'lop_id' => $lopId,
                        'sv_id' => $sinhVienId
                    ]);
                }

                $thongBao = 'Đăng ký học phần thành công!';
                $loaiThongBao = 'success';
=======
                // Tạo bản ghi đăng ký mới
                $stmt = $pdo->prepare("
                    INSERT INTO dang_ky_hoc
                        (lop_hoc_phan_id, sinh_vien_id, trang_thai, ngay_dang_ky)
                    VALUES
                        (:lop_id, :sv_id, 'dang_ky', NOW())
                ");

                $stmt->execute([
                    'lop_id' => $lopId,
                    'sv_id'  => $sinhVienId
                ]);
>>>>>>> ecf41f69be407f3cf84945b67f61f8da7f50cd82
            }

            $thongBao = 'Đăng ký học phần thành công!';
            $loaiThongBao = 'success';
        }

        $pdo->commit();

    } catch (Throwable $e) {
        if (
            $pdo instanceof PDO
            && $pdo->inTransaction()
        ) {
            $pdo->rollBack();
        }

        error_log(
            'StudentHub đăng ký học phần: '
            . $e->getMessage()
        );

        $thongBao = 'Không thể xử lý đăng ký. Hãy kiểm tra cấu hình và dữ liệu cơ sở dữ liệu.';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kết quả đăng ký | StudentHub</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #26364a;
        }

        .card {
            width: 100%;
            max-width: 500px;
            padding: 36px 28px;
            text-align: center;
            background: white;
            border-radius: 18px;
            box-shadow: 0 10px 35px rgba(20, 60, 110, .10);
        }

        .icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            font-size: 30px;
            background: <?= $loaiThongBao === 'success' ? '#e2f8ed' : '#fff0e8' ?>;
            color: <?= $loaiThongBao === 'success' ? '#15965c' : '#c45b28' ?>;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 24px;
        }

        p {
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 24px;
        }

        a {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 9px;
            text-decoration: none;
            font-weight: 600;
        }

        .primary {
            background: #1769d2;
            color: white;
        }

        .secondary {
            background: #edf3fb;
            color: #1769d2;
        }
    </style>
</head>

<body>
    <main class="card">
        <div class="icon">
            <?= $loaiThongBao === 'success' ? '✓' : '!' ?>
        </div>

        <h1>
            <?= $loaiThongBao === 'success'
                ? 'Đăng ký học phần'
                : 'Chưa thể đăng ký' ?>
        </h1>

        <p>
            <?= htmlspecialchars(
                $thongBao,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <div class="actions">
            <a
                class="primary"
                href="/studenthub/public/trang_dang_ky_hoc.php"
            >
                Quay lại đăng ký học phần
            </a>


         

            <a
                class="secondary"
                href="/studenthub/public/lich_su_dang_ky.php"
            >

                Xem lịch sử đăng ký
            </a>
        </div>
    </main>
</body>
</html>