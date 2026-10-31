<?php
/**
 * app/Controllers/AdminLopHocPhanController.php
 * Controller xử lý Quản lý Lớp học phần cho Quản trị viên (Admin)
 * Kiến trúc: MVC - PHP 8.x - PDO Prepared Statements - Bảo mật CSRF & Phân quyền
 */

require_once __DIR__ . '/../Models/AdminLopHocPhanModel.php';

class AdminLopHocPhanController {
    private AdminLopHocPhanModel $model;
    private PDO $conn;

    public function __construct(?PDO $db = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Phân quyền và kiểm soát truy cập (Access Control)
        $this->checkAuth();

        // 2. Khởi tạo CSRF Token
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // 3. Kết nối CSDL
        if ($db instanceof PDO) {
            $this->conn = $db;
        } else {
            $configFile = __DIR__ . '/../../config/database.php';
            if (file_exists($configFile)) {
                require_once $configFile;
                $this->conn = $conn ?? $pdo;
            } else {
                die("Không tìm thấy tệp cấu hình CSDL.");
            }
        }

        $this->model = new AdminLopHocPhanModel($this->conn);
    }

    /**
     * Kiểm tra phân quyền truy cập: Chỉ cho phép Quản trị viên (Admin)
     */
    private function checkAuth(): void {
        if (empty($_SESSION['username'])) {
            header('Location: /studenthub/public/index.php');
            exit;
        }

        $vaiTro = strtolower(trim((string)($_SESSION['vai_tro'] ?? $_SESSION['role'] ?? '')));
        if (!in_array($vaiTro, ['admin', 'quantri', 'quan_tri', 'quan_tri_vien'], true)) {
            header('Location: /studenthub/public/index.php');
            exit;
        }
    }

    /**
     * Điều phối hành động từ query string 'action'
     */
    public function handleRequest(): void {
        $action = $_GET['action'] ?? ($_POST['action'] ?? 'index');

        switch ($action) {
            case 'create':
                $this->create();
                break;
            case 'store':
                $this->store();
                break;
            case 'edit':
                $this->edit();
                break;
            case 'update':
                $this->update();
                break;
            case 'toggle':
            case 'toggle_status':
                $this->toggleStatus();
                break;
            case 'delete':
                $this->delete();
                break;
            case 'export_csv':
                $this->exportCsv();
                break;
            case 'index':
            default:
                $this->index();
                break;
        }
    }

    /**
     * Hiển thị danh sách lớp học phần kèm bộ lọc và thống kê
     */
    public function index(): void {
        $filters = [
            'tu_khoa'       => trim($_GET['tu_khoa'] ?? ''),
            'hoc_ky_id'     => trim($_GET['hoc_ky_id'] ?? ''),
            'hoc_phan_id'   => trim($_GET['hoc_phan_id'] ?? ''),
            'giang_vien_id' => trim($_GET['giang_vien_id'] ?? ''),
            'trang_thai'    => trim($_GET['trang_thai'] ?? ''),
        ];

        $classes = $this->model->getAllClasses($filters);
        $statistics = $this->model->getStatistics();
        $hocKyList = $this->model->getAllHocKy();
        $hocPhanList = $this->model->getAllHocPhan();
        $giangVienList = $this->model->getAllGiangVien();

        $notice = $_SESSION['admin_notice'] ?? '';
        $noticeType = $_SESSION['admin_notice_type'] ?? 'success';
        unset($_SESSION['admin_notice'], $_SESSION['admin_notice_type']);

        $csrfToken = $_SESSION['csrf_token'];
        $tenAdmin = $_SESSION['ho_ten'] ?? ($_SESSION['tenVaiTro'] ?? $_SESSION['username']);

        require __DIR__ . '/../Views/admin/classes/index.php';
    }

    /**
     * Hiển thị form tạo lớp học phần mới
     */
    public function create(): void {
        $hocKyList = $this->model->getAllHocKy();
        $hocPhanList = $this->model->getAllHocPhan();
        $giangVienList = $this->model->getAllGiangVien();

        $error = $_SESSION['form_error'] ?? '';
        $oldData = $_SESSION['form_old_data'] ?? [];
        unset($_SESSION['form_error'], $_SESSION['form_old_data']);

        $csrfToken = $_SESSION['csrf_token'];
        $tenAdmin = $_SESSION['ho_ten'] ?? ($_SESSION['tenVaiTro'] ?? $_SESSION['username']);

        require __DIR__ . '/../Views/admin/classes/create.php';
    }

    /**
     * Xử lý lưu lớp học phần mới vào CSDL
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: lop_hoc_phan.php');
            exit;
        }

        // [TC 8] Kiểm tra mã CSRF token trên mọi form gửi phương thức POST
        if (!isset($_POST['csrf_token']) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$_POST['csrf_token'])) {
            die("Yêu cầu không hợp lệ do mã bảo mật CSRF không chính xác.");
        }

        $maLop = strtoupper(trim($_POST['ma_lop'] ?? ''));
        $hocPhanId = (int)($_POST['hoc_phan_id'] ?? 0);
        $hocKyId = (int)($_POST['hoc_ky_id'] ?? 0);
        $giangVienId = !empty($_POST['giang_vien_id']) ? (int)$_POST['giang_vien_id'] : null;
        $siSoToiDa = (int)($_POST['si_so_toi_da'] ?? 40);
        $trangThai = in_array($_POST['trang_thai'] ?? 'mo', ['mo', 'khoa'], true) ? $_POST['trang_thai'] : 'mo';
        $lichHoc = trim($_POST['lich_hoc'] ?? '');
        $phongHoc = trim($_POST['phong_hoc'] ?? '');
        $thoiGianMoRieng = !empty($_POST['thoi_gian_mo_rieng']) ? $_POST['thoi_gian_mo_rieng'] : null;
        $thoiGianDongRieng = !empty($_POST['thoi_gian_dong_rieng']) ? $_POST['thoi_gian_dong_rieng'] : null;

        // Validation nghiệp vụ
        if ($maLop === '') {
            $this->redirectBackWithError('Vui lòng nhập Mã lớp học phần (ví dụ: LHP06).', $_POST);
        }

        if (!preg_match('/^[A-Z0-9_-]{2,30}$/', $maLop)) {
            $this->redirectBackWithError('Mã lớp phải có 2-30 ký tự gồm chữ cái in hoa, số hoặc gạch nối.', $_POST);
        }

        if ($this->model->checkMaLopExists($maLop)) {
            $this->redirectBackWithError("Mã lớp '{$maLop}' đã tồn tại trên hệ thống. Vui lòng chọn mã khác.", $_POST);
        }

        if ($hocPhanId <= 0) {
            $this->redirectBackWithError('Vui lòng chọn Học phần (Môn học).', $_POST);
        }

        if ($hocKyId <= 0) {
            $this->redirectBackWithError('Vui lòng chọn Học kỳ áp dụng.', $_POST);
        }

        if ($lichHoc !== '' && $phongHoc !== '') {
            try {
                $conflict = $this->model->findScheduleRoomConflict($hocKyId, $lichHoc, $phongHoc);
            } catch (PDOException $e) {
                error_log("Lỗi kiểm tra trùng lịch/phòng khi tạo lớp học phần: " . $e->getMessage());
                $this->redirectBackWithError('Không thể kiểm tra trùng lịch và phòng học. Vui lòng thử lại.', $_POST);
            }

            if ($conflict) {
                $this->redirectBackWithError(
                    "Lịch học và phòng học này đã được xếp cho lớp '{$conflict['ma_lop']}' trong học kỳ đã chọn.",
                    $_POST
                );
            }
        }

        if ($siSoToiDa <= 0 || $siSoToiDa > 500) {
            $this->redirectBackWithError('Sĩ số tối đa phải là số nguyên dương từ 1 đến 500 sinh viên.', $_POST);
        }

        if ($thoiGianMoRieng && $thoiGianDongRieng) {
            if (strtotime($thoiGianDongRieng) <= strtotime($thoiGianMoRieng)) {
                $this->redirectBackWithError('Thời gian đóng riêng phải sau thời gian mở riêng.', $_POST);
            }
        }

        $data = [
            'ma_lop'               => $maLop,
            'hoc_phan_id'          => $hocPhanId,
            'hoc_ky_id'            => $hocKyId,
            'giang_vien_id'        => $giangVienId,
            'si_so_toi_da'         => $siSoToiDa,
            'trang_thai'           => $trangThai,
            'thoi_gian_mo_rieng'   => $thoiGianMoRieng,
            'thoi_gian_dong_rieng' => $thoiGianDongRieng,
            'lich_hoc'             => $lichHoc,
            'phong_hoc'            => $phongHoc
        ];

        $success = $this->model->createClass($data);
        if ($success) {
            $_SESSION['admin_notice'] = "Tạo mới lớp học phần '{$maLop}' thành công!";
            $_SESSION['admin_notice_type'] = 'success';
            header('Location: lop_hoc_phan.php');
            exit;
        } else {
            $this->redirectBackWithError('Đã xảy ra lỗi khi tạo lớp học phần trong CSDL. Vui lòng thử lại.', $_POST);
        }
    }

    /**
     * Hiển thị form chỉnh sửa lớp học phần
     */
    public function edit(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['admin_notice'] = 'ID lớp học phần không hợp lệ.';
            $_SESSION['admin_notice_type'] = 'error';
            header('Location: lop_hoc_phan.php');
            exit;
        }

        $class = $this->model->getClassById($id);
        if (!$class) {
            $_SESSION['admin_notice'] = 'Không tìm thấy lớp học phần cần chỉnh sửa.';
            $_SESSION['admin_notice_type'] = 'error';
            header('Location: lop_hoc_phan.php');
            exit;
        }

        $hocKyList = $this->model->getAllHocKy();
        $hocPhanList = $this->model->getAllHocPhan();
        $giangVienList = $this->model->getAllGiangVien();

        $error = $_SESSION['form_error'] ?? '';
        unset($_SESSION['form_error']);

        $csrfToken = $_SESSION['csrf_token'];
        $tenAdmin = $_SESSION['ho_ten'] ?? ($_SESSION['tenVaiTro'] ?? $_SESSION['username']);

        require __DIR__ . '/../Views/admin/classes/edit.php';
    }

    /**
     * Xử lý cập nhật thông tin lớp học phần
     */
    public function update(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: lop_hoc_phan.php');
            exit;
        }

        // [TC 8] Kiểm tra mã CSRF token trên mọi form gửi phương thức POST
        if (!isset($_POST['csrf_token']) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$_POST['csrf_token'])) {
            die("Yêu cầu không hợp lệ do mã bảo mật CSRF không chính xác.");
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: lop_hoc_phan.php');
            exit;
        }

        $currentClass = $this->model->getClassById($id);
        if (!$currentClass) {
            $_SESSION['admin_notice'] = 'Lớp học phần không tồn tại.';
            $_SESSION['admin_notice_type'] = 'error';
            header('Location: lop_hoc_phan.php');
            exit;
        }

        $maLop = strtoupper(trim($_POST['ma_lop'] ?? ''));
        $hocPhanId = (int)($_POST['hoc_phan_id'] ?? 0);
        $hocKyId = (int)($_POST['hoc_ky_id'] ?? 0);
        $giangVienId = !empty($_POST['giang_vien_id']) ? (int)$_POST['giang_vien_id'] : null;
        $siSoToiDa = (int)($_POST['si_so_toi_da'] ?? 40);
        $trangThai = in_array($_POST['trang_thai'] ?? 'mo', ['mo', 'khoa'], true) ? $_POST['trang_thai'] : 'mo';
        $lichHoc = trim($_POST['lich_hoc'] ?? '');
        $phongHoc = trim($_POST['phong_hoc'] ?? '');
        $thoiGianMoRieng = !empty($_POST['thoi_gian_mo_rieng']) ? $_POST['thoi_gian_mo_rieng'] : null;
        $thoiGianDongRieng = !empty($_POST['thoi_gian_dong_rieng']) ? $_POST['thoi_gian_dong_rieng'] : null;

        // Validation
        if ($maLop === '') {
            $this->redirectEditWithError($id, 'Vui lòng nhập Mã lớp học phần.');
        }

        if (!preg_match('/^[A-Z0-9_-]{2,30}$/', $maLop)) {
            $this->redirectEditWithError($id, 'Mã lớp phải có 2-30 ký tự gồm chữ cái in hoa, số hoặc gạch nối.');
        }

        if ($this->model->checkMaLopExists($maLop, $id)) {
            $this->redirectEditWithError($id, "Mã lớp '{$maLop}' đã được sử dụng bởi lớp khác.");
        }

        if ($hocPhanId <= 0) {
            $this->redirectEditWithError($id, 'Vui lòng chọn Học phần.');
        }

        if ($hocKyId <= 0) {
            $this->redirectEditWithError($id, 'Vui lòng chọn Học kỳ.');
        }

        if ($lichHoc !== '' && $phongHoc !== '') {
            try {
                $conflict = $this->model->findScheduleRoomConflict($hocKyId, $lichHoc, $phongHoc, $id);
            } catch (PDOException $e) {
                error_log("Lỗi kiểm tra trùng lịch/phòng khi cập nhật lớp học phần: " . $e->getMessage());
                $this->redirectEditWithError($id, 'Không thể kiểm tra trùng lịch và phòng học. Vui lòng thử lại.');
            }

            if ($conflict) {
                $this->redirectEditWithError(
                    $id,
                    "Lịch học và phòng học này đã được xếp cho lớp '{$conflict['ma_lop']}' trong học kỳ đã chọn."
                );
            }
        }

        // Ràng buộc an toàn sĩ số: Không được giảm sĩ số tối đa xuống thấp hơn số sinh viên hiện tại
        $siSoHienTai = (int)$currentClass['si_so_hien_tai'];
        if ($siSoToiDa < $siSoHienTai) {
            $this->redirectEditWithError(
                $id, 
                "Sĩ số tối đa ({$siSoToiDa}) không được nhỏ hơn số sinh viên hiện đang đăng ký ({$siSoHienTai} sinh viên)."
            );
        }

        if ($thoiGianMoRieng && $thoiGianDongRieng) {
            if (strtotime($thoiGianDongRieng) <= strtotime($thoiGianMoRieng)) {
                $this->redirectEditWithError($id, 'Thời gian đóng riêng phải sau thời gian mở riêng.');
            }
        }

        $data = [
            'ma_lop'               => $maLop,
            'hoc_phan_id'          => $hocPhanId,
            'hoc_ky_id'            => $hocKyId,
            'giang_vien_id'        => $giangVienId,
            'si_so_toi_da'         => $siSoToiDa,
            'trang_thai'           => $trangThai,
            'thoi_gian_mo_rieng'   => $thoiGianMoRieng,
            'thoi_gian_dong_rieng' => $thoiGianDongRieng,
            'lich_hoc'             => $lichHoc,
            'phong_hoc'            => $phongHoc
        ];

        $success = $this->model->updateClass($id, $data);
        if ($success) {
            $_SESSION['admin_notice'] = "Cập nhật lớp học phần '{$maLop}' thành công!";
            $_SESSION['admin_notice_type'] = 'success';
            header('Location: lop_hoc_phan.php');
            exit;
        } else {
            $this->redirectEditWithError($id, 'Không thể cập nhật lớp học phần trong CSDL.');
        }
    }

    /**
     * Cầu dao Mở / Khóa trạng thái lớp học phần (Bắt buộc cho Demo Kịch bản Mục H)
     */
    public function toggleStatus(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: lop_hoc_phan.php');
            exit;
        }

        // [TC 8] Kiểm tra mã CSRF token trên mọi form gửi phương thức POST
        if (!isset($_POST['csrf_token']) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$_POST['csrf_token'])) {
            die("Yêu cầu không hợp lệ do mã bảo mật CSRF không chính xác.");
        }

        $id = (int)($_POST['id'] ?? 0);
        $class = $this->model->getClassById($id);

        if (!$class) {
            $_SESSION['admin_notice'] = 'Lớp học phần không tồn tại.';
            $_SESSION['admin_notice_type'] = 'error';
            header('Location: lop_hoc_phan.php');
            exit;
        }

        $newStatus = $this->model->toggleStatus($id);

        if ($newStatus === 'khoa') {
            $_SESSION['admin_notice'] = "Đã KHÓA lớp học phần '{$class['ma_lop']}'. Sinh viên ở cổng đăng ký sẽ lập tức bị chặn theo đúng kịch bản Mục H!";
            $_SESSION['admin_notice_type'] = 'success';
        } elseif ($newStatus === 'mo') {
            $_SESSION['admin_notice'] = "Đã MỞ lại đăng ký cho lớp học phần '{$class['ma_lop']}' thành công!";
            $_SESSION['admin_notice_type'] = 'success';
        } else {
            $_SESSION['admin_notice'] = "Không thể chuyển trạng thái cho lớp học phần '{$class['ma_lop']}'.";
            $_SESSION['admin_notice_type'] = 'error';
        }

        header('Location: lop_hoc_phan.php');
        exit;
    }

    /**
     * Xóa lớp học phần (Ràng buộc an toàn: Chặn nếu đã có sinh viên đăng ký)
     */
    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: lop_hoc_phan.php');
            exit;
        }

        // [TC 8] Kiểm tra mã CSRF token trên mọi form gửi phương thức POST
        if (!isset($_POST['csrf_token']) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$_POST['csrf_token'])) {
            die("Yêu cầu không hợp lệ do mã bảo mật CSRF không chính xác.");
        }

        $id = (int)($_POST['id'] ?? 0);
        $class = $this->model->getClassById($id);

        if (!$class) {
            $_SESSION['admin_notice'] = 'Lớp học phần không tồn tại.';
            $_SESSION['admin_notice_type'] = 'error';
            header('Location: lop_hoc_phan.php');
            exit;
        }

        // [BẢO VỆ 2 LỚP - CHỐNG RACE CONDITION]
        // LỚP 1: Kiểm tra trạng thái cầu dao. Lớp phải được KHÓA ('khoa') trước khi xóa
        if (($class['trang_thai'] ?? '') === 'mo') {
            $_SESSION['admin_notice'] = "Vui lòng ngắt cầu dao (Khóa lớp) trước khi thực hiện thao tác xóa để đảm bảo an toàn hệ thống.";
            $_SESSION['admin_notice_type'] = 'error';
            header('Location: lop_hoc_phan.php');
            exit;
        }

        // LỚP 2: Kiểm tra sĩ số đăng ký hiện tại (sĩ số phải == 0). Tuyệt đối chặn xóa nếu đã có sinh viên
        $check = $this->model->checkCanDelete($id);
        $siSoHienTai = max((int)($class['si_so_hien_tai'] ?? 0), (int)($check['enrollment_count'] ?? 0));
        if ($siSoHienTai > 0 || !$check['can_delete']) {
            $_SESSION['admin_notice'] = "KHÔNG THỂ XÓA lớp '{$class['ma_lop']}' vì hiện đã có {$siSoHienTai} sinh viên đăng ký trên hệ thống. Để ngừng tiếp nhận, vui lòng dùng nút 'Khóa' đăng ký.";
            $_SESSION['admin_notice_type'] = 'error';
            header('Location: lop_hoc_phan.php');
            exit;
        }

        $deleted = $this->model->deleteClass($id);
        if ($deleted) {
            $_SESSION['admin_notice'] = "Đã xóa vĩnh viễn lớp học phần '{$class['ma_lop']}' khỏi hệ thống.";
            $_SESSION['admin_notice_type'] = 'success';
        } else {
            $_SESSION['admin_notice'] = "Không thể xóa lớp học phần do vi phạm điều kiện an toàn (Lớp phải ở trạng thái Khóa và chưa có sinh viên đăng ký).";
            $_SESSION['admin_notice_type'] = 'error';
        }

        header('Location: lop_hoc_phan.php');
        exit;
    }

    /**
     * Tính năng mở rộng: Xuất danh sách lớp học phần dạng CSV (UTF-8 BOM)
     */
    public function exportCsv(): void {
        $filters = [
            'tu_khoa'       => trim($_GET['tu_khoa'] ?? ''),
            'hoc_ky_id'     => trim($_GET['hoc_ky_id'] ?? ''),
            'hoc_phan_id'   => trim($_GET['hoc_phan_id'] ?? ''),
            'giang_vien_id' => trim($_GET['giang_vien_id'] ?? ''),
            'trang_thai'    => trim($_GET['trang_thai'] ?? ''),
        ];

        $classes = $this->model->getAllClasses($filters);

        $filename = 'danh_sach_lop_hoc_phan_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Ghi UTF-8 BOM để Microsoft Excel đọc font tiếng Việt có dấu chuẩn 100%
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Tiêu đề cột
        fputcsv($output, [
            'STT',
            'Mã lớp',
            'Mã học phần',
            'Tên học phần',
            'Số tín chỉ',
            'Học kỳ',
            'Giảng viên phụ trách',
            'Lịch học',
            'Phòng học',
            'Sĩ số tối đa',
            'Sĩ số hiện tại',
            'Chỗ còn lại',
            'Trạng thái đăng ký',
            'Thời gian mở riêng',
            'Thời gian đóng riêng',
            'Ngày tạo'
        ]);

        $stt = 1;
        foreach ($classes as $row) {
            $siSoToiDa = (int)$row['si_so_toi_da'];
            $siSoHienTai = (int)$row['si_so_hien_tai'];
            $conLai = max(0, $siSoToiDa - $siSoHienTai);
            $trangThaiText = ($row['trang_thai'] === 'mo') ? 'Đang mở' : 'Đã khóa';

            fputcsv($output, [
                $stt++,
                $row['ma_lop'],
                $row['ma_hoc_phan'],
                $row['ten_hoc_phan'],
                $row['so_tin_chi'],
                $row['ten_hoc_ky'],
                $row['ten_giang_vien'] ?? 'Chưa phân công',
                $row['lich_hoc'] ?? 'Chưa xếp',
                $row['phong_hoc'] ?? 'Chưa xếp',
                $siSoToiDa,
                $siSoHienTai,
                $conLai,
                $trangThaiText,
                $row['thoi_gian_mo_rieng'] ?? '',
                $row['thoi_gian_dong_rieng'] ?? '',
                $row['ngay_tao']
            ]);
        }

        fclose($output);
        exit;
    }

    private function redirectBackWithError(string $message, array $oldData): void {
        $_SESSION['form_error'] = $message;
        $_SESSION['form_old_data'] = $oldData;
        header('Location: lop_hoc_phan.php?action=create');
        exit;
    }

    private function redirectEditWithError(int $id, string $message): void {
        $_SESSION['form_error'] = $message;
        header('Location: lop_hoc_phan.php?action=edit&id=' . $id);
        exit;
    }
}
