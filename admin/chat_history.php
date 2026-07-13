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
$activePage = 'chat';
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Lịch sử AI Chat</title><link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>"></head><body>
<div class="admin-layout"><?php require __DIR__ . '/_sidebar.php'; ?><main class="admin-content">
    <div class="role-page-header"><div><p class="role-page-kicker">Dữ liệu tư vấn</p><h1>Lịch sử Foodie AI</h1><p>Theo dõi câu hỏi của khách và nội dung hệ thống đã tư vấn.</p></div><span class="role-badge role-owner"><?= count($chats) ?> lượt gần nhất</span></div>
    <section class="table-card"><div class="responsive-table"><table class="table ai-history-table"><thead><tr><th>Bàn</th><th>Khách hỏi</th><th>AI trả lời</th><th>Loại</th><th>Tổng gợi ý</th><th>Thời gian</th></tr></thead><tbody><?php if (!$chats): ?><tr><td colspan="6">Chưa có lịch sử trò chuyện.</td></tr><?php endif; ?><?php foreach ($chats as $chat): ?><tr><td><strong>Bàn <?= htmlspecialchars($chat['so_ban'] ?? '—') ?></strong></td><td><?= nl2br(htmlspecialchars($chat['user_message'])) ?></td><td><div class="ai-response-cell"><?= nl2br(htmlspecialchars($chat['ai_response'])) ?></div></td><td><span class="role-badge role-manager"><?= htmlspecialchars($chat['response_type']) ?></span></td><td><?= number_format((float)$chat['tong_tien_goi_y'],0,',','.') ?>đ</td><td><?= htmlspecialchars($chat['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
</main></div></body></html>
