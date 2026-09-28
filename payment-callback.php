<?php
/**
 * Kobo Choice - Paystack Return URL
 *
 * Paystack sends the customer back here after the hosted checkout. The
 * reference in the query string is treated as an untrusted hint only: the
 * order is looked up by that reference and then confirmed strictly through
 * PaystackAPI::confirmReference(), which re-verifies against Paystack's API
 * and requires an exact amount + currency match before anything is marked
 * paid. A forged ?reference= can therefore never mark an order paid.
 */
require_once __DIR__ . '/includes/auth.php';
// auth.php doesn't register the class autoloader (only api/index.php did),
// so PaystackAPI must be made available explicitly here.
require_once __DIR__ . '/config/autoload.php';

$reference = trim((string)($_GET['reference'] ?? $_GET['trxref'] ?? ''));

if ($reference === '' || !preg_match('/^KOBO-\d+-[a-f0-9]{32}$/', $reference)) {
    set_flash('error', 'Invalid payment reference.');
    redirect(BASE_URL . '/');
}

$order = $db->fetch("SELECT * FROM orders WHERE payment_reference = ?", [$reference]);

$ownsOrder = $order && (
    (isset($_SESSION['customer_id']) && (int)$order['customer_id'] === (int)$_SESSION['customer_id'])
    || in_array((int)$order['id'], $_SESSION['placed_orders'] ?? [], true)
);

if (!$ownsOrder) {
    redirect(BASE_URL . '/');
}

$orderId = (int)$order['id'];

if ($order['status'] === 'pending') {
    $confirmed = PaystackAPI::confirmReference($reference);

    if (!$confirmed) {
        set_flash('error', 'We could not confirm your payment. If you were charged, please contact support with your order number.');
    }
}

redirect(BASE_URL . '/order-confirmation?id=' . $orderId);
