<?php
session_start();

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/SettingService.php';

/*
|--------------------------------------------------------------------------
| Auto nhận số bàn từ QR: /menu.php?table=1
|--------------------------------------------------------------------------
*/
if (isset($_GET['table'])) {
    $soBanQr = trim($_GET['table']);

    if ($soBanQr !== '') {
        $stmt = $pdo->prepare("
            SELECT * 
            FROM ban 
            WHERE so_ban = ? 
              AND trang_thai != 'tam_khoa'
        ");
        $stmt->execute([$soBanQr]);
        $banQr = $stmt->fetch();

        if ($banQr) {
            $_SESSION['ban_id'] = (int) $banQr['id'];
            $_SESSION['so_ban'] = $banQr['so_ban'];
        }
    }
}

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/
$restaurantName = SettingService::get($pdo, 'restaurant_name', 'Foodie AI Restaurant');

/*
|--------------------------------------------------------------------------
| Filter
|--------------------------------------------------------------------------
*/
$category = trim($_GET['category'] ?? '');
$search = trim($_GET['q'] ?? '');

$where = "trang_thai = 'dang_ban'";
$params = [];

if ($category !== '') {
    $where .= " AND danh_muc = ?";
    $params[] = $category;
}

if ($search !== '') {
    $where .= " AND (ten_mon LIKE ? OR mo_ta LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

/*
|--------------------------------------------------------------------------
| Foods
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT *
    FROM mon_an
    WHERE $where
    ORDER BY tag_hot DESC, so_lan_goi DESC, rating DESC, created_at DESC
");
$stmt->execute($params);
$foods = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/
$catStmt = $pdo->query("
    SELECT DISTINCT danh_muc
    FROM mon_an
    WHERE trang_thai = 'dang_ban'
      AND danh_muc IS NOT NULL
      AND danh_muc != ''
    ORDER BY danh_muc ASC
");
$categories = $catStmt->fetchAll();

$totalFoodCount = (int) $pdo->query("
    SELECT COUNT(*)
    FROM mon_an
    WHERE trang_thai = 'dang_ban'
")->fetchColumn();

$hotFoodCount = (int) $pdo->query("
    SELECT COUNT(*)
    FROM mon_an
    WHERE trang_thai = 'dang_ban'
      AND tag_hot = 1
")->fetchColumn();

function build_category_url(string $cat): string
{
    $params = $_GET;
    $params['category'] = $cat;

    return '/menu.php?' . http_build_query($params);
}

function build_all_url(): string
{
    $params = $_GET;
    unset($params['category']);

    $query = http_build_query($params);

    return $query ? '/menu.php?' . $query : '/menu.php';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($restaurantName) ?> | Menu Order Online</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta 
        name="description" 
        content="Khám phá menu món ăn hiện đại, gọi món nhanh theo số bàn và nhận tư vấn món ăn thông minh từ Foodie AI."
    >
    <meta 
        name="keywords" 
        content="menu nhà hàng, order món ăn, gọi món online, Foodie AI, đặt món theo bàn"
    >

    <link rel="stylesheet" href="/assets/css/menu.css?v=<?= time() ?>">
</head>

<body class="menu-page">

<div class="menu-shell">
    <header class="menu-navbar">
        <div class="menu-navbar-inner">
            <a href="/menu.php" class="brand-block">
    <div class="brand-logo">
        <img src="/img/logo.png" alt="Foodie AI Logo">
    </div>

    <div class="brand-text">
        <h1><?= htmlspecialchars($restaurantName) ?></h1>
        <p>Premium dining · Smart ordering</p>
    </div>
</a>

            <nav class="menu-nav-actions">
                <a href="/menu.php" class="nav-link">Menu</a>
                <a href="/cart.php" class="nav-link">Giỏ hàng</a>

                <button type="button" class="table-pill" onclick="openTablePopup()">
                    Bàn <?= htmlspecialchars($_SESSION['so_ban'] ?? 'chưa chọn') ?>
                </button>
            </nav>
        </div>
    </header>

    <main class="menu-main">
        <section class="menu-hero">
            <div class="hero-panel">
                <div class="hero-eyebrow">
                    Modern restaurant ordering
                </div>

                <h2>Oder Siêu Nhanh, Lên Món Hấp Dẫn</h2>

                <p>
                    Trải nghiệm menu online được thiết kế theo thời đại công nghệ
                    chọn món, thêm vào giỏ, gọi thêm nhiều lần và thanh toán nhanh theo bàn.
                </p>

                <div class="hero-actions">

                    <button type="button" class="secondary-cta" onclick="toggleFoodieChat()">
                        Chat Ngay Với AI 
                    </button>
                </div>
            </div>

            <aside class="hero-side">
                <div class="side-card">
                    <h3>Dining assistant</h3>
                    <p>
                        Foodie AI có thể gợi ý set món theo số người, ngân sách,
                        khẩu vị cay/không cay, món chay hoặc món phù hợp trẻ em.
                    </p>

                    <div class="hero-stats-grid">
                        <div class="clean-stat">
                            <strong><?= number_format($totalFoodCount) ?>+</strong>
                            <span>Món đang bán</span>
                        </div>

                        <div class="clean-stat">
                            <strong><?= number_format($hotFoodCount) ?></strong>
                            <span>Món nổi bật</span>
                        </div>

                        <div class="clean-stat">
                            <strong>1 bill</strong>
                            <span>Gộp thanh toán theo bàn</span>
                        </div>

                        <div class="clean-stat">
                            <strong>Fast</strong>
                            <span>Gửi order về bếp</span>
                        </div>
                    </div>
                </div>
            </aside>
        </section>

        <section class="menu-toolbar">
            <form method="GET" action="/menu.php" class="toolbar-form">
                <div class="field-wrap">
                    <label class="field-label">Tìm kiếm món ăn</label>
                    <input
                        class="search-input"
                        type="text"
                        name="q"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Nhập tên món, mô tả món..."
                    >
                </div>

                <div class="field-wrap">
                    <label class="field-label">Danh mục</label>
                    <select name="category" class="category-select">
                        <option value="">Tất cả danh mục</option>

                        <?php foreach ($categories as $cat): ?>
                            <option
                                value="<?= htmlspecialchars($cat['danh_muc']) ?>"
                                <?= $category === $cat['danh_muc'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($cat['danh_muc']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-wrap">
                    <label class="field-label">&nbsp;</label>
                    <button type="submit" class="search-btn">Tìm món</button>
                </div>
            </form>

            <div class="category-chips">
                <a 
                    href="<?= htmlspecialchars(build_all_url()) ?>" 
                    class="category-chip <?= $category === '' ? 'active' : '' ?>"
                >
                    Tất cả
                </a>

                <?php foreach ($categories as $cat): ?>
                    <a
                        href="<?= htmlspecialchars(build_category_url($cat['danh_muc'])) ?>"
                        class="category-chip <?= $category === $cat['danh_muc'] ? 'active' : '' ?>"
                    >
                        <?= htmlspecialchars($cat['danh_muc']) ?>
                    </a>
                <?php endforeach; ?>

                <?php if ($search !== '' || $category !== ''): ?>
                    <a href="/menu.php" class="category-chip">
                        Xóa lọc
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <section id="menu-list">
            <div class="section-heading">
                <div>
                    <h2>Menu hôm nay</h2>
                    <p>Chọn món yêu thích và gửi order trực tiếp về bếp.</p>
                </div>

                <div class="result-count">
                    <?= number_format(count($foods)) ?> món
                </div>
            </div>

            <div class="food-grid">
                <?php if (!empty($foods)): ?>
                    <?php foreach ($foods as $food): ?>
                        <article class="food-card-pro">
                            <div class="food-photo">
                                <?php if (!empty($food['hinh_anh'])): ?>
                                    <img
                                        src="/assets/uploads/mon-an/<?= htmlspecialchars($food['hinh_anh']) ?>"
                                        alt="<?= htmlspecialchars($food['ten_mon']) ?>"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <div class="food-placeholder">
                                        <?= htmlspecialchars($restaurantName) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="food-floating-badges">
                                    <?php if ((int) $food['tag_hot'] === 1): ?>
                                        <span class="tag tag-hot">Signature</span>
                                    <?php endif; ?>

                                    <?php if ((int) $food['is_chay'] === 1): ?>
                                        <span class="tag tag-veg">Chay</span>
                                    <?php endif; ?>

                                    <?php if ((int) $food['is_cay'] === 1): ?>
                                        <span class="tag tag-spicy">Cay</span>
                                    <?php endif; ?>

                                    <?php if ((int) $food['phu_hop_tre_em'] === 1): ?>
                                        <span class="tag tag-kid">Kids</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="food-content">
                                <div class="food-topline">
                                    <span class="food-category">
                                        <?= htmlspecialchars($food['danh_muc'] ?: 'Món ăn') ?>
                                    </span>

                                    <span class="food-rating">
                                        ★ <?= htmlspecialchars((string) ($food['rating'] ?? '5.0')) ?>
                                    </span>
                                </div>

                                <h3><?= htmlspecialchars($food['ten_mon']) ?></h3>

                                <p class="food-desc">
                                    <?= htmlspecialchars($food['mo_ta'] ?: 'Món ăn được chế biến tươi mới, phù hợp để thưởng thức tại bàn.') ?>
                                </p>

                                <div class="food-footer">
                                    <div class="food-price">
                                        <?= number_format((float) $food['gia'], 0, ',', '.') ?>đ
                                    </div>

                                    <div class="order-line">
                                        <input
                                            type="number"
                                            class="qty-input"
                                            id="qty-<?= (int) $food['id'] ?>"
                                            value="1"
                                            min="1"
                                        >

                                        <button
                                            type="button"
                                            class="add-btn"
                                            onclick="addToCart(<?= (int) $food['id'] ?>)"
                                        >
                                            Thêm vào giỏ
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Không tìm thấy món phù hợp</h3>
                        <p>Thử đổi từ khóa tìm kiếm hoặc quay lại toàn bộ menu.</p>
                        <a href="/menu.php">Xem toàn bộ menu</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

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

<!-- FOODIE AI CHATBOT -->
<div id="foodie-chatbot">
    <button id="chat-toggle" type="button" onclick="toggleFoodieChat()">AI</button>

    <div id="chat-box" style="display: none;">
        <div class="chat-header">
            <strong>Foodie AI</strong>
            <button type="button" onclick="toggleFoodieChat()">×</button>
        </div>

        <div id="chat-messages">
            <div class="msg ai">
                Xin chào, mình là Foodie AI. Bạn có thể hỏi:
                "Gợi ý set món cho 3 người khoảng 300k không cay".
            </div>
        </div>

        <div class="chat-input">
            <input id="chat-message" placeholder="Bạn muốn ăn gì hôm nay?">
            <button type="button" onclick="sendFoodieMessage()">Gửi</button>
        </div>
    </div>
</div>

<script src="/assets/js/app.js?v=<?= time() ?>"></script>

<script>
function toggleFoodieChat() {
    const chatBox = document.getElementById('chat-box');

    if (!chatBox) {
        alert('Không tìm thấy chat-box.');
        return;
    }

    if (chatBox.style.display === 'none' || chatBox.style.display === '') {
        chatBox.style.display = 'flex';
    } else {
        chatBox.style.display = 'none';
    }
}

function appendFoodieMessage(sender, text) {
    const chatMessages = document.getElementById('chat-messages');

    if (!chatMessages) {
        return;
    }

    const div = document.createElement('div');
    div.className = sender === 'user' ? 'msg user' : 'msg ai';
    div.innerText = text;

    chatMessages.appendChild(div);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function sendFoodieMessage() {
    const input = document.getElementById('chat-message');

    if (!input) {
        return;
    }

    const message = input.value.trim();

    if (!message) {
        return;
    }

    appendFoodieMessage('user', message);
    input.value = '';

    const formData = new FormData();
    formData.append('message', message);

    fetch('/api/ai_chat.php', {
        method: 'POST',
        body: formData
    })
    .then(function (res) {
        return res.json();
    })
    .then(function (res) {
        if (res.need_table) {
            appendFoodieMessage('ai', res.message);

            if (typeof openTablePopup === 'function') {
                openTablePopup();
            }

            return;
        }

        if (!res.success) {
            appendFoodieMessage('ai', res.message || 'Foodie AI đang lỗi.');
            return;
        }

        const data = res.data;

        appendFoodieMessage(
            'ai',
            data.message || data.ly_do || 'Mình đã gợi ý cho bạn.'
        );

        if (
            data.type === 'recommend_set' &&
            data.set_mon &&
            data.set_mon.length > 0
        ) {
            renderFoodieSet(data);
        }
    })
    .catch(function () {
        appendFoodieMessage('ai', 'Không thể kết nối Foodie AI.');
    });
}

function renderFoodieSet(data) {
    const chatMessages = document.getElementById('chat-messages');

    if (!chatMessages) {
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'ai-set-box';

    let html = '<strong>Set gợi ý:</strong><br>';

    data.set_mon.forEach(function (item) {
        html += escapeFoodieHtml(item.ten_mon)
            + ' x '
            + item.so_luong
            + ' - '
            + Number(item.gia).toLocaleString('vi-VN')
            + 'đ<br>';
    });

    html += '<b>Tổng: '
        + Number(data.tong_tien).toLocaleString('vi-VN')
        + 'đ</b><br>';

    if (data.ly_do) {
        html += '<small>' + escapeFoodieHtml(data.ly_do) + '</small><br>';
    }

    html += '<button type="button" onclick=\'addFoodieSetToCart('
        + JSON.stringify(data.set_mon)
        + ')\'>Thêm toàn bộ set vào giỏ</button>';

    wrapper.innerHTML = html;

    chatMessages.appendChild(wrapper);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function addFoodieSetToCart(items) {
    const formData = new FormData();
    formData.append('items', JSON.stringify(items));

    fetch('/api/cart_add_set.php', {
        method: 'POST',
        body: formData
    })
    .then(function (res) {
        return res.json();
    })
    .then(function (res) {
        if (res.need_table) {
            appendFoodieMessage('ai', res.message || 'Vui lòng nhập số bàn.');

            if (typeof openTablePopup === 'function') {
                openTablePopup();
            }

            return;
        }

        appendFoodieMessage('ai', res.message || 'Đã thêm set món vào giỏ.');
    })
    .catch(function () {
        appendFoodieMessage('ai', 'Không thể thêm set món vào giỏ.');
    });
}

function escapeFoodieHtml(str) {
    return String(str).replace(/[&<>"']/g, function (m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[m];
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('chat-message');

    if (input) {
        input.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                sendFoodieMessage();
            }
        });
    }
});
</script>

</body>
</html>
