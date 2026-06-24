<?php
declare(strict_types=1);

function upload_food_image(array $file): ?string
{
    if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload ảnh thất bại.');
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('Ảnh không được vượt quá 2MB.');
    }

    $tmp = $file['tmp_name'];
    $info = getimagesize($tmp);

    if ($info === false) {
        throw new RuntimeException('File upload không phải ảnh hợp lệ.');
    }

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $mime = $info['mime'] ?? '';

    if (!isset($allowedMime[$mime])) {
        throw new RuntimeException('Chỉ cho phép ảnh JPG, PNG hoặc WEBP.');
    }

    $ext = $allowedMime[$mime];
    $filename = 'food_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    $targetDir = __DIR__ . '/../../public_html/assets/uploads/mon-an';

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $target = $targetDir . '/' . $filename;

    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('Không thể lưu ảnh upload.');
    }

    return $filename;
}