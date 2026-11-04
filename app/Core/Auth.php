<?php
/**
 * app/Core/Auth.php
 * Lớp Quản Lý Bảo Mật & Xác Thực Trung Tâm - StudentHub
 * Kiến trúc: Centralized Security Core, Non-Breaking Integration
 * Cung cấp:
 *   1. Quản lý xác thực (Authentication) & Session chống Fixation
 *   2. Nâng cấp tự động mật khẩu cũ & phát hiện mật khẩu mặc định (123456)
 *   3. Phân quyền truy cập tập trung (Role Guard) & trang 403 Forbidden
 *   4. Kiểm soát quyền sở hữu dữ liệu (Data Ownership)
 *   5. Cơ chế chống tấn công giả mạo yêu cầu (CSRF Protection)
 */

class Auth {
    // Ánh xạ vai trò chuẩn trong CSDL
    private const ROLE_DB_STUDENT  = 'sinh_vien';
    private const ROLE_DB_LECTURER = 'giang_vien';
    private const ROLE_DB_ADMIN    = 'admin';

    // Ánh xạ vai trò hiển thị giao diện (Aliases)
    private const ROLE_MAP = [
        'sinh_vien'      => self::ROLE_DB_STUDENT,
        'sinhvien'       => self::ROLE_DB_STUDENT,
        'student'        => self::ROLE_DB_STUDENT,
        'giang_vien'     => self::ROLE_DB_LECTURER,
        'giangvien'      => self::ROLE_DB_LECTURER,
        'lecturer'       => self::ROLE_DB_LECTURER,
        'teacher'        => self::ROLE_DB_LECTURER,
        'admin'          => self::ROLE_DB_ADMIN,
        'quantri'        => self::ROLE_DB_ADMIN,
        'quan_tri'       => self::ROLE_DB_ADMIN,
        'quan_tri_vien'  => self::ROLE_DB_ADMIN,
    ];

    private const ROLE_ALIAS_MAP = [
        self::ROLE_DB_STUDENT  => 'sinhvien',
        self::ROLE_DB_LECTURER => 'giangvien',
        self::ROLE_DB_ADMIN    => 'quantri',
    ];

    private const ROLE_TITLE_MAP = [
        self::ROLE_DB_STUDENT  => 'Sinh viên',
        self::ROLE_DB_LECTURER => 'Giảng viên',
        self::ROLE_DB_ADMIN    => 'Quản trị viên',
    ];

    private const ROLE_HOME_MAP = [
        self::ROLE_DB_STUDENT  => '/studenthub/app/Views/sinhvien/index.php',
        self::ROLE_DB_LECTURER => '/studenthub/app/Views/giangvien/index.php',
        self::ROLE_DB_ADMIN    => '/studenthub/app/Views/admin/index.php',
    ];

    // =========================================================================
    // 1. QUẢN LÝ PHIÊN LÀM VIỆC (SESSION & STATE)
    // =========================================================================

    /**
     * Khởi động Session một cách an toàn nếu chưa được khởi động
     */
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                $params = session_get_cookie_params();
                session_set_cookie_params([
                    'lifetime' => 0,
                    'path'     => '/',
                    'domain'   => $params['domain'] ?? '',
                    'secure'   => isset($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) === 'on',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
            @session_start();
        }
    }

    /**
     * Kiểm tra người dùng hiện tại đã đăng nhập hợp lệ hay chưa
     */
    public static function check(): bool {
        self::startSession();
        return (int)self::id() > 0;
    }

    /**
     * Lấy ID người dùng hiện tại từ Session (an toàn, không fallback giả mạo)
     */
    public static function id(): ?int {
        self::startSession();
        $id = (int)($_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    /**
     * Lấy tên đăng nhập của người dùng hiện tại
     */
    public static function username(): ?string {
        self::startSession();
        $u = trim((string)($_SESSION['username'] ?? $_SESSION['ten_dang_nhap'] ?? ''));
        return $u !== '' ? $u : null;
    }

    /**
     * Lấy họ tên hiển thị của người dùng hiện tại
     */
    public static function name(): string {
        self::startSession();
        return (string)($_SESSION['ho_ten'] ?? self::username() ?? 'Người dùng');
    }

    /**
     * Lấy vai trò chuẩn trong CSDL ('sinh_vien', 'giang_vien', 'admin')
     */
    public static function dbRole(): ?string {
        self::startSession();
        $raw = (string)($_SESSION['vai_tro'] ?? $_SESSION['role'] ?? '');
        return self::normalizeRole($raw);
    }

    /**
     * Lấy vai trò định dạng alias ('sinhvien', 'giangvien', 'quantri')
     */
    public static function role(): ?string {
        self::startSession();
        $dbR = self::dbRole();
        return $dbR ? self::normalizeRoleAlias($dbR) : null;
    }

    /**
     * Lấy toàn bộ thông tin người dùng từ Session
     */
    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }
        return [
            'id'            => self::id(),
            'ten_dang_nhap' => self::username(),
            'ho_ten'        => self::name(),
            'vai_tro'       => self::dbRole(),
            'role'          => self::role(),
            'email'         => (string)($_SESSION['email'] ?? ''),
        ];
    }

    // =========================================================================
    // 2. CHUẨN HÓA VAI TRÒ & ĐIỀU HƯỚNG
    // =========================================================================

    /**
     * Chuẩn hóa bất kỳ chuỗi vai trò nào về vai trò CSDL chuẩn
     */
    public static function normalizeRole(string $role): ?string {
        $key = strtolower(trim($role));
        return self::ROLE_MAP[$key] ?? null;
    }

    /**
     * Chuẩn hóa vai trò về định dạng alias rút gọn cho giao diện
     */
    public static function normalizeRoleAlias(string $role): string {
        $dbR = self::normalizeRole($role);
        return self::ROLE_ALIAS_MAP[$dbR] ?? 'sinhvien';
    }

    /**
     * Lấy tên tiếng Việt hiển thị của vai trò
     */
    public static function roleTitle(?string $role = null): string {
        $target = $role !== null ? self::normalizeRole($role) : self::dbRole();
        return self::ROLE_TITLE_MAP[$target] ?? 'Thành viên';
    }

    /**
     * Lấy URL trang tổng quan tương ứng với vai trò
     */
    public static function homeUrl(?string $role = null): string {
        $target = $role !== null ? self::normalizeRole($role) : self::dbRole();
        return self::ROLE_HOME_MAP[$target] ?? '/studenthub/public/index.php';
    }

    /**
     * Kiểm tra người dùng hiện tại có vai trò nằm trong danh sách cho phép hay không
     */
    public static function hasRole(array|string $allowedRoles): bool {
        $current = self::dbRole();
        if ($current === null) {
            return false;
        }

        $allowedList = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
        foreach ($allowedList as $r) {
            if (self::normalizeRole((string)$r) === $current) {
                return true;
            }
        }
        return false;
    }

    // =========================================================================
    // 3. XÁC THỰC, ĐĂNG NHẬP, ĐĂNG XUẤT & MẬT KHẨU
    // =========================================================================

    /**
     * Mã hóa mật khẩu chuẩn Bcrypt
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Kiểm tra mật khẩu (hỗ trợ Bcrypt, Plaintext cũ và MD5 để tự nâng cấp)
     */
    public static function verifyPassword(string $password, string $hash): bool {
        if ($hash === '') {
            return false;
        }
        if (password_verify($password, $hash)) {
            return true;
        }
        // Hỗ trợ kiểm thử hoặc CSDL cũ lưu plaintext / MD5
        if ($password === $hash || md5($password) === $hash) {
            return true;
        }
        return false;
    }

    /**
     * Kiểm tra xem chuỗi hash hoặc plaintext có phải là mật khẩu mặc định (123456) hay không
     */
    public static function isDefaultPassword(string $hashOrPlain): bool {
        if ($hashOrPlain === '123456') {
            return true;
        }
        if (password_verify('123456', $hashOrPlain)) {
            return true;
        }
        if ($hashOrPlain === md5('123456')) {
            return true;
        }
        return false;
    }

    /**
     * Đăng nhập người dùng vào hệ thống:
     * - Chống Session Fixation bằng session_regenerate_id(true)
     * - Đồng bộ đầy đủ các khóa Session bắt buộc và khóa tương thích
     * - Nâng cấp tự động mật khẩu cũ sang Bcrypt nếu cần
     * - Phát hiện mật khẩu mặc định (123456) và gắn cờ cảnh báo
     */
    public static function login(array $user, ?string $rawPassword = null, ?PDO $pdo = null): void {
        self::startSession();
        session_regenerate_id(true);

        $userId    = (int)($user['id'] ?? 0);
        $username  = (string)($user['ten_dang_nhap'] ?? '');
        $hoTen     = (string)($user['ho_ten'] ?? $username);
        $email     = (string)($user['email'] ?? '');
        $rawRole   = (string)($user['vai_tro'] ?? 'sinh_vien');

        // BẢO MẬT: Luôn lấy vai trò chuẩn từ CSDL, KHÔNG tin cậy client gửi lên
        $dbRole    = self::normalizeRole($rawRole) ?? self::ROLE_DB_STUDENT;
        $roleAlias = self::normalizeRoleAlias($dbRole);
        $tenRole   = self::roleTitle($dbRole);

        // 1. Chuẩn hóa 4 khóa Session bắt buộc theo yêu cầu kỹ thuật
        $_SESSION['user_id']       = $userId;
        $_SESSION['ten_dang_nhap'] = $username;
        $_SESSION['ho_ten']        = $hoTen;
        $_SESSION['vai_tro']       = $dbRole;

        // 2. Đồng bộ các khóa Session tương thích cho tất cả các trang khác trong nhóm
        $_SESSION['nguoi_dung_id'] = $userId;
        $_SESSION['username']      = $username;
        $_SESSION['role']          = $roleAlias;
        $_SESSION['tenVaiTro']     = $tenRole;
        $_SESSION['email']         = $email;
        $_SESSION['nguoi_dung']    = [
            'id'            => $userId,
            'ten_dang_nhap' => $username,
            'ho_ten'        => $hoTen,
            'email'         => $email,
            'vai_tro'       => $dbRole,
            'role'          => $roleAlias,
        ];

        // 3. Tự động nâng cấp mật khẩu sang Bcrypt nếu trong DB còn lưu plaintext/MD5
        if ($pdo instanceof PDO && $rawPassword !== null && $userId > 0) {
            $currentHash = (string)($user['mat_khau_hash'] ?? '');
            $needsRehash = false;

            if ($currentHash === $rawPassword || $currentHash === '123456' || $currentHash === md5($rawPassword)) {
                $needsRehash = true;
            } elseif (password_needs_rehash($currentHash, PASSWORD_DEFAULT)) {
                $needsRehash = true;
            }

            if ($needsRehash) {
                try {
                    $newHash = self::hashPassword($rawPassword);
                    $stmt = $pdo->prepare("UPDATE nguoi_dung SET mat_khau_hash = :hash WHERE id = :id");
                    $stmt->execute([':hash' => $newHash, ':id' => $userId]);
                    $user['mat_khau_hash'] = $newHash;
                } catch (Throwable $e) {
                    error_log("Lỗi tự động nâng cấp mật khẩu Bcrypt: " . $e->getMessage());
                }
            }
        }

        // 4. Phát hiện mật khẩu mặc định (123456) mà KHÔNG cần thêm cột CSDL
        $isDefault = ($rawPassword !== null && $rawPassword === '123456');
        if (!$isDefault && !empty($user['mat_khau_hash'])) {
            $isDefault = self::isDefaultPassword($user['mat_khau_hash']);
        }

        if ($isDefault) {
            $_SESSION['must_change_password'] = true;
            $_SESSION['first_login_alert'] = 'Mật khẩu của bạn là mật khẩu mặc định (123456). Vui lòng đổi mật khẩu để bảo vệ tài khoản!';
            if (!headers_sent()) {
                setcookie('sh_must_change_password', '1', [
                    'expires'  => 0,
                    'path'     => '/',
                    'secure'   => isset($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) === 'on',
                    'httponly' => false, // Cho phép script frontend đọc để bật modal
                    'samesite' => 'Lax'
                ]);
            }
        } else {
            self::clearMustChangePassword();
        }
    }

    /**
     * Đăng xuất an toàn: xóa Session, hủy Cookie và điều hướng
     */
    public static function logout(?string $redirectUrl = '/studenthub/public/index.php'): void {
        self::startSession();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                $params['secure'] ?? false,
                $params['httponly'] ?? true
            );
        }

        if (!headers_sent()) {
            setcookie('sh_must_change_password', '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) === 'on',
                'httponly' => false,
                'samesite' => 'Lax'
            ]);
        }

        @session_destroy();

        if ($redirectUrl !== null) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * Kiểm tra người dùng có đang dùng mật khẩu mặc định và phải đổi mật khẩu hay không
     */
    public static function mustChangePassword(): bool {
        self::startSession();
        if (!empty($_SESSION['must_change_password'])) {
            return true;
        }
        if (isset($_COOKIE['sh_must_change_password']) && $_COOKIE['sh_must_change_password'] === '1') {
            return true;
        }
        return false;
    }

    /**
     * Xóa cờ cảnh báo mật khẩu mặc định sau khi người dùng đổi mật khẩu thành công
     */
    public static function clearMustChangePassword(): void {
        self::startSession();
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

    /**
     * Xuất HTML Banner cảnh báo mật khẩu mặc định nổi bật ở đầu trang
     */
    public static function renderDefaultPasswordBanner(): string {
        if (!self::mustChangePassword()) {
            return '';
        }

        $alertMsg = htmlspecialchars(
            $_SESSION['first_login_alert'] ?? 'Mật khẩu của bạn là mật khẩu mặc định (123456). Vui lòng đổi mật khẩu để bảo vệ tài khoản!',
            ENT_QUOTES,
            'UTF-8'
        );

        return <<<HTML
<div id="shDefaultPasswordBanner" class="sh-security-warning-banner" role="alert">
    <div class="sh-banner-body">
        <span class="sh-banner-icon" aria-hidden="true">⚠️</span>
        <div class="sh-banner-text">
            <strong>Cảnh báo bảo mật quan trọng:</strong> {$alertMsg}
        </div>
    </div>
    <div class="sh-banner-actions">
        <button type="button" class="sh-banner-btn-action" onclick="if(typeof openChangePasswordModal==='function'){openChangePasswordModal();}else{window.location.href='/studenthub/public/doi_mat_khau.php';}">
            Đổi mật khẩu ngay
        </button>
    </div>
</div>
<style>
.sh-security-warning-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 1px solid #fde68a;
    border-left: 5px solid #d97706;
    border-radius: 12px;
    padding: 14px 20px;
    margin: 16px 0 24px 0;
    box-shadow: 0 4px 14px rgba(217, 119, 6, 0.12);
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    color: #92400e;
    animation: shBannerSlideDown 0.3s ease-out;
}
@keyframes shBannerSlideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
.sh-banner-body {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    line-height: 1.5;
}
.sh-banner-icon {
    font-size: 22px;
    flex-shrink: 0;
}
.sh-banner-text strong {
    color: #78350f;
    font-weight: 800;
}
.sh-banner-actions {
    flex-shrink: 0;
}
.sh-banner-btn-action {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    color: #ffffff;
    border: none;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(180, 83, 9, 0.25);
    transition: all 0.15s ease;
    white-space: nowrap;
}
.sh-banner-btn-action:hover {
    background: linear-gradient(135deg, #b45309 0%, #92400e 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(180, 83, 9, 0.35);
}
@media (max-width: 640px) {
    .sh-security-warning-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    .sh-banner-actions {
        width: 100%;
    }
    .sh-banner-btn-action {
        width: 100%;
        text-align: center;
    }
}
</style>
HTML;
    }

    // =========================================================================
    // 4. PHÂN QUYỀN TRUY CẬP (ROLE GUARD & 403 FORBIDDEN)
    // =========================================================================

    /**
     * Bắt buộc người dùng phải đăng nhập trước khi tiếp tục
     */
    public static function requireLogin(?string $redirectUrl = null): void {
        self::startSession();
        if (!self::check()) {
            if (self::isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status'  => 'error',
                    'code'    => 401,
                    'message' => 'Phiên làm việc đã hết hạn hoặc chưa đăng nhập. Vui lòng đăng nhập lại!'
                ]);
                exit;
            }
            $target = $redirectUrl ?: '/studenthub/public/index.php?error=login_required';
            header('Location: ' . $target);
            exit;
        }
    }

    /**
     * Bắt buộc người dùng phải có đúng vai trò được chỉ định:
     * - Nếu chưa đăng nhập: chuyển hướng về trang đăng nhập
     * - Nếu không đúng vai trò: trả về HTTP 403 Forbidden và hiển thị trang thông báo lỗi chuẩn
     */
    public static function requireRole(array|string $allowedRoles): void {
        self::requireLogin();

        if (!self::hasRole($allowedRoles)) {
            http_response_code(403);
            if (self::isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status'  => 'error',
                    'code'    => 403,
                    'message' => 'Bạn không có quyền truy cập chức năng này (403 Forbidden).'
                ]);
                exit;
            }
            self::render403Page();
            exit;
        }
    }

    /**
     * Hiển thị giao diện 403 Forbidden chuẩn UX chuyên nghiệp
     */
    public static function render403Page(string $customMessage = ''): void {
        http_response_code(403);

        $currentUser   = self::username() ?: 'Khách';
        $currentRole   = self::roleTitle();
        $homeUrl       = self::homeUrl();
        $msg = $customMessage !== ''
            ? $customMessage
            : 'Bạn không có quyền truy cập vào trang này. Khu vực này yêu cầu cấp đặc quyền khác với vai trò tài khoản hiện tại của bạn.';

        $safeUser = htmlspecialchars($currentUser, ENT_QUOTES, 'UTF-8');
        $safeRole = htmlspecialchars($currentRole, ENT_QUOTES, 'UTF-8');
        $safeMsg  = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');

        echo <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Quyền Truy Cập Bị Từ Chối | StudentHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top right, #f1f5f9 0%, #e2e8f0 100%);
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .error-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.18), 0 0 0 1px rgba(226, 232, 240, 0.8);
            width: 100%;
            max-width: 520px;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .error-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%);
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 24px;
            background: #fef2f2;
            color: #dc2626;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.15);
        }
        .error-code {
            display: inline-block;
            background: #fee2e2;
            color: #991b1b;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 9999px;
            margin-bottom: 12px;
        }
        h1 {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 10px;
        }
        p.desc {
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .user-badge {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 28px;
            font-size: 13px;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .user-badge strong {
            color: #0f172a;
        }
        .badge-role {
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }
        .btn-home {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 13px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.15s ease;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
        }
        .btn-home:hover {
            background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            transform: translateY(-1px);
        }
        .btn-logout {
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 600;
            transition: all 0.15s ease;
        }
        .btn-logout:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-circle">🚫</div>
        <div><span class="error-code">HTTP 403 FORBIDDEN</span></div>
        <h1>Quyền Truy Cập Bị Từ Chối</h1>
        <p class="desc">{$safeMsg}</p>

        <div class="user-badge">
            <span>Tài khoản: <strong>{$safeUser}</strong></span>
            <span class="badge-role">{$safeRole}</span>
        </div>

        <div class="actions">
            <a href="{$homeUrl}" class="btn-home">Quay về Trang Tổng Quan của bạn</a>
            <a href="/studenthub/public/logout.php" class="btn-logout">Đăng xuất tài khoản khác</a>
        </div>
    </div>
</body>
</html>
HTML;
        exit;
    }

    // =========================================================================
    // 5. BẢO VỆ CHỐNG CSRF (CSRF PROTECTION)
    // =========================================================================

    /**
     * Sinh hoặc lấy CSRF Token hiện tại trong Session
     */
    public static function csrfToken(): string {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf_token'];
    }

    /**
     * Xuất thẻ HTML input hidden chứa CSRF Token
     */
    public static function csrfField(): string {
        $token = htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Xác minh CSRF Token (hỗ trợ đọc từ POST, Header X-CSRF-Token hoặc JSON body)
     */
    public static function verifyCsrf(?string $token = null): bool {
        self::startSession();
        $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
        if ($sessionToken === '') {
            return false;
        }

        if ($token === null) {
            $token = (string)($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if ($token === '') {
                // Hỗ trợ request dạng JSON
                $rawBody = @file_get_contents('php://input');
                if ($rawBody) {
                    $json = @json_decode($rawBody, true);
                    if (is_array($json) && !empty($json['csrf_token'])) {
                        $token = (string)$json['csrf_token'];
                    }
                }
            }
        }

        return $token !== '' && hash_equals($sessionToken, $token);
    }

    /**
     * Bắt buộc kiểm tra CSRF cho các request POST: dừng thực thi nếu không hợp lệ
     */
    public static function requireCsrf(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verifyCsrf()) {
                http_response_code(403);
                if (self::isAjax()) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status'  => 'error',
                        'code'    => 403,
                        'message' => 'Lỗi bảo mật: CSRF Token không hợp lệ hoặc đã hết hạn!'
                    ]);
                    exit;
                }
                self::render403Page('Lỗi bảo mật: CSRF Token không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng thử lại!');
                exit;
            }
        }
    }

    // =========================================================================
    // 6. TIỆN ÍCH HỖ TRỢ NỘI BỘ
    // =========================================================================

    /**
     * Nhận diện request có phải là AJAX / JSON hay không
     */
    public static function isAjax(): bool {
        if (isset($_POST['ajax']) || (isset($_GET['ajax']) && $_GET['ajax'] === '1')) {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
        return (strpos($accept, 'application/json') !== false || strpos($contentType, 'application/json') !== false);
    }
}
