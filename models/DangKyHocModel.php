
<?php

require_once __DIR__ . '/../config/database.php';

class DangKyHocModel
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Lấy danh sách lớp và số chỗ còn lại
    public function layDanhSachLop(): array
    {
        $sql = "
            SELECT
                lhp.id,
                lhp.ma_lop,
                hp.ma_hoc_phan,
                hp.ten_hoc_phan,
                hp.so_tin_chi,
                hk.ten_hoc_ky,
                nd.ho_ten AS ten_giang_vien,
                lhp.si_so_toi_da,
                lhp.trang_thai,
                COUNT(
                    CASE WHEN dkh.trang_thai = 'dang_ky'
                    THEN dkh.id END
                ) AS si_so_hien_tai
            FROM lop_hoc_phan lhp
            INNER JOIN hoc_phan hp ON hp.id = lhp.hoc_phan_id
            INNER JOIN hoc_ky hk ON hk.id = lhp.hoc_ky_id
            INNER JOIN nguoi_dung nd ON nd.id = lhp.giang_vien_id
            LEFT JOIN dang_ky_hoc dkh
                ON dkh.lop_hoc_phan_id = lhp.id
            GROUP BY
                lhp.id, lhp.ma_lop,
                hp.ma_hoc_phan, hp.ten_hoc_phan, hp.so_tin_chi,
                hk.ten_hoc_ky, nd.ho_ten,
                lhp.si_so_toi_da, lhp.trang_thai
            ORDER BY lhp.id
        ";

        return $this->conn->query($sql)->fetchAll();
    }

    // Đăng ký một lớp học phần
    public function dangKy(int $sinhVienId, int $lopId): array
    {
        try {
            $this->conn->beginTransaction();

            // Kiểm tra sinh viên
            $sql = "
                SELECT id
                FROM nguoi_dung
                WHERE id = :id AND vai_tro = 'sinh_vien'
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $sinhVienId]);

            if (!$stmt->fetch()) {
                throw new RuntimeException('Tài khoản sinh viên không hợp lệ.');
            }

            // Khóa dòng lớp để tránh hai lượt đăng ký chiếm cùng một chỗ cuối
            $sql = "
                SELECT
                    lhp.id,
                    lhp.si_so_toi_da,
                    lhp.trang_thai,
                    lhp.thoi_gian_mo_rieng,
                    lhp.thoi_gian_dong_rieng,
                    hk.trang_thai_dang_ky,
                    hk.thoi_gian_mo_dk,
                    hk.thoi_gian_dong_dk
                FROM lop_hoc_phan lhp
                INNER JOIN hoc_ky hk ON hk.id = lhp.hoc_ky_id
                WHERE lhp.id = :id
                FOR UPDATE
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $lopId]);
            $lop = $stmt->fetch();

            if (!$lop) {
                throw new RuntimeException('Lớp học phần không tồn tại.');
            }

            if ($lop['trang_thai'] !== 'mo') {
                throw new RuntimeException('Lớp học phần đã bị khóa.');
            }

            // Thời gian riêng của lớp được ưu tiên hơn thời gian học kỳ.
            $mo = $lop['thoi_gian_mo_rieng']
                ?? $lop['thoi_gian_mo_dk'];
            $dong = $lop['thoi_gian_dong_rieng']
                ?? $lop['thoi_gian_dong_dk'];

            $now = new DateTimeImmutable();

            if (
                $mo === null ||
                $dong === null ||
                $now < new DateTimeImmutable($mo) ||
                $now > new DateTimeImmutable($dong)
            ) {
                throw new RuntimeException(
                    'Chưa đến thời gian mở đăng ký hoặc đã hết hạn.'
                );
            }

            // Nếu lớp có thời gian riêng, cho phép mở ngoài đợt chung
            if (
                $lop['thoi_gian_mo_rieng'] === null &&
                !(bool) $lop['trang_thai_dang_ky']
            ) {
                throw new RuntimeException(
                    'Học kỳ hiện chưa mở đăng ký.'
                );
            }

            // Kiểm tra bản ghi đã tồn tại
            $sql = "
                SELECT id, trang_thai
                FROM dang_ky_hoc
                WHERE sinh_vien_id = :sv
                  AND lop_hoc_phan_id = :lop
                FOR UPDATE
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'sv' => $sinhVienId,
                'lop' => $lopId
            ]);
            $dangKyCu = $stmt->fetch();

            if ($dangKyCu && $dangKyCu['trang_thai'] === 'dang_ky') {
                throw new RuntimeException(
                    'Bạn đã đăng ký lớp học phần này rồi.'
                );
            }

            // Đếm số sinh viên đang đăng ký
            $sql = "
                SELECT COUNT(*)
                FROM dang_ky_hoc
                WHERE lop_hoc_phan_id = :lop
                  AND trang_thai = 'dang_ky'
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['lop' => $lopId]);
            $siSo = (int) $stmt->fetchColumn();

            if ($siSo >= (int) $lop['si_so_toi_da']) {
                throw new RuntimeException(
                    'Lớp đã đủ sĩ số, không thể đăng ký thêm.'
                );
            }

            if ($dangKyCu) {
                // Đăng ký lại: cập nhật bản ghi đã hủy
                $sql = "
                    UPDATE dang_ky_hoc
                    SET trang_thai = 'dang_ky',
                        ngay_dang_ky = CURRENT_TIMESTAMP,
                        ngay_huy = NULL
                    WHERE id = :id
                ";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute(['id' => $dangKyCu['id']]);
            } else {
                $sql = "
                    INSERT INTO dang_ky_hoc
                        (sinh_vien_id, lop_hoc_phan_id, trang_thai)
                    VALUES (:sv, :lop, 'dang_ky')
                ";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'sv' => $sinhVienId,
                    'lop' => $lopId
                ]);
            }

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Đăng ký học phần thành công!'
            ];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            if ($e instanceof RuntimeException) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }

            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi đăng ký. Vui lòng thử lại.'
            ];
        }
    }
}
