<?php
class ClassModel {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    // Lấy chi tiết lớp, thông tin học phần, giảng viên và đếm số ghế đã đăng ký
    public function getClassDetail($classId) {
        $sql = "SELECT c.id, c.room, c.schedule, c.max_seats, c.status,
                       co.course_code, co.course_name, co.credits,
                       u.fullname AS lecturer_name,
                       (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.id) AS registered_seats
                FROM classes c
                JOIN courses co ON c.course_id = co.id
                JOIN users u ON c.lecturer_id = u.id
                WHERE c.id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $classId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Kiểm tra sinh viên đã đăng ký chưa
    public function isEnrolled($classId, $studentId) {
        $sql = "SELECT id FROM enrollments WHERE class_id = :class_id AND student_id = :student_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['class_id' => $classId, 'student_id' => $studentId]);
        return $stmt->fetch() ? true : false;
    }

    // Thực hiện đăng ký
    public function enrollStudent($classId, $studentId) {
        $sql = "INSERT INTO enrollments (class_id, student_id, enrolled_at) VALUES (:class_id, :student_id, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['class_id' => $classId, 'student_id' => $studentId]);
    }
}