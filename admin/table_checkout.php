<?php
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/dashboard.php');
    exit;
}

$banId = (int) ($_POST['ban_id'] ?? 0);

if ($banId <= 0) {
    die('Thiếu mã bàn.');
}

try {
    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Cập nhật tất cả đơn chưa thanh toán của bàn này thành đã thanh toán
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
        UPDATE don_hang
        SET trang_thai = 'da_thanh_toan',
            updated_at = NOW()
        WHERE ban_id = ?
          AND trang_thai IN ('moi', 'dang_lam', 'da_xong')
    ");
    $stmt->execute([$banId]);

    /*
    |--------------------------------------------------------------------------
    | Trả bàn về trạng thái trống
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
        UPDATE ban
        SET trang_thai = 'trong'
        WHERE id = ?
    ");
    $stmt->execute([$banId]);

    /*
    |--------------------------------------------------------------------------
    | Xóa giỏ tạm nếu còn
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
        DELETE FROM gio_hang_tam
        WHERE ban_id = ?
    ");
    $stmt->execute([$banId]);

    $pdo->commit();

    header('Location: /admin/dashboard.php?paid=1');
    exit;
} catch (Throwable $e) {
    $pdo->rollBack();
    die('Lỗi thanh toán bàn: ' . $e->getMessage());
}