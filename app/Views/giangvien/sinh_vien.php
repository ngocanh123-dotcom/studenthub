<?php
/**
 * app/Views/giangvien/sinh_vien.php
 * View Danh sách sinh viên theo lớp học phần
 * Dữ liệu ($lop, $sinhVienList, $tongSinhVien, $tenGiangVien, $username) do GiangVienController truyền sang
 */

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars(
            (string)($value ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
    }
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
