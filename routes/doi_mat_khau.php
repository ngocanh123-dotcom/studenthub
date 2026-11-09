<?php
/**
 * routes/doi_mat_khau.php
 * Route điều phối module Đổi mật khẩu - StudentHub
 * Kiến trúc: MVC Chuẩn công nghiệp
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

$authController = new AuthController($pdo ?? $conn ?? null);
$authController->changePassword();
