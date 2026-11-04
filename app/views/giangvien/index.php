
<?php
session_start();

// ==========================================
// 1. KIỂM TRA QUYỀN GIẢNG VIÊN
// ==========================================
if (
    empty($_SESSION['username']) ||
    ($_SESSION['role'] ?? '') !== 'giangvien'
) {
    header('Location: ../../../public/index.php');
    exit;
}

$username = $_SESSION['username'];

// ==========================================
// 2. KẾT NỐI CƠ SỞ DỮ LIỆU
// ==========================================
require_once __DIR__ . '/../../../config/database.php';
$username = (string) $_SESSION['username'];

// ==========================================
// 3. LẤY THÔNG TIN GIẢNG VIÊN ĐANG ĐĂNG NHẬP
// ==========================================
try {
    $currentLecturerId = (int)(
        $_SESSION['user_id']
        ?? $_SESSION['nguoi_dung_id']
        ?? 0
    );

    // Nếu Session chưa có ID thì tìm theo tên đăng nhập
    if ($currentLecturerId <= 0) {
        $stmtGv = $pdo->prepare("
            SELECT id
            FROM nguoi_dung
            WHERE ten_dang_nhap = :username
              AND vai_tro = 'giang_vien'
            LIMIT 1
        ");

        $stmtGv->execute([
            ':username' => $username
        ]);

        $currentLecturerId = (int)$stmtGv->fetchColumn();
    }

    // Lấy họ tên chính xác từ cơ sở dữ liệu
    $stmtInfo = $pdo->prepare("
        SELECT id, ho_ten, ten_dang_nhap
        FROM nguoi_dung
        WHERE id = :id
          AND ten_dang_nhap = :username
          AND vai_tro = 'giang_vien'
        LIMIT 1
    ");

    $stmtInfo->execute([
        ':id' => $currentLecturerId,
        ':username' => $username
    ]);

    $lecturerInfo = $stmtInfo->fetch();

    if (!$lecturerInfo) {
        exit('Không tìm thấy thông tin tài khoản giảng viên.');
    }

    $currentLecturerId = (int)$lecturerInfo['id'];

    $tenGiangVien = $lecturerInfo['ho_ten']
        ?: ($_SESSION['tenVaiTro'] ?? 'Giảng viên');

    // Cập nhật họ tên vào Session nếu cần
    $_SESSION['ho_ten'] = $tenGiangVien;

    // ==========================================
    // 4. LẤY DANH SÁCH HỌC PHẦN ĐƯỢC PHÂN CÔNG
    // ==========================================
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

    $stmt->execute([
        ':current_lecturer_id' => $currentLecturerId
    ]);

    $classes = $stmt->fetchAll();

    // ==========================================
    // 5. THỐNG KÊ TỔNG QUAN
    // ==========================================
    $tongLop = 0;
    $tongSinhVien = 0;

    foreach ($classes as $class) {
        $tongLop += (int)$class['tong_lop'];
    }

    $tongHocPhan = count($classes);

    // Tổng lượt sinh viên đăng ký các lớp của giảng viên
    $sqlSinhVien = "
        SELECT COUNT(
            DISTINCT CONCAT(
                dk.lop_hoc_phan_id,
                '-',
                dk.sinh_vien_id
            )
        )
        FROM dang_ky_hoc dk
        INNER JOIN lop_hoc_phan lhp
            ON lhp.id = dk.lop_hoc_phan_id
        WHERE lhp.giang_vien_id = :current_lecturer_id
          AND dk.trang_thai = 'dang_ky'
    ";

    $stmtSinhVien = $pdo->prepare($sqlSinhVien);

    $stmtSinhVien->execute([
        ':current_lecturer_id' => $currentLecturerId
    ]);

    $tongSinhVien = (int)$stmtSinhVien->fetchColumn();

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Không thể tải dữ liệu giảng viên. Vui lòng kiểm tra cơ sở dữ liệu.');
}

// ==========================================
// 6. HÀM HỖ TRỢ
// ==========================================
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

    <title>Tổng quan giảng viên - StudentHub</title>

    <link rel="stylesheet" href="../../../assets/css/giangvien.css">
</head>

<body class="lecturer-page">

<div class="layout">

    <!-- ==================================
         SIDEBAR BÊN TRÁI
    =================================== -->
    <aside class="sidebar">

        <!-- Logo StudentHub -->
        <div class="sidebar-brand">

            <div class="sidebar-brand-icon">
                S
            </div>

            <div class="sidebar-brand-text">
                <h1>StudentHub</h1>
                <p>Cổng thông tin giảng viên</p>
            </div>

        </div>

        <!-- Menu -->
        <p class="menu-title">CỔNG GIẢNG VIÊN</p>

        <a href="index.php" class="menu-item active">
            <span>⌂</span>
            Tổng quan
        </a>

        <a href="lop_hoc_phan.php" class="menu-item">
            <span>▦</span>
            Lớp học phần
        </a>

        <a href="sinh_vien.php" class="menu-item">
            <span>♙</span>
            Sinh viên theo lớp
        </a>

        <!-- Đăng xuất -->
        <div class="sidebar-bottom">
            <a href="javascript:void(0)" onclick="openChangePasswordModal()" class="menu-item change-pass-link" style="color: #38bdf8;">
                <span>🔑</span> Đổi mật khẩu
            </a>
            <a href="../../../public/index.php?action=logout" class="menu-item logout">
                <span>⇥</span> Đăng xuất
            </a>
        </div>

    </aside>
<script src="/studenthub/assets/js/change_password.js"></script>


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
        <section class="panel">

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
                                            href="lop_hoc_phan.php?hoc_phan_id=<?= (int)$class['hoc_phan_id'] ?>"
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
