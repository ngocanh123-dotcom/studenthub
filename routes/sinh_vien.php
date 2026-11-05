<?php

require_once __DIR__ . '/_require_role.php';
studenthubRequireRole(['sinhvien']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/controllers/SinhVienDashboardController.php';

$controller = new SinhVienDashboardController($pdo);
$controller->index();
