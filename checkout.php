<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$cart = getCart();
$total = calculateCartTotal($cart);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Thanh toán</h1>
        <nav>
            <a href="order.php">Gọi món</a>
            <a href="menu.php">Menu</a>
        </nav>
    </header>
    <main>
        <h2>Thông tin giỏ hàng</h2>
        <?php if (empty($cart)): ?>
            <p>Giỏ hàng trống.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($cart as $item): ?>
                    <li><?= htmlspecialchars($item['name']) ?> x <?= htmlspecialchars($item['quantity']) ?> - <?= htmlspecialchars($item['price']) ?> VND</li>
                <?php endforeach; ?>
            </ul>
            <p>Tổng: <?= htmlspecialchars($total) ?> VND</p>
            <form action="checkout.php" method="post">
                <button type="submit" name="confirm_order">Xác nhận đơn hàng</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
