<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$itemId = $_GET['item_id'] ?? null;
if ($itemId !== null) {
    removeFromCart((int)$itemId);
}
header('Location: checkout.php');
exit;
