<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_roles('owner');

$todayRevenue = (float) $pdo->query("SELECT COALESCE(SUM(tong_tien),0) FROM thanh_toan WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$yesterdayRevenue = (float) $pdo->query("SELECT COALESCE(SUM(tong_tien),0) FROM thanh_toan WHERE DATE(created_at)=CURDATE()-INTERVAL 1 DAY")->fetchColumn();
$paidToday = (int) $pdo->query("SELECT COUNT(*) FROM thanh_toan WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$averageOrder = $paidToday ? $todayRevenue / $paidToday : 0;
$activeTables = (int) $pdo->query("SELECT COUNT(*) FROM ban WHERE trang_thai='dang_phuc_vu'")->fetchColumn();
$totalTables = (int) $pdo->query("SELECT COUNT(*) FROM ban")->fetchColumn();
$processingOrders = (int) $pdo->query("SELECT COUNT(*) FROM don_hang WHERE trang_thai IN ('moi','dang_lam')")->fetchColumn();
$growth = $yesterdayRevenue > 0 ? (($todayRevenue-$yesterdayRevenue)/$yesterdayRevenue*100) : ($todayRevenue > 0 ? 100 : 0);

$dailyMap = [];
for ($i=6;$i>=0;$i--) { $date=date('Y-m-d',strtotime("-$i day")); $dailyMap[$date]=0; }
$rows = $pdo->query("SELECT DATE(created_at) ngay,SUM(tong_tien) doanh_thu FROM thanh_toan WHERE created_at>=CURDATE()-INTERVAL 6 DAY GROUP BY DATE(created_at) ORDER BY ngay")->fetchAll();
foreach ($rows as $row) $dailyMap[$row['ngay']] = (float)$row['doanh_thu'];
$chartLabels = array_map(function($d){return date('d/m',strtotime($d));},array_keys($dailyMap));
$chartData = array_values($dailyMap);

$recentPaid = $pdo->query("SELECT t.tong_tien,t.phuong_thuc,t.created_at,b.so_ban,a.ho_ten,a.username FROM thanh_toan t JOIN ban b ON b.id=t.ban_id JOIN admin a ON a.id=t.collected_by ORDER BY t.id DESC LIMIT 7")->fetchAll();
$methodLabels=['tien_mat'=>'Tiền mặt','chuyen_khoan'=>'Chuyển khoản','the'=>'Thẻ','khac'=>'Khác'];
$activePage='owner';
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tổng quan chủ quán</title><link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>"></head><body>
<div class="admin-layout"><?php require __DIR__ . '/_sidebar.php'; ?><main class="admin-content">
    <div class="role-page-header clean-page-header"><div><p class="role-page-kicker">Foodie AI</p><h1>Tổng quan chủ quán</h1><p>Những con số quan trọng nhất của hôm nay.</p></div><div class="online-indicator"><i></i> Trực tuyến</div></div>
    <div class="metrics-grid owner-metric-grid">
        <div class="metric-card"><div class="metric-label">Doanh thu hôm nay</div><div class="metric-value"><?= number_format($todayRevenue,0,',','.') ?>đ</div><div class="metric-sub <?= $growth<0?'expense':'income' ?>"><?= $growth>=0?'+':'' ?><?= number_format($growth,1,',','.') ?>% so với hôm qua</div></div>
        <div class="metric-card"><div class="metric-label">Giá trị hóa đơn TB</div><div class="metric-value"><?= number_format($averageOrder,0,',','.') ?>đ</div><div class="metric-sub"><?= number_format($paidToday) ?> lượt thanh toán</div></div>
        <div class="metric-card"><div class="metric-label">Bàn đang phục vụ</div><div class="metric-value"><?= $activeTables ?> / <?= $totalTables ?></div><div class="metric-sub">Theo trạng thái bàn hiện tại</div></div>
        <div class="metric-card"><div class="metric-label">Đơn đang xử lý</div><div class="metric-value"><?= number_format($processingOrders) ?></div><div class="metric-sub">Đơn mới và bếp đang làm</div></div>
    </div>
    <div class="owner-dashboard-grid">
        <section class="chart-card owner-revenue-chart"><div class="settings-card-head"><div><h3>Doanh thu 7 ngày</h3></div><a href="/admin/reports.php">Xem báo cáo →</a></div><div class="chart-canvas-wrap"><canvas id="ownerRevenueChart"></canvas></div></section>
        <section class="table-card"><div class="settings-card-head"><div><h3>Thanh toán gần đây</h3></div><span>7 giao dịch mới nhất</span></div><div class="owner-transaction-list">
            <?php if (!$recentPaid): ?><div class="empty-box">Chưa có giao dịch.</div><?php endif; ?>
            <?php foreach($recentPaid as $row): ?><div class="owner-transaction"><span>Bàn <?= htmlspecialchars($row['so_ban']) ?><small><?= htmlspecialchars($methodLabels[$row['phuong_thuc']]??$row['phuong_thuc']) ?> · <?= date('H:i',strtotime($row['created_at'])) ?></small></span><strong><?= number_format((float)$row['tong_tien'],0,',','.') ?>đ</strong></div><?php endforeach; ?>
        </div></section>
    </div>
    <div class="owner-quick-grid"><a href="/admin/dashboard.php"><strong>Điều hành đơn</strong><span>Theo dõi tiến độ phục vụ →</span></a><a href="/admin/cashbook.php"><strong>Sổ thu chi & ca</strong><span>Đối soát dòng tiền →</span></a><a href="/admin/settings.php"><strong>Cài đặt thanh toán</strong><span>Ngân hàng và mã QR →</span></a></div>
</main></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script>Chart.defaults.font.family='Be Vietnam Pro';new Chart(document.getElementById('ownerRevenueChart'),{type:'line',data:{labels:<?= json_encode($chartLabels,JSON_UNESCAPED_UNICODE) ?>,datasets:[{data:<?= json_encode($chartData) ?>,borderColor:'#173f35',backgroundColor:'rgba(23,63,53,.07)',fill:true,tension:.45,borderWidth:3,pointRadius:0,pointHoverRadius:5}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return Number(c.raw).toLocaleString('vi-VN')+'đ'}}}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,grid:{color:'rgba(23,63,53,.07)'},ticks:{callback:function(v){return (v/1000000).toLocaleString('vi-VN')+'tr'}}}}}});</script>
</body></html>
