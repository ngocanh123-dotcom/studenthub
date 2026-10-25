<?php
session_start();

// Chỉ cho phép tài khoản giảng viên đã đăng nhập truy cập trang này.
if (
    empty($_SESSION['username']) ||
    ($_SESSION['role'] ?? '') !== 'giangvien'
) {
    header('Location: ../../../public/index.php');
    exit;
}

$tenGiangVien = $_SESSION['tenVaiTro'] ?? 'Giảng viên';
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang giảng viên - StudentHub</title>
<link rel="stylesheet" href="../../../assets/css/giangvien.css">
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
                <strong><?= htmlspecialchars($tenGiangVien, ENT_QUOTES, 'UTF-8') ?></strong>
                <small>Mã tài khoản: <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></small>
            </div>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <p class="menu-title">MENU CHÍNH</p>

            <a href="#" class="menu-item active">
                <span>▦</span> Tổng quan
            </a>

            <a href="#danh-sach-lop" class="menu-item">
                <span>▤</span> Lớp học phần
            </a>

            <a href="#danh-sach-sinh-vien" class="menu-item">
                <span>♙</span> Sinh viên theo lớp
            </a>

            <div class="sidebar-bottom">
                <a href="../../../public/index.php" class="menu-item logout">
                    <span>⇥</span> Quay lại đăng nhập
                </a>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-heading">
                <div>
                    <h2>Tổng quan giảng viên</h2>
                    <p>Quản lý lớp học phần và theo dõi sinh viên.</p>
                </div>

                <span class="today" id="today"></span>
            </div>

            <section class="stats">
                <div class="stat-card">
                    <div class="stat-icon blue">▤</div>
                    <div>
                        <p>Tổng lớp phụ trách</p>
                        <h3 id="totalClasses">0</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">♙</div>
                    <div>
                        <p>Tổng sinh viên</p>
                        <h3 id="totalStudents">0</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">◷</div>
                    <div>
                        <p>Lớp đang mở</p>
                        <h3 id="openClasses">0</h3>
                    </div>
                </div>
            </section>

            <section class="panel" id="danh-sach-lop">
                <div class="panel-heading">
                    <div>
                        <h3>Danh sách lớp học phần</h3>
                        <p>Các lớp được phân công giảng dạy</p>
                    </div>

                    <input
                        type="search"
                        id="searchClass"
                        placeholder="Tìm mã lớp, tên học phần..."
                        aria-label="Tìm mã lớp hoặc tên học phần"
                    >
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã lớp</th>
                                <th>Tên học phần</th>
                                <th>Tên lớp</th>
                                <th>Học kỳ</th>
                                <th>Sĩ số</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>

                        <tbody id="classTable">
                            <tr>
                                <td colspan="7" class="empty">Đang tải danh sách lớp...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="note">
                    * Dữ liệu hiện tại là dữ liệu mẫu để thiết kế giao diện, chưa kết nối cơ sở dữ liệu.
                </p>
            </section>

            <section class="panel" id="danh-sach-sinh-vien">
                <div class="panel-heading">
                    <div>
                        <h3>Danh sách sinh viên theo lớp</h3>
                        <p id="selectedClass">Chọn một lớp ở bảng phía trên để xem sinh viên.</p>
                    </div>

                    <input
                        type="search"
                        id="searchStudent"
                        placeholder="Tìm mã SV, họ tên..."
                        aria-label="Tìm mã sinh viên hoặc họ tên"
                    >
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã sinh viên</th>
                                <th>Họ và tên</th>
                                <th>Email</th>
                                <th>Số điện thoại</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>

                        <tbody id="studentTable">
                            <tr>
                                <td colspan="5" class="empty">Chưa chọn lớp học phần.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <footer>
                © 2026 StudentHub - Hệ thống quản lý học phần
            </footer>
        </main>
    </div>

    <script src="../../../assets/js/giangvien.js"></script>
<body class="lecturer-page">
</html>
