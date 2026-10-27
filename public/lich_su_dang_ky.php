<?php
session_start();
// Nhúng file kết nối DB. Nhớ sửa lại đường dẫn nếu cần
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../app/Controllers/LichSuController.php';

$controller = new LichSuController($pdo);
$controller->index();