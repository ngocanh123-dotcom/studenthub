<!-- app/Views/classes/detail.php -->
<div class="class-detail-container">
    <!-- Breadcrumb điều hướng -->
    <div class="breadcrumb">
        <a href="index.php?controller=class">Danh sách lớp</a> > 
        <span>Chi tiết lớp: <?= htmlspecialchars($classInfo['ma_lop']) ?></span>
    </div>

    <div class="detail-header">
        <h1><?= htmlspecialchars($classInfo['ten_mon_hoc']) ?></h1>
        <span class="status-badge active">Đang mở đăng ký</span>
    </div>

    <!-- KHỐI 1: THÔNG TIN CHUNG CỦA LỚP -->
    <div class="info-card">
        <h3 class="card-title">Thông tin học phần</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Mã lớp</span>
                <span class="info-value font-bold text-blue"><?= htmlspecialchars($classInfo['ma_lop']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Giảng viên phụ trách</span>
                <span class="info-value"><?= htmlspecialchars($classInfo['ten_giang_vien']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Số tín chỉ</span>
                <span class="info-value"><?= htmlspecialchars($classInfo['so_tin_chi']) ?> TC</span>
            </div>
            <div class="info-item">
                <span class="info-label">Lịch học / Phòng</span>
                <span class="info-value"><?= htmlspecialchars($classInfo['lich_hoc']) ?> | Phòng <?= htmlspecialchars($classInfo['phong_hoc']) ?></span>
            </div>
        </div>

        <!-- Thanh tiến trình sĩ số -->
        <div class="capacity-section">
            <div class="capacity-header">
                <span class="info-label">Sĩ số lớp (<?= htmlspecialchars($classInfo['si_so_hien_tai']) ?>/<?= htmlspecialchars($classInfo['si_so_toi_da']) ?>)</span>
            </div>
            <?php 
                $percent = ($classInfo['si_so_toi_da'] > 0) ? ($classInfo['si_so_hien_tai'] / $classInfo['si_so_toi_da']) * 100 : 0; 
                $barColor = $percent >= 100 ? 'bg-danger' : 'bg-primary';
            ?>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill <?= $barColor ?>" style="width: <?= $percent ?>%;"></div>
            </div>
        </div>
    </div>

    <!-- KHỐI 2: DANH SÁCH SINH VIÊN -->
    <div class="students-card">
        <h3 class="card-title">Danh sách sinh viên đã đăng ký</h3>
        
        <?php if (!empty($students)): ?>
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã Sinh Viên</th>
                        <th>Họ và Tên</th>
                        <th>Lớp Hành Chính</th>
                        <th>Thời gian đăng ký</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $sv): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td class="font-bold"><?= htmlspecialchars($sv['ma_sinh_vien']) ?></td>
                        <td><?= htmlspecialchars($sv['ho_ten']) ?></td>
                        <td><?= htmlspecialchars($sv['lop_hanh_chinh']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($sv['ngay_dang_ky'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Chưa có sinh viên nào đăng ký lớp học phần này.</p>
            </div>
        <?php endif; ?>
    </div>
</div>