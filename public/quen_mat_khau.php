<?php
/**
 * public/quen_mat_khau.php
 * Chức năng khôi phục lại mật khẩu của sinh viên - Cổng đào tạo tín chỉ StudentHub
 * Tích hợp thư viện PHPMailer kết nối máy chủ smtp.gmail.com (cổng 587 TLS, App Password)
 * Chuẩn bảo mật: Bcrypt password_hash, PDO Prepared Statements, CSRF Protection
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. NẠP THƯ VIỆN PHPMAILER
require_once __DIR__ . '/../app/Libraries/PHPMailer/Exception.php';
require_once __DIR__ . '/../app/Libraries/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../app/Libraries/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 2. KẾT NỐI DATABASE
$conn = null;
$dbFile = __DIR__ . '/../config/database.php';
if (file_exists($dbFile)) {
    require_once $dbFile;
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
            // Thử mật khẩu tiếp theo
        }
    }
}
$pdo = $conn;

// 3. CSRF TOKEN BẢO VỆ FORM
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Khởi tạo các biến phản hồi & form state (Đã loại bỏ hoàn toàn trường ngày tháng năm sinh)
$thongBaoLoi = '';
$thongBaoThanhCong = '';
$thongBaoThongTin = '';
$maSvInput = '';
$hoTenInput = '';
$sdtInput = '';
$hinhThucInput = 'email';

// 4. XỬ LÝ SUBMIT YÊU CẦU KHÔI PHỤC MẬT KHẨU
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($csrfToken, $submittedCsrf)) {
        $thongBaoLoi = 'Lỗi bảo mật: CSRF Token không hợp lệ. Vui lòng thử lại!';
    } else {
        $maSvInput     = trim($_POST['ma_sinh_vien'] ?? '');
        $hoTenInput    = trim($_POST['ho_ten'] ?? '');
        $sdtInput      = trim($_POST['so_dien_thoai'] ?? '');
        $hinhThucInput = trim($_POST['hinh_thuc'] ?? 'email');

        if ($maSvInput === '' || $hoTenInput === '') {
            $thongBaoLoi = 'Vui lòng nhập đầy đủ Mã sinh viên và Họ và tên!';
        } elseif ($hinhThucInput === 'ctsv') {
            $thongBaoThongTin = 'Yêu cầu của bạn đã được ghi nhận trên hệ thống. Đối với <strong>Sinh viên mới khóa 2026</strong>, vui lòng mang theo Căn cước công dân hoặc Thẻ sinh viên đến trực tiếp <strong>Phòng Công tác sinh viên (CTSV)</strong> trong giờ hành chính để được hỗ trợ cấp mật khẩu mới!';
        } else {
            // 4.1. Xác thực sinh viên trong CSDL (Khớp mã sinh viên và họ tên)
            if (!$pdo instanceof PDO) {
                $thongBaoLoi = 'Không thể kết nối cơ sở dữ liệu. Vui lòng liên hệ quản trị viên!';
            } else {
                try {
                    // 1. Tìm user theo mã sinh viên (không phân biệt hoa thường, cắt khoảng trắng)
                    $stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE LOWER(TRIM(ten_dang_nhap)) = LOWER(TRIM(:ma_sv)) LIMIT 1");
                    $stmt->execute([':ma_sv' => $maSvInput]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $thongBaoLoi = "Mã sinh viên không tồn tại trên hệ thống!";
                    } else {
                        // 2. Chuẩn hóa họ tên: chuyển chữ thường UTF-8, loại bỏ khoảng trắng kép
                        $dbName = mb_strtolower(preg_replace('/\s+/', ' ', trim($user['ho_ten'] ?? '')), 'UTF-8');
                        $inputName = mb_strtolower(preg_replace('/\s+/', ' ', trim($hoTenInput)), 'UTF-8');

                        // Nếu họ tên người dùng nhập có chứa trong tên CSDL hoặc khớp tương đối
                        if ($dbName !== $inputName && !str_contains($dbName, $inputName) && !str_contains($inputName, $dbName)) {
                            $thongBaoLoi = "Họ và tên không khớp với hồ sơ của mã sinh viên " . htmlspecialchars($maSvInput) . "!";
                        } elseif (empty($user['email'])) {
                            $thongBaoLoi = "Tài khoản này chưa được cập nhật địa chỉ Email trên hệ thống. Vui lòng liên hệ phòng Đào tạo!";
                        } else {
                        // 4.2. Sinh mật khẩu mới ngẫu nhiên (Đạt chuẩn chính sách mật khẩu mạnh)
                        $specialChars = ['@', '#', '!', '$'];
                        $pickedChar   = $specialChars[array_rand($specialChars)];
                        $randDigits   = str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
                        $randChar     = chr(random_int(65, 90)); // A-Z
                        $matKhauMoi   = trim('Hub' . $pickedChar . $randDigits . $randChar);

                        // Băm Bcrypt một chiều mật khẩu mới
                        $newHash = password_hash($matKhauMoi, PASSWORD_DEFAULT);

                        // 4.3. CẤU HÌNH VÀ GỬI EMAIL QUA PHPMAILER VỚI MÁY CHỦ SMTP GMAIL
                        $mailConfig = file_exists(__DIR__ . '/../config/mail.php')
                            ? require __DIR__ . '/../config/mail.php'
                            : [];

                        $smtpUsername = !empty($mailConfig['username']) ? $mailConfig['username'] : 'huyennguyensocute.06@gmail.com';
                        $smtpPassword = !empty($mailConfig['password']) ? $mailConfig['password'] : 'xdsi zpyn hfyd rrev';

                        $targetEmail = !empty($user['email']) 
                            ? $user['email'] 
                            : (strtolower($user['ten_dang_nhap']) . '@studenthub.edu.vn');

                        $mail = new PHPMailer(true);

                        try {
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = $smtpUsername; // Email dùng để gửi: huyennguyensocute.06@gmail.com
                            $mail->Password   = $smtpPassword; // Mật khẩu ứng dụng 16 ký tự của Google
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;
                            $mail->CharSet    = 'UTF-8';
                            $mail->Timeout    = 15;

                            $fromEmail = !empty($mailConfig['from_email']) ? $mailConfig['from_email'] : $smtpUsername;
                            $fromName  = !empty($mailConfig['from_name']) ? $mailConfig['from_name'] : 'Cổng đào tạo StudentHub';

                            $mail->setFrom($fromEmail, $fromName);
                            $mail->addAddress($targetEmail, $user['ho_ten']);
                            $mail->isHTML(true);
                            $mail->Subject = '[StudentHub] Cấp lại mật khẩu tài khoản sinh viên - ' . $user['ten_dang_nhap'];
                            $mail->Body    = "
                            <html>
                            <head><title>Cấp lại mật khẩu StudentHub</title></head>
                            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; background: #f8fafc; padding: 24px;'>
                                <div style='max-width: 600px; margin: 0 auto; background: #ffffff; padding: 28px; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
                                    <h2 style='color: #1d4ed8; border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-top: 0;'>HỆ THỐNG ĐÀO TẠO TÍN CHỈ - STUDENTHUB</h2>
                                    <p>Xin chào <strong>" . htmlspecialchars($user['ho_ten']) . "</strong> (Mã SV: <strong>" . htmlspecialchars($user['ten_dang_nhap']) . "</strong>),</p>
                                    <p>Yêu cầu khôi phục mật khẩu tài khoản của bạn trên hệ thống StudentHub đã được xử lý thành công.</p>
                                    <div style='background: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid #2563eb; padding: 16px; border-radius: 6px; margin: 20px 0;'>
                                        <span style='font-size: 13px; color: #475569; display: block; margin-bottom: 8px;'>Mật khẩu truy cập mới của bạn:</span>
                                        <p style='font-size: 22px; font-weight: bold; color: #dc2626; font-family: monospace; letter-spacing: 2px; margin: 4px 0; background: #ffffff; padding: 8px 14px; border-radius: 6px; display: inline-block; border: 1px dashed #f87171;'>
                                            " . htmlspecialchars($matKhauMoi) . "
                                        </p>
                                        <p style='font-size: 12px; color: #64748b; margin-top: 8px; margin-bottom: 0;'>
                                            <em>* Lưu ý: Khi sao chép mật khẩu, chú ý không quét thừa dấu cách ở đầu hoặc cuối.</em>
                                        </p>
                                    </div>
                                    <p style='color: #dc2626; font-size: 13.5px; font-weight: 600;'>
                                        ⚠️ Lưu ý bảo mật: Vui lòng sử dụng mật khẩu trên để đăng nhập và đổi lại mật khẩu cá nhân ngay để đảm bảo an toàn tuyệt đối cho tài khoản!
                                    </p>
                                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 24px 0 16px;'>
                                    <p style='font-size: 12px; color: #64748b; margin-bottom: 0;'>Thư này được gửi tự động từ máy chủ StudentHub, vui lòng không phản hồi thư này.</p>
                                </div>
                            </body>
                            </html>
                            ";

                            // GỬI EMAIL QUA SMTP GOOGLE
                            // Bắt buộc: Chỉ khi $mail->send() thành công 100% mới tiến hành cập nhật CSDL và hiển thị thông báo thành công màu xanh
                            $mail->send();

                            // 1. Cập nhật mật khẩu mới vào CSDL SAU KHI email đã gửi thành công
                            $updateSql = "UPDATE nguoi_dung SET mat_khau_hash = :hash";
                            $updateParams = [':hash' => $newHash, ':id' => (int)$user['id']];
                            if (array_key_exists('mat_khau', $user)) {
                                $updateSql .= ", mat_khau = :hash_plain";
                                $updateParams[':hash_plain'] = $newHash;
                            }
                            $updateSql .= " WHERE id = :id";
                            $stmtUpdate = $pdo->prepare($updateSql);
                            $stmtUpdate->execute($updateParams);

                            // 2. Chỉ gán thông báo thành công màu xanh khi email gửi thành công
                            $userEmail = !empty($user['email']) ? $user['email'] : $targetEmail;
                            $thongBaoThanhCong = "✓ Yêu cầu khôi phục thành công! Mật khẩu mới đã được cấp và gửi đến email: <strong>" . htmlspecialchars($userEmail) . "</strong>.<br>Vui lòng kiểm tra Hộp thư đến (hoặc mục Thư rác/Spam) để lấy mật khẩu đăng nhập vào hệ thống.";

                        } catch (Exception $e) {
                            // Bắt lỗi chi tiết từ PHPMailer / Google SMTP và gán vào $thongBaoLoi để hiển thị khung đỏ ra màn hình
                            $errorDetail = $mail->ErrorInfo ?: $e->getMessage();
                            $thongBaoLoi = "Lỗi gửi email: " . htmlspecialchars($errorDetail);
                            error_log("PHPMailer SMTP Error: " . $errorDetail);
                        }
                    }
                }
                } catch (PDOException $e) {
                    error_log("Lỗi truy vấn khôi phục mật khẩu: " . $e->getMessage());
                    $thongBaoLoi = "Có lỗi xảy ra trong quá trình xử lý. Vui lòng thử lại sau!";
                }
            }
        }
    }
}
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

        /* Hiệu ứng viền cam/đỏ khi chọn Card 1 và viền xám khi chọn Card 2 */
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
            ⚠️ <?= $thongBaoLoi ?>
        </div>
    <?php endif; ?>

    <?php if ($thongBaoThanhCong !== ''): ?>
        <div class="alert-box alert-success">
            <?= $thongBaoThanhCong ?>
        </div>
    <?php endif; ?>

    <?php if ($thongBaoThongTin !== ''): ?>
        <div class="alert-box alert-info">
            ℹ️ <?= $thongBaoThongTin ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="quen_mat_khau.php" class="recovery-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <!-- Dòng 1: Mã sinh viên -->
        <div class="form-row">
            <label class="form-label" for="ma_sinh_vien">Mã sinh viên:</label>
            <div>
                <input type="text" id="ma_sinh_vien" name="ma_sinh_vien" class="form-input" placeholder="Nhập mã sinh viên" required value="<?= htmlspecialchars($maSvInput) ?>">
            </div>
        </div>

        <!-- Dòng 2: Họ và tên -->
        <div class="form-row">
            <label class="form-label" for="ho_ten">Họ và tên:</label>
            <div>
                <input type="text" id="ho_ten" name="ho_ten" class="form-input" placeholder="Nhập họ và tên" required value="<?= htmlspecialchars($hoTenInput) ?>">
            </div>
        </div>

        <!-- Dòng 3: Số điện thoại (Đã loại bỏ hoàn toàn trường Ngày sinh) -->
        <div class="form-row">
            <label class="form-label" for="so_dien_thoai">Số điện thoại (đăng ký trên hệ thống):</label>
            <div>
                <input type="text" id="so_dien_thoai" name="so_dien_thoai" class="form-input" placeholder="Nhập SĐT đã đăng ký với nhà trường" value="<?= htmlspecialchars($sdtInput) ?>">
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
                <a href="/studenthub/public/index.php" class="btn-login">Đăng nhập</a>
            </div>
        </div>
    </form>
</div>

</body>
</html>
