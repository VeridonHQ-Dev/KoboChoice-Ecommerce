<?php
require_once __DIR__ . '/includes/auth.php';

$orderId = (int)($_GET['id'] ?? 0);
$order = $orderId ? $db->fetch("SELECT * FROM orders WHERE id = ?", [$orderId]) : null;

// Access control: a customer may view their own order; anyone may view an
// order they just placed in this session. Everything else is redirected away
// so order IDs cannot be enumerated.
$ownsOrder = $order && (
    (isset($_SESSION['customer_id']) && (int)$order['customer_id'] === (int)$_SESSION['customer_id'])
    || in_array($orderId, $_SESSION['placed_orders'] ?? [], true)
);

if (!$ownsOrder) {
    // A customer following the "View Your Order" link from their emailed
    // receipt often has no session on that device. Send them to login with a
    // return path so they land on the order instead of the home page.
    // Ownership is re-checked after login, so this leaks nothing.
    if (!is_customer_logged_in() && $orderId > 0) {
        redirect(BASE_URL . '/login?redirect=' . urlencode(BASE_URL . '/order-confirmation?id=' . $orderId));
    }
    redirect(BASE_URL . '/');
}

$order['items'] = $db->fetchAll(
    "SELECT oi.*, p.slug FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?",
    [$orderId]
);

$pageTitle = 'Order Confirmed';
$bodyClass = 'inner-page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/views/order-confirmation-view.php';
require_once __DIR__ . '/includes/footer.php';
