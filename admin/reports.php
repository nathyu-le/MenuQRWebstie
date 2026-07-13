<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_roles(['owner', 'manager', 'cashier']);

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_paid_orders,
        COALESCE(SUM(tong_tien), 0) AS total_revenue
    FROM don_hang
    WHERE trang_thai = 'da_thanh_toan'
      AND DATE(created_at) BETWEEN ? AND ?
");

$stmt->execute([$from, $to]);
$summary = $stmt->fetch();

$totalPaidOrders = (int) ($summary['total_paid_orders'] ?? 0);
$totalRevenue = (float) ($summary['total_revenue'] ?? 0);
$avgOrderValue = $totalPaidOrders > 0 ? $totalRevenue / $totalPaidOrders : 0;

/*
|--------------------------------------------------------------------------
| Revenue by day
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) AS ngay,
        COUNT(*) AS so_don,
        COALESCE(SUM(tong_tien), 0) AS doanh_thu
    FROM don_hang
    WHERE trang_thai = 'da_thanh_toan'
      AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY ngay ASC
");

$stmt->execute([$from, $to]);
$dailyRows = $stmt->fetchAll();

$revenueLabels = [];
$revenueData = [];
$orderCountData = [];

foreach ($dailyRows as $row) {
    $revenueLabels[] = date('d/m', strtotime($row['ngay']));
    $revenueData[] = (float) $row['doanh_thu'];
    $orderCountData[] = (int) $row['so_don'];
}

/*
|--------------------------------------------------------------------------
| Top foods
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT 
        ctdh.ten_mon,
        SUM(ctdh.so_luong) AS tong_so_luong,
        SUM(ctdh.thanh_tien) AS tong_doanh_thu
    FROM chi_tiet_don_hang ctdh
    JOIN don_hang dh ON ctdh.don_hang_id = dh.id
    WHERE dh.trang_thai = 'da_thanh_toan'
      AND DATE(dh.created_at) BETWEEN ? AND ?
    GROUP BY ctdh.mon_an_id, ctdh.ten_mon
    ORDER BY tong_so_luong DESC
    LIMIT 10
");

$stmt->execute([$from, $to]);
$topFoods = $stmt->fetchAll();

$topFoodLabels = [];
$topFoodQty = [];

foreach ($topFoods as $food) {
    $topFoodLabels[] = $food['ten_mon'];
    $topFoodQty[] = (int) $food['tong_so_luong'];
}

/*
|--------------------------------------------------------------------------
| Revenue by category
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(ma.danh_muc, 'Khác') AS danh_muc,
        COALESCE(SUM(ctdh.thanh_tien), 0) AS doanh_thu
    FROM chi_tiet_don_hang ctdh
    JOIN don_hang dh ON ctdh.don_hang_id = dh.id
    LEFT JOIN mon_an ma ON ctdh.mon_an_id = ma.id
    WHERE dh.trang_thai = 'da_thanh_toan'
      AND DATE(dh.created_at) BETWEEN ? AND ?
    GROUP BY ma.danh_muc
    ORDER BY doanh_thu DESC
");

$stmt->execute([$from, $to]);
$categoryRows = $stmt->fetchAll();

$categoryLabels = [];
$categoryRevenue = [];

foreach ($categoryRows as $row) {
    $categoryLabels[] = $row['danh_muc'];
    $categoryRevenue[] = (float) $row['doanh_thu'];
}

$bestFoodName = !empty($topFoods) ? $topFoods[0]['ten_mon'] : '--';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo doanh thu - Foodie AI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<div class="admin-layout">
    <?php $activePage = 'reports'; require __DIR__ . '/_sidebar.php'; ?>
    <aside class="admin-sidebar" style="display:none">
        <h2>Foodie AI</h2>
        <p><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></p>

        <a href="/admin/dashboard.php">Dashboard Order</a>
        <a href="/admin/kitchen.php">Màn hình bếp</a>
        <a href="/admin/menu.php">Quản lý menu</a>
        <a href="/admin/tables.php">Quản lý bàn + QR</a>
        <a href="/admin/reports.php" class="active">Báo cáo</a>
        <a href="/admin/chat_history.php">Lịch sử AI</a>
        <a href="/admin/settings.php">Settings AI</a>
        <a href="/admin/logout.php">Đăng xuất</a>
    </aside>

    <main class="admin-content">
        <h1>Báo cáo doanh thu</h1>

        <form method="GET" class="form-card">
            <h3>Bộ lọc thời gian</h3>

            <label>Từ ngày</label>
            <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">

            <label>Đến ngày</label>
            <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
<br>
<br>
            <button type="submit">Xem báo cáo</button>
            <a class="btn-light" href="/admin/reports.php">Tháng này</a>
        </form>
<br>
<br>
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-label">Tổng doanh thu</div>
                <div class="metric-value"><?= number_format($totalRevenue, 0, ',', '.') ?>đ</div>
                <div class="metric-sub">Tính theo đơn đã thanh toán</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">Tổng đơn đã thanh toán</div>
                <div class="metric-value"><?= number_format($totalPaidOrders) ?></div>
                <div class="metric-sub">Số đơn trong khoảng thời gian chọn</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">Giá trị đơn trung bình</div>
                <div class="metric-value"><?= number_format($avgOrderValue, 0, ',', '.') ?>đ</div>
                <div class="metric-sub">Average Order Value</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">Món bán chạy nhất</div>
                <div class="metric-value"><?= htmlspecialchars($bestFoodName) ?></div>
                <div class="metric-sub">Dựa trên số lượng bán</div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3>Doanh thu theo ngày</h3>
                <div class="chart-canvas-wrap">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3>Số đơn theo ngày</h3>
                <div class="chart-canvas-wrap">
                    <canvas id="orderChart"></canvas>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3>Top món bán chạy</h3>
                <div class="chart-canvas-wrap">
                    <canvas id="foodChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3>Doanh thu theo danh mục</h3>
                <div class="chart-canvas-wrap">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="table-card">
            <h3>Chi tiết top món bán chạy</h3>

            <table class="table">
                <tr>
                    <th>Tên món</th>
                    <th>Số lượng bán</th>
                    <th>Doanh thu</th>
                </tr>

                <?php foreach ($topFoods as $food): ?>
                    <tr>
                        <td><?= htmlspecialchars($food['ten_mon']) ?></td>
                        <td><?= number_format((int) $food['tong_so_luong']) ?></td>
                        <td><?= number_format((float) $food['tong_doanh_thu'], 0, ',', '.') ?>đ</td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($topFoods)): ?>
                    <tr>
                        <td colspan="3">Chưa có dữ liệu món bán chạy.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="table-card" style="margin-top: 22px;">
            <h3>Doanh thu theo ngày</h3>

            <table class="table">
                <tr>
                    <th>Ngày</th>
                    <th>Số đơn</th>
                    <th>Doanh thu</th>
                </tr>

                <?php foreach (array_reverse($dailyRows) as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['ngay']))) ?></td>
                        <td><?= number_format((int) $row['so_don']) ?></td>
                        <td><?= number_format((float) $row['doanh_thu'], 0, ',', '.') ?>đ</td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($dailyRows)): ?>
                    <tr>
                        <td colspan="3">Chưa có dữ liệu doanh thu.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const revenueLabels = <?= json_encode($revenueLabels, JSON_UNESCAPED_UNICODE) ?>;
const revenueData = <?= json_encode($revenueData, JSON_UNESCAPED_UNICODE) ?>;
const orderCountData = <?= json_encode($orderCountData, JSON_UNESCAPED_UNICODE) ?>;
const topFoodLabels = <?= json_encode($topFoodLabels, JSON_UNESCAPED_UNICODE) ?>;
const topFoodQty = <?= json_encode($topFoodQty, JSON_UNESCAPED_UNICODE) ?>;
const categoryLabels = <?= json_encode($categoryLabels, JSON_UNESCAPED_UNICODE) ?>;
const categoryRevenue = <?= json_encode($categoryRevenue, JSON_UNESCAPED_UNICODE) ?>;

const blue = '#1e88e5';
const blueDark = '#1565c0';
const blueLight = 'rgba(30, 136, 229, 0.16)';

new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: revenueLabels,
        datasets: [{
            label: 'Doanh thu',
            data: revenueData,
            borderColor: blue,
            backgroundColor: blueLight,
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointBackgroundColor: blueDark
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return Number(context.raw).toLocaleString('vi-VN') + 'đ';
                    }
                }
            }
        },
        scales: {
            y: {
                ticks: {
                    callback: function(value) {
                        return Number(value).toLocaleString('vi-VN') + 'đ';
                    }
                }
            }
        }
    }
});

new Chart(document.getElementById('orderChart'), {
    type: 'bar',
    data: {
        labels: revenueLabels,
        datasets: [{
            label: 'Số đơn',
            data: orderCountData,
            backgroundColor: '#42a5f5',
            borderRadius: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('foodChart'), {
    type: 'bar',
    data: {
        labels: topFoodLabels,
        datasets: [{
            label: 'Số lượng bán',
            data: topFoodQty,
            backgroundColor: ['#0d47a1', '#1565c0', '#1e88e5', '#42a5f5', '#90caf9', '#bbdefb'],
            borderRadius: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y'
    }
});

new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: categoryLabels,
        datasets: [{
            label: 'Doanh thu',
            data: categoryRevenue,
            backgroundColor: ['#0d47a1', '#1565c0', '#1e88e5', '#42a5f5', '#90caf9', '#bbdefb']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + Number(context.raw).toLocaleString('vi-VN') + 'đ';
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>
