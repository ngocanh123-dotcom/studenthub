
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
$dbPass = '';

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

// 3. Kiểm tra lớp học phần được chọn
$lopId = filter_input(INPUT_GET, 'lop_id', FILTER_VALIDATE_INT);

if (!$lopId || $lopId < 1) {
    header('Location: index.php');
    exit;
}

// 4. Lấy thông tin lớp và kiểm tra quyền giảng viên
try {
    // [TC 6]: Xác định ID giảng viên hiện tại từ Session
    $currentLecturerId = (int)($_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 0);
    if ($currentLecturerId <= 0) {
        $stmtGvId = $pdo->prepare("SELECT id FROM nguoi_dung WHERE ten_dang_nhap = :u AND vai_tro = 'giang_vien' LIMIT 1");
        $stmtGvId->execute([':u' => $username]);
        $currentLecturerId = (int)$stmtGvId->fetchColumn();
    }

    $sqlLop = "
        SELECT
            lhp.id,
            lhp.ma_lop,
            hp.id AS hoc_phan_id,
            hp.ma_hoc_phan,
            hp.ten_hoc_phan,
            hk.ten_hoc_ky
        FROM lop_hoc_phan lhp
        INNER JOIN hoc_phan hp
            ON hp.id = lhp.hoc_phan_id
        INNER JOIN hoc_ky hk
            ON hk.id = lhp.hoc_ky_id
        WHERE lhp.id = :lop_id
          AND lhp.giang_vien_id = :current_lecturer_id
        LIMIT 1
    ";

    $stmtLop = $pdo->prepare($sqlLop);
    $stmtLop->execute([
        'lop_id' => $lopId,
        'current_lecturer_id' => $currentLecturerId
    ]);

    $lop = $stmtLop->fetch();

    if (!$lop) {
        http_response_code(404);
        exit('Không tìm thấy lớp học phần hoặc bạn không có quyền xem lớp này.');
    }

    // 5. Lấy danh sách sinh viên đã đăng ký lớp
    $sqlSinhVien = "
        SELECT
            sv.id,
            sv.ten_dang_nhap,
            sv.ho_ten,
            sv.email,
            sv.so_dien_thoai
        FROM dang_ky_hoc dk
        INNER JOIN nguoi_dung sv
            ON sv.id = dk.sinh_vien_id
        WHERE dk.lop_hoc_phan_id = :lop_id
          AND dk.trang_thai = 'dang_ky'
          AND sv.vai_tro = 'sinh_vien'
        ORDER BY sv.ten_dang_nhap
    ";

    $stmtSinhVien = $pdo->prepare($sqlSinhVien);
    $stmtSinhVien->execute(['lop_id' => $lopId]);
    $sinhVienList = $stmtSinhVien->fetchAll();

    $tongSinhVien = count($sinhVienList);

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Không thể tải danh sách sinh viên. Vui lòng kiểm tra cấu trúc database.');
}

// 6. Hàm hỗ trợ hiển thị
function e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinh viên theo lớp - StudentHub</title>
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

        <a href="lop_hoc_phan.php?hoc_phan_id=<?= (int)$lop['hoc_phan_id'] ?>"
           class="menu-item">
            <span>▤</span> Lớp học phần
        </a>

        <a href="sinh_vien.php?lop_id=<?= (int)$lop['id'] ?>"
           class="menu-item active">
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
                <h2>Danh sách sinh viên</h2>
                <p>
                    Lớp: <?= e($lop['ma_lop']) ?>
                    — <?= e($lop['ten_hoc_phan']) ?>
                </p>
            </div>

            <span class="today" id="today"></span>
        </div>

        <!-- THỐNG KÊ SINH VIÊN -->
        <section class="stats">
            <div class="stat-card">
                <div class="stat-icon blue">♙</div>
                <div>
                    <p>Sinh viên đã đăng ký</p>
                    <h3><?= $tongSinhVien ?></h3>
                </div>
            </div>
        </section>

        <!-- THÔNG TIN LỚP -->
        <section class="panel">

            <div class="panel-heading">
                <div>
                    <h3>Thông tin lớp học phần</h3>
                    <p>
                        Mã lớp: <?= e($lop['ma_lop']) ?>
                        |
                        Mã học phần: <?= e($lop['ma_hoc_phan']) ?>
                        |
                        Học kỳ: <?= e($lop['ten_hoc_ky']) ?>
                    </p>
                </div>

                <input
                    type="search"
                    id="searchStudent"
                    placeholder="Tìm mã SV hoặc họ tên..."
                    aria-label="Tìm sinh viên"
                >
            </div>

            <!-- DANH SÁCH SINH VIÊN -->
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã sinh viên</th>
                            <th>Họ và tên</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                        </tr>
                    </thead>

                    <tbody id="studentTable">
                    <?php if (empty($sinhVienList)): ?>
                        <tr>
                            <td colspan="5" class="empty">
                                Chưa có sinh viên đăng ký lớp học phần này.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sinhVienList as $index => $sv): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e($sv['ten_dang_nhap']) ?></td>
                                <td><?= e($sv['ho_ten']) ?></td>
                                <td><?= e($sv['email']) ?></td>
                                <td><?= e($sv['so_dien_thoai']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px;">
                <a href="lop_hoc_phan.php?hoc_phan_id=<?= (int)$lop['hoc_phan_id'] ?>"
                   class="menu-item">
                    ← Quay lại danh sách lớp học phần
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

    // Tìm kiếm sinh viên
    document.getElementById('searchStudent')
        ?.addEventListener('input', function () {
            const keyword = this.value.toLocaleLowerCase('vi');

            document.querySelectorAll('#studentTable tr')
                .forEach(function (row) {
                    row.hidden = !row.textContent
                        .toLocaleLowerCase('vi')
                        .includes(keyword);
                });
        });
</script>

</body>
</html>
```