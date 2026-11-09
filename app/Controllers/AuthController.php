<?php
/**
 * app/Controllers/AuthController.php
 * Controller xử lý đăng nhập, đăng xuất, đổi mật khẩu và khôi phục mật khẩu - StudentHub
 * Kiến trúc: MVC Chuẩn công nghiệp - PHP 8.x - PDO Prepared Statements
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Core/Auth.php';

class AuthController
{
    private PDO $pdo;
    private UserModel $userModel;

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

        $this->userModel = new UserModel($this->pdo);
    }

    /**
     * Điều hướng người dùng đã đăng nhập về trang chủ tương ứng theo vai trò
     */
    private function redirectByRole(string $role): void
    {
        $normalized = strtolower(trim($role));

        if (in_array($normalized, ['admin', 'quantri', 'quan_tri', 'quan_tri_vien'], true)) {
            header('Location: index.php?route=admin_dashboard');
            exit;
        }

        if (in_array($normalized, ['giang_vien', 'giangvien'], true)) {
            header('Location: index.php?route=giang_vien');
            exit;
        }

        header('Location: index.php?route=dashboard');
        exit;
    }

    /**
     * Hiển thị giao diện Đăng nhập hệ thống (GET)
     * Nếu đã có Session đăng nhập hợp lệ thì tự động chuyển hướng theo vai trò
     */
    public function showLoginForm(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Nếu gọi POST trực tiếp vào showLoginForm thì chuyển sang xử lý login()
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->login();
            return;
        }

        // 1. Kiểm tra nếu người dùng đã đăng nhập thì tự động điều hướng
        $userId = (int)($_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 0);
        $sessionRole = strtolower(trim((string)($_SESSION['vai_tro'] ?? $_SESSION['role'] ?? '')));
        if ($userId > 0 && $sessionRole !== '') {
            $this->redirectByRole($sessionRole);
        }

        // 2. Lấy thông báo Flash message hoặc tham số GET error
        $thongBao = '';
        $loaiThongBao = '';
        $username = (string)($_SESSION['login_old_username'] ?? '');
        $role = 'sinhvien';

        if (!empty($_SESSION['login_error'])) {
            $thongBao = (string)$_SESSION['login_error'];
            $loaiThongBao = 'error';
            unset($_SESSION['login_error']);
        } elseif (!empty($_SESSION['login_success'])) {
            $thongBao = (string)$_SESSION['login_success'];
            $loaiThongBao = 'success';
            unset($_SESSION['login_success']);
        }
        unset($_SESSION['login_old_username']);

        $loiGet = (string)($_GET['error'] ?? '');
        if ($thongBao === '' && $loiGet !== '') {
            $thongBaoGet = [
                'login_required' => 'Vui lòng đăng nhập để sử dụng chức năng này.',
                'forbidden'      => 'Bạn không có quyền truy cập khu vực này.'
            ];
            if (isset($thongBaoGet[$loiGet])) {
                $thongBao = $thongBaoGet[$loiGet];
                $loaiThongBao = 'error';
            }
        }

        // 3. Nạp View đăng nhập
        require __DIR__ . '/../Views/auth/login.php';
    }

    /**
     * Xử lý xác thực đăng nhập người dùng (POST)
     */
    public function login(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->showLoginForm();
            return;
        }

        $username = trim((string)($_POST['username'] ?? $_POST['ten_dang_nhap'] ?? ''));
        $password = trim((string)($_POST['password'] ?? $_POST['mat_khau'] ?? ''));

        if ($username === '' || $password === '') {
            $_SESSION['login_error'] = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
            $_SESSION['login_old_username'] = $username;
            header('Location: index.php?route=login');
            exit;
        }

        try {
            $taiKhoan = $this->userModel->authenticate($username, $password);
        } catch (PDOException $e) {
            error_log('Lỗi truy vấn đăng nhập: ' . $e->getMessage());
            $_SESSION['login_error'] = 'Có lỗi xảy ra khi xử lý đăng nhập. Vui lòng thử lại sau.';
            $_SESSION['login_old_username'] = $username;
            header('Location: index.php?route=login');
            exit;
        }

        if (!$taiKhoan) {
            $_SESSION['login_error'] = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
            $_SESSION['login_old_username'] = $username;
            header('Location: index.php?route=login');
            exit;
        }

        // Đăng nhập thành công: Khởi tạo và đồng bộ Session
        session_regenerate_id(true);

        // Kiểm tra mật khẩu mặc định ('123456') để bật cờ yêu cầu đổi mật khẩu
        $isDefaultPass = ($password === '123456');
        if (!$isDefaultPass && !empty($taiKhoan['mat_khau_hash'])) {
            $isDefaultPass = password_verify('123456', (string)$taiKhoan['mat_khau_hash']);
        }

        if ($isDefaultPass) {
            $_SESSION['must_change_password'] = true;
            $_SESSION['first_login_alert'] = 'Cảnh báo bảo mật quan trọng: Mật khẩu của bạn là mật khẩu mặc định (123456). Vui lòng đổi mật khẩu để bảo vệ thông tin cá nhân và đảm bảo không ai (kể cả Admin) biết mật khẩu của bạn!';
            if (!headers_sent()) {
                setcookie('sh_must_change_password', '1', [
                    'expires'  => 0,
                    'path'     => '/',
                    'secure'   => isset($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) === 'on',
                    'httponly' => false,
                    'samesite' => 'Lax'
                ]);
            }
        } else {
            unset($_SESSION['must_change_password'], $_SESSION['first_login_alert']);
            if (!headers_sent()) {
                setcookie('sh_must_change_password', '', [
                    'expires'  => time() - 3600,
                    'path'     => '/',
                    'secure'   => isset($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) === 'on',
                    'httponly' => false,
                    'samesite' => 'Lax'
                ]);
            }
        }

        // Ánh xạ chuẩn hóa các key phân quyền ('sinh_vien', 'giang_vien', 'admin')
        $dbRole = strtolower(trim((string)($taiKhoan['vai_tro'] ?? 'sinh_vien')));
        $roleMap = [
            'sinh_vien'  => 'sinhvien',
            'sinhvien'   => 'sinhvien',
            'giang_vien' => 'giangvien',
            'giangvien'  => 'giangvien',
            'admin'      => 'quantri',
            'quantri'    => 'quantri'
        ];
        $role = $roleMap[$dbRole] ?? 'sinhvien';

        $vaiTroDatabaseVal = [
            'sinhvien'  => 'sinh_vien',
            'giangvien' => 'giang_vien',
            'quantri'   => 'admin'
        ];
        $vaiTroChuan = $vaiTroDatabaseVal[$role] ?? 'sinh_vien';

        $tenVaiTroMap = [
            'sinhvien'  => 'Sinh viên',
            'giangvien' => 'Giảng viên',
            'quantri'   => 'Quản trị viên'
        ];

        $_SESSION['user_id']       = (int)$taiKhoan['id'];
        $_SESSION['ten_dang_nhap'] = (string)$taiKhoan['ten_dang_nhap'];
        $_SESSION['ho_ten']        = (string)$taiKhoan['ho_ten'];
        $_SESSION['vai_tro']       = (string)$vaiTroChuan; // 'sinh_vien', 'giang_vien', 'admin'
        $_SESSION['nguoi_dung_id'] = (int)$taiKhoan['id'];
        $_SESSION['username']      = (string)$taiKhoan['ten_dang_nhap'];
        $_SESSION['role']          = (string)$role;        // 'sinhvien', 'giangvien', 'quantri'
        $_SESSION['tenVaiTro']     = $tenVaiTroMap[$role] ?? 'Thành viên';
        $_SESSION['csrf_token']    = bin2hex(random_bytes(32));
        $_SESSION['nguoi_dung']    = [
            'id'            => (int)$taiKhoan['id'],
            'ten_dang_nhap' => (string)$taiKhoan['ten_dang_nhap'],
            'ho_ten'        => (string)$taiKhoan['ho_ten'],
            'vai_tro'       => (string)$vaiTroChuan,
            'email'         => (string)($taiKhoan['email'] ?? '')
        ];

        session_write_close();
        $this->redirectByRole($vaiTroChuan);
    }

    /**
     * Đăng xuất khỏi hệ thống: Xóa sạch Session và chuyển hướng về trang login
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        header('Location: index.php?route=login');
        exit;
    }

    /**
     * Chức năng đổi mật khẩu cá nhân cho Sinh viên, Giảng viên & Quản trị viên
     * Hỗ trợ cả AJAX Fetch (từ Modal Sidebar) và Giao diện Web độc lập
     * Chuẩn bảo mật: Bcrypt password_hash, Session ownership & CSRF Protection
     */
    public function changePassword(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        Auth::startSession();

        // 1. Kiểm tra trạng thái đăng nhập
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
                echo json_encode([
                    'ok'      => false,
                    'status'  => 'error',
                    'message' => 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại!'
                ]);
                exit;
            }
            header('Location: /studenthub/public/index.php?error=login_required');
            exit;
        }

        // Tự động tìm user_id nếu session chỉ lưu username
        if ($userId <= 0 && $username !== '') {
            $userRecord = $this->userModel->findByUsername($username);
            if ($userRecord) {
                $userId = (int)$userRecord['id'];
                $_SESSION['user_id'] = $userId;
                if (empty($hoTen)) {
                    $hoTen = $userRecord['ho_ten'] ?? $username;
                    $_SESSION['ho_ten'] = $hoTen;
                }
            }
        }

        // 2. Khởi tạo và đồng bộ CSRF Token
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

        // 3. Xử lý đổi mật khẩu khi có yêu cầu POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                    // Kiểm tra mật khẩu hiện tại qua Model
                    if (!$this->userModel->verifyCurrentPassword($userId, $oldPass)) {
                        $errorMsg = 'Mật khẩu hiện tại không chính xác!';
                    } else {
                        // Cập nhật mật khẩu mới băm Bcrypt qua Model
                        $hashMoi = password_hash($newPass, PASSWORD_DEFAULT);
                        $updated = $this->userModel->updatePassword($userId, $hashMoi);

                        if ($updated) {
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

        // 4. Xác định URL quay lại theo vai trò người dùng
        $backUrl = '/studenthub/public/index.php';
        $roleClean = strtolower(trim((string)$role));
        if (in_array($roleClean, ['sinhvien', 'sinh_vien'])) {
            $backUrl = '/studenthub/public/index.php?route=dashboard';
        } elseif (in_array($roleClean, ['giangvien', 'giang_vien'])) {
            $backUrl = '/studenthub/public/index.php?route=giang_vien';
        } elseif (in_array($roleClean, ['admin', 'quantri', 'quan_tri'])) {
            $backUrl = '/studenthub/public/index.php?route=admin_dashboard';
        }

        // 5. Nạp giao diện View sạch
        require __DIR__ . '/../Views/auth/doi_mat_khau.php';
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
                                    $this->userModel->updatePassword((int)$user['id'], $newHash);

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
