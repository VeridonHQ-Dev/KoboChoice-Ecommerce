<?php
/**
 * Kobo Choice - Card Payment View
 * Expects: $order
 */
$payItems = $db->fetchAll(
    "SELECT name, price, quantity FROM order_items WHERE order_id = ?",
    [(int)$order['id']]
);
$payTax = (float)($order['tax'] ?? 0);
$paySubtotal = 0;
foreach ($payItems as $payItem) {
    $paySubtotal += (float)$payItem['price'] * (int)$payItem['quantity'];
}
?>

<style>
.pay-page { padding: 60px 0 80px; }
.pay-card {
    max-width: 520px;
    margin: 0 auto;
    background: #fff;
    border: 1px solid #ececf1;
    border-radius: 10px;
    padding: 38px 36px;
    /* mild, faintly black shadow */
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04), 0 12px 32px rgba(0, 0, 0, 0.08);
}
.pay-card-head { text-align: center; margin-bottom: 26px; }
.pay-card-head .pay-logo { margin: 0 auto 18px; }
.pay-card-head .pay-logo img { max-height: 52px; width: auto; }
.pay-card-head h2 { font-size: 23px; font-weight: 600; color: #1f2430; margin: 0 0 8px; }
.pay-card-head p { font-size: 15px; font-weight: 400; color: #6b7280; margin: 0; line-height: 1.6; }
.pay-amount-box {
    background: #f9fafb; border: 1px solid #eef0f3; border-radius: 8px;
    padding: 20px; margin-bottom: 24px;
}
.pay-line {
    display: flex; justify-content: space-between; align-items: baseline;
    padding: 7px 0; font-size: 15px; font-weight: 400; color: #4b5563;
}
.pay-line.grand {
    border-top: 1px solid #e5e7eb; margin-top: 8px; padding-top: 14px;
    font-size: 20px; font-weight: 700; color: #1f2430;
}
.pay-meta { font-size: 14px; font-weight: 400; color: #6b7280; text-align: center; margin-bottom: 24px; line-height: 1.7; }
.pay-meta strong { font-weight: 400; color: #1f2430; }
/* Same look as the checkout's "Continue Payment" button (.li-button + .li-button-dark).
   A <button> needs the browser's default border removed to match the <a> version. */
button.li-button { border: none; cursor: pointer; }
button.li-button:disabled,
button.li-button:disabled:hover { background: #9ca3af; color: #fff; cursor: not-allowed; }
.pay-btn-row { text-align: center; }
.pay-secure-note {
    margin-top: 18px; text-align: center; font-size: 13px; font-weight: 400;
    color: #9ca3af; line-height: 1.6;
}
.pay-back-link {
    display: block; text-align: center; margin-top: 20px;
    font-size: 15px; font-weight: 400; color: #6b7280; text-decoration: none;
}
.pay-back-link:hover { color: #F48201; }
@media (max-width: 575px) { .pay-card { padding: 28px 22px; } }
</style>

<div class="pay-page">
    <div class="container">
        <div class="pay-card">
            <div class="pay-card-head">
                <div class="pay-logo">
                    <img src="<?= BASE_URL ?>/images/menu/logo/2black.png" alt="<?= sanitize(SITE_NAME) ?>">
                </div>
                <h2>Complete Your Payment</h2>
                <p>Order #<?= (int)$order['id'] ?> &middot; Pay securely with your Mastercard, Visa or Verve card.</p>
            </div>

            <div class="pay-amount-box">
                <div class="pay-line">
                    <span>Subtotal</span><span><?= format_price($paySubtotal) ?></span>
                </div>
                <?php if ($payTax > 0): ?>
                <div class="pay-line">
                    <span>Tax</span><span><?= format_price($payTax) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($paySubtotal + $payTax > (float)$order['total']): ?>
                <div class="pay-line" style="color:#dc2626;">
                    <span>Discount</span><span>-<?= format_price($paySubtotal + $payTax - (float)$order['total']) ?></span>
                </div>
                <?php endif; ?>
                <div class="pay-line grand">
                    <span>Amount Due</span><span><?= format_price((float)$order['total']) ?></span>
                </div>
            </div>

            <div class="pay-meta">
                Receipt will be sent to <strong><?= sanitize($order['email'] ?? '') ?></strong>
            </div>

            <div class="pay-btn-row">
                <button type="button" class="li-button li-button-dark" id="pay-now-btn"
                        data-order-id="<?= (int)$order['id'] ?>">
                    <i class="fa fa-lock"></i> Pay <?= format_price((float)$order['total']) ?>
                </button>
            </div>

            <div class="pay-secure-note">
                <i class="fa fa-lock"></i> You'll continue to Paystack's secure page to enter your card details.
                Kobo Choice never sees or stores your card number.
            </div>

            <a class="pay-back-link" href="<?= base_url('/order-confirmation?id=' . (int)$order['id']) ?>">
                Pay later &mdash; view order instead
            </a>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function() {
    if (typeof jQuery === 'undefined') { return; }
    (function($) {
        var API = '<?= API_URL ?>';
        var $btn = $('#pay-now-btn');
        var originalHtml = $btn.html();

        $btn.on('click', function() {
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Redirecting to Paystack...');

            $.post(API + '?action=paystack_init', { order_id: $btn.data('order-id') }, function(res) {
                if (res.success && res.authorization_url) {
                    // Full-page handover to Paystack's hosted checkout.
                    window.location.href = res.authorization_url;
                } else {
                    $btn.prop('disabled', false).html(originalHtml);
                    showWarningModal(res.message || 'Could not start the payment. Please try again.');
                }
            }, 'json').fail(function() {
                $btn.prop('disabled', false).html(originalHtml);
                showWarningModal('An error occurred. Please try again.');
            });
        });
    })(jQuery);
});
</script>
