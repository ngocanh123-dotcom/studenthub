<?php
class HocKyModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Lấy danh sách học kỳ kèm số lượng lớp
    public function getAll($keyword = '') {
        $sql = "SELECT hk.*, (SELECT COUNT(*) FROM lop_hoc_phan lhp WHERE lhp.hoc_ky_id = hk.id) AS so_lop 
                FROM hoc_ky hk WHERE 1=1";
        $params = [];
        if ($keyword !== '') {
            $sql .= " AND hk.ten_hoc_ky LIKE :q";
            $params[':q'] = '%' . $keyword . '%';
        }
        $sql .= " ORDER BY hk.id DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy 1 học kỳ theo ID
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM hoc_ky WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Thêm học kỳ
    public function insert($ten, $bat_dau, $ket_thuc, $mo_dk, $dong_dk, $trang_thai) {
        $sql = "INSERT INTO hoc_ky (ten_hoc_ky, ngay_bat_dau, ngay_ket_thuc, thoi_gian_mo_dk, thoi_gian_dong_dk, trang_thai_dang_ky) 
                VALUES (:ten, :bat_dau, :ket_thuc, :mo_dk, :dong_dk, :trang_thai)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':ten' => $ten, ':bat_dau' => $bat_dau, ':ket_thuc' => $ket_thuc,
            ':mo_dk' => $mo_dk ?: null, ':dong_dk' => $dong_dk ?: null, ':trang_thai' => $trang_thai
        ]);
    }

    // Cập nhật học kỳ
    public function update($id, $ten, $bat_dau, $ket_thuc, $mo_dk, $dong_dk, $trang_thai) {
        $sql = "UPDATE hoc_ky SET ten_hoc_ky = :ten, ngay_bat_dau = :bat_dau, ngay_ket_thuc = :ket_thuc, 
                thoi_gian_mo_dk = :mo_dk, thoi_gian_dong_dk = :dong_dk, trang_thai_dang_ky = :trang_thai 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':ten' => $ten, ':bat_dau' => $bat_dau, ':ket_thuc' => $ket_thuc,
            ':mo_dk' => $mo_dk ?: null, ':dong_dk' => $dong_dk ?: null, ':trang_thai' => $trang_thai, ':id' => $id
        ]);
    }

    // Xóa học kỳ
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM hoc_ky WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Đếm số lớp học phần thuộc học kỳ
    public function countClasses($id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM lop_hoc_phan WHERE hoc_ky_id = :id");
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    // Đổi trạng thái đăng ký (1 - trạng thái)
    public function toggleStatus($id) {
        $stmt = $this->conn->prepare("UPDATE hoc_ky SET trang_thai_dang_ky = 1 - trang_thai_dang_ky WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Kiểm tra trùng lặp thời gian
    public function checkOverlap($bat_dau, $ket_thuc, $exclude_id = 0) {
        $sql = "SELECT COUNT(*) FROM hoc_ky WHERE (ngay_bat_dau <= :ket_thuc AND ngay_ket_thuc >= :bat_dau)";
        $params = [':bat_dau' => $bat_dau, ':ket_thuc' => $ket_thuc];
        if ($exclude_id > 0) {
            $sql .= " AND id != :id";
            $params[':id'] = $exclude_id;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    // Lấy danh sách lớp học phần theo ID học kỳ (cho phần xổ xuống)
    public function getClassesBySemester($hoc_ky_id) {
        $stmt = $this->conn->prepare("
            SELECT lhp.ma_lop, hp.ten_hoc_phan, hp.so_tin_chi, nd.ho_ten AS gv, lhp.si_so_toi_da 
            FROM lop_hoc_phan lhp 
            JOIN hoc_phan hp ON lhp.hoc_phan_id = hp.id 
            JOIN nguoi_dung nd ON lhp.giang_vien_id = nd.id 
            WHERE lhp.hoc_ky_id = :id
        ");
        $stmt->execute([':id' => $hoc_ky_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>