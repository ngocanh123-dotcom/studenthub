<?php
/**
 * LopHocPhanModel.php
 * Model xử lý truy vấn dữ liệu Lớp học phần cho StudentHub
 * Kiến trúc: MVC - PHP 8.x - PDO Prepared Statements
 */

class LopHocPhanModel {
    private ?PDO $conn;

    public function __construct(?PDO $dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * Lấy chi tiết 1 lớp học phần theo ID (hoặc Mã lớp) và thời gian sinh viên đăng nhập đã ghi danh (nếu có)
     * Đảm bảo SELECT đầy đủ các trường thời gian của học kỳ và thời gian riêng của lớp
     * 
     * @param int|string $classId ID hoặc Mã lớp học phần
     * @param int|null $studentId ID sinh viên đang đăng nhập
     * @return array|null Mảng dữ liệu chi tiết lớp hoặc null nếu không tìm thấy
     */
    public function getChiTietLop(int|string $classId, ?int $studentId = 0): ?array {
        if (!$this->conn instanceof PDO) {
            return null;
        }

        $sql = "SELECT 
                    lhp.id AS lop_id,
                    lhp.ma_lop,
                    lhp.lich_hoc,
                    lhp.phong_hoc,
                    lhp.si_so_toi_da,
                    lhp.trang_thai AS trang_thai_lop,
                    lhp.thoi_gian_mo_rieng,
                    lhp.thoi_gian_dong_rieng,
                    hp.ma_hoc_phan,
                    hp.ten_hoc_phan,
                    hp.so_tin_chi,
                    hp.mo_ta AS mo_ta_hoc_phan,
                    hp.dieu_kien_tien_quyet,
                    hk.ten_hoc_ky,
                    hk.ngay_bat_dau,
                    hk.ngay_ket_thuc,
                    hk.thoi_gian_mo_dk,
                    hk.thoi_gian_dong_dk,
                    hk.trang_thai_dang_ky,
                    gv.ho_ten AS ten_giang_vien,
                    gv.email AS email_giang_vien,
                    -- Đếm tổng sĩ số thực tế đã đăng ký hợp lệ
                    (SELECT COUNT(*) 
                     FROM dang_ky_hoc 
                     WHERE lop_hoc_phan_id = lhp.id AND trang_thai = 'dang_ky') AS si_so_hien_tai,
                    -- Lấy thời gian đăng ký của chính sinh viên đang đăng nhập
                    (SELECT ngay_dang_ky 
                     FROM dang_ky_hoc 
                     WHERE lop_hoc_phan_id = lhp.id 
                       AND sinh_vien_id = :student_id 
                       AND trang_thai = 'dang_ky' 
                     LIMIT 1) AS ngay_dang_ky_cua_ban
                FROM lop_hoc_phan lhp
                INNER JOIN hoc_phan hp ON lhp.hoc_phan_id = hp.id
                INNER JOIN hoc_ky hk ON lhp.hoc_ky_id = hk.id
                LEFT JOIN nguoi_dung gv ON lhp.giang_vien_id = gv.id
                WHERE (lhp.id = :class_id_numeric OR lhp.ma_lop = :class_code)
                LIMIT 1";

        try {
            $stmt = $this->conn->prepare($sql);
            $numericId = is_numeric($classId) ? (int)$classId : 0;
            $classCode = (string)$classId;

            $stmt->execute([
                ':class_id_numeric' => $numericId,
                ':class_code'       => $classCode,
                ':student_id'       => (int)($studentId ?? 0)
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Lỗi truy vấn LopHocPhanModel::getChiTietLop: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Phương thức tương thích ClassModel
     */
    public function getClassDetail(int|string $classId): ?array {
        $studentId = $_SESSION['user_id'] ?? ($_SESSION['nguoi_dung_id'] ?? 0);
        return $this->getChiTietLop($classId, (int)$studentId);
    }

    /**
     * Lấy danh sách sinh viên đã ghi danh vào lớp
     */
    public function getEnrolledStudents(int|string $classId): array {
        if (!$this->conn instanceof PDO) {
            return [];
        }

        $sql = "SELECT nd.id, nd.ten_dang_nhap, nd.ho_ten, nd.email, dkh.ngay_dang_ky
                FROM dang_ky_hoc dkh
                INNER JOIN nguoi_dung nd ON dkh.sinh_vien_id = nd.id
                INNER JOIN lop_hoc_phan lhp ON dkh.lop_hoc_phan_id = lhp.id
                WHERE (lhp.id = :class_id_numeric OR lhp.ma_lop = :class_code)
                  AND dkh.trang_thai = 'dang_ky'
                ORDER BY dkh.ngay_dang_ky ASC";

        try {
            $stmt = $this->conn->prepare($sql);
            $numericId = is_numeric($classId) ? (int)$classId : 0;
            $classCode = (string)$classId;

            $stmt->execute([
                ':class_id_numeric' => $numericId,
                ':class_code'       => $classCode
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Lỗi truy vấn LopHocPhanModel::getEnrolledStudents: " . $e->getMessage());
            return [];
        }
    }
}

// Khai báo bí danh ClassModel để tương thích với ClassController nếu có gọi tới
if (!class_exists('ClassModel')) {
    class ClassModel extends LopHocPhanModel {}
}
