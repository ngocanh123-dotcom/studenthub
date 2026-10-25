<div class="container mt-5">
    <div class="row">
        <!-- Thông tin lớp -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h2 class="card-title fw-bold text-primary mb-1"><?= htmlspecialchars($classInfo['course_name']) ?></h2>
                    <p class="text-muted mb-4">Mã lớp: <span class="badge bg-secondary"><?= htmlspecialchars($classInfo['id']) ?></span> | Mã HP: <?= htmlspecialchars($classInfo['course_code']) ?></p>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0"><strong>👨‍🏫 Giảng viên:</strong> <?= htmlspecialchars($classInfo['lecturer_name']) ?></li>
                        <li class="list-group-item px-0"><strong>📅 Lịch học:</strong> <?= htmlspecialchars($classInfo['schedule']) ?></li>
                        <li class="list-group-item px-0"><strong>📍 Phòng học:</strong> <?= htmlspecialchars($classInfo['room']) ?></li>
                        <li class="list-group-item px-0"><strong>🎓 Số tín chỉ:</strong> <?= htmlspecialchars($classInfo['credits']) ?> TC</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Bảng điều khiển đăng ký -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Tình trạng lớp</h5>
                    
                    <div class="d-flex justify-content-between mb-1">
                        <span>Đã đăng ký:</span>
                        <span id="seat-count" class="fw-bold"><?= $classInfo['registered_seats'] ?> / <?= $classInfo['max_seats'] ?></span>
                    </div>
                    <?php 
                        $percent = ($classInfo['registered_seats'] / $classInfo['max_seats']) * 100;
                        $progressColor = $percent >= 90 ? 'bg-danger' : ($percent >= 60 ? 'bg-warning' : 'bg-success');
                    ?>
                    <div class="progress mb-4" style="height: 10px;">
                        <div class="progress-bar <?= $progressColor ?>" role="progressbar" style="width: <?= $percent ?>%"></div>
                    </div>

                    <!-- Khu vực nút xử lý -->
                    <div id="action-area">
                        <?php if ($classInfo['status'] !== 'active'): ?>
                            <button class="btn btn-dark w-100 disabled">🔒 Lớp đã khóa</button>
                        <?php elseif ($isEnrolled): ?>
                            <button class="btn btn-success w-100 disabled">✅ Đã đăng ký</button>
                        <?php elseif ($isFull): ?>
                            <button class="btn btn-danger w-100 disabled">❌ Đã đủ sĩ số</button>
                        <?php else: ?>
                            <button id="btn-enroll" class="btn btn-primary w-100 fw-bold">Đăng ký học phần</button>
                        <?php endif; ?>
                    </div>
                    <!-- Thông báo Fetch API -->
                    <div id="enroll-message" class="mt-3 small text-center"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const btnEnroll = document.getElementById('btn-enroll');
    if (btnEnroll) {
        btnEnroll.addEventListener('click', function() {
            btnEnroll.innerHTML = 'Đang xử lý...';
            btnEnroll.disabled = true;

            // Dùng Fetch API đáp ứng yêu cầu bắt buộc
            fetch('/api/enroll', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    class_id: <?= $classInfo['id'] ?>,
                    csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                const msgBox = document.getElementById('enroll-message');
                if (data.status === 'success') {
                    // Thành công: Cập nhật giao diện không cần reload
                    btnEnroll.className = 'btn btn-success w-100 disabled';
                    btnEnroll.innerHTML = '✅ Đã đăng ký';
                    msgBox.innerHTML = `<span class="text-success">${data.message}</span>`;
                    
                    // Tăng sĩ số trên UI
                    let currentSeats = <?= $classInfo['registered_seats'] ?> + 1;
                    document.getElementById('seat-count').innerText = `${currentSeats} / <?= $classInfo['max_seats'] ?>`;
                } else {
                    // Lỗi: Khôi phục nút và báo lỗi
                    btnEnroll.innerHTML = 'Đăng ký học phần';
                    btnEnroll.disabled = false;
                    msgBox.innerHTML = `<span class="text-danger">⚠️ ${data.message}</span>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btnEnroll.innerHTML = 'Đăng ký học phần';
                btnEnroll.disabled = false;
            });
        });
    }
});
</script>