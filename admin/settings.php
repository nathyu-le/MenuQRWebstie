<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/services/SettingService.php';

require_roles('owner');

$message = '';
$error = '';
$settingKeys = [
    'restaurant_name', 'site_base_url',
    'bank_transfer_enabled', 'bank_code', 'bank_account_number',
    'bank_account_name', 'bank_transfer_prefix', 'bank_qr_template',
    'ai_provider', 'gemini_api_key', 'gpt_api_key', 'grok_api_key'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $section = trim($_POST['section'] ?? '');
        $allowedBySection = [
            'business' => ['restaurant_name', 'site_base_url'],
            'payment' => ['bank_transfer_enabled', 'bank_code', 'bank_account_number', 'bank_account_name', 'bank_transfer_prefix', 'bank_qr_template'],
            'ai' => ['ai_provider', 'gemini_api_key', 'gpt_api_key', 'grok_api_key']
        ];
        if (!isset($allowedBySection[$section])) {
            throw new RuntimeException('Nhóm cài đặt không hợp lệ.');
        }

        foreach ($allowedBySection[$section] as $key) {
            $value = trim($_POST[$key] ?? '');
            if ($key === 'bank_transfer_enabled') $value = isset($_POST[$key]) ? '1' : '0';
            SettingService::set($pdo, $key, $value);
        }
        $message = 'Đã lưu cài đặt ' . ($section === 'payment' ? 'chuyển khoản.' : ($section === 'business' ? 'nhà hàng.' : 'AI.'));
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$settings = [];
foreach ($settingKeys as $key) $settings[$key] = SettingService::get($pdo, $key, '');
$settings['restaurant_name'] = $settings['restaurant_name'] ?: 'Foodie AI Restaurant';
$settings['ai_provider'] = $settings['ai_provider'] ?: 'gemini';
$settings['bank_transfer_prefix'] = $settings['bank_transfer_prefix'] ?: 'FOODIE';
$settings['bank_qr_template'] = $settings['bank_qr_template'] ?: 'compact2';
$activePage = 'settings';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Cài đặt hệ thống</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-content">
        <div class="role-page-header clean-page-header">
            <div><p class="role-page-kicker">Foodie AI</p><h1>Cài đặt</h1><p>Thông tin nhà hàng, thanh toán chuyển khoản và kết nối AI.</p></div>
            <div class="online-indicator"><i></i> Hệ thống hoạt động</div>
        </div>

        <?php if ($message): ?><div class="role-alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="role-alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="settings-layout">
            <form method="POST" class="form-card settings-card">
                <?= csrf_field() ?><input type="hidden" name="section" value="business">
                <div class="settings-card-head"><div><small>01</small><h3>Thông tin nhà hàng</h3></div><span>Hiển thị trên hóa đơn</span></div>
                <label>Tên nhà hàng</label>
                <input name="restaurant_name" value="<?= htmlspecialchars($settings['restaurant_name']) ?>" required>
                <label>Địa chỉ website</label>
                <input name="site_base_url" value="<?= htmlspecialchars($settings['site_base_url']) ?>" placeholder="https://tenmiencuaban.com">
                <button type="submit">Lưu thông tin</button>
            </form>

            <form method="POST" class="form-card settings-card payment-settings-card">
                <?= csrf_field() ?><input type="hidden" name="section" value="payment">
                <div class="settings-card-head"><div><small>02</small><h3>Thanh toán chuyển khoản</h3></div><label class="switch-control"><input type="checkbox" name="bank_transfer_enabled" value="1" <?= $settings['bank_transfer_enabled'] === '1' ? 'checked' : '' ?>><span></span></label></div>
                <p class="settings-intro">Bật để thu ngân hiển thị QR chuyển khoản đúng số tiền hóa đơn.</p>
                <div class="settings-two-columns">
                    <div><label>Mã ngân hàng</label><input name="bank_code" value="<?= htmlspecialchars($settings['bank_code']) ?>" placeholder="VD: MB, VCB, ACB" maxlength="20"></div>
                    <div><label>Số tài khoản</label><input name="bank_account_number" value="<?= htmlspecialchars($settings['bank_account_number']) ?>" inputmode="numeric" placeholder="Nhập số tài khoản" maxlength="30"></div>
                </div>
                <label>Tên chủ tài khoản</label>
                <input name="bank_account_name" value="<?= htmlspecialchars($settings['bank_account_name']) ?>" placeholder="NGUYEN VAN A" maxlength="100">
                <div class="settings-two-columns">
                    <div><label>Tiền tố nội dung</label><input name="bank_transfer_prefix" value="<?= htmlspecialchars($settings['bank_transfer_prefix']) ?>" placeholder="FOODIE" maxlength="30"></div>
                    <div><label>Mẫu QR</label><select name="bank_qr_template"><option value="compact2" <?= $settings['bank_qr_template']==='compact2'?'selected':'' ?>>Gọn, có thông tin</option><option value="compact" <?= $settings['bank_qr_template']==='compact'?'selected':'' ?>>QR tối giản</option><option value="qr_only" <?= $settings['bank_qr_template']==='qr_only'?'selected':'' ?>>Chỉ mã QR</option></select></div>
                </div>
                <button type="submit">Lưu cấu hình chuyển khoản</button>
            </form>

            <form method="POST" class="form-card settings-card settings-wide">
                <?= csrf_field() ?><input type="hidden" name="section" value="ai">
                <div class="settings-card-head"><div><small>03</small><h3>Kết nối AI</h3></div><span>Tùy chọn</span></div>
                <label>Nhà cung cấp</label>
                <select name="ai_provider"><option value="gemini" <?= $settings['ai_provider']==='gemini'?'selected':'' ?>>Gemini</option><option value="gpt" <?= $settings['ai_provider']==='gpt'?'selected':'' ?>>OpenAI GPT</option><option value="grok" <?= $settings['ai_provider']==='grok'?'selected':'' ?>>Grok</option></select>
                <div class="settings-three-columns">
                    <div><label>Gemini API Key</label><input type="password" name="gemini_api_key" value="<?= htmlspecialchars($settings['gemini_api_key']) ?>" autocomplete="off"></div>
                    <div><label>OpenAI API Key</label><input type="password" name="gpt_api_key" value="<?= htmlspecialchars($settings['gpt_api_key']) ?>" autocomplete="off"></div>
                    <div><label>Grok API Key</label><input type="password" name="grok_api_key" value="<?= htmlspecialchars($settings['grok_api_key']) ?>" autocomplete="off"></div>
                </div>
                <button type="submit">Lưu kết nối AI</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
