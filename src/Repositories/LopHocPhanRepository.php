<?php
declare(strict_types=1);

/**
 * File: src/Repositories/LopHocPhanRepository.php
 * Module: Quản lý chi tiết Lớp học phần (StudentHub)
 * Phụ trách: Tạ Như Quỳnh - Bài tập về nhà 6
 * Kiến trúc: Repository Pattern - PHP 8.x OOP - PDO Prepared Statements
 */

class LopHocPhanRepository {
    private ?PDO $pdo;

    /**
     * Khởi tạo Repository với Dependency Injection đối tượng PDO
     *
     * @param PDO|null $pdo Đối tượng kết nối PDO (nếu null sẽ tự động nạp từ config/database.php)
     */
    public function __construct(?PDO $pdo = null) {
        if ($pdo === null) {
            $configFile = __DIR__ . '/../../config/database.php';
            if (file_exists($configFile)) {
                require_once $configFile;
                $pdo = $pdo ?? ($conn ?? null);
            }
        }
        $this->pdo = $pdo;
    }

    /**
     * Truy vấn chi tiết một lớp học phần theo ID
     * Thực hiện:
     * - INNER JOIN với bảng `hoc_phan` (mã, tên môn, tín chỉ, dieu_kien_tien_quyet, mo_ta)
     * - LEFT JOIN với `nguoi_dung` (thông tin giảng viên)
     * - LEFT JOIN với `hoc_ky` (thời gian triển khai, hạn mở cổng đăng ký)
     * - Subquery đếm sĩ số thực tế từ `dang_ky_hoc`
     *
     * @param int $id ID lớp học phần
     * @return array|null Mảng dữ liệu chi tiết lớp hoặc null nếu không tìm thấy
     */
    public function findById(int $id): ?array {
        if (!$this->pdo instanceof PDO) {
            return null;
        }

        $sql = "SELECT 
                    lhp.id AS lop_id,
                    lhp.id,
                    lhp.ma_lop,
                    lhp.hoc_phan_id,
                    lhp.hoc_ky_id,
                    lhp.giang_vien_id,
                    lhp.si_so_toi_da,
                    lhp.trang_thai AS trang_thai_lop,
                    lhp.trang_thai,
                    lhp.thoi_gian_mo_rieng,
                    lhp.thoi_gian_dong_rieng,
                    lhp.lich_hoc,
                    lhp.phong_hoc,
                    lhp.ngay_tao,
                    hp.ma_hoc_phan,
                    hp.ten_hoc_phan,
                    hp.so_tin_chi,
                    hp.loai_hoc_phan,
                    hp.mo_ta AS mo_ta_hoc_phan,
                    hp.mo_ta,
                    hp.dieu_kien_tien_quyet,
                    hk.ten_hoc_ky,
                    hk.ngay_bat_dau,
                    hk.ngay_ket_thuc,
                    hk.thoi_gian_mo_dk,
                    hk.thoi_gian_dong_dk,
                    hk.trang_thai_dang_ky,
                    gv.ho_ten AS ten_giang_vien,
                    gv.email AS email_giang_vien,
                    (SELECT COUNT(*) 
                     FROM dang_ky_hoc dkh 
                     WHERE dkh.lop_hoc_phan_id = lhp.id AND dkh.trang_thai = 'dang_ky') AS si_so_hien_tai
                FROM lop_hoc_phan lhp
                INNER JOIN hoc_phan hp ON lhp.hoc_phan_id = hp.id
                LEFT JOIN nguoi_dung gv ON lhp.giang_vien_id = gv.id
                LEFT JOIN hoc_ky hk ON lhp.hoc_ky_id = hk.id
                WHERE lhp.id = :id
                LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Lỗi LopHocPhanRepository::findById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Kiểm tra trạng thái ghi danh của sinh viên hiện tại kèm thời gian đăng ký
     *
     * @param int $lopId ID lớp học phần
     * @param int $sinhVienId ID sinh viên cần kiểm tra
     * @return array|null Mảng thông tin đăng ký (chứa ngày đăng ký, trạng thái) hoặc null nếu chưa đăng ký
     */
    public function isRegistered(int $lopId, int $sinhVienId): ?array {
        if (!$this->pdo instanceof PDO) {
            return null;
        }

        $sql = "SELECT 
                    dkh.id,
                    dkh.sinh_vien_id,
                    dkh.lop_hoc_phan_id,
                    dkh.trang_thai,
                    dkh.ngay_dang_ky,
                    dkh.ngay_huy
                FROM dang_ky_hoc dkh
                WHERE dkh.lop_hoc_phan_id = :lop_id 
                  AND dkh.sinh_vien_id = :sinh_vien_id 
                  AND dkh.trang_thai = 'dang_ky'
                LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':lop_id', $lopId, PDO::PARAM_INT);
            $stmt->bindValue(':sinh_vien_id', $sinhVienId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Lỗi LopHocPhanRepository::isRegistered: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy danh sách lớp học phần theo học kỳ phục vụ điều hướng quay lại danh mục
     *
     * @param int $hocKyId ID học kỳ
     * @return array Danh sách các lớp học phần thuộc học kỳ
     */
    public function getAllByHocKy(int $hocKyId): array {
        if (!$this->pdo instanceof PDO) {
            return [];
        }

        $sql = "SELECT 
                    lhp.id AS lop_id,
                    lhp.id,
                    lhp.ma_lop,
                    lhp.si_so_toi_da,
                    lhp.trang_thai AS trang_thai_lop,
                    lhp.trang_thai,
                    lhp.thoi_gian_mo_rieng,
                    lhp.thoi_gian_dong_rieng,
                    lhp.lich_hoc,
                    lhp.phong_hoc,
                    hp.ma_hoc_phan,
                    hp.ten_hoc_phan,
                    hp.so_tin_chi,
                    hp.dieu_kien_tien_quyet,
                    hp.mo_ta AS mo_ta_hoc_phan,
                    hk.ten_hoc_ky,
                    gv.ho_ten AS ten_giang_vien,
                    gv.email AS email_giang_vien,
                    (SELECT COUNT(*) 
                     FROM dang_ky_hoc dkh 
                     WHERE dkh.lop_hoc_phan_id = lhp.id AND dkh.trang_thai = 'dang_ky') AS si_so_hien_tai
                FROM lop_hoc_phan lhp
                INNER JOIN hoc_phan hp ON lhp.hoc_phan_id = hp.id
                LEFT JOIN nguoi_dung gv ON lhp.giang_vien_id = gv.id
                LEFT JOIN hoc_ky hk ON lhp.hoc_ky_id = hk.id
                WHERE lhp.hoc_ky_id = :hoc_ky_id
                ORDER BY lhp.ma_lop ASC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':hoc_ky_id', $hocKyId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Lỗi LopHocPhanRepository::getAllByHocKy: " . $e->getMessage());
            return [];
        }
    }
}

// Thiết lập alias namespace đảm bảo tương thích mọi cơ chế autoloader
if (!class_exists('Repositories\LopHocPhanRepository')) {
    class_alias('LopHocPhanRepository', 'Repositories\LopHocPhanRepository');
}
if (!class_exists('App\Repositories\LopHocPhanRepository')) {
    class_alias('LopHocPhanRepository', 'App\Repositories\LopHocPhanRepository');
}
