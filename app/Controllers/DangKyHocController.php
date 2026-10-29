<?php
require_once __DIR__ . '/../Models/DangKyHocModel.php';

class DangKyHocController
{
    private DangKyHocModel $model;

    public function __construct()
    {
        $this->model = new DangKyHocModel();
    }

    public function index(): void
    {
        $danhSachLop = $this->model->layDanhSachLop();

        require __DIR__ . '/../Views/dangky/index.php';
    }

    public function dangKy(int $sinhVienId, int $lopId): array
    {
        return $this->model->dangKy($sinhVienId, $lopId);
    }
}