<?php
// app/Controllers/ClassController.php
require_once 'app/Models/ClassModel.php';

class ClassController {
    private $classModel;

    public function __construct($db) {
        $this->classModel = new ClassModel($db);
    }

    // Hàm hiển thị chi tiết lớp
    public function detail() {
        // Lấy ID lớp từ URL (ví dụ: index.php?controller=class&action=detail&id=1)
        $class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($class_id > 0) {
            // Gọi Model lấy dữ liệu
            $classInfo = $this->classModel->getClassDetail($class_id);
            $students = $this->classModel->getEnrolledStudents($class_id);

            // Kiểm tra xem lớp có tồn tại không
            if ($classInfo) {
                // Load file giao diện header, detail và footer
                require_once 'app/Views/layouts/header.php';
                require_once 'app/Views/classes/detail.php';
                require_once 'app/Views/layouts/footer.php';
            } else {
                echo "Không tìm thấy thông tin lớp học phần này.";
            }
        } else {
            echo "Mã lớp không hợp lệ.";
        }
    }
}
?>