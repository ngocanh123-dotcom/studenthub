<?php
// app/Views/layouts/sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role =$_SESSION['role'] ?? 'sinhvien';
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="sh-sidebar">
    <!-- 1. Logo Thương Hiệu StudentHub -->
    <div class="sidebar-brand">
        <div class="brand-cap-icon">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 10L12 5 2 10l10 5 10-5z"/>
                <path d="M6 12v5c3 2.5 9 2.5 12 0v-5"/>
                <path d="M22 10v6"/>
            </svg>
        </div>
        <div class="brand-info">
            <h2>StudentHub</h2>
            <span>Học vụ & Đào tạo</span>
        </div>
    </div>

    <!-- 2. Danh mục điều hướng -->
    <nav class="sidebar-nav">
        <?php if ($role === 'sinhvien'): ?>
            <!-- Menu Sinh viên chuẩn Hình 2 -->
            <a href="/studenthub/app/Views/sinhvien/index.php" class="nav-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span>Trang chủ</span>
            </a>

            <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" class="nav-link <?= ($currentPage === 'danh_sach_lop_hoc_phan.php') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"/>
                    <line x1="8" y1="12" x2="21" y2="12"/>
                    <line x1="8" y1="18" x2="21" y2="18"/>
                    <line x1="3" y1="6" x2="3.01" y2="6"/>
                    <line x1="3" y1="12" x2="3.01" y2="12"/>
                    <line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
                <span>Danh sách học phần</span>
            </a>

            <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <line x1="19" y1="8" x2="19" y2="14"/>
                    <line x1="22" y1="11" x2="16" y2="11"/>
                </svg>
                <span>Đăng ký học phần</span>
            </a>

            <a href="#" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
                <span>Học phần đã đăng ký</span>
            </a>

            <a href="#" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 14 14"/>
                </svg>
                <span>Xem kết quả</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- 3. Khu vực đáy Sidebar: Nút Đăng nhập & Đăng xuất -->
    <div class="sidebar-footer">
        <a href="/studenthub/public/index.php" class="footer-btn btn-login-alt">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                <polyline points="10 17 15 12 10 7"/>
                <line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            <span>Trang đăng nhập</span>
        </a>

        <a href="/studenthub/public/index.php?action=logout" class="footer-btn btn-logout-action">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span>Đăng xuất</span>
        </a>
    </div>
</aside>