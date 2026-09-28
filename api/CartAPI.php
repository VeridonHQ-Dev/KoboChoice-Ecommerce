<?php
class CartAPI
{
    public static function get(): void
    {
        $cart = get_cart();
        $items = [];
        $total = 0;
        global $db;

        foreach ($cart as $item) {
            $product = $db->fetch(
                "SELECT id, name, slug, price, old_price, image, stock
                 FROM products WHERE id = ? AND status = 'active'",
                [$item['product_id']]
            );

            if ($product) {
                $lineTotal = $product['price'] * $item['quantity'];
                $product['quantity'] = $item['quantity'];
                $product['line_total'] = $lineTotal;
                $items[] = $product;
                $total += $lineTotal;
            }
        }

        json_success('', [
            'items'     => $items,
            'subtotal'  => $total,
            'count'     => cart_count(),
            'currency'  => CURRENCY_SYMBOL,
        ]);
    }

    public static function add(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        if (!$productId) {
            json_error('Product ID required.');
            return;
        }

        global $db;
        $product = $db->fetch(
            "SELECT id, name, price, stock FROM products WHERE id = ? AND status = 'active'",
            [$productId]
        );

        if (!$product) {
            json_error('Product not found.', 404);
            return;
        }

        if ($product['stock'] < $quantity) {
            json_error('Insufficient stock.');
            return;
        }

        $cart = get_cart();
        $found = false;

        foreach ($cart as &$item) {
            if ($item['product_id'] === $productId) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            $cart[] = ['product_id' => $productId, 'quantity' => $quantity];
        }

        set_cart($cart);
        json_success('Added to cart.', ['count' => cart_count()]);
    }

    public static function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $cart = get_cart();

        foreach ($cart as &$item) {
            if ($item['product_id'] === $productId) {
                $item['quantity'] = $quantity;
                break;
            }
        }
        unset($item);

        set_cart($cart);
        json_success('Cart updated.', ['count' => cart_count()]);
    }

    public static function remove(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $cart = get_cart();

        $cart = array_filter($cart, fn($item) => $item['product_id'] !== $productId);
        $cart = array_values($cart);

        set_cart($cart);
        json_success('Removed from cart.', ['count' => cart_count()]);
    }

    public static function count(): void
    {
        json_success('', ['count' => cart_count()]);
    }

    public static function applyCoupon(): void
    {
        global $db;

        $code = strtoupper(trim($_POST['code'] ?? ''));
        if (empty($code)) {
            json_error('Coupon code required.');
            return;
        }

        $coupon = $db->fetch(
            "SELECT * FROM coupons WHERE code = ? AND is_active = 1",
            [$code]
        );

        if (!$coupon) {
            json_error('Invalid coupon code.');
            return;
        }

        if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
            json_error('This coupon has expired.');
            return;
        }

        if ($coupon['max_uses'] && $coupon['used_count'] >= $coupon['max_uses']) {
            json_error('This coupon has reached its usage limit.');
            return;
        }

        $cartTotal = cart_total();
        if ($cartTotal < $coupon['min_order']) {
            json_error('Minimum order of ' . format_price($coupon['min_order']) . ' required.');
            return;
        }

        $discount = 0;
        if ($coupon['type'] === 'percentage') {
            $discount = $cartTotal * ($coupon['value'] / 100);
        } else {
            $discount = min($coupon['value'], $cartTotal);
        }

        $_SESSION['coupon'] = [
            'code'     => $coupon['code'],
            'type'     => $coupon['type'],
            'value'    => $coupon['value'],
            'discount' => $discount,
        ];

        json_success('Coupon applied.', [
            'discount' => $discount,
            'code'     => $coupon['code'],
            'currency' => CURRENCY_SYMBOL,
        ]);
    }
}
