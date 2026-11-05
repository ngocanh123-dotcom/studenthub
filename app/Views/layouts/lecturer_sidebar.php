<?php
require_once __DIR__ . '/../../Core/Auth.php';

$lecturerActiveMenu = $lecturerActiveMenu ?? 'overview';
$lecturerCourseLink = isset($lecturerCourseId)
    ? '/studenthub/public/index.php?route=giang_vien_lop_hoc_phan.php&hoc_phan_id=' . (int)$lecturerCourseId
    : '/studenthub/public/index.php?route=giang_vien.php#course-list';
$lecturerStudentsLink = isset($lecturerClassId)
    ? '/studenthub/public/index.php?route=giang_vien_sinh_vien.php&lop_id=' . (int)$lecturerClassId
    : '/studenthub/public/index.php?route=giang_vien.php#course-list';
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">S</div>
        <div class="sidebar-brand-text">
            <h1>StudentHub</h1>
            <p>Cổng thông tin giảng viên</p>
        </div>
    </div>

    <p class="menu-title">CỔNG GIẢNG VIÊN</p>

    <a href="/studenthub/public/index.php?route=giang_vien.php"
       class="menu-item <?= $lecturerActiveMenu === 'overview' ? 'active' : '' ?>">
        <span>⌂</span>
        Tổng quan
    </a>

    <a href="<?= htmlspecialchars($lecturerCourseLink, ENT_QUOTES, 'UTF-8') ?>"
       class="menu-item <?= $lecturerActiveMenu === 'course' ? 'active' : '' ?>">
        <span>▦</span>
        Lớp học phần
    </a>

    <a href="<?= htmlspecialchars($lecturerStudentsLink, ENT_QUOTES, 'UTF-8') ?>"
       class="menu-item <?= $lecturerActiveMenu === 'students' ? 'active' : '' ?>">
        <span>♙</span>
        Sinh viên theo lớp
    </a>

    <div class="sidebar-bottom">
        <a href="javascript:void(0)" onclick="openChangePasswordModal()" class="menu-item change-pass-link">
            <span>🔑</span>
            Đổi mật khẩu
        </a>
        <a href="/studenthub/public/index.php?action=logout" class="menu-item logout">
            <span>⇥</span>
            Quay lại đăng nhập
        </a>
    </div>
</aside>
<input type="hidden" name="csrf_token" id="modal_csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<script src="/studenthub/assets/js/change_password.js"></script>
