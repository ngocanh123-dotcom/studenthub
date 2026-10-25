
<?php
session_start();

require_once __DIR__ . '/../controllers/DangKyHocController.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$action = $_GET['action'] ?? 'danhsach';

try {
    $controller = new DangKyHocController();

    // Hiển thị danh sách lớp học phần
    if ($action === 'danhsach') {
        $controller->index();
        exit;
    }

    // Xử lý đăng ký học phần
    if ($action === 'dangky' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf = $_POST['csrf_token'] ?? '';

        // Kiểm tra biểu mẫu hợp lệ
        if (
            !is_string($csrf) ||
            !hash_equals($_SESSION['csrf_token'], $csrf)
        ) {
            $_SESSION['thong_bao'] =
                'Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.';

            header('Location: index.php');
            exit;
        }

        // Chỉ tài khoản sinh viên đã đăng nhập mới được đăng ký
        $nguoiDung = $_SESSION['nguoi_dung'] ?? null;

        if (
            !is_array($nguoiDung) ||
            !isset($nguoiDung['id']) ||
            ($nguoiDung['vai_tro'] ?? '') !== 'sinh_vien'
        ) {
            $_SESSION['thong_bao'] =
                'Vui lòng đăng nhập bằng tài khoản sinh viên trước khi đăng ký.';

            header('Location: index.php');
            exit;
        }

        $lopId = filter_input(
            INPUT_POST,
            'lop_id',
            FILTER_VALIDATE_INT
        );

        if ($lopId === false || $lopId === null || $lopId < 1) {
            $_SESSION['thong_bao'] =
                'Lớp học phần không hợp lệ.';

            header('Location: index.php');
            exit;
        }

        // Gọi model kiểm tra điều kiện và lưu đăng ký vào database
        $ketQua = $controller->dangKy(
            (int) $nguoiDung['id'],
            (int) $lopId
        );

        $_SESSION['thong_bao'] = $ketQua['message']
            ?? 'Không thể xử lý đăng ký.';

        header('Location: index.php');
        exit;
    }

    http_response_code(404);
    echo 'Không tìm thấy chức năng.';

} catch (Throwable $e) {
    error_log($e->getMessage());

    http_response_code(500);
    echo 'Đã xảy ra lỗi khi tải trang. Vui lòng kiểm tra kết nối cơ sở dữ liệu.';
}
