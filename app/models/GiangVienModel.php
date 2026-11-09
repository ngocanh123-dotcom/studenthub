<?php
/**
 * app/Models/GiangVienModel.php
 * Model xử lý dữ liệu Phân hệ Giảng viên (Lecturer Model)
 * Kiến trúc: MVC - PHP 8.x - PDO Prepared Statements
 */

class GiangVienModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lấy thông tin tài khoản giảng viên từ bảng nguoi_dung
     */
    public function getLecturerInfo(?int $userId = 0, ?string $username = ''): ?array
    {
        if ($userId > 0) {
            $stmt = $this->pdo->prepare("
                SELECT id, ho_ten, ten_dang_nhap, email, so_dien_thoai 
                FROM nguoi_dung 
                WHERE id = :id AND vai_tro = 'giang_vien' 
                LIMIT 1
            ");
            $stmt->execute([':id' => $userId]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) {
                return $res;
            }
        }

        if (!empty($username)) {
            $stmt = $this->pdo->prepare("
                SELECT id, ho_ten, ten_dang_nhap, email, so_dien_thoai 
                FROM nguoi_dung 
                WHERE ten_dang_nhap = :username AND vai_tro = 'giang_vien' 
                LIMIT 1
            ");
            $stmt->execute([':username' => $username]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) {
                return $res;
            }
        }

        return null;
    }

    /**
     * Thống kê tổng quan Dashboard giảng viên:
     * - Số học phần phụ trách (tính các môn có lớp mở mà giảng viên phụ trách)
     * - Tổng số lớp phụ trách
     * - Tổng lượt sinh viên đăng ký vào các lớp của giảng viên này
     * - Danh sách học phần được phân công kèm số lớp
     *
     * @param int $giangVienId ID giảng viên trong bảng nguoi_dung
     * @return array
     */
    public function getDashboardStats(int $giangVienId): array
    {
        // 1. Lấy danh sách học phần và số lớp tương ứng
        $sqlCourses = "
            SELECT
                hp.id AS hoc_phan_id,
                hp.ma_hoc_phan,
                hp.ten_hoc_phan,
                COUNT(DISTINCT lhp.id) AS tong_lop
            FROM hoc_phan hp
            INNER JOIN lop_hoc_phan lhp ON lhp.hoc_phan_id = hp.id
            WHERE lhp.giang_vien_id = :gv_id
            GROUP BY hp.id, hp.ma_hoc_phan, hp.ten_hoc_phan
            ORDER BY hp.ma_hoc_phan ASC
        ";
        $stmtCourses = $this->pdo->prepare($sqlCourses);
        $stmtCourses->execute([':gv_id' => $giangVienId]);
        $courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $tongHocPhan = count($courses);
        $tongLop = 0;
        foreach ($courses as $c) {
            $tongLop += (int)$c['tong_lop'];
        }

        // 2. Tổng sinh viên đã đăng ký vào các lớp của giảng viên này
        $sqlStudents = "
            SELECT COUNT(
                DISTINCT CONCAT(
                    dk.lop_hoc_phan_id,
                    '-',
                    dk.sinh_vien_id
                )
            )
            FROM dang_ky_hoc dk
            INNER JOIN lop_hoc_phan lhp ON lhp.id = dk.lop_hoc_phan_id
            WHERE lhp.giang_vien_id = :gv_id
              AND dk.trang_thai = 'dang_ky'
        ";
        $stmtStudents = $this->pdo->prepare($sqlStudents);
        $stmtStudents->execute([':gv_id' => $giangVienId]);
        $tongSinhVien = (int)$stmtStudents->fetchColumn();

        return [
            'tongHocPhan'  => $tongHocPhan,
            'tongLop'      => $tongLop,
            'tongSinhVien' => $tongSinhVien,
            'courses'      => $courses
        ];
    }

    /**
     * Lấy thông tin chi tiết một học phần thuộc phân công của giảng viên
     */
    public function getHocPhanOfLecturer(int $hocPhanId, int $giangVienId): ?array
    {
        $sql = "
            SELECT DISTINCT
                hp.id,
                hp.ma_hoc_phan,
                hp.ten_hoc_phan,
                hp.so_tin_chi,
                hp.loai_hoc_phan
            FROM hoc_phan hp
            INNER JOIN lop_hoc_phan lhp ON lhp.hoc_phan_id = hp.id
            WHERE hp.id = :hp_id AND lhp.giang_vien_id = :gv_id
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':hp_id' => $hocPhanId,
            ':gv_id' => $giangVienId
        ]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Lấy danh sách các lớp học phần được phân công kèm sĩ số, lịch học, phòng học
     *
     * @param int $giangVienId ID giảng viên
     * @param int|null $hocPhanId ID học phần (nếu muốn lọc theo học phần cụ thể)
     * @return array
     */
    public function getDanhSachLop(int $giangVienId, ?int $hocPhanId = null): array
    {
        $sql = "
            SELECT
                lhp.id,
                lhp.ma_lop,
                lhp.lich_hoc,
                lhp.phong_hoc,
                hk.ten_hoc_ky,
                lhp.si_so_toi_da,
                lhp.trang_thai,
                COUNT(
                    DISTINCT CASE
                        WHEN dk.trang_thai = 'dang_ky'
                        THEN dk.sinh_vien_id
                    END
                ) AS si_so_hien_tai
            FROM lop_hoc_phan lhp
            INNER JOIN hoc_ky hk ON hk.id = lhp.hoc_ky_id
            LEFT JOIN dang_ky_hoc dk ON dk.lop_hoc_phan_id = lhp.id
            WHERE lhp.giang_vien_id = :gv_id
        ";

        $params = [':gv_id' => $giangVienId];

        if ($hocPhanId !== null && $hocPhanId > 0) {
            $sql .= " AND lhp.hoc_phan_id = :hp_id ";
            $params[':hp_id'] = $hocPhanId;
        }

        $sql .= "
            GROUP BY
                lhp.id,
                lhp.ma_lop,
                lhp.lich_hoc,
                lhp.phong_hoc,
                hk.ten_hoc_ky,
                lhp.si_so_toi_da,
                lhp.trang_thai
            ORDER BY lhp.ma_lop ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lấy thông tin lớp học phần thuộc quyền giảng viên
     */
    public function getLopOfLecturer(int $lopId, int $giangVienId): ?array
    {
        $sql = "
            SELECT
                lhp.id,
                lhp.ma_lop,
                hp.id AS hoc_phan_id,
                hp.ma_hoc_phan,
                hp.ten_hoc_phan,
                hk.ten_hoc_ky,
                lhp.lich_hoc,
                lhp.phong_hoc,
                lhp.si_so_toi_da
            FROM lop_hoc_phan lhp
            INNER JOIN hoc_phan hp ON hp.id = lhp.hoc_phan_id
            INNER JOIN hoc_ky hk ON hk.id = lhp.hoc_ky_id
            WHERE lhp.id = :lop_id AND lhp.giang_vien_id = :gv_id
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':lop_id' => $lopId,
            ':gv_id'  => $giangVienId
        ]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Lấy danh sách sinh viên đã ghi danh của lớp
     * Kèm kiểm tra an toàn đảm bảo lớp này thuộc quyền phụ trách của giảng viên
     *
     * @param int $lopId ID lớp học phần
     * @param int $giangVienId ID giảng viên
     * @return array|null Mảng gồm ['lop' => ..., 'sinhVienList' => ..., 'tongSinhVien' => ...] hoặc null nếu không hợp lệ
     */
    public function getDanhSachSinhVienTheoLop(int $lopId, int $giangVienId): ?array
    {
        // 1. Kiểm tra an toàn: lớp có thuộc quyền giảng viên không
        $lop = $this->getLopOfLecturer($lopId, $giangVienId);
        if (!$lop) {
            return null;
        }

        // 2. Lấy danh sách sinh viên đăng ký lớp
        $sql = "
            SELECT
                sv.id,
                sv.ten_dang_nhap,
                sv.ho_ten,
                sv.email,
                sv.so_dien_thoai,
                dk.ngay_dang_ky
            FROM dang_ky_hoc dk
            INNER JOIN nguoi_dung sv ON sv.id = dk.sinh_vien_id
            WHERE dk.lop_hoc_phan_id = :lop_id
              AND dk.trang_thai = 'dang_ky'
              AND sv.vai_tro = 'sinh_vien'
            ORDER BY sv.ten_dang_nhap ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':lop_id' => $lopId]);
        $sinhVienList = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'lop'          => $lop,
            'sinhVienList' => $sinhVienList,
            'tongSinhVien' => count($sinhVienList)
        ];
    }
}
