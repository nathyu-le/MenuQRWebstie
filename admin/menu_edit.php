<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/helpers/upload.php';
require_roles(['owner','manager']);
$id=(int)($_GET['id']??0);$stmt=$pdo->prepare("SELECT * FROM mon_an WHERE id=? AND trang_thai!='da_xoa'");$stmt->execute([$id]);$food=$stmt->fetch();if(!$food)die('Không tìm thấy món.');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{$image=upload_food_image($_FILES['hinh_anh']??[]);if($image===null)$image=$food['hinh_anh'];$stmt=$pdo->prepare("UPDATE mon_an SET ten_mon=?,mo_ta=?,danh_muc=?,gia=?,hinh_anh=?,tag_hot=?,is_chay=?,is_cay=?,phu_hop_tre_em=?,rating=?,updated_at=NOW() WHERE id=?");$stmt->execute([trim($_POST['ten_mon']??''),trim($_POST['mo_ta']??''),trim($_POST['danh_muc']??''),(float)($_POST['gia']??0),$image,isset($_POST['tag_hot'])?1:0,isset($_POST['is_chay'])?1:0,isset($_POST['is_cay'])?1:0,isset($_POST['phu_hop_tre_em'])?1:0,(float)($_POST['rating']??5),$id]);header('Location:/admin/menu.php?updated=1');exit;}catch(Throwable $e){$error=$e->getMessage();}
}
$activePage='menu';
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sửa món</title><link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>"></head><body>
<div class="admin-layout"><?php require __DIR__.'/_sidebar.php'; ?><main class="admin-content">
<div class="role-page-header clean-page-header"><div><p class="role-page-kicker">Chỉnh sửa sản phẩm</p><h1><?= htmlspecialchars($food['ten_mon']) ?></h1><p>Cập nhật thông tin sẽ áp dụng ngay trên menu khách hàng.</p></div><a class="btn-light" href="/admin/menu.php">Quay lại menu</a></div>
<?php if($error): ?><div class="role-alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="menu-edit-layout"><aside class="form-card menu-preview-card"><?php if($food['hinh_anh']): ?><img src="/assets/uploads/mon-an/<?= htmlspecialchars($food['hinh_anh']) ?>" alt=""><?php else: ?><div class="menu-preview-placeholder">Foodie AI</div><?php endif; ?><small>Xem trước món</small><h2><?= htmlspecialchars($food['ten_mon']) ?></h2><strong><?= number_format((float)$food['gia'],0,',','.') ?>đ</strong><p><?= htmlspecialchars($food['mo_ta']?:'Chưa có mô tả.') ?></p></aside>
<form method="POST" enctype="multipart/form-data" class="form-card menu-edit-form"><div class="settings-card-head"><div><small>✎</small><h3>Thông tin món</h3></div><span>ID #<?= $id ?></span></div><label>Tên món</label><input name="ten_mon" value="<?= htmlspecialchars($food['ten_mon']) ?>" required><div class="settings-two-columns"><div><label>Danh mục</label><input name="danh_muc" value="<?= htmlspecialchars($food['danh_muc']??'') ?>"></div><div><label>Giá bán</label><input type="number" name="gia" min="0" step="1000" value="<?= htmlspecialchars((string)$food['gia']) ?>" required></div></div><label>Mô tả</label><textarea name="mo_ta" rows="4"><?= htmlspecialchars($food['mo_ta']??'') ?></textarea><div class="settings-two-columns"><div><label>Thay ảnh</label><input type="file" name="hinh_anh" accept="image/jpeg,image/png,image/webp"></div><div><label>Đánh giá</label><input type="number" step="0.1" min="1" max="5" name="rating" value="<?= htmlspecialchars((string)$food['rating']) ?>"></div></div><div class="business-check-grid"><label><input type="checkbox" name="tag_hot" <?= (int)$food['tag_hot']?'checked':'' ?>><span>Món nổi bật</span></label><label><input type="checkbox" name="is_chay" <?= (int)$food['is_chay']?'checked':'' ?>><span>Món chay</span></label><label><input type="checkbox" name="is_cay" <?= (int)$food['is_cay']?'checked':'' ?>><span>Món cay</span></label><label><input type="checkbox" name="phu_hop_tre_em" <?= (int)$food['phu_hop_tre_em']?'checked':'' ?>><span>Phù hợp trẻ em</span></label></div><button type="submit">Lưu thay đổi</button></form></div>
</main></div></body></html>
