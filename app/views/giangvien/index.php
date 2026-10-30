
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
$dbPass = '123456';

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

// 3. Lấy danh sách học phần của giảng viên [TC 6]
try {
    // [TC 6]: Xác định ID giảng viên hiện tại từ Session
    $currentLecturerId = (int)($_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 0);
    if ($currentLecturerId <= 0) {
        $stmtGvId = $pdo->prepare("SELECT id FROM nguoi_dung WHERE ten_dang_nhap = :u AND vai_tro = 'giang_vien' LIMIT 1");
        $stmtGvId->execute([':u' => $username]);
        $currentLecturerId = (int)$stmtGvId->fetchColumn();
    }

    // [TC 6]: Đảm bảo câu truy vấn lấy danh sách lớp luôn có điều kiện WHERE lhp.giang_vien_id = :current_lecturer_id
    $sql = "
        SELECT
            hp.id AS hoc_phan_id,
            hp.ma_hoc_phan,
            hp.ten_hoc_phan,
            COUNT(DISTINCT lhp.id) AS tong_lop
        FROM hoc_phan hp
        INNER JOIN lop_hoc_phan lhp
            ON lhp.hoc_phan_id = hp.id
        WHERE lhp.giang_vien_id = :current_lecturer_id
        GROUP BY
            hp.id,
            hp.ma_hoc_phan,
            hp.ten_hoc_phan
        ORDER BY hp.ma_hoc_phan
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['current_lecturer_id' => $currentLecturerId]);
    $classes = $stmt->fetchAll();

    // 4. Thống kê tổng quan (giữ nguyên)
    $tongLop = 0;
    $tongSinhVien = 0;
    $hocPhanIds = [];

    foreach ($classes as $class) {
        $tongLop += (int)$class['tong_lop'];
        $hocPhanIds[] = $class['ma_hoc_phan'];
    }

    $tongHocPhan = count($classes);

    // [TC 6]: Tính tổng lượt sinh viên đăng ký của các lớp do giảng viên phụ trách
    $sqlSinhVien = "
        SELECT COUNT(
            DISTINCT CONCAT(dk.lop_hoc_phan_id, '-', dk.sinh_vien_id)
        )
        FROM dang_ky_hoc dk
        INNER JOIN lop_hoc_phan lhp
            ON lhp.id = dk.lop_hoc_phan_id
        WHERE lhp.giang_vien_id = :current_lecturer_id
          AND dk.trang_thai = 'dang_ky'
    ";

    $stmtSinhVien = $pdo->prepare($sqlSinhVien);
    $stmtSinhVien->execute(['current_lecturer_id' => $currentLecturerId]);
    $tongSinhVien = (int)$stmtSinhVien->fetchColumn();

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Không thể tải danh sách học phần. Vui lòng kiểm tra cấu trúc database.');
}

// 5. Hàm hỗ trợ hiển thị
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
    <title>Tổng quan giảng viên - StudentHub</title>
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

    <a href="index.php" class="menu-item active">
        <span>▦</span> Tổng quan
    </a>

    <a href="lop_hoc_phan.php" class="menu-item">
        <span>▤</span> Lớp học phần
    </a>

    <a href="sinh_vien.php" class="menu-item">
        <span>♙</span> Sinh viên theo lớp
    </a>

    <a href="../../../public/index.php" class="menu-item logout">
        <span>⇥</span> Quay lại đăng nhập
    </a>
</aside>


    <main class="main-content">

        <div class="page-heading">
            <div>
                <h2>Tổng quan giảng viên</h2>
                <p>
                    Quản lý các học phần được phân công
                    và theo dõi tình hình đăng ký.
                </p>
            </div>

            <span class="today" id="today"></span>
        </div>

        <!-- THỐNG KÊ -->
        <section class="stats">

            <div class="stat-card">
                <div class="stat-icon blue">▤</div>
                <div>
                    <p>Học phần phụ trách</p>
                    <h3><?= $tongHocPhan ?></h3>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">▦</div>
                <div>
                    <p>Tổng lớp học phần</p>
                    <h3><?= $tongLop ?></h3>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">♙</div>
                <div>
                    <p>Tổng lượt sinh viên đăng ký</p>
                    <h3><?= $tongSinhVien ?></h3>
                </div>
            </div>

        </section>

        <!-- DANH SÁCH HỌC PHẦN -->
        <section class="panel">

            <div class="panel-heading">
                <div>
                    <h3>Danh sách học phần</h3>
                    <p>Các học phần do bạn phụ trách.</p>
                </div>

                <input
                    type="search"
                    id="searchClass"
                    placeholder="Tìm mã hoặc tên học phần..."
                    aria-label="Tìm học phần"
                >
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Mã học phần</th>
                            <th>Tên học phần</th>
                            <th>Số lớp học phần</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody id="classTable">
                    <?php if (empty($classes)): ?>
                        <tr>
                            <td colspan="4" class="empty">
                                Chưa có học phần được phân công.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($classes as $class): ?>
                            <tr>
                                <td>
                                    <?= e($class['ma_hoc_phan']) ?>
                                </td>

                                <td>
                                    <?= e($class['ten_hoc_phan']) ?>
                                </td>

                                <td>
                                    <?= (int)$class['tong_lop'] ?>
                                </td>

                                <td>
                                    <a href="lop_hoc_phan.php?hoc_phan_id=<?= (int)$class['hoc_phan_id'] ?>">
                                        Xem lớp học phần
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
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

    // Tìm kiếm học phần
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