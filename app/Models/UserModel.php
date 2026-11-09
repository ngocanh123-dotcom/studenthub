<?php
/**
 * app/Models/UserModel.php
 * Model quản lý thông tin người dùng và xác thực tài khoản - StudentHub
 * Kiến trúc: MVC Chuẩn công nghiệp - PDO Prepared Statements
 */

class UserModel
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Tìm người dùng theo ID
     */
    public function findById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM nguoi_dung WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Tìm người dùng theo tên đăng nhập hoặc email
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM nguoi_dung WHERE (LOWER(TRIM(ten_dang_nhap)) = LOWER(TRIM(:u)) OR LOWER(TRIM(email)) = LOWER(TRIM(:e))) LIMIT 1"
        );
        $stmt->execute([':u' => $username, ':e' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Kiểm tra thông tin đăng nhập (Tên đăng nhập/Email & Mật khẩu)
     * Tự động nâng cấp lên Bcrypt nếu dữ liệu khởi tạo chưa băm
     */
    public function authenticate(string $username, string $plainPassword): ?array
    {
        $user = $this->findByUsername($username);
        if (!$user) {
            return null;
        }

        $storedHash = (string)($user['mat_khau_hash'] ?? $user['mat_khau'] ?? '');

        // 1. Ưu tiên kiểm tra bằng mã băm chuẩn Bcrypt
        if (!empty($storedHash) && password_verify($plainPassword, $storedHash)) {
            return $user;
        }

        // 2. Chấp nhận nếu mật khẩu trong DB chưa băm (dữ liệu ban đầu) và khớp đúng, sau đó nâng cấp lên Bcrypt
        if (
            !empty($storedHash)
            && !str_starts_with($storedHash, '$2y$')
            && ($plainPassword === $storedHash || md5($plainPassword) === $storedHash)
        ) {
            $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
            $this->updatePassword((int)$user['id'], $newHash);
            $user['mat_khau_hash'] = $newHash;
            return $user;
        }

        return null;
    }

    /**
     * Xác thực mật khẩu hiện tại của người dùng
     * Hỗ trợ Bcrypt chuẩn an toàn và fallback tương thích
     */
    public function verifyCurrentPassword(int $userId, string $plainPassword): bool
    {
        $user = $this->findById($userId);
        if (!$user) {
            return false;
        }

        $dbHash = (string)($user['mat_khau_hash'] ?? '');

        // 1. Kiểm tra mã băm Bcrypt chuẩn
        if (!empty($dbHash) && password_verify($plainPassword, $dbHash)) {
            return true;
        }

        // 2. Fallback tương thích dữ liệu cũ (chuỗi gốc hoặc MD5)
        if ($plainPassword === $dbHash || md5($plainPassword) === $dbHash) {
            return true;
        }

        if (isset($user['mat_khau']) && $plainPassword === (string)$user['mat_khau']) {
            return true;
        }

        return false;
    }

    /**
     * Cập nhật mật khẩu mới (chuỗi băm an toàn)
     */
    public function updatePassword(int $userId, string $newPasswordHash): bool
    {
        $user = $this->findById($userId);
        if (!$user) {
            return false;
        }

        $sql = "UPDATE nguoi_dung SET mat_khau_hash = :hash";
        $params = [
            ':hash' => $newPasswordHash,
            ':id'   => $userId
        ];

        // Nếu hệ thống còn cột mat_khau cũ thì cập nhật song song
        if (array_key_exists('mat_khau', $user)) {
            $sql .= ", mat_khau = :hash_plain";
            $params[':hash_plain'] = $newPasswordHash;
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
}

/**
 * Bí danh tiếng Việt cho UserModel để tương thích linh hoạt
 */
class NguoiDungModel extends UserModel
{
}
