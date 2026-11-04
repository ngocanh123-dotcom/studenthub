<?php
/**
 * app/Models/AdminLopHocPhanModel.php
 * Model xử lý quản lý Lớp học phần dành cho Quản trị viên (Admin)
 * Kiến trúc: MVC - PHP 8.x - PDO Prepared Statements
 */

class AdminLopHocPhanModel {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * Lấy danh sách lớp học phần kèm bộ lọc tra cứu đa tiêu chí
     */
    public function getAllClasses(array $filters = []): array {
        $sql = "SELECT 
                    lhp.id,
                    lhp.ma_lop,
                    lhp.hoc_phan_id,
                    lhp.hoc_ky_id,
                    lhp.giang_vien_id,
                    lhp.si_so_toi_da,
                    lhp.trang_thai,
                    lhp.thoi_gian_mo_rieng,
                    lhp.thoi_gian_dong_rieng,
                    lhp.lich_hoc,
                    lhp.phong_hoc,
                    lhp.ngay_tao,
                    hp.ma_hoc_phan,
                    hp.ten_hoc_phan,
                    hp.so_tin_chi,
                    hk.ten_hoc_ky,
                    hk.thoi_gian_mo_dk,
                    hk.thoi_gian_dong_dk,
                    gv.ho_ten AS ten_giang_vien,
                    gv.email AS email_giang_vien,
                    (SELECT COUNT(*) 
                     FROM dang_ky_hoc dkh 
                     WHERE dkh.lop_hoc_phan_id = lhp.id 
                       AND dkh.trang_thai = 'dang_ky') AS si_so_hien_tai,
                    (SELECT COUNT(*) 
                     FROM dang_ky_hoc dkh_all 
                     WHERE dkh_all.lop_hoc_phan_id = lhp.id) AS tong_luot_dang_ky
                FROM lop_hoc_phan lhp
                INNER JOIN hoc_phan hp ON lhp.hoc_phan_id = hp.id
                INNER JOIN hoc_ky hk ON lhp.hoc_ky_id = hk.id
                LEFT JOIN nguoi_dung gv ON lhp.giang_vien_id = gv.id
                WHERE 1=1";

        $params = [];

        // Lọc theo từ khóa tìm kiếm (mã lớp, mã môn, tên môn)
        if (!empty($filters['tu_khoa'])) {
            $kw = '%' . trim($filters['tu_khoa']) . '%';
            $sql .= " AND (lhp.ma_lop LIKE :kw1 OR hp.ma_hoc_phan LIKE :kw2 OR hp.ten_hoc_phan LIKE :kw3)";
            $params[':kw1'] = $kw;
            $params[':kw2'] = $kw;
            $params[':kw3'] = $kw;
        }

        // Lọc theo học kỳ
        if (!empty($filters['hoc_ky_id']) && is_numeric($filters['hoc_ky_id'])) {
            $sql .= " AND lhp.hoc_ky_id = :hoc_ky_id";
            $params[':hoc_ky_id'] = (int)$filters['hoc_ky_id'];
        }

        // Lọc theo học phần
        if (!empty($filters['hoc_phan_id']) && is_numeric($filters['hoc_phan_id'])) {
            $sql .= " AND lhp.hoc_phan_id = :hoc_phan_id";
            $params[':hoc_phan_id'] = (int)$filters['hoc_phan_id'];
        }

        // Lọc theo giảng viên
        if (!empty($filters['giang_vien_id']) && is_numeric($filters['giang_vien_id'])) {
            $sql .= " AND lhp.giang_vien_id = :giang_vien_id";
            $params[':giang_vien_id'] = (int)$filters['giang_vien_id'];
        }

        // Lọc theo trạng thái ('mo' hoặc 'khoa')
        if (!empty($filters['trang_thai']) && in_array($filters['trang_thai'], ['mo', 'khoa'], true)) {
            $sql .= " AND lhp.trang_thai = :trang_thai";
            $params[':trang_thai'] = $filters['trang_thai'];
        }

        $sql .= " ORDER BY lhp.hoc_ky_id DESC, lhp.id DESC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Lỗi AdminLopHocPhanModel::getAllClasses: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy thông tin chi tiết của 1 lớp học phần theo ID
     */
    public function getClassById(int $id): ?array {
        $sql = "SELECT 
                    lhp.*,
                    hp.ma_hoc_phan,
                    hp.ten_hoc_phan,
                    hp.so_tin_chi,
                    hk.ten_hoc_ky,
                    gv.ho_ten AS ten_giang_vien,
                    gv.email AS email_giang_vien,
                    (SELECT COUNT(*) 
                     FROM dang_ky_hoc dkh 
                     WHERE dkh.lop_hoc_phan_id = lhp.id 
                       AND dkh.trang_thai = 'dang_ky') AS si_so_hien_tai,
                    (SELECT COUNT(*) 
                     FROM dang_ky_hoc dkh_all 
                     WHERE dkh_all.lop_hoc_phan_id = lhp.id) AS tong_luot_dang_ky
                FROM lop_hoc_phan lhp
                INNER JOIN hoc_phan hp ON lhp.hoc_phan_id = hp.id
                INNER JOIN hoc_ky hk ON lhp.hoc_ky_id = hk.id
                LEFT JOIN nguoi_dung gv ON lhp.giang_vien_id = gv.id
                WHERE lhp.id = :id
                LIMIT 1";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Lỗi AdminLopHocPhanModel::getClassById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Kiểm tra mã lớp đã tồn tại chưa (trừ ID hiện tại nếu là cập nhật)
     */
    public function checkMaLopExists(string $maLop, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM lop_hoc_phan WHERE ma_lop = :ma_lop";
        $params = [':ma_lop' => trim($maLop)];

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= " AND id <> :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Lỗi AdminLopHocPhanModel::checkMaLopExists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tìm lớp cùng học kỳ có lịch học và phòng học trùng nhau
     */
    public function findScheduleRoomConflict(
        int $hocKyId,
        string $lichHoc,
        string $phongHoc,
        ?int $excludeId = null
    ): ?array {
        $sql = "SELECT id, ma_lop, lich_hoc, phong_hoc
                FROM lop_hoc_phan
                WHERE hoc_ky_id = :hoc_ky_id
                  AND LOWER(TRIM(lich_hoc)) = LOWER(TRIM(:lich_hoc))
                  AND LOWER(TRIM(phong_hoc)) = LOWER(TRIM(:phong_hoc))";
        $params = [
            ':hoc_ky_id' => $hocKyId,
            ':lich_hoc' => trim($lichHoc),
            ':phong_hoc' => trim($phongHoc),
        ];

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= " AND id <> :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $conflict = $stmt->fetch(PDO::FETCH_ASSOC);

        return $conflict ?: null;
    }

    /**
     * Tạo lớp học phần mới
     */
    public function createClass(array $data): bool {
        $sql = "INSERT INTO lop_hoc_phan (
                    ma_lop, 
                    hoc_phan_id, 
                    hoc_ky_id, 
                    giang_vien_id, 
                    si_so_toi_da, 
                    trang_thai, 
                    thoi_gian_mo_rieng, 
                    thoi_gian_dong_rieng, 
                    lich_hoc, 
                    phong_hoc
                ) VALUES (
                    :ma_lop, 
                    :hoc_phan_id, 
                    :hoc_ky_id, 
                    :giang_vien_id, 
                    :si_so_toi_da, 
                    :trang_thai, 
                    :thoi_gian_mo_rieng, 
                    :thoi_gian_dong_rieng, 
                    :lich_hoc, 
                    :phong_hoc
                )";

        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':ma_lop'               => trim($data['ma_lop']),
                ':hoc_phan_id'          => (int)$data['hoc_phan_id'],
                ':hoc_ky_id'            => (int)$data['hoc_ky_id'],
                ':giang_vien_id'        => !empty($data['giang_vien_id']) ? (int)$data['giang_vien_id'] : null,
                ':si_so_toi_da'         => max(1, (int)$data['si_so_toi_da']),
                ':trang_thai'           => in_array($data['trang_thai'] ?? 'mo', ['mo', 'khoa'], true) ? $data['trang_thai'] : 'mo',
                ':thoi_gian_mo_rieng'   => !empty($data['thoi_gian_mo_rieng']) ? $data['thoi_gian_mo_rieng'] : null,
                ':thoi_gian_dong_rieng' => !empty($data['thoi_gian_dong_rieng']) ? $data['thoi_gian_dong_rieng'] : null,
                ':lich_hoc'             => !empty($data['lich_hoc']) ? trim($data['lich_hoc']) : null,
                ':phong_hoc'            => !empty($data['phong_hoc']) ? trim($data['phong_hoc']) : null,
            ]);
        } catch (PDOException $e) {
            error_log("Lỗi AdminLopHocPhanModel::createClass: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật thông tin lớp học phần
     */
    public function updateClass(int $id, array $data): bool {
        $sql = "UPDATE lop_hoc_phan SET
                    ma_lop = :ma_lop,
                    hoc_phan_id = :hoc_phan_id,
                    hoc_ky_id = :hoc_ky_id,
                    giang_vien_id = :giang_vien_id,
                    si_so_toi_da = :si_so_toi_da,
                    trang_thai = :trang_thai,
                    thoi_gian_mo_rieng = :thoi_gian_mo_rieng,
                    thoi_gian_dong_rieng = :thoi_gian_dong_rieng,
                    lich_hoc = :lich_hoc,
                    phong_hoc = :phong_hoc
                WHERE id = :id";

        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id'                   => $id,
                ':ma_lop'               => trim($data['ma_lop']),
                ':hoc_phan_id'          => (int)$data['hoc_phan_id'],
                ':hoc_ky_id'            => (int)$data['hoc_ky_id'],
                ':giang_vien_id'        => !empty($data['giang_vien_id']) ? (int)$data['giang_vien_id'] : null,
                ':si_so_toi_da'         => max(1, (int)$data['si_so_toi_da']),
                ':trang_thai'           => in_array($data['trang_thai'] ?? 'mo', ['mo', 'khoa'], true) ? $data['trang_thai'] : 'mo',
                ':thoi_gian_mo_rieng'   => !empty($data['thoi_gian_mo_rieng']) ? $data['thoi_gian_mo_rieng'] : null,
                ':thoi_gian_dong_rieng' => !empty($data['thoi_gian_dong_rieng']) ? $data['thoi_gian_dong_rieng'] : null,
                ':lich_hoc'             => !empty($data['lich_hoc']) ? trim($data['lich_hoc']) : null,
                ':phong_hoc'            => !empty($data['phong_hoc']) ? trim($data['phong_hoc']) : null,
            ]);
        } catch (PDOException $e) {
            error_log("Lỗi AdminLopHocPhanModel::updateClass: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cầu dao Mở / Khóa trạng thái lớp (Toggle Switch - Demo Kịch bản Mục H)
     * Chuyển đổi giữa 'mo' và 'khoa'
     * @return string|false Trạng thái mới ('mo' hoặc 'khoa') hoặc false nếu thất bại
     */
    public function toggleStatus(int $id): string|false {
        $class = $this->getClassById($id);
        if (!$class) {
            return false;
        }

        $newStatus = ($class['trang_thai'] === 'mo') ? 'khoa' : 'mo';

        $sql = "UPDATE lop_hoc_phan SET trang_thai = :new_status WHERE id = :id";
        try {
            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([
                ':new_status' => $newStatus,
                ':id'         => $id
            ]);

            return $success ? $newStatus : false;
        } catch (PDOException $e) {
            error_log("Lỗi AdminLopHocPhanModel::toggleStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra ràng buộc an toàn trước khi xóa lớp
     * Không cho phép xóa nếu đã có sinh viên trong dang_ky_hoc
     * @return array ['can_delete' => bool, 'enrollment_count' => int]
     */
    public function checkCanDelete(int $id): array {
        $sql = "SELECT COUNT(*) FROM dang_ky_hoc WHERE lop_hoc_phan_id = :id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $id]);
            $count = (int)$stmt->fetchColumn();

            return [
                'can_delete'       => ($count === 0),
                'enrollment_count' => $count
            ];
        } catch (PDOException $e) {
            error_log("Lỗi AdminLopHocPhanModel::checkCanDelete: " . $e->getMessage());
            return [
                'can_delete'       => false,
                'enrollment_count' => 999
            ];
        }
    }

    /**
     * Xóa lớp học phần (Chỉ khi chưa có sinh viên đăng ký)
     */
    public function deleteClass(int $id): bool {
        $check = $this->checkCanDelete($id);
        if (!$check['can_delete']) {
            return false;
        }

        $sql = "DELETE FROM lop_hoc_phan WHERE id = :id";
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Lỗi AdminLopHocPhanModel::deleteClass: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Danh sách tất cả học phần để điền vào dropdown
     */
    public function getAllHocPhan(): array {
        $sql = "SELECT id, ma_hoc_phan, ten_hoc_phan, so_tin_chi 
                FROM hoc_phan 
                ORDER BY ma_hoc_phan ASC";
        try {
            return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Lỗi AdminLopHocPhanModel::getAllHocPhan: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Danh sách tất cả học kỳ để điền vào dropdown
     */
    public function getAllHocKy(): array {
        $sql = "SELECT id, ten_hoc_ky, ngay_bat_dau, ngay_ket_thuc, trang_thai_dang_ky 
                FROM hoc_ky 
                ORDER BY id DESC";
        try {
            return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Lỗi AdminLopHocPhanModel::getAllHocKy: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Danh sách giảng viên để phân công phụ trách lớp
     */
    public function getAllGiangVien(): array {
        $sql = "SELECT id, ho_ten, email, ten_dang_nhap 
                FROM nguoi_dung 
                WHERE vai_tro = 'giang_vien' 
                ORDER BY ho_ten ASC";
        try {
            return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Lỗi AdminLopHocPhanModel::getAllGiangVien: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Thống kê tổng quan cho trang quản trị
     */
    public function getStatistics(): array {
        $stats = [
            'tong_lop'         => 0,
            'lop_dang_mo'      => 0,
            'lop_dang_khoa'    => 0,
            'tong_sv_ghi_danh' => 0
        ];

        try {
            // Tổng lớp
            $stmt = $this->conn->query("SELECT COUNT(*) FROM lop_hoc_phan");
            $stats['tong_lop'] = (int)$stmt->fetchColumn();

            // Lớp đang mở
            $stmt = $this->conn->query("SELECT COUNT(*) FROM lop_hoc_phan WHERE trang_thai = 'mo'");
            $stats['lop_dang_mo'] = (int)$stmt->fetchColumn();

            // Lớp đang khóa
            $stmt = $this->conn->query("SELECT COUNT(*) FROM lop_hoc_phan WHERE trang_thai = 'khoa'");
            $stats['lop_dang_khoa'] = (int)$stmt->fetchColumn();

            // Tổng lượt sinh viên ghi danh hợp lệ
            $stmt = $this->conn->query("SELECT COUNT(*) FROM dang_ky_hoc WHERE trang_thai = 'dang_ky'");
            $stats['tong_sv_ghi_danh'] = (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Lỗi AdminLopHocPhanModel::getStatistics: " . $e->getMessage());
        }

        return $stats;
    }
}
