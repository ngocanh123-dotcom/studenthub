<?php
// app/Controllers/AdminHocKyController.php
require_once __DIR__ . '/../Models/HocKyModel.php';

class AdminHocKyController {
    private $model;

    public function __construct($conn) {
        $this->model = new HocKyModel($conn);
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Kiểm tra quyền
        if (empty($_SESSION['username']) || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin', 'quantri', 'quan_tri', 'quan_tri_vien'])) {
            header('Location: /studenthub/public/index.php');
            exit;
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public function index() {
        $thongBao = ''; $loaiThongBao = 'success';
        $tuKhoa = trim($_GET['q'] ?? '');
        $duLieuSua = ['id' => '', 'ten_hoc_ky' => '', 'ngay_bat_dau' => '', 'ngay_ket_thuc' => '', 'thoi_gian_mo_dk' => '', 'thoi_gian_dong_dk' => '', 'trang_thai_dang_ky' => 1];

        // Lấy dữ liệu Sửa
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['sua'])) {
            $idSua = (int) $_GET['sua'];
            if ($idSua > 0) {
                $hocKySua = $this->model->getById($idSua);
                if ($hocKySua) $duLieuSua = $hocKySua;
            }
        }

        // Xử lý POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfNhan = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $csrfNhan)) {
                $thongBao = 'Phiên làm việc không hợp lệ.'; $loaiThongBao = 'error';
            } else {
                $hanhDong = $_POST['hanh_dong'] ?? '';
                try {
                    if ($hanhDong === 'toggle_status') {
                        $idToggle = (int) ($_POST['id'] ?? 0);
                        if ($idToggle > 0) {
                            $this->model->toggleStatus($idToggle);
                            $_SESSION['semester_notice'] = 'Đã thay đổi trạng thái đăng ký.';
                        }
                        header('Location: admin_hoc_ky.php'); exit;
                    }

                    if ($hanhDong === 'them' || $hanhDong === 'sua') {
                        $id = (int) ($_POST['id'] ?? 0);
                        $tenHocKy = trim($_POST['ten_hoc_ky'] ?? '');
                        $ngayBatDau = trim($_POST['ngay_bat_dau'] ?? '');
                        $ngayKetThuc = trim($_POST['ngay_ket_thuc'] ?? '');
                        $thoiGianMo = trim($_POST['thoi_gian_mo_dk'] ?? '');
                        $thoiGianDong = trim($_POST['thoi_gian_dong_dk'] ?? '');
                        $trangThai = (int) ($_POST['trang_thai_dang_ky'] ?? 1);

                        $duLieuSua = [
                            'id' => $id, 'ten_hoc_ky' => $tenHocKy, 'ngay_bat_dau' => $ngayBatDau,
                            'ngay_ket_thuc' => $ngayKetThuc, 'thoi_gian_mo_dk' => $thoiGianMo,
                            'thoi_gian_dong_dk' => $thoiGianDong, 'trang_thai_dang_ky' => $trangThai
                        ];

                        if ($tenHocKy === '') throw new RuntimeException('Vui lòng nhập tên học kỳ.');
                        if ($ngayBatDau === '' || $ngayKetThuc === '') throw new RuntimeException('Vui lòng nhập ngày bắt đầu và kết thúc.');
                        if (strtotime($ngayBatDau) > strtotime($ngayKetThuc)) throw new RuntimeException('Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày kết thúc.');
                        if ($thoiGianMo !== '' && $thoiGianDong !== '' && strtotime($thoiGianMo) > strtotime($thoiGianDong)) throw new RuntimeException('Thời gian mở đăng ký phải trước thời gian đóng.');

                        $excludeId = ($hanhDong === 'sua') ? $id : 0;
                        if ($this->model->checkOverlap($ngayBatDau, $ngayKetThuc, $excludeId)) {
                            throw new RuntimeException('Khoảng thời gian này bị trùng lặp với một học kỳ khác!');
                        }

                        if ($hanhDong === 'them') {
                            $this->model->insert($tenHocKy, $ngayBatDau, $ngayKetThuc, $thoiGianMo, $thoiGianDong, $trangThai);
                            $_SESSION['semester_notice'] = 'Thêm học kỳ thành công.';
                        } else {
                            if ($id <= 0) throw new RuntimeException('Học kỳ cần sửa không hợp lệ.');
                            $this->model->update($id, $tenHocKy, $ngayBatDau, $ngayKetThuc, $thoiGianMo, $thoiGianDong, $trangThai);
                            $_SESSION['semester_notice'] = 'Cập nhật học kỳ thành công.';
                        }
                        $_SESSION['semester_notice_type'] = 'success'; header('Location: admin_hoc_ky.php'); exit;
                    }

                    if ($hanhDong === 'xoa') {
                        $idXoa = (int) ($_POST['id'] ?? 0);
                        if ($this->model->countClasses($idXoa) > 0) {
                            throw new RuntimeException('Không thể xóa! Đã có lớp học phần thuộc học kỳ này.');
                        }
                        $this->model->delete($idXoa);
                        $_SESSION['semester_notice'] = 'Xóa học kỳ thành công.'; $_SESSION['semester_notice_type'] = 'success'; header('Location: admin_hoc_ky.php'); exit;
                    }
                } catch (Exception $e) { $thongBao = $e->getMessage(); $loaiThongBao = 'error'; }
            }
        }

        if (!empty($_SESSION['semester_notice'])) {
            $thongBao = $_SESSION['semester_notice']; $loaiThongBao = $_SESSION['semester_notice_type'] ?? 'success';
            unset($_SESSION['semester_notice'], $_SESSION['semester_notice_type']);
        }

        $danhSach = $this->model->getAll($tuKhoa);
        $tongHocKy = count($danhSach);

        // Lấy danh sách lớp cho từng học kỳ
        $dsLopTheoHocKy = [];
        foreach ($danhSach as $hk) {
            $dsLopTheoHocKy[$hk['id']] = $this->model->getClassesBySemester($hk['id']);
        }

        // Gọi View
        require_once __DIR__ . '/../Views/admin/hoc_ky.php';
    }
}
?>