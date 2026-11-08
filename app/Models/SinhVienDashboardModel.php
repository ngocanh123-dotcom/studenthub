
<?php

class SinhVienDashboardModel
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Xác định học kỳ hiện tại.
     * Ưu tiên học kỳ đang trong thời gian đăng ký
     * và có lớp học phần.
     */
    public function getCurrentSemester(): ?array
    {
        try {
            // Bước 1: Tìm học kỳ đang mở đăng ký thực tế.
            // Không chỉ dựa vào ID mới nhất vì có thể tồn tại
            // nhiều học kỳ cùng được bật trạng thái đăng ký.

            $sql = "
                SELECT
                    hk.*,
                    (
                        SELECT COUNT(*)
                        FROM lop_hoc_phan lhp
                        WHERE lhp.hoc_ky_id = hk.id
                    ) AS tong_so_lop
                FROM hoc_ky hk
                WHERE hk.trang_thai_dang_ky = 1
                  AND (
                        hk.thoi_gian_mo_dk IS NULL
                        OR hk.thoi_gian_mo_dk <= NOW()
                  )
                  AND (
                        hk.thoi_gian_dong_dk IS NULL
                        OR hk.thoi_gian_dong_dk >= NOW()
                  )
                ORDER BY
                    tong_so_lop DESC,
                    hk.id ASC
                LIMIT 1
            ";

            $stmt = $this->pdo->query($sql);
            $hk = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            // Bước 2: Nếu không có học kỳ đang mở đăng ký,
            // tìm học kỳ có ngày bắt đầu và kết thúc bao gồm hôm nay.

            if (!$hk) {
                $sql = "
                    SELECT
                        hk.*,
                        (
                            SELECT COUNT(*)
                            FROM lop_hoc_phan lhp
                            WHERE lhp.hoc_ky_id = hk.id
                        ) AS tong_so_lop
                    FROM hoc_ky hk
                    WHERE CURDATE() BETWEEN hk.ngay_bat_dau
                                        AND hk.ngay_ket_thuc
                    ORDER BY
                        tong_so_lop DESC,
                        hk.id ASC
                    LIMIT 1
                ";

                $stmt = $this->pdo->query($sql);
                $hk = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            // Bước 3: Xác định trạng thái đăng ký của học kỳ.

            if ($hk) {
                $hk['registration_open'] = (
                    (int) $hk['trang_thai_dang_ky'] === 1
                    && (
                        empty($hk['thoi_gian_mo_dk'])
                        || strtotime($hk['thoi_gian_mo_dk']) <= time()
                    )
                    && (
                        empty($hk['thoi_gian_dong_dk'])
                        || strtotime($hk['thoi_gian_dong_dk']) >= time()
                    )
                );
            }

            return $hk;

        } catch (PDOException $e) {
            error_log(
                "StudentHub DashboardModel - Lỗi lấy học kỳ hiện tại: "
                . $e->getMessage()
            );

            return null;
        }
    }

    /**
     * Đếm tổng số lớp học phần đang mở trong kỳ.
     */
    public function countOpenClasses(int $semesterId): int
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM lop_hoc_phan
                WHERE hoc_ky_id = :semester_id
                  AND trang_thai = 'mo'
            ");

            $stmt->execute([
                ':semester_id' => $semesterId
            ]);

            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log(
                "StudentHub DashboardModel - Lỗi đếm lớp mở: "
                . $e->getMessage()
            );

            return 0;
        }
    }

    /**
     * Đếm số lớp và tính tổng số tín chỉ sinh viên đã đăng ký trong kỳ.
     */
    public function getRegistrationStats(
        int $studentId,
        int $semesterId
    ): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(DISTINCT dk.lop_hoc_phan_id) AS registered_classes,
                    COALESCE(SUM(hp.so_tin_chi), 0) AS registered_credits
                FROM dang_ky_hoc dk
                INNER JOIN lop_hoc_phan lhp
                    ON lhp.id = dk.lop_hoc_phan_id
                INNER JOIN hoc_phan hp
                    ON hp.id = lhp.hoc_phan_id
                WHERE dk.sinh_vien_id = :student_id
                  AND dk.trang_thai = 'dang_ky'
                  AND lhp.hoc_ky_id = :semester_id
            ");

            $stmt->execute([
                ':student_id' => $studentId,
                ':semester_id' => $semesterId,
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            return [
                'registered_classes' => (int) (
                    $row['registered_classes'] ?? 0
                ),
                'registered_credits' => (int) (
                    $row['registered_credits'] ?? 0
                ),
            ];

        } catch (PDOException $e) {
            error_log(
                "StudentHub DashboardModel - Lỗi lấy thống kê đăng ký: "
                . $e->getMessage()
            );

            return [
                'registered_classes' => 0,
                'registered_credits' => 0,
            ];
        }
    }

    /**
     * Lấy danh sách các lớp học phần đang mở
     * để hiển thị ở bảng gợi ý trên dashboard.
     */
    public function getOpenClassesList(
        int $studentId,
        int $semesterId,
        ?array $semester = null
    ): array {
        try {
            if ($semester === null) {
                $semester = $this->getCurrentSemester();
            }

            if ($semester === null) {
                return [];
            }

            $regEnabled = !empty($semester['registration_open']) ? 1 : 0;

            $moDk = $semester['thoi_gian_mo_dk'] ?? null;
            $dongDk = $semester['thoi_gian_dong_dk'] ?? null;

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

                INNER JOIN hoc_phan hp
                    ON hp.id = lhp.hoc_phan_id

                LEFT JOIN nguoi_dung nd
                    ON nd.id = lhp.giang_vien_id

                WHERE lhp.hoc_ky_id = :semester_id

                  AND lhp.trang_thai = 'mo'

                  -- Kiểm tra sĩ số còn chỗ.
                  AND (
                        SELECT COUNT(*)
                        FROM dang_ky_hoc enrolled
                        WHERE enrolled.lop_hoc_phan_id = lhp.id
                          AND enrolled.trang_thai = 'dang_ky'
                      ) < lhp.si_so_toi_da

                  -- Kiểm tra thời gian đăng ký.
                  AND (
                        (
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
                        )

                        OR

                        (
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

                  -- Không hiển thị môn sinh viên đã đăng ký.
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
                ':semester_id' => $semesterId,

                ':registration_enabled' => $regEnabled,

                ':registration_opens_at_is_null' => $moDk,
                ':registration_opens_at_value' => $moDk,

                ':registration_closes_at_is_null' => $dongDk,
                ':registration_closes_at_value' => $dongDk,

                ':student_id' => $studentId,
            ]);

            return $openClassesStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) {
            error_log(
                "StudentHub DashboardModel - Lỗi lấy danh sách lớp mở: "
                . $e->getMessage()
            );

            return [];
        }
    }

    /**
     * Phương thức tổng hợp đầy đủ số liệu cho Dashboard.
     */
    public function getDashboardData(int $studentId): array
    {
        $semester = $this->getCurrentSemester();

        if ($semester === null) {
            return [
                'semester' => null,
                'registration_open' => false,
                'registered_classes' => 0,
                'registered_credits' => 0,
                'open_classes' => [],

                'summary' => [
                    'so_lop_dang_mo' => 0,
                    'so_lop_da_dang_ky' => 0,
                    'tong_tin_chi_da_dk' => 0,
                ]
            ];
        }

        $semesterId = (int) $semester['id'];

        $openCount = $this->countOpenClasses($semesterId);

        $registered = $this->getRegistrationStats(
            $studentId,
            $semesterId
        );

        $openClasses = $this->getOpenClassesList(
            $studentId,
            $semesterId,
            $semester
        );

        return [
            'semester' => $semester,

            'registration_open' => (bool) (
                $semester['registration_open'] ?? false
            ),

            'registered_classes' => (int) (
                $registered['registered_classes'] ?? 0
            ),

            'registered_credits' => (int) (
                $registered['registered_credits'] ?? 0
            ),

            'open_classes' => $openClasses,

            'summary' => [
                'so_lop_dang_mo' => $openCount,

                'so_lop_da_dang_ky' => (int) (
                    $registered['registered_classes'] ?? 0
                ),

                'tong_tin_chi_da_dk' => (int) (
                    $registered['registered_credits'] ?? 0
                ),
            ]
        ];
    }
}
