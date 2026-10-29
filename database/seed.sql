-- ==========================================
-- DỮ LIỆU MẪU BỔ SUNG (Do Kim Anh thêm)
-- ==========================================

-- 1. Thêm 2 học kỳ mới
INSERT INTO hoc_ky (ten_hoc_ky, ngay_bat_dau, ngay_ket_thuc, thoi_gian_mo_dk, thoi_gian_dong_dk, trang_thai_dang_ky) VALUES 
('Học kỳ Hè năm 2027', '2027-06-01', '2027-08-15', '2027-05-01 08:00:00', '2027-05-30 23:59:59', 0),
('Học kỳ 1 năm 2027-2028', '2027-09-01', '2028-01-15', '2027-08-15 08:00:00', '2027-11-30 23:59:59', 1);

-- 2. Thêm các lớp học phần cho Học kỳ 2 (Giả sử ID Học kỳ 2 là 2)
INSERT INTO lop_hoc_phan (ma_lop, hoc_phan_id, hoc_ky_id, giang_vien_id, si_so_toi_da, trang_thai) VALUES
('MAT101-02', 1, 2, 2, 40, 'mo'),
('WEB102-02', 3, 2, 2, 45, 'mo'),
('MAT205-02', 2, 2, 3, 35, 'mo');

-- 3. Thêm các lớp học phần cho Học kỳ Hè (Giả sử ID là 3)
INSERT INTO lop_hoc_phan (ma_lop, hoc_phan_id, hoc_ky_id, giang_vien_id, si_so_toi_da, trang_thai) VALUES
('WEB102-03', 3, 3, 3, 30, 'mo'),
('MAT101-03', 1, 3, 2, 40, 'mo');