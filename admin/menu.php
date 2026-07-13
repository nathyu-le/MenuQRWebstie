<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/helpers/upload.php';
require_roles(['owner','manager']);

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $image=upload_food_image($_FILES['hinh_anh']??[]);
        $name=trim($_POST['ten_mon']??'');$price=(float)($_POST['gia']??0);
        if($name===''||$price<0)throw new RuntimeException('Vui lòng nhập tên món và giá hợp lệ.');
        $stmt=$pdo->prepare("INSERT INTO mon_an (ten_mon,mo_ta,danh_muc,gia,hinh_anh,tag_hot,is_chay,is_cay,phu_hop_tre_em,rating) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$name,trim($_POST['mo_ta']??''),trim($_POST['danh_muc']??''),$price,$image,isset($_POST['tag_hot'])?1:0,isset($_POST['is_chay'])?1:0,isset($_POST['is_cay'])?1:0,isset($_POST['phu_hop_tre_em'])?1:0,(float)($_POST['rating']??5)]);
        header('Location:/admin/menu.php?created=1');exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}
$foods=$pdo->query("SELECT * FROM mon_an WHERE trang_thai!='da_xoa' ORDER BY created_at DESC")->fetchAll();
$activeCount=0;$pausedCount=0;$hotCount=0;foreach($foods as $food){if($food['trang_thai']==='dang_ban')$activeCount++;else$pausedCount++;if((int)$food['tag_hot'])$hotCount++;}
$activePage='menu';
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Quản lý menu</title><link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>"></head><body>
<div class="admin-layout"><?php require __DIR__.'/_sidebar.php'; ?><main class="admin-content">
    <div class="role-page-header clean-page-header"><div><p class="role-page-kicker">Danh mục sản phẩm</p><h1>Quản lý menu</h1><p>Cập nhật món, giá bán và trạng thái phục vụ tại một nơi.</p></div><div class="online-indicator"><i></i> <?= $activeCount ?> món đang bán</div></div>
    <?php if(isset($_GET['created'])): ?><div class="role-alert success">Đã thêm món mới vào menu.</div><?php endif; ?><?php if(isset($_GET['updated'])): ?><div class="role-alert success">Đã cập nhật thông tin món.</div><?php endif; ?><?php if($error): ?><div class="role-alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="metrics-grid menu-metrics"><div class="metric-card"><div class="metric-label">Tổng số món</div><div class="metric-value"><?= count($foods) ?></div></div><div class="metric-card"><div class="metric-label">Đang bán</div><div class="metric-value"><?= $activeCount ?></div></div><div class="metric-card"><div class="metric-label">Tạm ngưng</div><div class="metric-value"><?= $pausedCount ?></div></div><div class="metric-card"><div class="metric-label">Món nổi bật</div><div class="metric-value"><?= $hotCount ?></div></div></div>
    <div class="catalog-admin-layout">
        <form method="POST" enctype="multipart/form-data" class="form-card catalog-create-card"><div class="settings-card-head"><div><small>+</small><h3>Thêm món mới</h3></div><span>Hiển thị ngay sau khi lưu</span></div>
            <label>Tên món</label><input name="ten_mon" required placeholder="VD: Bò nướng sốt tiêu">
            <div class="settings-two-columns"><div><label>Danh mục</label><input name="danh_muc" placeholder="Món chính"></div><div><label>Giá bán</label><input type="number" name="gia" min="0" step="1000" required placeholder="0"></div></div>
            <label>Mô tả ngắn</label><textarea name="mo_ta" rows="3" placeholder="Điểm nổi bật của món..."></textarea>
            <div class="settings-two-columns"><div><label>Ảnh món</label><input type="file" name="hinh_anh" accept="image/jpeg,image/png,image/webp"></div><div><label>Đánh giá</label><input type="number" step="0.1" min="1" max="5" name="rating" value="5"></div></div>
            <div class="business-check-grid"><label><input type="checkbox" name="tag_hot"><span>Món nổi bật</span></label><label><input type="checkbox" name="is_chay"><span>Món chay</span></label><label><input type="checkbox" name="is_cay"><span>Món cay</span></label><label><input type="checkbox" name="phu_hop_tre_em" checked><span>Phù hợp trẻ em</span></label></div>
            <button type="submit">Thêm vào menu</button>
        </form>
        <section class="table-card catalog-list-card"><div class="settings-card-head"><div><h3>Danh sách món</h3></div><span><?= count($foods) ?> món</span></div><div class="responsive-table"><table class="table catalog-table"><thead><tr><th>Món ăn</th><th>Danh mục</th><th>Giá bán</th><th>Lượt gọi</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
        <?php if(!$foods): ?><tr><td colspan="6">Menu chưa có món.</td></tr><?php endif; ?>
        <?php foreach($foods as $food): ?><tr><td><div class="catalog-food-cell"><?php if($food['hinh_anh']): ?><img src="/assets/uploads/mon-an/<?= htmlspecialchars($food['hinh_anh']) ?>" alt=""><?php else: ?><span class="catalog-placeholder">FA</span><?php endif; ?><div><strong><?= htmlspecialchars($food['ten_mon']) ?></strong><small><?= (int)$food['tag_hot']?'Nổi bật · ':'' ?>★ <?= number_format((float)$food['rating'],1) ?></small></div></div></td><td><?= htmlspecialchars($food['danh_muc']?:'Khác') ?></td><td><strong><?= number_format((float)$food['gia'],0,',','.') ?>đ</strong></td><td><?= number_format((int)$food['so_lan_goi']) ?></td><td><span class="status-badge <?= $food['trang_thai']==='dang_ban'?'status-da_xong':'status-huy' ?>"><?= $food['trang_thai']==='dang_ban'?'Đang bán':'Tạm ngưng' ?></span></td><td><div class="catalog-actions"><a href="/admin/menu_edit.php?id=<?= (int)$food['id'] ?>">Sửa</a><a href="/admin/menu_toggle.php?id=<?= (int)$food['id'] ?>"><?= $food['trang_thai']==='dang_ban'?'Tạm ngưng':'Mở bán' ?></a><a class="danger-link" href="/admin/menu_delete.php?id=<?= (int)$food['id'] ?>" onclick="return confirm('Xóa món này?')">Xóa</a></div></td></tr><?php endforeach; ?>
        </tbody></table></div></section>
    </div>
</main></div></body></html>
