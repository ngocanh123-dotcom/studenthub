<?php

require_once __DIR__ . '/../models/GiangVienModel.php';

class GiangVienController
{
    private GiangVienModel $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new GiangVienModel($pdo);
    }

    public function index(): void
    {
        $maTaiKhoan = (int) ($_SESSION['MaTaiKhoan'] ?? 0);

        if (
            $maTaiKhoan <= 0 ||
            ($_SESSION['VaiTro'] ?? '') !== 'GiangVien'
        ) {
            http_response_code(403);
            exit('Bạn không có quyền truy cập trang giảng viên.');
        }

        $giangVien = $this->model
            ->layGiangVienTheoTaiKhoan($maTaiKhoan);

        if (!$giangVien) {
            http_response_code(403);
            exit('Không tìm thấy hồ sơ giảng viên của tài khoản này.');
        }

        $danhSachLop = $this->model
            ->layDanhSachLop($giangVien['MaGV']);

        require __DIR__ . '/../views/giangvien/index.php';
    }

    public function sinhVienTheoLop(): void
    {
        $maTaiKhoan = (int) ($_SESSION['MaTaiKhoan'] ?? 0);

        if (
            $maTaiKhoan <= 0 ||
            ($_SESSION['VaiTro'] ?? '') !== 'GiangVien'
        ) {
            http_response_code(403);
            exit('Bạn không có quyền truy cập.');
        }

        $giangVien = $this->model
            ->layGiangVienTheoTaiKhoan($maTaiKhoan);

        if (!$giangVien) {
            http_response_code(403);
            exit('Không tìm thấy hồ sơ giảng viên.');
        }

        $maLop = trim($_GET['maLop'] ?? '');

        if ($maLop === '' ||
            !$this->model->kiemTraLop($maLop, $giangVien['MaGV'])) {
            http_response_code(403);
            exit('Lớp không tồn tại hoặc không thuộc quyền phụ trách của bạn.');
        }

        $danhSachSinhVien = $this->model
            ->laySinhVienTheoLop($maLop, $giangVien['MaGV']);

        require __DIR__ . '/../views/giangvien/sinh-vien-theo-lop.php';
    }
}