<?php
/**
 * Kobo Choice - Paystack Card Payments
 *
 * Security model:
 *  - init() computes the amount to charge from the order's own `total`
 *    (set server-side at order_create time from live product prices) —
 *    the browser never gets to choose what amount is charged.
 *  - A fresh, unguessable reference is generated server-side and stored
 *    against the order before the popup ever opens.
 *  - verify() and webhook() NEVER trust the client's "it succeeded"
 *    signal. They independently ask Paystack's API / validate Paystack's
 *    webhook signature, then require status=success AND the verified
 *    amount + currency to exactly match what we expected. Any mismatch
 *    is logged and the order is left pending rather than marked paid.
 *  - The final status flip is an atomic conditional UPDATE
 *    (`WHERE status = 'pending'`) so a race between the browser callback
 *    and the webhook can only ever apply once.
 */
class PaystackAPI
{
    public static function init(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $orderId = (int) ($_POST['order_id'] ?? 0);
        if (!$orderId) {
            json_error('Order ID is required.');
            return;
        }

        $order = self::authorizedOrder($orderId);
        if (!$order) {
            json_error('Order not found.', 404);
            return;
        }

        if ($order['status'] !== 'pending') {
            json_error('This order has already been processed.');
            return;
        }

        if (empty($order['email']) || !is_email($order['email'])) {
            json_error('A valid email is required to pay by card.');
            return;
        }

        $amountMinorUnits = (int) round(((float) $order['total']) * 100);
        if ($amountMinorUnits <= 0) {
            json_error('Invalid order amount.');
            return;
        }

        // Fresh, unguessable reference (128 bits of randomness) for every
        // attempt, so an abandoned one can't be replayed later.
        $reference = 'KOBO-' . $orderId . '-' . bin2hex(random_bytes(16));
        $db->update('orders', ['payment_reference' => $reference], 'id = ?', [$orderId]);

        $result = paystack_initialize_transaction([
            'email'        => $order['email'],
            'amount'       => $amountMinorUnits,
            'currency'     => PAYSTACK_CURRENCY,
            'reference'    => $reference,
            'callback_url' => self::callbackUrl(),
            'metadata'     => ['order_id' => $orderId],
        ]);

        if ($result === null) {
            json_error('Could not reach the payment provider. Please try again in a moment.');
            return;
        }

        $authorizationUrl = $result['data']['authorization_url'] ?? null;
        if (empty($result['status']) || !$authorizationUrl) {
            error_log('[Paystack] Initialize rejected for order #' . $orderId . ': ' . ($result['message'] ?? 'unknown'));
            json_error($result['message'] ?? 'Could not start the payment. Please try again.');
            return;
        }

        json_success('', [
            'authorization_url' => $authorizationUrl,
            'reference'         => $reference,
        ]);
    }

    /** Absolute URL Paystack sends the customer back to after payment. */
    private static function callbackUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . BASE_URL . '/payment-callback';
    }

    /**
     * Server-side confirmation used by the Paystack return page. Takes only a
     * reference, re-verifies it against Paystack's API and applies the same
     * amount/currency checks as every other path. Returns true only if the
     * order is genuinely paid (or was already confirmed).
     */
    public static function confirmReference(string $reference): bool
    {
        global $db;

        if (!preg_match('/^KOBO-\d+-[a-f0-9]{32}$/', $reference)) {
            return false;
        }

        $order = $db->fetch("SELECT * FROM orders WHERE payment_reference = ?", [$reference]);
        if (!$order) {
            return false;
        }

        if ($order['status'] === 'paid') {
            return true;
        }

        if ($order['status'] !== 'pending') {
            return false;
        }

        $result = paystack_verify_transaction($reference);
        if ($result === null) {
            return false;
        }

        return self::confirmIfGenuine($order, $result['data'] ?? [], $reference);
    }

    public static function verify(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $reference = trim((string) ($_POST['reference'] ?? ''));
        if ($reference === '' || !preg_match('/^KOBO-\d+-[a-f0-9]{32}$/', $reference)) {
            json_error('Invalid payment reference.');
            return;
        }

        $order = $db->fetch("SELECT * FROM orders WHERE payment_reference = ?", [$reference]);
        if (!$order || !self::ownsOrder($order)) {
            json_error('Order not found.', 404);
            return;
        }

        if ($order['status'] === 'paid') {
            // Webhook may have already confirmed it — idempotent success either way.
            json_success('Payment already confirmed.', ['order_id' => (int) $order['id']]);
            return;
        }

        if ($order['status'] !== 'pending') {
            json_error('This order is no longer awaiting payment.');
            return;
        }

        $result = paystack_verify_transaction($reference);
        if ($result === null) {
            json_error('Could not reach the payment provider. Please try again in a moment.');
            return;
        }

        if (self::confirmIfGenuine($order, $result['data'] ?? [], $reference)) {
            json_success('Payment verified successfully.', ['order_id' => (int) $order['id']]);
        } else {
            json_error('We could not confirm your payment. If you were charged, please contact support with your order number.');
        }
    }

    /**
     * Public webhook endpoint — reachable by Paystack's servers directly (no
     * session, no CSRF token). Authenticity comes entirely from the
     * X-Paystack-Signature header, which is an HMAC-SHA512 of the raw body
     * keyed with the secret key. This is the authoritative, server-to-server
     * confirmation path once the site is deployed behind a public HTTPS URL
     * registered in the Paystack dashboard; it is inert on localhost since
     * Paystack can't reach it there.
     */
    public static function webhook(): void
    {
        global $db;

        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
        $expected = hash_hmac('sha512', (string) $payload, PAYSTACK_SECRET_KEY);

        if ($signature === '' || !hash_equals($expected, $signature)) {
            http_response_code(401);
            exit;
        }

        $event = json_decode((string) $payload, true);
        if (!is_array($event) || ($event['event'] ?? '') !== 'charge.success') {
            http_response_code(200);
            exit;
        }

        $data = $event['data'] ?? [];
        $reference = (string) ($data['reference'] ?? '');
        $order = $reference !== '' ? $db->fetch("SELECT * FROM orders WHERE payment_reference = ?", [$reference]) : null;

        if ($order && $order['status'] === 'pending') {
            self::confirmIfGenuine($order, $data, $reference);
        }

        http_response_code(200);
        exit;
    }

    /**
     * Shared verification core for both the browser-triggered verify() call
     * and the webhook: requires status=success and an exact amount +
     * currency match against the order's own stored total before flipping
     * status via an atomic conditional UPDATE. Returns true only if this
     * call was the one that actually confirmed the order (or it was already
     * confirmed) — false on any mismatch, which is logged for review.
     */
    private static function confirmIfGenuine(array $order, array $data, string $reference): bool
    {
        global $db;

        $expectedAmount = (int) round(((float) $order['total']) * 100);
        $status = $data['status'] ?? '';
        $amount = (int) ($data['amount'] ?? 0);
        $currency = $data['currency'] ?? '';

        if ($status !== 'success' || $amount !== $expectedAmount || $currency !== PAYSTACK_CURRENCY) {
            error_log(sprintf(
                '[Paystack] Verification mismatch for order #%d ref %s: status=%s amount=%d expected=%d currency=%s',
                $order['id'], $reference, $status, $amount, $expectedAmount, $currency
            ));
            return false;
        }

        $affected = $db->update(
            'orders',
            ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')],
            'id = ? AND payment_reference = ? AND status = ?',
            [$order['id'], $reference, 'pending']
        );

        // affected === 0 just means another caller (webhook vs. browser
        // callback) already confirmed it a moment earlier — still a success,
        // but the thank-you email must only go out on the winning update so
        // the customer never receives it twice.
        if ($affected > 0) {
            self::sendPaidConfirmation((int) $order['id']);
        }

        return true;
    }

    /**
     * Emails the "thank you for your purchase" confirmation with the itemised
     * order and the PDF receipt attached. Deliberately swallows every failure:
     * the payment has already succeeded at this point, so a mail problem must
     * never surface as a payment error to the customer.
     */
    private static function sendPaidConfirmation(int $orderId): void
    {
        global $db;

        try {
            $order = $db->fetch(
                "SELECT o.*, c.name AS customer_name
                 FROM orders o
                 LEFT JOIN customers c ON o.customer_id = c.id
                 WHERE o.id = ?",
                [$orderId]
            );
            if (!$order) {
                return;
            }

            $items = $db->fetchAll(
                "SELECT name, price, quantity FROM order_items WHERE order_id = ?",
                [$orderId]
            );

            send_order_paid_email($order, $items);
        } catch (Throwable $e) {
            error_log('[Paystack] Thank-you email failed for order #' . $orderId . ': ' . $e->getMessage());
        }
    }

    private static function authorizedOrder(int $orderId): ?array
    {
        global $db;
        $order = $db->fetch("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order || !self::ownsOrder($order)) {
            return null;
        }
        return $order;
    }

    private static function ownsOrder(array $order): bool
    {
        return (isset($_SESSION['customer_id']) && (int) $order['customer_id'] === (int) $_SESSION['customer_id'])
            || in_array((int) $order['id'], $_SESSION['placed_orders'] ?? [], true);
    }
}
