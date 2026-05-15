<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$menuItems = getSampleMenuItems();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Menu</h1>
        <nav>
            <a href="index.php">Trang chủ</a>
            <a href="order.php">Gọi món</a>
            <a href="checkout.php">Thanh toán</a>
        </nav>
    </header>
    <main>
        <ul>
            <?php foreach ($menuItems as $item): ?>
                <li>
                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                    <span><?= htmlspecialchars($item['price']) ?> VND</span>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>
</html>
