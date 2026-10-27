<?php
// app/Controllers/ClassController.php
require_once __DIR__ . '/../Models/ClassModel.php';

class ClassController {
    private $classModel;

    public function __construct($dbConnection = null) {
        if (!$dbConnection && file_exists(__DIR__ . '/../../config/database.php')) {
            require_once __DIR__ . '/../../config/database.php';
            $dbConnection = $conn ?? null;
        }
        $this->classModel = new ClassModel($dbConnection);
    }

    /**
     * Hành động xem chi tiết lớp học phần
     */
    public function detail() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Lấy ID hoặc mã lớp từ query string URL (mặc định lấy lớp 1)
        $classId = $_GET['id'] ?? $_GET['ma_lop'] ?? 1;

        $classInfo = $this->classModel->getClassDetail($classId);
        $enrolledStudents = $this->classModel->getEnrolledStudents($classId);

        // Nạp View chi tiết lớp học phần
        require_once __DIR__ . '/../Views/classes/detail.php';
    }
}