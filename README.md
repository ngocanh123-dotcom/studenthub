# HỆ THỐNG QUẢN LÝ KHÓA HỌC VÀ ĐĂNG KÝ HỌC PHẦN (STUDENTHUB)

## 4. Báo cáo chi tiết phần việc — Thành viên 4: Quy chuẩn giao diện (UI/UX)

### 4.1. Hình ảnh thực tế giao diện hệ thống
> 

![Giao diện Đăng nhập Hệ thống - Eduportal](image8.png)
*Hình 3.1: Giao diện Cổng đăng ký học phần & Quản trị học vụ (Bố cục Split-Screen trên Desktop)*

---

### 4.2. Hệ thống quy chuẩn thiết kế dùng chung (Design Tokens)
Toàn bộ mã nguồn giao diện được đóng gói thành các biến CSS (`:root`) nhằm thiết lập khung quy chuẩn nhất quán cho toàn bộ các màn hình chức năng của dự án:

* **Bảng màu chuẩn (Color Hierarchy — Tỷ lệ 60 - 30 - 10):**
  * *Màu chủ đạo (Primary - 30%):* `#2563eb` (Royal Blue), màu tương tác hover `#1d4ed8`, màu nền phụ `#eff6ff` kèm hiệu ứng đổ bóng mờ `rgba(37, 99, 235, 0.25)`.
  * *Màu nền thương hiệu (Dark Slate - 60%):* Gradient đa tầng từ `#0b0f19` qua `#0f172a` đến `#1e3a8a` tạo chiều sâu thị giác phân lớp (*Visual Depth*).
  * *Màu ngữ thái phản hồi (Semantic - 10%):* Báo lỗi `#ef4444` (nền `#fef2f2`, viền `#fecaca`), Báo thành công `#10b981` (nền `#ecfdf5`, viền `#a7f3d0`).
* **Hệ thống Font chữ (Typography System):**
  * Sử dụng bộ font hiện đại `Plus Jakarta Sans` với thang đo cấp bậc nghiêm ngặt:
    * Tiêu đề chính (`H1` / `H2`): **24px – 34px** (`font-weight: 800`, `letter-spacing: -0.02em`).
    * Nhãn trường (`Label`): **13px** (`font-weight: 600`, màu `Slate 700`).
    * Ô nhập liệu (`Input Text`): **14px** (`font-weight: 400`, `line-height: 1.5`).
    * Thông báo lỗi inline (`Error Helper`): **12px** (`font-weight: 500`).
* **Quy chuẩn kích thước & Lưới khoảng cách (Component Grid & Radius):**
  * Chiều cao cố định chuẩn cho toàn bộ Input và Button: **48px** (tối ưu tỷ lệ nhấp chuột trên máy tính và chạm trên thiết bị cảm ứng).
  * Bo góc hệ thống: `8px` cho nút bấm/ô nhập liệu, `14px` cho thẻ vai trò/card module, `24px` cho khối bố cục lớn.

---

### 4.3. Tái cấu trúc kiến trúc bố cục (Desktop & Responsive Architecture)
* **Khắc phục triệt để hiện tượng "hộp nổi trôi dạt" (Floating Box):** Chuyển dịch từ form đơn trơ trọi giữa màn hình sang mô hình **Bố cục chia đôi màn hình (Split-Screen Layout)** chuyên nghiệp:
  * **Cột trái (Brand & Showcase — 55% Viewport):**
    * Áp dụng hiệu ứng ánh sáng cực quang mờ đa tầng (*Aurora Radial Glow*) với họa tiết lưới điểm tinh tế.
    * Tích hợp khối nhận diện thương hiệu `EDUPORTAL - Hệ Thống Đào Tạo Tín Chỉ` kèm huy hiệu học kỳ hiển thị trạng thái hoạt động trực tiếp (`pulse-dot animation`).
    * Khối **Feature Highlights** giới thiệu hai tính năng cốt lõi (Đăng ký tín chỉ trực tuyến & Thời khóa biểu tích lũy) sử dụng icon nét đôi tối giản.
  * **Cột phải (Auth Interaction — 45% Viewport):**
    * Không gian tối giản hóa (*Clean Minimalist Surface*), căn lề và độ đệm chuẩn mực, hướng sự chú ý tối đa của người dùng vào form tương tác xác thực.
* **Tối ưu hóa đa thiết bị (Responsive Breakpoints):**
  * Tự động tái sắp xếp linh hoạt từ 2 cột thành 1 cột duy nhất mượt mà trên Tablet và Mobile (dưới `1024px`), ẩn bớt các khối phụ để ưu tiên trải nghiệm đăng nhập nhanh.

---

### 4.4. Thiết kế thành phần tương tác & Phản hồi vi mô (Micro-interactions)
* **Bộ chọn vai trò dạng Bento Grid (Role Tiles):**
  * Thay thế các nút radio truyền thống bằng cụm 3 thẻ trực quan ngang hàng (Sinh viên, Giảng viên, Quản trị).
  * Icon được bọc trong khung nổi riêng biệt; hỗ trợ hiệu ứng nổi khối (`translateY(-2px)`) khi hover và hiển thị viền đôi kèm bóng đổ xanh khi kích hoạt (`:checked`).
* **Ô nhập dữ liệu thông minh (Input Field States):**
  * Tích hợp icon trực quan nội vùng (User, Lock).
  * Trạng thái kích hoạt (`:focus`) tạo viền sáng chuyển màu và bóng đổ rộng `4px` (`var(--primary-glow)`).
  * Trạng thái cảnh báo lỗi: Tự động đổi viền đỏ và nền hồng nhạt tức thì khi nhập sai định dạng.
* **Kiểm soát mật khẩu trực quan:**
  * Tích hợp nút chuyển đổi SVG mắt mở/nhắm đồng bộ thời gian thực, tăng tính tiện dụng khi nhập mật khẩu dài.
* **Nút bấm xác thực (Submit Action):**
  * Phủ dải chuyển màu Gradient xanh chiều sâu, kèm trạng thái khóa thao tác (`disabled`) và chuyển đổi nhãn *"Đang xác thực hệ thống..."* chống lặp request.
* **Hệ thống phản hồi lỗi hai cấp (Feedback System):**
  * Lỗi chi tiết hiển thị trực tiếp dưới từng trường (`.field-error-msg`).
  * Thông báo trạng thái toàn cục (`.status-banner`) dạng banner nổi với hiệu ứng trượt nhẹ (`slideInDown`).

---

### 4.5. Bảo toàn tính toàn vẹn chức năng & Bảo mật
* **Phân quyền và phiên làm việc:** Giữ nguyên 100% logic đối chiếu tài khoản demo (3 Quản trị viên, 1 Giảng viên, 1 Sinh viên) và lưu trữ thông tin phiên `$_SESSION` trong PHP.
* **Cơ chế xác thực hai tầng (Two-tier Validation):**
  * *Tầng Client (JavaScript):* Xử lý Regex định dạng tài khoản, kiểm tra độ dài mật khẩu và phản hồi lỗi tức thì không cần tải lại trang.
  * *Tầng Server (PHP):* Xử lý kiểm tra dữ liệu nghiêm ngặt, bọc lọc dữ liệu đầu ra bằng `htmlspecialchars` ngăn chặn triệt để lỗ hổng XSS.
* **Ghi nhớ đăng nhập an toàn:** Tự động lưu trữ và phục hồi tên tài khoản thông qua `localStorage` khi người dùng tích chọn "Ghi nhớ phiên đăng nhập".
* **Làm sạch mã nguồn:** Loại bỏ hoàn toàn các chuỗi ký tự rác, đảm bảo cây DOM và file CSS đạt chuẩn W3C sạch sẽ, dễ bảo trì.