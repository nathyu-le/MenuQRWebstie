<?php
session_start();

$maDon = $_GET['ma_don'] ?? '';
$soBan = $_SESSION['so_ban'] ?? 'Chưa chọn';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Order thành công - Foodie AI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Đơn hàng đã được gửi về bếp thành công. Theo dõi mã đơn và tiếp tục gọi món tại Foodie AI Restaurant.">

    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<div class="success-page">
    <div class="success-card">
        <div class="success-icon">
            ✓
        </div>

        <div class="success-label">
            Order Submitted
        </div>

        <h1>Đã gửi order về bếp</h1>

        <p class="success-desc">
            Cảm ơn bạn đã gọi món. Đơn hàng của bạn đã được chuyển đến bếp và sẽ được xử lý trong thời gian sớm nhất.
        </p>

        <div class="success-info-grid">
            <div class="success-info-item">
                <span>Mã đơn</span>
                <strong><?= htmlspecialchars($maDon ?: 'Không có mã đơn') ?></strong>
            </div>

            <div class="success-info-item">
                <span>Số bàn</span>
                <strong>Bàn <?= htmlspecialchars($soBan) ?></strong>
            </div>

            <div class="success-info-item">
                <span>Trạng thái</span>
                <strong>Đã gửi về bếp</strong>
            </div>
        </div>

        <div class="success-timeline">
            <div class="timeline-step active">
                <div class="dot">1</div>
                <div>
                    <strong>Đã gửi order</strong>
                    <p>Hệ thống đã nhận đơn của bạn.</p>
                </div>
            </div>

            <div class="timeline-step">
                <div class="dot">2</div>
                <div>
                    <strong>Bếp tiếp nhận</strong>
                    <p>Nhân viên bếp sẽ bắt đầu chế biến.</p>
                </div>
            </div>

            <div class="timeline-step">
                <div class="dot">3</div>
                <div>
                    <strong>Hoàn tất món</strong>
                    <p>Món ăn sẽ được phục vụ tại bàn.</p>
                </div>
            </div>
        </div>

        <div class="success-actions">
            <a class="btn" href="/menu.php">
                Tiếp tục gọi món
            </a>

            <a class="btn-light" href="/cart.php">
                Xem giỏ hàng
            </a>
        </div>

        <p class="success-note">
            Nếu cần gọi thêm món, bạn có thể quay lại menu bất cứ lúc nào. Các order cùng bàn sẽ được gộp khi thanh toán.
        </p>
    </div>
</div>

</body>
</html>