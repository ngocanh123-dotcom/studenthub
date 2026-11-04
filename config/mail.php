<?php
/**
 * config/mail.php
 * Cấu hình tài khoản gửi email SMTP Gmail cho hệ thống StudentHub
 * Sử dụng cổng 587 (STARTTLS) và Mật khẩu ứng dụng 16 ký tự của Google (App Password)
 */

return [
    // Máy chủ SMTP của Google
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',

    // Tài khoản Gmail và Mật khẩu ứng dụng 16 ký tự của Google
    // Cách tạo App Password: myaccount.google.com/apppasswords
    'username'   => 'huyennguyensocute.06@gmail.com', // Email dùng để gửi
    'password'   => 'xdsi zpyn hfyd rrev', // Mật khẩu ứng dụng 16 ký tự của Google

    // Thông tin người gửi hiển thị
    'from_email' => 'huyennguyensocute.06@gmail.com',
    'from_name'  => 'Cổng đào tạo StudentHub',
];
