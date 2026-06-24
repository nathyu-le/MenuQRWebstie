<?php
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_admin_login();

$banId = (int) ($_GET['ban_id'] ?? 0);

if ($banId <= 0) {
    die('Thiếu mã bàn.');
}

/*
|--------------------------------------------------------------------------
| Lấy thông tin bàn
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("SELECT * FROM ban WHERE id = ?");
$stmt->execute([$banId]);
$ban = $stmt->fetch();

if (!$ban) {
    die('Không tìm thấy bàn.');
}

/*
|--------------------------------------------------------------------------
| Lấy các đơn chưa thanh toán của bàn
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT *
    FROM don_hang
    WHERE ban_id = ?
      AND trang_thai IN ('moi', 'dang_lam', 'da_xong')
    ORDER BY created_at ASC
");
$stmt->execute([$banId]);
$orders = $stmt->fetchAll();

$orderIds = array_column($orders, 'id');

if (empty($orderIds)) {
    $items = [];
    $totalBill = 0;
} else {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

    /*
    |--------------------------------------------------------------------------
    | Gộp món giống nhau lại
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
        SELECT 
            ten_mon,
            gia,
            SUM(so_luong) AS tong_so_luong,
            SUM(thanh_tien) AS tong_tien
        FROM chi_tiet_don_hang
        WHERE don_hang_id IN ($placeholders)
        GROUP BY ten_mon, gia
        ORDER BY ten_mon ASC
    ");
    $stmt->execute($orderIds);
    $items = $stmt->fetchAll();

    $totalBill = 0;
    foreach ($items as $item) {
        $totalBill += (float) $item['tong_tien'];
    }
}

$hasProcessingOrder = false;

foreach ($orders as $order) {
    if ($order['trang_thai'] === 'moi' || $order['trang_thai'] === 'dang_lam') {
        $hasProcessingOrder = true;
        break;
    }
}

function status_text($status)
{
    if ($status === 'moi') return 'Mới';
    if ($status === 'dang_lam') return 'Đang làm';
    if ($status === 'da_xong') return 'Đã xong';
    if ($status === 'da_thanh_toan') return 'Đã thanh toán';
    if ($status === 'huy') return 'Hủy';
    return $status;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn bàn <?= htmlspecialchars($ban['so_ban']) ?> - Foodie AI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">

    <style>
        @media print {
            .no-print,
            .admin-sidebar {
                display: none !important;
            }

            .admin-layout {
                display: block !important;
            }

            .admin-content {
                padding: 0 !important;
            }

            body {
                background: white !important;
            }

            .invoice-box {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body>

<div class="admin-layout">
    <aside class="admin-sidebar no-print">
        <h2>Foodie AI</h2>
        <p><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></p>

        <a href="/admin/dashboard.php">Dashboard Order</a>
        <a href="/admin/kitchen.php">Màn hình bếp</a>
        <a href="/admin/menu.php">Quản lý menu</a>
        <a href="/admin/tables.php">Quản lý bàn + QR</a>
        <a href="/admin/reports.php">Báo cáo</a>
        <a href="/admin/chat_history.php">Lịch sử AI</a>
        <a href="/admin/settings.php">Settings AI</a>
        <a href="/admin/logout.php">Đăng xuất</a>
    </aside>

    <main class="admin-content">
        <div class="invoice-box">
            <div class="invoice-header">
                <div>
                    <h1>Foodie AI Restaurant</h1>
                    <p>Hóa đơn thanh toán theo bàn</p>
                </div>

                <div class="invoice-table-number">
                    Bàn <?= htmlspecialchars($ban['so_ban']) ?>
                </div>
            </div>

            <div class="invoice-info-grid">
                <div>
                    <strong>Ngày:</strong>
                    <?= date('d/m/Y H:i') ?>
                </div>

                <div>
                    <strong>Số đơn gộp:</strong>
                    <?= count($orders) ?>
                </div>

                <div>
                    <strong>Trạng thái:</strong>
                    Chưa thanh toán
                </div>
            </div>

            <?php if ($hasProcessingOrder): ?>
                <div class="notice">
                    Bàn này vẫn còn đơn đang ở trạng thái <strong>Mới / Đang làm</strong>. 
                    Kiểm tra với bếp trước khi thanh toán.
                </div>
            <?php endif; ?>

            <?php if (!empty($orders)): ?>
                <div class="invoice-section">
                    <h3>Các đơn được gộp</h3>

                    <table class="table">
                        <tr>
                            <th>Mã đơn</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                            <th>Tổng tiền</th>
                        </tr>

                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['ma_don']) ?></td>
                                <td><?= htmlspecialchars(status_text($order['trang_thai'])) ?></td>
                                <td><?= htmlspecialchars($order['created_at']) ?></td>
                                <td><?= number_format((float) $order['tong_tien'], 0, ',', '.') ?>đ</td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <div class="invoice-section">
                    <h3>Chi tiết món ăn</h3>

                    <table class="table">
                        <tr>
                            <th>Món</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                        </tr>

                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['ten_mon']) ?></td>
                                <td><?= number_format((float) $item['gia'], 0, ',', '.') ?>đ</td>
                                <td><?= number_format((int) $item['tong_so_luong']) ?></td>
                                <td><?= number_format((float) $item['tong_tien'], 0, ',', '.') ?>đ</td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <div class="invoice-total">
                    <span>Tổng thanh toán</span>
                    <strong><?= number_format($totalBill, 0, ',', '.') ?>đ</strong>
                </div>

                <div class="invoice-actions no-print">
                    <button onclick="window.print()">In hóa đơn</button>

                    <form method="POST" action="/admin/table_checkout.php" onsubmit="return confirm('Xác nhận thanh toán toàn bộ đơn của bàn này?')">
                        <input type="hidden" name="ban_id" value="<?= (int) $ban['id'] ?>">
                        <button type="submit">Xác nhận đã thanh toán</button>
                    </form>

                    <a class="btn-light" href="/admin/dashboard.php">Quay lại dashboard</a>
                </div>
            <?php else: ?>
                <div class="empty-box">
                    Bàn này hiện không có đơn nào cần thanh toán.
                </div>

                <div class="invoice-actions no-print">
                    <a class="btn-light" href="/admin/dashboard.php">Quay lại dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>