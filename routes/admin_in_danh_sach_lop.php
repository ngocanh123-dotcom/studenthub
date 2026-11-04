<?php
// Print the classes for one semester.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền Admin
if (empty($_SESSION['username']) || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin', 'quantri', 'quan_tri', 'quan_tri_vien'])) {
    header('Location: /studenthub/public/index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/HocKyModel.php';
if (!$pdo instanceof PDO) {
    throw new RuntimeException('Không thể kết nối cơ sở dữ liệu để in danh sách lớp.');
}

$hocKyId = (int)($_GET['hoc_ky_id'] ?? 0);
if ($hocKyId <= 0) die("Học kỳ không hợp lệ.");

$model = new HocKyModel($pdo);
$hocKy = $model->getById($hocKyId);
$dsLop = $model->getClassesBySemester($hocKyId);

if (!$hocKy) die("Không tìm thấy học kỳ.");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In danh sách lớp - <?= htmlspecialchars($hocKy['ten_hoc_ky']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; }
        h2 { text-align: center; text-transform: uppercase; margin-bottom: 30px;}
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 10px; text-align: left; }
        th { background: #f2f2f2; }
        .no-print { text-align: center; margin-bottom: 20px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">🖨️ Bấm để In / Lưu thành PDF</button>
    </div>

    <h2>Danh sách lớp học phần - <?= htmlspecialchars($hocKy['ten_hoc_ky']) ?></h2>

    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Mã Lớp</th>
                <th>Tên Môn Học</th>
                <th>Số TC</th>
                <th>Giảng Viên</th>
                <th>Sĩ Số Tối Đa</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($dsLop)): ?>
                <tr><td colspan="6" style="text-align: center;">Chưa có lớp học phần nào.</td></tr>
            <?php else: $stt=1; foreach ($dsLop as $lop): ?>
                <tr>
                    <td><?= $stt++ ?></td>
                    <td><strong><?= htmlspecialchars($lop['ma_lop']) ?></strong></td>
                    <td><?= htmlspecialchars($lop['ten_hoc_phan']) ?></td>
                    <td><?= htmlspecialchars($lop['so_tin_chi']) ?></td>
                    <td><?= htmlspecialchars($lop['gv']) ?></td>
                    <td><?= htmlspecialchars($lop['si_so_toi_da']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</body>
</html>