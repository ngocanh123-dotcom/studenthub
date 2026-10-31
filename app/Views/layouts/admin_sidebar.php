<?php
/**
 * app/Views/layouts/admin_sidebar.php
 * Thanh điều hướng Sidebar cho Quản trị viên (Admin)
 * Phong cách thẩm mỹ đồng bộ 100% với Cổng Sinh viên
 */

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$currentUri = $_SERVER['REQUEST_URI'] ?? '';

// Tự động nhận diện trang active nếu biến $activeMenu chưa được truyền từ View cha
if (!isset($activeMenu) || empty($activeMenu)) {
    if (strpos($currentUri, 'tai_khoan.php') !== false) {
        $activeMenu = 'tai_khoan';
    } elseif (strpos($currentUri, 'hoc_phan.php') !== false) {
        $activeMenu = 'hoc_phan';
    } elseif (strpos($currentUri, 'hoc_ky.php') !== false) {
        $activeMenu = 'hoc_ky';
    } elseif (strpos($currentUri, 'lop_hoc_phan.php') !== false || strpos($currentUri, '/classes/') !== false) {
        $activeMenu = 'lop_hoc_phan';
    } else {
        $activeMenu = 'index';
    }
}
?>

<aside class="sidebar sh-sidebar">
    <div class="sidebar-top-group">
        <!-- Khối Logo & Tiêu đề Brand -->
        <a href="index.php" class="brand sidebar-brand-box">
            <div class="brand-icon brand-icon-s">S</div>
            <div class="brand-info">
                <strong>StudentHub</strong>
                <small>Cổng quản trị hệ thống</small>
            </div>
        </a>

        <!-- Tiêu đề phân mục -->
        <div class="menu-title sidebar-category-title">QUẢN TRỊ HỆ THỐNG</div>

        <!-- Danh sách 5 Menu Items chính của Quản trị viên -->
        <nav class="menu sidebar-menu-list">
            <!-- 1. Tổng quan -->
            <a href="index.php" class="menu-link sh-nav-item <?= $activeMenu === 'index' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span>Tổng quan</span>
            </a>

            <!-- 2. Quản lý tài khoản -->
            <a href="tai_khoan.php" class="menu-link sh-nav-item <?= $activeMenu === 'tai_khoan' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Quản lý tài khoản</span>
            </a>

            <!-- 3. Quản lý học phần -->
            <a href="hoc_phan.php" class="menu-link sh-nav-item <?= $activeMenu === 'hoc_phan' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    <line x1="9" y1="6" x2="16" y2="6"/>
                    <line x1="9" y1="10" x2="14" y2="10"/>
                </svg>
                <span>Quản lý học phần</span>
            </a>

            <!-- 4. Quản lý học kỳ -->
            <a href="hoc_ky.php" class="menu-link sh-nav-item <?= $activeMenu === 'hoc_ky' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Quản lý học kỳ</span>
            </a>

            <!-- 5. Quản lý lớp học phần -->
            <a href="lop_hoc_phan.php" class="menu-link sh-nav-item <?= $activeMenu === 'lop_hoc_phan' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                </svg>
                <span>Quản lý lớp học phần</span>
            </a>
        </nav>
    </div>

    <!-- Khu vực chân Sidebar (Footer Action) -->
    <div class="sidebar-bottom sidebar-footer-action">
        <a href="javascript:void(0)" onclick="openChangePasswordModal()" class="menu-link change-pass-link sh-nav-item" style="color: #38bdf8;">
            <span style="font-size: 16px;">🔑</span> <span>Đổi mật khẩu</span>
        </a>
        <a href="/studenthub/public/index.php?action=logout" class="menu-link logout sh-nav-item logout-link" title="Đăng xuất khỏi hệ thống">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span>Đăng xuất</span>
        </a>

        <!-- Thẻ thông tin phiên làm việc -->
        <div class="sidebar-semester-pill sidebar-note">
            <div class="semester-status-line">
                <span class="status-dot green-dot"></span>
                <span>Quản trị viên</span>
            </div>
            <p>Phiên làm việc Quản trị viên đang kết nối an toàn.</p>
        </div>
    </div>
</aside>
<script src="/studenthub/assets/js/change_password.js"></script>
