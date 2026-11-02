
const form = document.getElementById("loginForm");
const usernameInput = document.getElementById("username");
const passwordInput = document.getElementById("password");
const rememberInput = document.getElementById("remember");
const message = document.getElementById("message");
const togglePassword = document.getElementById("togglePassword");
const usernameError = document.getElementById("usernameError");
const passwordError = document.getElementById("passwordError");
const submitBtn = document.getElementById("submitBtn");
const forgotLink = document.getElementById("forgotLink");

const tenVaiTroMap = {
    sinhvien: "Sinh viên",
    giangvien: "Giảng viên",
    quantri: "Quản trị viên"
};

const usernameRegex = /^[a-zA-Z0-9_]{3,30}$/;

// Hiện thông báo
function showMessage(text, type) {
    if (!message) return;

    message.textContent = text;
    message.className = "status-banner " + type;
}

// Hiện lỗi tại ô nhập liệu
function setFieldError(input, errorEl, text) {
    if (input) {
        input.classList.add("invalid");
    }

    if (errorEl) {
        errorEl.textContent = text;
    }
}

// Xóa lỗi
function clearErrors() {
    if (usernameInput) {
        usernameInput.classList.remove("invalid");
    }

    if (passwordInput) {
        passwordInput.classList.remove("invalid");
    }

    if (usernameError) {
        usernameError.textContent = "";
    }

    if (passwordError) {
        passwordError.textContent = "";
    }

    if (message) {
        message.className = "status-banner";
        message.textContent = "";
    }
}

// Ghi nhớ tên đăng nhập, không lưu mật khẩu
try {
    const savedUsername = localStorage.getItem("rememberedUsername");

    if (savedUsername && usernameInput && rememberInput) {
        usernameInput.value = savedUsername;
        rememberInput.checked = true;
    }
} catch (error) {
    // Bỏ qua nếu trình duyệt không hỗ trợ localStorage
}

// Hiện hoặc ẩn mật khẩu
if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", function () {
        const isPassword = passwordInput.type === "password";

        passwordInput.type = isPassword ? "text" : "password";

        togglePassword.title = isPassword
            ? "Ẩn mật khẩu"
            : "Hiện mật khẩu";

        togglePassword.setAttribute(
            "aria-label",
            isPassword ? "Ẩn mật khẩu" : "Hiện mật khẩu"
        );
    });
}

// Xóa lỗi khi người dùng nhập lại
if (usernameInput) {
    usernameInput.addEventListener("input", function () {
        usernameInput.classList.remove("invalid");

        if (usernameError) {
            usernameError.textContent = "";
        }

        if (message) {
            message.textContent = "";
            message.className = "status-banner";
        }
    });
}

if (passwordInput) {
    passwordInput.addEventListener("input", function () {
        passwordInput.classList.remove("invalid");

        if (passwordError) {
            passwordError.textContent = "";
        }

        if (message) {
            message.textContent = "";
            message.className = "status-banner";
        }
    });
}

// Xử lý đăng nhập
if (form) {
    form.addEventListener("submit", function (event) {
        clearErrors();

        let hasError = false;

        const username = usernameInput
            ? usernameInput.value.trim()
            : "";

        const password = passwordInput
            ? passwordInput.value
            : "";

        const selectedRole = form.querySelector(
            'input[name="role"]:checked'
        );

        // Kiểm tra vai trò
        if (!selectedRole || !tenVaiTroMap[selectedRole.value]) {
            showMessage(
                "Vui lòng chọn vai trò đăng nhập.",
                "error"
            );

            hasError = true;
        }

        // Kiểm tra tên đăng nhập
        if (!username) {
            setFieldError(
                usernameInput,
                usernameError,
                "Vui lòng nhập tên đăng nhập."
            );

            hasError = true;
        } else if (!usernameRegex.test(username)) {
            setFieldError(
                usernameInput,
                usernameError,
                "Tên đăng nhập từ 3-30 ký tự, chỉ dùng chữ, số và dấu gạch dưới."
            );

            hasError = true;
        }

        // Kiểm tra mật khẩu
        if (!password.trim()) {
            setFieldError(
                passwordInput,
                passwordError,
                "Vui lòng nhập mật khẩu."
            );

            hasError = true;
        } else if (password.length < 6) {
            setFieldError(
                passwordInput,
                passwordError,
                "Mật khẩu phải có ít nhất 6 ký tự."
            );

            hasError = true;
        }

        // Nếu có lỗi thì không gửi biểu mẫu
        if (hasError) {
            event.preventDefault();
            return;
        }

        // Ghi nhớ tên đăng nhập nếu được chọn
        try {
            if (rememberInput && rememberInput.checked) {
                localStorage.setItem(
                    "rememberedUsername",
                    username
                );
            } else {
                localStorage.removeItem("rememberedUsername");
            }
        } catch (error) {
            // Không ảnh hưởng đến quá trình đăng nhập
        }

        // Cho phép form gửi username, password và role đến PHP.
        // PHP sẽ xác thực với MySQL và xử lý chuyển trang.
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = "ĐANG KIỂM TRA ĐĂNG NHẬP...";
        }
    });
}

// Khôi phục nút khi quay lại trang đăng nhập
window.addEventListener("pageshow", function () {
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = "ĐĂNG NHẬP VÀO HỆ THỐNG";
    }
});

// Xử lý quên mật khẩu
if (forgotLink) {
    forgotLink.addEventListener("click", function (event) {
        event.preventDefault();

        showMessage(
            "Vui lòng liên hệ Quản trị viên để được cấp lại mật khẩu.",
            "error"
        );
    });
}
