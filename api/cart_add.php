<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['ban_id'])) {
    echo json_encode([
        'success' => false,
        'need_table' => true,
        'message' => 'Vui lòng nhập số bàn trước.'
    ]);
    exit;
}

$banId = (int) $_SESSION['ban_id'];
$monAnId = (int) ($_POST['mon_an_id'] ?? 0);
$soLuong = max(1, (int) ($_POST['so_luong'] ?? 1));

$stmt = $pdo->prepare("SELECT id FROM mon_an WHERE id = ? AND trang_thai = 'dang_ban'");
$stmt->execute([$monAnId]);

if (!$stmt->fetch()) {
    echo json_encode([
        'success' => false,
        'message' => 'Món ăn không tồn tại hoặc tạm ngưng bán.'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, so_luong 
    FROM gio_hang_tam 
    WHERE ban_id = ? AND mon_an_id = ?
");
$stmt->execute([$banId, $monAnId]);
$item = $stmt->fetch();

if ($item) {
    $stmt = $pdo->prepare("
        UPDATE gio_hang_tam 
        SET so_luong = so_luong + ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$soLuong, $item['id']]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO gio_hang_tam (ban_id, mon_an_id, so_luong)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$banId, $monAnId, $soLuong]);
}

echo json_encode([
    'success' => true,
    'message' => 'Đã thêm món vào giỏ.'
]);