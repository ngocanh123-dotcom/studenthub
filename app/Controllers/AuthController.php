<?php
/**
 * app/Controllers/AuthController.php
 * Controller xử lý xác thực và phân hệ Quên mật khẩu - StudentHub
 * Kiến trúc: MVC Chuẩn công nghiệp
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class AuthController
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->pdo = $pdo;
        } else {
            $conn = null;
            $dbFile = __DIR__ . '/../../config/database.php';
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

            if (!$conn instanceof PDO) {
                throw new RuntimeException("Không thể kết nối cơ sở dữ liệu StudentHub.");
            }

            $this->pdo = $conn;
        }
    }

    /**
     * Chức năng khôi phục lại mật khẩu của sinh viên
     * Hỗ trợ GET (hiển thị form) và POST (xử lý cấp mật khẩu mới & gửi email)
     */
    public function quenMatKhau(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Tạo CSRF Token bảo vệ biểu mẫu
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_token'];

        $thongBaoLoi = '';
        $thongBaoThanhCong = '';
        $thongBaoThongTin = '';
        $maSvInput = '';
        $hoTenInput = '';
        $sdtInput = '';
        $hinhThucInput = 'email';

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
                    try {
                        // 1. Tìm tài khoản theo mã sinh viên (LOWER TRIM không phân biệt hoa thường)
                        $stmt = $this->pdo->prepare("SELECT * FROM nguoi_dung WHERE LOWER(TRIM(ten_dang_nhap)) = LOWER(TRIM(:ma_sv)) LIMIT 1");
                        $stmt->execute([':ma_sv' => $maSvInput]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$user) {
                            $thongBaoLoi = "Mã sinh viên không tồn tại trên hệ thống!";
                        } else {
                            // 2. Chuẩn hóa họ tên: chuyển chữ thường UTF-8, loại bỏ khoảng trắng kép
                            $dbName = mb_strtolower(preg_replace('/\s+/', ' ', trim($user['ho_ten'] ?? '')), 'UTF-8');
                            $inputName = mb_strtolower(preg_replace('/\s+/', ' ', trim($hoTenInput)), 'UTF-8');

                            if ($dbName !== $inputName && !str_contains($dbName, $inputName) && !str_contains($inputName, $dbName)) {
                                $thongBaoLoi = "Họ và tên không khớp với hồ sơ của mã sinh viên " . htmlspecialchars($maSvInput) . "!";
                            } elseif (empty($user['email'])) {
                                $thongBaoLoi = "Tài khoản này chưa được cập nhật địa chỉ Email trên hệ thống. Vui lòng liên hệ phòng Đào tạo!";
                            } else {
                                // 3. Sinh mật khẩu mới ngẫu nhiên an toàn (Đạt chuẩn chính sách mật khẩu mạnh)
                                $specialChars = ['@', '#', '!', '$'];
                                $pickedChar   = $specialChars[array_rand($specialChars)];
                                $randDigits   = str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
                                $randChar     = chr(random_int(65, 90)); // A-Z
                                $matKhauMoi   = trim('Hub' . $pickedChar . $randDigits . $randChar);

                                // Băm Bcrypt một chiều mật khẩu mới
                                $newHash = password_hash($matKhauMoi, PASSWORD_DEFAULT);

                                // 4. Nạp thư viện PHPMailer
                                require_once __DIR__ . '/../Libraries/PHPMailer/Exception.php';
                                require_once __DIR__ . '/../Libraries/PHPMailer/PHPMailer.php';
                                require_once __DIR__ . '/../Libraries/PHPMailer/SMTP.php';

                                // Đọc cấu hình mail
                                $mailConfig = file_exists(__DIR__ . '/../../config/mail.php')
                                    ? require __DIR__ . '/../../config/mail.php'
                                    : [];

                                $smtpUsername = !empty($mailConfig['username']) ? $mailConfig['username'] : 'huyennguyensocute.06@gmail.com';
                                $smtpPassword = !empty($mailConfig['password']) ? $mailConfig['password'] : 'xdsi zpyn hfyd rrev';
                                $targetEmail  = !empty($user['email']) 
                                    ? $user['email'] 
                                    : (strtolower($user['ten_dang_nhap']) . '@studenthub.edu.vn');

                                $mail = new PHPMailer(true);

                                try {
                                    $mail->isSMTP();
                                    $mail->Host       = 'smtp.gmail.com';
                                    $mail->SMTPAuth   = true;
                                    $mail->Username   = $smtpUsername;
                                    $mail->Password   = $smtpPassword;
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

                                    // Chỉ khi gửi email thành công 100% mới cập nhật CSDL
                                    $mail->send();

                                    // Cập nhật CSDL
                                    $updateSql = "UPDATE nguoi_dung SET mat_khau_hash = :hash";
                                    $updateParams = [':hash' => $newHash, ':id' => (int)$user['id']];
                                    if (array_key_exists('mat_khau', $user)) {
                                        $updateSql .= ", mat_khau = :hash_plain";
                                        $updateParams[':hash_plain'] = $newHash;
                                    }
                                    $updateSql .= " WHERE id = :id";
                                    $stmtUpdate = $this->pdo->prepare($updateSql);
                                    $stmtUpdate->execute($updateParams);

                                    $userEmail = !empty($user['email']) ? $user['email'] : $targetEmail;
                                    $thongBaoThanhCong = "✓ Yêu cầu khôi phục thành công! Mật khẩu mới đã được cấp và gửi đến email: <strong>" . htmlspecialchars($userEmail) . "</strong>.<br>Vui lòng kiểm tra Hộp thư đến (hoặc mục Thư rác/Spam) để lấy mật khẩu đăng nhập vào hệ thống.";

                                } catch (\Exception $e) {
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

        // Nạp View hiển thị giao diện
        require __DIR__ . '/../Views/auth/quen_mat_khau.php';
    }
}
