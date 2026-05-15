CREATE DATABASE db_order_monan 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE db_order_monan;

-- 1. Bàn ăn
CREATE TABLE ban (
    id INT AUTO_INCREMENT PRIMARY KEY,
    so_ban VARCHAR(20) UNIQUE NOT NULL,           -- B01, 05, VIP01...
    trang_thai ENUM('trong', 'dang_dung', 'da_dat') DEFAULT 'trong',
    so_nguoi INT DEFAULT 0,
    thoi_gian_vao DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Admin / Nhân viên
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    ho_ten VARCHAR(100),
    role ENUM('admin', 'nhanvien', 'bep') DEFAULT 'nhanvien',
    trang_thai TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Món ăn
CREATE TABLE mon_an (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_mon VARCHAR(150) NOT NULL,
    gia INT NOT NULL,
    danh_muc VARCHAR(50) NOT NULL,                 -- khai_vi, mon_chinh, canh, trang_mieng, do_uong, com, pho...
    mo_ta TEXT,
    anh VARCHAR(255),
    so_lan_goi INT DEFAULT 0,
    tag_hot TINYINT(1) DEFAULT 0,
    rating DECIMAL(3,1) DEFAULT 4.5,
    trang_thai TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Giỏ hàng tạm (RẤT QUAN TRỌNG)
CREATE TABLE gio_hang_tam (
    id INT AUTO_INCREMENT PRIMARY KEY,
    so_ban VARCHAR(20) NOT NULL,
    mon_an_id INT NOT NULL,
    so_luong INT NOT NULL DEFAULT 1,
    gia_thoi_diem INT NOT NULL,
    thoi_gian_them DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (so_ban) REFERENCES ban(so_ban),
    FOREIGN KEY (mon_an_id) REFERENCES mon_an(id)
);

-- 5. Đơn hàng chính thức
CREATE TABLE don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    so_ban VARCHAR(20) NOT NULL,
    tong_tien INT NOT NULL,
    trang_thai ENUM('dang_xu_ly', 'da_nhan_bep', 'dang_lam', 'da_hoan_thanh', 'da_thanh_toan', 'huy') DEFAULT 'dang_xu_ly',
    ghi_chu TEXT,
    thoi_gian_dat DATETIME DEFAULT CURRENT_TIMESTAMP,
    thoi_gian_hoan_thanh DATETIME NULL,
    FOREIGN KEY (so_ban) REFERENCES ban(so_ban)
);

-- 6. Chi tiết đơn hàng
CREATE TABLE chi_tiet_don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    don_hang_id INT NOT NULL,
    mon_an_id INT NOT NULL,
    so_luong INT NOT NULL,
    gia_thoi_diem INT NOT NULL,
    FOREIGN KEY (don_hang_id) REFERENCES don_hang(id) ON DELETE CASCADE,
    FOREIGN KEY (mon_an_id) REFERENCES mon_an(id)
);

-- 7. Lịch sử chat AI
CREATE TABLE ai_chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    so_ban VARCHAR(20) NOT NULL,
    tin_nhan TEXT NOT NULL,
    loai ENUM('user', 'ai') NOT NULL,
    thoi_gian DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (so_ban) REFERENCES ban(so_ban)
);

-- 8. Cài đặt hệ thống (tên quán, logo, API key...)
CREATE TABLE settings (
    id INT PRIMARY KEY DEFAULT 1,
    ten_quan VARCHAR(100),
    dia_chi TEXT,
    sdt VARCHAR(20),
    logo VARCHAR(255),
    grok_api_key VARCHAR(255),           -- nếu dùng Grok
    gemini_api_key VARCHAR(255),
    intro_ai TEXT,                       -- lời chào AI
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Index để tối ưu tốc độ
CREATE INDEX idx_so_ban ON don_hang(so_ban);
CREATE INDEX idx_so_ban_giohang ON gio_hang_tam(so_ban);
CREATE INDEX idx_tag_hot ON mon_an(tag_hot, so_lan_goi DESC);
CREATE INDEX idx_danh_muc ON mon_an(danh_muc);