<?php

class AdminDashboardModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getStatistics(): array
    {
        $statement = $this->pdo->query("
            SELECT
                (SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'sinh_vien') AS tong_sinh_vien,
                (SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'giang_vien') AS tong_giang_vien,
                (SELECT COUNT(*) FROM hoc_phan) AS tong_hoc_phan,
                (SELECT COUNT(*) FROM lop_hoc_phan) AS tong_lop_hoc_phan
        ");

        $statistics = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($statistics)) {
            throw new RuntimeException('Không nhận được số liệu tổng quan từ cơ sở dữ liệu.');
        }

        return [
            'tongSinhVien' => (int) $statistics['tong_sinh_vien'],
            'tongGiangVien' => (int) $statistics['tong_giang_vien'],
            'tongHocPhan' => (int) $statistics['tong_hoc_phan'],
            'tongLopHocPhan' => (int) $statistics['tong_lop_hoc_phan'],
        ];
    }
}
