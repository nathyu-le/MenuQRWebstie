<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$itemId = $_POST['item_id'] ?? null;
if ($itemId !== null) {
    addToCart((int)$itemId);
}
header('Location: order.php');
exit;
