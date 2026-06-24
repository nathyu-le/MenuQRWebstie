<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/helpers/upload.php';

require_admin_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $image = upload_food_image($_FILES['hinh_anh'] ?? []);

        $stmt = $pdo->prepare("
            INSERT INTO mon_an
            (ten_mon, mo_ta, danh_muc, gia, hinh_anh, tag_hot, is_chay, is_cay, phu_hop_tre_em, rating)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            (float) ($_POST['rating'] ?? 5)
        ]);

        header('Location: /admin/menu.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$stmt = $pdo->query("
    SELECT * 
    FROM mon_an 
    WHERE trang_thai != 'da_xoa' 
    ORDER BY created_at DESC
");

$foods = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý menu</title>
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
        <h1>Quản lý menu</h1>

        <form method="POST" enctype="multipart/form-data" class="form-card">
            <h3>Thêm món mới</h3>

            <?php if ($error): ?>
                <p class="notice"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <label>Tên món</label>
            <input name="ten_mon" required>

            <label>Danh mục</label>
            <input name="danh_muc" placeholder="Món chính, Lẩu, Đồ uống...">

            <label>Giá</label>
            <input type="number" name="gia" min="0" required>

            <label>Rating</label>
            <input type="number" step="0.1" min="1" max="5" name="rating" value="5">

            <label>Mô tả</label>
            <textarea name="mo_ta"></textarea>

            <label>Ảnh món</label>
            <input type="file" name="hinh_anh" accept="image/jpeg,image/png,image/webp">

            <label>
                <input type="checkbox" name="tag_hot"> Món HOT
            </label>

            <label>
                <input type="checkbox" name="is_chay"> Món chay
            </label>

            <label>
                <input type="checkbox" name="is_cay"> Món cay
            </label>

            <label>
                <input type="checkbox" name="phu_hop_tre_em" checked> Phù hợp trẻ em
            </label>
<br>
<br>
            <button type="submit">Thêm món</button>
        </form>

        <table class="table">
            <tr>
                <th>Ảnh</th>
                <th>Tên món</th>
                <th>Danh mục</th>
                <th>Giá</th>
                <th>HOT</th>
                <th>Bán chạy</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>

            <?php foreach ($foods as $food): ?>
                <tr>
                    <td>
                        <?php if ($food['hinh_anh']): ?>
                            <img src="/assets/uploads/mon-an/<?= htmlspecialchars($food['hinh_anh']) ?>" width="70">
                        <?php endif; ?>
                    </td>

                    <td><?= htmlspecialchars($food['ten_mon']) ?></td>
                    <td><?= htmlspecialchars($food['danh_muc'] ?? '') ?></td>
                    <td><?= number_format((float) $food['gia'], 0, ',', '.') ?>đ</td>
                    <td><?= (int) $food['tag_hot'] ? 'Có' : 'Không' ?></td>
                    <td><?= (int) $food['so_lan_goi'] ?></td>
                    <td><?= htmlspecialchars($food['trang_thai']) ?></td>

                    <td>
                        <a href="/admin/menu_edit.php?id=<?= (int) $food['id'] ?>">Sửa</a> |
                        <a href="/admin/menu_toggle.php?id=<?= (int) $food['id'] ?>">
                            <?= $food['trang_thai'] === 'dang_ban' ? 'Tạm ngưng' : 'Mở bán' ?>
                        </a> |
                        <a href="/admin/menu_delete.php?id=<?= (int) $food['id'] ?>" onclick="return confirm('Xóa món này?')">Xóa</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </main>
</div>

</body>
</html>