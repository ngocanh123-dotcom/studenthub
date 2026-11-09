<?php
// app/Controllers/ClassController.php
require_once __DIR__ . '/../Models/LopHocPhanModel.php';

class ClassController {
    private LopHocPhanModel $classModel;

    public function __construct(?PDO $dbConnection = null) {
        if (!$dbConnection instanceof PDO) {
            if (file_exists(__DIR__ . '/../../config/database.php')) {
                require_once __DIR__ . '/../../config/database.php';
                $dbConnection = $pdo ?? $conn ?? null;
            }
        }
        $this->classModel = new LopHocPhanModel($dbConnection);
    }

    /**
     * Hành động xem chi tiết lớp học phần
     * Chuẩn MVC: Nhận Request -> Gọi Model -> Xử lý dữ liệu/404 -> Nạp View
     */
    public function detail(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Kiểm tra xác thực phiên làm việc
        if (empty($_SESSION['user_id']) && empty($_SESSION['nguoi_dung_id'])) {
            header('Location: /studenthub/public/index.php');
            exit;
        }

        $currentUserId = (int)($_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 0);
        $hoTenSv       = (string)($_SESSION['ho_ten'] ?? ($_SESSION['username'] ?? 'Sinh viên'));
        $maSv          = (string)($_SESSION['ten_dang_nhap'] ?? ($_SESSION['username'] ?? ''));

        // 2. Nhận ID hoặc Mã lớp từ query string URL (mặc định lớp 1 nếu không truyền)
        $id = $_GET['id'] ?? ($_GET['ma_lop'] ?? null);
        if ($id === null || trim((string)$id) === '') {
            $id = 1;
        }

        // 3. Gọi Model để lấy dữ liệu chi tiết lớp học phần
        $lop = $this->classModel->getDetailById($id, $currentUserId);
        $class = $lop; // Alias tương thích cho View
        $classInfo = $lop;
        $enrolledStudents = $this->classModel->getEnrolledStudents($id);

        // 4. Nếu không tìm thấy lớp học phần, thiết lập HTTP Status 404
        if (!$lop) {
            http_response_code(404);
        }

        // 5. Truyền biến sang nạp View
        require __DIR__ . '/../Views/classes/detail.php';
    }
}
