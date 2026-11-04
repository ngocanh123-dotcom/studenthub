// assets/js/change_password.js
// Modal Đổi Mật Khẩu Tích Hợp Toàn Diện (StudentHub)

(function() {
    if (!document.getElementById('changePasswordModalStyles')) {
        const style = document.createElement('style');
        style.id = 'changePasswordModalStyles';
        style.textContent = `
            .cp-modal-overlay {
                position: fixed;
                top: 0; left: 0;
                width: 100vw; height: 100vh;
                background: rgba(15, 23, 42, 0.65);
                backdrop-filter: blur(5px);
                -webkit-backdrop-filter: blur(5px);
                z-index: 99999;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 20px;
                animation: cpFadeIn 0.2s ease-out;
            }
            @keyframes cpFadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            .cp-modal-card {
                background: #ffffff;
                border-radius: 18px;
                box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(226, 232, 240, 0.8);
                width: 100%;
                max-width: 440px;
                padding: 28px 30px;
                position: relative;
                color: #0f172a;
                font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            }
            .cp-close-btn {
                position: absolute;
                top: 14px; right: 14px;
                width: 32px; height: 32px;
                border-radius: 50%;
                border: none;
                background: #f1f5f9;
                color: #64748b;
                font-size: 18px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.15s;
                line-height: 1;
            }
            .cp-close-btn:hover {
                background: #e2e8f0;
                color: #0f172a;
            }
            .cp-header {
                text-align: center;
                margin-bottom: 20px;
            }
            .cp-header-icon {
                font-size: 26px;
                width: 48px; height: 48px;
                border-radius: 14px;
                background: #eff6ff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 8px;
                box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
            }
            .cp-header h3 {
                font-size: 18px;
                font-weight: 800;
                color: #0f172a;
                margin: 0 0 4px 0;
            }
            .cp-header p {
                font-size: 12px;
                color: #64748b;
                margin: 0;
            }
            .cp-alert {
                padding: 10px 14px;
                border-radius: 8px;
                font-size: 12.5px;
                font-weight: 600;
                margin-bottom: 14px;
                line-height: 1.45;
            }
            .cp-alert-error {
                background: #fef2f2;
                color: #dc2626;
                border: 1px solid #fecaca;
            }
            .cp-alert-success {
                background: #ecfdf5;
                color: #059669;
                border: 1px solid #a7f3d0;
            }
            .cp-field {
                margin-bottom: 14px;
            }
            .cp-field label {
                display: block;
                font-size: 12.5px;
                font-weight: 600;
                color: #334155;
                margin-bottom: 5px;
            }
            .cp-input-wrap {
                position: relative;
                display: flex;
                align-items: center;
            }
            .cp-input {
                width: 100%;
                height: 42px;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                padding: 0 38px 0 12px;
                font-size: 13.5px;
                font-family: inherit;
                color: #0f172a;
                background: #f8fafc;
                transition: border-color 0.15s, box-shadow 0.15s;
            }
            .cp-input:focus {
                outline: none;
                border-color: #2563eb;
                background: #ffffff;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            }
            .cp-eye-btn {
                position: absolute;
                right: 8px;
                background: none;
                border: none;
                cursor: pointer;
                font-size: 15px;
                color: #64748b;
                padding: 4px;
            }
            .cp-btn-submit {
                width: 100%;
                height: 44px;
                background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
                color: #ffffff;
                border: none;
                border-radius: 8px;
                font-size: 13.5px;
                font-weight: 700;
                font-family: inherit;
                cursor: pointer;
                transition: all 0.15s;
                margin-top: 14px;
                box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            }
            .cp-btn-submit:hover {
                background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
                transform: translateY(-1px);
            }
            .cp-footer-actions {
                display: flex;
                justify-content: center;
                margin-top: 10px;
            }
            .cp-btn-cancel {
                background: none;
                border: none;
                color: #64748b;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: underline;
                padding: 0;
            }
            .cp-btn-cancel:hover {
                color: #2563eb;
            }
        `;
        document.head.appendChild(style);
    }
})();

function openChangePasswordModal() {
    let modal = document.getElementById('changePasswordModal');
    if (!modal) {
        const modalDiv = document.createElement('div');
        modalDiv.id = 'changePasswordModal';
        modalDiv.className = 'cp-modal-overlay';
        modalDiv.innerHTML = `
            <div class="cp-modal-card">
                <button type="button" class="cp-close-btn" onclick="closeChangePasswordModal()" title="Đóng">&times;</button>
                <div class="cp-header">
                    <div class="cp-header-icon">🔑</div>
                    <h3>Đổi mật khẩu tài khoản</h3>
                    <p>Cập nhật mật khẩu cá nhân định kỳ để tăng cường bảo mật</p>
                </div>

                <div id="cpAlert" class="cp-alert" style="display: none;"></div>

                <form id="cpForm" onsubmit="submitChangePassword(event)">
                    <input type="hidden" name="csrf_token" id="modal_csrf_token" value="">
                    <div class="cp-field">
                        <label for="cp_old_password">Mật khẩu hiện tại</label>
                        <div class="cp-input-wrap">
                            <input type="password" id="cp_old_password" name="old_password" class="cp-input" placeholder="Nhập mật khẩu đang sử dụng" required autofocus>
                            <button type="button" class="cp-eye-btn" onclick="toggleCpPass('cp_old_password', this)" title="Hiện mật khẩu">👁</button>
                        </div>
                    </div>

                    <div class="cp-field">
                        <label for="cp_new_password">Mật khẩu mới</label>
                        <div class="cp-input-wrap">
                            <input type="password" id="cp_new_password" name="new_password" class="cp-input" placeholder="Tối thiểu 8 ký tự" minlength="8" required>
                            <button type="button" class="cp-eye-btn" onclick="toggleCpPass('cp_new_password', this)" title="Hiện mật khẩu">👁</button>
                        </div>
                        <p style="font-size: 11.5px; color: #64748b; margin-top: 4px; margin-bottom: 0; line-height: 1.35;">Yêu cầu: Tối thiểu 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt (!@#$...).</p>
                    </div>

                    <div class="cp-field">
                        <label for="cp_confirm_password">Xác nhận mật khẩu mới</label>
                        <div class="cp-input-wrap">
                            <input type="password" id="cp_confirm_password" name="confirm_password" class="cp-input" placeholder="Nhập lại mật khẩu mới" minlength="6" required>
                            <button type="button" class="cp-eye-btn" onclick="toggleCpPass('cp_confirm_password', this)" title="Hiện mật khẩu">👁</button>
                        </div>
                    </div>

                    <button type="submit" id="cpSubmitBtn" class="cp-btn-submit">
                        <span>Xác nhận đổi mật khẩu</span>
                    </button>

                    <div class="cp-footer-actions">
                        <button type="button" onclick="closeChangePasswordModal()" class="cp-btn-cancel">Hủy bỏ</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modalDiv);

        modalDiv.addEventListener('click', function(e) {
            if (e.target === modalDiv) {
                closeChangePasswordModal();
            }
        });

        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeChangePasswordModal();
            }
        });

        modal = modalDiv;
    }

    const form = document.getElementById('cpForm');
    if (form) form.reset();

    // Đồng bộ mã CSRF Token vào ô #modal_csrf_token từ trang chính hoặc cookie
    const cpCsrfInput = document.getElementById('modal_csrf_token');
    if (cpCsrfInput) {
        let token = '';
        const pageCsrf = document.querySelector('input[name="csrf_token"]:not(#cpForm #modal_csrf_token)');
        if (pageCsrf && pageCsrf.value) token = pageCsrf.value.trim();
        if (!token) {
            const cookieMatch = document.cookie.match(/(?:^|;)\s*sh_csrf_token=([^;]+)/);
            if (cookieMatch) token = decodeURIComponent(cookieMatch[1]);
        }
        if (token) cpCsrfInput.value = token;
    }
    const alertBox = document.getElementById('cpAlert');
    if (alertBox) alertBox.style.display = 'none';
    const submitBtn = document.getElementById('cpSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<span>Xác nhận đổi mật khẩu</span>';
    }

    modal.style.display = 'flex';
    setTimeout(() => {
        const first = document.getElementById('cp_old_password');
        if (first) first.focus();
    }, 50);
}

function closeChangePasswordModal() {
    const modal = document.getElementById('changePasswordModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function toggleCpPass(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.textContent = isPass ? '🔒' : '👁';
}

function submitChangePassword(event) {
    event.preventDefault();
    const oldPass = document.getElementById('cp_old_password').value;
    const newPass = document.getElementById('cp_new_password').value;
    const confirmPass = document.getElementById('cp_confirm_password').value;
    const alertBox = document.getElementById('cpAlert');
    const submitBtn = document.getElementById('cpSubmitBtn');

    if (!oldPass || !newPass || !confirmPass) {
        alertBox.className = 'cp-alert cp-alert-error';
        alertBox.textContent = 'Vui lòng nhập đầy đủ các trường thông tin!';
        alertBox.style.display = 'block';
        return;
    }

    const policyRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

    if (newPass.length < 8) {
        alertBox.className = 'cp-alert cp-alert-error';
        alertBox.textContent = 'Mật khẩu mới phải có tối thiểu 8 ký tự!';
        alertBox.style.display = 'block';
        return;
    }

    if (!policyRegex.test(newPass)) {
        alertBox.className = 'cp-alert cp-alert-error';
        alertBox.textContent = 'Mật khẩu mới phải chứa ít nhất 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt (!@#$%...)!';
        alertBox.style.display = 'block';
        return;
    }

    if (newPass === '123456' || newPass.toLowerCase() === 'password') {
        alertBox.className = 'cp-alert cp-alert-error';
        alertBox.textContent = 'Mật khẩu mới không được là mật khẩu mặc định (123456)!';
        alertBox.style.display = 'block';
        return;
    }

    if (newPass !== confirmPass) {
        alertBox.className = 'cp-alert cp-alert-error';
        alertBox.textContent = 'Xác nhận mật khẩu mới không khớp!';
        alertBox.style.display = 'block';
        return;
    }

    if (newPass === oldPass) {
        alertBox.className = 'cp-alert cp-alert-error';
        alertBox.textContent = 'Mật khẩu mới không được trùng với mật khẩu hiện tại!';
        alertBox.style.display = 'block';
        return;
    }

    // Lấy chính xác giá trị CSRF token từ ô input #modal_csrf_token
    const modalCsrfEl = document.getElementById('modal_csrf_token');
    let csrfToken = modalCsrfEl ? modalCsrfEl.value.trim() : '';

    if (!csrfToken) {
        const pageToken = document.querySelector('input[name="csrf_token"]:not(#modal_csrf_token)');
        if (pageToken && pageToken.value) csrfToken = pageToken.value.trim();
    }
    if (!csrfToken) {
        const cookieMatch = document.cookie.match(/(?:^|;)\s*sh_csrf_token=([^;]+)/);
        if (cookieMatch) csrfToken = decodeURIComponent(cookieMatch[1]);
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>Đang cập nhật...</span>';

    // Tạo payload FormData / URLSearchParams truyền kèm cả csrf_token và csrf
    const payload = new URLSearchParams();
    payload.append('ajax', '1');
    payload.append('csrf_token', csrfToken);
    payload.append('csrf', csrfToken);
    payload.append('old_password', oldPass);
    payload.append('new_password', newPass);
    payload.append('confirm_password', confirmPass);

    fetch('/studenthub/public/index.php?route=doi_mat_khau.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken
        },
        body: payload
    })
    .then(res => res.json())
    .then(data => {
        if (data.ok === true || data.status === 'success') {
            alertBox.className = 'cp-alert cp-alert-success';
            alertBox.textContent = '✓ ' + (data.message || 'Đổi mật khẩu thành công!');
            alertBox.style.display = 'block';
            // Xóa cookie cảnh báo đổi mật khẩu mặc định
            document.cookie = 'sh_must_change_password=; Max-Age=0; path=/;';
            const banner = document.getElementById('shDefaultPasswordBanner');
            if (banner) banner.style.display = 'none';
            document.getElementById('cpForm').reset();
            setTimeout(() => {
                closeChangePasswordModal();
            }, 1800);
        } else {
            alertBox.className = 'cp-alert cp-alert-error';
            alertBox.textContent = '⚠️ ' + (data.message || 'Đổi mật khẩu thất bại. Vui lòng thử lại!');
            alertBox.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span>Xác nhận đổi mật khẩu</span>';
        }
    })
    .catch(err => {
        alertBox.className = 'cp-alert cp-alert-error';
        alertBox.textContent = '⚠️ Có lỗi xảy ra trong quá trình gửi yêu cầu. Vui lòng thử lại!';
        alertBox.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<span>Xác nhận đổi mật khẩu</span>';
    });
}

// Tự động kiểm tra cờ phải đổi mật khẩu (sh_must_change_password) để mở modal ở lần đăng nhập đầu tiên
(function checkAutoOpenChangePassword() {
    function initCheck() {
        const isMustChange = document.cookie.split(';').some(item => item.trim().startsWith('sh_must_change_password=1'));
        if (isMustChange) {
            setTimeout(function() {
                if (typeof openChangePasswordModal === 'function') {
                    openChangePasswordModal();
                    const alertBox = document.getElementById('cpAlert');
                    if (alertBox) {
                        alertBox.className = 'cp-alert';
                        alertBox.style.background = '#fffbeb';
                        alertBox.style.color = '#b45309';
                        alertBox.style.border = '1px solid #fde68a';
                        alertBox.style.display = 'block';
                        alertBox.innerHTML = '<strong>⚠️ Cảnh báo bảo mật:</strong> Mật khẩu của bạn là mật khẩu mặc định (123456). Vui lòng đặt mật khẩu mới mạnh hơn để bảo vệ tài khoản!';
                    }
                }
            }, 350);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCheck);
    } else {
        initCheck();
    }
})();
