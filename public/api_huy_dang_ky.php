<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Controllers/LichSuController.php';

$controller = new LichSuController($pdo);
$controller->apiHuyDangKy();