<?php
/**
 * app/Views/auth/login.php
 * Giao diện Cổng Đăng Nhập Hệ Thống - StudentHub
 * Kiến trúc: MVC Chuẩn công nghiệp - View thuần HTML/PHP
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cổng Đăng Ký Học Phần & Quản Trị Học Vụ - StudentHub</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css?v=<?= time() ?>">
    <style>
        /* Tùy chỉnh thông báo thành công cho form đăng nhập */
        .notification-banner.success {
            display: block !important;
            background: #ecfdf5 !important;
            color: #065f46 !important;
            border: 1px solid #a7f3d0 !important;
        }
    </style>
</head>

<body class="login-page">
<div class="login-split-layout">

    <!-- CỘT TRÁI: SHOWCASE & GIỚI THIỆU -->
    <aside class="showcase-pane">
        <div class="showcase-header">
            <div class="brand-badge-box">
                <svg viewBox="0 0 24 24" width="24" height="24"
                     fill="none" stroke="currentColor" stroke-width="2.2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10L12 5 2 10l10 5 10-5z"/>
                    <path d="M6 12v5c3 2.5 9 2.5 12 0v-5"/>
                    <path d="M22 10v6"/>
                </svg>
            </div>

            <div class="brand-text-wrap">
                <h2>StudentHub</h2>
                <span>HỆ THỐNG ĐÀO TẠO TÍN CHỈ</span>
            </div>
        </div>

        <div class="showcase-body">
            <h1 class="showcase-main-title">
                Cổng Đăng Ký Học Phần &amp; Quản Trị Học Vụ
            </h1>

            <p class="showcase-description">
                Hệ sinh thái học tập số hóa đồng bộ: hỗ trợ sinh viên tra cứu
                lộ trình đào tạo, đăng ký tín chỉ trực tuyến và quản trị dữ liệu
                học phần dành riêng cho giảng viên và nhà trường.
            </p>

            <div class="features-list">
                <div class="feature-card-item">
                    <div class="feature-icon-box">
                        <svg viewBox="0 0 24 24" width="20" height="20"
                             fill="none" stroke="currentColor" stroke-width="2.2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>

                    <div class="feature-content">
                        <strong>Đăng ký tín chỉ trực tuyến</strong>
                        <p>Thời gian thực, kiểm soát sĩ số phòng học và chống trùng lịch học thông minh.</p>
                    </div>
                </div>

                <div class="feature-card-item">
                    <div class="feature-icon-box">
                        <svg viewBox="0 0 24 24" width="20" height="20"
                             fill="none" stroke="currentColor" stroke-width="2.2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>

                    <div class="feature-content">
                        <strong>Thời khóa biểu &amp; Tiến độ học tập</strong>
                        <p>Tích hợp đề cương học phần và cảnh báo học vụ chuẩn xác.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="showcase-footer">
            &copy; 2026 Trung tâm Công nghệ Thông tin &amp; Khảo thí Đào tạo - StudentHub
        </div>
    </aside>

    <!-- CỘT PHẢI: FORM ĐĂNG NHẬP -->
    <main class="form-pane">
        <div class="auth-card-container">
            <div class="auth-card-header">
                <h2>Đăng nhập hệ thống</h2>
                <p>Nhập thông tin xác thực để bắt đầu phiên làm việc</p>
            </div>

            <form method="POST" action="index.php?route=login" id="authLoginForm" novalidate>
                <!-- Ẩn khối Chọn vai trò truy cập (Backend tự động nhận diện từ CSDL) -->
                <div style="display: none;" aria-hidden="true">
                <span class="bento-group-label">CHỌN VAI TRÒ TRUY CẬP</span>

                <div class="bento-tiles-grid" role="radiogroup">
                    <!-- SINH VIÊN -->
                    <div class="bento-tile-item">
                        <input type="radio" name="role" id="role_sinhvien"
                               value="sinhvien"
                            <?= ($role ?? 'sinhvien') === 'sinhvien' ? 'checked' : '' ?>>

                        <label for="role_sinhvien">
                            <span class="tile-icon-box">
                                <svg viewBox="0 0 24 24" width="20" height="20"
                                     fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 10L12 5 2 10l10 5 10-5z"/>
                                    <path d="M6 12v5c3 2.5 9 2.5 12 0v-5"/>
                                </svg>
                            </span>
                            <span class="tile-title">Sinh viên</span>
                        </label>
                    </div>

                    <!-- GIẢNG VIÊN -->
                    <div class="bento-tile-item">
                        <input type="radio" name="role" id="role_giangvien"
                               value="giangvien"
                            <?= ($role ?? 'sinhvien') === 'giangvien' ? 'checked' : '' ?>>

                        <label for="role_giangvien">
                            <span class="tile-icon-box">
                                <svg viewBox="0 0 24 24" width="20" height="20"
                                     fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                    <line x1="8" y1="21" x2="16" y2="21"/>
                                    <line x1="12" y1="17" x2="12" y2="21"/>
                                </svg>
                            </span>
                            <span class="tile-title">Giảng viên</span>
                        </label>
                    </div>

                    <!-- QUẢN TRỊ -->
                    <div class="bento-tile-item">
                        <input type="radio" name="role" id="role_quantri"
                               value="quantri"
                            <?= ($role ?? 'sinhvien') === 'quantri' ? 'checked' : '' ?>>

                        <label for="role_quantri">
                            <span class="tile-icon-box">
                                <svg viewBox="0 0 24 24" width="20" height="20"
                                     fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                            </span>
                            <span class="tile-title">Quản trị</span>
                        </label>
                    </div>
                </div>
                </div>

                <!-- TÊN ĐĂNG NHẬP -->
                <div class="field-control-group">
                    <label for="usernameInput">Mã định danh / Tên đăng nhập</label>
                    <div class="field-input-wrapper">
                        <span class="field-leading-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18"
                                 fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>

                        <input type="text" id="usernameInput" name="username"
                               value="<?= htmlspecialchars(($username ?? '') !== '' ? $username : 'sv001', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Ví dụ: sv001, gv001, admin..."
                               autocomplete="username" required>
                    </div>
                </div>

                <!-- MẬT KHẨU -->
                <div class="field-control-group">
                    <label for="passwordInput">Mật khẩu truy cập</label>
                    <div class="field-input-wrapper">
                        <span class="field-leading-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18"
                                 fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>

                        <input type="password" id="passwordInput" name="password"
                               placeholder="Nhập mật khẩu truy cập..."
                               autocomplete="current-password" required>

                        <button type="button" class="btn-eye-toggle"
                                id="btnTogglePassword" title="Hiện mật khẩu">
                            <svg viewBox="0 0 24 24" width="18" height="18"
                                 fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- GHI NHỚ VÀ QUÊN MẬT KHẨU -->
                <div class="aux-actions-row">
                    <label class="remember-label" for="rememberMeCheckbox">
                        <input type="checkbox" id="rememberMeCheckbox" name="remember">
                        <span>Ghi nhớ phiên đăng nhập</span>
                    </label>

                    <a href="/studenthub/public/index.php?route=quen_mat_khau" class="forgot-link">
                        Quên mật khẩu?
                    </a>
                </div>

                <!-- THÔNG BÁO LỖI HOẶC THÀNH CÔNG NẾU CÓ -->
                <?php if (!empty($thongBao)): ?>
                    <div class="notification-banner <?= ($loaiThongBao ?? '') === 'success' ? 'success' : 'error' ?>" id="statusBanner">
                        <?= htmlspecialchars($thongBao, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php else: ?>
                    <div class="notification-banner" id="statusBanner" style="display: none;"></div>
                <?php endif; ?>

                <!-- NÚT ĐĂNG NHẬP -->
                <button type="submit" class="btn-primary-auth" id="btnSubmitAuth">
                    <span>ĐĂNG NHẬP VÀO HỆ THỐNG</span>
                </button>
            </form>

            <div class="auth-card-footer">
                Hệ thống Quản lý khóa học và Đăng ký học phần • StudentHub 2026
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/login.js"></script>
</body>
</html>
