<?php
if (!function_exists('current_admin_role')) {
    require_once __DIR__ . '/../app/app/helpers/auth.php';
}
$activePage = $activePage ?? '';
$role = current_admin_role();
$displayName = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Nhân viên';
$displayInitial = function_exists('mb_substr') ? mb_substr($displayName, 0, 1, 'UTF-8') : substr($displayName, 0, 1);
$nav = [];

if ($role === 'owner') {
    $nav[] = ['owner', '/admin/owner.php', 'Tổng quan chủ quán'];
    $nav[] = ['dashboard', '/admin/dashboard.php', 'Điều hành đơn hàng'];
} elseif ($role === 'manager') {
    $nav[] = ['dashboard', '/admin/dashboard.php', 'Điều hành ca'];
}
if (in_array($role, ['owner', 'manager', 'kitchen'], true)) {
    $nav[] = ['kitchen', '/admin/kitchen.php', 'Màn hình bếp'];
}
if (in_array($role, ['owner', 'manager', 'cashier'], true)) {
    $nav[] = ['cashier', '/admin/cashier.php', 'Thu ngân & thanh toán'];
    $nav[] = ['cashbook', '/admin/cashbook.php', 'Sổ thu chi & ca'];
}
if (in_array($role, ['owner', 'manager'], true)) {
    $nav[] = ['menu', '/admin/menu.php', 'Quản lý menu'];
    $nav[] = ['tables', '/admin/tables.php', 'Bàn & mã QR'];
}
if (in_array($role, ['owner', 'manager', 'cashier'], true)) {
    $nav[] = ['reports', '/admin/reports.php', 'Doanh thu & món bán chạy'];
}
if ($role === 'owner') {
    $nav[] = ['users', '/admin/users.php', 'Tài khoản nhân sự'];
    $nav[] = ['chat', '/admin/chat_history.php', 'Lịch sử AI'];
    $nav[] = ['settings', '/admin/settings.php', 'Cài đặt hệ thống'];
}
$nav[] = ['profile', '/admin/profile.php', 'Đổi mật khẩu'];
?>
<aside class="admin-sidebar role-sidebar">
    <div class="sidebar-brand">Foodie <b>AI</b></div>
    <div class="sidebar-user">
        <span><?= htmlspecialchars($displayInitial) ?></span>
        <div><strong><?= htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Nhân viên') ?></strong><small><?= htmlspecialchars(role_label()) ?></small></div>
    </div>
    <nav>
        <?php foreach ($nav as [$key, $href, $label]): ?>
            <a href="<?= $href ?>" class="<?= $activePage === $key ? 'active' : '' ?>"><?= htmlspecialchars($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <a class="sidebar-logout" href="/admin/logout.php">Đăng xuất</a>
</aside>
