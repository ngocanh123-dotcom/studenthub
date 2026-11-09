<?php
/**
 * app/Views/auth/doi_mat_khau.php
 * Giao diện độc lập cho chức năng Đổi mật khẩu tài khoản - StudentHub
 * Kiến trúc: MVC Chuẩn công nghiệp
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu tài khoản | StudentHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(226, 232, 240, 0.8);
            width: 100%;
            max-width: 440px;
            padding: 32px 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 22px;
        }
        .header-icon {
            font-size: 28px;
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: #eff6ff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }
        .header h1 {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .header p {
            font-size: 13px;
            color: #64748b;
        }
        .alert {
            padding: 11px 14px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 18px;
            line-height: 1.45;
        }
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .field {
            margin-bottom: 16px;
        }
        .field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input {
            width: 100%;
            height: 44px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 0 42px 0 14px;
            font-size: 14px;
            font-family: inherit;
            color: #0f172a;
            background: #f8fafc;
            transition: all 0.15s;
        }
        .input:focus {
            outline: none;
            border-color: #2563eb;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
        .eye-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #64748b;
            padding: 4px;
        }
        .btn-submit {
            width: 100%;
            height: 46px;
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.15s;
            margin-top: 14px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            transform: translateY(-1px);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            margin-top: 16px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            transition: color 0.15s;
        }
        .back-link:hover {
            color: #2563eb;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="header">
        <div class="header-icon">🔑</div>
        <h1>Đổi mật khẩu tài khoản</h1>
        <p>Xin chào <strong><?= htmlspecialchars($hoTen ?? '', ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($username ?? '', ENT_QUOTES, 'UTF-8') ?>)</p>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="/studenthub/public/index.php?route=doi_mat_khau.php">
        <input type="hidden" name="csrf_token" id="modal_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="field">
            <label for="old_password">Mật khẩu hiện tại</label>
            <div class="input-wrap">
                <input type="password" id="old_password" name="old_password" class="input" placeholder="Nhập mật khẩu đang dùng" required autofocus>
                <button type="button" class="eye-btn" onclick="togglePass('old_password', this)" title="Hiện/Ẩn">👁</button>
            </div>
        </div>

        <div class="field">
            <label for="new_password">Mật khẩu mới</label>
            <div class="input-wrap">
                <input type="password" id="new_password" name="new_password" class="input" placeholder="Tối thiểu 8 ký tự (hoa, thường, số, ký tự đặc biệt)" minlength="8" required>
                <button type="button" class="eye-btn" onclick="togglePass('new_password', this)" title="Hiện/Ẩn">👁</button>
            </div>
            <small style="color: #64748b; font-size: 11.5px; margin-top: 4px; display: block;">Mật khẩu gồm ít nhất 8 ký tự: 1 chữ hoa (A-Z), 1 chữ thường (a-z), 1 chữ số (0-9) và 1 ký tự đặc biệt (!@#$...).</small>
        </div>

        <div class="field">
            <label for="confirm_password">Xác nhận mật khẩu mới</label>
            <div class="input-wrap">
                <input type="password" id="confirm_password" name="confirm_password" class="input" placeholder="Nhập lại mật khẩu mới" minlength="6" required>
                <button type="button" class="eye-btn" onclick="togglePass('confirm_password', this)" title="Hiện/Ẩn">👁</button>
            </div>
        </div>

        <button type="submit" class="btn-submit">Lưu thay đổi mật khẩu</button>

        <a href="<?= htmlspecialchars($backUrl ?? '/studenthub/public/index.php', ENT_QUOTES, 'UTF-8') ?>" class="back-link">
            &larr; Quay lại trang điều khiển
        </a>
    </form>
</div>

<script>
function togglePass(id, btn) {
    const input = document.getElementById(id);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.textContent = isPass ? '🔒' : '👁';
}
</script>

</body>
</html>
