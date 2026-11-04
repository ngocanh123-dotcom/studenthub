<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Controllers/DanhSachLopHocPhanController.php';

if (!$pdo instanceof PDO) {
    throw new RuntimeException('Không thể kết nối cơ sở dữ liệu để hiển thị danh sách lớp học phần.');
}

$controller = new DanhSachLopHocPhanController($pdo);
$controller->index();
