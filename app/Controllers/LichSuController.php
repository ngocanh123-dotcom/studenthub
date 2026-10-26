<?php
require_once __DIR__ . '/../Models/LichSuModel.php';

class LichSuController {
    private $model;

    public function __construct($pdo) {
        $this->model = new LichSuModel($pdo);
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    // Hiển thị trang Lịch sử
    public function index() {
        // Lấy ID sinh viên (hỗ trợ cả 2 cách lưu session của nhóm)
        $sinhVienId = $_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 4;

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrf_token = $_SESSION['csrf_token'];

        $danhSachLichSu = $this->model->getLichSu($sinhVienId);

        // Gọi View
        require_once __DIR__ . '/../Views/lichsu/index.php';
    }

    // API xử lý Hủy đăng ký (Gọi qua Fetch JS)
    public function apiHuyDangKy() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ.']);
            return;
        }

        // Lấy dữ liệu JSON từ Fetch
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 1. Kiểm tra CSRF Token (Chống tấn công CSRF - Test Case 8)
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi bảo mật: CSRF Token không hợp lệ.']);
            return;
        }

        $dangKyId = (int)($data['dang_ky_id'] ?? 0);
        $sinhVienId = $_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 4;

        // 2. Kiểm tra bản ghi có tồn tại không
        $dangKy = $this->model->getDangKyById($dangKyId);
        if (!$dangKy) {
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy thông tin đăng ký.']);
            return;
        }

        // 3. Kiểm tra IDOR (Chỉ được hủy của chính mình - Test Case 5)
        if ($dangKy['sinh_vien_id'] != $sinhVienId) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền hủy đăng ký này.']);
            return;
        }

        // 4. Kiểm tra trạng thái hiện tại
        if ($dangKy['trang_thai'] === 'da_huy') {
            echo json_encode(['status' => 'error', 'message' => 'Đăng ký này đã được hủy trước đó.']);
            return;
        }

        // 5. Kiểm tra thời hạn hủy (Quy định của hệ thống)
        // Ưu tiên thời gian đóng riêng của lớp, nếu không có thì lấy của học kỳ
        $deadline = $dangKy['thoi_gian_dong_rieng'] ?? $dangKy['thoi_gian_dong_dk'];
        
        if ($deadline && strtotime(date('Y-m-d H:i:s')) > strtotime($deadline)) {
            echo json_encode(['status' => 'error', 'message' => 'Đã quá thời hạn cho phép hủy đăng ký.']);
            return;
        }

        // 6. Tiến hành hủy
        if ($this->model->huyDangKy($dangKyId)) {
            echo json_encode(['status' => 'success', 'message' => 'Hủy đăng ký thành công!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống, vui lòng thử lại sau.']);
        }
    }
}