
<?php
session_start();

// 1. Kiểm tra quyền giảng viên
if (
    empty($_SESSION['username']) ||
    ($_SESSION['role'] ?? '') !== 'giangvien'
) {
    header('Location: ../../../public/index.php');
    exit;
}

$username = $_SESSION['username'];
$tenGiangVien = $_SESSION['tenVaiTro'] ?? 'Giảng viên';

// 2. Kết nối cơ sở dữ liệu
$host = 'localhost';
$dbname = 'studenthub';
$dbUser = 'root';
$dbPass = 'Quynh@24012006'; // Mật khẩu mặc định của XAMPP

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Không thể kết nối cơ sở dữ liệu StudentHub.');
}

// 3. Kiểm tra học phần được chọn
$hocPhanId = filter_input(INPUT_GET, 'hoc_phan_id', FILTER_VALIDATE_INT);

if (!$hocPhanId || $hocPhanId < 1) {
    header('Location: index.php');
    exit;
}

// 4. Lấy thông tin học phần và các lớp thuộc học phần đó
try {
    // Kiểm tra học phần có thuộc giảng viên đang đăng nhập không
    // [TC 6]: Xác định ID giảng viên hiện tại từ Session
    $currentLecturerId = (int)($_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 0);
    if ($currentLecturerId <= 0) {
        $stmtGvId = $pdo->prepare("SELECT id FROM nguoi_dung WHERE ten_dang_nhap = :u AND vai_tro = 'giang_vien' LIMIT 1");
        $stmtGvId->execute([':u' => $username]);
        $currentLecturerId = (int)$stmtGvId->fetchColumn();
    }

    $sqlHocPhan = "
        SELECT DISTINCT
            hp.id,
            hp.ma_hoc_phan,
            hp.ten_hoc_phan
        FROM hoc_phan hp
        INNER JOIN lop_hoc_phan lhp
            ON lhp.hoc_phan_id = hp.id
        WHERE hp.id = :hoc_phan_id
          AND lhp.giang_vien_id = :current_lecturer_id
        LIMIT 1
    ";

    $stmtHocPhan = $pdo->prepare($sqlHocPhan);
    $stmtHocPhan->execute([
        'hoc_phan_id' => $hocPhanId,
        'current_lecturer_id' => $currentLecturerId
    ]);

    $hocPhan = $stmtHocPhan->fetch();

    if (!$hocPhan) {
        http_response_code(404);
        exit('Không tìm thấy học phần hoặc bạn không có quyền xem học phần này.');
    }

    // [TC 6]: Lấy danh sách lớp thuộc học phần do giảng viên hiện tại phụ trách
    $sqlLop = "
        SELECT
            lhp.id,
            lhp.ma_lop,
            hk.ten_hoc_ky,
            lhp.si_so_toi_da,
            lhp.trang_thai,
            COUNT(
                DISTINCT CASE
                    WHEN dk.trang_thai = 'dang_ky'
                    THEN dk.sinh_vien_id
                END
            ) AS si_so_hien_tai
        FROM lop_hoc_phan lhp
        INNER JOIN hoc_ky hk
            ON hk.id = lhp.hoc_ky_id
        LEFT JOIN dang_ky_hoc dk
            ON dk.lop_hoc_phan_id = lhp.id
        WHERE lhp.hoc_phan_id = :hoc_phan_id
          AND lhp.giang_vien_id = :current_lecturer_id
        GROUP BY
            lhp.id,
            lhp.ma_lop,
            hk.ten_hoc_ky,
            lhp.si_so_toi_da,
            lhp.trang_thai
        ORDER BY lhp.ma_lop
    ";

    $stmtLop = $pdo->prepare($sqlLop);
    $stmtLop->execute([
        'hoc_phan_id' => $hocPhanId,
        'current_lecturer_id' => $currentLecturerId
    ]);

    $classes = $stmtLop->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Không thể tải danh sách lớp học phần. Vui lòng kiểm tra cấu trúc database.');
}

// 5. Hàm hỗ trợ
function e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function trangThaiLop($status): string
{
    return match ($status) {
        'mo' => 'Đang mở',
        'khoa' => 'Đã khóa',
        default => (string)$status
    };
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lớp học phần - StudentHub</title>
    <link rel="stylesheet" href="../../../assets/css/giangvien.css">
</head>

<body class="lecturer-page">

<header class="header">
    <div class="brand">
        <div class="brand-icon">S</div>
        <div>
            <h1>StudentHub</h1>
            <p>CỔNG THÔNG TIN GIẢNG VIÊN</p>
        </div>
    </div>

    <div class="user-info">
        <div class="avatar">GV</div>
        <div>
            <strong><?= e($tenGiangVien) ?></strong>
            <small>Mã tài khoản: <?= e($username) ?></small>
        </div>
    </div>
</header>

<div class="layout">

    <aside class="sidebar">
        <p class="menu-title">MENU CHÍNH</p>

        <a href="index.php" class="menu-item">
            <span>▦</span> Tổng quan
        </a>

        <a href="lop_hoc_phan.php?hoc_phan_id=<?= (int)$hocPhan['id'] ?>"
           class="menu-item active">
            <span>▤</span> Lớp học phần
        </a>

        <a href="index.php" class="menu-item">
            <span>♙</span> Sinh viên theo lớp
        </a>

        <div class="sidebar-bottom">
            <a href="../../../public/index.php"
               class="menu-item logout">
                <span>⇥</span> Quay lại đăng nhập
            </a>
        </div>
    </aside>

    <main class="main-content">

        <div class="page-heading">
            <div>
                <h2>Danh sách lớp học phần</h2>
                <p>
                    Học phần:
                    <?= e($hocPhan['ma_hoc_phan']) ?> -
                    <?= e($hocPhan['ten_hoc_phan']) ?>
                </p>
            </div>

            <span class="today" id="today"></span>
        </div>

        <!-- DANH SÁCH LỚP THUỘC HỌC PHẦN -->
        <section class="panel">

            <div class="panel-heading">
                <div>
                    <h3>
                        Các lớp thuộc học phần
                        <?= e($hocPhan['ma_hoc_phan']) ?>
                    </h3>
                    <p>Chọn một lớp để xem danh sách sinh viên đã đăng ký.</p>
                </div>

                <input
                    type="search"
                    id="searchClass"
                    placeholder="Tìm mã lớp hoặc học kỳ..."
                    aria-label="Tìm lớp học phần"
                >
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã lớp</th>
                            <th>Học kỳ</th>
                            <th>Sĩ số</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody id="classTable">
                    <?php if (empty($classes)): ?>
                        <tr>
                            <td colspan="6" class="empty">
                                Học phần này chưa có lớp học phần được phân công.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($classes as $index => $class): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td><?= e($class['ma_lop']) ?></td>

                                <td><?= e($class['ten_hoc_ky']) ?></td>

                                <td>
                                    <?= (int)$class['si_so_hien_tai'] ?>
                                    /
                                    <?= (int)$class['si_so_toi_da'] ?>
                                </td>

                                <td>
                                    <span class="status">
                                        <?= e(trangThaiLop($class['trang_thai'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <a href="sinh_vien.php?lop_id=<?= (int)$class['id'] ?>">
                                        Xem sinh viên
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px;">
                <a href="index.php" class="menu-item">
                    ← Quay lại danh sách học phần
                </a>
            </div>

        </section>

        <footer>
            © 2026 StudentHub - Hệ thống quản lý học phần
        </footer>

    </main>
</div>

<script>
    // Hiển thị ngày hiện tại
    const today = document.getElementById('today');

    if (today) {
        today.textContent =
            new Date().toLocaleDateString('vi-VN');
    }

    // Tìm kiếm lớp học phần
    document.getElementById('searchClass')
        ?.addEventListener('input', function () {
            const keyword = this.value.toLocaleLowerCase('vi');

            document.querySelectorAll('#classTable tr')
                .forEach(function (row) {
                    row.hidden = !row.textContent
                        .toLocaleLowerCase('vi')
                        .includes(keyword);
                });
        });
</script>

</body>
</html>
