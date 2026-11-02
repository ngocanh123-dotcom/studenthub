<?php
declare(strict_types=1);

/**
 * File: src/Controllers/LopHocPhanController.php
 * Module: Quản lý chi tiết Lớp học phần (StudentHub)
 * Phụ trách: Tạ Như Quỳnh - Bài tập về nhà 6
 * Kiến trúc: MVC - PHP 8.x OOP - Dependency Injection & Guard Clause
 */

require_once __DIR__ . '/../Repositories/LopHocPhanRepository.php';

class LopHocPhanController {
    private LopHocPhanRepository $repository;

    /**
     * Tiêm phụ thuộc (Dependency Injection) LopHocPhanRepository vào Controller
     *
     * @param LopHocPhanRepository|null $repository Đối tượng Repository (nếu null sẽ tự khởi tạo)
     */
    public function __construct(?LopHocPhanRepository $repository = null) {
        $this->repository = $repository ?? new LopHocPhanRepository();
    }

    /**
     * Xử lý hiển thị thông tin chi tiết của một lớp học phần
     * Route xử lý: GET /lop-hoc-phan/chi-tiet?id={id}
     *
     * Luồng thực thi:
     * 1. Guard Clause kiểm tra và làm sạch ID từ URL ($_GET['id'])
     * 2. Gọi Repository để lấy dữ liệu lớp học phần
     * 3. Kiểm tra tính tồn tại, nếu không tìm thấy điều hướng an toàn về danh sách kèm thông báo lỗi
     * 4. Gọi Repository để kiểm tra trạng thái ghi danh của sinh viên hiện tại
     * 5. Truyền dữ liệu sạch sang View và nạp View hiển thị
     */
    public function detail(): void {
        // Khởi động phiên làm việc nếu chưa có
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        // 1. Guard Clause: Kiểm tra và làm sạch tham số ID từ $_GET['id']
        $rawId = $_GET['id'] ?? null;
        $id = filter_var($rawId, FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) {
            $_SESSION['flash_error'] = 'Mã ID lớp học phần không hợp lệ hoặc để trống.';
            header('Location: /studenthub/public/danh_sach_lop_hoc_phan.php');
            exit;
        }

        // 2. Nhận diện phiên sinh viên đăng nhập
        $currentUserId = (int)($_SESSION['user_id'] ?? ($_SESSION['nguoi_dung_id'] ?? 4));
        $hoTenSv       = (string)($_SESSION['ho_ten'] ?? 'Nguyễn Thu Trang');
        $maSv          = (string)($_SESSION['ten_dang_nhap'] ?? ($_SESSION['username'] ?? 'SV001'));

        // 3. Gọi Repository để truy vấn dữ liệu lớp học phần
        $lop = $this->repository->findById($id);

        // Kiểm tra nếu không tìm thấy lớp: điều hướng an toàn về danh sách kèm flash message
        if (!$lop) {
            $_SESSION['flash_error'] = 'Lớp học phần bạn đang tìm kiếm không tồn tại hoặc đã được gỡ bỏ khỏi hệ thống.';
            header('Location: /studenthub/public/danh_sach_lop_hoc_phan.php');
            exit;
        }

        // 4. Kiểm tra trạng thái ghi danh của sinh viên hiện tại
        $isRegistered = $this->repository->isRegistered($id, $currentUserId);
        if (!empty($isRegistered['ngay_dang_ky'])) {
            $lop['ngay_dang_ky_cua_ban'] = $isRegistered['ngay_dang_ky'];
        }

        // 5. Truyền dữ liệu sạch sang View và nạp View hiển thị
        $viewPath = __DIR__ . '/../../views/classes/detail.php';
        if (!file_exists($viewPath)) {
            $viewPath = __DIR__ . '/../../app/Views/classes/detail.php';
        }

        require $viewPath;
    }
}

// Thiết lập alias namespace đảm bảo tương thích mọi cơ chế autoloader
if (!class_exists('Controllers\LopHocPhanController')) {
    class_alias('LopHocPhanController', 'Controllers\LopHocPhanController');
}
if (!class_exists('App\Controllers\LopHocPhanController')) {
    class_alias('LopHocPhanController', 'App\Controllers\LopHocPhanController');
}

// Hỗ trợ tự động chạy khi truy cập trực tiếp file Controller
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    $controller = new LopHocPhanController();
    $controller->detail();
}
