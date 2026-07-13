<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_roles('owner');

$todayRevenue = (float) $pdo->query("SELECT COALESCE(SUM(tong_tien),0) FROM don_hang WHERE trang_thai='da_thanh_toan' AND DATE(updated_at)=CURDATE()") ->fetchColumn();
$monthRevenue = (float) $pdo->query("SELECT COALESCE(SUM(tong_tien),0) FROM don_hang WHERE trang_thai='da_thanh_toan' AND YEAR(updated_at)=YEAR(CURDATE()) AND MONTH(updated_at)=MONTH(CURDATE())") ->fetchColumn();
$todayOrders = (int) $pdo->query("SELECT COUNT(*) FROM don_hang WHERE DATE(created_at)=CURDATE()") ->fetchColumn();
$paidToday = (int) $pdo->query("SELECT COUNT(*) FROM don_hang WHERE trang_thai='da_thanh_toan' AND DATE(updated_at)=CURDATE()") ->fetchColumn();
$averageOrder = $paidToday > 0 ? $todayRevenue / $paidToday : 0;
$staffCount = (int) $pdo->query("SELECT COUNT(*) FROM admin WHERE role <> 'owner'") ->fetchColumn();
$activeTables = (int) $pdo->query("SELECT COUNT(*) FROM ban WHERE trang_thai='dang_phuc_vu'") ->fetchColumn();
$recentPaid = $pdo->query("SELECT dh.ma_don, dh.tong_tien, dh.updated_at, b.so_ban FROM don_hang dh JOIN ban b ON b.id=dh.ban_id WHERE dh.trang_thai='da_thanh_toan' ORDER BY dh.updated_at DESC LIMIT 8") ->fetchAll();
$activePage = 'owner';
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tổng quan chủ quán</title><link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>"></head><body>
<div class="admin-layout"><?php require __DIR__ . '/_sidebar.php'; ?><main class="admin-content">
    <div class="role-page-header"><div><p class="role-page-kicker">Dashboard chủ quán</p><h1>Toàn cảnh kinh doanh</h1><p>Doanh thu, hoạt động hôm nay và nhân sự trên một màn hình.</p></div><a class="btn-light" href="/admin/reports.php">Xem báo cáo chi tiết</a></div>
    <div class="metrics-grid owner-metric-grid">
        <div class="metric-card"><div class="metric-label">Doanh thu hôm nay</div><div class="metric-value"><?= number_format($todayRevenue,0,',','.') ?>đ</div><div class="metric-sub"><?= $paidToday ?> đơn đã thanh toán</div></div>
        <div class="metric-card"><div class="metric-label">Doanh thu tháng này</div><div class="metric-value"><?= number_format($monthRevenue,0,',','.') ?>đ</div><div class="metric-sub">Tính theo ngày thanh toán</div></div>
        <div class="metric-card"><div class="metric-label">Giá trị đơn trung bình</div><div class="metric-value"><?= number_format($averageOrder,0,',','.') ?>đ</div><div class="metric-sub"><?= $todayOrders ?> order phát sinh hôm nay</div></div>
        <div class="metric-card"><div class="metric-label">Đang vận hành</div><div class="metric-value"><?= $activeTables ?> bàn</div><div class="metric-sub"><?= $staffCount ?> tài khoản nhân sự</div></div>
    </div>
    <div class="owner-quick-grid">
        <a href="/admin/dashboard.php"><strong>Điều hành đơn hàng</strong><span>Xem đơn mới, đang làm và đã hoàn tất →</span></a>
        <a href="/admin/users.php"><strong>Quản lý nhân sự</strong><span>Tạo tài khoản và phân quyền →</span></a>
        <a href="/admin/reports.php"><strong>Phân tích doanh thu</strong><span>Xem doanh thu và món bán chạy →</span></a>
    </div>
    <div class="table-card"><h3>Thanh toán gần đây</h3><div class="responsive-table"><table class="table"><thead><tr><th>Mã đơn</th><th>Bàn</th><th>Giá trị</th><th>Thời gian</th></tr></thead><tbody><?php if (!$recentPaid): ?><tr><td colspan="4">Chưa có giao dịch thanh toán.</td></tr><?php endif; ?><?php foreach ($recentPaid as $order): ?><tr><td><?= htmlspecialchars($order['ma_don']) ?></td><td>Bàn <?= htmlspecialchars($order['so_ban']) ?></td><td><strong><?= number_format((float)$order['tong_tien'],0,',','.') ?>đ</strong></td><td><?= htmlspecialchars($order['updated_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
</main></div></body></html>
