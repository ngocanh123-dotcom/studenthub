document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("authLoginForm");
  const usernameInput = document.getElementById("usernameInput");
  const passwordInput = document.getElementById("passwordInput");
  const rememberCheckbox = document.getElementById("rememberMeCheckbox");
  const toggleBtn = document.getElementById("btnTogglePassword");
  const banner = document.getElementById("statusBanner");
  const submitBtn = document.getElementById("btnSubmitAuth");

  // Icon con mắt mở & đóng SVG
  const eyeOpen = `
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>
    </svg>`;
  const eyeClosed = `
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
      <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
      <line x1="1" y1="1" x2="23" y2="23"/>
    </svg>`;

  // 1. Phục hồi tên đăng nhập đã lưu trong localStorage
  try {
    const savedUser = localStorage.getItem("remembered_user");
    if (savedUser && rememberCheckbox) {
      usernameInput.value = savedUser;
      rememberCheckbox.checked = true;
    }
  } catch (e) {}

  // 2. Chuyển đổi trạng thái ẩn/hiện mật khẩu
  if (toggleBtn && passwordInput) {
    toggleBtn.addEventListener("click", () => {
      const isPassword = passwordInput.type === "password";
      passwordInput.type = isPassword ? "text" : "password";
      toggleBtn.innerHTML = isPassword ? eyeClosed : eyeOpen;
      toggleBtn.title = isPassword ? "Ẩn mật khẩu" : "Hiện mật khẩu";
    });
  }

  // 3. Xử lý ghi nhớ và hiệu ứng bấm submit
  if (form) {
    form.addEventListener("submit", (e) => {
      const u = usernameInput.value.trim();
      const p = passwordInput.value;

      if (!u || !p) {
        e.preventDefault();
        if (banner) {
          banner.style.display = "block";
          banner.className = "notification-banner error";
          banner.textContent = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.";
        }
        return;
      }

      // Lưu/Xóa localStorage
      try {
        if (rememberCheckbox && rememberCheckbox.checked) {
          localStorage.setItem("remembered_user", u);
        } else {
          localStorage.removeItem("remembered_user");
        }
      } catch (err) {}

      // Trạng thái khóa nút khi dữ liệu gửi lên PHP
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<span>Đang xác thực hệ thống...</span>`;
      }
    });
  }

  // 4. Quên mật khẩu
  const forgotLink = document.getElementById("linkForgotPassword");
  if (forgotLink) {
    forgotLink.addEventListener("click", (e) => {
      e.preventDefault();
      alert("Vui lòng liên hệ phòng Đào tạo hoặc Quản trị viên để được cấp lại mật khẩu truy cập.");
    });
  }
});