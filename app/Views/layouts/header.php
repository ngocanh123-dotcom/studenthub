<?php
// app/Views/layouts/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$hoTen = $_SESSION['ho_ten'] ?? 'Nguyễn Thu Trang';
$maSinhVien = $_SESSION['username'] ?? 'SV001';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Cổng thông tin sinh viên</title>
    <!-- Font chữ chuẩn -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Nạp CSS hệ thống chung & sinh viên -->
    <link rel="stylesheet" href="/studenthub/assets/css/sinhvien.css">
</head>
<body class="sh-body">

<div class="sh-container">
    <!-- Nạp Sidebar thống nhất -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Mở vùng nội dung chính bên phải -->
    <main class="sh-main-content">
        <!-- Header tiêu đề trang & Thẻ cá nhân góc phải -->
        <header class="sh-top-header">
            <div>
                <div class="top-breadcrumb"><?= $breadcrumb ?? 'Trang chủ / Tổng quan' ?></div>
                <h1><?= $pageTitle ?? 'Tổng quan học tập' ?></h1>
                <p class="header-subtext"><?= $pageSubtitle ?? 'Theo dõi tiến độ đăng ký học phần, số tín chỉ tích lũy và lịch học trong học kỳ.' ?></p>
            </div>

            <!-- Thẻ thông tin cá nhân -->
            <div class="profile-pill-box">
                <div class="profile-avatar-circle">SV</div>
                <div class="profile-name-role">
                    <strong><?= htmlspecialchars($hoTen) ?></strong>
                    <span>Sinh viên • <?= htmlspecialchars($maSinhVien) ?></span>
                </div>
            </div>
        </header>