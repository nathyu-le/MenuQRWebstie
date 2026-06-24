<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';

if (!isset($_SESSION['ban_id'])) {
    json_response([
        'success' => false,
        'message' => 'Vui lòng nhập số bàn.'
    ]);
}

$banId = (int) $_SESSION['ban_id'];
$note = trim($_POST['note'] ?? '');

$stmt = $pdo->prepare("
    SELECT 
        gh.*, 
        ma.ten_mon, 
        ma.gia
    FROM gio_hang_tam gh
    JOIN mon_an ma ON gh.mon_an_id = ma.id
    WHERE gh.ban_id = ?
");

$stmt->execute([$banId]);
$items = $stmt->fetchAll();

if (empty($items)) {
    json_response([
        'success' => false,
        'message' => 'Giỏ hàng đang trống.'
    ]);
}

$total = 0;

foreach ($items as $item) {
    $total += (float) $item['gia'] * (int) $item['so_luong'];
}

$maDon = 'DH' . date('YmdHis') . random_int(100, 999);

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("
        INSERT INTO don_hang 
        (ma_don, ban_id, tong_tien, trang_thai, ghi_chu)
        VALUES (?, ?, ?, 'moi', ?)
    ");

    $stmt->execute([
        $maDon,
        $banId,
        $total,
        $note
    ]);

    $orderId = (int) $pdo->lastInsertId();

    foreach ($items as $item) {
        $qty = (int) $item['so_luong'];
        $price = (float) $item['gia'];
        $line = $qty * $price;

        $stmt = $pdo->prepare("
            INSERT INTO chi_tiet_don_hang
            (don_hang_id, mon_an_id, ten_mon, gia, so_luong, thanh_tien, ghi_chu)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $orderId,
            (int) $item['mon_an_id'],
            $item['ten_mon'],
            $price,
            $qty,
            $line,
            $item['ghi_chu'] ?? null
        ]);

        $stmt = $pdo->prepare("
            UPDATE mon_an 
            SET so_lan_goi = so_lan_goi + ? 
            WHERE id = ?
        ");

        $stmt->execute([
            $qty,
            (int) $item['mon_an_id']
        ]);
    }

    $stmt = $pdo->prepare("
        DELETE FROM gio_hang_tam 
        WHERE ban_id = ?
    ");

    $stmt->execute([$banId]);

    $stmt = $pdo->prepare("
        UPDATE ban 
        SET trang_thai = 'dang_phuc_vu' 
        WHERE id = ?
    ");

    $stmt->execute([$banId]);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Đã gửi order về bếp.',
        'ma_don' => $maDon
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();

    json_response([
        'success' => false,
        'message' => 'Không thể gửi order.'
    ]);
}