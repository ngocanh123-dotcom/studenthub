<?php
/**
 * app/Controllers/GiangVienController.php
 * Controller điều phối Phân hệ Giảng viên (Lecturer Controller)
 * Kiến trúc: MVC - PHP 8.x - PDO Prepared Statements
 */

require_once __DIR__ . '/../Models/GiangVienModel.php';

class GiangVienController
{
    private GiangVienModel $model;
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        if (!$pdo instanceof PDO) {
            require __DIR__ . '/../../config/database.php';
            $pdo = $pdo ?? $conn ?? null;
        }

        if (!$pdo instanceof PDO) {
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu StudentHub.');
        }

        $this->pdo = $pdo;
        $this->model = new GiangVienModel($this->pdo);
    }

    /**
     * Xác thực phân quyền giảng viên từ Session
     * 
     * @return array Dữ liệu tài khoản giảng viên [id, ho_ten, ten_dang_nhap, email]
     */
    private function checkLecturerAuth(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role = strtolower(trim((string)($_SESSION['role'] ?? ($_SESSION['vai_tro'] ?? ''))));
        if (!in_array($role, ['giangvien', 'giang_vien'], true)) {
            header('Location: /studenthub/public/index.php?error=forbidden');
            exit('Bạn không có quyền truy cập phân hệ Giảng viên.');
        }

        $userId = (int)($_SESSION['user_id'] ?? ($_SESSION['nguoi_dung_id'] ?? 0));
        $username = (string)($_SESSION['username'] ?? ($_SESSION['ten_dang_nhap'] ?? ''));

        $lecturer = $this->model->getLecturerInfo($userId, $username);
        if (!$lecturer) {
            http_response_code(403);
            exit('Không tìm thấy thông tin tài khoản giảng viên.');
        }

        // Đồng bộ dữ liệu phiên làm việc
        $_SESSION['user_id'] = (int)$lecturer['id'];
        $_SESSION['ho_ten'] = $lecturer['ho_ten'];
        $_SESSION['tenVaiTro'] = $lecturer['ho_ten'];

        return $lecturer;
    }

    /**
     * Màn hình Dashboard Tổng quan Giảng viên
     */
    public function dashboard(): void
    {
        $lecturer = $this->checkLecturerAuth();
        $lecturerId = (int)$lecturer['id'];

        $stats = $this->model->getDashboardStats($lecturerId);

        $currentLecturerId = $lecturerId;
        $tenGiangVien      = $lecturer['ho_ten'];
        $username          = $lecturer['ten_dang_nhap'];
        $tongHocPhan       = $stats['tongHocPhan'];
        $tongLop           = $stats['tongLop'];
        $tongSinhVien      = $stats['tongSinhVien'];
        $classes           = $stats['courses'];
        $danhSachHocPhan   = $stats['courses'];

        require __DIR__ . '/../Views/giangvien/index.php';
    }

    /**
     * Màn hình Danh sách Lớp học phần theo môn
     */
    public function classes(): void
    {
        $lecturer = $this->checkLecturerAuth();
        $lecturerId = (int)$lecturer['id'];

        $hocPhanId = filter_input(INPUT_GET, 'hoc_phan_id', FILTER_VALIDATE_INT);
        if (!$hocPhanId || $hocPhanId < 1) {
            header('Location: /studenthub/public/index.php?route=giang_vien.php');
            exit;
        }

        $hocPhan = $this->model->getHocPhanOfLecturer($hocPhanId, $lecturerId);
        if (!$hocPhan) {
            http_response_code(404);
            exit('Không tìm thấy học phần hoặc bạn không có quyền xem học phần này.');
        }

        $classes = $this->model->getDanhSachLop($lecturerId, $hocPhanId);
        $danhSachLop = $classes;

        $currentLecturerId = $lecturerId;
        $tenGiangVien      = $lecturer['ho_ten'];
        $username          = $lecturer['ten_dang_nhap'];

        require __DIR__ . '/../Views/giangvien/lop_hoc_phan.php';
    }

    /**
     * Màn hình Danh sách Sinh viên theo lớp
     */
    public function students(): void
    {
        $lecturer = $this->checkLecturerAuth();
        $lecturerId = (int)$lecturer['id'];

        $lopId = filter_input(INPUT_GET, 'lop_id', FILTER_VALIDATE_INT);
        if (!$lopId || $lopId < 1) {
            header('Location: /studenthub/public/index.php?route=giang_vien.php');
            exit;
        }

        $result = $this->model->getDanhSachSinhVienTheoLop($lopId, $lecturerId);
        if (!$result) {
            http_response_code(404);
            exit('Không tìm thấy lớp học phần hoặc bạn không có quyền xem lớp này.');
        }

        $lop              = $result['lop'];
        $sinhVienList     = $result['sinhVienList'];
        $danhSachSinhVien = $sinhVienList;
        $tongSinhVien     = $result['tongSinhVien'];

        $currentLecturerId = $lecturerId;
        $tenGiangVien      = $lecturer['ho_ten'];
        $username          = $lecturer['ten_dang_nhap'];

        require __DIR__ . '/../Views/giangvien/sinh_vien.php';
    }

    // Các alias tương thích ngược
    public function index(): void
    {
        $this->dashboard();
    }

    public function lopHocPhan(): void
    {
        $this->classes();
    }

    public function sinhVien(): void
    {
        $this->students();
    }
}
