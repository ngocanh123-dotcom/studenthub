<?php


require_once __DIR__ . '/../config/database.php';

echo 'Kết nối MySQL thành công!<br>';
echo 'Database đang sử dụng: ' .
    htmlspecialchars((string) $conn->query('SELECT DATABASE()')->fetchColumn());