<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_roles(['owner', 'manager', 'cashier']);

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');
if ($from > $to) [$from, $to] = [$to, $from];

$stmt = $pdo->prepare("SELECT COUNT(*) so_lan_thanh_toan,COALESCE(SUM(tong_tien),0) doanh_thu FROM thanh_toan WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$summary = $stmt->fetch();
$paymentCount = (int) ($summary['so_lan_thanh_toan'] ?? 0);
$totalRevenue = (float) ($summary['doanh_thu'] ?? 0);
$avgPayment = $paymentCount ? $totalRevenue / $paymentCount : 0;

$stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN loai='chi' THEN so_tien ELSE 0 END),0) tong_chi,COALESCE(SUM(CASE WHEN loai='thu' THEN so_tien ELSE 0 END),0) thu_khac FROM thu_chi WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$cashFlow = $stmt->fetch();
$totalExpense = (float) ($cashFlow['tong_chi'] ?? 0);
$otherIncome = (float) ($cashFlow['thu_khac'] ?? 0);
$netCashFlow = $totalRevenue + $otherIncome - $totalExpense;

$stmt = $pdo->prepare("SELECT DATE(created_at) ngay,COUNT(*) so_giao_dich,COALESCE(SUM(tong_tien),0) doanh_thu FROM thanh_toan WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY ngay");
$stmt->execute([$from, $to]);
$dailyRows = $stmt->fetchAll();
$revenueLabels = array_map(function ($r) { return date('d/m', strtotime($r['ngay'])); }, $dailyRows);
$revenueData = array_map(function ($r) { return (float) $r['doanh_thu']; }, $dailyRows);

$stmt = $pdo->prepare("SELECT phuong_thuc,COUNT(*) so_giao_dich,COALESCE(SUM(tong_tien),0) doanh_thu FROM thanh_toan WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY phuong_thuc ORDER BY doanh_thu DESC");
$stmt->execute([$from, $to]);
$paymentRows = $stmt->fetchAll();
$methodLabels = ['tien_mat'=>'Tiền mặt','chuyen_khoan'=>'Chuyển khoản','the'=>'Thẻ','khac'=>'Khác'];

$stmt = $pdo->prepare("SELECT c.ten_mon,SUM(c.so_luong) tong_so_luong,SUM(c.thanh_tien) tong_doanh_thu FROM chi_tiet_don_hang c JOIN don_hang d ON d.id=c.don_hang_id WHERE d.trang_thai='da_thanh_toan' AND DATE(d.updated_at) BETWEEN ? AND ? GROUP BY c.mon_an_id,c.ten_mon ORDER BY tong_so_luong DESC LIMIT 8");
$stmt->execute([$from, $to]);
$topFoods = $stmt->fetchAll();
$topFoodLabels = array_map(function ($r) { return $r['ten_mon']; }, $topFoods);
$topFoodQty = array_map(function ($r) { return (int) $r['tong_so_luong']; }, $topFoods);
$bestFoodName = $topFoods ? $topFoods[0]['ten_mon'] : 'Chưa có dữ liệu';
$activePage = 'reports';
?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Báo cáo kinh doanh</title><link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>"></head>
<body>
<div class="admin-layout">
<?php require __DIR__ . '/_sidebar.php'; ?>
<main class="admin-content">
    <div class="role-page-header clean-page-header"><div><p class="role-page-kicker">Phân tích vận hành</p><h1>Báo cáo kinh doanh</h1><p>Doanh thu, dòng tiền và món bán chạy trong một màn hình.</p></div><div class="online-indicator"><i></i> Dữ liệu trực tiếp</div></div>

    <form method="GET" class="form-card report-filter-bar">
        <div><label>Từ ngày</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></div>
        <div><label>Đến ngày</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></div>
        <button type="submit">Áp dụng</button><a class="btn-light" href="/admin/reports.php">Tháng này</a>
    </form>

    <div class="metrics-grid owner-metric-grid">
        <div class="metric-card"><div class="metric-label">Doanh thu</div><div class="metric-value"><?= number_format($totalRevenue,0,',','.') ?>đ</div><div class="metric-sub"><?= number_format($paymentCount) ?> lần thanh toán</div></div>
        <div class="metric-card"><div class="metric-label">Giá trị hóa đơn TB</div><div class="metric-value"><?= number_format($avgPayment,0,',','.') ?>đ</div><div class="metric-sub">Tính theo lượt thanh toán bàn</div></div>
        <div class="metric-card"><div class="metric-label">Tổng chi</div><div class="metric-value expense"><?= number_format($totalExpense,0,',','.') ?>đ</div><div class="metric-sub">Chi phí đã ghi trong sổ</div></div>
        <div class="metric-card"><div class="metric-label">Dòng tiền ròng</div><div class="metric-value"><?= number_format($netCashFlow,0,',','.') ?>đ</div><div class="metric-sub">Doanh thu + thu khác − chi</div></div>
    </div>

    <div class="report-main-grid">
        <section class="chart-card report-main-chart"><div class="settings-card-head"><div><h3>Xu hướng doanh thu</h3></div><span><?= date('d/m/Y',strtotime($from)) ?> – <?= date('d/m/Y',strtotime($to)) ?></span></div><div class="chart-canvas-wrap"><canvas id="revenueChart"></canvas></div></section>
        <section class="chart-card"><div class="settings-card-head"><div><h3>Phương thức thanh toán</h3></div><span><?= number_format($paymentCount) ?> giao dịch</span></div><div class="payment-breakdown">
            <?php if (!$paymentRows): ?><p class="empty-box">Chưa có thanh toán.</p><?php endif; ?>
            <?php foreach ($paymentRows as $row): $percent=$totalRevenue>0?((float)$row['doanh_thu']/$totalRevenue*100):0; ?>
            <div class="payment-breakdown-row"><div><span><?= htmlspecialchars($methodLabels[$row['phuong_thuc']] ?? $row['phuong_thuc']) ?> · <?= (int)$row['so_giao_dich'] ?> lần</span><strong><?= number_format((float)$row['doanh_thu'],0,',','.') ?>đ</strong></div><i style="--w:<?= round($percent,1) ?>%"></i></div>
            <?php endforeach; ?>
        </div></section>
    </div>

    <div class="report-secondary-grid">
        <section class="chart-card"><div class="settings-card-head"><div><h3>Món bán chạy</h3></div><span><?= htmlspecialchars($bestFoodName) ?></span></div><div class="chart-canvas-wrap"><canvas id="foodChart"></canvas></div></section>
        <section class="table-card"><h3>Hiệu quả theo món</h3><div class="responsive-table"><table class="table"><thead><tr><th>Món</th><th>Đã bán</th><th>Doanh thu</th></tr></thead><tbody>
            <?php if (!$topFoods): ?><tr><td colspan="3">Chưa có dữ liệu.</td></tr><?php endif; ?>
            <?php foreach ($topFoods as $food): ?><tr><td><strong><?= htmlspecialchars($food['ten_mon']) ?></strong></td><td><?= number_format((int)$food['tong_so_luong']) ?></td><td><?= number_format((float)$food['tong_doanh_thu'],0,',','.') ?>đ</td></tr><?php endforeach; ?>
        </tbody></table></div></section>
    </div>
</main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
Chart.defaults.font.family='Be Vietnam Pro'; Chart.defaults.color='#718079';
const green='#173f35', grid='rgba(23,63,53,.08)';
new Chart(document.getElementById('revenueChart'),{type:'line',data:{labels:<?= json_encode($revenueLabels,JSON_UNESCAPED_UNICODE) ?>,datasets:[{data:<?= json_encode($revenueData) ?>,borderColor:green,backgroundColor:'rgba(23,63,53,.08)',fill:true,tension:.42,borderWidth:3,pointRadius:2,pointHoverRadius:5}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return Number(c.raw).toLocaleString('vi-VN')+'đ'}}}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,grid:{color:grid},ticks:{callback:function(v){return (v/1000000).toLocaleString('vi-VN')+'tr'}}}}}});
new Chart(document.getElementById('foodChart'),{type:'bar',data:{labels:<?= json_encode($topFoodLabels,JSON_UNESCAPED_UNICODE) ?>,datasets:[{data:<?= json_encode($topFoodQty) ?>,backgroundColor:green,borderRadius:7,barThickness:15}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,grid:{color:grid}},y:{grid:{display:false}}}}});
</script>
</body></html>
