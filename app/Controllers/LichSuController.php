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
        $sinhVienId = (int)($_SESSION['user_id'] ?? ($_SESSION['nguoi_dung_id'] ?? 0));

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
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ.']);
            return;
        }

        // Lấy dữ liệu JSON từ Fetch
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $data = [];
        }

        // 1. Kiểm tra phiên đăng nhập sinh viên (Loại bỏ triệt để fallback ?? 4)
        $sinhVienId = (int)($_SESSION['user_id'] ?? ($_SESSION['nguoi_dung_id'] ?? 0));
        if ($sinhVienId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập tài khoản sinh viên']);
            return;
        }

        // 2. Kiểm tra CSRF Token bằng hash_equals (Chống tấn công CSRF - Test Case 8)
        if (
            empty($_SESSION['csrf_token']) ||
            !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)($data['csrf_token'] ?? ''))
        ) {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi bảo mật: CSRF Token không hợp lệ.']);
            return;
        }

        $dangKyId = (int)($data['dang_ky_id'] ?? 0);

        // 3. Kiểm tra bản ghi có tồn tại không
        $dangKy = $this->model->getDangKyById($dangKyId);
        if (!$dangKy) {
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy thông tin đăng ký.']);
            return;
        }

        // 4. Kiểm tra IDOR (Chỉ được hủy của chính mình - Test Case 6)
        if ((int)$dangKy['sinh_vien_id'] !== $sinhVienId) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền hủy đăng ký này.']);
            return;
        }

        // 5. Kiểm tra trạng thái hiện tại của bản ghi đăng ký
        if ($dangKy['trang_thai'] === 'da_huy') {
            echo json_encode(['status' => 'error', 'message' => 'Đăng ký này đã được hủy trước đó.']);
            return;
        }

        // 6. Kiểm tra trạng thái lớp và cổng đăng ký học kỳ
        if (strtolower(trim((string)($dangKy['trang_thai_lop'] ?? $dangKy['lhp_trang_thai'] ?? 'mo'))) !== 'mo') {
            echo json_encode(['status' => 'error', 'message' => 'Đã hết thời hạn hủy học phần (Lớp đã khóa).']);
            return;
        }

        $coHanRieng = !empty($dangKy['thoi_gian_dong_rieng']);
        if (!$coHanRieng && (int)($dangKy['trang_thai_dang_ky'] ?? 1) !== 1) {
            echo json_encode(['status' => 'error', 'message' => 'Đã hết thời hạn hủy học phần (Cổng đăng ký học kỳ đã đóng).']);
            return;
        }

        // 7. Kiểm tra thời hạn hủy (Ưu tiên thời gian đóng riêng của lớp, nếu không có thì lấy của học kỳ)
        $deadline = !empty($dangKy['thoi_gian_dong_rieng'])
            ? $dangKy['thoi_gian_dong_rieng']
            : ($dangKy['thoi_gian_dong_dk'] ?? null);

        if ($deadline && strtotime(date('Y-m-d H:i:s')) > strtotime($deadline)) {
            echo json_encode(['status' => 'error', 'message' => 'Đã quá thời hạn cho phép hủy đăng ký.']);
            return;
        }

        // 8. Tiến hành hủy
        if ($this->model->huyDangKy($dangKyId)) {
            echo json_encode(['status' => 'success', 'message' => 'Hủy đăng ký thành công!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống, vui lòng thử lại sau.']);
        }
    }
}
