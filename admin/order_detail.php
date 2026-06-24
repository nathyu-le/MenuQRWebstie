<?php
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_admin_login();

$orderId = (int) ($_GET['id'] ?? 0);

if ($orderId <= 0) {
    die('Thiếu mã đơn hàng.');
}

/*
|--------------------------------------------------------------------------
| Lấy thông tin đơn hàng
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT 
        dh.*,
        b.so_ban
    FROM don_hang dh
    JOIN ban b ON dh.ban_id = b.id
    WHERE dh.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die('Không tìm thấy đơn hàng.');
}

/*
|--------------------------------------------------------------------------
| Lấy chi tiết món
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT 
        ten_mon,
        gia,
        so_luong,
        thanh_tien,
        ghi_chu
    FROM chi_tiet_don_hang
    WHERE don_hang_id = ?
    ORDER BY id ASC
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

function status_text($status)
{
    if ($status === 'moi') return 'Mới';
    if ($status === 'dang_lam') return 'Đang làm';
    if ($status === 'da_xong') return 'Đã xong';
    if ($status === 'da_thanh_toan') return 'Đã thanh toán';
    if ($status === 'huy') return 'Hủy';
    return $status;
}

function status_class($status)
{
    return 'status-badge status-' . htmlspecialchars($status);
}

$canPay = !in_array($order['trang_thai'], ['da_thanh_toan', 'huy'], true);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn <?= htmlspecialchars($order['ma_don']) ?> - Foodie AI</title>
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
                max-width: 100% !important;
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
                    <h1>Chi tiết đơn hàng</h1>
                    <p><?= htmlspecialchars($order['ma_don']) ?></p>
                </div>

                <div class="invoice-table-number">
                    Bàn <?= htmlspecialchars($order['so_ban']) ?>
                </div>
            </div>

            <div class="invoice-info-grid">
                <div>
                    <strong>Trạng thái:</strong><br>
                    <span class="<?= status_class($order['trang_thai']) ?>">
                        <?= htmlspecialchars(status_text($order['trang_thai'])) ?>
                    </span>
                </div>

                <div>
                    <strong>Thời gian tạo:</strong><br>
                    <?= htmlspecialchars($order['created_at']) ?>
                </div>

                <div>
                    <strong>Tổng tiền:</strong><br>
                    <?= number_format((float) $order['tong_tien'], 0, ',', '.') ?>đ
                </div>
            </div>

            <?php if (!empty($order['ghi_chu'])): ?>
                <div class="notice">
                    <strong>Ghi chú đơn hàng:</strong>
                    <?= nl2br(htmlspecialchars($order['ghi_chu'])) ?>
                </div>
            <?php endif; ?>

            <div class="invoice-section">
                <h3>Danh sách món</h3>

                <table class="table">
                    <tr>
                        <th>Món</th>
                        <th>Đơn giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                        <th>Ghi chú món</th>
                    </tr>

                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['ten_mon']) ?></td>
                            <td><?= number_format((float) $item['gia'], 0, ',', '.') ?>đ</td>
                            <td><?= number_format((int) $item['so_luong']) ?></td>
                            <td><?= number_format((float) $item['thanh_tien'], 0, ',', '.') ?>đ</td>
                            <td><?= htmlspecialchars($item['ghi_chu'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="5">Đơn này chưa có món.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <div class="invoice-total">
                <span>Tổng thanh toán</span>
                <strong><?= number_format((float) $order['tong_tien'], 0, ',', '.') ?>đ</strong>
            </div>

            <div class="invoice-actions no-print">
                <button type="button" onclick="window.print()">
                    In hóa đơn
                </button>

                <?php if ($canPay): ?>
                    <form 
                        method="POST" 
                        action="/admin/order_update.php"
                        onsubmit="return confirm('Xác nhận thanh toán đơn này?')"
                    >
                        <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                        <input type="hidden" name="trang_thai" value="da_thanh_toan">
                        <input type="hidden" name="redirect" value="/admin/order_detail.php?id=<?= (int) $order['id'] ?>">

                        <button type="submit">
                            Xác nhận thanh toán
                        </button>
                    </form>
                <?php else: ?>
                    <button type="button" disabled>
                        Đơn đã thanh toán / đã hủy
                    </button>
                <?php endif; ?>

                <a 
                    class="btn-light" 
                    href="/admin/invoice_table.php?ban_id=<?= (int) $order['ban_id'] ?>"
                >
                    Tính tiền cả bàn
                </a>

                <a class="btn-light" href="/admin/dashboard.php">
                    Quay lại dashboard
                </a>
            </div>
        </div>
    </main>
</div>

</body>
</html>