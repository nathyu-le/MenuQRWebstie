<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/services/SettingService.php';

require_roles(['owner', 'manager', 'cashier']);

$banId = (int) ($_GET['ban_id'] ?? 0);
if ($banId <= 0) die('Thiếu mã bàn.');

$stmt = $pdo->prepare('SELECT * FROM ban WHERE id=?');
$stmt->execute([$banId]);
$ban = $stmt->fetch();
if (!$ban) die('Không tìm thấy bàn.');

$stmt = $pdo->prepare("SELECT * FROM don_hang WHERE ban_id=? AND trang_thai IN ('moi','dang_lam','da_xong') ORDER BY created_at ASC");
$stmt->execute([$banId]);
$orders = $stmt->fetchAll();
$orderIds = array_column($orders, 'id');
$items = [];
$totalBill = 0;

if ($orderIds) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmt = $pdo->prepare("SELECT ten_mon,gia,SUM(so_luong) tong_so_luong,SUM(thanh_tien) tong_tien FROM chi_tiet_don_hang WHERE don_hang_id IN ($placeholders) GROUP BY ten_mon,gia ORDER BY MIN(id)");
    $stmt->execute($orderIds);
    $items = $stmt->fetchAll();
    foreach ($items as $item) $totalBill += (float) $item['tong_tien'];
}

$hasProcessingOrder = false;
foreach ($orders as $order) {
    if (in_array($order['trang_thai'], ['moi', 'dang_lam'], true)) $hasProcessingOrder = true;
}

$restaurantName = SettingService::get($pdo, 'restaurant_name', 'Foodie AI Restaurant');
$bankEnabled = SettingService::get($pdo, 'bank_transfer_enabled', '0') === '1';
$bankCodeInput = strtoupper(trim(SettingService::get($pdo, 'bank_code', '')));
$bankCodeKey = preg_replace('/[^A-Z0-9]/', '', $bankCodeInput);
$bankAliases = [
    'MBBANK'=>'MB','MILITARYBANK'=>'MB','MB'=>'MB',
    'VIETCOMBANK'=>'VCB','VCB'=>'VCB',
    'TECHCOMBANK'=>'TCB','TCB'=>'TCB',
    'VIETINBANK'=>'ICB','ICB'=>'ICB',
    'BIDV'=>'BIDV','AGRIBANK'=>'VBA','VBA'=>'VBA',
    'ACB'=>'ACB','VPBANK'=>'VPB','VPB'=>'VPB',
    'TPBANK'=>'TPB','TPB'=>'TPB','SACOMBANK'=>'STB','STB'=>'STB',
    'VIB'=>'VIB','HDBANK'=>'HDB','HDB'=>'HDB','OCB'=>'OCB',
    'SHB'=>'SHB','SEABANK'=>'SEAB','SEAB'=>'SEAB','MSB'=>'MSB'
];
$bankCode = $bankAliases[$bankCodeKey] ?? $bankCodeKey;
$bankAccount = SettingService::get($pdo, 'bank_account_number', '');
$bankName = SettingService::get($pdo, 'bank_account_name', '');
$transferPrefix = SettingService::get($pdo, 'bank_transfer_prefix', 'FOODIE');
$qrTemplate = SettingService::get($pdo, 'bank_qr_template', 'compact2');
$transferReady = $bankEnabled && $bankCode !== '' && $bankAccount !== '';
$transferContent = trim($transferPrefix . ' BAN' . $ban['so_ban']);
$qrUrl = $transferReady
    ? 'https://img.vietqr.io/image/' . rawurlencode($bankCode) . '-' . rawurlencode($bankAccount) . '-' . rawurlencode($qrTemplate)
      . '.png?amount=' . rawurlencode((string) round($totalBill))
      . '&addInfo=' . rawurlencode($transferContent)
      . '&accountName=' . rawurlencode($bankName)
    : '';
$activePage = 'cashier';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Thu ngân · Bàn <?= htmlspecialchars($ban['so_ban']) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-content">
        <div class="role-page-header clean-page-header no-print">
            <div><p class="role-page-kicker"><?= htmlspecialchars($restaurantName) ?></p><h1>Thu ngân · Bàn <?= htmlspecialchars($ban['so_ban']) ?></h1><p><?= count($orders) ?> order được gộp trong hóa đơn này.</p></div>
            <div class="online-indicator"><i></i> Trực tuyến</div>
        </div>

        <?php if (!$orders): ?>
            <div class="empty-box">Bàn này hiện không có hóa đơn cần thanh toán.</div>
            <a class="btn-light no-print" href="<?= htmlspecialchars(role_home()) ?>">Quay lại</a>
        <?php else: ?>
        <div class="pos-checkout-layout">
            <section class="pos-bill-card">
                <div class="pos-bill-head"><div><small>Chi tiết hóa đơn</small><h2>Bàn <?= htmlspecialchars($ban['so_ban']) ?></h2></div><button type="button" class="btn-light no-print" onclick="window.print()">In hóa đơn</button></div>
                <div class="pos-receipt-meta"><div><span>Thời gian</span><strong><?= date('H:i · d/m/Y') ?></strong></div><div><span>Số order gộp</span><strong><?= count($orders) ?> order</strong></div><div><span>Trạng thái</span><strong>Chưa thanh toán</strong></div></div>
                <?php if ($hasProcessingOrder): ?><div class="notice no-print">Một số món vẫn đang được bếp xử lý. Hãy kiểm tra trước khi thanh toán.</div><?php endif; ?>
                <div class="pos-item-columns"><span>Món ăn</span><span>Thành tiền</span></div>
                <div class="pos-item-list">
                    <?php foreach ($items as $item): ?>
                    <div class="pos-item-row"><div><strong><?= number_format((int)$item['tong_so_luong']) ?>× <?= htmlspecialchars($item['ten_mon']) ?></strong><small><?= number_format((float)$item['gia'],0,',','.') ?>đ / món</small></div><b><?= number_format((float)$item['tong_tien'],0,',','.') ?>đ</b></div>
                    <?php endforeach; ?>
                </div>
                <div class="pos-receipt-summary"><div><span>Tạm tính</span><strong><?= number_format($totalBill,0,',','.') ?>đ</strong></div><div><span>Phụ thu / giảm giá</span><strong>0đ</strong></div><div class="grand-total"><span>Tổng thanh toán</span><strong><?= number_format($totalBill,0,',','.') ?>đ</strong></div></div>
                <p class="pos-receipt-thanks">Cảm ơn quý khách. Vui lòng kiểm tra hóa đơn trước khi thanh toán.</p>
                <div class="pos-print-meta"><span><?= date('d/m/Y H:i') ?></span><span><?= htmlspecialchars($restaurantName) ?></span></div>
                <?php if ($transferReady): ?>
                <div class="print-transfer-receipt print-only">
                    <div>
                        <strong>Quét QR để chuyển khoản</strong>
                        <span><?= htmlspecialchars($bankCode) ?> · <?= htmlspecialchars($bankAccount) ?></span>
                        <span><?= htmlspecialchars($bankName) ?></span>
                        <span>Nội dung: <?= htmlspecialchars($transferContent) ?></span>
                        <b><?= number_format($totalBill,0,',','.') ?>đ</b>
                    </div>
                    <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR thanh toán chuyển khoản">
                </div>
                <?php endif; ?>
            </section>

            <aside class="pos-payment-card no-print">
                <div class="pos-total-label">Tạm tính</div>
                <div class="pos-total-value"><?= number_format($totalBill,0,',','.') ?>đ</div>
                <form method="POST" action="/admin/table_checkout.php" id="checkout-form" onsubmit="return confirmCheckout()">
                    <?= csrf_field() ?><input type="hidden" name="ban_id" value="<?= (int)$ban['id'] ?>">
                    <p class="payment-section-label">Phương thức</p>
                    <div class="payment-method-grid">
                        <label class="payment-method active"><input type="radio" name="phuong_thuc" value="tien_mat" checked><span>Tiền mặt</span></label>
                        <label class="payment-method <?= $transferReady ? '' : 'disabled' ?>"><input type="radio" name="phuong_thuc" value="chuyen_khoan" <?= $transferReady ? '' : 'disabled' ?>><span>Chuyển khoản</span></label>
                    </div>

                    <div class="cash-payment-panel payment-panel active" data-payment-panel="tien_mat">
                        <label>Khách đưa</label>
                        <input type="number" id="cash-received" min="0" step="1000" placeholder="Nhập số tiền khách đưa">
                        <div class="cash-change"><span>Tiền trả khách</span><strong id="cash-change">0đ</strong></div>
                    </div>

                    <div class="bank-payment-panel payment-panel" data-payment-panel="chuyen_khoan">
                        <?php if ($transferReady): ?>
                            <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR chuyển khoản <?= htmlspecialchars($bankCode) ?>">
                            <div class="bank-transfer-details"><span><?= htmlspecialchars($bankCode) ?> · <?= htmlspecialchars($bankAccount) ?></span><strong><?= htmlspecialchars($bankName) ?></strong><small>Nội dung: <?= htmlspecialchars($transferContent) ?></small></div>
                            <label>Mã giao dịch / tham chiếu</label><input name="ma_tham_chieu" maxlength="100" placeholder="Có thể nhập để đối soát">
                        <?php else: ?>
                            <p>Chủ quán chưa cấu hình chuyển khoản.</p>
                        <?php endif; ?>
                    </div>

                    <label>Ghi chú thanh toán</label>
                    <input name="ghi_chu_thanh_toan" maxlength="255" placeholder="Không bắt buộc">
                    <button type="submit" class="checkout-confirm-btn">Xác nhận thanh toán</button>
                </form>
                <a class="pos-back-link" href="<?= htmlspecialchars(role_home()) ?>">Quay lại danh sách bàn</a>
            </aside>
        </div>
        <?php endif; ?>
    </main>
</div>
<script>
(function () {
    const total = <?= json_encode($totalBill) ?>;
    const radios = document.querySelectorAll('input[name="phuong_thuc"]');
    radios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.payment-method').forEach(function (item) { item.classList.remove('active'); });
            radio.closest('.payment-method').classList.add('active');
            document.querySelectorAll('.payment-panel').forEach(function (panel) { panel.classList.toggle('active', panel.dataset.paymentPanel === radio.value); });
            document.body.classList.toggle('print-bank-transfer', radio.value === 'chuyen_khoan');
        });
    });
    const selectedMethod = document.querySelector('input[name="phuong_thuc"]:checked');
    document.body.classList.toggle('print-bank-transfer', !!selectedMethod && selectedMethod.value === 'chuyen_khoan');
    const received = document.getElementById('cash-received');
    if (received) received.addEventListener('input', function () {
        const change = Math.max(0, Number(received.value || 0) - total);
        document.getElementById('cash-change').textContent = change.toLocaleString('vi-VN') + 'đ';
    });
})();
function confirmCheckout() {
    const method = document.querySelector('input[name="phuong_thuc"]:checked');
    const label = method && method.value === 'chuyen_khoan' ? 'chuyển khoản' : 'tiền mặt';
    return confirm('Xác nhận đã nhận đủ <?= number_format($totalBill,0,',','.') ?>đ bằng ' + label + '?');
}
</script>
</body>
</html>
