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

$stmt = $pdo->prepare("
    DELETE FROM gio_hang_tam 
    WHERE ban_id = ?
");

$stmt->execute([
    (int) $_SESSION['ban_id']
]);

json_response([
    'success' => true,
    'message' => 'Đã xóa toàn bộ giỏ hàng.'
]);