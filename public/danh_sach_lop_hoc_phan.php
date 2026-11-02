<?php
/**
 * public/danh_sach_lop_hoc_phan.php
 * Điểm điều hướng (Dispatcher) cho trang Danh sách lớp học phần của Sinh viên
 * Kiến trúc: MVC (Model - View - Controller)
 */

require_once __DIR__ . '/../app/Controllers/DanhSachLopHocPhanController.php';

$controller = new DanhSachLopHocPhanController();
$controller->index();
