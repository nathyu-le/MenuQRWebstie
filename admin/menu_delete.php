<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_roles(['owner', 'manager']);

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    UPDATE mon_an 
    SET trang_thai = 'da_xoa', updated_at = NOW() 
    WHERE id = ?
");

$stmt->execute([$id]);

header('Location: /admin/menu.php');
exit;
