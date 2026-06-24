<?php
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/dashboard.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$trangThai = trim($_POST['trang_thai'] ?? '');
$redirect = trim($_POST['redirect'] ?? '');

$allowedStatus = ['moi', 'dang_lam', 'da_xong', 'da_thanh_toan', 'huy'];

if ($id <= 0 || !in_array($trangThai, $allowedStatus, true)) {
    die('Dữ liệu cập nhật trạng thái không hợp lệ.');
}

try {
    $stmt = $pdo->prepare("
        UPDATE don_hang
        SET trang_thai = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([$trangThai, $id]);

    /*
    |--------------------------------------------------------------------------
    | Redirect thông minh
    |--------------------------------------------------------------------------
    | Nếu form gửi từ kitchen thì quay lại kitchen
    | Nếu form gửi từ dashboard thì quay lại dashboard
    |--------------------------------------------------------------------------
    */
    if ($redirect !== '') {
        header('Location: ' . $redirect);
        exit;
    }

    if (!empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    header('Location: /admin/dashboard.php');
    exit;
} catch (Throwable $e) {
    die('Lỗi cập nhật trạng thái đơn hàng: ' . $e->getMessage());
}