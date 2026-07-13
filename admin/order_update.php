<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_roles(['owner', 'manager', 'kitchen']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . role_home());
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$nextStatus = trim($_POST['trang_thai'] ?? '');
$refererPath = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH);
$defaultRedirect = $refererPath === '/admin/kitchen.php' ? '/admin/kitchen.php' : role_home();
$redirect = safe_local_redirect(trim($_POST['redirect'] ?? ''), $defaultRedirect);

// Mọi thao tác xuất phát từ màn hình bếp phải ở lại màn hình bếp,
// kể cả khi người thao tác đăng nhập bằng role owner hoặc manager.
if ($refererPath === '/admin/kitchen.php' || trim($_POST['redirect'] ?? '') === '/admin/kitchen.php') {
    $redirect = '/admin/kitchen.php';
}
$allStatuses = ['moi', 'dang_lam', 'da_xong', 'da_thanh_toan', 'huy'];

if ($id <= 0 || !in_array($nextStatus, $allStatuses, true)) {
    http_response_code(422);
    die('Dữ liệu cập nhật trạng thái không hợp lệ.');
}

$stmt = $pdo->prepare('SELECT trang_thai FROM don_hang WHERE id = ?');
$stmt->execute([$id]);
$currentStatus = $stmt->fetchColumn();
if ($currentStatus === false) {
    http_response_code(404);
    die('Không tìm thấy đơn hàng.');
}

if (in_array($currentStatus, ['da_thanh_toan', 'huy'], true)) {
    http_response_code(409);
    die('Đơn đã chốt không thể thay đổi trạng thái.');
}

$role = current_admin_role();
if ($role === 'kitchen') {
    $allowedTransitions = [
        'moi' => ['dang_lam'],
        'dang_lam' => ['da_xong'],
        'da_xong' => [],
        'da_thanh_toan' => [],
        'huy' => [],
    ];
    if (!in_array($nextStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
        http_response_code(403);
        die('Bếp chỉ được nhận đơn mới và xác nhận hoàn tất chế biến.');
    }
}

// Thanh toán phải đi qua table_checkout.php để cập nhật đơn và trạng thái bàn cùng lúc.
if ($nextStatus === 'da_thanh_toan') {
    http_response_code(422);
    die('Vui lòng thanh toán từ màn hình thu ngân.');
}

$stmt = $pdo->prepare('UPDATE don_hang SET trang_thai = ?, updated_at = NOW() WHERE id = ?');
$stmt->execute([$nextStatus, $id]);

header('Location: ' . $redirect);
exit;
