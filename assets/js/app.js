function openTablePopup() {
    const popup = document.getElementById('table-popup');

    if (popup) {
        popup.classList.remove('hidden');
    }
}

function closeTablePopup() {
    const popup = document.getElementById('table-popup');

    if (popup) {
        popup.classList.add('hidden');
    }
}

function saveTable() {
    const input = document.getElementById('so_ban');
    const soBan = input ? input.value.trim() : '';

    const formData = new FormData();
    formData.append('so_ban', soBan);

    fetch('/api/set_table.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        alert(res.message);

        if (res.success) {
            closeTablePopup();
            location.reload();
        }
    })
    .catch(() => {
        alert('Không thể kết nối server.');
    });
}

function addToCart(monAnId) {
    const qtyInput = document.getElementById('qty-' + monAnId);
    const qty = qtyInput ? qtyInput.value : 1;

    const formData = new FormData();
    formData.append('mon_an_id', monAnId);
    formData.append('so_luong', qty);

    fetch('/api/cart_add.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.need_table) {
            openTablePopup();
            return;
        }

        alert(res.message);
    })
    .catch(() => {
        alert('Không thể thêm món.');
    });
}

function updateCart(cartId, change) {
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('change', change);

    fetch('/api/cart_update.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            location.reload();
        } else {
            alert(res.message);
        }
    });
}

function removeCartItem(cartId) {
    if (!confirm('Xóa món này khỏi giỏ?')) {
        return;
    }

    const formData = new FormData();
    formData.append('cart_id', cartId);

    fetch('/api/cart_remove.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            location.reload();
        } else {
            alert(res.message);
        }
    });
}

function clearCart() {
    if (!confirm('Xóa toàn bộ giỏ hàng?')) {
        return;
    }

    fetch('/api/cart_clear.php', {
        method: 'POST'
    })
    .then(res => res.json())
    .then(res => {
        alert(res.message);

        if (res.success) {
            location.reload();
        }
    });
}

function submitOrder() {
    if (!confirm('Xác nhận gửi order về bếp?')) {
        return;
    }

    const noteEl = document.getElementById('order-note');

    const formData = new FormData();
    formData.append('note', noteEl ? noteEl.value : '');

    fetch('/api/order_submit.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        alert(res.message);

        if (res.success) {
            window.location.href = '/order_success.php?ma_don=' + encodeURIComponent(res.ma_don);
        }
    });
}