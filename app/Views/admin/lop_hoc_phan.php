<?php
/**
 * app/Views/admin/lop_hoc_phan.php
 * Điểm điều hướng trung tâm (Dispatcher / Front Controller) cho Module Quản lý Lớp học phần Admin
 * Kiến trúc: MVC - Gọi Controller chuyên biệt AdminLopHocPhanController
 */

require_once __DIR__ . '/../../Controllers/AdminLopHocPhanController.php';

$controller = new AdminLopHocPhanController();
$controller->handleRequest();
