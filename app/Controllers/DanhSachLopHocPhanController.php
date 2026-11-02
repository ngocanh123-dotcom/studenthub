<?php
/**
 * app/Controllers/DanhSachLopHocPhanController.php
 * Controller xử lý điều phối và chuẩn bị dữ liệu Danh sách lớp học phần cho Sinh viên
 * Kiến trúc: MVC - PHP 8.x
 */

require_once __DIR__ . '/../Models/DanhSachLopHocPhanModel.php';

class DanhSachLopHocPhanController {
    private DanhSachLopHocPhanModel $model;
    private PDO $conn;

    public function __construct(?PDO $db = null) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if ($db instanceof PDO) {
            $this->conn = $db;
        } else {
            $configPaths = [
                __DIR__ . '/../../config/database.php',
                __DIR__ . '/../../config/db.php',
                __DIR__ . '/../Config/database.php',
            ];

            foreach ($configPaths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    break;
                }
            }

            $this->conn = $conn ?? $pdo ?? $db ?? null;

            if (!$this->conn instanceof PDO) {
                $dbHost = 'localhost';
                $dbName = 'studenthub';
                $dbUser = 'root';
                $dbPass = getenv('STUDENTHUB_DB_PASS') ?: '';

                try {
                    $this->conn = new PDO(
                        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
                        $dbUser,
                        $dbPass,
                        [
                            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES   => false,
                        ]
                    );
                } catch (PDOException $e) {
                    die("Lỗi kết nối CSDL studenthub bằng PDO: " . $e->getMessage());
                }
            }
        }

        $this->model = new DanhSachLopHocPhanModel($this->conn);
    }

    /**
     * Hiển thị danh sách lớp học phần theo môn cho sinh viên
     */
    public function index(): void {
        // Thông tin sinh viên đang đăng nhập
        if (!isset($_SESSION['nguoi_dung_id']) && !isset($_SESSION['user_id'])) {
            $_SESSION['nguoi_dung_id'] = 4;
            $_SESSION['username']      = 'SV001';
            $_SESSION['ho_ten']        = 'Nguyễn Thu Trang';
            $_SESSION['role']          = 'sinhvien';
        }
        $sinh_vien_id_hien_tai = (int)($_SESSION['user_id'] ?? $_SESSION['nguoi_dung_id'] ?? 4);
        $hoTen                 = $_SESSION['ho_ten'] ?? 'Nguyễn Thu Trang';
        $maSinhVien            = $_SESSION['username'] ?? 'SV001';

        // Nhận tham số tìm kiếm & bộ lọc từ $_GET
        $tu_khoa       = trim($_GET['tu_khoa'] ?? '');
        $hoc_ky_id     = isset($_GET['hoc_ky_id']) ? trim((string)$_GET['hoc_ky_id']) : '';
        $giang_vien_id = isset($_GET['giang_vien_id']) ? trim((string)$_GET['giang_vien_id']) : '';

        $filters = [
            'tu_khoa'       => $tu_khoa,
            'hoc_ky_id'     => $hoc_ky_id,
            'giang_vien_id' => $giang_vien_id,
        ];

        // Truy vấn danh sách lớp học phần từ Model
        $danh_sach_goc = $this->model->getClassesWithFilters($sinh_vien_id_hien_tai, $filters);

        // Gom nhóm theo (học kỳ + học phần)
        $nhom_hoc_phan = [];
        $tong_so_lop   = 0;
        $so_lop_da_dk  = 0;

        foreach ($danh_sach_goc as $row) {
            $tong_so_lop++;

            if ((int)$row['sv_da_dang_ky'] > 0) {
                $so_lop_da_dk++;
            }

            $group_key = $row['hoc_ky_id'] . '_' . $row['hoc_phan_id'];
            if (!isset($nhom_hoc_phan[$group_key])) {
                $nhom_hoc_phan[$group_key] = [
                    'hoc_phan_id'   => (int)$row['hoc_phan_id'],
                    'hoc_ky_id'     => (int)$row['hoc_ky_id'],
                    'ma_hoc_phan'   => $row['ma_hoc_phan'],
                    'ten_hoc_phan'  => $row['ten_hoc_phan'],
                    'so_tin_chi'    => (int)$row['so_tin_chi'],
                    'ten_hoc_ky'    => $row['ten_hoc_ky'],
                    'ds_lop'        => []
                ];
            }

            $nhom_hoc_phan[$group_key]['ds_lop'][] = $row;
        }

        $tong_so_mon = count($nhom_hoc_phan);

        // Lấy danh sách học kỳ và giảng viên cho bộ lọc
        $ds_hoc_ky     = $this->model->getAllHocKy();
        $ds_giang_vien = $this->model->getAllGiangVien();

        // Lấy học kỳ đang mở cổng đăng ký từ Quản trị viên phục vụ hiển thị Sidebar
        $hocKyHienTai = $this->model->getActiveHocKy();

        $ten_hk_dang_chon = '';
        foreach ($ds_hoc_ky as $hk_item) {
            if ($hoc_ky_id !== '' && (int)$hoc_ky_id === (int)$hk_item['id']) {
                $ten_hk_dang_chon = $hk_item['ten_hoc_ky'];
                break;
            }
        }

        // Nạp tầng giao diện (View)
        require __DIR__ . '/../Views/sinhvien/danh_sach_lop_hoc_phan.php';
    }
}
