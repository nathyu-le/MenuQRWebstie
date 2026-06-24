<?php
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_admin_login();

function fetch_orders_by_status(PDO $pdo, string $status): array
{
    $stmt = $pdo->prepare("
        SELECT 
            dh.id,
            dh.ma_don,
            dh.ban_id,
            dh.tong_tien,
            dh.trang_thai,
            dh.ghi_chu,
            dh.created_at,
            b.so_ban
        FROM don_hang dh
        JOIN ban b ON dh.ban_id = b.id
        WHERE dh.trang_thai = ?
        ORDER BY dh.created_at ASC
    ");
    $stmt->execute([$status]);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $detailStmt = $pdo->prepare("
            SELECT ten_mon, so_luong
            FROM chi_tiet_don_hang
            WHERE don_hang_id = ?
            ORDER BY id ASC
        ");
        $detailStmt->execute([$order['id']]);
        $order['items'] = $detailStmt->fetchAll();
    }

    return $orders;
}

$newOrders = fetch_orders_by_status($pdo, 'moi');
$cookingOrders = fetch_orders_by_status($pdo, 'dang_lam');
$doneOrders = fetch_orders_by_status($pdo, 'da_xong');

function render_kitchen_cards(array $orders, string $status): void
{
    if (empty($orders)) {
        echo '<div class="kitchen-empty">Không có đơn nào.</div>';
        return;
    }

    foreach ($orders as $order) {
        echo '<div class="kitchen-order-card">';
        echo '<div class="kitchen-order-top">';
        echo '<div>';
        echo '<div class="kitchen-table">Bàn ' . htmlspecialchars($order['so_ban']) . '</div>';
        echo '<div class="kitchen-order-code">' . htmlspecialchars($order['ma_don']) . '</div>';
        echo '</div>';
        echo '<div class="kitchen-time">' . htmlspecialchars(date('H:i d/m', strtotime($order['created_at']))) . '</div>';
        echo '</div>';

        echo '<div class="kitchen-order-body">';

        if (!empty($order['ghi_chu'])) {
            echo '<div class="kitchen-note">';
            echo '<strong>Ghi chú:</strong> ' . nl2br(htmlspecialchars($order['ghi_chu']));
            echo '</div>';
        }

        echo '<ul class="kitchen-item-list">';
        foreach ($order['items'] as $item) {
            echo '<li><strong>' . (int)$item['so_luong'] . 'x</strong> ' . htmlspecialchars($item['ten_mon']) . '</li>';
        }
        echo '</ul>';

        echo '<div class="kitchen-total">Tổng: ' . number_format((float)$order['tong_tien'], 0, ',', '.') . 'đ</div>';

        echo '</div>';

        echo '<div class="kitchen-order-actions">';
        if ($status === 'moi') {
            echo '<form method="POST" action="/admin/order_update.php">';
            echo '<input type="hidden" name="id" value="' . (int)$order['id'] . '">';
            echo '<input type="hidden" name="trang_thai" value="dang_lam">';
            echo '<button type="submit" class="btn kitchen-btn-start">Nhận làm</button>';
            echo '</form>';
        } elseif ($status === 'dang_lam') {
            echo '<form method="POST" action="/admin/order_update.php">';
            echo '<input type="hidden" name="id" value="' . (int)$order['id'] . '">';
            echo '<input type="hidden" name="trang_thai" value="da_xong">';
            echo '<button type="submit" class="btn kitchen-btn-done">Hoàn tất</button>';
            echo '</form>';
        } elseif ($status === 'da_xong') {
            echo '<a class="btn-light" href="/admin/order_detail.php?id=' . (int)$order['id'] . '">Xem chi tiết</a>';
        }
        echo '</div>';

        echo '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Màn hình bếp - Foodie AI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <h2>Foodie AI</h2>
        <p><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></p>

        <a href="/admin/dashboard.php">Dashboard Order</a>
        <a href="/admin/kitchen.php" class="active">Màn hình bếp</a>
        <a href="/admin/menu.php">Quản lý menu</a>
        <a href="/admin/tables.php">Quản lý bàn + QR</a>
        <a href="/admin/reports.php">Báo cáo</a>
        <a href="/admin/chat_history.php">Lịch sử AI</a>
        <a href="/admin/settings.php">Settings AI</a>
        <a href="/admin/logout.php">Đăng xuất</a>
    </aside>

    <main class="admin-content">
        <div class="kitchen-header-bar">
            <div>
                <h1>Màn hình bếp</h1>
                <p>Quản lý tiến độ chế biến món ăn theo thời gian thực cơ bản.</p>
            </div>

            <div class="kitchen-summary">
                <div class="kitchen-mini-stat">
                    <span>Đơn mới</span>
                    <strong><?= count($newOrders) ?></strong>
                </div>
                <div class="kitchen-mini-stat">
                    <span>Đang làm</span>
                    <strong><?= count($cookingOrders) ?></strong>
                </div>
                <div class="kitchen-mini-stat">
                    <span>Đã xong</span>
                    <strong><?= count($doneOrders) ?></strong>
                </div>
            </div>
        </div>

        <div class="kitchen-board">
            <section class="kitchen-column">
                <div class="kitchen-column-head kitchen-head-new">
                    <h3>Đơn mới</h3>
                    <span><?= count($newOrders) ?></span>
                </div>
                <div class="kitchen-column-body">
                    <?php render_kitchen_cards($newOrders, 'moi'); ?>
                </div>
            </section>

            <section class="kitchen-column">
                <div class="kitchen-column-head kitchen-head-cooking">
                    <h3>Đang làm</h3>
                    <span><?= count($cookingOrders) ?></span>
                </div>
                <div class="kitchen-column-body">
                    <?php render_kitchen_cards($cookingOrders, 'dang_lam'); ?>
                </div>
            </section>

            <section class="kitchen-column">
                <div class="kitchen-column-head kitchen-head-done">
                    <h3>Đã xong</h3>
                    <span><?= count($doneOrders) ?></span>
                </div>
                <div class="kitchen-column-body">
                    <?php render_kitchen_cards($doneOrders, 'da_xong'); ?>
                </div>
            </section>
        </div>
    </main>
</div>

<script>
setTimeout(function () {
    window.location.reload();
}, 15000);
</script>

</body>
</html>