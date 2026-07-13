<?php
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_roles(['owner', 'manager', 'kitchen']);

function fetch_orders_by_status(PDO $pdo, string $status): array
{
    $stmt = $pdo->prepare("
        SELECT 
            dh.id,
            dh.ma_don,
            dh.ban_id,
            dh.tong_tien,
            dh.trang_thai,
            dh.ghi_chu,
            dh.created_at,
            b.so_ban
        FROM don_hang dh
        JOIN ban b ON dh.ban_id = b.id
        WHERE dh.trang_thai = ?
        ORDER BY dh.created_at ASC
    ");
    $stmt->execute([$status]);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $detailStmt = $pdo->prepare("
            SELECT ten_mon, so_luong
            FROM chi_tiet_don_hang
            WHERE don_hang_id = ?
            ORDER BY id ASC
        ");
        $detailStmt->execute([$order['id']]);
        $order['items'] = $detailStmt->fetchAll();
    }

    return $orders;
}

$newOrders = fetch_orders_by_status($pdo, 'moi');
$cookingOrders = fetch_orders_by_status($pdo, 'dang_lam');
$doneOrders = fetch_orders_by_status($pdo, 'da_xong');

function render_kitchen_cards(array $orders, string $status): void
{
    if (empty($orders)) {
        echo '<div class="kitchen-empty">Không có đơn nào.</div>';
        return;
    }

    foreach ($orders as $order) {
        $elapsedMinutes = max(0, (int) floor((time() - strtotime($order['created_at'])) / 60));
        echo '<div class="kitchen-order-card ' . ($elapsedMinutes >= 20 && $status !== 'da_xong' ? 'is-late' : '') . '">';
        echo '<div class="kitchen-order-top">';
        echo '<div>';
        echo '<div class="kitchen-table">Bàn ' . htmlspecialchars($order['so_ban']) . '</div>';
        echo '<div class="kitchen-order-code">' . htmlspecialchars($order['ma_don']) . '</div>';
        echo '</div>';
        echo '<div class="kitchen-time">' . $elapsedMinutes . ' phút</div>';
        echo '</div>';

        echo '<div class="kitchen-order-body">';

        if (!empty($order['ghi_chu'])) {
            echo '<div class="kitchen-note">';
            echo '<strong>Ghi chú:</strong> ' . nl2br(htmlspecialchars($order['ghi_chu']));
            echo '</div>';
        }

        echo '<ul class="kitchen-item-list">';
        foreach ($order['items'] as $item) {
            echo '<li><strong>' . (int)$item['so_luong'] . 'x</strong> ' . htmlspecialchars($item['ten_mon']) . '</li>';
        }
        echo '</ul>';

        if (current_admin_role() !== 'kitchen') {
            echo '<div class="kitchen-total">Tổng: ' . number_format((float)$order['tong_tien'], 0, ',', '.') . 'đ</div>';
        }

        echo '</div>';

        echo '<div class="kitchen-order-actions">';
        if ($status === 'moi') {
            echo '<form method="POST" action="/admin/order_update.php">';
            echo '<input type="hidden" name="id" value="' . (int)$order['id'] . '">';
            echo '<input type="hidden" name="trang_thai" value="dang_lam">';
            echo '<button type="submit" class="btn kitchen-btn-start">Nhận làm</button>';
            echo '</form>';
        } elseif ($status === 'dang_lam') {
            echo '<form method="POST" action="/admin/order_update.php">';
            echo '<input type="hidden" name="id" value="' . (int)$order['id'] . '">';
            echo '<input type="hidden" name="trang_thai" value="da_xong">';
            echo '<button type="submit" class="btn kitchen-btn-done">Hoàn tất</button>';
            echo '</form>';
        } elseif ($status === 'da_xong' && current_admin_role() !== 'kitchen') {
            echo '<a class="btn-light" href="/admin/order_detail.php?id=' . (int)$order['id'] . '">Xem chi tiết</a>';
        }
        echo '</div>';

        echo '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Màn hình bếp - Foodie AI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<div class="admin-layout">
    <?php $activePage = 'kitchen'; require __DIR__ . '/_sidebar.php'; ?>
    <aside class="admin-sidebar" style="display:none">
        <h2>Foodie AI</h2>
        <p><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></p>

        <a href="/admin/dashboard.php">Dashboard Order</a>
        <a href="/admin/kitchen.php" class="active">Màn hình bếp</a>
        <a href="/admin/menu.php">Quản lý menu</a>
        <a href="/admin/tables.php">Quản lý bàn + QR</a>
        <a href="/admin/reports.php">Báo cáo</a>
        <a href="/admin/chat_history.php">Lịch sử AI</a>
        <a href="/admin/settings.php">Settings AI</a>
        <a href="/admin/logout.php">Đăng xuất</a>
    </aside>

    <main class="admin-content">
        <div class="kitchen-header-bar">
            <div>
                <h1>Màn hình bếp</h1>
                <p>Quản lý tiến độ chế biến món ăn theo thời gian thực cơ bản.</p>
            </div>

            <div class="kitchen-summary">
                <div class="kitchen-mini-stat">
                    <span>Đơn mới</span>
                    <strong><?= count($newOrders) ?></strong>
                </div>
                <div class="kitchen-mini-stat">
                    <span>Đang làm</span>
                    <strong><?= count($cookingOrders) ?></strong>
                </div>
                <div class="kitchen-mini-stat">
                    <span>Đã xong</span>
                    <strong><?= count($doneOrders) ?></strong>
                </div>
                <button type="button" class="kitchen-notify-btn" onclick="enableKitchenNotice()">
    Bật thông báo bếp
</button>
<!--
<button type="button" class="kitchen-notify-btn" onclick="playKitchenSound()">
    Test âm thanh
</button>-->
            </div>
        </div>

        <div class="kitchen-board">
            <section class="kitchen-column">
                <div class="kitchen-column-head kitchen-head-new">
                    <h3>Đơn mới</h3>
                    <span><?= count($newOrders) ?></span>
                </div>
                <div class="kitchen-column-body">
                    <?php render_kitchen_cards($newOrders, 'moi'); ?>
                </div>
            </section>

            <section class="kitchen-column">
                <div class="kitchen-column-head kitchen-head-cooking">
                    <h3>Đang làm</h3>
                    <span><?= count($cookingOrders) ?></span>
                </div>
                <div class="kitchen-column-body">
                    <?php render_kitchen_cards($cookingOrders, 'dang_lam'); ?>
                </div>
            </section>

            <section class="kitchen-column">
                <div class="kitchen-column-head kitchen-head-done">
                    <h3>Đã xong</h3>
                    <span><?= count($doneOrders) ?></span>
                </div>
                <div class="kitchen-column-body">
                    <?php render_kitchen_cards($doneOrders, 'da_xong'); ?>
                </div>
            </section>
        </div>
    </main>
</div>

<div id="kitchen-toast" class="kitchen-toast hidden">
    <div class="kitchen-toast-icon">!</div>
    <div>
        <strong>Có đơn mới!</strong>
        <p id="kitchen-toast-text">Bếp vừa nhận được order mới.</p>
    </div>
</div>
<audio id="kitchen-sound" preload="auto">
    <source src="/assets/sounds/kitchen.mp3?v=<?= time() ?>" type="audio/mpeg">
</audio>
<script>
let kitchenNoticeEnabled = localStorage.getItem('kitchen_notice_enabled') === '1';
let kitchenLastOrderId = parseInt(localStorage.getItem('kitchen_last_order_id') || '0', 10);
let kitchenFirstCheck = true;

function enableKitchenNotice() {
    kitchenNoticeEnabled = true;
    localStorage.setItem('kitchen_notice_enabled', '1');

    if ('Notification' in window && Notification.permission !== 'granted') {
        Notification.requestPermission();
    }

    playKitchenSound();

    alert('Đã bật thông báo bếp. Khi có đơn mới, màn hình sẽ báo âm thanh và hiện thông báo.');
}

function playKitchenSound() {
    const sound = document.getElementById('kitchen-sound');

    if (sound) {
        sound.pause();
        sound.currentTime = 0;
        sound.volume = 1.0;

        sound.play().catch(function () {
            playFallbackBeep();
        });

        return;
    }

    playFallbackBeep();
}

function playFallbackBeep() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const audioCtx = new AudioContext();

        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);

        gainNode.gain.setValueAtTime(0.001, audioCtx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.35, audioCtx.currentTime + 0.03);
        gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.7);

        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);

        oscillator.start();
        oscillator.stop(audioCtx.currentTime + 0.75);
    } catch (e) {
        console.log('Không phát được âm thanh:', e);
    }
}

function showKitchenToast(text) {
    const toast = document.getElementById('kitchen-toast');
    const toastText = document.getElementById('kitchen-toast-text');

    if (!toast || !toastText) {
        return;
    }

    toastText.innerText = text;
    toast.classList.remove('hidden');

    setTimeout(function () {
        toast.classList.add('hidden');
    }, 7000);
}

function showBrowserNotification(title, body) {
    if (!('Notification' in window)) {
        return;
    }

    if (Notification.permission === 'granted') {
        new Notification(title, {
            body: body,
            icon: '/img/iconlogo.jpg'
        });
    }
}

function checkKitchenNewOrders() {
    fetch('/api/kitchen_new_orders.php?after_id=' + kitchenLastOrderId + '&t=' + Date.now())
        .then(function (res) {
            return res.json();
        })
        .then(function (data) {
            if (!data.success) {
                console.log(data.message || 'Không kiểm tra được đơn mới.');
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Lần đầu mở màn bếp: chỉ lưu ID mới nhất, không báo lại đơn cũ
            |--------------------------------------------------------------------------
            */
            if (kitchenFirstCheck) {
                kitchenFirstCheck = false;

                if (kitchenLastOrderId === 0 && data.latest_id > 0) {
                    kitchenLastOrderId = data.latest_id;
                    localStorage.setItem('kitchen_last_order_id', String(kitchenLastOrderId));
                }

                return;
            }

            if (data.count > 0) {
                const newest = data.orders[data.orders.length - 1];

                kitchenLastOrderId = Math.max(kitchenLastOrderId, data.latest_id);
                localStorage.setItem('kitchen_last_order_id', String(kitchenLastOrderId));

                const message = 'Có ' + data.count + ' đơn mới. Mới nhất: Bàn ' + newest.so_ban + ' - ' + newest.ma_don;

                showKitchenToast(message);

                if (kitchenNoticeEnabled) {
                    playKitchenSound();
                    showBrowserNotification('Foodie AI - Có đơn mới', message);
                }

                /*
                |--------------------------------------------------------------------------
                | Reload nhẹ để đơn mới nhảy vào cột Đơn mới
                |--------------------------------------------------------------------------
                */
                setTimeout(function () {
                    window.location.reload();
                }, 2500);
            }
        })
        .catch(function (err) {
            console.log('Kitchen notice error:', err);
        });
}

/*
|--------------------------------------------------------------------------
| Kiểm tra đơn mới mỗi 5 giây
|--------------------------------------------------------------------------
*/
checkKitchenNewOrders();
setInterval(checkKitchenNewOrders, 5000);
</script>

</body>
</html>
