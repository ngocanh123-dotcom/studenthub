<?php
// app/Views/lichsu/index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra quyền sinh viên bảo mật (Giống các bạn)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'sinhvien') {
    header('Location: /studenthub/public/index.php');
    exit;
}

// 2. Khai báo các biến cho Header
$breadcrumb = 'Trang chủ / Lịch sử đăng ký';
$pageTitle = 'Lịch sử đăng ký học phần';
$pageSubtitle = 'Theo dõi các lớp đã đăng ký và thực hiện hủy nếu còn trong thời gian quy định.';
$activeSidebar = 'lich_su';

// 3. Nhúng Header (Bao gồm cả Sidebar)
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- NỘI DUNG CHÍNH CỦA BẠN (BẮT ĐẦU) -->
<section class="panel-card-custom" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04); overflow: hidden; margin-top: 20px;">
    <div class="table-wrap-custom" style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <tr>
                    <th style="padding: 13px 16px; font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap;">MÃ LỚP</th>
                    <th style="padding: 13px 16px; font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap;">TÊN HỌC PHẦN</th>
                    <th style="padding: 13px 16px; font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap;">SỐ TC</th>
                    <th style="padding: 13px 16px; font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap;">GIẢNG VIÊN</th>
                    <th style="padding: 13px 16px; font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap;">NGÀY ĐĂNG KÝ</th>
                    <th style="padding: 13px 16px; font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap;">TRẠNG THÁI</th>
                    <th style="padding: 13px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align:right;">THAO TÁC</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachLichSu)): ?>
                    <tr><td colspan="7" style="text-align:center; padding: 30px; color: #64748b;">Bạn chưa đăng ký lớp học phần nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($danhSachLichSu as $row): 
                        $isDaHuy = ($row['trang_thai'] === 'da_huy');
                        
                        // Logic kiểm tra quá hạn (ẩn nút Hủy nếu quá hạn)
                        $deadline = $row['thoi_gian_dong_rieng'] ?? $row['thoi_gian_dong_dk'];
                        $isExpired = $deadline && strtotime(date('Y-m-d H:i:s')) > strtotime($deadline);
                    ?>
                        <tr id="row-<?= $row['id'] ?>" style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 14px 16px;">
                                <span style="font-weight:700; color:#2563eb; background:#eff6ff; padding:4px 10px; border-radius:7px; font-size:12.5px;">
                                    <?= htmlspecialchars($row['ma_lop']) ?>
                                </span>
                            </td>
                            <td style="padding: 14px 16px; font-weight:600; color:#0f172a;">
                                <?= htmlspecialchars($row['ten_hoc_phan']) ?>
                            </td>
                            <td style="padding: 14px 16px; font-size:13.5px;"><?= (int)$row['so_tin_chi'] ?></td>
                            <td style="padding: 14px 16px; font-size:13.5px;"><?= htmlspecialchars($row['ten_giang_vien']) ?></td>
                            <td style="padding: 14px 16px; font-size:13.5px;"><?= date('d/m/Y H:i', strtotime($row['ngay_dang_ky'])) ?></td>
                            <td style="padding: 14px 16px;">
                                <?php if ($isDaHuy): ?>
                                    <span style="background: #fef2f2; color: #dc2626; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex;">
                                        Đã hủy
                                    </span>
                                <?php else: ?>
                                    <span style="background: #ecfdf5; color: #059669; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex;">
                                        Đã đăng ký
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 14px 16px; text-align:right;">
                                <?php if ($isDaHuy): ?>
                                    <button style="height: 32px; padding: 0 11px; font-size: 12px; border-radius: 8px; font-weight: 600; background: #f1f5f9; color: #94a3b8; border: none; cursor: not-allowed;" disabled>Đã hủy</button>
                                <?php elseif ($isExpired): ?>
                                    <button style="height: 32px; padding: 0 11px; font-size: 12px; border-radius: 8px; font-weight: 600; background: #f1f5f9; color: #94a3b8; border: none; cursor: not-allowed;" title="Đã quá thời hạn hủy" disabled>Quá hạn hủy</button>
                                <?php else: ?>
                                    <button class="btn-huy" data-id="<?= $row['id'] ?>" style="height: 32px; padding: 0 11px; font-size: 12px; border-radius: 8px; font-weight: 600; background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; cursor: pointer;">
                                        ✖ Hủy đăng ký
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- SCRIPT FETCH API -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    const buttons = document.querySelectorAll('.btn-huy');

    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Bạn có chắc chắn muốn hủy đăng ký lớp học phần này không?')) return;

            const dangKyId = this.dataset.id;
            const originalText = this.innerHTML;
            
            this.disabled = true;
            this.innerHTML = 'Đang xử lý...';

            // ĐƯỜNG DẪN FETCH API SỬA LẠI CHO ĐÚNG THƯ MỤC PUBLIC
            fetch('/studenthub/public/api_huy_dang_ky.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    dang_ky_id: dangKyId,
                    csrf_token: csrfToken
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const row = document.getElementById(`row-${dangKyId}`);
                    const statusBadge = row.querySelector('.badge');
                    // Cập nhật lại màu badge
                    if(statusBadge) {
                         statusBadge.style.background = '#fef2f2';
                         statusBadge.style.color = '#dc2626';
                         statusBadge.innerText = 'Đã hủy';
                    }
                    
                    this.style.background = '#f1f5f9';
                    this.style.color = '#94a3b8';
                    this.style.border = 'none';
                    this.style.cursor = 'not-allowed';
                    this.innerHTML = 'Đã hủy';
                } else {
                    alert('Lỗi: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = originalText;
                }
            })
            .catch(err => {
                alert('Có lỗi xảy ra khi kết nối máy chủ. Vui lòng kiểm tra lại.');
                this.disabled = false;
                this.innerHTML = originalText;
            });
        });
    });
});
</script>
<!-- NỘI DUNG CHÍNH CỦA BẠN (KẾT THÚC) -->

<?php 
// 4. Nhúng Footer
require_once __DIR__ . '/../layouts/footer.php'; 
?>