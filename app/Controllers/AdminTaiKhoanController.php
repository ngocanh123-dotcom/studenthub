<?php

require_once __DIR__ . '/../models/AdminTaiKhoanModel.php';

class AdminTaiKhoanController
{
    private AdminTaiKhoanModel $model;

    public function __construct(?PDO $pdo = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->checkAdminAccess();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if (!$pdo instanceof PDO) {
            require __DIR__ . '/../../config/database.php';
        }
        $pdo = $pdo ?? null;

        if (!$pdo instanceof PDO) {
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu StudentHub.');
        }

        $this->model = new AdminTaiKhoanModel($pdo);
    }

    public function index(): void
    {
        $thongBao = '';
        $loaiThongBao = 'success';
        $loi = '';
        $tuKhoa = trim((string) ($_GET['q'] ?? ''));
        $locVaiTro = trim((string) ($_GET['vai_tro'] ?? ''));
        $duLieuSua = [
            'id' => '',
            'ten_dang_nhap' => '',
            'ho_ten' => '',
            'email' => '',
            'so_dien_thoai' => '',
            'vai_tro' => 'sinh_vien',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($thongBao, $loaiThongBao, $duLieuSua);
        }

        if (!empty($_SESSION['account_notice'])) {
            $thongBao = (string) $_SESSION['account_notice'];
            $loaiThongBao = (string) ($_SESSION['account_notice_type'] ?? 'success');
            unset($_SESSION['account_notice'], $_SESSION['account_notice_type']);
        }

        if (isset($_GET['sua'])) {
            $id = filter_input(INPUT_GET, 'sua', FILTER_VALIDATE_INT);
            if ($id && $id > 0) {
                try {
                    $account = $this->model->findById($id);
                    if ($account !== null) {
                        $duLieuSua = $account;
                    } else {
                        $thongBao = 'Không tìm thấy tài khoản cần sửa.';
                        $loaiThongBao = 'error';
                    }
                } catch (PDOException $e) {
                    error_log('StudentHub Account Edit: ' . $e->getMessage());
                    $thongBao = 'Không thể tải thông tin tài khoản.';
                    $loaiThongBao = 'error';
                }
            }
        }

        $danhSach = [];
        $tongTaiKhoan = 0;
        try {
            $danhSach = $this->model->getAccounts($tuKhoa, $locVaiTro);
            $tongTaiKhoan = $this->model->countAccounts();
        } catch (PDOException $e) {
            error_log('StudentHub Account List: ' . $e->getMessage());
            $loi = 'Không thể tải danh sách tài khoản. Hãy kiểm tra cấu trúc bảng nguoi_dung.';
        }

        require __DIR__ . '/../views/admin/tai_khoan_view.php';
    }

    private function handlePost(string &$thongBao, string &$loaiThongBao, array &$duLieuSua): void
    {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!is_string($csrf) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
            $thongBao = 'Phiên làm việc không hợp lệ. Hãy tải lại trang.';
            $loaiThongBao = 'error';
            return;
        }

        $action = (string) ($_POST['hanh_dong'] ?? '');
        try {
            if ($action === 'them' || $action === 'sua') {
                $this->saveAccount($action, $duLieuSua, $thongBao);
                $_SESSION['account_notice'] = $thongBao;
                $_SESSION['account_notice_type'] = 'success';
                header('Location: tai_khoan.php');
                exit;
            }

            if ($action === 'xoa') {
                $this->deleteAccount((int) ($_POST['id'] ?? 0));
                $_SESSION['account_notice'] = 'Xóa tài khoản thành công.';
                $_SESSION['account_notice_type'] = 'success';
                header('Location: tai_khoan.php');
                exit;
            }
        } catch (RuntimeException $e) {
            $thongBao = $e->getMessage();
            $loaiThongBao = 'error';
        } catch (PDOException $e) {
            error_log('StudentHub Account: ' . $e->getMessage());
            $thongBao = 'Không thể xử lý tài khoản. Hãy kiểm tra dữ liệu và các ràng buộc trong CSDL.';
            $loaiThongBao = 'error';
        }
    }

    private function saveAccount(string $action, array &$account, string &$notice): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $account = [
            'id' => $id,
            'ten_dang_nhap' => trim((string) ($_POST['ten_dang_nhap'] ?? '')),
            'ho_ten' => trim((string) ($_POST['ho_ten'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'so_dien_thoai' => trim((string) ($_POST['so_dien_thoai'] ?? '')),
            'vai_tro' => trim((string) ($_POST['vai_tro'] ?? '')),
        ];

        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $account['ten_dang_nhap'])) {
            throw new RuntimeException('Tên đăng nhập phải gồm 3–30 ký tự chữ, số hoặc dấu gạch dưới.');
        }
        if ($account['ho_ten'] === '') {
            throw new RuntimeException('Vui lòng nhập họ và tên.');
        }
        if ($account['email'] !== '' && !filter_var($account['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Địa chỉ email không hợp lệ.');
        }
        if (
            $account['so_dien_thoai'] !== ''
            && !preg_match('/^[0-9]{10}$/', $account['so_dien_thoai'])
        ) {
            throw new RuntimeException('Số điện thoại phải gồm đúng 10 chữ số.');
        }
        if (!in_array($account['vai_tro'], ['sinh_vien', 'giang_vien', 'admin'], true)) {
            throw new RuntimeException('Vui lòng chọn vai trò hợp lệ.');
        }
        if ($action === 'sua' && $id <= 0) {
            throw new RuntimeException('Tài khoản cần sửa không hợp lệ.');
        }

        $isCurrentAccount = $account['ten_dang_nhap'] === ($_SESSION['username'] ?? '')
            || (!empty($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $id);
        if ($action === 'sua' && $isCurrentAccount && $account['vai_tro'] !== 'admin') {
            throw new RuntimeException('Không thể đổi vai trò của tài khoản Admin đang đăng nhập.');
        }
        if ($this->model->usernameExists($account['ten_dang_nhap'], $action === 'sua' ? $id : 0)) {
            throw new RuntimeException('Tên đăng nhập này đã tồn tại.');
        }

        if ($action === 'them') {
            $this->model->create($account, password_hash('123456', PASSWORD_DEFAULT));
            $notice = 'Thêm tài khoản thành công. Mật khẩu mặc định là 123456.';
            return;
        }

        $this->model->update($id, $account);
        if ($isCurrentAccount) {
            $_SESSION['username'] = $account['ten_dang_nhap'];
            $_SESSION['ho_ten'] = $account['ho_ten'];
            $_SESSION['role'] = 'admin';
            $_SESSION['tenVaiTro'] = 'Quản trị viên';
        }
        $notice = 'Cập nhật tài khoản thành công.';
    }

    private function deleteAccount(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('Mã tài khoản không hợp lệ.');
        }

        $account = $this->model->findById($id);
        if ($account === null) {
            throw new RuntimeException('Không tìm thấy tài khoản cần xóa.');
        }
        if ($account['ten_dang_nhap'] === ($_SESSION['username'] ?? '')) {
            throw new RuntimeException('Bạn không thể xóa tài khoản đang đăng nhập.');
        }
        if ($account['vai_tro'] === 'admin' && $this->model->countAdmins() <= 1) {
            throw new RuntimeException('Không thể xóa Admin cuối cùng của hệ thống.');
        }

        $this->model->delete($id);
    }

    private function checkAdminAccess(): void
    {
        if (empty($_SESSION['username'])) {
            header('Location: /studenthub/public/index.php');
            exit;
        }

        $role = strtolower(trim((string) ($_SESSION['role'] ?? '')));
        if (!in_array($role, ['admin', 'quantri', 'quan_tri', 'quan_tri_vien'], true)) {
            header('Location: /studenthub/public/index.php');
            exit;
        }
    }
}
