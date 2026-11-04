<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

$hocKyId = (int)($_GET['hoc_ky_id'] ?? 0);
if ($hocKyId <= 0) die("Học kỳ không hợp lệ.");

// Lấy tên học kỳ
$stmt = $conn->prepare("SELECT ten_hoc_ky FROM hoc_ky WHERE id = :id");
$stmt->execute([':id' => $hocKyId]);
$tenHocKy = $stmt->fetchColumn();

// Lấy danh sách lớp
$stmtLop = $conn->prepare("
    SELECT lhp.ma_lop, hp.ten_hoc_phan, hp.so_tin_chi, nd.ho_ten AS gv, lhp.si_so_toi_da 
    FROM lop_hoc_phan lhp 
    JOIN hoc_phan hp ON lhp.hoc_phan_id = hp.id 
    JOIN nguoi_dung nd ON lhp.giang_vien_id = nd.id 
    WHERE lhp.hoc_ky_id = :id
");
$stmtLop->execute([':id' => $hocKyId]);
$dsLop = $stmtLop->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In danh sách lớp - <?= htmlspecialchars($tenHocKy) ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { text-align: center; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 10px; text-align: left; }
        th { background: #f2f2f2; }
        .no-print { text-align: center; margin-bottom: 20px; }
        @media print {
            .no-print { display: none; } /* Ẩn nút in khi in thật */
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">🖨️ In / Lưu thành PDF</button>
    </div>

    <h2>Danh sách lớp học phần - <?= htmlspecialchars($tenHocKy) ?></h2>
    
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
                    <td><?= $lop['so_tin_chi'] ?></td>
                    <td><?= htmlspecialchars($lop['gv']) ?></td>
                    <td><?= $lop['si_so_toi_da'] ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</body>
</html>