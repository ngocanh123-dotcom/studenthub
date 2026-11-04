
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Nhận diện sinh viên từ session của StudentHub.
 */
$sinhVienId = (int) (
    $_SESSION['nguoi_dung_id']
    ?? $_SESSION['user_id']
    ?? 0
);

$vaiTro = strtolower(trim((string) (
    $_SESSION['role']
    ?? $_SESSION['vai_tro']
    ?? ''
)));

if ($sinhVienId <= 0) {
    exit('Không tìm thấy phiên đăng nhập. Vui lòng đăng nhập lại.');
}

if (!in_array($vaiTro, ['sinhvien', 'sinh_vien'], true)) {
    exit(
        'Tài khoản hiện tại không có quyền đăng ký học phần. Vai trò: '
        . htmlspecialchars($vaiTro ?: 'chưa xác định', ENT_QUOTES, 'UTF-8')
    );
}

// Nhận mã lớp học phần từ URL.
$lopId = filter_input(INPUT_GET, 'lop_hoc_phan_id', FILTER_VALIDATE_INT);

$thongBao = '';
$loaiThongBao = 'error';

if (!$lopId || $lopId <= 0) {
    $thongBao = 'Mã lớp học phần không hợp lệ.';
} else {

    $pdo = null;

    try {

        // Tìm và nạp file kết nối database.
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

        // Lấy kết nối PDO đã được tạo.
        $pdo = $pdo ?? $conn ?? $db ?? null;

        // Nếu dự án sử dụng class Database.
        if (!$pdo instanceof PDO && class_exists('Database')) {
            $database = new Database();

            if (method_exists($database, 'connect')) {
                $pdo = $database->connect();
            }
        }

        // Kết nối dự phòng nếu chưa có kết nối PDO.
        if (!$pdo instanceof PDO) {
            $pdo = new PDO(
                'mysql:host=localhost;port=3306;dbname=studenthub;charset=utf8mb4',
                'root',
                '',
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

        // Bắt đầu giao dịch.
        $pdo->beginTransaction();

        // 1. Kiểm tra lớp học phần.
        $stmtLop = $pdo->prepare("
            SELECT
                id,
                ma_lop,
                hoc_phan_id,
                trang_thai,
                si_so_toi_da,
                thoi_gian_mo_rieng,
                thoi_gian_dong_rieng
            FROM lop_hoc_phan
            WHERE id = :lop_id
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

        // 2. Kiểm tra sinh viên đã đăng ký lớp này chưa.
        $stmtTrung = $pdo->prepare("
            SELECT id, trang_thai
            FROM dang_ky_hoc
            WHERE lop_hoc_phan_id = :lop_id
              AND sinh_vien_id = :sv_id
            ORDER BY id DESC
            LIMIT 1
            FOR UPDATE
        ");

        $stmtTrung->execute([
            'lop_id' => $lopId,
            'sv_id' => $sinhVienId
        ]);

        $dangKyCu = $stmtTrung->fetch();

        // 3. Kiểm tra sinh viên đã đăng ký lớp khác
        // thuộc cùng một học phần hay chưa.
        $stmtTrungHocPhan = $pdo->prepare("
            SELECT
                dk.id,
                lhp.ma_lop
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
            'sv_id' => $sinhVienId,
            'hoc_phan_id' => $lop['hoc_phan_id'],
            'lop_id' => $lopId
        ]);

        $lopTrungHocPhan = $stmtTrungHocPhan->fetch();

        // 4. Kiểm tra thời gian đăng ký riêng của lớp.
        $nowTime = time();

        $moRieng = !empty($lop['thoi_gian_mo_rieng'])
            ? strtotime($lop['thoi_gian_mo_rieng'])
            : null;

        $dongRieng = !empty($lop['thoi_gian_dong_rieng'])
            ? strtotime($lop['thoi_gian_dong_rieng'])
            : null;

        // 5. Kiểm tra các điều kiện đăng ký.
        if ($dangKyCu && $dangKyCu['trang_thai'] === 'dang_ky') {

            $thongBao = 'Bạn đã đăng ký lớp học phần này rồi.';

        } elseif ($lopTrungHocPhan) {

            $thongBao = 'Bạn đã đăng ký học phần này ở lớp '
                . $lopTrungHocPhan['ma_lop']
                . '. Bạn không thể đăng ký thêm lớp khác của cùng học phần.';

        } elseif ($moRieng !== null && $moRieng !== false && $nowTime < $moRieng) {

            $thongBao = 'Lớp học phần chưa đến hạn mở đăng ký (Đã khóa).';

        } elseif ($dongRieng !== null && $dongRieng !== false && $nowTime > $dongRieng) {

            $thongBao = 'Lớp học phần đã hết hạn đăng ký (Đã khóa).';

        } elseif (strtolower(trim((string) $lop['trang_thai'])) !== 'mo') {

            $thongBao = 'Lớp học phần hiện không mở đăng ký.';

        } else {

            // 6. Kiểm tra sĩ số hiện tại.
            $stmtSiSo = $pdo->prepare("
                SELECT COUNT(*)
                FROM dang_ky_hoc
                WHERE lop_hoc_phan_id = :lop_id
                  AND trang_thai = 'dang_ky'
            ");

            $stmtSiSo->execute([
                'lop_id' => $lopId
            ]);

            $siSoHienTai = (int) $stmtSiSo->fetchColumn();
            $siSoToiDa = (int) $lop['si_so_toi_da'];

            if ($siSoToiDa > 0 && $siSoHienTai >= $siSoToiDa) {

                $thongBao = 'Lớp học phần đã đủ sĩ số.';

            } else {

                // 7. Xử lý đăng ký.
                if ($dangKyCu) {

                    // Nếu đã hủy trước đó thì khôi phục bản ghi.
                    $stmt = $pdo->prepare("
                        UPDATE dang_ky_hoc
                        SET trang_thai = 'dang_ky'
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

                    // Nếu chưa từng đăng ký thì chỉ INSERT một lần.
                    $stmt = $pdo->prepare("
                        INSERT INTO dang_ky_hoc
                            (
                                lop_hoc_phan_id,
                                sinh_vien_id,
                                trang_thai,
                                ngay_dang_ky
                            )
                        VALUES
                            (
                                :lop_id,
                                :sv_id,
                                'dang_ky',
                                NOW()
                            )
                    ");

                    $stmt->execute([
                        'lop_id' => $lopId,
                        'sv_id' => $sinhVienId
                    ]);
                }

                $thongBao = 'Đăng ký học phần thành công!';
                $loaiThongBao = 'success';
            }
        }

        // Hoàn tất giao dịch.
        $pdo->commit();

    } catch (Throwable $e) {

        // Nếu xảy ra lỗi thì hoàn tác giao dịch.
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Ghi lỗi chi tiết vào PHP error log.
        error_log(
            'StudentHub đăng ký học phần: '
            . $e->getMessage()
        );

        // Đảm bảo hiển thị đúng trạng thái thất bại.
        $loaiThongBao = 'error';

        $thongBao = 'Không thể xử lý đăng ký. '
            . 'Hãy kiểm tra cấu hình và dữ liệu cơ sở dữ liệu.';
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

            background: <?= $loaiThongBao === 'success'
                ? '#e2f8ed'
                : '#fff0e8' ?>;

            color: <?= $loaiThongBao === 'success'
                ? '#15965c'
                : '#c45b28' ?>;
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

            <a class="primary"
               href="/studenthub/public/trang_dang_ky_hoc.php">
                Quay lại đăng ký học phần
            </a>

            <a class="secondary"
   href="/studenthub/public/lich_su_dang_ky.php">
    Xem lịch sử đăng ký
</a>

        </div>

    </main>

</body>
</html>
