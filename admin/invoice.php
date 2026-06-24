<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/services/SettingService.php';

require_admin_login();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT 
        dh.*, 
        b.so_ban
    FROM don_hang dh
    JOIN ban b ON dh.ban_id = b.id
    WHERE dh.id = ?
");

$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    die('Không tìm thấy đơn hàng.');
}

$stmt = $pdo->prepare("
    SELECT * 
    FROM chi_tiet_don_hang 
    WHERE don_hang_id = ?
");

$stmt->execute([$id]);
$items = $stmt->fetchAll();

$restaurantName = SettingService::get($pdo, 'restaurant_name', 'Foodie AI Restaurant');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn <?= htmlspecialchars($order['ma_don']) ?></title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 420px;
            margin: 20px auto;
        }

        h2,
        p {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            padding: 7px 0;
            border-bottom: 1px dashed #ccc;
            font-size: 14px;
        }

        .right {
            text-align: right;
        }

        .print {
            margin-top: 20px;
            width: 100%;
            padding: 10px;
        }

        @media print {
            .print {
                display: none;
            }
        }
    </style>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<h2><?= htmlspecialchars($restaurantName) ?></h2>

<p>HÓA ĐƠN THANH TOÁN</p>

<p>
    Mã đơn: <?= htmlspecialchars($order['ma_don']) ?><br>
    Bàn: <?= htmlspecialchars($order['so_ban']) ?><br>
    <?= htmlspecialchars($order['created_at']) ?>
</p>

<table>
    <tr>
        <th>Món</th>
        <th>SL</th>
        <th class="right">Tiền</th>
    </tr>

    <?php foreach ($items as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['ten_mon']) ?></td>
            <td><?= (int) $item['so_luong'] ?></td>
            <td class="right"><?= number_format((float) $item['thanh_tien'], 0, ',', '.') ?>đ</td>
        </tr>
    <?php endforeach; ?>
</table>

<h3 class="right">
    Tổng: <?= number_format((float) $order['tong_tien'], 0, ',', '.') ?>đ
</h3>

<p>Cảm ơn quý khách!</p>

<button class="print" onclick="window.print()">In hóa đơn</button>

</body>
</html>