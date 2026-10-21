const form = document.getElementById("loginForm");
const usernameInput = document.getElementById("username");
const passwordInput = document.getElementById("password");
const rememberInput = document.getElementById("remember");
const message = document.getElementById("message");
const togglePassword = document.getElementById("togglePassword");
const usernameError = document.getElementById("usernameError");
const passwordError = document.getElementById("passwordError");
const submitBtn = document.getElementById("submitBtn");

// Danh sách tài khoản demo
const taiKhoanDemo = {
  sv001:  { password: "Sv@123",    role: "sinhvien",  tenVaiTro: "Sinh viên" },
  gv001:  { password: "Gv@123",    role: "giangvien", tenVaiTro: "Giảng viên" },
  admin:  { password: "Admin@123", role: "quantri",   tenVaiTro: "Quản trị viên" },
  admin1: { password: "Admin@123", role: "quantri",   tenVaiTro: "Quản trị viên 1" },
  admin2: { password: "Admin@123", role: "quantri",   tenVaiTro: "Quản trị viên 2" },
  admin3: { password: "Admin@123", role: "quantri",   tenVaiTro: "Quản trị viên 3" }
};

const tenVaiTroMap = {
  sinhvien: "Sinh viên",
  giangvien: "Giảng viên",
  quantri: "Quản trị viên"
};

// SVG Icon con mắt đóng / mở
const eyeOpenSVG = `
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
    <circle cx="12" cy="12" r="3"/>
  </svg>`;

const eyeClosedSVG = `
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
    <line x1="1" y1="1" x2="23" y2="23"/>
  </svg>`;

// Phục hồi tên đăng nhập đã lưu trong localStorage
try {
  const savedUsername = localStorage.getItem("rememberedUsername");
  if (savedUsername && rememberInput) {
    usernameInput.value = savedUsername;
    rememberInput.checked = true;
  }
} catch (e) {
  // Tránh lỗi môi trường Private/Incognito chặn Storage
}

function showMessage(text, type) {
  message.textContent = text;
  message.className = "status-banner " + type;
}

function setFieldError(input, errorEl, text) {
  input.classList.add("invalid");
  if (errorEl) errorEl.textContent = text;
}

function clearErrors() {
  usernameInput.classList.remove("invalid");
  passwordInput.classList.remove("invalid");
  if (usernameError) usernameError.textContent = "";
  if (passwordError) passwordError.textContent = "";
  message.className = "status-banner";
  message.textContent = "";
}

// Ẩn hoặc hiện mật khẩu
togglePassword.addEventListener("click", () => {
  const isPass = passwordInput.type === "password";
  passwordInput.type = isPass ? "text" : "password";
  togglePassword.innerHTML = isPass ? eyeClosedSVG : eyeOpenSVG;
  togglePassword.title = isPass ? "Ẩn mật khẩu" : "Hiện mật khẩu";
});

// Xóa lỗi tức thì khi người dùng gõ phím
[usernameInput, passwordInput].forEach(input => {
  input.addEventListener("input", () => {
    input.classList.remove("invalid");
    clearErrors();
  });
});

// Xử lý Validation Submit
form.addEventListener("submit", event => {
  event.preventDefault();
  clearErrors();

  const username = usernameInput.value.trim();
  const password = passwordInput.value;

  // 1. Kiểm tra vai trò
  const selectedRole = form.querySelector('input[name="role"]:checked');
  if (!selectedRole || !tenVaiTroMap[selectedRole.value]) {
    showMessage("Vui lòng chọn vai trò đăng nhập.", "error");
    return;
  }
  const role = selectedRole.value;

  // 2. Kiểm tra tên đăng nhập
  const usernameRegex = /^[a-zA-Z0-9_]{3,30}$/;
  if (!username) {
    setFieldError(usernameInput, usernameError, "Vui lòng nhập tên đăng nhập.");
    showMessage("Vui lòng nhập tên đăng nhập.", "error");
    usernameInput.focus();
    return;
  }
  if (!usernameRegex.test(username)) {
    setFieldError(usernameInput, usernameError, "Tên đăng nhập từ 3-30 ký tự, không chứa ký tự đặc biệt.");
    showMessage("Định dạng tên đăng nhập không hợp lệ.", "error");
    usernameInput.focus();
    return;
  }

  // 3. Kiểm tra mật khẩu
  if (!password.trim()) {
    setFieldError(passwordInput, passwordError, "Vui lòng nhập mật khẩu.");
    showMessage("Vui lòng nhập mật khẩu.", "error");
    passwordInput.focus();
    return;
  }
  if (password.length < 6) {
    setFieldError(passwordInput, passwordError, "Mật khẩu phải có ít nhất 6 ký tự.");
    showMessage("Mật khẩu phải có ít nhất 6 ký tự.", "error");
    passwordInput.focus();
    return;
  }

  // 4. Kiểm tra tài khoản và đối chiếu vai trò
  const account = taiKhoanDemo[username];
  if (!account) {
    setFieldError(usernameInput, usernameError, "Tài khoản không tồn tại trong hệ thống.");
    showMessage("Tài khoản không tồn tại trong hệ thống!", "error");
    usernameInput.focus();
    return;
  }

  if (account.password !== password) {
    setFieldError(passwordInput, passwordError, "Mật khẩu không chính xác.");
    showMessage("Mật khẩu không chính xác!", "error");
    passwordInput.focus();
    return;
  }

  if (account.role !== role) {
    showMessage(`Sai vai trò! Tài khoản "${username}" không thuộc vai trò ${tenVaiTroMap[role]}.`, "error");
    return;
  }

  // 5. Lưu tên đăng nhập nếu chọn Ghi nhớ
  try {
    if (rememberInput && rememberInput.checked) {
      localStorage.setItem("rememberedUsername", username);
    } else {
      localStorage.removeItem("rememberedUsername");
    }
  } catch (e) {}

  // 6. Hiệu ứng phản hồi thành công
  submitBtn.disabled = true;
  submitBtn.innerHTML = `<span>Đang xác thực hệ thống...</span>`;
  
  showMessage(`Đăng nhập thành công! Xin chào ${account.tenVaiTro}.`, "success");

  setTimeout(() => {
    submitBtn.disabled = false;
    submitBtn.innerHTML = `<span>ĐĂNG NHẬP VÀO HỆ THỐNG</span>`;
  }, 1200);
});

// Xử lý quên mật khẩu
document.getElementById("forgotLink").addEventListener("click", event => {
  event.preventDefault();
  showMessage("Vui lòng liên hệ phòng Đào tạo hoặc Quản trị viên để được cấp lại mật khẩu.", "error");
});