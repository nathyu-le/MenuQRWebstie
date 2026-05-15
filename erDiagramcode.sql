erDiagram
    BAN ||--o{ GIO_HANG_TAM : đặt
    MON_AN ||--o{ GIO_HANG_TAM : có
    BAN ||--o{ DON_HANG : có
    MON_AN ||--o{ CHI_TIET_DON_HANG : thuộc
    DON_HANG ||--o{ CHI_TIET_DON_HANG : chứa
    BAN ||--o{ AI_CHAT_HISTORY : lưu_trữ
    BAN ||--o{ DON_HANG : tham_chieu
    BAN }o--|| SETTINGS : sử_dụng

    BAN {
        string so_ban "B01, 05 Uk"
        string trang_thai "trong, dang_dung, da_dat"
    }
    ADMIN {
        string username "User"
        string ho_ten "Full Name"
        string role "admin, nhanvien, bep"
    }
    MON_AN {
        string ten_mon "Dish Name"
        int gia "Price"
        string danh_muc "Category"
        string mo_ta "Description"
    }
    GIO_HANG_TAM {
        int so_luong "Quantity"
        int gia_thoi_diem "Current Price"
    }
    DON_HANG {
        string trang_thai "dang_xu_ly, da_nhan_bep"
        int tong_tien "Total Price"
        string ghi_chu "Notes"
    }
    CHI_TIET_DON_HANG {
        int so_luong "Quantity"
        int gia_thoi_diem "Current Price"
    }
    AI_CHAT_HISTORY {
        string loai "user, ai"
    }
    SETTINGS {
        string ten_quan "Restaurant Name"
    }