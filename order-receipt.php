<?php
/**
 * Kobo Choice - Order Receipt (PDF download)
 *
 * The document itself is built by includes/receipt.php so the identical
 * receipt can also be attached to the "payment received" email.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/receipt.php';

if (!is_customer_logged_in()) {
    set_flash('error', 'Please login to view your order.');
    redirect(base_url('/login'));
}

$orderId = (int)($_GET['id'] ?? 0);
$customerId = $_SESSION['customer_id'];

// A customer may only download the receipt for their own orders.
$order = $orderId ? $db->fetch(
    "SELECT * FROM orders WHERE id = ? AND customer_id = ?",
    [$orderId, $customerId]
) : null;

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect(base_url('/orders'));
}

$items = $db->fetchAll(
    "SELECT * FROM order_items WHERE order_id = ?",
    [$orderId]
);

// Fall back to the session name for the "Billed To" block on customer orders.
if (empty($order['guest_name'])) {
    $order['customer_name'] = $_SESSION['customer_name'] ?? 'Customer';
}

$pdf = generate_order_receipt_pdf($order, $items);

if ($pdf === null) {
    set_flash('error', 'Sorry, the receipt could not be generated. Please try again.');
    redirect(base_url('/orders'));
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="order-' . (int)$order['id'] . '-receipt.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
