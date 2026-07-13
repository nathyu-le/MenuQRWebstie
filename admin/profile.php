<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_admin_login();

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $stmt = $pdo->prepare('SELECT password FROM admin WHERE id = ?');
    $stmt->execute([current_admin_id()]);
    $hash = (string) $stmt->fetchColumn();
    if (!password_verify($currentPassword, $hash)) {
        $error = 'Mật khẩu hiện tại không đúng.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Mật khẩu mới cần ít nhất 8 ký tự.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Xác nhận mật khẩu mới không khớp.';
    } else {
        $stmt = $pdo->prepare('UPDATE admin SET password = ? WHERE id = ?');
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), current_admin_id()]);
        $message = 'Đã đổi mật khẩu thành công.';
    }
}
$activePage = 'profile';
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Đổi mật khẩu</title><link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>"></head><body>
<div class="admin-layout"><?php require __DIR__ . '/_sidebar.php'; ?><main class="admin-content"><div class="role-page-header"><div><p class="role-page-kicker">Bảo mật tài khoản</p><h1>Đổi mật khẩu</h1><p>Không tiếp tục sử dụng mật khẩu mặc định sau lần đăng nhập đầu tiên.</p></div></div><?php if ($message): ?><div class="role-alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?><?php if ($error): ?><div class="role-alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?><form method="POST" class="form-card profile-form"><?= csrf_field() ?><label>Mật khẩu hiện tại</label><input type="password" name="current_password" autocomplete="current-password" required><label>Mật khẩu mới</label><input type="password" name="new_password" minlength="8" autocomplete="new-password" required><label>Nhập lại mật khẩu mới</label><input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required><button type="submit">Cập nhật mật khẩu</button></form></main></div>
</body></html>
