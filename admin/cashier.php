<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_roles(['owner', 'manager', 'cashier']);

$stmt = $pdo->query("
    SELECT b.id, b.so_ban, b.trang_thai,
           COUNT(dh.id) AS so_don,
           COALESCE(SUM(dh.tong_tien), 0) AS tong_tien,
           SUM(CASE WHEN dh.trang_thai = 'da_xong' THEN 1 ELSE 0 END) AS don_san_sang,
           SUM(CASE WHEN dh.trang_thai IN ('moi', 'dang_lam') THEN 1 ELSE 0 END) AS don_dang_xu_ly
    FROM ban b
    LEFT JOIN don_hang dh ON dh.ban_id = b.id
        AND dh.trang_thai IN ('moi', 'dang_lam', 'da_xong')
    GROUP BY b.id, b.so_ban, b.trang_thai
    ORDER BY CAST(b.so_ban AS UNSIGNED), b.so_ban
");
$tables = $stmt->fetchAll();

$todayRevenue = (float) $pdo->query("SELECT COALESCE(SUM(tong_tien), 0) FROM don_hang WHERE trang_thai = 'da_thanh_toan' AND DATE(updated_at) = CURDATE()") ->fetchColumn();
$paidToday = (int) $pdo->query("SELECT COUNT(*) FROM don_hang WHERE trang_thai = 'da_thanh_toan' AND DATE(updated_at) = CURDATE()") ->fetchColumn();
$stmt = $pdo->prepare("SELECT id FROM ca_thu_ngan WHERE opened_by=? AND trang_thai='dang_mo' ORDER BY id DESC LIMIT 1");
$stmt->execute([current_admin_id()]);
$hasOpenShift = (bool) $stmt->fetchColumn();
$waitingTables = 0;
foreach ($tables as $tableRow) {
    if ((int) $tableRow['so_don'] > 0) $waitingTables++;
}
$activePage = 'cashier';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Thu ngân - Foodie AI</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-content">
        <div class="role-page-header"><div><p class="role-page-kicker">Khu vực thu ngân</p><h1>Thanh toán theo bàn</h1><p>Kiểm tra trạng thái món trước khi xác nhận và in hóa đơn.</p></div><a class="btn-light" href="/admin/cashier.php">Làm mới</a></div>
        <?php if (isset($_GET['paid'])): ?><div class="role-alert success">Đã thanh toán và trả bàn về trạng thái trống.</div><?php endif; ?>
        <?php if (current_admin_role() === 'cashier' && !$hasOpenShift): ?><div class="role-alert error">Bạn chưa mở ca. <a href="/admin/cashbook.php">Mở ca thu ngân</a> trước khi nhận thanh toán.</div><?php endif; ?>
        <div class="metrics-grid cashier-metrics">
            <div class="metric-card"><div class="metric-label">Doanh thu hôm nay</div><div class="metric-value"><?= number_format($todayRevenue, 0, ',', '.') ?>đ</div></div>
            <div class="metric-card"><div class="metric-label">Đơn đã thanh toán</div><div class="metric-value"><?= number_format($paidToday) ?></div></div>
            <div class="metric-card"><div class="metric-label">Bàn đang có hóa đơn</div><div class="metric-value"><?= number_format($waitingTables) ?></div></div>
        </div>
        <div class="cashier-table-grid">
            <?php foreach ($tables as $table): ?>
                <?php $hasBill = (int) $table['so_don'] > 0; $processing = (int) $table['don_dang_xu_ly'] > 0; ?>
                <article class="cashier-table-card <?= $hasBill ? 'has-bill' : 'empty' ?>">
                    <div class="cashier-table-top"><span>Bàn</span><strong><?= htmlspecialchars($table['so_ban']) ?></strong><i class="<?= $processing ? 'processing' : ($hasBill ? 'ready' : '') ?>"><?= $processing ? 'Đang phục vụ' : ($hasBill ? 'Sẵn sàng' : 'Trống') ?></i></div>
                    <?php if ($hasBill): ?>
                        <div class="cashier-table-info"><span><?= (int) $table['so_don'] ?> đơn chưa thanh toán</span><strong><?= number_format((float) $table['tong_tien'], 0, ',', '.') ?>đ</strong></div>
                        <a class="btn" href="/admin/invoice_table.php?ban_id=<?= (int) $table['id'] ?>">Xem hóa đơn</a>
                    <?php else: ?><p>Chưa có order cần thanh toán.</p><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body></html>
