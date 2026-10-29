-- =============================================================================
-- CSDL HỆ THỐNG QUẢN LÝ KHÓA HỌC VÀ ĐĂNG KÝ HỌC PHẦN (STUDENTHUB)
-- File: schema.sql (Thư mục database/)
-- Chuẩn kỹ thuật: MySQL 8.x / MariaDB, Bộ mã: utf8mb4 / utf8mb4_unicode_ci
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. BẢNG NGƯỜI DÙNG (Tài khoản truy cập hệ thống)
DROP TABLE IF EXISTS `dang_ky_hoc`;
DROP TABLE IF EXISTS `lop_hoc_phan`;
DROP TABLE IF EXISTS `hoc_phan`;
DROP TABLE IF EXISTS `hoc_ky`;
DROP TABLE IF EXISTS `nguoi_dung`;

CREATE TABLE `nguoi_dung` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ten_dang_nhap` VARCHAR(50) NOT NULL,
    `mat_khau_hash` VARCHAR(255) NOT NULL,
    `ho_ten` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NULL,
    `so_dien_thoai` VARCHAR(20) NULL,
    `vai_tro` ENUM('sinh_vien', 'giang_vien', 'admin') NOT NULL DEFAULT 'sinh_vien',
    `ngay_tao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `uc_nguoi_dung_username` UNIQUE (`ten_dang_nhap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. BẢNG HỌC KỲ (Khung thời gian đào tạo & cổng đăng ký)
CREATE TABLE `hoc_ky` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ten_hoc_ky` VARCHAR(100) NOT NULL,
    `ngay_bat_dau` DATE NOT NULL,
    `ngay_ket_thuc` DATE NOT NULL,
    `thoi_gian_mo_dk` DATETIME NOT NULL,
    `thoi_gian_dong_dk` DATETIME NOT NULL,
    `trang_thai_dang_ky` TINYINT(1) NOT NULL DEFAULT 1,
    `ngay_tao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. BẢNG HỌC PHẦN (Danh mục môn học)
CREATE TABLE `hoc_phan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_hoc_phan` VARCHAR(20) NOT NULL,
    `ten_hoc_phan` VARCHAR(150) NOT NULL,
    `so_tin_chi` INT NOT NULL,
    `loai_hoc_phan` ENUM('dai_cuong', 'co_so_nganh', 'chuyen_nganh') NOT NULL DEFAULT 'co_so_nganh',
    `hoc_phan_tien_quyet_id` INT NULL,
    `mo_ta` TEXT NULL,
    `dieu_kien_tien_quyet` VARCHAR(255) NOT NULL DEFAULT 'Không có',
    `ngay_tao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `uc_hoc_phan_ma` UNIQUE (`ma_hoc_phan`),
    CONSTRAINT `chk_hoc_phan_tin_chi` CHECK (`so_tin_chi` > 0),
    CONSTRAINT `fk_hoc_phan_tien_quyet` FOREIGN KEY (`hoc_phan_tien_quyet_id`)
        REFERENCES `hoc_phan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. BẢNG LỚP HỌC PHẦN (Các lớp mở thực tế theo học kỳ)
CREATE TABLE `lop_hoc_phan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_lop` VARCHAR(50) NOT NULL,
    `hoc_phan_id` INT NOT NULL,
    `hoc_ky_id` INT NOT NULL,
    `giang_vien_id` INT NULL,
    `si_so_toi_da` INT NOT NULL DEFAULT 40,
    `trang_thai` ENUM('mo', 'khoa') NOT NULL DEFAULT 'mo',
    `thoi_gian_mo_rieng` DATETIME NULL,
    `thoi_gian_dong_rieng` DATETIME NULL,
    `lich_hoc` VARCHAR(100) NULL,
    `phong_hoc` VARCHAR(100) NULL,
    `ngay_tao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `uc_lop_ma_lop` UNIQUE (`ma_lop`),
    CONSTRAINT `chk_lop_si_so` CHECK (`si_so_toi_da` > 0),
    CONSTRAINT `fk_lop_hoc_phan` FOREIGN KEY (`hoc_phan_id`)
        REFERENCES `hoc_phan` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_lop_hoc_ky` FOREIGN KEY (`hoc_ky_id`)
        REFERENCES `hoc_ky` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_lop_giang_vien` FOREIGN KEY (`giang_vien_id`)
        REFERENCES `nguoi_dung` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. BẢNG ĐĂNG KÝ HỌC (Ghi nhận đăng ký tín chỉ của sinh viên)
CREATE TABLE `dang_ky_hoc` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sinh_vien_id` INT NOT NULL,
    `lop_hoc_phan_id` INT NOT NULL,
    `trang_thai` ENUM('dang_ky', 'da_huy') NOT NULL DEFAULT 'dang_ky',
    `ngay_dang_ky` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ngay_huy` DATETIME NULL,
    CONSTRAINT `uc_sinh_vien_lop` UNIQUE (`sinh_vien_id`, `lop_hoc_phan_id`),
    CONSTRAINT `fk_dkh_sinh_vien` FOREIGN KEY (`sinh_vien_id`)
        REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dkh_lop_hoc_phan` FOREIGN KEY (`lop_hoc_phan_id`)
        REFERENCES `lop_hoc_phan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CÁC CHỈ MỤC (INDEX) TỐI ƯU HIỆU NĂNG TRUY VẤN
CREATE INDEX `idx_nguoi_dung_vai_tro` ON `nguoi_dung` (`vai_tro`);
CREATE INDEX `idx_lop_hoc_phan_ky` ON `lop_hoc_phan` (`hoc_ky_id`, `hoc_phan_id`);
CREATE INDEX `idx_lop_giang_vien` ON `lop_hoc_phan` (`giang_vien_id`);
CREATE INDEX `idx_dkh_trang_thai` ON `dang_ky_hoc` (`lop_hoc_phan_id`, `trang_thai`);
CREATE INDEX `idx_dkh_sinh_vien` ON `dang_ky_hoc` (`sinh_vien_id`, `trang_thai`);

SET FOREIGN_KEY_CHECKS = 1;
