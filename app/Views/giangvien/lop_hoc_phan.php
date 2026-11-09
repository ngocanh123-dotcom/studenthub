<?php
/**
 * app/Views/giangvien/lop_hoc_phan.php
 * View Danh sách lớp học phần theo môn của Giảng viên
 * Dữ liệu ($hocPhan, $classes, $tenGiangVien, $username) do GiangVienController truyền sang
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

if (!function_exists('trangThaiLop')) {
    function trangThaiLop($status): string
    {
        return match ($status) {
            'mo' => 'Đang mở',
            'khoa' => 'Đã khóa',
            default => (string)$status
        };
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lớp học phần - StudentHub</title>

    <link rel="stylesheet" href="/studenthub/assets/css/giangvien.css">
</head>

<body class="lecturer-page">

<div class="layout">

    <?php
    $lecturerActiveMenu = 'course';
    $lecturerCourseId = (int)$hocPhan['id'];
    require __DIR__ . '/../layouts/lecturer_sidebar.php';
    ?>

    <!-- ==================================
         NỘI DUNG CHÍNH
    =================================== -->
    <main class="main-content">
        <!-- TOPBAR -->
        <div class="student-topbar">

            <div class="breadcrumb">
                Trang chủ / Lớp học phần
            </div>

            <div class="profile-pill">

                <div class="profile-avatar">
                    GV
                </div>

                <div class="profile-details">
                    <strong><?= e($tenGiangVien) ?></strong>

                    <span>
                        Giảng viên • <?= e($username) ?>
                    </span>
                </div>

            </div>

        </div>

        <!-- TIÊU ĐỀ TRANG -->
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

        <!-- DANH SÁCH LỚP -->
        <section class="panel">

            <div class="panel-heading">

                <div>
                    <h3>
                        Các lớp thuộc học phần
                        <?= e($hocPhan['ma_hoc_phan']) ?>
                    </h3>

                    <p>
                        Lịch học và phòng học được hiển thị bên dưới. Chọn một lớp để xem danh sách sinh viên đã đăng ký.
                    </p>
                </div>

                <input
                    type="search"
                    id="searchClass"
                    placeholder="Tìm mã lớp, học kỳ, lịch học hoặc phòng..."
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
                            <th>Lịch học</th>
                            <th>Phòng học</th>
                            <th>Sĩ số</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody id="classTable">

                    <?php if (empty($classes)): ?>

                        <tr>
                            <td colspan="7" class="empty">
                                Học phần này chưa có lớp học phần được phân công.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($classes as $index => $class): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= e($class['ma_lop']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= e($class['ten_hoc_ky']) ?>
                                </td>

                                <td>
                                    <?= e($class['lich_hoc'] ?: 'Chưa xếp lịch') ?>
                                </td>

                                <td>
                                    <?= e($class['phong_hoc'] ?: 'Chưa phân phòng') ?>
                                </td>

                                <td>
                                    <?= (int)$class['si_so_hien_tai'] ?>
                                    /
                                    <?= (int)$class['si_so_toi_da'] ?>
                                </td>

                                <td>
                                    <a
                                        class="btn"
                                        href="/studenthub/public/index.php?route=giang_vien_sinh_vien.php&lop_id=<?= (int)$class['id'] ?>"
                                    >
                                        Xem sinh viên
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- NÚT QUAY LẠI -->
            <div class="back-action">
                <a href="/studenthub/public/index.php?route=giang_vien.php" class="back-button">
                    <span>←</span>
                    Quay lại danh sách học phần
                </a>
            </div>

        </section>

        <footer>
            © 2026 StudentHub - Hệ thống quản lý học phần
        </footer>

    </main>

</div>

<script>
// ==========================================
// HIỂN THỊ NGÀY HIỆN TẠI
// ==========================================
const today = document.getElementById('today');

if (today) {
    today.textContent = new Date().toLocaleDateString('vi-VN');
}

// ==========================================
// TÌM KIẾM LỚP HỌC PHẦN
// ==========================================
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
