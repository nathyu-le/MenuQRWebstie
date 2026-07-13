<?php
session_start();

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function send_json(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_roles(['owner', 'manager', 'kitchen']);

    $afterId = (int) ($_GET['after_id'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | ID đơn mới nhất đang ở trạng thái 'moi'
    |--------------------------------------------------------------------------
    */
    $latestId = (int) $pdo->query("
        SELECT COALESCE(MAX(id), 0)
        FROM don_hang
        WHERE trang_thai = 'moi'
    ")->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | Lấy các đơn mới phát sinh sau after_id
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
        SELECT 
            dh.id,
            dh.ma_don,
            dh.created_at,
            b.so_ban
        FROM don_hang dh
        JOIN ban b ON dh.ban_id = b.id
        WHERE dh.trang_thai = 'moi'
          AND dh.id > ?
        ORDER BY dh.id ASC
    ");

    $stmt->execute([$afterId]);
    $orders = $stmt->fetchAll();

    $stateRows = $pdo->query("
        SELECT trang_thai, COUNT(*) AS so_luong,
               COALESCE(MAX(UNIX_TIMESTAMP(COALESCE(updated_at, created_at))), 0) AS lan_cap_nhat
        FROM don_hang
        WHERE trang_thai IN ('moi', 'dang_lam', 'da_xong')
        GROUP BY trang_thai
        ORDER BY trang_thai
    ")->fetchAll();
    $stateVersion = sha1(json_encode($stateRows));

    send_json([
        'success' => true,
        'latest_id' => $latestId,
        'count' => count($orders),
        'orders' => $orders,
        'state_version' => $stateVersion
    ]);
} catch (Throwable $e) {
    send_json([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
