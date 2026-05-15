<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng</title>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main>
        <h1>Đơn hàng</h1>
        <p>Hiển thị đơn hàng mẫu.</p>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
