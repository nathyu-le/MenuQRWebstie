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
    <title>Gọi món</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/cart.js" defer></script>
</head>
<body>
    <header>
        <h1>Gọi món</h1>
        <nav>
            <a href="menu.php">Menu</a>
            <a href="checkout.php">Thanh toán</a>
        </nav>
    </header>
    <main>
        <form action="add-to-cart.php" method="post">
            <ul>
                <?php foreach ($menuItems as $item): ?>
                    <li>
                        <strong><?= htmlspecialchars($item['name']) ?></strong>
                        <span><?= htmlspecialchars($item['price']) ?> VND</span>
                        <button type="submit" name="item_id" value="<?= htmlspecialchars($item['id']) ?>">Thêm vào giỏ</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </form>
    </main>
</body>
</html>
