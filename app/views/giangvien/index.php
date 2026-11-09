<?php
/**
 * app/Views/giangvien/index.php
 * View Tổng quan dành cho Giảng viên (Lecturer Dashboard)
 * Dữ liệu ($tenGiangVien, $username, $tongHocPhan, $tongLop, $tongSinhVien, $classes) do GiangVienController truyền sang
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

    <title>Tổng quan giảng viên - StudentHub</title>

    <link rel="stylesheet" href="/studenthub/assets/css/giangvien.css">
</head>

<body class="lecturer-page">

<div class="layout">

    <?php
    $lecturerActiveMenu = 'overview';
    require __DIR__ . '/../layouts/lecturer_sidebar.php';
    ?>


    <!-- ==================================
         NỘI DUNG CHÍNH
    =================================== -->
    <main class="main-content">
        <!-- TOP BAR: THÔNG TIN GIẢNG VIÊN -->
        <div class="student-topbar">

            <div class="breadcrumb">
                Trang chủ / Tổng quan
            </div>

            <div class="profile-pill">

                <div class="profile-avatar">
                    GV
                </div>

                <div class="profile-details">

                    <strong>
                        <?= e($tenGiangVien) ?>
                    </strong>

                    <span>
                        Giảng viên • <?= e($username) ?>
                    </span>

                </div>

            </div>

        </div>


        <!-- TIÊU ĐỀ TRANG -->
        <div class="page-heading">

            <div>
                <h2>Tổng quan giảng viên</h2>

                <p>
                    Theo dõi học phần được phân công,
                    lớp học phần và tình hình đăng ký của sinh viên.
                </p>
            </div>

            <span class="today" id="today"></span>

        </div>


        <!-- ==================================
             BANNER XIN CHÀO
        =================================== -->
        <section class="welcome-banner">

            <div class="welcome-content">

                <p class="welcome-label">
                    STUDENTHUB LECTURER PORTAL
                </p>

                <h2>
                    Xin chào, <?= e($tenGiangVien) ?>!
                </h2>

                <p class="welcome-description">
                    Chào mừng bạn đến với hệ thống quản lý giảng dạy
                    StudentHub. Tại đây, bạn có thể theo dõi các lớp
                    học phần được phân công và tình hình đăng ký
                    học tập của sinh viên.
                </p>

            </div>

            <div class="welcome-logo">
                GV
            </div>

        </section>


        <!-- ==================================
             THỐNG KÊ GIẢNG VIÊN
        =================================== -->
        <section class="overview-section">

            <div class="section-heading">

                <h3>Số liệu giảng dạy</h3>

                <p>
                    Tổng hợp tình hình giảng dạy của bạn trong hệ thống.
                </p>

            </div>

            <div class="stats">

                <!-- Học phần -->
                <div class="stat-card">

                    <div>
                        <p>Học phần phụ trách</p>

                        <h3>
                            <?= $tongHocPhan ?>
                        </h3>

                        <small>
                            Số học phần được phân công
                        </small>
                    </div>

                    <div class="stat-icon blue">
                        ▤
                    </div>

                </div>


                <!-- Lớp học phần -->
                <div class="stat-card">

                    <div>
                        <p>Tổng lớp học phần</p>

                        <h3>
                            <?= $tongLop ?>
                        </h3>

                        <small>
                            Các lớp đang phụ trách
                        </small>
                    </div>

                    <div class="stat-icon green">
                        ▦
                    </div>

                </div>


                <!-- Sinh viên đăng ký -->
                <div class="stat-card">

                    <div>
                        <p>Tổng lượt sinh viên đăng ký</p>

                        <h3>
                            <?= $tongSinhVien ?>
                        </h3>

                        <small>
                            Tổng lượt đăng ký các lớp
                        </small>
                    </div>

                    <div class="stat-icon orange">
                        ♙
                    </div>

                </div>

            </div>

        </section>


        <!-- ==================================
             DANH SÁCH HỌC PHẦN
        =================================== -->
        <section class="panel" id="course-list">

            <div class="panel-heading">

                <div>

                    <h3>Danh sách học phần phụ trách</h3>

                    <p>
                        Các học phần được phân công cho bạn giảng dạy.
                    </p>

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
                                        <strong>
                                            <?= e($class['ma_hoc_phan']) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= e($class['ten_hoc_phan']) ?>
                                    </td>

                                    <td>
                                        <?= (int)$class['tong_lop'] ?>
                                    </td>

                                    <td>
                                        <a
                                            class="btn"
                                            href="/studenthub/public/index.php?route=giang_vien_lop_hoc_phan.php&hoc_phan_id=<?= (int)$class['hoc_phan_id'] ?>"
                                        >
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


        <!-- FOOTER -->
        <footer>
            © 2026 StudentHub - Hệ thống quản lý học phần
        </footer>

    </main>

</div>


<!-- ==================================
     JAVASCRIPT
=================================== -->
<script>

// Hiển thị ngày hiện tại
const today = document.getElementById('today');

if (today) {
    today.textContent = new Date().toLocaleDateString('vi-VN');
}


// Tìm kiếm học phần
const searchClass = document.getElementById('searchClass');

if (searchClass) {

    searchClass.addEventListener('input', function () {

        const keyword = this.value
            .toLocaleLowerCase('vi')
            .trim();

        document.querySelectorAll('#classTable tr')
            .forEach(function (row) {

                const content = row.textContent
                    .toLocaleLowerCase('vi');

                row.hidden = !content.includes(keyword);

            });

    });

}

</script>

</body>
</html>
