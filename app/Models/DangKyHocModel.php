
<?php

require_once __DIR__ . '/../../config/database.php';

class DangKyHocModel
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Lấy danh sách lớp học phần
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
                    CASE
                        WHEN dkh.trang_thai = 'dang_ky'
                        THEN dkh.id
                    END
                ) AS si_so_hien_tai
            FROM lop_hoc_phan lhp
            INNER JOIN hoc_phan hp
                ON hp.id = lhp.hoc_phan_id
            INNER JOIN hoc_ky hk
                ON hk.id = lhp.hoc_ky_id
            LEFT JOIN nguoi_dung nd
                ON nd.id = lhp.giang_vien_id
            LEFT JOIN dang_ky_hoc dkh
                ON dkh.lop_hoc_phan_id = lhp.id
            GROUP BY
                lhp.id,
                lhp.ma_lop,
                hp.ma_hoc_phan,
                hp.ten_hoc_phan,
                hp.so_tin_chi,
                hk.ten_hoc_ky,
                nd.ho_ten,
                lhp.si_so_toi_da,
                lhp.trang_thai
            ORDER BY lhp.id DESC
        ";

        return $this->conn->query($sql)->fetchAll();
    }

    // Đăng ký lớp học phần
    public function dangKy(int $sinhVienId, int $lopId): array
    {
        if ($sinhVienId <= 0 || $lopId <= 0) {
            return [
                'success' => false,
                'message' => 'Thông tin đăng ký không hợp lệ.'
            ];
        }

        try {
            $this->conn->beginTransaction();

            // Kiểm tra tài khoản sinh viên
            $sql = "
                SELECT id
                FROM nguoi_dung
                WHERE id = :id
                  AND vai_tro = 'sinh_vien'
                LIMIT 1
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $sinhVienId]);

            if (!$stmt->fetch()) {
                throw new RuntimeException(
                    'Tài khoản không tồn tại hoặc không phải sinh viên.'
                );
            }

            // Lấy thông tin lớp và học kỳ
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
                INNER JOIN hoc_ky hk
                    ON hk.id = lhp.hoc_ky_id
                WHERE lhp.id = :lop_id
                FOR UPDATE
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['lop_id' => $lopId]);
            $lop = $stmt->fetch();

            if (!$lop) {
                throw new RuntimeException(
                    'Không tìm thấy lớp học phần.'
                );
            }

            if ($lop['trang_thai'] !== 'mo') {
                throw new RuntimeException(
                    'Lớp học phần hiện không mở đăng ký.'
                );
            }

            // Ưu tiên thời gian riêng của lớp nếu được thiết lập.
            $gioMo = !empty($lop['thoi_gian_mo_rieng'])
                ? $lop['thoi_gian_mo_rieng']
                : $lop['thoi_gian_mo_dk'];

            $gioDong = !empty($lop['thoi_gian_dong_rieng'])
                ? $lop['thoi_gian_dong_rieng']
                : $lop['thoi_gian_dong_dk'];

            $coThoiGianRieng =
                !empty($lop['thoi_gian_mo_rieng']) ||
                !empty($lop['thoi_gian_dong_rieng']);

            if (
                !$coThoiGianRieng &&
                (int) $lop['trang_thai_dang_ky'] !== 1
            ) {
                throw new RuntimeException(
                    'Học kỳ hiện chưa mở đăng ký.'
                );
            }

            if (empty($gioMo) || empty($gioDong)) {
                throw new RuntimeException(
                    'Chưa thiết lập thời gian mở và đóng đăng ký.'
                );
            }

            $now = new DateTimeImmutable();
            $start = new DateTimeImmutable($gioMo);
            $end = new DateTimeImmutable($gioDong);

            if ($now < $start || $now > $end) {
                throw new RuntimeException(
                    'Hiện không nằm trong thời gian đăng ký học phần.'
                );
            }

            // Kiểm tra đăng ký hiện có
            $sql = "
                SELECT id, trang_thai
                FROM dang_ky_hoc
                WHERE sinh_vien_id = :sinh_vien_id
                  AND lop_hoc_phan_id = :lop_id
                LIMIT 1
                FOR UPDATE
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'sinh_vien_id' => $sinhVienId,
                'lop_id' => $lopId
            ]);

            $dangKyCu = $stmt->fetch();

            if (
                $dangKyCu &&
                $dangKyCu['trang_thai'] === 'dang_ky'
            ) {
                throw new RuntimeException(
                    'Bạn đã đăng ký lớp học phần này rồi.'
                );
            }

            // Kiểm tra sĩ số lớp
            $sql = "
                SELECT COUNT(*)
                FROM dang_ky_hoc
                WHERE lop_hoc_phan_id = :lop_id
                  AND trang_thai = 'dang_ky'
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['lop_id' => $lopId]);

            $siSoHienTai = (int) $stmt->fetchColumn();

            if ($siSoHienTai >= (int) $lop['si_so_toi_da']) {
                throw new RuntimeException(
                    'Lớp đã đủ sĩ số. Vui lòng chọn lớp khác.'
                );
            }

            // Khôi phục đăng ký đã hủy hoặc tạo đăng ký mới
            if ($dangKyCu) {
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
                        (
                            sinh_vien_id,
                            lop_hoc_phan_id,
                            trang_thai,
                            ngay_dang_ky,
                            ngay_huy
                        )
                    VALUES
                        (
                            :sinh_vien_id,
                            :lop_id,
                            'dang_ky',
                            CURRENT_TIMESTAMP,
                            NULL
                        )
                ";

                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'sinh_vien_id' => $sinhVienId,
                    'lop_id' => $lopId
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

            error_log(
                'DangKyHocModel: ' . $e->getMessage()
            );

            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đăng ký học phần. Vui lòng kiểm tra lại cấu trúc database.'
            ];
        }
    }
}
