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
$items = json_decode($_POST['items'] ?? '[]', true);

if (!is_array($items) || empty($items)) {
    echo json_encode([
        'success' => false,
        'message' => 'Set món không hợp lệ.'
    ]);
    exit;
}

foreach ($items as $item) {
    $monAnId = (int) ($item['id'] ?? 0);
    $soLuong = max(1, (int) ($item['so_luong'] ?? 1));

    $stmt = $pdo->prepare("SELECT id FROM mon_an WHERE id = ? AND trang_thai = 'dang_ban'");
    $stmt->execute([$monAnId]);

    if (!$stmt->fetch()) {
        continue;
    }

    $stmt = $pdo->prepare("
        SELECT id 
        FROM gio_hang_tam 
        WHERE ban_id = ? AND mon_an_id = ?
    ");
    $stmt->execute([$banId, $monAnId]);
    $cartItem = $stmt->fetch();

    if ($cartItem) {
        $stmt = $pdo->prepare("
            UPDATE gio_hang_tam
            SET so_luong = so_luong + ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$soLuong, $cartItem['id']]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO gio_hang_tam (ban_id, mon_an_id, so_luong)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$banId, $monAnId, $soLuong]);
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Đã thêm toàn bộ set món vào giỏ.'
]);