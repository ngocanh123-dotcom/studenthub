<?php
// app/Models/ClassModel.php
class ClassModel {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // Lấy thông tin chung của lớp (Tên môn, Giảng viên, Sĩ số, Phòng học...)
    public function getClassDetail($class_id) {
        $sql = "SELECT l.*, m.ten_mon_hoc, m.so_tin_chi, g.ho_ten as ten_giang_vien 
                FROM lop_hoc_phan l
                JOIN mon_hoc m ON l.ma_mon_hoc = m.ma_mon_hoc
                JOIN giang_vien g ON l.ma_giang_vien = g.ma_giang_vien
                WHERE l.ma_lop = :class_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['class_id' => $class_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy danh sách sinh viên đã đăng ký vào lớp này
    public function getEnrolledStudents($class_id) {
        $sql = "SELECT s.ma_sinh_vien, s.ho_ten, s.lop_hanh_chinh, dk.ngay_dang_ky 
                FROM dang_ky_hoc_phan dk
                JOIN sinh_vien s ON dk.ma_sinh_vien = s.ma_sinh_vien
                WHERE dk.ma_lop = :class_id
                ORDER BY s.ma_sinh_vien ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['class_id' => $class_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>