
const form = document.getElementById("loginForm");
const usernameInput = document.getElementById("username");
const passwordInput = document.getElementById("password");
const rememberInput = document.getElementById("remember");
const message = document.getElementById("message");
const togglePassword = document.getElementById("togglePassword");
const usernameError = document.getElementById("usernameError");
const passwordError = document.getElementById("passwordError");
const submitBtn = document.getElementById("submitBtn");

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
} catch (e) {}
const forgotLink = document.getElementById("forgotLink");

const tenVaiTroMap = {
    sinhvien: "Sinh viên",
    giangvien: "Giảng viên",
    quantri: "Quản trị viên"
};


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

// Ghi nhớ tên đăng nhập, không lưu mật khẩu
try {
    const savedUsername = localStorage.getItem("rememberedUsername");

    if (savedUsername) {
        usernameInput.value = savedUsername;
        rememberInput.checked = true;
    }
} catch (error) {
    // Bỏ qua nếu trình duyệt không cho phép sử dụng localStorage
}

// Hiện hoặc ẩn mật khẩu
if (togglePassword) {
    togglePassword.addEventListener("click", () => {
        const isPassword = passwordInput.type === "password";

        passwordInput.type = isPassword ? "text" : "password";
        togglePassword.title = isPassword ? "Ẩn mật khẩu" : "Hiện mật khẩu";
        togglePassword.setAttribute(
            "aria-label",
            isPassword ? "Ẩn mật khẩu" : "Hiện mật khẩu"
        );
    });
}

// Xóa thông báo lỗi khi người dùng sửa dữ liệu
[usernameInput, passwordInput].forEach(input => {
    input.addEventListener("input", () => {
        input.classList.remove("invalid");
        clearErrors();
    });
});

form.addEventListener("submit", event => {

  let hasError = false;
  clearErrors();

  const username = usernameInput.value.trim();
  const password = passwordInput.value;
  const selectedRole = form.querySelector('input[name="role"]:checked');

  // 1. Kiểm tra trên giao diện (Frontend Validation)
  if (!selectedRole) {
    showMessage("Vui lòng chọn vai trò đăng nhập.", "error");
    hasError = true;
  }

  const usernameRegex = /^[a-zA-Z0-9_]{3,30}$/;
  if (!username) {
    setFieldError(usernameInput, usernameError, "Vui lòng nhập tên đăng nhập.");
    hasError = true;
  } else if (!usernameRegex.test(username)) {
    setFieldError(usernameInput, usernameError, "Định dạng không hợp lệ.");
    hasError = true;
  }

  if (!password || password.length < 6) {
    setFieldError(passwordInput, passwordError, "Mật khẩu phải có ít nhất 6 ký tự.");
    hasError = true;
  }

  // Nếu có lỗi nhập liệu thì CHẶN form lại, không gửi lên PHP
  if (hasError) {
    event.preventDefault();
    return;
  }

  // 2. Nếu điền chuẩn, lưu localStorage và CHO PHÉP form gửi data sang index.php
  try {
    if (rememberInput && rememberInput.checked) {
      localStorage.setItem("rememberedUsername", username);
    } else {
      localStorage.removeItem("rememberedUsername");

    clearErrors();

    const username = usernameInput.value.trim();
    const password = passwordInput.value;
    const selectedRole = form.querySelector('input[name="role"]:checked');

    // Kiểm tra vai trò
    if (!selectedRole || !tenVaiTroMap[selectedRole.value]) {
        event.preventDefault();
        showMessage("Vui lòng chọn vai trò đăng nhập.", "error");
        return;
    }


  submitBtn.disabled = true;
  submitBtn.innerHTML = `<span>Đang xác thực hệ thống...</span>`;
});

// Xử lý quên mật khẩu
document.getElementById("forgotLink").addEventListener("click", event => {
  event.preventDefault();
  showMessage("Vui lòng liên hệ phòng Đào tạo để được cấp lại mật khẩu.", "error");
});
    // Kiểm tra tên đăng nhập
    const usernameRegex = /^[a-zA-Z0-9_]{3,30}$/;

    if (!username) {
        event.preventDefault();
        setFieldError(
            usernameInput,
            usernameError,
            "Vui lòng nhập tên đăng nhập."
        );
        showMessage("Vui lòng nhập tên đăng nhập.", "error");
        usernameInput.focus();
        return;
    }

    if (!usernameRegex.test(username)) {
        event.preventDefault();
        setFieldError(
            usernameInput,
            usernameError,
            "Tên đăng nhập từ 3-30 ký tự, chỉ dùng chữ, số và dấu gạch dưới."
        );
        showMessage("Tên đăng nhập không đúng định dạng.", "error");
        usernameInput.focus();
        return;
    }

    // Kiểm tra mật khẩu
    if (!password.trim()) {
        event.preventDefault();
        setFieldError(
            passwordInput,
            passwordError,
            "Vui lòng nhập mật khẩu."
        );
        showMessage("Vui lòng nhập mật khẩu.", "error");
        passwordInput.focus();
        return;
    }

    if (password.length < 6) {
        event.preventDefault();
        setFieldError(
            passwordInput,
            passwordError,
            "Mật khẩu phải có ít nhất 6 ký tự."
        );
        showMessage("Mật khẩu phải có ít nhất 6 ký tự.", "error");
        passwordInput.focus();
        return;
    }

    // Ghi nhớ tên đăng nhập nếu được chọn
    try {
        if (rememberInput.checked) {
            localStorage.setItem("rememberedUsername", username);
        } else {
            localStorage.removeItem("rememberedUsername");
        }
    } catch (error) {
        // Không ảnh hưởng đến việc đăng nhập
    }

    // Không kiểm tra tài khoản demo tại JavaScript.
    // Để form gửi username, password và role đến PHP.
    // PHP sẽ xác thực với MySQL và chuyển trang khi thành công.
    submitBtn.disabled = true;
    submitBtn.textContent = "ĐANG KIỂM TRA ĐĂNG NHẬP...";
});

// Khôi phục trạng thái nút nếu PHP trả lại trang do đăng nhập lỗi
window.addEventListener("pageshow", () => {
    submitBtn.disabled = false;
    submitBtn.innerHTML = "<span>ĐĂNG NHẬP VÀO HỆ THỐNG</span>";
});

// Quên mật khẩu
if (forgotLink) {
    forgotLink.addEventListener("click", event => {
        event.preventDefault();
        showMessage(
            "Vui lòng liên hệ Quản trị viên để được cấp lại mật khẩu.",
            "error"
        );
    });
}

