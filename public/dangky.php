
<?php
session_start();

require_once __DIR__ . '/../app/Controllers/DangKyHocController.php';

// Tạo mã bảo vệ biểu mẫu
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Đường dẫn riêng cho chức năng đăng ký học phần
$trangDangKy = 'dangky.php';

$action = $_GET['action'] ?? 'danhsach';

try {
    $controller = new DangKyHocController();

    // Hiển thị danh sách lớp học phần
    if ($action === 'danhsach') {
        // Chuyển phiên đăng nhập của nhóm sang cấu trúc module
        if (
            isset($_SESSION['user_id'], $_SESSION['role'])
            && !isset($_SESSION['nguoi_dung'])
        ) {
            $vaiTroMap = [
                'sinhvien' => 'sinh_vien',
                'giangvien' => 'giang_vien',
                'quantri' => 'admin'
            ];

            $_SESSION['nguoi_dung'] = [
                'id' => (int) $_SESSION['user_id'],
                'ten_dang_nhap' => $_SESSION['username'] ?? '',
                'ho_ten' => $_SESSION['ho_ten'] ?? '',
                'vai_tro' => $vaiTroMap[$_SESSION['role']]
                    ?? $_SESSION['role']
            ];
        }

        $controller->index();
        exit;
    }

    // Xử lý đăng ký học phần
    if (
        $action === 'dangky'
        && $_SERVER['REQUEST_METHOD'] === 'POST'
    ) {
        $csrf = $_POST['csrf_token'] ?? '';

        if (
            !is_string($csrf)
            || !hash_equals($_SESSION['csrf_token'], $csrf)
        ) {
            $_SESSION['thong_bao'] =
                'Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.';

            header('Location: ' . $trangDangKy);
            exit;
        }

        // Chỉ sinh viên đã đăng nhập mới được đăng ký
        $nguoiDung = $_SESSION['nguoi_dung'] ?? null;

        if (
            !is_array($nguoiDung)
            || !isset($nguoiDung['id'])
            || ($nguoiDung['vai_tro'] ?? '') !== 'sinh_vien'
        ) {
            $_SESSION['thong_bao'] =
                'Vui lòng đăng nhập bằng tài khoản sinh viên trước khi đăng ký.';

            header('Location: ' . $trangDangKy);
            exit;
        }

        $lopId = filter_input(
            INPUT_POST,
            'lop_id',
            FILTER_VALIDATE_INT
        );

        if ($lopId === false || $lopId === null || $lopId < 1) {
            $_SESSION['thong_bao'] = 'Lớp học phần không hợp lệ.';

            header('Location: ' . $trangDangKy);
            exit;
        }

        $ketQua = $controller->dangKy(
            (int) $nguoiDung['id'],
            (int) $lopId
        );

        $_SESSION['thong_bao'] = $ketQua['message']
            ?? 'Không thể xử lý đăng ký.';

        header('Location: ' . $trangDangKy);
        exit;
    }

    http_response_code(404);
    echo 'Không tìm thấy chức năng.';

} catch (Throwable $e) {
    error_log($e->getMessage());

    http_response_code(500);
    echo 'Đã xảy ra lỗi khi tải trang. Vui lòng kiểm tra kết nối cơ sở dữ liệu.';
}
