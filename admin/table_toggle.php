<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_admin_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM ban WHERE id = ?");
$stmt->execute([$id]);
$table = $stmt->fetch();

if ($table) {
    $newStatus = $table['trang_thai'] === 'tam_khoa' ? 'trong' : 'tam_khoa';

    $stmt = $pdo->prepare("UPDATE ban SET trang_thai = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
}

header('Location: /admin/tables.php');
exit;