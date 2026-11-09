<?php
/**
 * public/index.php
 * Front Controller & Router Trung Tâm - StudentHub
 * Kiến trúc: MVC Chuẩn công nghiệp - PHP 8.x
 */

// 1. KHỞI ĐỘNG SESSION & KẾT NỐI CƠ SỞ DỮ LIỆU
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// 2. TIẾP NHẬN VÀ CHUẨN HÓA THAM SỐ ROUTE
$route = strtolower(trim((string)($_GET['route'] ?? 'login'), '/'));
if ($route === '') {
    $route = 'login';
}

// Hỗ trợ tương thích ngược với tham số ?action=logout
if (isset($_GET['action']) && strtolower(trim((string)$_GET['action'])) === 'logout') {
    $route = 'logout';
}

// 3. ĐIỀU PHỐI ROUTE (FRONT CONTROLLER SWITCH)
switch ($route) {
    // --- PHÂN HỆ XÁC THỰC (AUTH) ---
    case 'login':
    case 'login.php':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        $authController = new AuthController($pdo ?? $conn ?? null);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->login();
        } else {
            $authController->showLoginForm();
        }
        break;

    case 'logout':
    case 'logout.php':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        $authController = new AuthController($pdo ?? $conn ?? null);
        $authController->logout();
        break;

    case 'quen_mat_khau':
    case 'quen_mat_khau.php':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        $authController = new AuthController($pdo ?? $conn ?? null);
        $authController->quenMatKhau();
        break;

    case 'doi_mat_khau':
    case 'doi_mat_khau.php':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        $authController = new AuthController($pdo ?? $conn ?? null);
        $authController->changePassword();
        break;

    // --- PHÂN HỆ SINH VIÊN ---
    case 'dashboard':
    case 'tong_quan_sv':
    case 'tong_quan_sv.php':
    case 'sinh_vien':
    case 'sinh_vien.php':
        require_once __DIR__ . '/../routes/sinh_vien.php';
        break;

    case 'danh_sach_lop_hoc_phan':
    case 'danh_sach_lop_hoc_phan.php':
        require_once __DIR__ . '/../routes/danh_sach_lop_hoc_phan.php';
        break;

    case 'chi_tiet_lop_hoc_phan':
    case 'chi_tiet_lop_hoc_phan.php':
        require_once __DIR__ . '/../routes/chi_tiet_lop_hoc_phan.php';
        break;

    case 'lich_su_dang_ky':
    case 'lich_su_dang_ky.php':
        require_once __DIR__ . '/../routes/lich_su_dang_ky.php';
        break;

    case 'dangky':
    case 'dangky.php':
        header('Location: /studenthub/public/index.php?route=trang_dang_ky_hoc.php');
        exit;

    case 'trang_dang_ky_hoc':
    case 'trang_dang_ky_hoc.php':
        require_once __DIR__ . '/../routes/trang_dang_ky_hoc.php';
        break;

    case 'dang_ky_hoc':
    case 'dang_ky_hoc.php':
        require_once __DIR__ . '/../routes/dang_ky_hoc.php';
        break;

    case 'huy_dang_ky_hoc':
    case 'huy_dang_ky_hoc.php':
        require_once __DIR__ . '/../routes/huy_dang_ky_hoc.php';
        break;

    case 'api_huy_dang_ky':
    case 'api_huy_dang_ky.php':
        require_once __DIR__ . '/../routes/api_huy_dang_ky.php';
        break;

    // --- PHÂN HỆ GIẢNG VIÊN ---
    case 'giang_vien':
    case 'giang_vien.php':
        require_once __DIR__ . '/../routes/giang_vien.php';
        break;

    case 'giang_vien_lop_hoc_phan':
    case 'giang_vien_lop_hoc_phan.php':
        require_once __DIR__ . '/../routes/giang_vien_lop_hoc_phan.php';
        break;

    case 'giang_vien_sinh_vien':
    case 'giang_vien_sinh_vien.php':
        require_once __DIR__ . '/../routes/giang_vien_sinh_vien.php';
        break;

    // --- PHÂN HỆ QUẢN TRỊ VIÊN (ADMIN) ---
    case 'admin_dashboard':
    case 'admin_dashboard.php':
        require_once __DIR__ . '/../app/Controllers/AdminDashboardController.php';
        $adminDashboardController = new AdminDashboardController($pdo ?? $conn ?? null);
        $adminDashboardController->index();
        break;

    case 'admin_tai_khoan':
    case 'admin_tai_khoan.php':
    case 'tai_khoan':
    case 'tai_khoan.php':
        require_once __DIR__ . '/../routes/admin_tai_khoan.php';
        break;

    case 'admin_hoc_phan':
    case 'admin_hoc_phan.php':
    case 'hoc_phan':
    case 'hoc_phan.php':
        require_once __DIR__ . '/../routes/admin_hoc_phan.php';
        break;

    case 'admin_hoc_ky':
    case 'admin_hoc_ky.php':
        require_once __DIR__ . '/../routes/admin_hoc_ky.php';
        break;

    case 'admin_lop_hoc_phan':
    case 'admin_lop_hoc_phan.php':
        require_once __DIR__ . '/../routes/admin_lop_hoc_phan.php';
        break;

    case 'admin_in_danh_sach_lop':
    case 'admin_in_danh_sach_lop.php':
        require_once __DIR__ . '/../routes/admin_in_danh_sach_lop.php';
        break;

    default:
        http_response_code(404);
        exit('Không tìm thấy trang yêu cầu.');
}
