<?php
class LichSuModel {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    // Lấy danh sách lịch sử đăng ký của sinh viên
    public function getLichSu($sinhVienId) {
        $sql = "SELECT dkh.id, dkh.trang_thai, dkh.ngay_dang_ky, dkh.ngay_huy,
                       lhp.ma_lop, lhp.thoi_gian_dong_rieng,
                       hp.ten_hoc_phan, hp.so_tin_chi,
                       gv.ho_ten AS ten_giang_vien,
                       hk.thoi_gian_dong_dk
                FROM dang_ky_hoc dkh
                INNER JOIN lop_hoc_phan lhp ON dkh.lop_hoc_phan_id = lhp.id
                INNER JOIN hoc_phan hp ON lhp.hoc_phan_id = hp.id
                INNER JOIN hoc_ky hk ON lhp.hoc_ky_id = hk.id
                INNER JOIN nguoi_dung gv ON lhp.giang_vien_id = gv.id
                WHERE dkh.sinh_vien_id = :sinh_vien_id
                ORDER BY dkh.ngay_dang_ky DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sinh_vien_id' => $sinhVienId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy 1 bản ghi đăng ký (để kiểm tra quyền sở hữu)
    public function getDangKyById($dangKyId) {
        $sql = "SELECT dkh.id, dkh.sinh_vien_id, dkh.trang_thai, lhp.thoi_gian_dong_rieng, hk.thoi_gian_dong_dk
                FROM dang_ky_hoc dkh
                INNER JOIN lop_hoc_phan lhp ON dkh.lop_hoc_phan_id = lhp.id
                INNER JOIN hoc_ky hk ON lhp.hoc_ky_id = hk.id
                WHERE dkh.id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $dangKyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cập nhật trạng thái thành đã hủy
    public function huyDangKy($dangKyId) {
        $sql = "UPDATE dang_ky_hoc SET trang_thai = 'da_huy', ngay_huy = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $dangKyId]);
    }
}