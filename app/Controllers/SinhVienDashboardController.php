<?php

require_once __DIR__ . '/../models/SinhVienDashboardModel.php';

class SinhVienDashboardController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Bước 1 (Xác định Sinh viên): Lấy nguoi_dung_id từ $_SESSION['user_id']
        $nguoiDungId = (int) ($_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 0);
        $sinhVienId = $nguoiDungId;

        // Nếu bảng sinh_vien tách riêng, truy vấn lấy ngay sinh_vien_id
        try {
            $hasSvTable = $this->pdo->query("SHOW TABLES LIKE 'sinh_vien'")->fetch();
            if ($hasSvTable) {
                $svStmt = $this->pdo->prepare("SELECT id FROM sinh_vien WHERE nguoi_dung_id = :uid LIMIT 1");
                $svStmt->execute([':uid' => $nguoiDungId]);
                $svRow = $svStmt->fetch(PDO::FETCH_ASSOC);
                if ($svRow && !empty($svRow['id'])) {
                    $sinhVienId = (int) $svRow['id'];
                }
            }
        } catch (PDOException $e) {
            // Dùng mặc định $sinhVienId = $nguoiDungId
        }

        // Bước 2 (Xác định Học kỳ hiện tại TRƯỚC TIÊN):
        $model = new SinhVienDashboardModel($this->pdo);
        $hocKyHienTai = $model->getCurrentSemester();
        $hocKyId = (int) ($hocKyHienTai['id'] ?? 0);

        // Đồng thời lưu luôn vào session
        $_SESSION['current_hoc_ky_id'] = $hocKyId;

        // Bước 3 (Thực hiện đếm thống kê SAU KHI đã có $hocKyId và $sinhVienId):
        $soLopDangMo = 0;
        $soLopDaDangKy = 0;
        $tongTinChiDK = 0;
        $cacLopMo = [];
        $dashboardError = '';

        if ($hocKyId > 0) {
            try {
                // Đếm tổng số lớp học phần đang mở trong kỳ
                $soLopDangMo = $model->countOpenClasses($hocKyId);

                // Đếm số lớp sinh viên đã đăng ký và tính tổng số tín chỉ tích lũy trong kỳ
                $regStats = $model->getRegistrationStats($sinhVienId, $hocKyId);
                $soLopDaDangKy = (int) ($regStats['registered_classes'] ?? 0);
                $tongTinChiDK  = (int) ($regStats['registered_credits'] ?? 0);

                // Lấy danh sách lớp mở để hiển thị ở bảng
                $cacLopMo = $model->getOpenClassesList($sinhVienId, $hocKyId, $hocKyHienTai);
            } catch (PDOException $e) {
                error_log('StudentHub Student Dashboard: ' . $e->getMessage());
                $dashboardError = 'Không thể tải số liệu học tập. Vui lòng kiểm tra cơ sở dữ liệu.';
            }
        }

        // Bước 4: Truyền dữ liệu hoàn chỉnh này sang View để render giao diện ngay trong lần load đầu tiên
        $dashboardData = [
            'semester' => $hocKyHienTai,
            'registration_open' => (bool) ($hocKyHienTai['registration_open'] ?? false),
            'registered_classes' => $soLopDaDangKy,
            'registered_credits' => $tongTinChiDK,
            'open_classes' => $cacLopMo,
            'summary' => [
                'so_lop_dang_mo' => $soLopDangMo,
                'so_lop_da_dang_ky' => $soLopDaDangKy,
                'tong_tin_chi_da_dk' => $tongTinChiDK,
            ]
        ];

        // Gán các biến cục bộ để View hiển thị mượt mà
        $soTinChiDaDangKy = $tongTinChiDK;

        require __DIR__ . '/../Views/sinhvien/index.php';
    }
}
