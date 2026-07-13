<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_roles(['owner','manager']);

$status=trim($_GET['status']??'');
$allowed=['moi','dang_lam','da_xong','da_thanh_toan','huy'];
if(!in_array($status,$allowed,true))$status='';
$labels=['moi'=>'Đơn mới','dang_lam'=>'Đang làm','da_xong'=>'Đã xong','da_thanh_toan'=>'Đã thanh toán','huy'=>'Đã hủy'];
$editable=['moi','dang_lam','da_xong','huy'];

$counts=['moi'=>0,'dang_lam'=>0,'da_xong'=>0];
foreach($pdo->query("SELECT trang_thai,COUNT(*) so_luong FROM don_hang WHERE trang_thai IN ('moi','dang_lam','da_xong') GROUP BY trang_thai")->fetchAll() as $row)$counts[$row['trang_thai']]=(int)$row['so_luong'];
$activeTables=(int)$pdo->query("SELECT COUNT(*) FROM ban WHERE trang_thai='dang_phuc_vu'")->fetchColumn();
$totalTables=(int)$pdo->query("SELECT COUNT(*) FROM ban")->fetchColumn();
$sql="SELECT d.id,d.ban_id,d.ma_don,d.tong_tien,d.trang_thai,d.ghi_chu,d.created_at,b.so_ban FROM don_hang d JOIN ban b ON b.id=d.ban_id";
if($status!==''){$sql.=' WHERE d.trang_thai=?';$stmt=$pdo->prepare($sql.' ORDER BY d.id DESC LIMIT 50');$stmt->execute([$status]);}else{$stmt=$pdo->query($sql.' ORDER BY d.id DESC LIMIT 50');}
$orders=$stmt->fetchAll();$activePage='dashboard';
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Điều hành ca</title><link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>"></head><body>
<div class="admin-layout"><?php require __DIR__ . '/_sidebar.php'; ?><main class="admin-content">
    <div class="role-page-header clean-page-header"><div><p class="role-page-kicker">Foodie AI</p><h1>Điều hành ca</h1><p>Theo dõi đơn hàng và tình trạng phục vụ theo thời gian thực.</p></div><div class="online-indicator"><i></i> Trực tuyến</div></div>
    <div class="metrics-grid shift-metrics"><div class="metric-card"><div class="metric-label">Bàn đang phục vụ</div><div class="metric-value"><?= $activeTables ?> / <?= $totalTables ?></div></div><div class="metric-card"><div class="metric-label">Đơn mới</div><div class="metric-value"><?= $counts['moi'] ?></div></div><div class="metric-card"><div class="metric-label">Đang làm</div><div class="metric-value"><?= $counts['dang_lam'] ?></div></div><div class="metric-card"><div class="metric-label">Chờ phục vụ</div><div class="metric-value"><?= $counts['da_xong'] ?></div></div></div>
    <div class="order-toolbar"><div class="order-filter-chips"><a class="<?= $status===''?'active':'' ?>" href="/admin/dashboard.php">Tất cả</a><?php foreach($allowed as $key): ?><a class="<?= $status===$key?'active':'' ?>" href="?status=<?= $key ?>"><?= htmlspecialchars($labels[$key]) ?></a><?php endforeach; ?></div><a class="btn-light" href="/admin/dashboard.php<?= $status!==''?'?status='.urlencode($status):'' ?>">Làm mới</a></div>
    <section class="table-card"><div class="settings-card-head"><div><h3>Đơn hàng gần đây</h3></div><span><?= count($orders) ?> kết quả</span></div><div class="responsive-table"><table class="table order-ops-table"><thead><tr><th>Đơn / bàn</th><th>Giá trị</th><th>Trạng thái</th><th>Ghi chú</th><th>Thời gian</th><th>Thao tác</th></tr></thead><tbody>
    <?php if(!$orders): ?><tr><td colspan="6">Chưa có đơn hàng.</td></tr><?php endif; ?>
    <?php foreach($orders as $order): ?><tr><td><strong><?= htmlspecialchars($order['ma_don']) ?></strong><small class="ledger-method">Bàn <?= htmlspecialchars($order['so_ban']) ?></small></td><td><strong><?= number_format((float)$order['tong_tien'],0,',','.') ?>đ</strong></td><td><span class="status-badge status-<?= htmlspecialchars($order['trang_thai']) ?>"><?= htmlspecialchars($labels[$order['trang_thai']]??$order['trang_thai']) ?></span></td><td><?= htmlspecialchars($order['ghi_chu']?:'—') ?></td><td><?= date('H:i d/m',strtotime($order['created_at'])) ?></td><td><div class="order-row-actions"><a class="btn-light" href="/admin/order_detail.php?id=<?= (int)$order['id'] ?>">Xem</a><?php if(!in_array($order['trang_thai'],['da_thanh_toan','huy'],true)): ?><form method="POST" action="/admin/order_update.php"><input type="hidden" name="id" value="<?= (int)$order['id'] ?>"><select name="trang_thai"><?php foreach($editable as $key): ?><option value="<?= $key ?>" <?= $order['trang_thai']===$key?'selected':'' ?>><?= htmlspecialchars($labels[$key]) ?></option><?php endforeach; ?></select><button type="submit">Lưu</button></form><?php endif; ?><a class="btn-light" href="/admin/invoice_table.php?ban_id=<?= (int)$order['ban_id'] ?>">Tính tiền</a></div></td></tr><?php endforeach; ?>
    </tbody></table></div></section>
</main></div></body></html>
