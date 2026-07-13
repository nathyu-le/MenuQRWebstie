<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_roles(['owner','manager','cashier']);

$tables=$pdo->query("SELECT b.id,b.so_ban,b.trang_thai,COUNT(d.id) so_don,COALESCE(SUM(d.tong_tien),0) tong_tien,SUM(CASE WHEN d.trang_thai='da_xong' THEN 1 ELSE 0 END) don_san_sang,SUM(CASE WHEN d.trang_thai IN ('moi','dang_lam') THEN 1 ELSE 0 END) don_dang_xu_ly FROM ban b LEFT JOIN don_hang d ON d.ban_id=b.id AND d.trang_thai IN ('moi','dang_lam','da_xong') GROUP BY b.id,b.so_ban,b.trang_thai ORDER BY CAST(b.so_ban AS UNSIGNED),b.so_ban")->fetchAll();
$stmt=$pdo->query("SELECT COALESCE(SUM(tong_tien),0) doanh_thu,COUNT(*) so_luot,COALESCE(SUM(CASE WHEN phuong_thuc='tien_mat' THEN tong_tien ELSE 0 END),0) tien_mat,COALESCE(SUM(CASE WHEN phuong_thuc='chuyen_khoan' THEN tong_tien ELSE 0 END),0) chuyen_khoan FROM thanh_toan WHERE DATE(created_at)=CURDATE()");
$today=$stmt->fetch();
$stmt=$pdo->prepare("SELECT id FROM ca_thu_ngan WHERE opened_by=? AND trang_thai='dang_mo' ORDER BY id DESC LIMIT 1");$stmt->execute([current_admin_id()]);$hasOpenShift=(bool)$stmt->fetchColumn();
$waitingTables=0;foreach($tables as $row)if((int)$row['so_don']>0)$waitingTables++;
$activePage='cashier';
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Thu ngân</title><link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>"></head><body>
<div class="admin-layout"><?php require __DIR__ . '/_sidebar.php'; ?><main class="admin-content">
    <div class="role-page-header clean-page-header"><div><p class="role-page-kicker">Foodie AI</p><h1>Thu ngân & thanh toán</h1><p>Chọn bàn để kiểm tra hóa đơn và nhận thanh toán.</p></div><div class="online-indicator"><i></i> Trực tuyến</div></div>
    <?php if(isset($_GET['paid'])): ?><div class="role-alert success">Đã ghi nhận thanh toán <?= ($_GET['method']??'')==='chuyen_khoan'?'chuyển khoản':'tiền mặt' ?> và trả bàn về trạng thái trống.</div><?php endif; ?>
    <?php if(current_admin_role()==='cashier'&&!$hasOpenShift): ?><div class="role-alert error">Bạn chưa mở ca. <a href="/admin/cashbook.php">Mở ca thu ngân</a> trước khi nhận thanh toán.</div><?php endif; ?>
    <div class="metrics-grid cashier-metrics cashier-payment-metrics">
        <div class="metric-card"><div class="metric-label">Doanh thu hôm nay</div><div class="metric-value"><?= number_format((float)$today['doanh_thu'],0,',','.') ?>đ</div><div class="metric-sub"><?= (int)$today['so_luot'] ?> lượt thanh toán</div></div>
        <div class="metric-card"><div class="metric-label">Tiền mặt</div><div class="metric-value"><?= number_format((float)$today['tien_mat'],0,',','.') ?>đ</div><div class="metric-sub">Cần đối soát cuối ca</div></div>
        <div class="metric-card"><div class="metric-label">Chuyển khoản</div><div class="metric-value"><?= number_format((float)$today['chuyen_khoan'],0,',','.') ?>đ</div><div class="metric-sub">Giao dịch không tiền mặt</div></div>
        <div class="metric-card"><div class="metric-label">Bàn chờ thanh toán</div><div class="metric-value"><?= $waitingTables ?></div><div class="metric-sub">Có order chưa thanh toán</div></div>
    </div>
    <div class="section-title-row"><div><h2>Sơ đồ thanh toán</h2><p>Bàn xanh đậm đang có hóa đơn.</p></div><a class="btn-light" href="/admin/cashier.php">Làm mới</a></div>
    <div class="cashier-table-grid">
    <?php foreach($tables as $table): $hasBill=(int)$table['so_don']>0;$processing=(int)$table['don_dang_xu_ly']>0; ?>
        <article class="cashier-table-card <?= $hasBill?'has-bill':'empty' ?>"><div class="cashier-table-top"><span>Bàn</span><strong><?= htmlspecialchars($table['so_ban']) ?></strong><i class="<?= $processing?'processing':($hasBill?'ready':'') ?>"><?= $processing?'Đang phục vụ':($hasBill?'Sẵn sàng':'Trống') ?></i></div>
        <?php if($hasBill): ?><div class="cashier-table-info"><span><?= (int)$table['so_don'] ?> order</span><strong><?= number_format((float)$table['tong_tien'],0,',','.') ?>đ</strong></div><a class="btn" href="/admin/invoice_table.php?ban_id=<?= (int)$table['id'] ?>">Mở hóa đơn</a><?php else: ?><p>Chưa có order cần thanh toán.</p><?php endif; ?></article>
    <?php endforeach; ?>
    </div>
</main></div></body></html>
