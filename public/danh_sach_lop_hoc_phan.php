<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Controllers/DanhSachLopHocPhanController.php';

$controller = new DanhSachLopHocPhanController($pdo);
$controller->index();
