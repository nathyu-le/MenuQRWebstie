-- =========================================================
-- FOODIE AI - DATABASE HOÀN CHỈNH
-- Dùng cho cả:
--   1. Cài database mới hoàn toàn.
--   2. Nâng cấp database cũ đang có dữ liệu.
--
-- Cách chạy: chọn đúng database trong phpMyAdmin rồi Import file này.
-- File không DROP bảng và không xóa đơn hàng/menu hiện tại.
-- Nên sao lưu database trước khi chạy trên hệ thống thật.
-- =========================================================

-- =========================================================
-- BÀN
-- =========================================================
CREATE TABLE IF NOT EXISTS ban (
    id INT AUTO_INCREMENT PRIMARY KEY,
    so_ban VARCHAR(20) NOT NULL UNIQUE,
    trang_thai ENUM('trong', 'dang_phuc_vu', 'tam_khoa') NOT NULL DEFAULT 'trong',
    ghi_chu VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- TÀI KHOẢN VÀ PHÂN QUYỀN
-- owner   : Chủ quán, toàn quyền.
-- manager : Quản lý vận hành, menu, bàn và báo cáo.
-- cashier : Thu ngân, hóa đơn và thanh toán.
-- kitchen : Bếp, nhận và hoàn tất món.
-- =========================================================
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    ho_ten VARCHAR(150) NULL,
    role ENUM('owner', 'manager', 'cashier', 'kitchen') NOT NULL DEFAULT 'kitchen',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mở rộng ENUM tạm thời để database cũ vẫn đọc được role cũ.
ALTER TABLE admin
    MODIFY role ENUM(
        'super_admin',
        'staff',
        'owner',
        'manager',
        'cashier',
        'kitchen'
    ) NOT NULL DEFAULT 'kitchen';

-- Chuyển tài khoản cũ sang hệ role mới mà không mất tài khoản.
UPDATE admin SET role = 'owner' WHERE role = 'super_admin';
UPDATE admin SET role = 'manager' WHERE role = 'staff';

-- Khóa cấu trúc về bốn role chính thức.
ALTER TABLE admin
    MODIFY role ENUM('owner', 'manager', 'cashier', 'kitchen')
    NOT NULL DEFAULT 'kitchen';

-- =========================================================
-- CA THU NGÂN
-- Mỗi nhân viên có thể mở một ca riêng để đối soát tiền mặt.
-- =========================================================
CREATE TABLE IF NOT EXISTS ca_thu_ngan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opened_by INT NOT NULL,
    ten_nhan_vien VARCHAR(150) NOT NULL,
    ca_lam ENUM('sang', 'chieu', 'toi') NOT NULL,
    opening_cash DECIMAL(12,2) NOT NULL DEFAULT 0,
    opened_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_by INT NULL,
    closing_cash DECIMAL(12,2) NULL,
    expected_cash DECIMAL(12,2) NULL,
    discrepancy DECIMAL(12,2) NULL,
    closed_at TIMESTAMP NULL DEFAULT NULL,
    trang_thai ENUM('dang_mo', 'da_dong') NOT NULL DEFAULT 'dang_mo',
    ghi_chu VARCHAR(500) NULL,
    CONSTRAINT fk_ca_opened_by
        FOREIGN KEY (opened_by) REFERENCES admin(id),
    CONSTRAINT fk_ca_closed_by
        FOREIGN KEY (closed_by) REFERENCES admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bổ sung hai cột trên nếu đây là database đã chạy phiên bản cũ.
SET @has_shift_employee = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ca_thu_ngan' AND COLUMN_NAME = 'ten_nhan_vien'
);
SET @shift_employee_sql = IF(
    @has_shift_employee = 0,
    'ALTER TABLE ca_thu_ngan ADD COLUMN ten_nhan_vien VARCHAR(150) NULL AFTER opened_by',
    'SELECT 1'
);
PREPARE shift_employee_stmt FROM @shift_employee_sql;
EXECUTE shift_employee_stmt;
DEALLOCATE PREPARE shift_employee_stmt;

SET @has_shift_period = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ca_thu_ngan' AND COLUMN_NAME = 'ca_lam'
);
SET @shift_period_sql = IF(
    @has_shift_period = 0,
    'ALTER TABLE ca_thu_ngan ADD COLUMN ca_lam ENUM(''sang'',''chieu'',''toi'') NULL AFTER ten_nhan_vien',
    'SELECT 1'
);
PREPARE shift_period_stmt FROM @shift_period_sql;
EXECUTE shift_period_stmt;
DEALLOCATE PREPARE shift_period_stmt;

-- =========================================================
-- GIAO DỊCH THU/CHI THỦ CÔNG
-- Ví dụ: mua nguyên liệu, tiền điện, tạm ứng, thu khác.
-- =========================================================
CREATE TABLE IF NOT EXISTS thu_chi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ca_id INT NULL,
    loai ENUM('thu', 'chi') NOT NULL,
    danh_muc VARCHAR(100) NOT NULL,
    so_tien DECIMAL(12,2) NOT NULL,
    ghi_chu VARCHAR(500) NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_thu_chi_ca
        FOREIGN KEY (ca_id) REFERENCES ca_thu_ngan(id) ON DELETE SET NULL,
    CONSTRAINT fk_thu_chi_user
        FOREIGN KEY (created_by) REFERENCES admin(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- LỊCH SỬ THANH TOÁN
-- Một lần tính tiền cả bàn tạo một bản ghi để đối soát ca.
-- =========================================================
CREATE TABLE IF NOT EXISTS thanh_toan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ban_id INT NOT NULL,
    ca_id INT NULL,
    tong_tien DECIMAL(12,2) NOT NULL,
    phuong_thuc ENUM('tien_mat', 'chuyen_khoan', 'the', 'khac') NOT NULL DEFAULT 'tien_mat',
    ma_tham_chieu VARCHAR(100) NULL,
    ghi_chu VARCHAR(255) NULL,
    collected_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_thanh_toan_ban
        FOREIGN KEY (ban_id) REFERENCES ban(id),
    CONSTRAINT fk_thanh_toan_ca
        FOREIGN KEY (ca_id) REFERENCES ca_thu_ngan(id) ON DELETE SET NULL,
    CONSTRAINT fk_thanh_toan_user
        FOREIGN KEY (collected_by) REFERENCES admin(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bổ sung dữ liệu đối soát cho database phiên bản cũ.
SET @has_payment_reference = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'thanh_toan' AND COLUMN_NAME = 'ma_tham_chieu'
);
SET @payment_reference_sql = IF(
    @has_payment_reference = 0,
    'ALTER TABLE thanh_toan ADD COLUMN ma_tham_chieu VARCHAR(100) NULL AFTER phuong_thuc',
    'SELECT 1'
);
PREPARE payment_reference_stmt FROM @payment_reference_sql;
EXECUTE payment_reference_stmt;
DEALLOCATE PREPARE payment_reference_stmt;

SET @has_payment_note = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'thanh_toan' AND COLUMN_NAME = 'ghi_chu'
);
SET @payment_note_sql = IF(
    @has_payment_note = 0,
    'ALTER TABLE thanh_toan ADD COLUMN ghi_chu VARCHAR(255) NULL AFTER ma_tham_chieu',
    'SELECT 1'
);
PREPARE payment_note_stmt FROM @payment_note_sql;
EXECUTE payment_note_stmt;
DEALLOCATE PREPARE payment_note_stmt;

-- =========================================================
-- MÓN ĂN
-- =========================================================
CREATE TABLE IF NOT EXISTS mon_an (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_mon VARCHAR(200) NOT NULL,
    mo_ta TEXT NULL,
    danh_muc VARCHAR(100) NULL,
    gia DECIMAL(12,2) NOT NULL DEFAULT 0,
    hinh_anh VARCHAR(255) NULL,
    tag_hot TINYINT(1) NOT NULL DEFAULT 0,
    so_lan_goi INT NOT NULL DEFAULT 0,
    rating DECIMAL(2,1) NOT NULL DEFAULT 5.0,
    trang_thai ENUM('dang_ban', 'tam_ngung', 'da_xoa') NOT NULL DEFAULT 'dang_ban',
    is_chay TINYINT(1) NOT NULL DEFAULT 0,
    is_cay TINYINT(1) NOT NULL DEFAULT 0,
    phu_hop_tre_em TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Đồng bộ trạng thái xóa mềm cho database phiên bản cũ.
ALTER TABLE mon_an
    MODIFY trang_thai ENUM('dang_ban', 'tam_ngung', 'da_xoa')
    NOT NULL DEFAULT 'dang_ban';

-- =========================================================
-- GIỎ HÀNG TẠM THEO BÀN
-- =========================================================
CREATE TABLE IF NOT EXISTS gio_hang_tam (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ban_id INT NOT NULL,
    mon_an_id INT NOT NULL,
    so_luong INT NOT NULL DEFAULT 1,
    ghi_chu VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gio_hang_ban
        FOREIGN KEY (ban_id) REFERENCES ban(id) ON DELETE CASCADE,
    CONSTRAINT fk_gio_hang_mon
        FOREIGN KEY (mon_an_id) REFERENCES mon_an(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- ĐƠN HÀNG
-- =========================================================
CREATE TABLE IF NOT EXISTS don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_don VARCHAR(50) NOT NULL UNIQUE,
    ban_id INT NOT NULL,
    tong_tien DECIMAL(12,2) NOT NULL DEFAULT 0,
    trang_thai ENUM(
        'moi',
        'dang_lam',
        'da_xong',
        'da_thanh_toan',
        'huy'
    ) NOT NULL DEFAULT 'moi',
    ghi_chu TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_don_hang_ban
        FOREIGN KEY (ban_id) REFERENCES ban(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- CHI TIẾT ĐƠN HÀNG
-- Lưu cả tên và giá tại thời điểm gọi để hóa đơn cũ không đổi
-- khi menu được chỉnh sửa sau này.
-- =========================================================
CREATE TABLE IF NOT EXISTS chi_tiet_don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    don_hang_id INT NOT NULL,
    mon_an_id INT NOT NULL,
    ten_mon VARCHAR(200) NOT NULL,
    gia DECIMAL(12,2) NOT NULL,
    so_luong INT NOT NULL,
    thanh_tien DECIMAL(12,2) NOT NULL,
    ghi_chu VARCHAR(255) NULL,
    CONSTRAINT fk_chi_tiet_don
        FOREIGN KEY (don_hang_id) REFERENCES don_hang(id) ON DELETE CASCADE,
    CONSTRAINT fk_chi_tiet_mon
        FOREIGN KEY (mon_an_id) REFERENCES mon_an(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- LỊCH SỬ CHAT AI
-- =========================================================
CREATE TABLE IF NOT EXISTS ai_chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ban_id INT NULL,
    user_message TEXT NOT NULL,
    ai_response LONGTEXT NOT NULL,
    response_type VARCHAR(50) NOT NULL DEFAULT 'text',
    tong_tien_goi_y DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_chat_ban
        FOREIGN KEY (ban_id) REFERENCES ban(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- CÀI ĐẶT HỆ THỐNG VÀ AI
-- =========================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cấu hình thanh toán mặc định; chủ quán chỉnh lại trong trang Cài đặt.
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('bank_transfer_enabled', '0'),
('bank_code', ''),
('bank_account_number', ''),
('bank_account_name', ''),
('bank_transfer_prefix', 'FOODIE'),
('bank_qr_template', 'compact2');

-- =========================================================
-- TÀI KHOẢN KHỞI TẠO
-- Tất cả tài khoản dùng chung hash mật khẩu do chủ quán cung cấp.
-- Câu UPDATE bên dưới áp dụng cả tài khoản cũ đã tồn tại.
-- Đăng nhập xong phải đổi ngay tại /admin/profile.php.
-- =========================================================
INSERT IGNORE INTO admin (username, password, ho_ten, role) VALUES
('owner',   '$2y$10$c25NVkdeIMqWdbgR4883YuE/s2CT1mCmGPm5Ma1XbUqGqM26ClTGe', 'Chủ quán', 'owner'),
('manager', '$2y$10$c25NVkdeIMqWdbgR4883YuE/s2CT1mCmGPm5Ma1XbUqGqM26ClTGe', 'Quản lý', 'manager'),
('cashier', '$2y$10$c25NVkdeIMqWdbgR4883YuE/s2CT1mCmGPm5Ma1XbUqGqM26ClTGe', 'Thu ngân', 'cashier'),
('kitchen', '$2y$10$c25NVkdeIMqWdbgR4883YuE/s2CT1mCmGPm5Ma1XbUqGqM26ClTGe', 'Nhân viên bếp', 'kitchen');

UPDATE admin
SET password = '$2y$10$c25NVkdeIMqWdbgR4883YuE/s2CT1mCmGPm5Ma1XbUqGqM26ClTGe';

-- =========================================================
-- KIỂM TRA SAU KHI CHẠY
-- =========================================================
SELECT id, username, ho_ten, role, created_at
FROM admin
ORDER BY id;
