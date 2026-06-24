<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/services/SettingService.php';

require_admin_login();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = [
        'restaurant_name',
        'site_base_url',
        'ai_provider',
        'gemini_api_key',
        'gpt_api_key',
        'grok_api_key'
    ];

    foreach ($keys as $key) {
        SettingService::set($pdo, $key, trim($_POST[$key] ?? ''));
    }

    $message = 'Đã lưu settings.';
}

$restaurantName = SettingService::get($pdo, 'restaurant_name', 'Foodie AI Restaurant');
$siteBaseUrl = SettingService::get($pdo, 'site_base_url', '');
$aiProvider = SettingService::get($pdo, 'ai_provider', 'gemini');
$geminiApiKey = SettingService::get($pdo, 'gemini_api_key', '');
$gptApiKey = SettingService::get($pdo, 'gpt_api_key', '');
$grokApiKey = SettingService::get($pdo, 'grok_api_key', '');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Settings AI</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <h2>Foodie AI</h2>
        <a href="/admin/dashboard.php">Dashboard Order</a>
        <a href="/admin/kitchen.php">Màn hình bếp</a>
        <a href="/admin/menu.php">Quản lý menu</a>
        <a href="/admin/tables.php">Quản lý bàn + QR</a>
        <a href="/admin/reports.php">Báo cáo</a>
        <a href="/admin/chat_history.php">Lịch sử AI</a>
        <a href="/admin/settings.php">Settings AI</a>
        <a href="/admin/logout.php">Đăng xuất</a>
    </aside>

    <main class="admin-content">
        <h1>Settings hệ thống + AI</h1>

        <form method="POST" class="form-card">
            <?php if ($message): ?>
                <p class="notice"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <label>Tên nhà hàng</label>
            <input name="restaurant_name" value="<?= htmlspecialchars($restaurantName) ?>">

            <label>Site base URL</label>
            <input name="site_base_url" value="<?= htmlspecialchars($siteBaseUrl) ?>" placeholder="https://tenmiencuaban.com">

            <label>AI Provider</label>
            <select name="ai_provider">
                <option value="gemini" <?= $aiProvider === 'gemini' ? 'selected' : '' ?>>Gemini</option>
                <option value="gpt" <?= $aiProvider === 'gpt' ? 'selected' : '' ?>>GPT - nâng cấp sau</option>
                <option value="grok" <?= $aiProvider === 'grok' ? 'selected' : '' ?>>Grok - nâng cấp sau</option>
            </select>

            <label>Gemini API Key</label>
            <input name="gemini_api_key" value="<?= htmlspecialchars($geminiApiKey) ?>">

            <label>GPT API Key</label>
            <input name="gpt_api_key" value="<?= htmlspecialchars($gptApiKey) ?>">

            <label>Grok API Key</label>
            <input name="grok_api_key" value="<?= htmlspecialchars($grokApiKey) ?>">
<br>
<br>
            <button type="submit">Lưu settings</button>
        </form>

       
    </main>
</div>

</body>
</html>