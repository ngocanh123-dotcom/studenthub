<?php
/**
 * routes/admin_tai_khoan.php
 * Route điều phối phân hệ Quản lý tài khoản (Admin) - StudentHub
 * Kiến trúc: MVC Chuẩn công nghiệp
 */

require_once __DIR__ . '/_require_role.php';
studenthubRequireRole(['quantri', 'admin', 'quan_tri', 'quan_tri_vien']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Controllers/AdminTaiKhoanController.php';

$controller = new AdminTaiKhoanController($pdo ?? $conn ?? null);
$controller->index();
