<?php
/**
 * public/admin_lop_hoc_phan.php
 * Entrypoint chuyển tiếp (Bridge Router) vào phân hệ Quản trị Lớp học phần
 */

$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: /studenthub/app/Views/admin/lop_hoc_phan.php' . $queryString);
exit;
