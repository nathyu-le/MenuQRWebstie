<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

if (is_admin_logged_in()) {
    header('Location: ' . role_home());
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['ho_ten'] ?: $admin['username'];
        $_SESSION['admin_role'] = normalize_admin_role($admin['role']);

        header('Location: ' . role_home($admin['role']));
        exit;
    } else {
        $error = 'Sai username hoặc password.';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Đăng nhập vận hành - Foodie AI</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-story">
        <a class="login-brand" href="/"><span>F</span>Foodie <b>AI</b></a>
        <div><p class="role-page-kicker">Workspace dành cho F&amp;B</p><h1>Mỗi vai trò,<br>một nhịp vận hành.</h1><p>Bếp, thu ngân, quản lý và chủ quán đăng nhập chung một nơi — hệ thống tự đưa từng người đến đúng màn hình làm việc.</p></div>
        <div class="login-role-list"><span>Bếp</span><span>Thu ngân</span><span>Quản lý</span><span>Chủ quán</span></div>
    </section>
    <section class="login-box">
        <div class="login-mobile-brand">Foodie <b>AI</b></div>
        <p class="role-page-kicker">Chào mừng trở lại</p>
        <h2>Đăng nhập hệ thống</h2>
        <p class="login-help">Sử dụng tài khoản được chủ quán cấp cho bạn.</p>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <label>Tên đăng nhập</label>
            <input type="text" name="username" autocomplete="username" placeholder="Nhập username" required autofocus>
            <label>Mật khẩu</label>
            <input type="password" name="password" autocomplete="current-password" placeholder="Nhập mật khẩu" required>
            <button type="submit">Đăng nhập vào workspace</button>
        </form>
        <a class="login-back" href="/">← Quay lại trang chủ</a>
    </section>
</main>
</body>
</html>
