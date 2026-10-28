
<?php
require_once __DIR__ . '/../config/database.php';

// Danh sách tài khoản và mật khẩu mới
$accounts = [
    'GV001' => 'Teacher@123',
    'admin' => 'Admin@123'
];

foreach ($accounts as $username => $newPassword) {
    // Tạo mã hash cho mật khẩu
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    $sql = "UPDATE nguoi_dung
            SET mat_khau_hash = :hash
            WHERE ten_dang_nhap = :username";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':hash' => $hash,
        ':username' => $username
    ]);

    if ($stmt->rowCount() > 0) {
        echo 'Đã đổi mật khẩu cho tài khoản '
            . htmlspecialchars($username, ENT_QUOTES, 'UTF-8')
            . '<br>';
    } else {
        $check = $conn->prepare(
            "SELECT id FROM nguoi_dung
             WHERE ten_dang_nhap = :username"
        );
        $check->execute([':username' => $username]);

        if ($check->fetch()) {
            echo 'Tài khoản '
                . htmlspecialchars($username, ENT_QUOTES, 'UTF-8')
                . ' đã tồn tại; mật khẩu có thể đã được cập nhật.<br>';
        } else {
            echo 'Không tìm thấy tài khoản '
                . htmlspecialchars($username, ENT_QUOTES, 'UTF-8')
                . '<br>';
        }
    }
}
?>
