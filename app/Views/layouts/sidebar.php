<?php
// app/Views/layouts/sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUri = $_SERVER['REQUEST_URI'];
?>

<aside class="sh-sidebar">
    <div>
        <!-- Logo Thương hiệu StudentHub -->
        <div class="sidebar-brand-box">
            <div class="brand-icon-s">S</div>
            <div class="brand-info">
                <h2>StudentHub</h2>
                <p>Cổng thông tin sinh viên</p>
            </div>
        </div>

        <!-- Danh mục Menu chức năng -->
        <div class="sidebar-category-title">CỔNG SINH VIÊN</div>
        <nav class="sidebar-menu-list">
            <!-- 1. Tổng quan -->
            <a href="/studenthub/app/Views/sinhvien/index.php" 
               class="sh-nav-item <?= (strpos($currentUri, '/sinhvien/index.php') !== false || (strpos($currentUri, 'sinhvien') !== false && strpos($currentUri, 'detail') === false)) ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span>Tổng quan</span>
            </a>

            <!-- 2. Danh sách lớp học phần -->
            <a href="/studenthub/public/danh_sach_lop_hoc_phan.php" 
               class="sh-nav-item <?= (strpos($currentUri, 'danh_sach_lop_hoc_phan') !== false && strpos($currentUri, 'dang_ky') === false) ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Danh sách lớp học phần</span>
            </a>

            <!-- 3. ĐĂNG KÝ HỌC PHẦN (MỚI BỔ SUNG) -->
            <a href="/studenthub/public/danh_sach_lop_hoc_phan.php?action=dangky" 
               class="sh-nav-item <?= (strpos($currentUri, 'action=dangky') !== false || strpos($currentUri, 'dang_ky_hoc_phan') !== false) ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                <span>Đăng ký học phần</span>
            </a>

            <!-- 4. Chi tiết lớp học phần -->
<a href="/studenthub/app/Views/classes/detail.php?id=1" 
               class="sh-nav-item <?= (strpos($currentUri, '/classes/detail.php') !== false) ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span>Chi tiết lớp học phần</span>
            </a>

            <!-- 5. Lịch sử đăng ký -->
            <a href="#" class="sh-nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 14 14"></polyline>
                </svg>
                <span>Lịch sử đăng ký</span>
            </a>

            <!-- 6. Đăng xuất -->
            <a href="/studenthub/public/index.php?action=logout" class="sh-nav-item logout-link" title="Đăng xuất khỏi hệ thống">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span>Đăng xuất</span>
            </a>
        </nav>
    </div>

    <!-- Khối trạng thái học kỳ ở chân Sidebar -->
    <div class="sidebar-semester-pill">
        <div class="semester-status-line">
            <span class="green-dot"></span>
            <span>Học kỳ 1 (2026 - 2027)</span>
        </div>
        <p>Cổng đăng ký tín chỉ đang mở cho sinh viên toàn trường.</p>
    </div>
</aside>
