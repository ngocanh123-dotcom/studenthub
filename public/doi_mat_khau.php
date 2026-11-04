<?php
/**
 * public/doi_mat_khau.php
 * Module Đổi mật khẩu cá nhân cho Sinh viên, Giảng viên & Quản trị viên
 * Hỗ trợ cả AJAX Fetch (từ Modal Sidebar) và Giao diện Web độc lập
 * Chuẩn bảo mật: Bcrypt password_hash, Session ownership & CSRF Protection
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/Core/Auth.php';
Auth::startSession();

// 1. KIỂM TRA ĐĂNG NHẬP
$userId   = (int)($_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 0);
$username = (string)($_SESSION['username'] ?? $_SESSION['ten_dang_nhap'] ?? '');
$hoTen    = (string)($_SESSION['ho_ten'] ?? $username);
$role     = (string)($_SESSION['role'] ?? $_SESSION['vai_tro'] ?? 'sinhvien');

$isAjax = (
    isset($_POST['ajax']) ||
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
);

if ($userId <= 0 && $username === '') {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'status' => 'error', 'message' => 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại!']);
        exit;
    }
    header('Location: /studenthub/public/index.php?error=login_required');
    exit;
}

// 2. KẾT NỐI DATABASE
$conn = null;
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
    $conn = $conn ?? $pdo ?? null;
}

if (!$conn instanceof PDO) {
    $dbPassCandidates = ['123456', ''];
    foreach ($dbPassCandidates as $candidate) {
        try {
            $conn = new PDO(
                "mysql:host=localhost;dbname=studenthub;charset=utf8mb4",
                "root",
                $candidate,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            break;
        } catch (PDOException $e) {
            // try next
        }
    }
}
$pdo = $conn;

// 3. KHỞI TẠO VÀ ĐỒNG BỘ CSRF TOKEN
$csrfToken = Auth::csrfToken();
if (!headers_sent()) {
    setcookie('sh_csrf_token', $csrfToken, [
        'expires'  => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) === 'on',
        'httponly' => false,
        'samesite' => 'Lax'
    ]);
}

$errorMsg   = '';
$successMsg = '';

// 4. XỬ LÝ ĐỔI MẬT KHẨU
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 4.1. KIỂM TRA BẢO MẬT CSRF TOKEN
    $submittedCsrf = (string)(
        $_POST['csrf_token']
        ?? $_POST['csrf']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? ''
    );

    if ($submittedCsrf === '' && $isAjax) {
        $rawInput = @file_get_contents('php://input');
        if ($rawInput) {
            $jsonData = @json_decode($rawInput, true);
            if (is_array($jsonData)) {
                $submittedCsrf = (string)($jsonData['csrf_token'] ?? $jsonData['csrf'] ?? '');
            }
        }
    }

    $isCsrfValid = !empty($submittedCsrf) && hash_equals($csrfToken, $submittedCsrf);

    if (!$isCsrfValid && $isAjax) {
        $cookieCsrf = (string)($_COOKIE['sh_csrf_token'] ?? '');
        if (!empty($cookieCsrf) && hash_equals($csrfToken, $cookieCsrf)) {
            $isCsrfValid = true;
        }
    }

    if (!$isCsrfValid) {
        $errorMsg = 'Lỗi bảo mật: CSRF Token không hợp lệ hoặc phiên làm việc đã hết hạn!';
    } else {
        $oldPass     = (string)($_POST['old_password'] ?? '');
        $newPass     = (string)($_POST['new_password'] ?? '');
        $confirmPass = (string)($_POST['confirm_password'] ?? '');

        $policyRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';

        if ($oldPass === '' || $newPass === '' || $confirmPass === '') {
            $errorMsg = 'Vui lòng nhập đầy đủ các trường thông tin!';
        } elseif (strlen($newPass) < 8) {
            $errorMsg = 'Mật khẩu mới phải có tối thiểu 8 ký tự!';
        } elseif (!preg_match($policyRegex, $newPass)) {
            $errorMsg = 'Mật khẩu mới phải bao gồm ít nhất 1 chữ hoa, 1 chữ thường, 1 chữ số và 1 ký tự đặc biệt (!@#$%^&*...)!';
        } elseif ($newPass === '123456' || strtolower($newPass) === 'password') {
            $errorMsg = 'Mật khẩu mới không được là mật khẩu mặc định (123456)!';
        } elseif ($newPass !== $confirmPass) {
            $errorMsg = 'Xác nhận mật khẩu mới không khớp!';
        } elseif ($newPass === $oldPass) {
            $errorMsg = 'Mật khẩu mới không được trùng với mật khẩu hiện tại!';
        } else {
            // Truy vấn lấy đúng bản ghi hiện tại
            $user = null;
            if ($pdo instanceof PDO) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE id = :id OR ten_dang_nhap = :u LIMIT 1");
                    $stmt->execute([':id' => $userId, ':u' => $username]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    error_log("Lỗi truy vấn người dùng đổi mật khẩu: " . $e->getMessage());
                }
            }

            if (!$user) {
                $errorMsg = 'Không tìm thấy thông tin tài khoản trong cơ sở dữ liệu!';
            } else {
                // Kiểm tra mật khẩu cũ
                $isOldPassValid = false;
                $dbHash = (string)($user['mat_khau_hash'] ?? '');
                if (!empty($dbHash) && password_verify($oldPass, $dbHash)) {
                    $isOldPassValid = true;
                } elseif ($oldPass === $dbHash || $oldPass === ($user['mat_khau'] ?? '') || md5($oldPass) === $dbHash) {
                    $isOldPassValid = true;
                }

                if (!$isOldPassValid) {
                    $errorMsg = 'Mật khẩu hiện tại không chính xác!';
                } else {
                    // Cập nhật MẬT KHẨU MỚI (Bắt buộc mã hóa Bcrypt một chiều)
                    $hashMoi = password_hash($newPass, PASSWORD_DEFAULT);
                    $updateSql = "UPDATE nguoi_dung SET mat_khau_hash = :hash";
                    $params = [':hash' => $hashMoi, ':target_id' => $user['id']];
                    if (array_key_exists('mat_khau', $user)) {
                        $updateSql .= ", mat_khau = :hash_plain";
                        $params[':hash_plain'] = $hashMoi;
                    }
                    $updateSql .= " WHERE id = :target_id";

                    try {
                        $stmtUpdate = $pdo->prepare($updateSql);
                        $stmtUpdate->execute($params);

                        if ($stmtUpdate->rowCount() >= 0) {
                            unset($_SESSION['must_change_password'], $_SESSION['first_login_alert']);
                            setcookie('sh_must_change_password', '', ['expires' => time() - 3600, 'path' => '/']);

                            if ($isAjax) {
                                header('Content-Type: application/json; charset=utf-8');
                                echo json_encode([
                                    'ok'      => true,
                                    'status'  => 'success',
                                    'message' => 'Đổi mật khẩu thành công! Hãy đăng nhập lại bằng mật khẩu mới.'
                                ]);
                                exit;
                            }
                            $successMsg = 'Đổi mật khẩu thành công! Hãy đăng nhập lại bằng mật khẩu mới.';
                        } else {
                            $errorMsg = 'Lỗi hệ thống khi cập nhật mật khẩu mới!';
                        }
                    } catch (PDOException $e) {
                        error_log("Lỗi cập nhật mật khẩu mới: " . $e->getMessage());
                        $errorMsg = 'Lỗi hệ thống khi cập nhật mật khẩu mới!';
                    }
                }
            }
        }
    }

    if ($isAjax && $errorMsg !== '') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'      => false,
            'status'  => 'error',
            'message' => $errorMsg
        ]);
        exit;
    }
}

// 5. ĐIỀU HƯỚNG QUAY LẠI THEO VAI TRÒ
$backUrl = '/studenthub/public/index.php';
$roleClean = strtolower(trim((string)$role));
if (in_array($roleClean, ['sinhvien', 'sinh_vien'])) {
    $backUrl = '/studenthub/app/Views/sinhvien/index.php';
} elseif (in_array($roleClean, ['giangvien', 'giang_vien'])) {
    $backUrl = '/studenthub/app/Views/giangvien/index.php';
} elseif (in_array($roleClean, ['admin', 'quantri', 'quan_tri'])) {
    $backUrl = '/studenthub/app/Views/admin/index.php';
}
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
        <p>Xin chào <strong><?= htmlspecialchars($hoTen) ?></strong> (<?= htmlspecialchars($username) ?>)</p>
    </div>

    <?php if ($errorMsg !== ''): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <?php if ($successMsg !== ''): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>

    <form method="POST" action="doi_mat_khau.php">
        <input type="hidden" name="csrf_token" id="modal_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

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

        <a href="<?= htmlspecialchars($backUrl) ?>" class="back-link">
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