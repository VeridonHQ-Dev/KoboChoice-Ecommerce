<?php
/**
 * Kobo Choice - Card Payment Page
 *
 * Dedicated full page (no popup) where the customer confirms the amount
 * before being sent to Paystack's secure hosted checkout.
 */
require_once __DIR__ . '/includes/auth.php';

$orderId = (int)($_GET['id'] ?? 0);
$order = $orderId ? $db->fetch("SELECT * FROM orders WHERE id = ?", [$orderId]) : null;

// Same access rule as the confirmation page: your own order, or one placed
// in this browser session. Prevents order IDs being enumerated.
$ownsOrder = $order && (
    (isset($_SESSION['customer_id']) && (int)$order['customer_id'] === (int)$_SESSION['customer_id'])
    || in_array($orderId, $_SESSION['placed_orders'] ?? [], true)
);

if (!$ownsOrder) {
    redirect(BASE_URL . '/');
}

// Nothing to pay for — send them to the confirmation instead.
if ($order['status'] !== 'pending') {
    redirect(BASE_URL . '/order-confirmation?id=' . $orderId);
}

$pageTitle = 'Complete Payment';
$bodyClass = 'inner-page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/views/payment-view.php';
require_once __DIR__ . '/includes/footer.php';
