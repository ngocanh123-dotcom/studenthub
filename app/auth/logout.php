<?php
/**
 * app/auth/logout.php
 * Endpoint tương thích ngược cho các view cũ
 */
header('Location: /studenthub/public/index.php?action=logout');
exit;
