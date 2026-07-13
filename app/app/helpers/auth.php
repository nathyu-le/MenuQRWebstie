<?php
declare(strict_types=1);

const ADMIN_ROLES = ['owner', 'manager', 'cashier', 'kitchen'];

function ensure_admin_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function normalize_admin_role(?string $role): string
{
    $role = trim((string) $role);
    if ($role === 'super_admin') return 'owner';
    if ($role === 'staff') return 'manager';
    return in_array($role, ADMIN_ROLES, true) ? $role : 'manager';
}

function current_admin_role(): string
{
    ensure_admin_session();
    return normalize_admin_role($_SESSION['admin_role'] ?? null);
}

function current_admin_id(): int
{
    ensure_admin_session();
    return (int) ($_SESSION['admin_id'] ?? 0);
}

function is_admin_logged_in(): bool
{
    return current_admin_id() > 0;
}

function role_home(?string $role = null): string
{
    $role = normalize_admin_role($role ?? current_admin_role());
    if ($role === 'kitchen') return '/admin/kitchen.php';
    if ($role === 'cashier') return '/admin/cashier.php';
    if ($role === 'owner') return '/admin/owner.php';
    return '/admin/dashboard.php';
}

function require_admin_login(): void
{
    if (!is_admin_logged_in()) {
        $next = $_SERVER['REQUEST_URI'] ?? '';
        $query = $next !== '' ? '?next=' . urlencode($next) : '';
        header('Location: /admin/login.php' . $query);
        exit;
    }
}

function has_admin_role($roles): bool
{
    require_admin_login();
    $roles = is_array($roles) ? $roles : [$roles];
    return in_array(current_admin_role(), $roles, true);
}

function require_roles($roles): void
{
    require_admin_login();
    if (has_admin_role($roles)) return;

    http_response_code(403);
    $home = htmlspecialchars(role_home(), ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="vi"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Không có quyền truy cập</title><body style="margin:0;background:#f7f5f0;color:#17201c;font-family:Arial,sans-serif">';
    echo '<main style="max-width:560px;margin:12vh auto;padding:36px;border:1px solid #dde3df;border-radius:18px;background:white">';
    echo '<p style="color:#a07844;font-weight:700">403 · PHÂN QUYỀN</p><h1>Không có quyền truy cập</h1>';
    echo '<p>Tài khoản của bạn không được phép mở khu vực hoặc thực hiện thao tác này.</p>';
    echo '<a href="' . $home . '" style="display:inline-block;margin-top:14px;padding:12px 18px;border-radius:999px;color:white;background:#173f35;text-decoration:none">Về màn hình làm việc</a>';
    echo '</main></body></html>';
    exit;
}

function role_label(?string $role = null): string
{
    $role = normalize_admin_role($role ?? current_admin_role());
    if ($role === 'owner') return 'Chủ quán';
    if ($role === 'manager') return 'Quản lý';
    if ($role === 'cashier') return 'Thu ngân';
    if ($role === 'kitchen') return 'Bếp';
    return 'Nhân viên';
}

function safe_local_redirect(string $target, string $fallback): string
{
    if ($target === '' || substr($target, 0, 1) !== '/' || substr($target, 0, 2) === '//') return $fallback;
    return $target;
}

function csrf_token(): string
{
    ensure_admin_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(): void
{
    ensure_admin_session();
    $submitted = (string) ($_POST['csrf_token'] ?? '');
    if ($submitted === '' || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $submitted)) {
        http_response_code(419);
        die('Phiên biểu mẫu đã hết hạn. Vui lòng tải lại trang và thử lại.');
    }
}
