<?php
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_roles(['owner', 'manager']);

$status = trim($_GET['status'] ?? '');

$allowedStatus = ['moi', 'dang_lam', 'da_xong', 'da_thanh_toan', 'huy'];
$editableStatus = ['moi', 'dang_lam', 'da_xong', 'huy'];

function get_status_text($status)
{
    if ($status === 'moi') return 'Mới';
    if ($status === 'dang_lam') return 'Đang làm';
    if ($status === 'da_xong') return 'Đã xong';
    if ($status === 'da_thanh_toan') return 'Đã thanh toán';
    if ($status === 'huy') return 'Hủy';
    return $status;
}

function get_status_class($status)
{
    return 'status-badge status-' . htmlspecialchars($status);
}

try {
    $totalOrders = (int) $pdo->query("SELECT COUNT(*) FROM don_hang")->fetchColumn();

    $newOrders = (int) $pdo->query("
        SELECT COUNT(*) 
        FROM don_hang 
        WHERE trang_thai = 'moi'
    ")->fetchColumn();

    $processingOrders = (int) $pdo->query("
        SELECT COUNT(*) 
        FROM don_hang 
        WHERE trang_thai = 'dang_lam'
    ")->fetchColumn();

    $doneOrders = (int) $pdo->query("
        SELECT COUNT(*) 
        FROM don_hang 
        WHERE trang_thai = 'da_xong'
    ")->fetchColumn();

    $paidRevenue = (float) $pdo->query("
        SELECT COALESCE(SUM(tong_tien), 0) 
        FROM don_hang 
        WHERE trang_thai = 'da_thanh_toan'
    ")->fetchColumn();

    $todayRevenue = (float) $pdo->query("
        SELECT COALESCE(SUM(tong_tien), 0) 
        FROM don_hang 
        WHERE trang_thai = 'da_thanh_toan'
          AND DATE(created_at) = CURDATE()
    ")->fetchColumn();

    if ($status !== '' && in_array($status, $allowedStatus)) {
        $stmt = $pdo->prepare("
            SELECT 
                dh.id,
                dh.ban_id,
                dh.ma_don,
                dh.tong_tien,
                dh.trang_thai,
                dh.ghi_chu,
                dh.created_at,
                b.so_ban
            FROM don_hang dh
            JOIN ban b ON dh.ban_id = b.id
            WHERE dh.trang_thai = ?
            ORDER BY dh.created_at DESC
            LIMIT 50
        ");

        $stmt->execute([$status]);
    } else {
        $stmt = $pdo->query("
            SELECT 
                dh.id,
                dh.ban_id,
                dh.ma_don,
                dh.tong_tien,
                dh.trang_thai,
                dh.ghi_chu,
                dh.created_at,
                b.so_ban
            FROM don_hang dh
            JOIN ban b ON dh.ban_id = b.id
            ORDER BY dh.created_at DESC
            LIMIT 50
        ");
    }

    $orders = $stmt->fetchAll();
} catch (Throwable $e) {
    die('Dashboard lỗi: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Order - Foodie AI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<div class="admin-layout">
    <?php $activePage = 'dashboard'; require __DIR__ . '/_sidebar.php'; ?>
    <aside class="admin-sidebar" style="display:none">
        <h2>Foodie AI</h2>
        <p><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></p>

        <a href="/admin/dashboard.php" class="active">Dashboard Order</a>
        <a href="/admin/kitchen.php">Màn hình bếp</a>
        <a href="/admin/menu.php">Quản lý menu</a>
        <a href="/admin/tables.php">Quản lý bàn + QR</a>
        <a href="/admin/reports.php">Báo cáo</a>
        <a href="/admin/chat_history.php">Lịch sử AI</a>
        <a href="/admin/settings.php">Settings AI</a>
        <a href="/admin/logout.php">Đăng xuất</a>
    </aside>

    <main class="admin-content">
        <h1>Dashboard Order</h1>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-label">Tổng đơn hàng</div>
                <div class="metric-value"><?= number_format($totalOrders) ?></div>
                <div class="metric-sub">Tất cả đơn trong hệ thống</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">Đơn mới</div>
                <div class="metric-value"><?= number_format($newOrders) ?></div>
                <div class="metric-sub">Đang chờ bếp tiếp nhận</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">Đang làm</div>
                <div class="metric-value"><?= number_format($processingOrders) ?></div>
                <div class="metric-sub">Đơn đang xử lý</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">Đã xong</div>
                <div class="metric-value"><?= number_format($doneOrders) ?></div>
                <div class="metric-sub">Chờ phục vụ / thanh toán</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">Doanh thu hôm nay</div>
                <div class="metric-value"><?= number_format($todayRevenue, 0, ',', '.') ?>đ</div>
                <div class="metric-sub">Đơn đã thanh toán hôm nay</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">Tổng doanh thu</div>
                <div class="metric-value"><?= number_format($paidRevenue, 0, ',', '.') ?>đ</div>
                <div class="metric-sub">Tổng doanh thu thực nhận</div>
            </div>
        </div>

        <form method="GET" class="form-card">
            <h3>Lọc đơn hàng</h3>

            <label>Trạng thái đơn</label>
            <select name="status">
                <option value="">Tất cả trạng thái</option>

                <?php foreach ($allowedStatus as $st): ?>
                    <option value="<?= htmlspecialchars($st) ?>" <?= $status === $st ? 'selected' : '' ?>>
                        <?= htmlspecialchars(get_status_text($st)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
<br>
<br>
            <button type="submit">Lọc</button>
            <a class="btn-light" href="/admin/dashboard.php">Xóa lọc</a>
        </form>
<br>
<br>
        <div class="table-card">
            <h3>Đơn hàng gần đây</h3>

            <table class="table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Bàn</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                        <th>Thời gian</th>
                        <th>Chi tiết</th>
                        <th>Cập nhật</th>
                        <th>Tính tiền</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['ma_don']) ?></td>

                                <td>
                                    <strong>Bàn <?= htmlspecialchars($order['so_ban']) ?></strong>
                                </td>

                                <td>
                                    <?= number_format((float) $order['tong_tien'], 0, ',', '.') ?>đ
                                </td>

                                <td>
                                    <span class="<?= get_status_class($order['trang_thai']) ?>">
                                        <?= htmlspecialchars(get_status_text($order['trang_thai'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($order['ghi_chu'] ?? '') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($order['created_at']) ?>
                                </td>

                                <td>
                                    <a href="/admin/order_detail.php?id=<?= (int) $order['id'] ?>">Xem</a>
                                </td>

                                <td>
                                    <?php if (!in_array($order['trang_thai'], ['da_thanh_toan', 'huy'], true)): ?>
                                    <form method="POST" action="/admin/order_update.php">
                                        <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">

                                        <select name="trang_thai">
                                            <?php foreach ($editableStatus as $st): ?>
                                                <option 
                                                    value="<?= htmlspecialchars($st) ?>" 
                                                    <?= $order['trang_thai'] === $st ? 'selected' : '' ?>
                                                >
                                                    <?= htmlspecialchars(get_status_text($st)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button type="submit">Lưu</button>
                                    </form>
                                    <?php else: ?><span class="muted">Đã khóa</span><?php endif; ?>
                                </td>
                                <td>
    <a class="btn-light" href="/admin/invoice_table.php?ban_id=<?= (int) $order['ban_id'] ?>">
        Tính tiền bàn
    </a>
</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">Chưa có đơn hàng nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>
