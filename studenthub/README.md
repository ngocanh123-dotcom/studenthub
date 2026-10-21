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


III. Thành viên 3 – Luồng điều hướng - Lê Khánh Ly
1. Nhiệm vụ được phân công
Thành viên 3 phụ trách xác định và xây dựng luồng điều hướng của hệ thống StudentHub sau khi người dùng đăng nhập. Nội dung tập trung vào việc xác định hướng điều hướng tương ứng với từng vai trò, bao gồm Sinh viên, Giảng viên và Quản trị viên, đồng thời xác định cách xử lý khi đăng nhập thành công, đăng nhập thất bại và đăng xuất khỏi hệ thống.
2. Mô tả luồng điều hướng
Sinh viên: Sau khi đăng nhập thành công và được hệ thống xác minh quyền truy cập, người dùng được điều hướng đến khu vực chức năng dành cho sinh viên.
Giảng viên: Sau khi đăng nhập thành công và được xác minh quyền, người dùng được điều hướng đến khu vực chức năng dành cho giảng viên.
Quản trị viên: Sau khi đăng nhập thành công và được xác minh quyền, người dùng được điều hướng đến khu vực quản trị hệ thống.
Đăng nhập thất bại: Hệ thống giữ người dùng tại trang đăng nhập và hiển thị thông báo khi tài khoản, mật khẩu không chính xác hoặc thông tin nhập vào chưa đầy đủ.
Đăng xuất: Hệ thống kết thúc phiên đăng nhập và đưa người dùng trở về trang đăng nhập.
Truy cập không hợp lệ: Hệ thống kiểm tra trạng thái đăng nhập và quyền của người dùng trước khi cho phép truy cập các khu vực chức năng, tránh việc truy cập trái phép.
3. Kết quả cần đạt
Xác định rõ luồng di chuyển giữa màn hình đăng nhập và các khu vực chức năng theo từng vai trò, tạo cơ sở cho việc triển khai lập trình và kiểm thử hệ thống. Việc điều hướng phải dựa trên vai trò đã được xác minh ở phía máy chủ, kết hợp với cơ chế quản lý phiên đăng nhập và kiểm tra quyền truy cập.
Ở giai đoạn demo hiện tại, thành viên 3 thực hiện xác định luồng điều hướng dự kiến theo ba vai trò. Việc chuyển đến các trang chức năng cụ thể sẽ được hoàn thiện khi các trang đích của hệ thống được xây dựng.
<img width="1448" height="1086" alt="image" src="https://github.com/user-attachments/assets/b0e7a6a6-c3da-4ae5-a3f9-87758fff6bae" />
IV. Thành viên 4 — Validation đăng nhập
- Xác định các trường bắt buộc trên form đăng nhập: Vai trò đăng nhập, Tên đăng nhập và Mật khẩu.
- Quy định thông báo lỗi chi tiết khi người dùng bỏ trống hoặc nhập sai định dạng:
  + Bỏ trống tên đăng nhập hoặc mật khẩu: Hiển thị thông báo *"Vui lòng nhập tên đăng nhập/mật khẩu"* và đánh viền đỏ trường dữ liệu.
  + Tên đăng nhập không đúng định dạng: Kiểm tra độ dài (3-30 ký tự) và ký tự hợp lệ.
  + Mật khẩu không đạt yêu cầu: Yêu cầu tối thiểu 6 ký tự.
- Xác định cơ chế kiểm tra chéo vai trò và tài khoản ở cả phía Client (JavaScript) và Server (PHP):
  + Đối chiếu tên đăng nhập và mật khẩu với cơ sở dữ liệu/danh sách demo (hỗ trợ Sinh viên, Giảng viên và 3 tài khoản Quản trị viên).
  + Kiểm tra tính khớp lệnh giữa vai trò người dùng chọn trên giao diện và phân quyền thực tế của tài khoản đó trong hệ thống.
- Lập bảng các trường hợp kiểm thử (Test Cases) cho chức năng đăng nhập:
  + TC01: Bỏ trống toàn bộ các trường bắt buộc.
  <img width="292" height="404" alt="image" src="https://github.com/user-attachments/assets/7df1f1c9-ae76-4596-995e-5343b54b3d1e" />

  + TC02: Nhập sai định dạng tên đăng nhập hoặc mật khẩu quá ngắn (<6 ký tự).
  <img width="285" height="410" alt="image" src="https://github.com/user-attachments/assets/d6703b0c-6615-4392-8e5c-d71502bbf4ab" />

  + TC03: Sai thông tin tài khoản hoặc mật khẩu.
<img width="284" height="401" alt="image" src="https://github.com/user-attachments/assets/af7f9e6c-81fc-4639-bab4-160ba43e2978" />

  + TC04: Chọn đúng tài khoản nhưng chọn sai vai trò đăng nhập.
<img width="287" height="390" alt="image" src="https://github.com/user-attachments/assets/3099c351-3a0b-4f1c-854b-a672a787ff41" />

  + TC05: Đăng nhập thành công với phân quyền hợp lệ (Sinh viên, Giảng viên, Quản trị viên).
<img width="284" height="383" alt="image" src="https://github.com/user-attachments/assets/9d5a32fe-f009-4082-84f1-215061aa300a" />



