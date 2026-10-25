
<?php
require_once __DIR__ . '/../models/DangKyHocModel.php';

class DangKyHocController
{
    private DangKyHocModel $model;

    public function __construct()
    {
        $this->model = new DangKyHocModel();
    }

    // Hiển thị danh sách lớp học phần
    public function index(): void
    {
        $danhSachLop = $this->model->layDanhSachLop();

        require __DIR__ . '/../views/dangky/index.php';
    }

    // Xử lý đăng ký học phần
    public function dangKy(int $sinhVienId, int $lopId): array
    {
        return $this->model->dangKy($sinhVienId, $lopId);
    }
}
