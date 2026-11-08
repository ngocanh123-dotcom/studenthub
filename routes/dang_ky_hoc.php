<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Phân tích lịch học theo thứ và tiết.
 *
 * Ví dụ:
 * Thứ 3 và Thứ 5, tiết 1-3
 *
 * Quy ước:
 * Thứ 2 = 1
 * Thứ 3 = 2
 * ...
 * Thứ 7 = 6
 * Chủ nhật = 7
 */
function phanTichLichHoc(string $lichHoc): ?array
{
    preg_match_all('/Thứ\s*([2-7])/iu', $lichHoc, $matches);

    $cacThu = [];

    foreach ($matches[1] as $thu) {
        $cacThu[] = (int) $thu - 1;
    }

    if (preg_match('/Chủ\s*nhật/iu', $lichHoc)) {
        $cacThu[] = 7;
    }

    if (empty($cacThu)) {
        return null;
    }

    if (
        !preg_match(
            '/tiết\s*(\d+)\s*(?:-|–|đến)\s*(?:tiết\s*)?(\d+)/iu',
            $lichHoc,
            $tiet
        )
    ) {
        return null;
    }

    $batDau = (int) $tiet[1];
    $ketThuc = (int) $tiet[2];

    if (
        $batDau < 1
        || $ketThuc < $batDau
        || $ketThuc > 12
    ) {
        return null;
    }

    return [
        'thu' => array_values(array_unique($cacThu)),
        'bat_dau' => $batDau,
        'ket_thuc' => $ketThuc
    ];
}

/**
 * Kiểm tra hai lịch học có trùng thứ và tiết hay không.
 */
function kiemTraTrungLich(string $lichA, string $lichB): bool
{
    $a = phanTichLichHoc($lichA);
    $b = phanTichLichHoc($lichB);

    if ($a === null || $b === null) {
        return false;
    }

    $trungThu = count(array_intersect(
        $a['thu'],
        $b['thu']
    )) > 0;

    $trungTiet = (
        $a['bat_dau'] <= $b['ket_thuc']
        && $b['bat_dau'] <= $a['ket_thuc']
    );

    return $trungThu && $trungTiet;
}

/**
 * Xác định sinh viên đăng nhập.
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

if (
    $sinhVienId <= 0
    || !in_array($vaiTro, ['sinh_vien', 'sinhvien'], true)
) {
    header('Location: /studenthub/public/index.php');
    exit;
}

/**
 * Chỉ cho phép gửi biểu mẫu bằng POST.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Phương thức không hợp lệ. Hãy gửi biểu mẫu đăng ký.');
}

/**
 * Kiểm tra CSRF.
 */
$csrfSession = (string) ($_SESSION['csrf_token'] ?? '');
$csrfPost = (string) ($_POST['csrf_token'] ?? '');

if (
    $csrfSession === ''
    || $csrfPost === ''
    || !hash_equals($csrfSession, $csrfPost)
) {
    http_response_code(403);
    exit('Yêu cầu không hợp lệ do mã bảo mật CSRF không chính xác.');
}

/**
 * Chỉ nhận mã lớp học phần từ POST.
 */
$lopId = filter_input(
    INPUT_POST,
    'lop_hoc_phan_id',
    FILTER_VALIDATE_INT
);

$thongBao = '';
$loaiThongBao = 'error';

if (!$lopId || $lopId <= 0) {
    $thongBao = 'Mã lớp học phần không hợp lệ.';
} else {
    $pdo = null;

    try {
        /**
         * Tìm file kết nối database.
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
         * Lấy kết nối PDO hiện có.
         */
        $pdo = $pdo ?? $conn ?? $db ?? null;

        if (!$pdo instanceof PDO && class_exists('Database')) {
            $database = new Database();

            if (method_exists($database, 'connect')) {
                $pdo = $database->connect();
            }
        }

        /**
         * Kết nối dự phòng nếu chưa có PDO.
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

        $pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        /**
         * Bắt đầu giao dịch.
         */
        $pdo->beginTransaction();

        /**
         * Lấy thông tin lớp học phần.
         */
        $stmtLop = $pdo->prepare("
            SELECT
                lhp.id,
                lhp.ma_lop,
                lhp.hoc_phan_id,
                lhp.hoc_ky_id,
                lhp.trang_thai,
                lhp.si_so_toi_da,
                lhp.lich_hoc,
                lhp.thoi_gian_mo_rieng,
                lhp.thoi_gian_dong_rieng,

                hk.trang_thai_dang_ky,
                hk.thoi_gian_mo_dk,
                hk.thoi_gian_dong_dk

            FROM lop_hoc_phan AS lhp

            INNER JOIN hoc_ky AS hk
                ON hk.id = lhp.hoc_ky_id

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

        /**
         * Kiểm tra sinh viên đã đăng ký lớp này chưa.
         */
        $stmtCheckTrung = $pdo->prepare("
            SELECT COUNT(*)

            FROM dang_ky_hoc

            WHERE sinh_vien_id = :sv_id
              AND lop_hoc_phan_id = :lhp_id
              AND trang_thai = 'dang_ky'
        ");

        $stmtCheckTrung->execute([
            'sv_id' => $sinhVienId,
            'lhp_id' => $lopId
        ]);

        $daDangKyLopNay = (int) $stmtCheckTrung->fetchColumn();

        /**
         * Kiểm tra đăng ký trùng học phần
         * trong cùng một học kỳ.
         */
        $stmtTrungHocPhan = $pdo->prepare("
            SELECT
                dk.id,
                lhp.ma_lop

            FROM dang_ky_hoc AS dk

            INNER JOIN lop_hoc_phan AS lhp
                ON lhp.id = dk.lop_hoc_phan_id

            WHERE dk.sinh_vien_id = :sv_id
              AND lhp.hoc_phan_id = :hoc_phan_id
              AND lhp.hoc_ky_id = :hoc_ky_id
              AND dk.trang_thai = 'dang_ky'
              AND dk.lop_hoc_phan_id <> :lop_id

            LIMIT 1
        ");

        $stmtTrungHocPhan->execute([
            'sv_id' => $sinhVienId,
            'hoc_phan_id' => $lop['hoc_phan_id'],
            'hoc_ky_id' => $lop['hoc_ky_id'],
            'lop_id' => $lopId
        ]);

        $lopTrungHocPhan = $stmtTrungHocPhan->fetch();

        /**
         * Kiểm tra lịch học của lớp mới.
         */
        $lichHocMoi = trim((string) ($lop['lich_hoc'] ?? ''));

        $lichMoiHopLe = (
            phanTichLichHoc($lichHocMoi) !== null
        );

        $lopTrungLich = null;
        $lopLichChuaRo = null;

        /**
         * Lấy các lớp sinh viên đã đăng ký
         * trong cùng học kỳ để kiểm tra trùng lịch.
         */
        $stmtTrungLich = $pdo->prepare("
            SELECT
                lhp.ma_lop,
                lhp.lich_hoc

            FROM dang_ky_hoc AS dk

            INNER JOIN lop_hoc_phan AS lhp
                ON lhp.id = dk.lop_hoc_phan_id

            WHERE dk.sinh_vien_id = :sv_id
              AND lhp.hoc_ky_id = :hoc_ky_id
              AND dk.trang_thai = 'dang_ky'
              AND dk.lop_hoc_phan_id <> :lop_id
        ");

        $stmtTrungLich->execute([
            'sv_id' => $sinhVienId,
            'hoc_ky_id' => $lop['hoc_ky_id'],
            'lop_id' => $lopId
        ]);

        $cacLopDaDangKy = $stmtTrungLich->fetchAll();

        if (!$lichMoiHopLe) {
            $lopLichChuaRo = [
                'ma_lop' => $lop['ma_lop']
            ];
        } else {
            foreach ($cacLopDaDangKy as $lopDaDangKy) {
                $lichCu = trim(
                    (string) ($lopDaDangKy['lich_hoc'] ?? '')
                );

                if (phanTichLichHoc($lichCu) === null) {
                    $lopLichChuaRo = $lopDaDangKy;
                    break;
                }

                if (kiemTraTrungLich($lichHocMoi, $lichCu)) {
                    $lopTrungLich = $lopDaDangKy;
                    break;
                }
            }
        }

        /**
         * Đếm sĩ số hiện tại từ database.
         */
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

        /**
         * Kiểm tra thời gian mở đăng ký.
         *
         * Nếu thời gian riêng của lớp là NULL,
         * lấy mốc thời gian tương ứng từ học kỳ.
         */
        $now = new DateTimeImmutable();

        $moDangKy = !empty($lop['thoi_gian_mo_rieng'])
            ? new DateTimeImmutable($lop['thoi_gian_mo_rieng'])
            : (
                !empty($lop['thoi_gian_mo_dk'])
                    ? new DateTimeImmutable($lop['thoi_gian_mo_dk'])
                    : null
            );

        $dongDangKy = !empty($lop['thoi_gian_dong_rieng'])
            ? new DateTimeImmutable($lop['thoi_gian_dong_rieng'])
            : (
                !empty($lop['thoi_gian_dong_dk'])
                    ? new DateTimeImmutable($lop['thoi_gian_dong_dk'])
                    : null
            );

        /**
         * Nếu lớp không có bất kỳ mốc thời gian riêng nào,
         * phải kiểm tra trạng thái mở đăng ký của học kỳ.
         *
         * Nếu có ít nhất một mốc riêng thì dùng thời gian
         * riêng và mốc còn thiếu sẽ kế thừa từ học kỳ.
         */
        $coThoiGianRieng = (
            !empty($lop['thoi_gian_mo_rieng'])
            || !empty($lop['thoi_gian_dong_rieng'])
        );

        $trongHanDangKy = true;
        $lyDoHetHan = '';

        if (!$coThoiGianRieng) {
            if ((int) $lop['trang_thai_dang_ky'] !== 1) {
                $trongHanDangKy = false;

                $lyDoHetHan = 'Cổng đăng ký của học kỳ hiện đang đóng.';
            }
        }

        if ($trongHanDangKy) {
            if ($moDangKy && $now < $moDangKy) {
                $trongHanDangKy = false;

                $lyDoHetHan = 'Chưa đến thời gian mở đăng ký lớp học phần này.';
            } elseif ($dongDangKy && $now > $dongDangKy) {
                $trongHanDangKy = false;

                $lyDoHetHan = 'Đã hết thời gian đăng ký lớp học phần này.';
            }
        }

        /**
         * Kiểm tra các điều kiện đăng ký.
         */
        if ($daDangKyLopNay > 0) {
            $thongBao = 'Bạn đã đăng ký lớp học phần này rồi.';

        } elseif ($chuaDatTienQuyet) {
            $thongBao = 'Bạn cần đăng ký môn học tiên quyết '
                . ($lop['ten_hoc_phan_tien_quyet'] ?? '')
                . ' trước khi đăng ký học phần này.';

        } elseif ($lopTrungHocPhan) {
            $thongBao = 'Bạn đã đăng ký học phần này ở lớp '
                . $lopTrungHocPhan['ma_lop']
                . '. Bạn không thể đăng ký thêm lớp khác của cùng học phần trong học kỳ này.';

        } elseif (!$lichMoiHopLe) {
            $thongBao = 'Lớp học phần này chưa có lịch học hợp lệ nên chưa thể kiểm tra trùng lịch. Vui lòng liên hệ phòng đào tạo để cập nhật lịch.';

        } elseif ($lopLichChuaRo) {
            $thongBao = 'Chưa thể xác nhận lịch lớp này không bị trùng vì lớp đã đăng ký '
                . $lopLichChuaRo['ma_lop']
                . ' chưa có lịch học hợp lệ. Vui lòng liên hệ phòng đào tạo để cập nhật lịch.';

        } elseif ($lopTrungLich) {
            $thongBao = 'Lịch học bị trùng với lớp '
                . $lopTrungLich['ma_lop']
                . ' ('
                . $lopTrungLich['lich_hoc']
                . '). Vui lòng chọn lớp có lịch khác.';

        } elseif (
            strtolower(trim((string) $lop['trang_thai'])) !== 'mo'
        ) {
            $thongBao = 'Lớp học phần hiện không mở đăng ký.';

        } elseif (!$trongHanDangKy) {
            $thongBao = $lyDoHetHan;

        } elseif (
            $siSoToiDa > 0
            && $siSoHienTai >= $siSoToiDa
        ) {
            $thongBao = 'Lớp học phần đã đủ sĩ số.';

        } else {
            /**
             * Kiểm tra bản ghi đăng ký cũ.
             */
            $stmtDangKyCu = $pdo->prepare("
                SELECT id

                FROM dang_ky_hoc

                WHERE sinh_vien_id = :sv_id
                  AND lop_hoc_phan_id = :lhp_id

                ORDER BY id DESC

                LIMIT 1
            ");

            $stmtDangKyCu->execute([
                'sv_id' => $sinhVienId,
                'lhp_id' => $lopId
            ]);

            $banGhiCu = $stmtDangKyCu->fetch();

            if ($banGhiCu) {
                /**
                 * Khôi phục bản ghi nếu từng hủy lớp.
                 */
                $stmt = $pdo->prepare("
                    UPDATE dang_ky_hoc

                    SET
                        trang_thai = 'dang_ky',
                        ngay_dang_ky = NOW(),
                        ngay_huy = NULL

                    WHERE id = :id
                      AND sinh_vien_id = :sv_id
                      AND lop_hoc_phan_id = :lop_id
                ");

                $stmt->execute([
                    'id' => $banGhiCu['id'],
                    'sv_id' => $sinhVienId,
                    'lop_id' => $lopId
                ]);

            } else {
                /**
                 * Tạo bản ghi đăng ký mới.
                 */
                $stmt = $pdo->prepare("
                    INSERT INTO dang_ky_hoc (
                        lop_hoc_phan_id,
                        sinh_vien_id,
                        trang_thai,
                        ngay_dang_ky
                    )

                    VALUES (
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

            $thongBao = 'Đăng ký lớp học phần thành công!';
            $loaiThongBao = 'success';
        }

        /**
         * Hoàn tất giao dịch.
         */
        $pdo->commit();

    } catch (Throwable $e) {
        if (
            $pdo instanceof PDO
            && $pdo->inTransaction()
        ) {
            $pdo->rollBack();
        }

        error_log(
            'StudentHub đăng ký lớp học phần: '
            . $e->getMessage()
        );

        $thongBao = 'Không thể xử lý đăng ký. Hãy kiểm tra cấu hình và dữ liệu cơ sở dữ liệu.';
        $loaiThongBao = 'error';
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

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

        a:hover {
            opacity: .9;
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
                ? 'Đăng ký lớp học phần'
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
                href="/studenthub/public/index.php?route=trang_dang_ky_hoc.php"
            >
                Quay lại chọn lớp học phần
            </a>

            <a
                class="secondary"
                href="/studenthub/public/index.php?route=lich_su_dang_ky.php"
            >
                Xem lịch sử đăng ký
            </a>

        </div>

    </main>

</body>
</html>