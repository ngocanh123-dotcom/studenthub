<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    exit('Không tìm thấy phiên đăng nhập. Vui lòng đăng nhập lại.');
}

if (($_SESSION['role'] ?? '') !== 'sinhvien') {
    exit('Tài khoản hiện tại không có quyền đăng ký học phần. Vai trò: '
        . htmlspecialchars((string)($_SESSION['role'] ?? 'chưa xác định'), ENT_QUOTES, 'UTF-8'));
}

$sinhVienId = (int) $_SESSION['user_id'];
$lopId = filter_input(INPUT_GET, 'lop_hoc_phan_id', FILTER_VALIDATE_INT);
$thongBao = '';
$loaiThongBao = 'error';

if (!$lopId || $lopId <= 0) {
    $thongBao = 'Mã lớp học phần không hợp lệ.';
} else {
    try {
        // Tìm file kết nối database của dự án.
        $duongDanKetNoi = [
            __DIR__ . '/../config/database.php',
            __DIR__ . '/../config/Database.php',
            __DIR__ . '/../app/Config/database.php',
            __DIR__ . '/../config/db.php',
        ];

        $daNapKetNoi = false;

        foreach ($duongDanKetNoi as $file) {
            if (is_file($file)) {
                require_once $file;
                $daNapKetNoi = true;
                break;
            }
        }

        // Lấy kết nối PDO từ các kiểu thường dùng trong dự án.
        $pdo = $pdo ?? $conn ?? $db ?? null;

        if (!$pdo instanceof PDO && class_exists('Database')) {
            $database = new Database();

            if (method_exists($database, 'connect')) {
                $pdo = $database->connect();
            }
        }

        if (!$pdo instanceof PDO) {
            throw new RuntimeException(
                'Chưa tìm thấy kết nối PDO. Hãy kiểm tra file config/database.php hoặc lớp Database của dự án.'
            );
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Kiểm tra lớp học phần.
        $sqlLop = "
            SELECT
                id,
                trang_thai,
                si_so_toi_da
            FROM lop_hoc_phan
            WHERE id = :lop_id
            LIMIT 1
        ";

        $stmtLop = $pdo->prepare($sqlLop);
        $stmtLop->execute(['lop_id' => $lopId]);
        $lop = $stmtLop->fetch(PDO::FETCH_ASSOC);

        if (!$lop) {
            throw new RuntimeException('Không tìm thấy lớp học phần này.');
        }

        // Kiểm tra trạng thái đăng ký hiện tại.
        $stmtTrung = $pdo->prepare("
            SELECT id, trang_thai
            FROM dang_ky_hoc
            WHERE lop_hoc_phan_id = :lop_id
              AND sinh_vien_id = :sv_id
            LIMIT 1
        ");

        $stmtTrung->execute([
            'lop_id' => $lopId,
            'sv_id' => $sinhVienId,
        ]);

        $dangKyCu = $stmtTrung->fetch(PDO::FETCH_ASSOC);

        if ($dangKyCu && $dangKyCu['trang_thai'] === 'dang_ky') {
            $thongBao = 'M đã đăng ký lớp học phần này rồi.';
        } elseif (strtolower((string)$lop['trang_thai']) !== 'mo') {
            $thongBao = 'Lớp học phần hiện không mở đăng ký.';
        } else {
            // Đếm số sinh viên đã đăng ký còn hiệu lực.
            $stmtSiSo = $pdo->prepare("
                SELECT COUNT(*)
                FROM dang_ky_hoc
                WHERE lop_hoc_phan_id = :lop_id
                  AND trang_thai = 'dang_ky'
            ");

            $stmtSiSo->execute(['lop_id' => $lopId]);
            $siSoHienTai = (int)$stmtSiSo->fetchColumn();
            $siSoToiDa = (int)$lop['si_so_toi_da'];

            if ($siSoToiDa > 0 && $siSoHienTai >= $siSoToiDa) {
                $thongBao = 'Lớp học phần đã đủ sĩ số.';
            } else {
                if ($dangKyCu) {
                    // Khôi phục đăng ký đã hủy.
                    $stmt = $pdo->prepare("
                        UPDATE dang_ky_hoc
                        SET trang_thai = 'dang_ky'
                        WHERE id = :id
                    ");
                    $stmt->execute(['id' => $dangKyCu['id']]);
                } else {
                    // Tạo đăng ký mới.
                    $stmt = $pdo->prepare("
                        INSERT INTO dang_ky_hoc
                            (lop_hoc_phan_id, sinh_vien_id, trang_thai)
                        VALUES
                            (:lop_id, :sv_id, 'dang_ky')
                    ");

                    $stmt->execute([
                        'lop_id' => $lopId,
                        'sv_id' => $sinhVienId,
                    ]);
                }

                $thongBao = 'Đăng ký học phần thành công!';
                $loaiThongBao = 'success';
            }
        }
    } catch (Throwable $e) {
        error_log('StudentHub đăng ký học phần: ' . $e->getMessage());
        $thongBao = 'Không thể xử lý đăng ký. Chi tiết: ' . $e->getMessage();
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
        * { box-sizing: border-box; }
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
        h1 { margin: 0 0 12px; font-size: 24px; }
        p { line-height: 1.7; overflow-wrap: anywhere; }
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
        .primary { background: #1769d2; color: white; }
        .secondary { background: #edf3fb; color: #1769d2; }
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

        <p><?= htmlspecialchars($thongBao, ENT_QUOTES, 'UTF-8') ?></p>

        <div class="actions">
            <a class="primary"
               href="/studenthub/public/danh_sach_lop_hoc_phan.php">
                Quay lại danh sách lớp
            </a>

            <a class="secondary"
               href="/studenthub/app/Views/lichsu/index.php">
                Xem lịch sử đăng ký
            </a>
        </div>
    </main>
</body>
</html>
