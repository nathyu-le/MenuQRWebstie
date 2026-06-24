<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';

if (!isset($_SESSION['ban_id'])) {
    json_response([
        'success' => false,
        'need_table' => true,
        'message' => 'Vui lòng nhập số bàn.'
    ]);
}

$cartId = (int) ($_POST['cart_id'] ?? 0);
$change = (int) ($_POST['change'] ?? 0);

if ($cartId <= 0 || $change === 0) {
    json_response([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ.'
    ]);
}

$stmt = $pdo->prepare("
    SELECT * 
    FROM gio_hang_tam 
    WHERE id = ? AND ban_id = ?
");

$stmt->execute([
    $cartId,
    (int) $_SESSION['ban_id']
]);

$item = $stmt->fetch();

if (!$item) {
    json_response([
        'success' => false,
        'message' => 'Không tìm thấy món trong giỏ.'
    ]);
}

$newQty = (int) $item['so_luong'] + $change;

if ($newQty <= 0) {
    $stmt = $pdo->prepare("
        DELETE FROM gio_hang_tam 
        WHERE id = ? AND ban_id = ?
    ");

    $stmt->execute([
        $cartId,
        (int) $_SESSION['ban_id']
    ]);
} else {
    $stmt = $pdo->prepare("
        UPDATE gio_hang_tam 
        SET so_luong = ?, updated_at = NOW() 
        WHERE id = ? AND ban_id = ?
    ");

    $stmt->execute([
        $newQty,
        $cartId,
        (int) $_SESSION['ban_id']
    ]);
}

json_response([
    'success' => true,
    'message' => 'Đã cập nhật giỏ hàng.'
]);