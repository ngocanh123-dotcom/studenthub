<?php
/**
 * public/api_lop_hoc_phan.php
 * Endpoint JSON kiểm tra số chỗ còn lại và tìm kiếm lớp học phần theo từ khóa (Mục C đề bài)
 * Kiến trúc: PHP 8.x - PDO Prepared Statements - JSON Response
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $duongDanKetNoi = [
        __DIR__ . '/../config/database.php',
        __DIR__ . '/../config/db.php',
        __DIR__ . '/../app/Config/database.php',
    ];

    foreach ($duongDanKetNoi as $file) {
        if (is_file($file)) {
            require_once $file;
            break;
        }
    }

    $pdo = $pdo ?? $conn ?? $db ?? null;

    if (!$pdo instanceof PDO) {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=studenthub;charset=utf8mb4',
            'root',
            getenv('STUDENTHUB_DB_PASS') ?: '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $tuKhoa = trim((string)($_GET['tu_khoa'] ?? ''));

    // 1. Tra cứu nhanh 1 lớp học phần theo ID
    if ($id) {
        $sql = "
            SELECT
                lhp.id,
                lhp.ma_lop,
                lhp.si_so_toi_da,
                lhp.trang_thai,
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
                ) AS da_dang_ky
            FROM lop_hoc_phan lhp
            INNER JOIN hoc_phan hp ON hp.id = lhp.hoc_phan_id
            LEFT JOIN hoc_ky hk ON hk.id = lhp.hoc_ky_id
            LEFT JOIN nguoi_dung nd ON nd.id = lhp.giang_vien_id
            WHERE lhp.id = :id
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $lop = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lop) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy lớp học phần.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $siSoToiDa = (int)$lop['si_so_toi_da'];
        $daDangKy  = (int)$lop['da_dang_ky'];
        $conLai    = max(0, $siSoToiDa - $daDangKy);

        echo json_encode([
            'success'       => true,
            'id'            => (int)$lop['id'],
            'ma_lop'        => (string)$lop['ma_lop'],
            'ma_hoc_phan'   => (string)$lop['ma_hoc_phan'],
            'ten_hoc_phan'  => (string)$lop['ten_hoc_phan'],
            'so_tin_chi'    => (int)$lop['so_tin_chi'],
            'ten_hoc_ky'    => (string)($lop['ten_hoc_ky'] ?? ''),
            'giang_vien'    => (string)($lop['ten_giang_vien'] ?? ''),
            'si_so_toi_da'  => $siSoToiDa,
            'da_dang_ky'    => $daDangKy,
            'con_lai'       => $conLai,
            'trang_thai'    => (string)$lop['trang_thai'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Tìm kiếm danh sách lớp học phần theo từ khóa (hoặc lấy tất cả nếu rỗng)
    $where  = '1=1';
    $params = [];

    if ($tuKhoa !== '') {
        $where = '(lhp.ma_lop LIKE :kw1 OR hp.ma_hoc_phan LIKE :kw2 OR hp.ten_hoc_phan LIKE :kw3)';
        $like = '%' . $tuKhoa . '%';
        $params[':kw1'] = $like;
        $params[':kw2'] = $like;
        $params[':kw3'] = $like;
    }

    $sql = "
        SELECT
            lhp.id,
            lhp.ma_lop,
            lhp.si_so_toi_da,
            lhp.trang_thai,
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
            ) AS da_dang_ky
        FROM lop_hoc_phan lhp
        INNER JOIN hoc_phan hp ON hp.id = lhp.hoc_phan_id
        LEFT JOIN hoc_ky hk ON hk.id = lhp.hoc_ky_id
        LEFT JOIN nguoi_dung nd ON nd.id = lhp.giang_vien_id
        WHERE $where
        ORDER BY lhp.id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $danhSach = [];
    foreach ($rows as $r) {
        $toiDa = (int)$r['si_so_toi_da'];
        $daDk  = (int)$r['da_dang_ky'];
        $danhSach[] = [
            'id'           => (int)$r['id'],
            'ma_lop'       => (string)$r['ma_lop'],
            'ma_hoc_phan'  => (string)$r['ma_hoc_phan'],
            'ten_hoc_phan' => (string)$r['ten_hoc_phan'],
            'so_tin_chi'   => (int)$r['so_tin_chi'],
            'ten_hoc_ky'   => (string)($r['ten_hoc_ky'] ?? ''),
            'giang_vien'   => (string)($r['ten_giang_vien'] ?? ''),
            'si_so_toi_da' => $toiDa,
            'da_dang_ky'   => $daDk,
            'con_lai'      => max(0, $toiDa - $daDk),
            'trang_thai'   => (string)$r['trang_thai'],
        ];
    }

    $first = $danhSach[0] ?? null;

    echo json_encode([
        'success'    => true,
        'con_lai'    => $first['con_lai'] ?? 0,
        'trang_thai' => $first['trang_thai'] ?? 'khoa',
        'total'      => count($danhSach),
        'data'       => $danhSach,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('api_lop_hoc_phan: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi máy chủ khi truy vấn lớp học phần.',
    ], JSON_UNESCAPED_UNICODE);
}
