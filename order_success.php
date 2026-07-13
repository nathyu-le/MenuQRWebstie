<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/SettingService.php';

$maDon=trim($_GET['ma_don']??'');
$soBan=$_SESSION['so_ban']??'Chưa chọn';
$restaurantName=SettingService::get($pdo,'restaurant_name','Foodie AI Restaurant');
$order=null;
if($maDon!==''){$stmt=$pdo->prepare("SELECT id,tong_tien,trang_thai,created_at FROM don_hang WHERE ma_don=? LIMIT 1");$stmt->execute([$maDon]);$order=$stmt->fetch();}
$status=$order['trang_thai']??'moi';
$statusLabels=['moi'=>'Đã gửi về bếp','dang_lam'=>'Bếp đang chuẩn bị','da_xong'=>'Món đã sẵn sàng','da_thanh_toan'=>'Đã thanh toán','huy'=>'Đơn đã hủy'];
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Order thành công · <?= htmlspecialchars($restaurantName) ?></title><meta name="description" content="Đơn hàng đã được gửi về bếp thành công."><link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>"></head><body class="customer-success-page">
<main class="success-shell-v2">
    <header class="success-brand"><a href="/menu.php"><span>FA</span><strong><?= htmlspecialchars($restaurantName) ?></strong></a><div class="online-indicator"><i></i> Đã kết nối với bếp</div></header>
    <section class="success-hero-v2">
        <div class="success-checkmark">✓</div><p class="success-kicker">ORDER CONFIRMED</p><h1>Order đã được gửi<br>tới gian bếp.</h1><p class="success-lead">Bếp đã nhận thông tin và sẽ chuẩn bị món cho bàn của bạn. Bạn có thể gọi thêm bất cứ lúc nào; hóa đơn vẫn được gộp theo bàn.</p>
        <div class="success-order-ticket"><div><span>Mã order</span><strong><?= htmlspecialchars($maDon?:'Đang cập nhật') ?></strong></div><div><span>Bàn</span><strong><?= htmlspecialchars($soBan) ?></strong></div><div><span>Tổng order</span><strong><?= $order?number_format((float)$order['tong_tien'],0,',','.').'đ':'—' ?></strong></div></div>
        <div class="success-actions-v2"><a class="success-primary" href="/menu.php">Tiếp tục gọi món</a><a class="success-secondary" href="/cart.php">Xem giỏ hàng</a></div>
    </section>
    <aside class="success-progress-card">
        <div class="success-progress-head"><div><small>Trạng thái trực tiếp</small><h2 id="order-status-label"><?= htmlspecialchars($statusLabels[$status]??$status) ?></h2></div><span id="order-live-dot"></span></div>
        <div class="success-progress-line" id="order-progress" data-status="<?= htmlspecialchars($status) ?>">
            <article data-step="moi"><i>1</i><div><strong>Đã tiếp nhận</strong><p>Order đã vào hệ thống bếp.</p></div></article>
            <article data-step="dang_lam"><i>2</i><div><strong>Đang chế biến</strong><p>Đầu bếp bắt đầu chuẩn bị món.</p></div></article>
            <article data-step="da_xong"><i>3</i><div><strong>Sẵn sàng phục vụ</strong><p>Món sẽ được mang ra bàn.</p></div></article>
        </div>
        <div class="success-note-v2"><strong>Cần hỗ trợ?</strong><p>Vui lòng gọi nhân viên tại bàn nếu bạn cần thay đổi hoặc hủy món.</p></div>
    </aside>
</main>
<nav class="customer-mobile-dock success-mobile-dock" aria-label="Điều hướng sau order"><a href="/menu.php"><i>+</i><span>Gọi thêm</span></a><a href="/cart.php"><i>Bag</i><span>Giỏ hàng</span></a><a class="active" href="/order_success.php?ma_don=<?= urlencode($maDon) ?>"><i>✓</i><span>Đơn hiện tại</span></a></nav>
<script>
const orderCode=<?= json_encode($maDon,JSON_UNESCAPED_UNICODE) ?>;
const labels={moi:'Đã gửi về bếp',dang_lam:'Bếp đang chuẩn bị',da_xong:'Món đã sẵn sàng',da_thanh_toan:'Đã thanh toán',huy:'Đơn đã hủy'};
function paintOrderStatus(status){const order=['moi','dang_lam','da_xong'];const index=order.indexOf(status);document.getElementById('order-status-label').textContent=labels[status]||status;document.getElementById('order-progress').dataset.status=status;document.querySelectorAll('[data-step]').forEach(function(step,i){step.classList.toggle('active',i<=index||(status==='da_thanh_toan'));});}
paintOrderStatus(<?= json_encode($status) ?>);
if(orderCode)setInterval(function(){fetch('/api/order_status.php?ma_don='+encodeURIComponent(orderCode)+'&t='+Date.now(),{cache:'no-store'}).then(function(r){return r.json()}).then(function(data){if(data.success)paintOrderStatus(data.status)}).catch(function(){})},5000);
</script></body></html>
