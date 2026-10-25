<?php

class GiangVienModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Lấy thông tin giảng viên theo tài khoản đăng nhập
    public function layGiangVienTheoTaiKhoan(int $maTaiKhoan): ?array
    {
        $sql = "SELECT MaGV, HoTen, Email
                FROM GiangVien
                WHERE MaTaiKhoan = :maTaiKhoan";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['maTaiKhoan' => $maTaiKhoan]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    // Lấy các lớp do giảng viên phụ trách
    public function layDanhSachLop(string $maGV): array
    {
        $sql = "SELECT
                    lhp.MaLopHocPhan,
                    lhp.MaHocPhan,
                    lhp.TenHocPhan,
                    lhp.SoTinChi,
                    lhp.TenLop,
                    hk.TenHocKy,
                    hk.NamHoc,
                    lhp.SiSoToiDa,
                    lhp.TrangThai,
                    (
                        SELECT COUNT(*)
                        FROM DangKy dk
                        WHERE dk.MaLopHocPhan = lhp.MaLopHocPhan
                          AND dk.TrangThai = 'DaDangKy'
                    ) AS SoSinhVien
                FROM LopHocPhan lhp
                INNER JOIN HocKy hk
                    ON lhp.MaHocKy = hk.MaHocKy
                WHERE lhp.MaGV = :maGV
                ORDER BY hk.NamHoc DESC, lhp.MaLopHocPhan";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['maGV' => $maGV]);

        return $stmt->fetchAll();
    }

    // Lấy sinh viên đăng ký một lớp thuộc giảng viên hiện tại
    public function laySinhVienTheoLop(
        string $maLopHocPhan,
        string $maGV
    ): array {
        $sql = "SELECT
                    sv.MaSV,
                    sv.HoTen,
                    sv.Email,
                    sv.SoDienThoai,
                    dk.NgayDangKy,
                    dk.TrangThai
                FROM LopHocPhan lhp
                INNER JOIN DangKy dk
                    ON lhp.MaLopHocPhan = dk.MaLopHocPhan
                INNER JOIN SinhVien sv
                    ON dk.MaSV = sv.MaSV
                WHERE lhp.MaLopHocPhan = :maLop
                  AND lhp.MaGV = :maGV
                  AND dk.TrangThai = 'DaDangKy'
                ORDER BY sv.MaSV";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'maLop' => $maLopHocPhan,
            'maGV' => $maGV
        ]);

        return $stmt->fetchAll();
    }

    // Kiểm tra lớp có thuộc giảng viên hay không
    public function kiemTraLop(
        string $maLopHocPhan,
        string $maGV
    ): bool {
        $sql = "SELECT COUNT(*)
                FROM LopHocPhan
                WHERE MaLopHocPhan = :maLop
                  AND MaGV = :maGV";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'maLop' => $maLopHocPhan,
            'maGV' => $maGV
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }
}