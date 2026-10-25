
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$thongBao = $_SESSION['thong_bao'] ?? null;
unset($_SESSION['thong_bao']);

// Hỗ trợ cả thông báo dạng chuỗi và dạng mảng từ code cũ
$noiDungThongBao = '';
$loaiThongBao = 'info';

if (is_string($thongBao)) {
    $noiDungThongBao = $thongBao;
} elseif (is_array($thongBao)) {
    $noiDungThongBao = (string) (
        $thongBao['message']
        ?? $thongBao['noi_dung']
        ?? ''
    );

    if (
        !empty($thongBao['success'])
        || ($thongBao['loai'] ?? '') === 'thanhcong'
    ) {
        $loaiThongBao = 'success';
    } else {
        $loaiThongBao = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký học phần | StudentHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header class="topbar">
        <div class="brand">
            <div class="brand-icon">S</div>
            <div>
                <h1>StudentHub</h1>
                <p>Hệ thống đăng ký học phần</p>
            </div>
        </div>
        <span class="student-label">CỔNG SINH VIÊN</span>
    </header>

    <main class="container">
        <section class="welcome">
            <div>
                <span class="eyebrow">HỌC TẬP • KẾ HOẠCH • TƯƠNG LAI</span>
                <h2>Đăng ký học phần</h2>
                <p>
                    Tra cứu lớp học, kiểm tra sĩ số và đăng ký học phần
                    cho học kỳ của bạn.
                </p>
            </div>
            <div class="welcome-icon">📚</div>
        </section>

        <?php if ($noiDungThongBao !== ''): ?>
            <div
                class="notice <?= htmlspecialchars($loaiThongBao, ENT_QUOTES, 'UTF-8') ?>"
                role="alert"
            >
                <?= htmlspecialchars(
                    $noiDungThongBao,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>
        <?php endif; ?>

        <section class="toolbar">
            <div>
                <h3>Danh sách lớp học phần</h3>
                <p>Chọn lớp phù hợp với kế hoạch học tập của bạn.</p>
            </div>

            <input
                type="search"
                id="searchClass"
                placeholder="Tìm mã lớp, tên học phần..."
                aria-label="Tìm kiếm lớp học phần"
            >
        </section>

        <section class="class-list">
            <?php if (empty($danhSachLop)): ?>
                <div class="empty-state">
                    Hiện chưa có lớp học phần nào.
                </div>
            <?php else: ?>

                <?php foreach ($danhSachLop as $lop): ?>
                    <?php
                    $siSo = (int) $lop['si_so_hien_tai'];
                    $toiDa = (int) $lop['si_so_toi_da'];
                    $conCho = max(0, $toiDa - $siSo);

                    $lopMo = $lop['trang_thai'] === 'mo';
                    $phanTram = $toiDa > 0
                        ? min(100, ($siSo / $toiDa) * 100)
                        : 0;
                    ?>

                    <article
                        class="class-card"
                        data-search="<?= htmlspecialchars(
                            $lop['ma_lop'] . ' ' .
                            $lop['ma_hoc_phan'] . ' ' .
                            $lop['ten_hoc_phan'] . ' ' .
                            $lop['ten_giang_vien'] . ' ' .
                            $lop['ten_hoc_ky'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        <div class="card-top">
                            <span class="subject-code">
                                <?= htmlspecialchars(
                                    $lop['ma_hoc_phan'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <span class="status <?= $lopMo ? 'open' : 'locked' ?>">
                                <?= $lopMo ? 'Lớp đang mở' : 'Lớp đã khóa' ?>
                            </span>
                        </div>

                        <h3>
                            <?= htmlspecialchars(
                                $lop['ten_hoc_phan'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h3>

                        <p class="class-code">
                            Mã lớp:
                            <strong>
                                <?= htmlspecialchars(
                                    $lop['ma_lop'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>
                        </p>

                        <div class="details">
                            <div>
                                <span>Giảng viên</span>
                                <strong>
                                    <?= htmlspecialchars(
                                        $lop['ten_giang_vien'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </div>

                            <div>
                                <span>Học kỳ</span>
                                <strong>
                                    <?= htmlspecialchars(
                                        $lop['ten_hoc_ky'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </div>

                            <div>
                                <span>Số tín chỉ</span>
                                <strong>
                                    <?= (int) $lop['so_tin_chi'] ?> tín chỉ
                                </strong>
                            </div>

                            <div>
                                <span>Sĩ số</span>
                                <strong>
                                    <?= $siSo ?>/<?= $toiDa ?> sinh viên
                                </strong>
                            </div>
                        </div>

                        <div class="seats">
                            <div class="seat-label">
                                <span>Số chỗ còn lại</span>
                                <strong><?= $conCho ?> chỗ</strong>
                            </div>

                            <div
                                class="progress"
                                role="progressbar"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="<?= (int) round($phanTram) ?>"
                            >
                                <div style="width: <?= $phanTram ?>%"></div>
                            </div>
                        </div>

                        <?php if (!$lopMo): ?>
                            <button class="btn disabled" type="button" disabled>
                                Lớp đã khóa
                            </button>

                        <?php elseif ($conCho <= 0): ?>
                            <button class="btn disabled" type="button" disabled>
                                Lớp đã đầy
                            </button>

                        <?php else: ?>
                            <form
                                method="POST"
                                action="index.php?action=dangky"
                                class="register-form"
                            >
                                <input
                                    type="hidden"
                                    name="lop_id"
                                    value="<?= (int) $lop['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(
                                        $_SESSION['csrf_token'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                                <button type="submit" class="btn">
                                    Đăng ký học phần
                                    <span>→</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>

            <?php endif; ?>
        </section>

        <footer>
            StudentHub · Hỗ trợ sinh viên xây dựng kế hoạch học tập
        </footer>
    </main>

    <script src="assets/js/dangky.js" defer></script>
</body>
</html>
