<?php

require_once __DIR__ . '/_require_role.php';
studenthubRequireRole(['sinhvien']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Controllers/ClassController.php';

$controller = new ClassController($pdo ?? $conn ?? null);
$controller->detail();
