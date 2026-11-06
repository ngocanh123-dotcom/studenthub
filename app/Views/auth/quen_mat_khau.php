<?php
/**
 * app/Views/auth/quen_mat_khau.php
 * Giao diện chức năng khôi phục lại mật khẩu của sinh viên - StudentHub
 * Kiến trúc: MVC View
 */
$csrfToken = $csrfToken ?? ($_SESSION['csrf_token'] ?? '');
$thongBaoLoi = $thongBaoLoi ?? '';
$thongBaoThanhCong = $thongBaoThanhCong ?? '';
$thongBaoThongTin = $thongBaoThongTin ?? '';
$maSvInput = $maSvInput ?? '';
$hoTenInput = $hoTenInput ?? '';
$sdtInput = $sdtInput ?? '';
$hinhThucInput = $hinhThucInput ?? 'email';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khôi phục mật khẩu sinh viên - StudentHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }

        .recovery-card {
            background: #ffffff;
            width: 100%;
            max-width: 780px;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            border: 1px solid #e2e8f0;
            padding: 36px 44px;
        }

        .recovery-header {
            text-align: center;
        }

        .recovery-title {
            font-size: 17px;
            color: #334155;
            font-weight: 700;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .recovery-warning {
            color: #dc2626;
            font-size: 13.5px;
            line-height: 1.55;
            font-weight: 600;
            margin: 14px 0 20px;
            text-align: center;
        }

        .recovery-divider {
            border: 0;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }

        /* THÔNG BÁO */
        .alert-box {
            padding: 13px 18px;
            border-radius: 8px;
            font-size: 13.5px;
            line-height: 1.5;
            margin-bottom: 22px;
            word-break: break-word;
        }
        .alert-error {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            font-weight: 600;
        }
        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            font-weight: 600;
        }
        .alert-info {
            background-color: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
            font-weight: 600;
        }

        /* FORM BỐ CỤC 2 CỘT */
        .recovery-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 240px 1fr;
            align-items: center;
            gap: 16px;
        }

        .form-row.top-aligned {
            align-items: flex-start;
        }

        .form-label {
            font-size: 13.5px;
            font-weight: 600;
            color: #334155;
            text-align: right;
            padding-right: 4px;
        }

        .form-input {
            width: 100%;
            height: 40px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 0 14px;
            font-size: 13.5px;
            color: #0f172a;
            background: #ffffff;
            transition: border-color 0.15s, box-shadow 0.15s;
            font-family: inherit;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        /* KHỐI RADIO CARDS HÌNH THỨC NHẬN */
        .radio-cards-wrap {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }

        .radio-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }

        .radio-card input[type="radio"] {
            margin-top: 3px;
            accent-color: #ef4444;
            cursor: pointer;
            width: 16px;
            height: 16px;
        }

        .radio-card-content {
            flex: 1;
        }

        .radio-card-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #1e293b;
            display: block;
            margin-bottom: 2px;
        }

        .radio-card-desc {
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
            display: block;
        }

        .radio-card:has(input[value="email"]:checked) {
            border-color: #ef4444;
            background-color: #fef2f2;
        }
        .radio-card:has(input[value="email"]:checked) .radio-card-title {
            color: #b91c1c;
        }

        .radio-card:has(input[value="ctsv"]:checked) {
            border-color: #64748b;
            background-color: #f8fafc;
        }

        /* NÚT HÀNH ĐỘNG */
        .btn-actions-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
        }

        .btn-recover {
            background-color: #ef4444;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 10px 22px;
            font-size: 13.5px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: background-color 0.15s, transform 0.1s;
        }

        .btn-recover:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
        }

        .btn-login {
            background-color: #3b82f6;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 10px 22px;
            font-size: 13.5px;
            font-weight: 700;
            font-family: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.15s, transform 0.1s;
        }

        .btn-login:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
        }

        /* RESPONSIVE TRÊN THIẾT BỊ DI ĐỘNG */
        @media (max-width: 680px) {
            .recovery-card {
                padding: 24px 20px;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }
            .form-label {
                text-align: left;
                padding-right: 0;
            }
            .btn-actions-row {
                flex-direction: column;
                align-items: stretch;
            }
            .btn-recover, .btn-login {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<div class="recovery-card">
    <div class="recovery-header">
        <h1 class="recovery-title">Chức năng khôi phục lại mật khẩu của sinh viên</h1>
        <p class="recovery-warning">
            Sinh viên phải nhập đầy đủ, chính xác thông tin đã cập nhật trên hệ thống. Sau khi xác thực thành công, mật khẩu mới sẽ được gửi đến email cá nhân đã đăng ký trên hệ thống. Liên hệ Giáo viên chủ nhiệm trong giờ hành chính để được hỗ trợ khi cần.
        </p>
    </div>

    <hr class="recovery-divider">

    <?php if ($thongBaoLoi !== ''): ?>
        <div class="alert-box alert-error">
            ⚠️ <?= htmlspecialchars($thongBaoLoi, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($thongBaoThanhCong !== ''): ?>
        <div class="alert-box alert-success">
            <?= $thongBaoThanhCong ?>
        </div>
    <?php endif; ?>

    <?php if ($thongBaoThongTin !== ''): ?>
        <div class="alert-box alert-info">
            <?= $thongBaoThongTin ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?route=quen_mat_khau" class="recovery-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <!-- Dòng 1: Mã sinh viên -->
        <div class="form-row">
            <label class="form-label" for="ma_sinh_vien">Mã sinh viên:</label>
            <div>
                <input type="text" id="ma_sinh_vien" name="ma_sinh_vien" class="form-input" placeholder="Nhập mã sinh viên" required value="<?= htmlspecialchars($maSvInput, ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <!-- Dòng 2: Họ và tên -->
        <div class="form-row">
            <label class="form-label" for="ho_ten">Họ và tên:</label>
            <div>
                <input type="text" id="ho_ten" name="ho_ten" class="form-input" placeholder="Nhập họ và tên" required value="<?= htmlspecialchars($hoTenInput, ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <!-- Dòng 3: Số điện thoại -->
        <div class="form-row">
            <label class="form-label" for="so_dien_thoai">Số điện thoại (đăng ký trên hệ thống):</label>
            <div>
                <input type="text" id="so_dien_thoai" name="so_dien_thoai" class="form-input" placeholder="Nhập SĐT đã đăng ký với nhà trường" value="<?= htmlspecialchars($sdtInput, ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <!-- Dòng 4: Hình thức nhận mật khẩu -->
        <div class="form-row top-aligned">
            <div class="form-label" style="padding-top: 6px;">Hình thức nhận mật khẩu:</div>
            <div class="radio-cards-wrap">
                <!-- Card 1: Có email cá nhân đã đăng ký (mặc định) -->
                <label class="radio-card">
                    <input type="radio" name="hinh_thuc" value="email" <?= $hinhThucInput === 'email' ? 'checked' : '' ?>>
                    <div class="radio-card-content">
                        <span class="radio-card-title">Có email cá nhân đã đăng ký</span>
                        <span class="radio-card-desc">Mật khẩu mới được gửi đến email cá nhân lưu trên hệ thống.</span>
                    </div>
                </label>

                <!-- Card 2: Sinh viên mới khóa 2026 -->
                <label class="radio-card">
                    <input type="radio" name="hinh_thuc" value="ctsv" <?= $hinhThucInput === 'ctsv' ? 'checked' : '' ?>>
                    <div class="radio-card-content">
                        <span class="radio-card-title">Sinh viên mới khóa 2026</span>
                        <span class="radio-card-desc">Sau khi gửi yêu cầu khôi phục mật khẩu, SV liên hệ với phòng CTSV để nhận mật khẩu mới.</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Dòng 5: Cụm nút hành động -->
        <div class="form-row">
            <div></div>
            <div class="btn-actions-row">
                <button type="submit" class="btn-recover">Khôi phục mật khẩu</button>
                <a href="index.php" class="btn-login">Đăng nhập</a>
            </div>
        </div>
    </form>
</div>

</body>
</html>
