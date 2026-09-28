<?php
/**
 * Kobo Choice - Order Receipt Builder
 *
 * Shared PDF generation so the same receipt is produced whether the customer
 * downloads it from their order page or it is attached to the "payment
 * received" email. Previously this template lived inside order-receipt.php
 * and could only be streamed to the browser.
 */

require_once __DIR__ . '/functions.php';

/**
 * Renders the receipt as HTML (the Dompdf source document).
 *
 * @param array $order Full order row
 * @param array $items Rows from order_items
 */
function build_order_receipt_html(array $order, array $items): string
{
    $logoFile = ROOT_PATH . '/images/menu/logo/login-logo.png';
    $logoData = is_file($logoFile)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile))
        : '';

    $customerName = $order['guest_name'] ?: ($order['customer_name'] ?? 'Customer');
    $shippingLines = array_filter([
        trim((string)($order['shipping_address'] ?? '')),
        trim(implode(', ', array_filter([$order['city'] ?? '', $order['state'] ?? '', $order['country'] ?? '']))),
        trim((string)($order['phone'] ?? '')),
    ]);

    $receiptTax = (float)($order['tax'] ?? 0);
    $itemsTotal = 0;
    foreach ($items as $item) {
        $itemsTotal += (float)$item['price'] * (int)$item['quantity'];
    }

    ob_start();
    ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; color: #1f2430; font-size: 12px; }
    .receipt-header { width: 100%; border-bottom: 3px solid #F48201; padding-bottom: 14px; margin-bottom: 20px; }
    .receipt-header img { height: 42px; }
    .receipt-title { text-align: right; }
    .receipt-title h1 { margin: 0; font-size: 20px; color: #1f2430; }
    .receipt-title p { margin: 4px 0 0; color: #6b7280; font-size: 11px; }
    table.layout { width: 100%; border-collapse: collapse; }
    table.layout td { vertical-align: top; }
    .info-block { width: 48%; }
    .info-block h4 { margin: 0 0 6px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #9333ea; }
    .info-block p { margin: 0 0 3px; font-size: 12px; line-height: 1.5; }
    .items-table { width: 100%; border-collapse: collapse; margin-top: 24px; }
    .items-table th { background: #f3f4f6; text-align: left; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; padding: 8px 10px; }
    .items-table td { padding: 9px 10px; border-bottom: 1px solid #e5e7eb; font-size: 12px; }
    .items-table th.num, .items-table td.num { text-align: right; }
    .totals-table { width: 260px; margin-left: auto; margin-top: 14px; border-collapse: collapse; }
    .totals-table td { padding: 5px 10px; font-size: 12px; }
    .totals-table td.label { color: #6b7280; }
    .totals-table td.value { text-align: right; }
    .totals-table tr.grand td { border-top: 2px solid #1f2430; font-weight: bold; font-size: 14px; padding-top: 8px; }
    .status-pill { display: inline-block; padding: 3px 10px; border-radius: 10px; background: #dbeafe; color: #2563eb; font-size: 11px; font-weight: bold; text-transform: capitalize; }
    .footer-note { margin-top: 36px; padding-top: 14px; border-top: 1px solid #e5e7eb; font-size: 10.5px; color: #9ca3af; text-align: center; }
</style>
</head>
<body>
    <table class="receipt-header" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:50%;"><?php if ($logoData): ?><img src="<?= $logoData ?>"><?php endif; ?></td>
            <td class="receipt-title" style="width:50%;">
                <h1>Order Receipt</h1>
                <p>Order #<?= (int)$order['id'] ?> &middot; <?= sanitize(format_date($order['created_at'], 'M d, Y')) ?></p>
            </td>
        </tr>
    </table>

    <table class="layout" cellpadding="0" cellspacing="0">
        <tr>
            <td class="info-block">
                <h4>Billed To</h4>
                <p><strong><?= sanitize($customerName) ?></strong></p>
                <p><?= sanitize($order['email'] ?? '') ?></p>
                <?php foreach ($shippingLines as $line): ?>
                    <p><?= sanitize($line) ?></p>
                <?php endforeach; ?>
            </td>
            <td class="info-block" style="text-align:right;">
                <h4>Order Info</h4>
                <p>Status: <span class="status-pill"><?= sanitize($order['status'] === 'completed' ? 'Completed/Closed' : ucfirst($order['status'])) ?></span></p>
                <?php if (!empty($order['payment_method'])): ?>
                    <p>Payment: <?= sanitize(ucwords(str_replace('_', ' ', $order['payment_method']))) ?></p>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th>Product</th>
                <th class="num">Unit Price</th>
                <th class="num">Qty</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= sanitize($item['name']) ?></td>
                <td class="num"><?= sanitize(format_price((float)$item['price'])) ?></td>
                <td class="num"><?= (int)$item['quantity'] ?></td>
                <td class="num"><?= sanitize(format_price((float)$item['price'] * (int)$item['quantity'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value"><?= sanitize(format_price($itemsTotal)) ?></td>
        </tr>
        <?php if ($receiptTax > 0): ?>
        <tr>
            <td class="label">Tax</td>
            <td class="value"><?= sanitize(format_price($receiptTax)) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="grand">
            <td class="label">Order Total</td>
            <td class="value"><?= sanitize(format_price((float)$order['total'])) ?></td>
        </tr>
    </table>

    <div class="footer-note">
        Thank you for shopping with <?= sanitize(SITE_NAME) ?>. This receipt was generated on <?= sanitize(date('M d, Y h:i A')) ?>.
    </div>
</body>
</html>
    <?php
    return (string) ob_get_clean();
}

/**
 * Renders the receipt to raw PDF bytes. Returns null if Dompdf is missing or
 * rendering fails — callers should treat the PDF as optional rather than let
 * a receipt problem break the payment flow.
 */
function generate_order_receipt_pdf(array $order, array $items): ?string
{
    if (!file_exists(ROOT_PATH . '/vendor/autoload.php')) {
        error_log('[Receipt] vendor/autoload.php missing — cannot generate PDF.');
        return null;
    }
    require_once ROOT_PATH . '/vendor/autoload.php';

    if (!class_exists(\Dompdf\Dompdf::class)) {
        error_log('[Receipt] Dompdf is not installed — cannot generate PDF.');
        return null;
    }

    try {
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(build_order_receipt_html($order, $items));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    } catch (Throwable $e) {
        error_log('[Receipt] PDF generation failed for order #' . ($order['id'] ?? '?') . ': ' . $e->getMessage());
        return null;
    }
}
