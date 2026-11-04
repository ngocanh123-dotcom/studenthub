<?php
session_start();
require_once __DIR__ . '/../config/database.php';
// SỬA DÒNG NÀY (Bỏ chữ /Admin/ đi)
require_once __DIR__ . '/../app/Controllers/AdminHocKyController.php';

// SỬA TÊN CLASS Ở ĐÂY
$controller = new AdminHocKyController($conn);
$controller->index();