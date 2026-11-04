<?php
session_start();
if ((int)($_SESSION['user_id'] ?? 0) <= 0) {
    header('Location: /studenthub/public/index.php?error=login_required');
    exit;
}
// Nhúng file kết nối DB. Nhớ sửa lại đường dẫn nếu cần
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../app/Controllers/LichSuController.php';

$controller = new LichSuController($pdo);
$controller->index();