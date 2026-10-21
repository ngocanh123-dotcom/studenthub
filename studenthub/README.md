# HỆ THỐNG QUẢN LÝ KHÓA HỌC VÀ ĐĂNG KÝ HỌC PHẦN (STUDENTHUB)

## 1. Thông tin chung
- Môn học: Lập trình web
- Chủ đề: Chủ đề 1
- Công nghệ: PHP 8.x, MySQL, PDO, MVC, Fetch API

## 2. Thành viên
- Nhóm trưởng: Phạm Ngọc Anh
- Thành viên 2: Nguyễn Thị Kim Anh 
- Thành viên 3: Tạ Như Quỳnh
- Thành viên 4: Nguyễn Thị Thu Huyền
- Thành viên 5: Lê Khánh Ly

## 3. Phân công nhiệm vụ 
I. Nhóm trưởng: Thành viên 1 - Phạm Ngọc Anh — Form đăng nhập
- Hoàn thiện wireframe đăng nhập.
- Bố trí 3 vai trò: Sinh viên, Giảng viên, Quản trị viên.
- Mô tả các trường tài khoản, mật khẩu và nút đăng nhập.
- Xác định luồng đăng nhập thành công và thất bại.
- <img width="786" height="591" alt="image" src="https://github.com/user-attachments/assets/e808a6b3-5498-43bd-990e-3aac18eb47a2" />
1. Mô tả các thành phần
- Sinh viên: 	Chọn vai trò sinh viên để đăng nhập
- Giảng viên:	Chọn vai trò giảng viên
- Quản trị viên:	Chọn vai trò quản trị hệ thống
- Tên đăng nhập:	Nhập tài khoản được cấp
- Mật khẩu: Nhập mật khẩu; có thể hiện hoặc ẩn
- Ghi nhớ tên đăng nhập:	Cho phép ghi nhớ tên đăng nhập trên thiết bị nếu triển khai chức năng này
- Quên mật khẩu:	Hướng dẫn khôi phục tài khoản
- Đăng nhập:	Gửi thông tin để hệ thống kiểm tra
2. Luồng đăng nhập thành công và thất bại
  <img width="1089" height="537" alt="image" src="https://github.com/user-attachments/assets/e15c0b9e-98f2-445a-822d-343173651e35" />
- Khi đăng nhập thành công, hệ thống phải xác minh vai trò và quyền của tài khoản ở phía máy chủ, không chỉ dựa vào vai trò người dùng tự chọn trên giao diện.

Thành viên 1 phụ trách thiết kế wireframe cho màn hình đăng nhập của hệ thống StudentHub. Giao diện gồm ba vai trò: Sinh viên, Giảng viên và Quản trị viên; trường nhập tên đăng nhập, mật khẩu và nút Đăng nhập. Ngoài ra, giao diện có thể bổ sung chức năng hiện/ẩn mật khẩu, ghi nhớ tên đăng nhập và hỗ trợ khi quên mật khẩu.
Luồng đăng nhập được chia thành hai trường hợp. Khi thông tin hợp lệ, hệ thống xác thực tài khoản, tạo phiên đăng nhập và điều hướng người dùng đến trang chức năng phù hợp với vai trò đã được xác minh. Khi thông tin không hợp lệ hoặc còn thiếu, hệ thống hiển thị thông báo lỗi và cho phép người dùng nhập lại.
Kết quả cần đạt là giao diện rõ ràng, dễ sử dụng, thống nhất về bố cục và xác định được các luồng xử lý thành công, thất bại để làm cơ sở cho việc lập trình và kiểm thử.



