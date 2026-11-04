<?php

require_once __DIR__ . '/../models/AdminDashboardModel.php';

class AdminDashboardController
{
    private AdminDashboardModel $model;

    public function __construct(?PDO $pdo = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->checkAdminAccess();

        if (!$pdo instanceof PDO) {
            require __DIR__ . '/../../config/database.php';
            $pdo = $pdo ?? $conn ?? null;
        }

        if (!$pdo instanceof PDO) {
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu StudentHub.');
        }

        $this->model = new AdminDashboardModel($pdo);
    }

    public function index(): void
    {
        $tenAdmin = $_SESSION['ho_ten']
            ?? $_SESSION['tenVaiTro']
            ?? $_SESSION['username'];
        $tongSinhVien = 0;
        $tongGiangVien = 0;
        $tongHocPhan = 0;
        $tongLopHocPhan = 0;
        $loi = '';

        try {
            $statistics = $this->model->getStatistics();
            $tongSinhVien = $statistics['tongSinhVien'];
            $tongGiangVien = $statistics['tongGiangVien'];
            $tongHocPhan = $statistics['tongHocPhan'];
            $tongLopHocPhan = $statistics['tongLopHocPhan'];
        } catch (PDOException | RuntimeException $e) {
            error_log('StudentHub Admin Dashboard: ' . $e->getMessage());
            $loi = 'Không thể tải số liệu. Vui lòng kiểm tra cấu trúc cơ sở dữ liệu.';
        }

        require __DIR__ . '/../views/admin/dashboard.php';
    }

    private function checkAdminAccess(): void
    {
        if (empty($_SESSION['username'])) {
            header('Location: /studenthub/public/index.php');
            exit;
        }

        $role = strtolower(trim((string) ($_SESSION['role'] ?? '')));
        if (!in_array($role, ['admin', 'quantri', 'quan_tri', 'quan_tri_vien'], true)) {
            header('Location: /studenthub/public/index.php');
            exit;
        }
    }
}
