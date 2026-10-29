-- =============================================================================
-- CSDL HỆ THỐNG QUẢN LÝ KHÓA HỌC VÀ ĐĂNG KÝ HỌC PHẦN (STUDENTHUB)
-- File: seed.sql (Dữ liệu mẫu chuẩn hóa đại học thực tế)
-- Phiên bản: 2.0 - Phục vụ kiểm thử toàn diện 3 vai trò (Sinh viên, Giảng viên, Admin)
-- Mật khẩu mặc định đồng nhất cho TẤT CẢ tài khoản: 123456 (hoặc password)
-- Hash Bcrypt: $2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `dang_ky_hoc`;
TRUNCATE TABLE `lop_hoc_phan`;
TRUNCATE TABLE `hoc_phan`;
TRUNCATE TABLE `hoc_ky`;
TRUNCATE TABLE `nguoi_dung`;

-- =============================================================================
-- 1. BẢNG NGƯỜI DÙNG (nguoi_dung) - 18 TÀI KHOẢN ĐẦY ĐỦ 3 PHÂN HỆ
-- =============================================================================
INSERT INTO `nguoi_dung` (`id`, `ten_dang_nhap`, `mat_khau_hash`, `ho_ten`, `email`, `so_dien_thoai`, `vai_tro`, `ngay_tao`) VALUES
-- 1.1. QUẢN TRỊ VIÊN HỆ THỐNG (Admin)
(1, 'admin', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'Quản trị viên Hệ thống', 'admin@studenthub.edu.vn', '0901234567', 'admin', '2026-08-01 08:00:00'),

-- 1.2. GIẢNG VIÊN (5 Thầy/Cô đa dạng học vị & bộ môn)
(2, 'GV001', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'ThS Trần Bình Trọng', 'trong.tb@studenthub.edu.vn', '0912345678', 'giang_vien', '2026-08-01 08:00:00'),
(3, 'GV002', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'TS Lê Phương Hoa', 'hoa.lp@studenthub.edu.vn', '0913456789', 'giang_vien', '2026-08-01 08:00:00'),
(4, 'GV003', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'PGS.TS Nguyễn Văn An', 'an.nv@studenthub.edu.vn', '0914567890', 'giang_vien', '2026-08-01 08:00:00'),
(5, 'GV004', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'ThS Hoàng Bích Ngọc', 'ngoc.hb@studenthub.edu.vn', '0915678901', 'giang_vien', '2026-08-01 08:00:00'),
(6, 'GV005', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'TS Vũ Đình Đạt', 'dat.vd@studenthub.edu.vn', '0916789012', 'giang_vien', '2026-08-01 08:00:00'),

-- 1.3. SINH VIÊN (12 Sinh viên đại diện các khóa K23, K24, K25)
(7,  'SV001', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'Nguyễn Thu Trang', 'trang.nt@studenthub.edu.vn', '0934567890', 'sinh_vien', '2026-08-15 08:00:00'),
(8,  'SV002', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'Vũ Quốc Bảo', 'bao.vq@studenthub.edu.vn', '0945678901', 'sinh_vien', '2026-08-15 08:00:00'),
(9,  'SV003', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'Lê Hoàng Nam', 'nam.lh@studenthub.edu.vn', '0956789012', 'sinh_vien', '2026-08-15 08:00:00'),
(10, 'SV004', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'Phạm Mai Phương', 'phuong.pm@studenthub.edu.vn', '0967890123', 'sinh_vien', '2026-08-15 08:00:00'),
(11, 'SV005', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'Đỗ Minh Quân', 'quan.dm@studenthub.edu.vn', '0978901234', 'sinh_vien', '2026-08-15 08:00:00'),
(12, 'SV006', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'Trần Thị Mai', 'mai.tt@studenthub.edu.vn', '0989012345', 'sinh_vien', '2026-08-15 08:00:00'),
(13, 'SV007', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'Hoàng Đức Anh', 'anh.hd@studenthub.edu.vn', '0911223344', 'sinh_vien', '2026-08-15 08:00:00'),
(14, 'SV008', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'Bùi Khánh Linh', 'linh.bk@studenthub.edu.vn', '0922334455', 'sinh_vien', '2026-08-15 08:00:00'),
(15, 'SV009', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'Dương Tuấn Kiệt', 'kiet.dt@studenthub.edu.vn', '0933445566', 'sinh_vien', '2026-08-15 08:00:00'),
(16, 'SV010', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'Ngô Phương Thảo', 'thao.np@studenthub.edu.vn', '0944556677', 'sinh_vien', '2026-08-15 08:00:00'),
(17, 'SV011', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'Đinh Trọng Hiếu', 'hieu.dt@studenthub.edu.vn', '0955667788', 'sinh_vien', '2026-08-15 08:00:00'),
(18, 'SV012', '$2y$10$Knpw6PuRu8HeFmmuvzjzUOmK4IMoR03PcaOxqZ53TnuMRR0BxF2ri', 'Lê Thanh Hằng', 'hang.lt@studenthub.edu.vn', '0966778899', 'sinh_vien', '2026-08-15 08:00:00');

-- =============================================================================
-- 2. BẢNG HỌC KỲ (hoc_ky) - KHUNG THỜI GIAN ĐÀO TẠO & CỔNG ĐĂNG KÝ
-- =============================================================================
INSERT INTO `hoc_ky` (`id`, `ten_hoc_ky`, `ngay_bat_dau`, `ngay_ket_thuc`, `thoi_gian_mo_dk`, `thoi_gian_dong_dk`, `trang_thai_dang_ky`) VALUES
(1, 'Học kỳ 1 (2026 - 2027)', '2026-09-01', '2027-01-15', '2026-08-15 08:00:00', '2026-12-30 23:59:59', 1),
(2, 'Học kỳ 2 (2026 - 2027)', '2027-02-01', '2027-06-30', '2027-01-10 08:00:00', '2027-02-10 23:59:59', 0);

-- =============================================================================
-- 3. BẢNG HỌC PHẦN (hoc_phan) - 10 HỌC PHẦN CHUẨN ĐẠI HỌC CÓ TIÊN QUYẾT
-- =============================================================================
INSERT INTO `hoc_phan` (`id`, `ma_hoc_phan`, `ten_hoc_phan`, `so_tin_chi`, `loai_hoc_phan`, `hoc_phan_tien_quyet_id`, `mo_ta`, `dieu_kien_tien_quyet`) VALUES
(1, 'MAT101', 'Toán Cao Cấp', 3, 'dai_cuong', NULL, 
 'Trang bị kiến thức nền tảng về phép tính vi tích phân, chuỗi số, ma trận định thức và hệ phương trình tuyến tính.', 
 'Không có'),

(2, 'MAT205', 'Giải Tích Số', 3, 'co_so_nganh', 1, 
 'Nghiên cứu các phương pháp giải gần đúng bài toán toán học: giải phương trình phi tuyến, hệ đại số tuyến tính, nội suy và xấp xỉ hàm số.', 
 'Toán Cao Cấp (MAT101)'),

(3, 'WEB102', 'Lập Trình Web Cơ Bản', 3, 'co_so_nganh', NULL, 
 'Nhập môn thiết kế giao diện web với HTML5, CSS3, JavaScript tương tác, kiến trúc phân tán client-server và giao thức HTTP cơ bản.', 
 'Không có'),

(4, 'WEB201', 'Phát Triển Web Nâng Cao', 3, 'chuyen_nganh', 3, 
 'Xây dựng ứng dụng web hiện đại theo mô hình MVC sử dụng PHP 8.x, MySQL, PDO Prepared Statements, RESTful API, tối ưu bảo mật CSRF/XSS.', 
 'Lập Trình Web Cơ Bản (WEB102)'),

(5, 'DB101', 'Cơ Sở Dữ Liệu Quan Hệ', 3, 'co_so_nganh', NULL, 
 'Nắm vững mô hình quan hệ ERD, đại số quan hệ, ngôn ngữ truy vấn SQL từ cơ bản đến nâng cao, chuẩn hóa dữ liệu 1NF đến BCNF.', 
 'Không có'),

(6, 'DSA102', 'Cấu Trúc Dữ Liệu & Giải Thuật', 4, 'co_so_nganh', NULL, 
 'Nghiên cứu các cấu trúc dữ liệu kinh điển: danh sách liên kết, ngăn xếp, hàng đợi, cây nhị phân, đồ thị và các giải thuật tìm kiếm, sắp xếp nâng cao.', 
 'Không có'),

(7, 'NET101', 'Mạng Máy Tính Căn Bản', 3, 'co_so_nganh', NULL, 
 'Khái niệm mô hình OSI 7 tầng, bộ giao thức TCP/IP, định tuyến IP, phân mạng con subnet, cấu hình chuyển mạch Switch và Router.', 
 'Không có'),

(8, 'SE201', 'Công Nghệ Phần Mềm', 3, 'chuyen_nganh', 6, 
 'Quy trình phát triển phần mềm Agile/Scrum, phân tích yêu cầu SRS, mô hình hóa UML, thiết kế kiến trúc hệ thống và kiểm thử phần mềm.', 
 'Cấu Trúc Dữ Liệu & Giải Thuật (DSA102)'),

(9, 'AI301', 'Trí Tuệ Nhân Tạo & Học Máy', 3, 'chuyen_nganh', 6, 
 'Phương pháp tìm kiếm không gian trạng thái, giải bài toán tối ưu, cây quyết định, mạng nơ-ron nhân tạo và ứng dụng xử lý dữ liệu học máy.', 
 'Cấu Trúc Dữ Liệu & Giải Thuật (DSA102)'),

(10, 'SEC201', 'An Toàn & Bảo Mật Thông Tin', 3, 'chuyen_nganh', 7, 
 'Các kỹ thuật tấn công và phòng thủ mạng, mật mã học đối xứng và bất đối xứng, chứng thực số, chữ ký điện tử và tường lửa bảo mật.', 
 'Mạng Máy Tính Căn Bản (NET101)');

-- =============================================================================
-- 4. BẢNG LỚP HỌC PHẦN (lop_hoc_phan) - 14 LỚP PHỤC VỤ TRỌN VẸN CÁC TEST CASES
-- =============================================================================
-- Test Case Đáp ứng:
--   - Lớp bình thường còn chỗ: LHP-WEB01, LHP-DB02, LHP-DSA02, LHP-MAT01, LHP-NET01, LHP-SE01, LHP-W102, LHP-M205, LHP-SE02
--   - Lớp ĐẦY SĨ SỐ (100% capacity - Test Case G.3): LHP-DB01 (5/5), LHP-DSA01 (6/6)
--   - Lớp ĐÃ BỊ KHÓA ĐĂNG KÝ (trang_thai = 'khoa' - Kịch bản Mục H): LHP-AI01, LHP-SEC01
--   - Lớp CÓ THỜI GIAN MỞ RIÊNG: LHP-WEB02
INSERT INTO `lop_hoc_phan` (`id`, `ma_lop`, `hoc_phan_id`, `hoc_ky_id`, `giang_vien_id`, `si_so_toi_da`, `trang_thai`, `thoi_gian_mo_rieng`, `thoi_gian_dong_rieng`, `lich_hoc`, `phong_hoc`) VALUES
-- 1. Lớp bình thường còn chỗ
(1,  'LHP-WEB01', 4, 1, 2, 40, 'mo', NULL, NULL, 'Thứ 2 & Thứ 4 (Tiết 1 - 3, Sáng)', 'Lab 402 - Nhà C'),

-- 2. Lớp có thời gian mở riêng
(2,  'LHP-WEB02', 4, 1, 2, 35, 'mo', '2026-10-01 08:00:00', '2026-11-30 23:59:59', 'Thứ 3 & Thứ 5 (Tiết 7 - 9, Chiều)', 'Lab 403 - Nhà C'),

-- 3. Lớp ĐẦY SĨ SỐ (Full 5/5) - Kiểm thử chặn đăng ký
(3,  'LHP-DB01',  5, 1, 3, 5,  'mo', NULL, NULL, 'Thứ 3 & Thứ 5 (Tiết 1 - 3, Sáng)', 'Phòng 301 - Tòa A'),

-- 4. Lớp bình thường còn chỗ
(4,  'LHP-DB02',  5, 1, 3, 45, 'mo', NULL, NULL, 'Thứ 4 & Thứ 6 (Tiết 7 - 9, Chiều)', 'Phòng 302 - Tòa A'),

-- 5. Lớp ĐẦY SĨ SỐ (Full 6/6) - Kiểm thử chặn đăng ký
(5,  'LHP-DSA01', 6, 1, 4, 6,  'mo', NULL, NULL, 'Thứ 2 & Thứ 6 (Tiết 4 - 6, Sáng)', 'Hội trường B2'),

-- 6. Lớp bình thường còn chỗ
(6,  'LHP-DSA02', 6, 1, 4, 50, 'mo', NULL, NULL, 'Thứ 3 & Thứ 5 (Tiết 10 - 12, Chiều)', 'Hội trường B3'),

-- 7. Lớp bình thường còn chỗ
(7,  'LHP-MAT01', 1, 1, 5, 60, 'mo', NULL, NULL, 'Thứ 4 & Thứ 6 (Tiết 1 - 3, Sáng)', 'Phòng 201 - Tòa B'),

-- 8. Lớp bình thường còn chỗ
(8,  'LHP-NET01', 7, 1, 6, 40, 'mo', NULL, NULL, 'Thứ 7 (Tiết 1 - 5, Sáng)', 'Lab Mạng - Tòa CNTT'),

-- 9. Lớp bình thường còn chỗ
(9,  'LHP-SE01',  8, 1, 2, 40, 'mo', NULL, NULL, 'Thứ 2 & Thứ 4 (Tiết 7 - 9, Chiều)', 'Phòng 405 - Tòa C'),

-- 10. Lớp BỊ KHÓA ĐĂNG KÝ BỞI ADMIN (Mục H Demo)
(10, 'LHP-AI01',  9, 1, 4, 35, 'khoa', NULL, NULL, 'Thứ 5 & Thứ 7 (Tiết 7 - 9, Chiều)', 'Lab AI - Tòa CNTT'),

-- 11. Lớp BỊ KHÓA ĐĂNG KÝ BỞI ADMIN (Mục H Demo)
(11, 'LHP-SEC01', 10, 1, 6, 30, 'khoa', NULL, NULL, 'Thứ 3 & Thứ 5 (Tiết 4 - 6, Sáng)', 'Phòng 502 - Tòa A'),

-- 12. Lớp nhập môn Web
(12, 'LHP-W102',  3, 1, 3, 45, 'mo', NULL, NULL, 'Thứ 2 & Thứ 6 (Tiết 7 - 9, Chiều)', 'Lab 301 - Nhà C'),

-- 13. Lớp thuộc Học kỳ 2
(13, 'LHP-M205',  2, 2, 5, 50, 'mo', NULL, NULL, 'Thứ 3 & Thứ 5 (Tiết 1 - 3, Sáng)', 'Phòng 204 - Tòa B'),

-- 14. Lớp thuộc Học kỳ 2
(14, 'LHP-SE02',  8, 2, 2, 40, 'mo', NULL, NULL, 'Thứ 4 & Thứ 6 (Tiết 7 - 9, Chiều)', 'Phòng 406 - Tòa C');

-- =============================================================================
-- 5. BẢNG ĐĂNG KÝ HỌC (dang_ky_hoc) - 32 BẢN GHI PHÂN BỔ THỰC TẾ
-- =============================================================================
-- Ghi nhận lịch sử ghi danh phong phú cho cả 12 Sinh viên và 5 Giảng viên:
--   - GV001 (id 2): Phụ trách Lớp 1 (6 SV), Lớp 9 (4 SV) -> Tổng 10 sinh viên
--   - GV002 (id 3): Phụ trách Lớp 3 (5 SV), Lớp 12 (3 SV) -> Tổng 8 sinh viên
--   - GV003 (id 4): Phụ trách Lớp 5 (6 SV) -> Tổng 6 sinh viên
--   - GV004 (id 5): Phụ trách Lớp 7 (4 SV) -> Tổng 4 sinh viên
--   - GV005 (id 6): Phụ trách Lớp 8 (5 SV) -> Tổng 5 sinh viên
--   - Các bản ghi 'da_huy' phục vụ hiển thị đối soát tại trang Lịch sử đăng ký
INSERT INTO `dang_ky_hoc` (`id`, `sinh_vien_id`, `lop_hoc_phan_id`, `trang_thai`, `ngay_dang_ky`, `ngay_huy`) VALUES
-- --- LỚP 1: LHP-WEB01 (6 Sinh viên ghi danh hợp lệ) ---
(1,  7,  1, 'dang_ky', '2026-10-15 08:30:15', NULL),
(2,  8,  1, 'dang_ky', '2026-10-15 08:32:00', NULL),
(3,  9,  1, 'dang_ky', '2026-10-15 08:35:40', NULL),
(4,  10, 1, 'dang_ky', '2026-10-15 08:40:12', NULL),
(5,  11, 1, 'dang_ky', '2026-10-15 08:45:00', NULL),
(6,  12, 1, 'dang_ky', '2026-10-15 09:00:22', NULL),

-- --- LỚP 3: LHP-DB01 (ĐỦ 5/5 SINH VIÊN - 100% SĨ SỐ, CHẶN ĐĂNG KÝ) ---
(7,  7,  3, 'dang_ky', '2026-10-15 08:31:00', NULL),
(8,  8,  3, 'dang_ky', '2026-10-15 08:33:10', NULL),
(9,  13, 3, 'dang_ky', '2026-10-15 08:36:55', NULL),
(10, 14, 3, 'dang_ky', '2026-10-15 08:42:18', NULL),
(11, 15, 3, 'dang_ky', '2026-10-15 08:50:30', NULL),

-- --- LỚP 5: LHP-DSA01 (ĐỦ 6/6 SINH VIÊN - 100% SĨ SỐ, CHẶN ĐĂNG KÝ) ---
(12, 9,  5, 'dang_ky', '2026-10-15 08:37:12', NULL),
(13, 10, 5, 'dang_ky', '2026-10-15 08:41:45', NULL),
(14, 11, 5, 'dang_ky', '2026-10-15 08:46:00', NULL),
(15, 12, 5, 'dang_ky', '2026-10-15 09:02:10', NULL),
(16, 16, 5, 'dang_ky', '2026-10-15 09:15:30', NULL),
(17, 17, 5, 'dang_ky', '2026-10-15 09:20:00', NULL),

-- --- LỚP 7: LHP-MAT01 (4 Sinh viên) ---
(18, 13, 7, 'dang_ky', '2026-10-15 08:38:00', NULL),
(19, 14, 7, 'dang_ky', '2026-10-15 08:43:20', NULL),
(20, 15, 7, 'dang_ky', '2026-10-15 08:51:10', NULL),
(21, 16, 7, 'dang_ky', '2026-10-15 09:16:45', NULL),

-- --- LỚP 8: LHP-NET01 (5 Sinh viên) ---
(22, 7,  8, 'dang_ky', '2026-10-15 08:34:00', NULL),
(23, 10, 8, 'dang_ky', '2026-10-15 08:42:00', NULL),
(24, 12, 8, 'dang_ky', '2026-10-15 09:03:50', NULL),
(25, 17, 8, 'dang_ky', '2026-10-15 09:22:15', NULL),
(26, 18, 8, 'dang_ky', '2026-10-15 09:30:00', NULL),

-- --- LỚP 9: LHP-SE01 (4 Sinh viên) ---
(27, 8,  9, 'dang_ky', '2026-10-15 08:35:00', NULL),
(28, 9,  9, 'dang_ky', '2026-10-15 08:39:10', NULL),
(29, 11, 9, 'dang_ky', '2026-10-15 08:48:30', NULL),
(30, 18, 9, 'dang_ky', '2026-10-15 09:32:40', NULL),

-- --- LỚP 12: LHP-W102 (3 Sinh viên) ---
(31, 13, 12, 'dang_ky', '2026-10-15 08:40:00', NULL),
(32, 14, 12, 'dang_ky', '2026-10-15 08:44:50', NULL),
(33, 17, 12, 'dang_ky', '2026-10-15 09:25:00', NULL),

-- --- CÁC BẢN GHI ĐÃ HỦY (da_huy) PHỤC VỤ TRANG LỊCH SỬ ĐĂNG KÝ CỦA SINH VIÊN ---
(34, 7,  4,  'da_huy', '2026-10-10 09:10:00', '2026-10-12 14:30:15'),
(35, 8,  7,  'da_huy', '2026-10-08 08:15:00', '2026-10-09 10:20:00'),
(36, 9,  8,  'da_huy', '2026-10-11 10:00:00', '2026-10-13 16:45:00'),
(37, 11, 12, 'da_huy', '2026-10-09 14:00:00', '2026-10-11 11:15:30');

SET FOREIGN_KEY_CHECKS = 1;
