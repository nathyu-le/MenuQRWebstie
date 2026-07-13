<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_roles('owner');

$message = '';
$error = '';
$roles = ['owner', 'manager', 'cashier', 'kitchen'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = trim($_POST['action'] ?? 'create');
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === current_admin_id()) {
            $error = 'Bạn không thể xóa chính tài khoản đang đăng nhập.';
        } elseif ($id > 0) {
            $stmt = $pdo->prepare('SELECT role FROM admin WHERE id = ?');
            $stmt->execute([$id]);
            $targetRole = normalize_admin_role($stmt->fetchColumn() ?: '');
            $ownerCount = (int) $pdo->query("SELECT COUNT(*) FROM admin WHERE role IN ('owner', 'super_admin')")->fetchColumn();
            if ($targetRole === 'owner' && $ownerCount <= 1) {
                $error = 'Hệ thống phải luôn còn ít nhất một tài khoản chủ quán.';
            } else {
                try {
                    $stmt = $pdo->prepare('DELETE FROM admin WHERE id = ?');
                    $stmt->execute([$id]);
                    $message = 'Đã xóa tài khoản nhân sự.';
                } catch (PDOException $e) {
                    $error = 'Không thể xóa tài khoản đã có lịch sử ca hoặc giao dịch.';
                }
            }
        }
    } else {
        $username = trim($_POST['username'] ?? '');
        $name = trim($_POST['ho_ten'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? '');
        if ($username === '' || $name === '' || strlen($password) < 8 || !in_array($role, $roles, true)) {
            $error = 'Vui lòng nhập đủ thông tin; mật khẩu cần ít nhất 8 ký tự.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO admin (username, password, ho_ten, role) VALUES (?, ?, ?, ?)');
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name, $role]);
                $message = 'Đã tạo tài khoản ' . role_label($role) . '.';
            } catch (PDOException $e) {
                $error = $e->getCode() === '23000' ? 'Tên đăng nhập đã tồn tại.' : 'Không thể tạo tài khoản.';
            }
        }
    }
}

$users = $pdo->query('SELECT id, username, ho_ten, role, created_at FROM admin ORDER BY FIELD(role, "owner", "manager", "cashier", "kitchen"), id')->fetchAll();
$activePage = 'users';
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tài khoản nhân sự</title><link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>"></head><body>
<div class="admin-layout"><?php require __DIR__ . '/_sidebar.php'; ?><main class="admin-content">
    <div class="role-page-header"><div><p class="role-page-kicker">Chỉ dành cho chủ quán</p><h1>Tài khoản nhân sự</h1><p>Tạo tài khoản riêng để mỗi người chỉ truy cập đúng khu vực làm việc.</p></div></div>
    <?php if ($message): ?><div class="role-alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="role-alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="users-layout">
        <form method="POST" class="form-card"><?= csrf_field() ?><input type="hidden" name="action" value="create"><h3>Thêm tài khoản</h3><label>Họ tên</label><input name="ho_ten" required><label>Tên đăng nhập</label><input name="username" autocomplete="off" required><label>Mật khẩu ban đầu</label><input type="password" name="password" minlength="8" required><label>Vai trò</label><select name="role" required><option value="kitchen">Bếp</option><option value="cashier">Thu ngân</option><option value="manager">Quản lý</option><option value="owner">Chủ quán</option></select><button type="submit">Tạo tài khoản</button></form>
        <div class="table-card"><h3>Danh sách tài khoản</h3><div class="responsive-table"><table class="table"><thead><tr><th>Nhân sự</th><th>Username</th><th>Vai trò</th><th>Ngày tạo</th><th></th></tr></thead><tbody><?php foreach ($users as $user): ?><tr><td><strong><?= htmlspecialchars($user['ho_ten'] ?: $user['username']) ?></strong></td><td><?= htmlspecialchars($user['username']) ?></td><td><span class="role-badge role-<?= htmlspecialchars(normalize_admin_role($user['role'])) ?>"><?= htmlspecialchars(role_label($user['role'])) ?></span></td><td><?= htmlspecialchars(date('d/m/Y', strtotime($user['created_at']))) ?></td><td><?php if ((int) $user['id'] !== current_admin_id()): ?><form method="POST" onsubmit="return confirm('Xóa tài khoản này?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $user['id'] ?>"><button class="danger" type="submit">Xóa</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div>
    </div>
</main></div></body></html>
