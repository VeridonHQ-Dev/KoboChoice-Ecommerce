<?php
class OrderAPI
{
    public static function create(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $cart = get_cart();
        if (empty($cart)) {
            json_error('Your cart is empty.');
            return;
        }

        $data = $_POST;
        $errors = required_fields($data, [
            'name'    => 'Name',
            'email'   => 'Email',
            'phone'   => 'Phone',
            'address' => 'Address',
            'city'    => 'City',
            'state'   => 'State',
        ]);

        if (!empty($errors)) {
            json_error(implode(' ', $errors));
            return;
        }

        $customerId = $_SESSION['customer_id'] ?? null;
        $pdo = $db->getConnection();

        try {
            $pdo->beginTransaction();

            // Build the order from an authoritative, current read of each product.
            $subtotal = 0;
            $lines = [];
            foreach ($cart as $item) {
                $product = $db->fetch(
                    "SELECT id, name, price, stock FROM products WHERE id = ? AND status = 'active'",
                    [$item['product_id']]
                );
                if (!$product) {
                    throw new RuntimeException('A product in your cart is no longer available.');
                }

                $qty = (int) $item['quantity'];
                if ($qty < 1 || $product['stock'] < $qty) {
                    throw new RuntimeException('Insufficient stock for "' . $product['name'] . '".');
                }

                $subtotal += $product['price'] * $qty;
                $lines[] = [
                    'product_id' => $product['id'],
                    'name'       => $product['name'],
                    'price'      => $product['price'],
                    'quantity'   => $qty,
                ];
            }

            // Recompute the coupon discount against the CURRENT subtotal so a
            // stale session value can never exceed what is actually owed.
            $discount = 0;
            $couponCode = null;
            if (!empty($_SESSION['coupon'])) {
                $coupon = $db->fetch(
                    "SELECT * FROM coupons WHERE code = ? AND is_active = 1",
                    [$_SESSION['coupon']['code']]
                );
                $expired = $coupon && $coupon['expires_at'] && strtotime($coupon['expires_at']) < time();
                $exhausted = $coupon && $coupon['max_uses'] && $coupon['used_count'] >= $coupon['max_uses'];

                if ($coupon && !$expired && !$exhausted && $subtotal >= $coupon['min_order']) {
                    if ($coupon['type'] === 'percentage') {
                        $discount = $subtotal * ($coupon['value'] / 100);
                    } else {
                        $discount = min($coupon['value'], $subtotal);
                    }
                    $couponCode = $coupon['code'];
                }
            }

            // Tax is charged on the goods subtotal, then added on top of the
            // discounted amount. Rounded to 2dp here so the stored total is
            // exactly what the payment gateway will be asked to charge.
            $tax = round($subtotal * TAX_RATE, 2);
            $total = round(max(0, $subtotal - $discount) + $tax, 2);

            $orderId = $db->insert('orders', [
                'customer_id'       => $customerId,
                'guest_name'        => $customerId ? null : $data['name'],
                'email'             => $data['email'],
                'phone'             => $data['phone'],
                'shipping_address'  => $data['address'],
                'city'              => $data['city'],
                'state'             => $data['state'],
                'country'           => $data['country'] ?? 'Nigeria',
                'note'              => $data['note'] ?? null,
                'payment_method'    => $data['payment_method'] ?? null,
                'tax'               => $tax,
                'total'             => $total,
                'status'            => 'pending',
            ]);

            foreach ($lines as $line) {
                $db->insert('order_items', [
                    'order_id'   => $orderId,
                    'product_id' => $line['product_id'],
                    'name'       => $line['name'],
                    'price'      => $line['price'],
                    'quantity'   => $line['quantity'],
                ]);

                // Conditional decrement guards against a concurrent oversell.
                $affected = $db->query(
                    "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?",
                    [$line['quantity'], $line['product_id'], $line['quantity']]
                )->rowCount();

                if ($affected === 0) {
                    throw new RuntimeException('Stock changed for "' . $line['name'] . '". Please review your cart.');
                }
            }

            // Only now that the order is committed do we consume a coupon use.
            if ($couponCode !== null) {
                $db->query(
                    "UPDATE coupons SET used_count = used_count + 1 WHERE code = ?",
                    [$couponCode]
                );
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[Kobo Order] ' . $e->getMessage());
            json_error($e->getMessage());
            return;
        }

        unset($_SESSION['coupon']);
        set_cart([]);

        // Authorize the confirmation page for this browser (esp. guests, who
        // have no customer_id link) without exposing other orders by ID.
        $_SESSION['placed_orders'][] = $orderId;

        json_success('Order placed successfully.', ['order_id' => $orderId]);
    }

    public static function list(): void
    {
        global $db;

        $customerId = $_SESSION['customer_id'];
        $pagination = paginate($db, 'orders', 'customer_id = ?', [$customerId]);

        $orders = $db->fetchAll(
            "SELECT * FROM orders WHERE customer_id = ?
             ORDER BY created_at DESC
             LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
            [$customerId]
        );

        json_success('', ['orders' => $orders, 'pagination' => $pagination]);
    }

    public static function detail(): void
    {
        global $db;

        $orderId = (int)($_GET['id'] ?? 0);
        $customerId = $_SESSION['customer_id'];

        $order = $db->fetch(
            "SELECT * FROM orders WHERE id = ? AND customer_id = ?",
            [$orderId, $customerId]
        );

        if (!$order) {
            json_error('Order not found.', 404);
            return;
        }

        $order['items'] = $db->fetchAll(
            "SELECT oi.*, p.image, p.slug
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?",
            [$orderId]
        );

        json_success('', ['order' => $order]);
    }

    public static function adminList(): void
    {
        global $db;

        $where = "1";
        $params = [];

        if (!empty($_GET['status'])) {
            $where .= " AND status = ?";
            $params[] = $_GET['status'];
        }

        if (!empty($_GET['search'])) {
            $where .= " AND (id LIKE ? OR email LIKE ? OR guest_name LIKE ?)";
            $term = '%' . $_GET['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $pagination = paginate($db, 'orders', $where, $params, ADMIN_ITEMS_PER_PAGE);

        $orders = $db->fetchAll(
            "SELECT o.*, c.name AS customer_name
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             WHERE $where
             ORDER BY o.created_at DESC
             LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
            $params
        );

        json_success('', ['orders' => $orders, 'pagination' => $pagination]);
    }

    public static function adminUpdate(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $orderId = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $validStatuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];

        if (!$orderId || !in_array($status, $validStatuses)) {
            json_error('Invalid order ID or status.');
            return;
        }

        $order = $db->fetch("SELECT id FROM orders WHERE id = ?", [$orderId]);
        if (!$order) {
            json_error('Order not found.', 404);
            return;
        }

        $db->update('orders', ['status' => $status], 'id = ?', [$orderId]);

        $db->insert('admin_logs', [
            'admin_id'    => $_SESSION['admin_id'],
            'action'      => 'order_update',
            'description' => "Order #$orderId status changed to $status",
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        json_success('Order updated.');
    }

    public static function delete(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $orderId = (int)($_POST['id'] ?? 0);
        if (!$orderId) {
            json_error('Invalid order ID.');
            return;
        }

        $order = $db->fetch("SELECT id FROM orders WHERE id = ?", [$orderId]);
        if (!$order) {
            json_error('Order not found.', 404);
            return;
        }

        $db->delete('order_items', 'order_id = ?', [$orderId]);
        $db->delete('orders', 'id = ?', [$orderId]);

        $db->insert('admin_logs', [
            'admin_id'    => $_SESSION['admin_id'],
            'action'      => 'order_delete',
            'description' => "Order #$orderId deleted",
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        json_success('Order deleted.');
    }

    public static function uploadPaymentReceipt(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $customerId = $_SESSION['customer_id'] ?? null;

        if (!$orderId) {
            json_error('Order ID required.');
            return;
        }

        $order = $db->fetch("SELECT * FROM orders WHERE id = ? AND (customer_id = ? OR customer_id IS NULL)", [$orderId, $customerId]);
        if (!$order) {
            json_error('Order not found.', 404);
            return;
        }

        if (empty($_FILES['receipt']['name'])) {
            json_error('Please select a payment receipt file.');
            return;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        if (!in_array($_FILES['receipt']['type'], $allowed)) {
            json_error('Only JPG, PNG, GIF, WebP, or PDF files are allowed.');
            return;
        }

        if ($_FILES['receipt']['size'] > 5 * 1024 * 1024) {
            json_error('File must be under 5MB.');
            return;
        }

        $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
        $filename = 'receipt_' . $orderId . '_' . time() . '.' . strtolower($ext);
        $dest = PRODUCT_IMG_PATH . '/' . $filename;

        if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $dest)) {
            json_error('Failed to upload file.');
            return;
        }

        $db->update('orders', ['payment_receipt' => $filename], 'id = ?', [$orderId]);

        json_success('Payment receipt uploaded successfully.');
    }
}
