<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json; charset=utf-8');

$soBan = trim($_POST['so_ban'] ?? '');

if ($soBan === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập số bàn.'
    ]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM ban WHERE so_ban = ? AND trang_thai != 'tam_khoa'");
$stmt->execute([$soBan]);
$ban = $stmt->fetch();

if (!$ban) {
    echo json_encode([
        'success' => false,
        'message' => 'Số bàn không tồn tại hoặc đang bị khóa.'
    ]);
    exit;
}

$_SESSION['ban_id'] = $ban['id'];
$_SESSION['so_ban'] = $ban['so_ban'];

echo json_encode([
    'success' => true,
    'message' => 'Đã chọn bàn ' . $ban['so_ban'],
    'ban' => $ban
]);