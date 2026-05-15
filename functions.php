<?php
function getSampleMenuItems() {
    return [
        ['id' => 1, 'name' => 'Phở bò', 'price' => 65000],
        ['id' => 2, 'name' => 'Bún chả', 'price' => 70000],
        ['id' => 3, 'name' => 'Cơm tấm', 'price' => 55000],
        ['id' => 4, 'name' => 'Gỏi cuốn', 'price' => 45000],
    ];
}

function getCart() {
    return $_SESSION['cart'] ?? [];
}

function calculateCartTotal(array $cart) {
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

function addToCart(int $itemId) {
    $menu = getSampleMenuItems();
    foreach ($menu as $item) {
        if ($item['id'] === $itemId) {
            $cart = &$_SESSION['cart'];
            if (!isset($cart[$itemId])) {
                $cart[$itemId] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => 0,
                ];
            }
            $cart[$itemId]['quantity']++;
            return;
        }
    }
}

function removeFromCart(int $itemId) {
    if (isset($_SESSION['cart'][$itemId])) {
        unset($_SESSION['cart'][$itemId]);
    }
}

function clearCart() {
    unset($_SESSION['cart']);
}
