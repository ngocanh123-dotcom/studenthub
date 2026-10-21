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
Thành viên 3 phụ trách xác định luồng điều hướng của hệ thống StudentHub, bao gồm luồng xử lý sau khi đăng nhập thành công, đăng nhập thất bại và hướng điều hướng dự kiến theo từng vai trò người dùng. Ba vai trò được xác định trong hệ thống gồm Sinh viên, Giảng viên và Quản trị viên.
Nhiệm vụ này nhằm làm rõ cách hệ thống xử lý thông tin đăng nhập, xác minh vai trò và xác định khu vực chức năng tương ứng, làm cơ sở cho việc xây dựng và hoàn thiện các chức năng của hệ thống trong những giai đoạn tiếp theo.
2. Mô tả luồng điều hướng
Luồng điều hướng của hệ thống StudentHub được mô tả qua các bước sau:
Thứ nhất, tiếp nhận thông tin đăng nhập. Người dùng truy cập trang đăng nhập, lựa chọn vai trò và nhập tên đăng nhập, mật khẩu. Sau đó, hệ thống tiếp nhận thông tin để thực hiện kiểm tra.
Thứ hai, kiểm tra thông tin đăng nhập. Hệ thống kiểm tra tính hợp lệ của tên đăng nhập và mật khẩu. Nếu thông tin không chính xác hoặc còn thiếu, hệ thống hiển thị thông báo lỗi và cho phép người dùng nhập lại.
Thứ ba, xác minh vai trò và xác định hướng điều hướng. Khi thông tin đăng nhập hợp lệ, hệ thống cần xác minh vai trò và quyền truy cập ở phía máy chủ. Dựa trên vai trò đã được xác minh, hệ thống xác định hướng điều hướng tương ứng với Sinh viên, Giảng viên hoặc Quản trị viên.
Thứ tư, quản lý phiên đăng nhập và quyền truy cập. Hệ thống cần duy trì trạng thái đăng nhập trong phiên làm việc, kiểm tra quyền trước khi cho phép người dùng truy cập các khu vực chức năng và kết thúc phiên khi người dùng đăng xuất.
3. Sơ đồ luồng điều hướng
<img width="1448" height="1086" alt="image" src="https://github.com/user-attachments/assets/38e4162d-47e9-4966-93c3-41e60d28b3b0" />

Hình 1. Sơ đồ luồng điều hướng dự kiến của hệ thống StudentHub.
Sơ đồ thể hiện quá trình xử lý từ trang đăng nhập, kiểm tra thông tin, xác minh vai trò đến hướng điều hướng tương ứng với từng nhóm người dùng. Nếu đăng nhập thất bại, hệ thống hiển thị thông báo lỗi và quay lại trang đăng nhập. Ngoài ra, sơ đồ thể hiện yêu cầu kiểm tra quyền truy cập và đăng xuất nhằm bảo đảm quá trình sử dụng hệ thống được thực hiện theo đúng quyền hạn.
4. Kết quả thực hiện
Ở giai đoạn hiện tại, thành viên 3 đã xác định luồng điều hướng dự kiến cho ba vai trò người dùng và bổ sung phần xác định đường dẫn tương ứng theo vai trò trong mã nguồn PHP của trang đăng nhập. Phần demo hiện có thể kiểm tra thông tin đăng nhập, hiển thị thông báo thành công hoặc thất bại và lưu thông tin phiên đăng nhập bằng Session.
Do các trang chức năng dành cho Sinh viên, Giảng viên và Quản trị viên chưa được xây dựng, việc chuyển hướng đến các trang đích chưa được triển khai hoàn chỉnh. Chức năng kiểm tra quyền truy cập và đăng xuất cũng cần được hoàn thiện trong giai đoạn phát triển tiếp theo.

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



