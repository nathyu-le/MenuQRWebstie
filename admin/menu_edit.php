<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/helpers/upload.php';

require_admin_login();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT * 
    FROM mon_an 
    WHERE id = ? AND trang_thai != 'da_xoa'
");

$stmt->execute([$id]);
$food = $stmt->fetch();

if (!$food) {
    die('Không tìm thấy món.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $image = upload_food_image($_FILES['hinh_anh'] ?? []);

        if ($image === null) {
            $image = $food['hinh_anh'];
        }

        $stmt = $pdo->prepare("
            UPDATE mon_an
            SET 
                ten_mon = ?, 
                mo_ta = ?, 
                danh_muc = ?, 
                gia = ?, 
                hinh_anh = ?,
                tag_hot = ?, 
                is_chay = ?, 
                is_cay = ?, 
                phu_hop_tre_em = ?, 
                rating = ?, 
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            trim($_POST['ten_mon'] ?? ''),
            trim($_POST['mo_ta'] ?? ''),
            trim($_POST['danh_muc'] ?? ''),
            (float) ($_POST['gia'] ?? 0),
            $image,
            isset($_POST['tag_hot']) ? 1 : 0,
            isset($_POST['is_chay']) ? 1 : 0,
            isset($_POST['is_cay']) ? 1 : 0,
            isset($_POST['phu_hop_tre_em']) ? 1 : 0,
            (float) ($_POST['rating'] ?? 5),
            $id
        ]);

        header('Location: /admin/menu.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa món</title>
    <link rel="stylesheet" href="/assets/css/style.css">
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
        <h1>Sửa món ăn</h1>

        <form method="POST" enctype="multipart/form-data" class="form-card">
            <?php if ($error): ?>
                <p class="notice"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <label>Tên món</label>
            <input name="ten_mon" value="<?= htmlspecialchars($food['ten_mon']) ?>" required>

            <label>Danh mục</label>
            <input name="danh_muc" value="<?= htmlspecialchars($food['danh_muc'] ?? '') ?>">

            <label>Giá</label>
            <input type="number" name="gia" value="<?= htmlspecialchars((string) $food['gia']) ?>" required>

            <label>Rating</label>
            <input type="number" step="0.1" min="1" max="5" name="rating" value="<?= htmlspecialchars((string) $food['rating']) ?>">

            <label>Mô tả</label>
            <textarea name="mo_ta"><?= htmlspecialchars($food['mo_ta'] ?? '') ?></textarea>

            <?php if ($food['hinh_anh']): ?>
                <p>Ảnh hiện tại:</p>
                <img src="/assets/uploads/mon-an/<?= htmlspecialchars($food['hinh_anh']) ?>" width="160">
            <?php endif; ?>

            <label>Đổi ảnh</label>
            <input type="file" name="hinh_anh" accept="image/jpeg,image/png,image/webp">

            <label>
                <input type="checkbox" name="tag_hot" <?= (int) $food['tag_hot'] ? 'checked' : '' ?>>
                Món HOT
            </label>

            <label>
                <input type="checkbox" name="is_chay" <?= (int) $food['is_chay'] ? 'checked' : '' ?>>
                Món chay
            </label>

            <label>
                <input type="checkbox" name="is_cay" <?= (int) $food['is_cay'] ? 'checked' : '' ?>>
                Món cay
            </label>

            <label>
                <input type="checkbox" name="phu_hop_tre_em" <?= (int) $food['phu_hop_tre_em'] ? 'checked' : '' ?>>
                Phù hợp trẻ em
            </label>

            <button type="submit">Lưu thay đổi</button>
            <a class="btn-light" href="/admin/menu.php">Quay lại</a>
        </form>
    </main>
</div>

</body>
</html>