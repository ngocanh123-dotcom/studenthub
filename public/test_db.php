
<?php

$duongDan = __DIR__ . '/../config/database.php';

if (!file_exists($duongDan)) {
    exit('Không tìm thấy file config/database.php');
}

require_once $duongDan;

echo 'Kết nối MySQL thành công!<br>';

echo 'Database đang sử dụng: ' .
    htmlspecialchars($conn->query('SELECT DATABASE()')->fetchColumn());
