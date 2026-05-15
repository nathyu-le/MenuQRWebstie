<?php
// Trang đăng nhập admin mẫu
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($user === 'admin' && $pass === 'admin') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Đăng nhập không đúng.';
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin</title>
</head>
<body>
    <h1>Đăng nhập Admin</h1>
    <?php if (!empty($error)): ?><p><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        <label>Tên đăng nhập: <input name="username"></label><br>
        <label>Mật khẩu: <input type="password" name="password"></label><br>
        <button type="submit">Đăng nhập</button>
    </form>
</body>
</html>
