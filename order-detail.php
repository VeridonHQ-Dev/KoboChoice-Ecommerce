<?php
require_once __DIR__ . '/includes/auth.php';

if (!is_customer_logged_in()) {
    set_flash('error', 'Please login to view your order.');
    redirect(base_url('/login'));
}

$orderId = (int)($_GET['id'] ?? 0);
$customerId = $_SESSION['customer_id'];

// A customer may only view their own orders.
$order = $orderId ? $db->fetch(
    "SELECT * FROM orders WHERE id = ? AND customer_id = ?",
    [$orderId, $customerId]
) : null;

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect(base_url('/orders'));
}

$order['items'] = $db->fetchAll(
    "SELECT oi.*, p.slug FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?",
    [$orderId]
);

$pageTitle = 'Order #' . $order['id'];
$bodyClass = 'inner-page';
$orderDetailMode = true;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/views/order-confirmation-view.php';
require_once __DIR__ . '/includes/footer.php';
