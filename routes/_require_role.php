<?php

function studenthubRequireRole(array $allowedRoles): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $role = strtolower(trim((string) ($_SESSION['role'] ?? $_SESSION['vai_tro'] ?? '')));
    $roleAliases = [
        'sinh_vien' => 'sinhvien',
        'giang_vien' => 'giangvien',
        'admin' => 'quantri',
    ];
    $role = $roleAliases[$role] ?? $role;
    $userId = (int) ($_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 0);

    if ($userId <= 0 || !in_array($role, $allowedRoles, true)) {
        header('Location: /studenthub/public/index.php');
        exit;
    }
}
