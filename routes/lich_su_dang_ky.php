<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/_require_role.php';
studenthubRequireRole(['sinhvien']);

// Nhúng file kết nối DB. Nhớ sửa lại đường dẫn nếu cần
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../app/Controllers/LichSuController.php';

$controller = new LichSuController($pdo);
$controller->index();