<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/services/SettingService.php';
require_roles(['owner','manager']);

if($_SERVER['REQUEST_METHOD']==='POST'){
    $soBan=trim($_POST['so_ban']??'');
    if($soBan!==''){$stmt=$pdo->prepare("INSERT IGNORE INTO ban(so_ban,trang_thai) VALUES(?,'trong')");$stmt->execute([$soBan]);}
    header('Location:/admin/tables.php?created=1');exit;
}
$baseUrl=SettingService::get($pdo,'site_base_url','');
if($baseUrl===''){$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$baseUrl=$scheme.'://'.($_SERVER['HTTP_HOST']??'localhost');}
$tables=$pdo->query("SELECT * FROM ban ORDER BY CAST(so_ban AS UNSIGNED),so_ban")->fetchAll();
$available=0;$serving=0;$locked=0;foreach($tables as $table){if($table['trang_thai']==='trong')$available++;elseif($table['trang_thai']==='dang_phuc_vu')$serving++;else$locked++;}
$activePage='tables';
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Bàn và mã QR</title><link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>"></head><body>
<div class="admin-layout"><?php require __DIR__.'/_sidebar.php'; ?><main class="admin-content">
    <div class="role-page-header clean-page-header"><div><p class="role-page-kicker">Sơ đồ phục vụ</p><h1>Bàn & mã QR</h1><p>Quản lý trạng thái bàn và đường dẫn order dành riêng cho từng bàn.</p></div><div class="online-indicator"><i></i> <?= count($tables) ?> bàn đã cấu hình</div></div>
    <?php if(isset($_GET['created'])): ?><div class="role-alert success">Đã cập nhật danh sách bàn.</div><?php endif; ?>
    <div class="metrics-grid table-management-metrics"><div class="metric-card"><div class="metric-label">Tổng số bàn</div><div class="metric-value"><?= count($tables) ?></div></div><div class="metric-card"><div class="metric-label">Đang trống</div><div class="metric-value"><?= $available ?></div></div><div class="metric-card"><div class="metric-label">Đang phục vụ</div><div class="metric-value"><?= $serving ?></div></div><div class="metric-card"><div class="metric-label">Tạm khóa</div><div class="metric-value"><?= $locked ?></div></div></div>
    <form method="POST" class="form-card quick-create-bar"><div><label>Thêm bàn mới</label><input name="so_ban" required placeholder="VD: 12 hoặc VIP-01"></div><button type="submit">Thêm bàn</button><small>Mã QR order được tạo tự động sau khi thêm.</small></form>
    <div class="section-title-row"><div><h2>Danh sách bàn</h2><p>Bấm vào đường dẫn để sao chép hoặc quét thử QR.</p></div><a class="btn-light" href="/admin/tables.php">Làm mới</a></div>
    <div class="business-table-grid">
    <?php foreach($tables as $table): $url=rtrim($baseUrl,'/').'/menu.php?table='.urlencode($table['so_ban']); $statusLabel=$table['trang_thai']==='trong'?'Trống':($table['trang_thai']==='dang_phuc_vu'?'Đang phục vụ':'Tạm khóa'); ?>
        <article class="business-table-card <?= htmlspecialchars($table['trang_thai']) ?>"><div class="business-table-head"><div><small>Bàn</small><strong><?= htmlspecialchars($table['so_ban']) ?></strong></div><span><?= $statusLabel ?></span></div><div class="business-table-qr"><img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($url) ?>" alt="QR bàn <?= htmlspecialchars($table['so_ban']) ?>"></div><button type="button" class="table-copy-link" data-copy="<?= htmlspecialchars($url) ?>" onclick="copyTableLink(this)">Sao chép link order</button><div class="business-table-actions"><a class="btn-light" href="<?= htmlspecialchars($url) ?>" target="_blank">Mở menu</a><a class="btn-light" href="/admin/table_toggle.php?id=<?= (int)$table['id'] ?>"><?= $table['trang_thai']==='tam_khoa'?'Mở khóa':'Tạm khóa' ?></a></div></article>
    <?php endforeach; ?>
    </div>
</main></div><script>function copyTableLink(button){navigator.clipboard.writeText(button.dataset.copy).then(function(){const old=button.textContent;button.textContent='Đã sao chép';setTimeout(function(){button.textContent=old},1600)});}</script></body></html>
