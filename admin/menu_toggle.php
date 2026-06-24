<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_admin_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM mon_an WHERE id = ?");
$stmt->execute([$id]);
$food = $stmt->fetch();

if ($food) {
    $newStatus = $food['trang_thai'] === 'dang_ban' ? 'tam_ngung' : 'dang_ban';

    $stmt = $pdo->prepare("
        UPDATE mon_an
        SET trang_thai = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$newStatus, $id]);
}

header('Location: /admin/menu.php');
exit;