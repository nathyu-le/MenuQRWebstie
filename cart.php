<?php
session_start();

require_once __DIR__ . '/../app/config/database.php';

$items = [];
$total = 0;
$totalQty = 0;

if (isset($_SESSION['ban_id'])) {
    $stmt = $pdo->prepare("
        SELECT 
            gh.*,
            ma.ten_mon,
            ma.gia,
            ma.hinh_anh,
            ma.danh_muc
        FROM gio_hang_tam gh
        JOIN mon_an ma ON gh.mon_an_id = ma.id
        WHERE gh.ban_id = ?
        ORDER BY gh.created_at DESC
    ");

    $stmt->execute([(int) $_SESSION['ban_id']]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $qty = (int) $item['so_luong'];
        $price = (float) $item['gia'];

        $total += $price * $qty;
        $totalQty += $qty;
    }
}

$soBan = $_SESSION['so_ban'] ?? 'Chưa chọn';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giỏ hàng - Foodie AI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta 
        name="description" 
        content="Kiểm tra giỏ hàng, cập nhật số lượng món ăn và gửi order trực tiếp về bếp tại Foodie AI Restaurant."
    >

    <link rel="stylesheet" href="/assets/css/cart.css?v=<?= time() ?>">
</head>

<body class="cart-page">

<header class="cart-navbar">
    <div class="cart-navbar-inner">
        <a href="/menu.php" class="brand-block">
            <div class="brand-mark">FA</div>

            <div class="brand-text">
                <h1>Foodie AI Restaurant</h1>
                <p>Review your order · Send to kitchen</p>
            </div>
        </a>

        <nav class="cart-nav-actions">
            <a href="/menu.php" class="nav-link">Tiếp tục chọn món</a>
            <a href="/cart.php" class="nav-link">Giỏ hàng</a>

            <button type="button" class="table-pill" onclick="openTablePopup()">
                Bàn <?= htmlspecialchars($soBan) ?>
            </button>
        </nav>
    </div>
</header>

<main class="cart-main">
    <section class="cart-hero">
        <div class="cart-hero-label">
            Kiểm tra order
        </div>

        <h2>Order của bạn đã sẵn sàng.</h2>

        <p>
            Bạn có thể tăng giảm số lượng, xóa món, ghi chú cho bếp và gửi order.
            Nếu gọi thêm nhiều lần, hệ thống sẽ gộp hóa đơn theo bàn khi thanh toán.
        </p>
    </section>

    <?php if (!isset($_SESSION['ban_id'])): ?>
        <section class="empty-panel">
            <div class="empty-icon">#</div>

            <h2>Bạn chưa chọn số bàn</h2>

            <p>
                Vui lòng nhập số bàn trước khi thêm món hoặc gửi order về bếp.
                Khách không cần đăng nhập tài khoản.
            </p>

            <button type="button" class="primary-btn" onclick="openTablePopup()">
                Nhập số bàn
            </button>
        </section>
    <?php elseif (empty($items)): ?>
        <section class="empty-panel">
            <div class="empty-icon">0</div>

            <h2>Giỏ hàng đang trống</h2>

            <p>
                Bạn chưa có món nào trong giỏ. Quay lại menu để chọn món yêu thích
                hoặc hỏi Foodie AI gợi ý set món phù hợp.
            </p>

            <a class="primary-btn" href="/menu.php">
                Chọn món ngay
            </a>
        </section>
    <?php else: ?>
        <section class="cart-layout">
            <div class="cart-panel">
                <div class="panel-head">
                    <h3>Món đã chọn</h3>
                    <span><?= number_format($totalQty) ?> món</span>
                </div>

                <div class="cart-items">
                    <?php foreach ($items as $item): ?>
                        <?php
                            $qty = (int) $item['so_luong'];
                            $price = (float) $item['gia'];
                            $lineTotal = $price * $qty;
                        ?>

                        <article class="cart-item">
                            <div class="cart-thumb">
                                <?php if (!empty($item['hinh_anh'])): ?>
                                    <img 
                                        src="/assets/uploads/mon-an/<?= htmlspecialchars($item['hinh_anh']) ?>" 
                                        alt="<?= htmlspecialchars($item['ten_mon']) ?>"
                                    >
                                <?php else: ?>
                                    <div class="cart-thumb-placeholder">
                                        Foodie
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="item-main">
                                <h3 class="item-title">
                                    <?= htmlspecialchars($item['ten_mon']) ?>
                                </h3>

                                <div class="item-meta">
                                    <span class="meta-pill">
                                        <?= htmlspecialchars($item['danh_muc'] ?: 'Món ăn') ?>
                                    </span>

                                    <span class="meta-pill">
                                        Đơn giá <?= number_format($price, 0, ',', '.') ?>đ
                                    </span>
                                </div>

                                <div class="item-price">
                                    <?= number_format($lineTotal, 0, ',', '.') ?>đ
                                </div>
                            </div>

                            <div class="item-side">
                                <div class="qty-control">
                                    <button 
                                        type="button" 
                                        onclick="updateCart(<?= (int) $item['id'] ?>, -1)"
                                    >
                                        −
                                    </button>

                                    <strong><?= $qty ?></strong>

                                    <button 
                                        type="button" 
                                        onclick="updateCart(<?= (int) $item['id'] ?>, 1)"
                                    >
                                        +
                                    </button>
                                </div>

                                <div class="line-total">
                                    <?= number_format($lineTotal, 0, ',', '.') ?>đ
                                </div>

                                <button 
                                    type="button" 
                                    class="remove-btn" 
                                    onclick="removeCartItem(<?= (int) $item['id'] ?>)"
                                >
                                    Xóa món
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside class="summary-panel">
                <h3>Tóm tắt order</h3>

                <div class="summary-row">
                    <span>Số bàn</span>
                    <strong>Bàn <?= htmlspecialchars($soBan) ?></strong>
                </div>

                <div class="summary-row">
                    <span>Tổng số lượng</span>
                    <strong><?= number_format($totalQty) ?> món</strong>
                </div>

                <div class="summary-row">
                    <span>Tạm tính</span>
                    <strong><?= number_format($total, 0, ',', '.') ?>đ</strong>
                </div>

                <div class="summary-total">
                    <span>Tổng order</span>
                    <strong><?= number_format($total, 0, ',', '.') ?>đ</strong>
                </div>

                <textarea 
                    id="order-note" 
                    class="order-note" 
                    placeholder="Ghi chú cho bếp nếu có. Ví dụ: ít cay, không hành, làm nhanh giúp..."
                ></textarea>

                <div class="summary-actions">
                    <button type="button" class="primary-btn" onclick="submitOrder()">
                        Gửi order về bếp
                    </button>

                    <a href="/menu.php" class="secondary-btn">
                        Tiếp tục gọi món
                    </a>

                    <button type="button" class="danger-soft-btn" onclick="clearCart()">
                        Xóa toàn bộ giỏ
                    </button>
                </div>

                <p class="cart-hint">
                    Sau khi gửi order, giỏ hàng sẽ được làm trống. Nếu bạn gọi thêm món sau đó,
                    hệ thống vẫn gộp chung hóa đơn theo bàn khi thanh toán.
                </p>
            </aside>
        </section>
    <?php endif; ?>
</main>

<nav class="customer-mobile-dock cart-mobile-dock" aria-label="Điều hướng giỏ hàng">
    <a href="/menu.php"><i>←</i><span>Chọn món</span></a>
    <a class="active" href="/cart.php"><i>Bag</i><span><?= number_format($totalQty) ?> món</span></a>
    <button type="button" onclick="openTablePopup()"><i>#</i><span>Bàn <?= htmlspecialchars($soBan) ?></span></button>
    <?php if (!empty($items)): ?><button type="button" class="dock-order-button" onclick="submitOrder()"><i>✓</i><span>Gửi order</span></button><?php else: ?><a href="/menu.php"><i>+</i><span>Thêm món</span></a><?php endif; ?>
</nav>

<!-- POPUP NHẬP SỐ BÀN -->
<div id="table-popup" class="popup hidden">
    <div class="popup-content">
        <h3>Nhập số bàn</h3>

        <p>
            Khách không cần tài khoản. Chỉ cần nhập số bàn để gọi món
            và thanh toán gộp khi kết thúc bữa ăn.
        </p>

        <input 
            type="text" 
            id="so_ban" 
            placeholder="Ví dụ: 1"
            value="<?= htmlspecialchars($_SESSION['so_ban'] ?? '') ?>"
        >

        <button type="button" onclick="saveTable()">Xác nhận</button>
        <button type="button" class="btn-light" onclick="closeTablePopup()">Đóng</button>
    </div>
</div>

<script src="/assets/js/app.js?v=<?= time() ?>"></script>

</body>
</html>
