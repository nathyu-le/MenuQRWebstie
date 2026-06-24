CREATE DATABASE foodie_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE foodie_ai;

CREATE TABLE ban (
    id INT AUTO_INCREMENT PRIMARY KEY,
    so_ban VARCHAR(20) NOT NULL UNIQUE,
    trang_thai ENUM('trong', 'dang_phuc_vu', 'tam_khoa') DEFAULT 'trong',
    ghi_chu VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    ho_ten VARCHAR(150),
    role ENUM('super_admin', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE mon_an (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_mon VARCHAR(200) NOT NULL,
    mo_ta TEXT,
    danh_muc VARCHAR(100),
    gia DECIMAL(12,2) NOT NULL DEFAULT 0,
    hinh_anh VARCHAR(255),
    tag_hot TINYINT(1) DEFAULT 0,
    so_lan_goi INT DEFAULT 0,
    rating DECIMAL(2,1) DEFAULT 5.0,
    trang_thai ENUM('dang_ban', 'tam_ngung') DEFAULT 'dang_ban',
    is_chay TINYINT(1) DEFAULT 0,
    is_cay TINYINT(1) DEFAULT 0,
    phu_hop_tre_em TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE gio_hang_tam (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ban_id INT NOT NULL,
    mon_an_id INT NOT NULL,
    so_luong INT NOT NULL DEFAULT 1,
    ghi_chu VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ban_id) REFERENCES ban(id) ON DELETE CASCADE,
    FOREIGN KEY (mon_an_id) REFERENCES mon_an(id) ON DELETE CASCADE
);

CREATE TABLE don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_don VARCHAR(50) NOT NULL UNIQUE,
    ban_id INT NOT NULL,
    tong_tien DECIMAL(12,2) NOT NULL DEFAULT 0,
    trang_thai ENUM('moi', 'dang_lam', 'da_xong', 'da_thanh_toan', 'huy') DEFAULT 'moi',
    ghi_chu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ban_id) REFERENCES ban(id)
);

CREATE TABLE chi_tiet_don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    don_hang_id INT NOT NULL,
    mon_an_id INT NOT NULL,
    ten_mon VARCHAR(200) NOT NULL,
    gia DECIMAL(12,2) NOT NULL,
    so_luong INT NOT NULL,
    thanh_tien DECIMAL(12,2) NOT NULL,
    ghi_chu VARCHAR(255),
    FOREIGN KEY (don_hang_id) REFERENCES don_hang(id) ON DELETE CASCADE,
    FOREIGN KEY (mon_an_id) REFERENCES mon_an(id)
);

CREATE TABLE ai_chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ban_id INT NULL,
    user_message TEXT NOT NULL,
    ai_response LONGTEXT NOT NULL,
    response_type VARCHAR(50) DEFAULT 'text',
    tong_tien_goi_y DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ban_id) REFERENCES ban(id) ON DELETE SET NULL
);

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);