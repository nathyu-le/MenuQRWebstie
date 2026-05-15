<?php
// Xử lý Ajax chat AI cơ bản
header('Content-Type: application/json');
$request = json_decode(file_get_contents('php://input'), true);
$message = trim($request['message'] ?? '');
$response = [
    'success' => false,
    'message' => 'Không có nội dung.',
];
if ($message !== '') {
    $response['success'] = true;
    $response['message'] = 'AI trả lời: Tôi đã nhận được câu hỏi của bạn - "' . htmlspecialchars($message) . '"';
}
echo json_encode($response);
