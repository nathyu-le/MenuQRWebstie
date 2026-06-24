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

if ($cartId <= 0) {
    json_response([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ.'
    ]);
}

$stmt = $pdo->prepare("
    DELETE FROM gio_hang_tam 
    WHERE id = ? AND ban_id = ?
");

$stmt->execute([
    $cartId,
    (int) $_SESSION['ban_id']
]);

json_response([
    'success' => true,
    'message' => 'Đã xóa món khỏi giỏ.'
]);