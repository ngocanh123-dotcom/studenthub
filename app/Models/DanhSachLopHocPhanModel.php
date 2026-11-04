<?php
/**
 * app/Models/DanhSachLopHocPhanModel.php
 * Model xử lý truy vấn dữ liệu Danh sách lớp học phần cho phân hệ Sinh viên
 * Kiến trúc: MVC - PHP 8.x - PDO Prepared Statements
 */

class DanhSachLopHocPhanModel {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * Lấy danh sách lớp học phần kèm bộ lọc tra cứu động
     * Đồng bộ đầy đủ lịch học (lhp.lich_hoc, lhp.phong_hoc) và thời gian học kỳ (hk.thoi_gian_mo_dk, hk.thoi_gian_dong_dk)
     * 
     * @param int $sinhVienId ID sinh viên đang đăng nhập để kiểm tra sv_da_dang_ky
     * @param array $filters Mảng tham số lọc: tu_khoa, hoc_ky_id, giang_vien_id
     * @return array Danh sách các lớp học phần
     */
    public function getClassesWithFilters(int $sinhVienId, array $filters = []): array {
        $where = ["1=1"];
        $params = [':sv_id' => $sinhVienId];

        $tuKhoa = trim($filters['tu_khoa'] ?? '');
        if ($tuKhoa !== '') {
            $where[] = "(lhp.ma_lop LIKE :kw1 OR hp.ma_hoc_phan LIKE :kw2 OR hp.ten_hoc_phan LIKE :kw3)";
            $params[':kw1'] = '%' . $tuKhoa . '%';
            $params[':kw2'] = '%' . $tuKhoa . '%';
            $params[':kw3'] = '%' . $tuKhoa . '%';
        }

        $hocKyId = isset($filters['hoc_ky_id']) ? trim((string)$filters['hoc_ky_id']) : '';
        if ($hocKyId !== '' && ctype_digit($hocKyId)) {
            $where[] = "lhp.hoc_ky_id = :hoc_ky_id";
            $params[':hoc_ky_id'] = (int)$hocKyId;
        }

        $giangVienId = isset($filters['giang_vien_id']) ? trim((string)$filters['giang_vien_id']) : '';
        if ($giangVienId !== '' && ctype_digit($giangVienId)) {
            $where[] = "lhp.giang_vien_id = :giang_vien_id";
            $params[':giang_vien_id'] = (int)$giangVienId;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "
            SELECT 
                lhp.id,
                lhp.ma_lop,
                lhp.hoc_phan_id,
                lhp.hoc_ky_id,
                lhp.giang_vien_id,
                lhp.si_so_toi_da,
                lhp.thoi_gian_mo_rieng,
                lhp.thoi_gian_dong_rieng,
                lhp.lich_hoc,
                lhp.phong_hoc,

                hp.ma_hoc_phan,
                hp.ten_hoc_phan,
                hp.so_tin_chi,

                hk.ten_hoc_ky,
                hk.ngay_bat_dau,
                hk.ngay_ket_thuc,
                hk.thoi_gian_mo_dk,
                hk.thoi_gian_dong_dk,
                hk.trang_thai_dang_ky,

                nd.ten_dang_nhap AS ma_giang_vien,
                nd.ho_ten AS ho_ten_giang_vien,
                nd.email AS email_giang_vien,

                (
                    SELECT COUNT(*) 
                    FROM dang_ky_hoc dkh 
                    WHERE dkh.lop_hoc_phan_id = lhp.id 
                      AND dkh.trang_thai = 'dang_ky'
                ) AS si_so_hien_tai,

                (
                    SELECT COUNT(*) 
                    FROM dang_ky_hoc dkh_sv 
                    WHERE dkh_sv.lop_hoc_phan_id = lhp.id 
                      AND dkh_sv.sinh_vien_id = :sv_id 
                      AND dkh_sv.trang_thai = 'dang_ky'
                ) AS sv_da_dang_ky

            FROM lop_hoc_phan lhp
            INNER JOIN hoc_phan hp ON lhp.hoc_phan_id = hp.id
            INNER JOIN hoc_ky hk ON lhp.hoc_ky_id = hk.id
            LEFT JOIN nguoi_dung nd ON lhp.giang_vien_id = nd.id
            WHERE $whereSql
            ORDER BY lhp.hoc_ky_id ASC, hp.ma_hoc_phan ASC, lhp.ma_lop ASC
        ";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Lỗi DanhSachLopHocPhanModel::getClassesWithFilters: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy danh sách tất cả học kỳ cho dropdown bộ lọc
     * 
     * @return array Danh sách học kỳ
     */
    public function getAllHocKy(): array {
        try {
            $stmt = $this->conn->query("SELECT id, ten_hoc_ky, ngay_bat_dau, ngay_ket_thuc, thoi_gian_mo_dk, thoi_gian_dong_dk, trang_thai_dang_ky FROM hoc_ky ORDER BY id ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Lỗi DanhSachLopHocPhanModel::getAllHocKy: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy danh sách tất cả giảng viên cho dropdown bộ lọc
     * 
     * @return array Danh sách giảng viên
     */
    public function getAllGiangVien(): array {
        try {
            $stmt = $this->conn->query("SELECT id, ten_dang_nhap, ho_ten FROM nguoi_dung WHERE vai_tro = 'giang_vien' ORDER BY id ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Lỗi DanhSachLopHocPhanModel::getAllGiangVien: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy thông tin học kỳ đang mở cổng đăng ký (trang_thai_dang_ky = 1)
     * Phục vụ hiển thị sidebar-semester-pill đồng bộ với Admin
     * 
     * @return array|null
     */
    public function getActiveHocKy(): ?array {
        try {
            // Ưu tiên học kỳ có cổng đăng ký đang mở
            $stmt = $this->conn->query("
                SELECT id, ten_hoc_ky, ngay_bat_dau, ngay_ket_thuc, thoi_gian_mo_dk, thoi_gian_dong_dk, trang_thai_dang_ky 
                FROM hoc_ky 
                WHERE trang_thai_dang_ky = 1 
                ORDER BY id DESC 
                LIMIT 1
            ");
            $active = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($active) {
                return $active;
            }

            // Fallback lấy học kỳ mới nhất nếu không có học kỳ nào đang mở
            $stmt = $this->conn->query("
                SELECT id, ten_hoc_ky, ngay_bat_dau, ngay_ket_thuc, thoi_gian_mo_dk, thoi_gian_dong_dk, trang_thai_dang_ky 
                FROM hoc_ky 
                ORDER BY id DESC 
                LIMIT 1
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Lỗi DanhSachLopHocPhanModel::getActiveHocKy: " . $e->getMessage());
            return null;
        }
    }
}
