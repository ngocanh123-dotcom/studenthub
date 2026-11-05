<?php

require_once __DIR__ . '/SinhVienDashboardController.php';

class SinhVienController extends SinhVienDashboardController
{
    public function tongQuan(): void
    {
        $this->index();
    }
}
