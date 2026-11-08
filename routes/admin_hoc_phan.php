<?php

require_once __DIR__ . '/_require_role.php';
studenthubRequireRole(['quantri', 'admin', 'quan_tri', 'quan_tri_vien']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Controllers/HocPhanController.php';

if (!$pdo instanceof PDO) {
    throw new RuntimeException('Không thể kết nối cơ sở dữ liệu để quản lý học phần.');
}

$controller = new HocPhanController($pdo);
$controller->index();
