<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_roles(['owner', 'manager', 'cashier']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . role_home());
    exit;
}

verify_csrf();

$banId = (int) ($_POST['ban_id'] ?? 0);
$paymentMethod = trim($_POST['phuong_thuc'] ?? 'tien_mat');
$allowedMethods = ['tien_mat', 'chuyen_khoan', 'the', 'khac'];

if ($banId <= 0 || !in_array($paymentMethod, $allowedMethods, true)) {
    http_response_code(422);
    die('Thông tin thanh toán không hợp lệ.');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(tong_tien), 0)
        FROM don_hang
        WHERE ban_id = ?
          AND trang_thai IN ('moi', 'dang_lam', 'da_xong')
        FOR UPDATE
    ");
    $stmt->execute([$banId]);
    $total = (float) $stmt->fetchColumn();

    if ($total <= 0) {
        throw new RuntimeException('Bàn này không còn hóa đơn cần thanh toán.');
    }

    $stmt = $pdo->prepare("
        SELECT id FROM ca_thu_ngan
        WHERE opened_by = ? AND trang_thai = 'dang_mo'
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([current_admin_id()]);
    $shiftId = $stmt->fetchColumn();

    if (current_admin_role() === 'cashier' && !$shiftId) {
        throw new RuntimeException('Thu ngân cần mở ca trước khi thanh toán.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO thanh_toan (ban_id, ca_id, tong_tien, phuong_thuc, collected_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$banId, $shiftId ?: null, $total, $paymentMethod, current_admin_id()]);

    $stmt = $pdo->prepare("
        UPDATE don_hang
        SET trang_thai = 'da_thanh_toan', updated_at = NOW()
        WHERE ban_id = ? AND trang_thai IN ('moi', 'dang_lam', 'da_xong')
    ");
    $stmt->execute([$banId]);

    $stmt = $pdo->prepare("UPDATE ban SET trang_thai = 'trong' WHERE id = ?");
    $stmt->execute([$banId]);

    $stmt = $pdo->prepare("DELETE FROM gio_hang_tam WHERE ban_id = ?");
    $stmt->execute([$banId]);

    $pdo->commit();
    header('Location: ' . role_home() . '?paid=1');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(422);
    die('Không thể hoàn tất thanh toán: ' . htmlspecialchars($e->getMessage()));
}
