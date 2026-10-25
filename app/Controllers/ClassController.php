<?php
class ClassController {
    private $classModel;

    public function __construct($model) {
        $this->classModel = $model;
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    // Hiển thị trang chi tiết
    public function showDetail($classId) {
        $classInfo = $this->classModel->getClassDetail($classId);
        
        if (!$classInfo) {
            die("Lớp học phần không tồn tại.");
        }

        // Tạo CSRF Token nếu chưa có
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Giả lập ID sinh viên đang đăng nhập (Thực tế lấy từ $_SESSION['user_id'])
        $studentId = $_SESSION['user_id'] ?? 1; 

        $isFull = $classInfo['registered_seats'] >= $classInfo['max_seats'];
        $isEnrolled = $this->classModel->isEnrolled($classId, $studentId);

        // Gọi View
        require_once 'app/Views/layouts/header.php';
        require_once 'app/Views/classes/detail.php';
        require_once 'app/Views/layouts/footer.php';
    }

    // Endpoint API xử lý đăng ký (Được gọi bằng Fetch)
    public function apiEnroll() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            return;
        }

        // Kiểm tra CSRF Token bắt buộc
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['status' => 'error', 'message' => 'CSRF Token không hợp lệ.']);
            return;
        }

        $classId = $data['class_id'];
        $studentId = $_SESSION['user_id'] ?? 1;

        // Nghiệp vụ: Chặn thao tác sai
        $classInfo = $this->classModel->getClassDetail($classId);
        if ($classInfo['status'] !== 'active') {
            echo json_encode(['status' => 'error', 'message' => 'Lớp học phần đã bị khóa.']);
            return;
        }
        if ($classInfo['registered_seats'] >= $classInfo['max_seats']) {
            echo json_encode(['status' => 'error', 'message' => 'Lớp đã đủ sĩ số.']);
            return;
        }
        if ($this->classModel->isEnrolled($classId, $studentId)) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn đã đăng ký lớp này rồi.']);
            return;
        }

        // Tiến hành ghi dữ liệu
        if ($this->classModel->enrollStudent($classId, $studentId)) {
            echo json_encode(['status' => 'success', 'message' => 'Đăng ký thành công!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống.']);
        }
    }
}