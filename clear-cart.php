<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
clearCart();
header('Location: checkout.php');
exit;
