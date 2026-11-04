<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Controllers/AdminLopHocPhanController.php';

if (!$pdo instanceof PDO) {
    throw new RuntimeException('Không thể kết nối cơ sở dữ liệu để quản lý lớp học phần.');
}

$controller = new AdminLopHocPhanController($pdo);
$controller->handleRequest();
