
<?php
session_start();

// Kiểm tra đăng nhập và quyền quản trị viên.
if (empty($_SESSION['username'])) {
    header('Location: ../../../public/index.php');
    exit;
}

$vaiTro = strtolower(trim((string) ($_SESSION['role'] ?? '')));

if (!in_array($vaiTro, ['admin', 'quantri', 'quan_tri', 'quan_tri_vien'], true)) {
    header('Location: ../../../public/index.php');
    exit;
}

// Kết nối cơ sở dữ liệu.
require_once __DIR__ . '/../../../config/database.php';

$tenAdmin = $_SESSION['ho_ten']
    ?? $_SESSION['tenVaiTro']
    ?? $_SESSION['username'];

$tongSinhVien = 0;
$tongGiangVien = 0;
$tongHocPhan = 0;
$tongLopHocPhan = 0;
$loi = '';

try {
    $stmt = $conn->query(
        "SELECT COUNT(*) FROM nguoi_dung
         WHERE vai_tro = 'sinh_vien'"
    );
    $tongSinhVien = (int) $stmt->fetchColumn();

    $stmt = $conn->query(
        "SELECT COUNT(*) FROM nguoi_dung
         WHERE vai_tro = 'giang_vien'"
    );
    $tongGiangVien = (int) $stmt->fetchColumn();

    $stmt = $conn->query(
        "SELECT COUNT(*) FROM hoc_phan"
    );
    $tongHocPhan = (int) $stmt->fetchColumn();

    $stmt = $conn->query(
        "SELECT COUNT(*) FROM lop_hoc_phan"
    );
    $tongLopHocPhan = (int) $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log('StudentHub Admin: ' . $e->getMessage());
    $loi = 'Không thể tải số liệu. Vui lòng kiểm tra cấu trúc cơ sở dữ liệu.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tổng quan quản trị | StudentHub</title>

    <link rel="stylesheet"
          href="../../../assets/css/quantrivien.css">
</head>

<body>
<div class="layout">

    <!-- THANH MENU BÊN TRÁI -->
    <aside class="sidebar">
        <a href="index.php" class="brand">
            <span class="brand-icon">S</span>
            <span>
                <strong>StudentHub</strong>
                <small>Quản lý học phần</small>
            </span>
        </a>

        <div class="menu-title">QUẢN TRỊ HỆ THỐNG</div>

        <nav class="menu">
            <a href="index.php" class="menu-link active">
                <span class="menu-icon">⌂</span>
                <span>Tổng quan</span>
            </a>

            <a href="tai_khoan.php" class="menu-link">
                <span class="menu-icon">♙</span>
                <span>Quản lý tài khoản</span>
            </a>

            <a href="hoc_phan.php" class="menu-link">
                <span class="menu-icon">▤</span>
                <span>Quản lý học phần</span>
            </a>

            <a href="hoc_ky.php" class="menu-link">
                <span class="menu-icon">▦</span>
                <span>Quản lý học kỳ</span>
            </a>

            <a href="lop_hoc_phan.php" class="menu-link">
                <span class="menu-icon">▣</span>
                <span>Quản lý lớp học phần</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="menu-title">TÀI KHOẢN</div>

            <a href="../../../app/auth/logout.php"
               class="menu-link logout">
                <span class="menu-icon">↪</span>
                <span>Đăng xuất</span>
            </a>

            <div class="sidebar-note">
                <span class="status-dot"></span>
                Hệ thống StudentHub
            </div>
        </div>
    </aside>

    <!-- NỘI DUNG CHÍNH -->
    <main class="main">

        <header class="topbar">
            <div>
                <div class="breadcrumb">Trang chủ / Tổng quan</div>
                <h1>Tổng quan hệ thống</h1>
                <p>Quản lý tài khoản và hoạt động đăng ký học phần.</p>
            </div>

            <div class="admin-profile">
                <div class="avatar">QT</div>
                <div class="profile-info">
                    <strong>
                        <?= htmlspecialchars(
                            $tenAdmin,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                    <span>Quản trị viên</span>
                </div>
            </div>
        </header>

        <div class="content">

            <!-- LỜI CHÀO -->
            <section class="welcome">
                <div class="welcome-text">
                    <span class="welcome-tag">STUDENTHUB ADMIN</span>
                    <h2>Xin chào, Quản trị viên!</h2>
                    <p>
                        Chào mừng bạn đến với hệ thống quản lý học phần
                        StudentHub. Hãy quản lý dữ liệu đào tạo tại đây.
                    </p>
                </div>

                <div class="welcome-symbol" aria-hidden="true">
                    <span>SH</span>
                </div>
            </section>

            <!-- THÔNG BÁO LỖI -->
            <?php if ($loi !== ''): ?>
                <div class="error-message">
                    <?= htmlspecialchars(
                        $loi,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            <?php endif; ?>

            <!-- THẺ THỐNG KÊ -->
            <section class="stats-section">
                <div class="section-heading">
                    <div>
                        <h2>Số liệu hệ thống</h2>
                        <p>Tổng hợp dữ liệu hiện có trong StudentHub.</p>
                    </div>
                </div>

                <div class="stats">
                    <article class="stat-card blue">
                        <div class="stat-top">
                            <span class="stat-label">Tổng sinh viên</span>
                            <span class="stat-icon">♙</span>
                        </div>
                        <h3><?= number_format($tongSinhVien) ?></h3>
                        <p>Tài khoản sinh viên</p>
                    </article>

                    <article class="stat-card green">
                        <div class="stat-top">
                            <span class="stat-label">Tổng giảng viên</span>
                            <span class="stat-icon">♧</span>
                        </div>
                        <h3><?= number_format($tongGiangVien) ?></h3>
                        <p>Tài khoản giảng viên</p>
                    </article>

                    <article class="stat-card purple">
                        <div class="stat-top">
                            <span class="stat-label">Tổng học phần</span>
                            <span class="stat-icon">▤</span>
                        </div>
                        <h3><?= number_format($tongHocPhan) ?></h3>
                        <p>Học phần trong hệ thống</p>
                    </article>

                    <article class="stat-card orange">
                        <div class="stat-top">
                            <span class="stat-label">Tổng lớp học phần</span>
                            <span class="stat-icon">▣</span>
                        </div>
                        <h3><?= number_format($tongLopHocPhan) ?></h3>
                        <p>Lớp học phần đã tạo</p>
                    </article>
                </div>
            </section>

            <!-- CHỨC NĂNG QUẢN TRỊ -->
            <section class="section">
                <div class="section-heading">
                    <div>
                        <h2>Chức năng quản trị</h2>
                        <p>Chọn chức năng bạn muốn thực hiện.</p>
                    </div>
                </div>

                <div class="quick-links">

                    <a class="quick-link" href="tai_khoan.php">
                        <div class="quick-icon blue-bg">♙</div>
                        <div class="quick-content">
                            <h3>Quản lý tài khoản</h3>
                            <p>
                                Tạo và quản lý tài khoản sinh viên,
                                giảng viên.
                            </p>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>

                    <a class="quick-link" href="hoc_phan.php">
                        <div class="quick-icon purple-bg">▤</div>
                        <div class="quick-content">
                            <h3>Quản lý học phần</h3>
                            <p>
                                Theo dõi và cập nhật danh sách
                                học phần đào tạo.
                            </p>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>

                    <a class="quick-link" href="hoc_ky.php">
                        <div class="quick-icon green-bg">▦</div>
                        <div class="quick-content">
                            <h3>Quản lý học kỳ</h3>
                            <p>
                                Quản lý các học kỳ phục vụ
                                đăng ký học phần.
                            </p>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>

                    <a class="quick-link" href="lop_hoc_phan.php">
                        <div class="quick-icon orange-bg">▣</div>
                        <div class="quick-content">
                            <h3>Quản lý lớp học phần</h3>
                            <p>
                                Quản lý lớp, giảng viên phụ trách
                                và sĩ số tối đa.
                            </p>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>

                </div>
            </section>

            <footer class="page-footer">
                StudentHub · Hệ thống quản lý học phần
            </footer>
        </div>
    </main>
</div>

<script src="../../../assets/js/quantrivien.js"></script>
</body>
</html>
