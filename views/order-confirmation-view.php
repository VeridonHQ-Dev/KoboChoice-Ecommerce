<?php
/**
 * Kobo Choice - Order Confirmation View
 * Expects: $order (array, incl. $order['items'])
 */
$awaitingCardPayment = $order['status'] === 'pending' && $order['payment_method'] === 'paystack';
?>
<div class="order-confirmation-area pt-60 pb-60">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-12">
                <?php $isDetailView = !empty($orderDetailMode); ?>
                <?php if ($isDetailView): ?>
                <style>
                .order-receipt-link {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    color: #1f2430;
                    font-size: 14px;
                    font-weight: 600;
                    text-decoration: none;
                }
                .order-receipt-link-text {
                    position: relative;
                    padding-bottom: 3px;
                }
                .order-receipt-link-text::after {
                    content: '';
                    position: absolute;
                    left: 0;
                    bottom: 0;
                    width: 100%;
                    height: 2px;
                    background: #1f2430;
                    transform-origin: center;
                    transform: scaleX(1);
                }
                .order-receipt-link:hover {
                    color: #F48201;
                }
                .order-receipt-link:hover .order-receipt-link-text::after {
                    background: #F48201;
                    animation: receiptUnderlineToFro 0.45s ease-out;
                }
                @keyframes receiptUnderlineToFro {
                    0% { transform: scaleX(0); }
                    100% { transform: scaleX(1); }
                }
                </style>
                <div class="text-right pb-20">
                    <a href="<?php echo base_url('/order-receipt?id=' . (int)$order['id']); ?>" class="order-receipt-link">
                        <i class="fa fa-download"></i> <span class="order-receipt-link-text">Download Order Receipt</span>
                    </a>
                </div>
                <?php endif; ?>
                <div class="text-center pb-30">
                    <?php if ($isDetailView): ?>
                        <img src="<?php echo base_url('/images/menu/logo/login-logo.png'); ?>" alt="<?php echo sanitize(SITE_NAME); ?>" style="height:56px;width:auto;">
                    <?php else: ?>
                        <i class="fa fa-check-circle" style="font-size:56px;color:#5cb85c;"></i>
                    <?php endif; ?>
                    <?php if ($isDetailView): ?>
                        <h2 class="pt-20">Order #<?php echo (int)$order['id']; ?></h2>
                        <p>Placed on <strong><?php echo format_date($order['created_at']); ?></strong>.
                           A copy was sent to <strong><?php echo sanitize($order['email'] ?? ''); ?></strong>.</p>
                    <?php else: ?>
                        <h2 class="pt-20">Thank you for your order!</h2>
                        <p>Your order <strong>#<?php echo (int)$order['id']; ?></strong> has been placed successfully.
                           A confirmation has been recorded and we will contact you at
                           <strong><?php echo sanitize($order['email'] ?? ''); ?></strong>.</p>
                    <?php endif; ?>
                </div>

                <div class="your-order-table table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="cart-product-name">Product</th>
                                <th class="cart-product-quantity">Qty</th>
                                <th class="cart-product-total">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                            <tr class="cart_item">
                                <td class="cart-product-name">
                                    <a href="<?php echo base_url('/single-product?slug=' . urlencode($item['slug'] ?? '')); ?>" style="color:#1f2430;text-decoration:none;">
                                        <?php echo sanitize($item['name']); ?>
                                    </a>
                                </td>
                                <td class="cart-product-quantity">
                                    <strong class="product-quantity"><?php echo (int)$item['quantity']; ?></strong>
                                </td>
                                <td class="cart-product-total">
                                    <span class="amount"><?php echo format_price($item['price'] * $item['quantity']); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <?php if ((float)($order['tax'] ?? 0) > 0): ?>
                            <tr>
                                <th colspan="2">Tax</th>
                                <td><span class="amount"><?php echo format_price((float)$order['tax']); ?></span></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="order-total">
                                <th colspan="2">Order Total</th>
                                <td><strong><span class="amount"><?php echo format_price($order['total']); ?></span></strong></td>
                            </tr>
                            <tr>
                                <th colspan="2">Status</th>
                                <td>
                                    <span class="amount"><?php echo sanitize(ucfirst($order['status'])); ?></span>
                                    <?php if (!empty($order['payment_method'])): ?>
                                        <br><small style="color:#6b7280;">Payment: <?php echo sanitize(ucwords(str_replace('_', ' ', $order['payment_method']))); ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($order['cancel_reason'])): ?>
                                        <br><small style="color:#6b7280;font-style:italic;">Reason: <?php echo sanitize($order['cancel_reason']); ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <?php if ($awaitingCardPayment): ?>
                <div style="margin-top:20px;padding:16px 18px;border:1px solid #fde68a;background:#fffbeb;border-radius:6px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
                    <div>
                        <strong style="color:#92400e;"><i class="fa fa-exclamation-circle"></i> Payment not completed</strong>
                        <p style="margin:4px 0 0;font-size:13px;color:#78350f;">Your order is saved, but the card payment wasn't finished. Complete it now to avoid delays.</p>
                    </div>
                    <button type="button" id="retry-payment-btn" style="padding:10px 20px;border:none;border-radius:4px;background:#F48201;color:#fff;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap;">
                        <i class="fa fa-credit-card"></i> Complete Payment
                    </button>
                </div>
                <?php endif; ?>

                <?php if (!empty($orderDetailMode) && !in_array($order['status'], ['cancelled', 'delivered', 'completed'])): ?>
                <div style="margin-top:20px;text-align:right;">
                    <button type="button" onclick="openCancelModal()" style="padding:10px 22px;border:2px solid #dc2626;border-radius:4px;background:#fff;color:#dc2626;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.2s;">
                        <i class="fa fa-times-circle"></i> Cancel Order
                    </button>
                </div>
                <?php endif; ?>

                <div class="pt-20">
                    <h4>Shipping To</h4>
                    <p>
                        <?php echo sanitize($order['guest_name'] ?? ($_SESSION['customer_name'] ?? '')); ?><br>
                        <?php echo sanitize($order['shipping_address'] ?? ''); ?><br>
                        <?php echo sanitize(trim(($order['city'] ?? '') . ', ' . ($order['state'] ?? '') . ', ' . ($order['country'] ?? ''), ', ')); ?><br>
                        <?php echo sanitize($order['phone'] ?? ''); ?>
                    </p>
                </div>

                <div class="text-center pt-30">
                    <a href="<?php echo base_url('/shop'); ?>" class="li-button">Continue Shopping</a>
                    <?php if (is_customer_logged_in()): ?>
                    <a href="<?php echo base_url('/orders'); ?>" class="li-button li-button-dark">View My Orders</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<style>
.cancel-reason-label {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 4px;
    margin-bottom: 6px; cursor: pointer; font-size: 13px; color: #374151;
    transition: all 0.15s;
}
.cancel-reason-label:hover { border-color: #d1d5db; background: #f9fafb; }
.cancel-reason-label input[type="radio"] {
    width: 16px; height: 16px; margin: 0; padding: 0;
    accent-color: #F48201; flex-shrink: 0; cursor: pointer;
}
.cancel-reason-label:has(input:checked) { border-color: #F48201; background: #fff7ed; }
.cancel-reason-last { margin-bottom: 0; }
</style>
<div id="cancelModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:6px;max-width:440px;width:90%;padding:28px;position:relative;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <button onclick="closeCancelModal()" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:20px;color:#999;cursor:pointer;">&times;</button>
        <h3 style="margin:0 0 6px;font-size:18px;color:#1f2430;">Cancel Order #<?php echo (int)$order['id']; ?></h3>
        <p style="margin:0 0 18px;font-size:13px;color:#6b7280;">Please tell us why you want to cancel this order.</p>
        <form id="cancelForm">
            <input type="hidden" name="id" value="<?php echo (int)$order['id']; ?>">
            <div style="margin-bottom:14px;">
                <label class="cancel-reason-label">
                    <input type="radio" name="cancel_reason" value="Changed my mind"> Changed my mind
                </label>
                <label class="cancel-reason-label">
                    <input type="radio" name="cancel_reason" value="Found a better price elsewhere"> Found a better price elsewhere
                </label>
                <label class="cancel-reason-label">
                    <input type="radio" name="cancel_reason" value="Ordered by mistake"> Ordered by mistake
                </label>
                <label class="cancel-reason-label">
                    <input type="radio" name="cancel_reason" value="Delivery too slow"> Delivery too slow
                </label>
                <label class="cancel-reason-label">
                    <input type="radio" name="cancel_reason" value="No longer needed"> No longer needed
                </label>
                <label class="cancel-reason-label cancel-reason-last">
                    <input type="radio" name="cancel_reason" value="other"> Other
                </label>
            </div>
            <div id="cancelOtherWrap" style="display:none;margin-bottom:18px;">
                <textarea id="cancelOtherText" placeholder="Please specify your reason..." rows="3" style="width:100%;padding:10px 14px;border:1px solid #dcdfe4;border-radius:4px;font-size:14px;resize:vertical;box-sizing:border-box;"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeCancelModal()" style="padding:10px 20px;border:1px solid #dcdfe4;border-radius:4px;background:#fff;font-size:14px;cursor:pointer;color:#374151;">Keep Order</button>
                <button type="submit" id="cancelSubmitBtn" style="padding:10px 20px;border:none;border-radius:4px;background:#dc2626;color:#fff;font-size:14px;font-weight:600;cursor:pointer;">Cancel Order</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCancelModal() {
    document.getElementById('cancelModal').style.display = 'flex';
}
function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="cancel_reason"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.getElementById('cancelOtherWrap').style.display = this.value === 'other' ? 'block' : 'none';
        });
    });
    document.getElementById('cancelModal').addEventListener('click', function(e) {
        if (e.target === this) closeCancelModal();
    });
    document.getElementById('cancelForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var reason = document.querySelector('input[name="cancel_reason"]:checked');
        if (!reason) { showWarningModal('Please select a reason for cancellation.'); return; }
        var cancelReason = reason.value === 'other' ? document.getElementById('cancelOtherText').value.trim() : reason.value;
        if (reason.value === 'other' && !cancelReason) { showWarningModal('Please provide your reason.'); return; }
        if (!confirm('Are you sure you want to cancel this order?')) return;
        var btn = document.getElementById('cancelSubmitBtn');
        btn.disabled = true; btn.textContent = 'Cancelling...';
        var fd = new FormData(this);
        fd.set('cancel_reason', cancelReason);
        fetch('<?php echo API_URL; ?>?action=customer_cancel_order', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                btn.disabled = false; btn.textContent = 'Cancel Order';
                if (res.success) {
                    closeCancelModal();
                    showWarningModal(res.message);
                    location.reload();
                } else {
                    showWarningModal(res.message || 'Failed to cancel order.');
                }
            })
            .catch(function() { btn.disabled = false; btn.textContent = 'Cancel Order'; showWarningModal('An error occurred.'); });
    });
});
</script>

<?php if ($awaitingCardPayment): ?>
<script>
window.addEventListener('load', function() {
    var btn = document.getElementById('retry-payment-btn');
    if (!btn) { return; }
    // Card payment continues on its own page rather than a popup.
    btn.addEventListener('click', function() {
        window.location.href = '<?php echo base_url('/payment?id=' . (int) $order['id']); ?>';
    });
});
</script>
<?php endif; ?>
