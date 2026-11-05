
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra quyền giảng viên
if (
    empty($_SESSION['username']) ||
    ($_SESSION['role'] ?? '') !== 'giangvien'
) {
    header('Location: /studenthub/public/index.php');
    exit;
}

// 2. Kết nối cơ sở dữ liệu
// [BUG-01 Hotfix] Nạp kết nối cơ sở dữ liệu dùng chung từ config/database.php
require_once __DIR__ . '/../../../config/database.php';
$username = $_SESSION['username'];

if (!isset($pdo) || !($pdo instanceof PDO)) {
    exit('Không thể kết nối cơ sở dữ liệu StudentHub.');
}

// 3. Kiểm tra lớp học phần được chọn
$lopId = filter_input(INPUT_GET, 'lop_id', FILTER_VALIDATE_INT);

if (!$lopId || $lopId < 1) {
    header('Location: /studenthub/public/index.php?route=giang_vien.php');
    exit;
}

// 4. Lấy thông tin giảng viên và kiểm tra quyền
try {
    $stmtGiangVien = $pdo->prepare("
        SELECT id, ho_ten, ten_dang_nhap
        FROM nguoi_dung
        WHERE ten_dang_nhap = :username
          AND vai_tro = 'giang_vien'
        LIMIT 1
    ");

    $stmtGiangVien->execute([
        'username' => $username
    ]);

    $giangVien = $stmtGiangVien->fetch();

    if (!$giangVien) {
        http_response_code(403);
        exit('Không tìm thấy tài khoản giảng viên.');
    }

    $currentLecturerId = (int)$giangVien['id'];
    $tenGiangVien = $giangVien['ho_ten'] ?: 'Giảng viên';

    $_SESSION['user_id'] = $currentLecturerId;
    $_SESSION['tenVaiTro'] = $tenGiangVien;

    // Kiểm tra lớp có thuộc giảng viên hiện tại không
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
          AND lhp.giang_vien_id = :giang_vien_id
        LIMIT 1
    ";

    $stmtLop = $pdo->prepare($sqlLop);

    $stmtLop->execute([
        'lop_id' => $lopId,
        'giang_vien_id' => $currentLecturerId
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
        ORDER BY sv.ten_dang_nhap ASC
    ";

    $stmtSinhVien = $pdo->prepare($sqlSinhVien);

    $stmtSinhVien->execute([
        'lop_id' => $lopId
    ]);

    $sinhVienList = $stmtSinhVien->fetchAll();

    $tongSinhVien = count($sinhVienList);

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Không thể tải danh sách sinh viên. Vui lòng kiểm tra cấu trúc database.');
}

// 6. Hàm bảo vệ dữ liệu khi hiển thị
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

    <link rel="stylesheet" href="/studenthub/assets/css/giangvien.css">
</head>

<body class="lecturer-page">

<div class="layout">

    <?php
    $lecturerActiveMenu = 'students';
    $lecturerCourseId = (int)$lop['hoc_phan_id'];
    $lecturerClassId = (int)$lop['id'];
    require __DIR__ . '/../layouts/lecturer_sidebar.php';
    ?>

    <!-- NỘI DUNG CHÍNH -->
    <main class="main-content">
        <!-- TOPBAR -->
        <header class="student-topbar">

            <div class="breadcrumb">
                <span>Giảng viên</span>
                <span>/</span>
                <strong>Sinh viên theo lớp</strong>
            </div>

            <div class="profile-pill">
                <div class="profile-avatar">GV</div>

                <div class="profile-details">
                    <strong><?= e($tenGiangVien) ?></strong>
                    <small>Mã tài khoản: <?= e($username) ?></small>
                </div>
            </div>

        </header>

        <!-- TIÊU ĐỀ TRANG -->
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


        <!-- DANH SÁCH SINH VIÊN -->
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

                <div class="student-actions">

                    <input
                        type="search"
                        id="searchStudent"
                        placeholder="Tìm mã SV hoặc họ tên..."
                        aria-label="Tìm kiếm sinh viên"
                    >

                    <button
                        type="button"
                        id="btnExportPDF"
                        class="btn-export"
                    >
                        ⬇ Xuất PDF
                    </button>

                </div>

            </div>

            <!-- THÔNG TIN DÀNH CHO BẢN IN -->
            <div class="pdf-header">

                <h1>STUDENTHUB</h1>

                <h2>DANH SÁCH SINH VIÊN THEO LỚP HỌC PHẦN</h2>

                <p>
                    <strong>Mã lớp:</strong>
                    <?= e($lop['ma_lop']) ?>
                </p>

                <p>
                    <strong>Mã học phần:</strong>
                    <?= e($lop['ma_hoc_phan']) ?>
                </p>

                <p>
                    <strong>Tên học phần:</strong>
                    <?= e($lop['ten_hoc_phan']) ?>
                </p>

                <p>
                    <strong>Học kỳ:</strong>
                    <?= e($lop['ten_hoc_ky']) ?>
                </p>

                <p>
                    <strong>Tổng số sinh viên:</strong>
                    <?= $tongSinhVien ?>
                </p>

                <p>
                    <strong>Ngày xuất:</strong>
                    <?= date('d/m/Y') ?>
                </p>

            </div>

            <!-- BẢNG SINH VIÊN -->
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

            <!-- QUAY LẠI -->
            <div class="back-action">

                <a
                    href="/studenthub/public/index.php?route=giang_vien_lop_hoc_phan.php&hoc_phan_id=<?= (int)$lop['hoc_phan_id'] ?>"
                    class="back-button"
                >
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
        today.textContent = new Date().toLocaleDateString('vi-VN');
    }

    // Tìm kiếm sinh viên
    const searchStudent = document.getElementById('searchStudent');

    if (searchStudent) {
        searchStudent.addEventListener('input', function () {
            const keyword = this.value.toLocaleLowerCase('vi');

            document.querySelectorAll('#studentTable tr').forEach(function (row) {
                row.hidden = !row.textContent
                    .toLocaleLowerCase('vi')
                    .includes(keyword);
            });
        });
    }

    // Xuất danh sách sinh viên thành PDF
    const btnExportPDF = document.getElementById('btnExportPDF');

    if (btnExportPDF) {
        btnExportPDF.addEventListener('click', function () {
            const titleCu = document.title;

            document.title =
                'Danh_sach_sinh_vien_' +
                <?= json_encode($lop['ma_lop']) ?>;

            window.print();

            document.title = titleCu;
        });
    }
</script>

</body>
</html>
