<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

echo "Kết nối MySQL thành công!\n";
echo 'Database đang sử dụng: ' .
    $pdo->query('SELECT DATABASE()')->fetchColumn() . PHP_EOL;