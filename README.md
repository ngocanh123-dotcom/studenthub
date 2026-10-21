# HỆ THỐNG QUẢN LÝ KHÓA HỌC VÀ ĐĂNG KÝ HỌC PHẦN (STUDENTHUB)

## 1. Thông tin chung
- Môn học: Lập trình web
- Chủ đề: Chủ đề 1
- Công nghệ: PHP 8.x, MySQL, PDO, MVC, Fetch API

## 2. Phân công thành viên
- Nhóm trưởng: Khung MVC, CSDL
- Thành viên 2: Module Sinh viên
- Thành viên 3: Module Giảng viên
- Thành viên 4: UI
- Thành viên 5: Fetch API & UI
Thành viên 4:
1. Thiết lập Bộ quy chuẩn thiết kế dùng chung (Design Tokens)
Xây dựng bảng màu chuẩn (Color Palette):

Màu chủ đạo (Primary): #2563eb (Xanh Royal Blue), màu hover #1d4ed8, màu nền phụ #eff6ff.

Màu nền không gian (Dark Slate): Gradient #0b0f19 kết hợp #0f172a và #1e3a8a tạo chiều sâu cho khu vực thương hiệu.

Màu phản hồi ngữ thái: Màu lỗi #ef4444 (đỏ), màu thành công #10b981 (xanh lá).

Quy chuẩn Font chữ (Typography):

Sử dụng bộ font hiện đại Plus Jakarta Sans kết hợp thang phân cấp tỷ lệ chặt chẽ: Tiêu đề H1/H2 (24–34px, Bold 800), Nhãn trường (13px, Semi-bold 600), Ô nhập liệu (14px, Regular 400), Chú thích và báo lỗi (12px, Medium 500).

Quy chuẩn kích thước & Lưới khoảng cách (Component Sizing & Spacing):

Đồng bộ chiều cao chuẩn cho ô nhập liệu (input) và nút bấm (button) ở mức 48px.

Thiết lập bo góc chuẩn: 8px cho nút/input, 14px cho thẻ vai trò/card, 24px cho các khối lớn.

Đóng gói toàn bộ thuộc tính thành biến CSS (:root) để toàn bộ nhóm tái sử dụng đồng bộ cho các trang sau.

2. Tái cấu trúc Bố cục hiển thị trên máy tính (Desktop Layout)
Chuyển đổi từ "Hộp nổi đơn độc" sang "Bố cục chia đôi màn hình" (Split-Screen Layout):

Cột trái (Brand & Showcase - 55%):

Tích hợp hiệu ứng ánh sáng mờ đa tầng (Aurora Radial Glow) giúp lấp đầy khoảng trống màn hình lớn.

Đặt khối nhận diện thương hiệu EDUPORTAL - Hệ Thống Đào Tạo Tín Chỉ.

Thêm huy hiệu trạng thái học kỳ có chấm sáng nhấp nháy (pulse-dot).

Tích hợp khối Feature Highlights (Tính năng nổi bật) tinh giản với icon nét đôi: Đăng ký tín chỉ trực tuyến và Thời khóa biểu & Tiến độ tích lũy.

Cột phải (Auth Interaction - 45%):

Không gian nền trắng tối giản, tập trung thị giác vào form đăng nhập, căn lề và khoảng đệm chuẩn mực.

Tối ưu hóa khả năng thích ứng (Responsive):

Tự động co gọn thành 1 cột mượt mà khi xem trên máy tính bảng hoặc màn hình điện thoại dưới 1024px.

3. Tái thiết kế các Thành phần tương tác (UI Components & Micro-interactions)
Bộ chọn vai trò dạng Bento Grid (Role Tiles):

Chuyển các radio button thông thường thành 3 thẻ khối ngang hàng (Sinh viên, Giảng viên, Quản trị).

Tích hợp khung icon riêng biệt, hiệu ứng nổi khối (translateY(-2px)) khi rê chuột, và viền xanh kèm bóng đổ khi được chọn (:checked).

Ô nhập dữ liệu (Input Fields):

Tích hợp biểu tượng chỉ dẫn (User, Lock) bên trong ô nhập.

Hiệu ứng viền chuyển sắc và tạo viền sáng (box-shadow glow) khi trỏ chuột vào ô (:focus).

Trạng thái cảnh báo lỗi: Ô nhập tự đổi viền đỏ và nền hồng nhạt khi dữ liệu không hợp lệ.

Nút ẩn/hiện mật khẩu:

Đặt gọn bên phải ô mật khẩu, chuyển đổi mượt mà giữa biểu tượng mắt mở và mắt đóng.

Nút gửi biểu mẫu (Submit Button):

Thiết kế dải màu Gradient chuyển sắc, hiệu ứng đổ bóng mềm, kèm trạng thái khóa nút và hiển thị "Đang xác thực hệ thống..." khi người dùng bấm gửi.

Hệ thống thông báo & Phản hồi (Feedback System):

Báo lỗi tức thì tại chân từng ô nhập (.field-error-msg).

Hộp thông báo kết quả chung (.status-banner) dạng banner nổi có hiệu ứng trượt nhẹ (slideInDown).

4. Đảm bảo & Giữ nguyên 100% Chức năng cốt lõi
Giữ nguyên toàn bộ logic đăng nhập, đối chiếu danh sách tài khoản demo (3 Admin, 1 Giảng viên, 1 Sinh viên) và lưu thông tin $_SESSION trong index.php.

Giữ nguyên cơ chế kiểm tra lỗi hai lớp: Kiểm tra tính hợp lệ bằng JavaScript trước khi gửi và kiểm tra bảo mật ở phía server PHP.

Duy trì tính năng ghi nhớ tên đăng nhập bằng localStorage khi người dùng tích chọn "Ghi nhớ phiên đăng nhập".

Loại bỏ toàn bộ các ký tự rác, chuỗi tham chiếu không mong muốn để giao diện hiển thị sạch sẽ, chuyên nghiệp.
