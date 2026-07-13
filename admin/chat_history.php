<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_roles('owner');

$stmt = $pdo->query("
    SELECT ach.*, b.so_ban
    FROM ai_chat_history ach
    LEFT JOIN ban b ON ach.ban_id = b.id
    ORDER BY ach.created_at DESC
    LIMIT 100
");

$chats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử AI Chat</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<h1>Lịch sử Foodie AI</h1>

<a href="/admin/dashboard.php">Dashboard</a>

<hr>

<table border="1" cellpadding="10" width="100%">
    <tr>
        <th>Bàn</th>
        <th>Khách hỏi</th>
        <th>AI trả lời</th>
        <th>Loại</th>
        <th>Tổng tiền gợi ý</th>
        <th>Thời gian</th>
    </tr>

    <?php foreach ($chats as $chat): ?>
        <tr>
            <td><?= htmlspecialchars($chat['so_ban'] ?? 'Không rõ') ?></td>
            <td><?= nl2br(htmlspecialchars($chat['user_message'])) ?></td>
            <td>
                <pre style="white-space: pre-wrap;">
<?= htmlspecialchars($chat['ai_response']) ?>
                </pre>
            </td>
            <td><?= htmlspecialchars($chat['response_type']) ?></td>
            <td><?= number_format($chat['tong_tien_goi_y'], 0, ',', '.') ?>đ</td>
            <td><?= htmlspecialchars($chat['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
