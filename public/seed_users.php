
<?php
require_once __DIR__ . '/../config/database.php';

$taiKhoanMau = [
    [
        'ten_dang_nhap' => 'sv001',
        'mat_khau' => 'Sv@123',
        'ho_ten' => 'Nguyen Van A',
        'email' => 'sv001@example.com',
        'vai_tro' => 'sinh_vien'
    ],
    [
        'ten_dang_nhap' => 'gv001',
        'mat_khau' => 'Gv@123',
        'ho_ten' => 'Tran Thi B',
        'email' => 'gv001@example.com',
        'vai_tro' => 'giang_vien'
    ],
    [
        'ten_dang_nhap' => 'admin',
        'mat_khau' => 'Admin@123',
        'ho_ten' => 'Quan tri vien',
        'email' => 'admin@example.com',
        'vai_tro' => 'admin'
    ]
];

try {
    $sql = "INSERT INTO nguoi_dung
            (ten_dang_nhap, mat_khau_hash, ho_ten, email, vai_tro)
            VALUES (:username, :password, :name, :email, :role)";

    $stmt = $conn->prepare($sql);

    foreach ($taiKhoanMau as $tk) {
        $stmt->execute([
            ':username' => $tk['ten_dang_nhap'],
            ':password' => password_hash(
                $tk['mat_khau'],
                PASSWORD_DEFAULT
            ),
            ':name' => $tk['ho_ten'],
            ':email' => $tk['email'],
            ':role' => $tk['vai_tro']
        ]);
    }

    echo 'Đã tạo thành công 3 tài khoản mẫu.';
} catch (PDOException $e) {
    http_response_code(500);
    exit('Không thể tạo tài khoản. Có thể dữ liệu đã tồn tại.');
}
