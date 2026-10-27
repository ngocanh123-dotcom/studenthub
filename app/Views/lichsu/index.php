<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đăng ký - StudentHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Kế thừa CSS từ file danh_sach_lop_hoc_phan.php của bạn Huyền để đồng bộ */
        :root { --navy: #0f2342; --blue: #2563eb; --bg: #f4f7fb; --card: #fff; --text: #0f172a; --muted: #64748b; --border: #e2e8f0; --danger: #dc2626; --danger-bg: #fef2f2; --success: #059669; --success-bg: #ecfdf5; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: "Segoe UI", sans-serif; color: var(--text); background: var(--bg); min-height: 100vh; display: flex; flex-direction: column; }
        
        .topbar { height: 68px; background: var(--card); border-bottom: 1px solid var(--border); padding: 0 28px; display: flex; align-items: center; justify-content: space-between; }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text); }
        .brand-logo { width: 40px; height: 40px; border-radius: 12px; background: var(--blue); color: #fff; font-weight: 800; display: grid; place-items: center; }
        .app-body { display: flex; flex: 1; }
        
        .sidebar { width: 245px; background: var(--navy); color: #cbd5e1; padding: 22px 14px; flex-shrink: 0; }
        .nav-list { list-style: none; display: flex; flex-direction: column; gap: 6px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 10px; color: #cbd5e1; text-decoration: none; font-size: 14px; }
        .nav-link:hover, .nav-link.active { background: var(--blue); color: #fff; }
        
        .main-content { flex: 1; padding: 26px 32px; }
        .page-header { margin-bottom: 22px; }
        .page-header h2 { font-size: 24px; font-weight: 800; }
        
        .panel-card { background: var(--card); border: 1px solid var(--border); border-radius: 18px; overflow: hidden; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04); }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 13px 16px; background: #f8fafc; font-size: 12px; font-weight: 700; color: #475569; border-bottom: 1px solid var(--border); white-space: nowrap; }
        td { padding: 14px 16px; font-size: 13.5px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .badge { display: inline-flex; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge-success { background: var(--success-bg); color: var(--success); }
        .badge-danger { background: var(--danger-bg); color: var(--danger); }
        
        .btn { height: 32px; padding: 0 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; transition: 0.15s; }
        .btn-danger { background: var(--danger-bg); color: var(--danger); border-color: #fca5a5; }
        .btn-danger:hover { background: var(--danger); color: #fff; }
        .btn-disabled { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; border-color: #e2e8f0; }
    </style>
</head>
<body>

    <header class="topbar">
        <a href="#" class="brand">
            <div class="brand-logo">S</div>
            <div class="brand-text">
                <h1 style="font-size: 19px; font-weight: 800;">StudentHub</h1>
                <span style="font-size: 10.5px; color: var(--muted); font-weight: 700;">CỔNG THÔNG TIN SINH VIÊN</span>
            </div>
        </a>
        <div class="user-profile">
            <strong>Sinh viên: <?= htmlspecialchars($_SESSION['ho_ten'] ?? 'Nguyễn Thu Trang') ?></strong>
        </div>
    </header>

    <div class="app-body">
        <aside class="sidebar">
            <div class="sidebar-label" style="font-size:11px; font-weight:700; color:#64748b; padding:0 12px; margin-bottom:10px;">MENU SINH VIÊN</div>
            <ul class="nav-list">
                <li><a href="danh_sach_lop_hoc_phan.php" class="nav-link"><i class="fa-solid fa-table-list"></i> Danh sách lớp học phần</a></li>
                <li><a href="lich_su_dang_ky.php" class="nav-link active"><i class="fa-solid fa-clock-rotate-left"></i> Lịch sử đăng ký</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h2>Lịch sử đăng ký học phần</h2>
                <p style="color: var(--muted); font-size: 13.5px; margin-top: 4px;">Theo dõi các lớp đã đăng ký và thực hiện hủy nếu còn trong thời gian quy định.</p>
            </div>

            <section class="panel-card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã lớp</th>
                                <th>Tên học phần</th>
                                <th>Số TC</th>
                                <th>Giảng viên</th>
                                <th>Ngày đăng ký</th>
                                <th>Trạng thái</th>
                                <th style="text-align:right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($danhSachLichSu)): ?>
                                <tr><td colspan="7" style="text-align:center; padding: 30px; color: var(--muted);">Bạn chưa đăng ký lớp học phần nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($danhSachLichSu as $row): 
                                    $isDaHuy = ($row['trang_thai'] === 'da_huy');
                                    
                                    // Logic kiểm tra quá hạn (ẩn nút Hủy nếu quá hạn)
                                    $deadline = $row['thoi_gian_dong_rieng'] ?? $row['thoi_gian_dong_dk'];
                                    $isExpired = $deadline && strtotime(date('Y-m-d H:i:s')) > strtotime($deadline);
                                ?>
                                    <tr id="row-<?= $row['id'] ?>">
                                        <td><span style="font-weight:700; color:var(--blue); background:#eff6ff; padding:4px 10px; border-radius:7px;"><?= htmlspecialchars($row['ma_lop']) ?></span></td>
                                        <td><div style="font-weight:600;"><?= htmlspecialchars($row['ten_hoc_phan']) ?></div></td>
                                        <td><?= (int)$row['so_tin_chi'] ?></td>
                                        <td><?= htmlspecialchars($row['ten_giang_vien']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($row['ngay_dang_ky'])) ?></td>
                                        <td>
                                            <span class="badge <?= $isDaHuy ? 'badge-danger' : 'badge-success' ?>">
                                                <?= $isDaHuy ? 'Đã hủy' : 'Đã đăng ký' ?>
                                            </span>
                                        </td>
                                        <td style="text-align:right;">
                                            <?php if ($isDaHuy): ?>
                                                <button class="btn btn-disabled" disabled>Đã hủy</button>
                                            <?php elseif ($isExpired): ?>
                                                <button class="btn btn-disabled" title="Đã quá thời hạn hủy" disabled>Quá hạn hủy</button>
                                            <?php else: ?>
                                                <button class="btn btn-danger btn-huy" data-id="<?= $row['id'] ?>">
                                                    <i class="fa-solid fa-xmark"></i> Hủy đăng ký
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
        </main>
    </div>

    <!-- Script xử lý Fetch API -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const csrfToken = '<?= $_SESSION['csrf_token'] ?>';
        const buttons = document.querySelectorAll('.btn-huy');

        buttons.forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Bạn có chắc chắn muốn hủy đăng ký lớp học phần này không?')) return;

                const dangKyId = this.dataset.id;
                const originalText = this.innerHTML;
                
                this.disabled = true;
                this.innerHTML = 'Đang xử lý...';

                fetch('api_huy_dang_ky.php', {
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
                        // Cập nhật giao diện dòng đó
                        const row = document.getElementById(`row-${dangKyId}`);
                        const statusBadge = row.querySelector('.badge');
                        statusBadge.className = 'badge badge-danger';
                        statusBadge.innerText = 'Đã hủy';
                        
                        this.className = 'btn btn-disabled';
                        this.innerHTML = 'Đã hủy';
                    } else {
                        alert('Lỗi: ' + data.message);
                        this.disabled = false;
                        this.innerHTML = originalText;
                    }
                })
                .catch(err => {
                    alert('Có lỗi xảy ra khi kết nối máy chủ.');
                    this.disabled = false;
                    this.innerHTML = originalText;
                });
            });
        });
    });
    </script>
</body>
</html>