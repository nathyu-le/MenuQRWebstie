<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

function cart_add_response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$databaseCandidates = [
    __DIR__ . '/../../app/config/database.php',
    __DIR__ . '/../app/config/database.php',
    __DIR__ . '/../app/app/config/database.php',
];
$databaseFile = null;
foreach ($databaseCandidates as $candidate) {
    if (is_file($candidate)) {
        $databaseFile = $candidate;
        break;
    }
}
if ($databaseFile === null) {
    cart_add_response([
        'success' => false,
        'message' => 'Server không tìm thấy app/config/database.php. Kiểm tra lại vị trí thư mục app trên hosting.'
    ], 500);
}
require_once $databaseFile;

try {
    if (empty($_SESSION['ban_id'])) {
        cart_add_response(['success' => false, 'need_table' => true, 'message' => 'Vui lòng chọn số bàn trước khi thêm món.'], 422);
    }

    $banId = (int) $_SESSION['ban_id'];
    $monAnId = (int) ($_POST['mon_an_id'] ?? 0);
    $soLuong = max(1, min(99, (int) ($_POST['so_luong'] ?? 1)));

    if ($monAnId <= 0) {
        cart_add_response(['success' => false, 'message' => 'Mã món ăn không hợp lệ.'], 422);
    }

    $stmt = $pdo->prepare("SELECT id, so_ban FROM ban WHERE id = ? AND trang_thai != 'tam_khoa'");
    $stmt->execute([$banId]);
    if (!$stmt->fetch()) {
        unset($_SESSION['ban_id'], $_SESSION['so_ban']);
        cart_add_response(['success' => false, 'need_table' => true, 'message' => 'Phiên bàn cũ không còn hợp lệ. Vui lòng chọn lại bàn.'], 422);
    }

    $stmt = $pdo->prepare("SELECT id FROM mon_an WHERE id = ? AND trang_thai = 'dang_ban'");
    $stmt->execute([$monAnId]);
    if (!$stmt->fetch()) {
        cart_add_response(['success' => false, 'message' => 'Món ăn không tồn tại hoặc đang tạm ngưng bán.'], 404);
    }

    $stmt = $pdo->prepare("SELECT id FROM gio_hang_tam WHERE ban_id = ? AND mon_an_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->execute([$banId, $monAnId]);
    $cartId = $stmt->fetchColumn();

    if ($cartId) {
        $stmt = $pdo->prepare("UPDATE gio_hang_tam SET so_luong = so_luong + ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$soLuong, $cartId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO gio_hang_tam (ban_id, mon_an_id, so_luong) VALUES (?, ?, ?)");
        $stmt->execute([$banId, $monAnId, $soLuong]);
    }

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(so_luong), 0) FROM gio_hang_tam WHERE ban_id = ?");
    $stmt->execute([$banId]);
    cart_add_response(['success' => true, 'message' => 'Đã thêm món vào giỏ.', 'cart_count' => (int) $stmt->fetchColumn()]);
} catch (PDOException $e) {
    error_log('cart_add database error: ' . $e->getMessage());
    $missingTable = $e->getCode() === '42S02' || stripos($e->getMessage(), 'gio_hang_tam') !== false;
    cart_add_response([
        'success' => false,
        'message' => $missingTable ? 'Database chưa có bảng giỏ hàng. Hãy import lại file sql.sql.' : 'Không thể lưu giỏ hàng vào database. Kiểm tra error_log trên hosting.'
    ], 500);
} catch (Throwable $e) {
    error_log('cart_add error: ' . $e->getMessage());
    cart_add_response(['success' => false, 'message' => 'Server gặp lỗi khi thêm món. Kiểm tra error_log trên hosting.'], 500);
}
