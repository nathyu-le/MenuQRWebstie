<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_roles(['owner', 'manager', 'cashier']);

$userId = current_admin_id();
$role = current_admin_role();
$employeeName = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Nhân viên';
$currentHour = (int) date('G');
$suggestedShift = $currentHour < 12 ? 'sang' : ($currentHour < 18 ? 'chieu' : 'toi');
$message = '';
$error = '';

function get_open_shift(PDO $pdo, int $userId)
{
    $stmt = $pdo->prepare("SELECT * FROM ca_thu_ngan WHERE opened_by=? AND trang_thai='dang_mo' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function cashbook_limit(string $value, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
}

$openShift = get_open_shift($pdo, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = trim($_POST['action'] ?? '');
    try {
        if ($action === 'open_shift') {
            if ($openShift) throw new RuntimeException('Bạn đang có một ca chưa chốt.');
            $openingCash = (float) ($_POST['opening_cash'] ?? 0);
            $shiftEmployeeName = trim($_POST['ten_nhan_vien'] ?? '');
            $shiftPeriod = trim($_POST['ca_lam'] ?? '');
            if ($openingCash < 0) throw new RuntimeException('Tiền đầu ca không hợp lệ.');
            if ($shiftEmployeeName === '') throw new RuntimeException('Vui lòng nhập tên nhân viên trực ca.');
            if (!in_array($shiftPeriod, ['sang', 'chieu', 'toi'], true)) throw new RuntimeException('Vui lòng chọn ca làm việc.');
            $stmt = $pdo->prepare("INSERT INTO ca_thu_ngan (opened_by, ten_nhan_vien, ca_lam, opening_cash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, cashbook_limit($shiftEmployeeName, 150), $shiftPeriod, $openingCash]);
            $message = 'Đã mở ca thu ngân.';
        } elseif ($action === 'add_transaction') {
            $type = trim($_POST['loai'] ?? '');
            $category = trim($_POST['danh_muc'] ?? '');
            $amount = (float) ($_POST['so_tien'] ?? 0);
            $note = trim($_POST['ghi_chu'] ?? '');
            if (!in_array($type, ['thu', 'chi'], true) || $category === '' || $amount <= 0) {
                throw new RuntimeException('Vui lòng nhập đầy đủ loại, danh mục và số tiền.');
            }
            if ($role === 'cashier' && !$openShift) {
                throw new RuntimeException('Thu ngân cần mở ca trước khi ghi nhận thu chi.');
            }
            $stmt = $pdo->prepare("INSERT INTO thu_chi (ca_id, loai, danh_muc, so_tien, ghi_chu, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$openShift['id'] ?? null, $type, cashbook_limit($category, 100), $amount, cashbook_limit($note, 500), $userId]);
            $message = $type === 'thu' ? 'Đã ghi nhận khoản thu.' : 'Đã ghi nhận khoản chi.';
        } elseif ($action === 'close_shift') {
            if (!$openShift) throw new RuntimeException('Không có ca đang mở để chốt.');
            $closingCash = (float) ($_POST['closing_cash'] ?? -1);
            $note = trim($_POST['ghi_chu_ca'] ?? '');
            if ($closingCash < 0) throw new RuntimeException('Tiền thực tế cuối ca không hợp lệ.');

            $stmt = $pdo->prepare("SELECT COALESCE(SUM(tong_tien),0) FROM thanh_toan WHERE ca_id=? AND phuong_thuc='tien_mat'");
            $stmt->execute([$openShift['id']]);
            $cashPayments = (float) $stmt->fetchColumn();
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN loai='thu' THEN so_tien ELSE 0 END),0), COALESCE(SUM(CASE WHEN loai='chi' THEN so_tien ELSE 0 END),0) FROM thu_chi WHERE ca_id=?");
            $stmt->execute([$openShift['id']]);
            [$manualIncome, $manualExpense] = array_map('floatval', $stmt->fetch(PDO::FETCH_NUM));
            $expected = (float) $openShift['opening_cash'] + $cashPayments + $manualIncome - $manualExpense;
            $difference = $closingCash - $expected;

            $stmt = $pdo->prepare("UPDATE ca_thu_ngan SET closed_by=?, closing_cash=?, expected_cash=?, discrepancy=?, closed_at=NOW(), trang_thai='da_dong', ghi_chu=? WHERE id=? AND trang_thai='dang_mo'");
            $stmt->execute([$userId, $closingCash, $expected, $difference, cashbook_limit($note, 500), $openShift['id']]);
            $message = 'Đã chốt ca. Chênh lệch: ' . number_format($difference, 0, ',', '.') . 'đ.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    $openShift = get_open_shift($pdo, $userId);
}

$scopePayment = $role === 'cashier' ? ' AND tt.collected_by = ?' : '';
$scopeCash = $role === 'cashier' ? ' AND tc.created_by = ?' : '';
$params = $role === 'cashier' ? [$userId] : [];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(tt.tong_tien),0) FROM thanh_toan tt WHERE DATE(tt.created_at)=CURDATE()" . $scopePayment);
$stmt->execute($params);
$paymentToday = (float) $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tc.loai='thu' THEN tc.so_tien ELSE 0 END),0), COALESCE(SUM(CASE WHEN tc.loai='chi' THEN tc.so_tien ELSE 0 END),0) FROM thu_chi tc WHERE DATE(tc.created_at)=CURDATE()" . $scopeCash);
$stmt->execute($params);
[$manualIncomeToday, $expenseToday] = array_map('floatval', $stmt->fetch(PDO::FETCH_NUM));

$stmt = $pdo->prepare("SELECT tt.id, 'thanh_toan' AS loai, CONCAT('Thanh toán bàn ', b.so_ban) AS danh_muc, tt.tong_tien AS so_tien, tt.phuong_thuc, tt.created_at, a.ho_ten, a.username FROM thanh_toan tt JOIN ban b ON b.id=tt.ban_id JOIN admin a ON a.id=tt.collected_by WHERE 1=1" . $scopePayment . " ORDER BY tt.created_at DESC LIMIT 30");
$stmt->execute($params);
$payments = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT tc.id, tc.loai, tc.danh_muc, tc.so_tien, NULL AS phuong_thuc, tc.created_at, a.ho_ten, a.username FROM thu_chi tc JOIN admin a ON a.id=tc.created_by WHERE 1=1" . $scopeCash . " ORDER BY tc.created_at DESC LIMIT 30");
$stmt->execute($params);
$manualRows = $stmt->fetchAll();
$ledger = array_merge($payments, $manualRows);
usort($ledger, function ($a, $b) { return strcmp($b['created_at'], $a['created_at']); });
$ledger = array_slice($ledger, 0, 40);

$shiftScope = $role === 'cashier' ? ' WHERE c.opened_by = ?' : '';
$stmt = $pdo->prepare("SELECT c.*, a.ho_ten, a.username FROM ca_thu_ngan c JOIN admin a ON a.id=c.opened_by" . $shiftScope . " ORDER BY c.id DESC LIMIT 12");
$stmt->execute($params);
$shifts = $stmt->fetchAll();
$activePage = 'cashbook';
$shiftLabels = ['sang' => 'Ca sáng', 'chieu' => 'Ca chiều', 'toi' => 'Ca tối'];
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sổ thu chi và ca</title><link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>"></head><body>
<div class="admin-layout"><?php require __DIR__ . '/_sidebar.php'; ?><main class="admin-content">
    <div class="role-page-header"><div><p class="role-page-kicker">Kiểm soát dòng tiền</p><h1>Sổ thu chi & chốt ca</h1><p>Ghi nhận tiền vào, tiền ra và đối soát tiền mặt cuối ca.</p></div><a class="btn-light" href="/admin/cashbook.php">Làm mới</a></div>
    <?php if ($message): ?><div class="role-alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="role-alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="metrics-grid cashbook-metrics"><div class="metric-card"><div class="metric-label">Thanh toán hôm nay</div><div class="metric-value"><?= number_format($paymentToday,0,',','.') ?>đ</div></div><div class="metric-card"><div class="metric-label">Thu khác</div><div class="metric-value income"><?= number_format($manualIncomeToday,0,',','.') ?>đ</div></div><div class="metric-card"><div class="metric-label">Chi hôm nay</div><div class="metric-value expense"><?= number_format($expenseToday,0,',','.') ?>đ</div></div><div class="metric-card"><div class="metric-label">Dòng tiền ròng</div><div class="metric-value"><?= number_format($paymentToday+$manualIncomeToday-$expenseToday,0,',','.') ?>đ</div></div></div>
    <div class="cashbook-actions">
        <section class="form-card shift-card"><h3><?= $openShift ? 'Ca đang mở' : 'Mở ca mới' ?></h3><?php if ($openShift): ?><div class="shift-identity"><div><span>Nhân viên</span><strong><?= htmlspecialchars($openShift['ten_nhan_vien'] ?: $employeeName) ?></strong></div><div><span>Ca làm việc</span><strong><?= htmlspecialchars($shiftLabels[$openShift['ca_lam']] ?? 'Chưa xác định') ?></strong></div><div><span>Mở lúc</span><strong><?= htmlspecialchars($openShift['opened_at']) ?></strong></div></div><div class="shift-opening"><span>Tiền đầu ca</span><strong><?= number_format((float)$openShift['opening_cash'],0,',','.') ?>đ</strong></div><form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="close_shift"><label>Tiền mặt thực tế cuối ca</label><input type="number" name="closing_cash" min="0" step="1000" required><label>Ghi chú chốt ca</label><textarea name="ghi_chu_ca" rows="2"></textarea><button type="submit" onclick="return confirm('Xác nhận chốt ca hiện tại?')">Chốt ca</button></form><?php else: ?><form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="open_shift"><label>Tên nhân viên trực ca</label><input type="text" name="ten_nhan_vien" maxlength="150" value="<?= htmlspecialchars($employeeName) ?>" placeholder="Nhập họ tên nhân viên" required><small class="field-help">Có thể sửa tên gợi ý trước khi mở ca</small><label>Ca làm việc</label><select name="ca_lam" required><option value="sang" <?= $suggestedShift==='sang'?'selected':'' ?>>Ca sáng</option><option value="chieu" <?= $suggestedShift==='chieu'?'selected':'' ?>>Ca chiều</option><option value="toi" <?= $suggestedShift==='toi'?'selected':'' ?>>Ca tối</option></select><label>Tiền mặt đầu ca</label><input type="number" name="opening_cash" min="0" step="1000" value="0" required><button type="submit">Mở ca</button></form><?php endif; ?></section>
        <form method="POST" class="form-card"><h3>Ghi nhận thu chi</h3><?= csrf_field() ?><input type="hidden" name="action" value="add_transaction"><label>Loại giao dịch</label><select name="loai" required><option value="chi">Khoản chi</option><option value="thu">Khoản thu khác</option></select><label>Danh mục</label><select name="danh_muc" required><option value="Mua nguyên liệu">Mua nguyên liệu</option><option value="Điện nước">Điện nước</option><option value="Tạm ứng nhân viên">Tạm ứng nhân viên</option><option value="Chi phí vận chuyển">Chi phí vận chuyển</option><option value="Thu khác">Thu khác</option><option value="Chi khác">Chi khác</option></select><label>Số tiền</label><input type="number" name="so_tien" min="1" step="1000" required><label>Ghi chú</label><textarea name="ghi_chu" rows="2"></textarea><button type="submit">Lưu giao dịch</button></form>
    </div>
    <div class="cashbook-grid"><section class="table-card"><h3>Giao dịch gần đây</h3><div class="responsive-table"><table class="table"><thead><tr><th>Loại</th><th>Nội dung</th><th>Người ghi</th><th>Số tiền</th><th>Thời gian</th></tr></thead><tbody><?php if (!$ledger): ?><tr><td colspan="5">Chưa có giao dịch.</td></tr><?php endif; ?><?php foreach ($ledger as $row): $isExpense=$row['loai']==='chi'; ?><tr><td><span class="ledger-type <?= $isExpense?'expense':'income' ?>"><?= $isExpense?'Chi':($row['loai']==='thanh_toan'?'Thanh toán':'Thu') ?></span></td><td><strong><?= htmlspecialchars($row['danh_muc']) ?></strong><?php if ($row['phuong_thuc']): ?><small class="ledger-method"><?= htmlspecialchars(str_replace('_',' ',$row['phuong_thuc'])) ?></small><?php endif; ?></td><td><?= htmlspecialchars($row['ho_ten'] ?: $row['username']) ?></td><td class="money <?= $isExpense?'expense':'income' ?>"><?= $isExpense?'-':'+' ?><?= number_format((float)$row['so_tien'],0,',','.') ?>đ</td><td><?= htmlspecialchars($row['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
    <section class="table-card"><h3>Lịch sử ca</h3><div class="responsive-table"><table class="table"><thead><tr><th>Nhân viên / ca</th><th>Trạng thái</th><th>Đầu ca</th><th>Kỳ vọng</th><th>Thực tế</th><th>Lệch</th></tr></thead><tbody><?php foreach ($shifts as $shift): ?><tr><td><strong><?= htmlspecialchars($shift['ten_nhan_vien'] ?: ($shift['ho_ten'] ?: $shift['username'])) ?></strong><small class="ledger-method"><?= htmlspecialchars($shiftLabels[$shift['ca_lam']] ?? 'Ca cũ') ?> · <?= htmlspecialchars($shift['opened_at']) ?></small></td><td><span class="role-badge <?= $shift['trang_thai']==='dang_mo'?'role-cashier':'role-manager' ?>"><?= $shift['trang_thai']==='dang_mo'?'Đang mở':'Đã chốt' ?></span></td><td><?= number_format((float)$shift['opening_cash'],0,',','.') ?>đ</td><td><?= $shift['expected_cash']===null?'—':number_format((float)$shift['expected_cash'],0,',','.').'đ' ?></td><td><?= $shift['closing_cash']===null?'—':number_format((float)$shift['closing_cash'],0,',','.').'đ' ?></td><td class="money <?= (float)$shift['discrepancy']<0?'expense':'income' ?>"><?= $shift['discrepancy']===null?'—':number_format((float)$shift['discrepancy'],0,',','.').'đ' ?></td></tr><?php endforeach; ?></tbody></table></div></section></div>
</main></div></body></html>
