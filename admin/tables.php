<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/services/SettingService.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soBan = trim($_POST['so_ban'] ?? '');

    if ($soBan !== '') {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO ban (so_ban, trang_thai) 
            VALUES (?, 'trong')
        ");

        $stmt->execute([$soBan]);
    }

    header('Location: /admin/tables.php');
    exit;
}

$baseUrl = SettingService::get($pdo, 'site_base_url', '');

if ($baseUrl === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

$stmt = $pdo->query("
    SELECT * 
    FROM ban 
    ORDER BY CAST(so_ban AS UNSIGNED), so_ban
");

$tables = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý bàn</title>
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
        <h1>Quản lý bàn + QR</h1>

        <form method="POST" class="form-card">
            <h3>Thêm bàn</h3>
            <input name="so_ban" placeholder="Nhập số bàn" required>
            <button type="submit">Thêm bàn</button>
        </form>

        <table class="table">
            <tr>
                <th>Số bàn</th>
                <th>Trạng thái</th>
                <th>Link order</th>
                <th>QR</th>
                <th>Hành động</th>
            </tr>

            <?php foreach ($tables as $table): ?>
                <?php
                $url = rtrim($baseUrl, '/') . '/menu.php?table=' . urlencode($table['so_ban']);
                ?>

                <tr>
                    <td>Bàn <?= htmlspecialchars($table['so_ban']) ?></td>
                    <td><?= htmlspecialchars($table['trang_thai']) ?></td>

                    <td>
                        <input value="<?= htmlspecialchars($url) ?>" readonly onclick="this.select()">
                    </td>

                    <td>
                        <img 
                            width="120" 
                            height="120"
                            src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= urlencode($url) ?>"
                            alt="QR bàn <?= htmlspecialchars($table['so_ban']) ?>"
                        >
                    </td>

                    <td>
                        <a href="/admin/table_toggle.php?id=<?= (int) $table['id'] ?>">
                            <?= $table['trang_thai'] === 'tam_khoa' ? 'Mở khóa' : 'Tạm khóa' ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </main>
</div>

</body>
</html>