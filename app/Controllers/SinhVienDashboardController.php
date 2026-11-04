<?php

require_once __DIR__ . '/../models/SinhVienDashboardModel.php';

class SinhVienDashboardController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function index(): void
    {
        $studentId = (int) ($_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 0);
        $dashboardData = null;
        $dashboardError = '';

        try {
            $model = new SinhVienDashboardModel($this->pdo);
            $dashboardData = $model->getDashboardData($studentId);
        } catch (PDOException $e) {
            error_log('StudentHub Student Dashboard: ' . $e->getMessage());
            $dashboardError = 'Không thể tải số liệu học tập. Vui lòng kiểm tra cơ sở dữ liệu.';
        }

        require __DIR__ . '/../Views/sinhvien/index.php';
    }
}
