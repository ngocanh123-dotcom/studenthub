<?php

class SinhVienDashboardModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getDashboardData(int $studentId): array
    {
        $semesterStatement = $this->pdo->query("
            SELECT
                id,
                ten_hoc_ky,
                ngay_bat_dau,
                ngay_ket_thuc,
                thoi_gian_mo_dk,
                thoi_gian_dong_dk,
                trang_thai_dang_ky,
                CASE
                    WHEN trang_thai_dang_ky = 1
                     AND (thoi_gian_mo_dk IS NULL OR thoi_gian_mo_dk <= NOW())
                     AND (thoi_gian_dong_dk IS NULL OR thoi_gian_dong_dk >= NOW())
                    THEN 1
                    ELSE 0
                END AS registration_open
            FROM hoc_ky
            ORDER BY
                CASE
                    WHEN CURDATE() BETWEEN ngay_bat_dau AND ngay_ket_thuc THEN 0
                    WHEN ngay_bat_dau > CURDATE() THEN 1
                    ELSE 2
                END,
                CASE
                    WHEN ngay_bat_dau > CURDATE() THEN ngay_bat_dau
                    ELSE NULL
                END ASC,
                ngay_bat_dau DESC
            LIMIT 1
        ");
        $semester = $semesterStatement->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($semester === null) {
            return [
                'semester' => null,
                'registration_open' => false,
                'registered_classes' => 0,
                'registered_credits' => 0,
                'open_classes' => [],
            ];
        }

        $registeredStatement = $this->pdo->prepare("
            SELECT
                COUNT(DISTINCT dk.lop_hoc_phan_id) AS registered_classes,
                COALESCE(SUM(hp.so_tin_chi), 0) AS registered_credits
            FROM dang_ky_hoc dk
            INNER JOIN lop_hoc_phan lhp ON lhp.id = dk.lop_hoc_phan_id
            INNER JOIN hoc_phan hp ON hp.id = lhp.hoc_phan_id
            WHERE dk.sinh_vien_id = :student_id
              AND dk.trang_thai = 'dang_ky'
              AND lhp.hoc_ky_id = :semester_id
        ");
        $registeredStatement->execute([
            ':student_id' => $studentId,
            ':semester_id' => (int) $semester['id'],
        ]);
        $registered = $registeredStatement->fetch(PDO::FETCH_ASSOC);

        $openClassesStatement = $this->pdo->prepare("
            SELECT
                lhp.id,
                lhp.ma_lop,
                lhp.si_so_toi_da,
                hp.id AS hoc_phan_id,
                hp.ma_hoc_phan,
                hp.ten_hoc_phan,
                hp.so_tin_chi,
                nd.ho_ten AS ten_giang_vien,
                COALESCE((
                    SELECT COUNT(*)
                    FROM dang_ky_hoc enrolled
                    WHERE enrolled.lop_hoc_phan_id = lhp.id
                      AND enrolled.trang_thai = 'dang_ky'
                ), 0) AS si_so_hien_tai
            FROM lop_hoc_phan lhp
            INNER JOIN hoc_phan hp ON hp.id = lhp.hoc_phan_id
            LEFT JOIN nguoi_dung nd ON nd.id = lhp.giang_vien_id
            WHERE lhp.hoc_ky_id = :semester_id
              AND lhp.trang_thai = 'mo'
              AND (
                    SELECT COUNT(*)
                    FROM dang_ky_hoc enrolled
                    WHERE enrolled.lop_hoc_phan_id = lhp.id
                      AND enrolled.trang_thai = 'dang_ky'
                  ) < lhp.si_so_toi_da
              AND (
                    (
                        lhp.thoi_gian_mo_rieng IS NOT NULL
                        OR lhp.thoi_gian_dong_rieng IS NOT NULL
                    )
                    AND (
                        lhp.thoi_gian_mo_rieng IS NULL
                        OR lhp.thoi_gian_mo_rieng <= NOW()
                    )
                    AND (
                        lhp.thoi_gian_dong_rieng IS NULL
                        OR lhp.thoi_gian_dong_rieng >= NOW()
                    )
                    OR (
                        lhp.thoi_gian_mo_rieng IS NULL
                        AND lhp.thoi_gian_dong_rieng IS NULL
                        AND :registration_enabled = 1
                        AND (
                            :registration_opens_at_is_null IS NULL
                            OR :registration_opens_at_value <= NOW()
                        )
                        AND (
                            :registration_closes_at_is_null IS NULL
                            OR :registration_closes_at_value >= NOW()
                        )
                    )
              )
              AND NOT EXISTS (
                    SELECT 1
                    FROM dang_ky_hoc own_registration
                    INNER JOIN lop_hoc_phan own_class
                        ON own_class.id = own_registration.lop_hoc_phan_id
                    WHERE own_registration.sinh_vien_id = :student_id
                      AND own_registration.trang_thai = 'dang_ky'
                      AND own_class.hoc_phan_id = lhp.hoc_phan_id
              )
            ORDER BY hp.ten_hoc_phan, lhp.ma_lop
        ");
        $openClassesStatement->execute([
            ':semester_id' => (int) $semester['id'],
            ':registration_enabled' => (int) $semester['trang_thai_dang_ky'],
            ':registration_opens_at_is_null' => $semester['thoi_gian_mo_dk'],
            ':registration_opens_at_value' => $semester['thoi_gian_mo_dk'],
            ':registration_closes_at_is_null' => $semester['thoi_gian_dong_dk'],
            ':registration_closes_at_value' => $semester['thoi_gian_dong_dk'],
            ':student_id' => $studentId,
        ]);
        $openClasses = $openClassesStatement->fetchAll(PDO::FETCH_ASSOC);

        return [
            'semester' => $semester,
            'registration_open' => (bool) $semester['registration_open'],
            'registered_classes' => (int) ($registered['registered_classes'] ?? 0),
            'registered_credits' => (int) ($registered['registered_credits'] ?? 0),
            'open_classes' => $openClasses,
        ];
    }
}
