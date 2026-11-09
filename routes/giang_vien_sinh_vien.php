<?php

require_once __DIR__ . '/_require_role.php';
studenthubRequireRole(['giangvien']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Controllers/GiangVienController.php';

$controller = new GiangVienController($pdo ?? $conn ?? null);
$controller->students();
