<?php
// app/Views/layouts/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$hoTen =$_SESSION['ho_ten'] ?? 'Nguyễn Thu Trang';
$vaiTro =$_SESSION['tenVaiTro'] ?? 'Sinh viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Hệ thống quản lý học phần</title>
    
    <!-- Font chữ hiện đại -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS Khung sườn & Sidebar -->
    <link rel="stylesheet" href="/studenthub/assets/css/style.css">
    
    <!-- CSS riêng cho trang Sinh viên -->
    <link rel="stylesheet" href="/studenthub/assets/css/sinhvien.css">
</head>
<body class="sh-app-body">

<div class="sh-main-wrapper">
    <!-- Nạp Sidebar chung -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Khu vực nội dung bên phải (Gồm Header và Ruột trang) -->
    <div class="sh-content-area">
        <!-- TOPBAR TRÊN CÙNG -->
        <header class="sh-topbar">
            <!-- Ô tìm kiếm học phần -->
            <div class="search-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" placeholder="Tìm kiếm học phần...">
            </div>

            <!-- Cụm thông báo & Tài khoản người dùng -->
            <div class="topbar-right">
                <button class="btn-notification" title="Thông báo mới">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span class="noti-badge"></span>
                </button>

                <!-- Profile Pill chuẩn Hình 2 -->
                <div class="user-profile-badge">
                    <div class="avatar-circle">
                        <?= strtoupper(substr($hoTen, 0, 1)) ?>
                    </div>
                    <div class="user-meta">
                        <span class="user-full-name"><?= htmlspecialchars($hoTen) ?></span>
                        <span class="user-role-tag"><?= htmlspecialchars($vaiTro) ?></span>
                    </div>
                    <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </div>
            </div>
        </header>

        <!-- KHỐI CHÍNH (Sẽ được đóng ở footer.php) -->
        <main class="sh-page-body">