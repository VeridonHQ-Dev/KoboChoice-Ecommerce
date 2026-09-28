<?php
$cart = get_cart();
$cartItems = [];
$subtotal = 0;

foreach ($cart as $item) {
    $product = $db->fetch(
        "SELECT id, name, slug, price, image FROM products WHERE id = ? AND status = 'active'",
        [$item['product_id']]
    );
    if ($product) {
        $product['quantity'] = $item['quantity'];
        $product['line_total'] = $product['price'] * $item['quantity'];
        $subtotal += $product['line_total'];
        $cartItems[] = $product;
    }
}

$discount = $_SESSION['coupon']['discount'] ?? 0;
// Mirrors the authoritative calculation in api/OrderAPI.php::create().
$tax = round($subtotal * TAX_RATE, 2);
$total = round(max(0, $subtotal - $discount) + $tax, 2);

$customer = get_current_customer();
$customerName = $customer ? sanitize($customer['shipping_name'] ?: $customer['name']) : '';
$customerEmail = $customer ? sanitize($customer['email']) : '';
$customerPhone = $customer ? sanitize($customer['shipping_phone'] ?: $customer['phone'] ?? '') : '';
$customerAddress = $customer ? sanitize($customer['shipping_address'] ?? '') : '';
$customerCity = $customer ? sanitize($customer['shipping_city'] ?? '') : '';
$customerState = $customer ? sanitize($customer['shipping_state'] ?? '') : '';
$customerCountry = $customer ? sanitize($customer['shipping_country'] ?? 'Nigeria') : 'Nigeria';
?>

<style>
.checkout-wrap { max-width: 1100px; margin: 0 auto; }
.checkout-section { margin-bottom: 30px; }
.checkout-section h3 { font-size: 22px; font-weight: 600; color: #1f2430; margin: 0 0 18px; padding-bottom: 12px; border-bottom: 2px solid #F48201; }
.checkout-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.checkout-form-grid .full-width { grid-column: 1 / -1; }
.checkout-form-grid label { display: block; font-size: 15px; font-weight: 400; color: #374151; margin-bottom: 6px; }
.checkout-form-grid input, .checkout-form-grid select, .checkout-form-grid textarea {
    width: 100%; padding: 11px 14px; border: 1px solid #d1d5db; border-radius: 4px;
    font-size: 16px; font-weight: 400; color: #1f2430; background: #fff; box-sizing: border-box;
    transition: border-color 0.2s;
}
.checkout-form-grid input:focus, .checkout-form-grid select:focus, .checkout-form-grid textarea:focus {
    border-color: #F48201; outline: none;
}
/* ── Order summary ────────────────────────────────────────────────────
   Same panel language as the cart and wishlist pages: square corners,
   #e2e5ea hairlines, navy table head. Weights stay at 400 everywhere
   except the section titles and the Grand Total. */
.order-summary-panel { border: 1px solid #e2e5ea; background: #fff; }
.order-summary-table { width: 100%; border-collapse: collapse; }
.order-summary-table th {
    text-align: left; padding: 13px 16px; background: #0D1B2A; color: #fff;
    font-size: 12px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase;
    border: 0; white-space: nowrap;
}
.order-summary-table td {
    padding: 15px 16px; border-top: 1px solid #eef1f5;
    font-size: 16px; font-weight: 400; color: #1f2430; vertical-align: middle;
}
.order-summary-table tbody tr:first-child td { border-top: 0; }
.order-summary-table .qty { text-align: center; white-space: nowrap; }
.order-summary-table .num { text-align: right; white-space: nowrap; }
.summary-product { display: flex; align-items: center; gap: 13px; }
.order-summary-table .summary-img {
    flex-shrink: 0; width: 58px; height: 58px; object-fit: contain;
    border-radius: 0; border: 1px solid #e2e5ea; background: #f7f8fa;
}
.summary-name { font-size: 15px; line-height: 1.4; color: #1f2430; }
.summary-unit { margin-top: 3px; font-size: 13px; color: #9ca3af; }

/* The totals live inside the same panel as the rows they add up. */
.order-summary-totals {
    margin: 0; padding: 16px 18px; background: #fafbfc;
    border-top: 1px solid #e2e5ea; text-align: right;
}
.order-summary-totals .row-line {
    display: flex; justify-content: space-between; gap: 16px; padding: 7px 0;
    font-size: 16px; font-weight: 400; color: #4b5563;
    max-width: 380px; margin-left: auto;
}
.order-summary-totals .row-line span:last-child { color: #1f2430; }
.order-summary-totals .row-line.discount span:last-child { color: #178201; }
.order-summary-totals .row-line.total {
    margin-top: 6px; padding-top: 13px; border-top: 1px solid #e2e5ea;
    font-size: 17px; font-weight: 600; color: #1f2430;
}
.order-summary-totals .row-line.total span:last-child { font-size: 22px; font-weight: 700; }

/* Coupon — matches the cart page's joined field + button. */
.checkout-coupon { display: flex; max-width: 440px; }
.checkout-coupon input {
    flex: 1; min-width: 0; height: 44px; padding: 0 14px; border-radius: 0;
    border: 1px solid #d9dde4; border-right: 0;
    font-size: 15px; font-weight: 400; color: #1f2430; background: #fff;
}
.checkout-coupon input:focus { outline: none; border-color: #F48201; }
.checkout-coupon button {
    height: 44px; padding: 0 24px; border: 0; border-radius: 0;
    background: #000a2e; color: #fff; font-size: 12.5px; font-weight: 400;
    letter-spacing: .4px; text-transform: uppercase; cursor: pointer;
    transition: background .2s ease;
}
.checkout-coupon button:hover { background: #F48201; }
.checkout-coupon-msg { margin-top: 10px; font-size: 14px; line-height: 1.5; }
.checkout-coupon-msg .ok { color: #178201; }
.checkout-coupon-msg .bad { color: #c0392b; }

/* ── Payment method accordion ─────────────────────────────────────── */
.payment-accordion { border: 1px solid #e2e5ea; border-radius: 0; overflow: hidden; background: #fff; }
.payment-option { border-bottom: 1px solid #e2e5ea; }
.payment-option:last-child { border-bottom: none; }
.payment-option-header {
    display: flex; align-items: center; gap: 13px; padding: 17px 18px;
    cursor: pointer; background: #fff; border-left: 3px solid transparent;
    transition: background .2s ease, border-color .2s ease;
}
.payment-option-header:hover { background: #fbfcfd; }
/* The chosen method gets an orange rail and a warm tint, so the selection
   reads at a glance rather than resting on the small native radio alone. */
.payment-option.is-selected .payment-option-header { background: #fff8f1; border-left-color: #F48201; }
.payment-option-header input[type="radio"] { accent-color: #F48201; width: 17px; height: 17px; margin: 0; flex-shrink: 0; }
.payment-option-header .pay-label {
    font-size: 16px; font-weight: 400; color: #1f2430; flex: 1;
    display: flex; align-items: center; gap: 7px;
}
.payment-option-header .pay-desc { font-size: 13.5px; font-weight: 400; color: #6b7280; text-align: right; }
.payment-option-body {
    display: none; padding: 18px; background: #fbfcfd;
    border-top: 1px solid #eef1f5; border-left: 3px solid #F48201;
    font-size: 15px; font-weight: 400; color: #4b5563; line-height: 1.65;
}
.payment-option-body.active { display: block; }
.payment-option-body p { margin: 0 0 10px; }
.payment-option-body p:last-child { margin-bottom: 0; }
.payment-option-body .pay-body-heading { font-size: 15px; font-weight: 400; color: #1f2430; }
.payment-option-body strong { font-weight: 400; color: #1f2430; }
/* Explanatory line with its leading icon — keeps the icon from being pulled
   into the text flow when the sentence wraps to a second line. */
.pay-note { display: flex; align-items: flex-start; gap: 9px; }
.pay-note > i { color: #F48201; font-size: 15px; line-height: 1.65; flex-shrink: 0; }

/* Bank details as label/value rows rather than a run of paragraphs, so the
   account number can be read off cleanly while typing it into a banking app. */
.bank-details-box { background: #fff; border: 1px solid #e2e5ea; border-radius: 0; padding: 0; margin-bottom: 16px; }
.bank-details-box .bank-row {
    display: flex; justify-content: space-between; align-items: baseline; gap: 16px;
    padding: 12px 16px; border-top: 1px solid #eef1f5; font-size: 15px;
}
.bank-details-box .bank-row:first-child { border-top: 0; }
.bank-details-box .bank-key { color: #6b7280; font-weight: 400; }
.bank-details-box .bank-val { color: #1f2430; font-weight: 400; text-align: right; }
.bank-details-box .bank-row.acct .bank-val {
    font-family: 'Courier New', Courier, monospace;
    font-size: 18px; letter-spacing: 1.5px; color: #0D1B2A;
}

/* ── Evidence-of-payment upload ── */
.receipt-upload-area {
    border: 2px dashed #d9dde4; border-radius: 0; padding: 26px 20px;
    text-align: center; cursor: pointer; background: #fff;
    transition: border-color .2s ease, background .2s ease;
}
.receipt-upload-area:hover,
.receipt-upload-area.is-dragging { border-color: #F48201; background: #fff8f1; }
.receipt-upload-area input[type="file"] { display: none; }
.receipt-upload-area p { margin: 0; font-size: 15px; font-weight: 400; color: #4b5563; }
.receipt-upload-area .upload-hint { margin-top: 5px; font-size: 13px; color: #9ca3af; }
.receipt-upload-area .upload-icon { font-size: 30px; color: #c3c8d0; margin-bottom: 8px; }
.receipt-file-name {
    margin-top: 10px; padding: 10px 14px; display: none;
    background: #e7f6ec; border-left: 3px solid #178201;
    font-size: 14px; font-weight: 400; color: #178201;
}
/* Place-order button reuses the site's .li-button look (same as the
   "View My Orders" button). A <button> needs the browser's default border
   removed to render identically to the <a> version. */
button.li-button { border: none; cursor: pointer; }
button.li-button:disabled,
button.li-button:disabled:hover { background: #9ca3af; color: #fff; cursor: not-allowed; }
@media (max-width: 767px) { .checkout-form-grid { grid-template-columns: 1fr; } }

/* ── Multi-step checkout ── */
.checkout-step { display: none; }
.checkout-step.active { display: block; }
.checkout-steps-nav { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 34px; flex-wrap: wrap; }
.checkout-steps-nav .step-pill { display: flex; align-items: center; gap: 9px; font-size: 15px; font-weight: 400; color: #9ca3af; }
.checkout-steps-nav .step-pill .step-num {
    width: 30px; height: 30px; border-radius: 50%; background: #e5e7eb; color: #6b7280;
    display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 600;
    transition: background 0.25s, color 0.25s;
}
.checkout-steps-nav .step-pill.active { color: #1f2430; }
.checkout-steps-nav .step-pill.active .step-num { background: #F48201; color: #fff; }
.checkout-steps-nav .step-pill.done .step-num { background: #16a34a; color: #fff; }
.checkout-steps-nav .step-sep { width: 34px; height: 2px; background: #e5e7eb; }
.checkout-step-actions { display: flex; justify-content: space-between; gap: 14px; margin-top: 26px; }
.checkout-step-actions.only-next { justify-content: flex-end; }
.step-btn {
    padding: 13px 34px; border-radius: 4px; font-size: 16px; font-weight: 500;
    cursor: pointer; transition: background 0.2s, color 0.2s; border: 1px solid transparent;
}
.step-btn-next { background: #F48201; color: #fff; border-color: #F48201; }
.step-btn-next:hover { background: #e0740e; border-color: #e0740e; }
.step-btn-back { background: #fff; color: #374151; border-color: #d1d5db; }
.step-btn-back:hover { background: #f9fafb; border-color: #9ca3af; }
/* Order Summary "Continue" — same look as the cart page's
   "Proceed to checkout" button (.cart-page-total > a in style.css).
   margin-top is omitted; .checkout-step-actions already spaces the row. */
.checkout-proceed-btn {
    background-color: #333;
    border: 1px solid #333;
    color: #fff;
    display: inline-block;
    padding: 10px 30px;
    font-size: 14px;
    text-transform: capitalize;
    border-radius: 0;
    cursor: pointer;
    -webkit-transition: all 0.3s ease 0s;
    transition: all 0.3s ease 0s;
}
.checkout-proceed-btn:hover {
    background: #F48201;
    border-color: #F48201;
    color: #fff;
}
@media (max-width: 575px) {
    .checkout-steps-nav .step-pill .step-label { display: none; }
    .checkout-steps-nav .step-sep { width: 22px; }
    .step-btn { padding: 12px 22px; font-size: 15px; }

    /* The description can't share a line with the label on a phone, so it
       drops underneath, indented past the radio. */
    .payment-option-header { flex-wrap: wrap; }
    .payment-option-header .pay-desc {
        flex: 1 0 100%; text-align: left; padding-left: 30px; margin-top: 3px;
    }
    /* Label above value, so long bank names don't get squeezed into a column. */
    .bank-details-box .bank-row { flex-direction: column; align-items: flex-start; gap: 3px; }
    .bank-details-box .bank-val { text-align: left; }
    .order-summary-totals .row-line { max-width: none; }
    .order-summary-table td { padding: 13px 12px; font-size: 15px; }
    .order-summary-table .summary-img { width: 50px; height: 50px; }
}

/* ── Bank transfer confirmation modal ── */
.bt-modal-overlay {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.55); z-index: 99999; align-items: center; justify-content: center;
}
.bt-modal-box {
    background: #fff; border-radius: 8px; max-width: 440px; width: 90%; padding: 32px 28px;
    text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: btPopIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes btPopIn { from { opacity: 0; transform: scale(0.9) translateY(10px); } to { opacity: 1; transform: none; } }
.bt-check { width: 78px; height: 78px; display: block; margin: 0 auto 18px; }
.bt-check-circle {
    stroke: #16a34a; stroke-width: 2; fill: none;
    stroke-dasharray: 166; stroke-dashoffset: 166;
    animation: btStroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}
.bt-check-mark {
    stroke: #16a34a; stroke-width: 4; fill: none;
    stroke-linecap: round; stroke-linejoin: round;
    stroke-dasharray: 48; stroke-dashoffset: 48;
    animation: btStroke 0.32s cubic-bezier(0.65, 0, 0.45, 1) 0.55s forwards;
}
@keyframes btStroke { to { stroke-dashoffset: 0; } }
.bt-modal-box h3 { margin: 0 0 10px; font-size: 19px; font-weight: 600; color: #1f2430; }
.bt-modal-box p { margin: 0 0 24px; font-size: 14px; color: #4b5563; line-height: 1.6; }
.bt-modal-box .bt-ok-btn {
    padding: 11px 40px; border: none; border-radius: 4px; background: #16a34a; color: #fff;
    font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s;
}
.bt-modal-box .bt-ok-btn:hover { background: #15803d; }
</style>

<?php if (empty($cartItems)): ?>
<div class="checkout-area pt-60 pb-60">
    <div class="container text-center">
        <h3>Your cart is empty.</h3>
        <p class="mt-20"><a href="<?= base_url('/shop') ?>" class="li-button">Continue Shopping</a></p>
    </div>
</div>
<?php else: ?>
<div class="checkout-area pt-60 pb-60">
    <div class="container">
        <div class="checkout-wrap">

            <!-- STEP INDICATOR -->
            <div class="checkout-steps-nav">
                <div class="step-pill active" data-step-pill="1">
                    <span class="step-num">1</span><span class="step-label">Order Summary</span>
                </div>
                <span class="step-sep"></span>
                <div class="step-pill" data-step-pill="2">
                    <span class="step-num">2</span><span class="step-label">Billing &amp; Shipping</span>
                </div>
                <span class="step-sep"></span>
                <div class="step-pill" data-step-pill="3">
                    <span class="step-num">3</span><span class="step-label">Payment</span>
                </div>
            </div>

            <!-- ══ STEP 1: ORDER SUMMARY ══ -->
            <div class="checkout-step active" data-step="1">
            <div class="checkout-section">
                <h3><i class="fa fa-shopping-cart"></i> Order Summary</h3>
                <div class="order-summary-panel">
                    <table class="order-summary-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="qty">Qty</th>
                                <th class="num">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                            <?php
                                $summaryImg = !empty($item['image'])
                                    ? PRODUCT_IMG_URL . '/' . rawurlencode($item['image'])
                                    : PRODUCT_IMG_URL . '/placeholder.jpg';
                            ?>
                            <tr>
                                <td>
                                    <div class="summary-product">
                                        <img class="summary-img" src="<?= $summaryImg ?>" alt="<?= sanitize($item['name']) ?>">
                                        <div>
                                            <div class="summary-name"><?= sanitize($item['name']) ?></div>
                                            <div class="summary-unit"><?= format_price($item['price']) ?> each</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="qty"><?= (int)$item['quantity'] ?></td>
                                <td class="num"><?= format_price($item['line_total']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="order-summary-totals">
                        <div class="row-line"><span>Subtotal</span><span><?= format_price($subtotal) ?></span></div>
                        <div class="row-line"><span>Tax (<?= rtrim(rtrim(number_format(TAX_RATE * 100, 2), '0'), '.') ?>%)</span><span><?= format_price($tax) ?></span></div>
                        <div class="row-line"><span>Shipping Cost</span><span><?= format_price(0) ?></span></div>
                        <?php if ($discount > 0): ?>
                        <div class="row-line discount"><span>Coupon Discount</span><span>-<?= format_price($discount) ?></span></div>
                        <?php endif; ?>
                        <div class="row-line total"><span>Grand Total</span><span><?= format_price($total) ?></span></div>
                    </div>
                </div>
            </div>

            <!-- COUPON (belongs with the summary — it changes these totals) -->
            <div class="checkout-section">
                <h3><i class="fa fa-tag"></i> Have a Coupon?</h3>
                <div class="checkout-coupon">
                    <input type="text" id="checkout-coupon" placeholder="Enter coupon code" autocomplete="off">
                    <button type="button" id="checkout-coupon-btn">Apply</button>
                </div>
                <div class="checkout-coupon-msg" id="checkout-coupon-msg"></div>
            </div>

            <div class="checkout-step-actions only-next">
                <button type="button" class="checkout-proceed-btn" data-goto-step="2">
                    Continue <i class="fa fa-angle-right"></i>
                </button>
            </div>
            </div><!-- /step 1 -->

            <!-- ══ STEP 2: BILLING & SHIPPING DETAILS ══ -->
            <div class="checkout-step" data-step="2">
            <div class="checkout-section">
                <h3><i class="fa fa-map-marker"></i> Billing &amp; Shipping Details</h3>
                <form id="checkout-form">
                    <div class="checkout-form-grid">
                        <div>
                            <label>Country <span style="color:#dc2626;">*</span></label>
                            <select name="country" required>
                                <?php foreach (['Nigeria','Ghana','Kenya','South Africa'] as $c): ?>
                                <option value="<?= $c ?>" <?= $customerCountry === $c ? 'selected' : '' ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Full Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="name" required value="<?= $customerName ?>">
                        </div>
                        <div>
                            <label>Email Address <span style="color:#dc2626;">*</span></label>
                            <input type="email" name="email" required value="<?= $customerEmail ?>">
                        </div>
                        <div>
                            <label>Phone Number <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="phone" required value="<?= $customerPhone ?>">
                        </div>
                        <div class="full-width">
                            <label>Street Address <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="address" required value="<?= $customerAddress ?>" placeholder="House number and street name">
                        </div>
                        <div>
                            <label>City <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="city" required value="<?= $customerCity ?>">
                        </div>
                        <div>
                            <label>State <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="state" required value="<?= $customerState ?>">
                        </div>
                        <div class="full-width">
                            <label>Order Notes (optional)</label>
                            <textarea name="note" rows="3" placeholder="Notes about your order, e.g. special delivery instructions."></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <div class="checkout-step-actions">
                <button type="button" class="step-btn step-btn-back" data-goto-step="1">
                    <i class="fa fa-angle-left"></i> Back
                </button>
                <button type="button" class="checkout-proceed-btn" id="step2-continue-btn">
                    Continue <i class="fa fa-angle-right"></i>
                </button>
            </div>
            </div><!-- /step 2 -->

            <!-- ══ STEP 3: PAYMENT METHOD ══ -->
            <div class="checkout-step" data-step="3">
            <div class="checkout-section">
                <h3><i class="fa fa-credit-card"></i> Payment Method</h3>
                <div class="payment-accordion">

                    <?php /* ── Pay on Delivery — disabled for now, kept for future use.
                             Re-enable by removing this PHP comment wrapper.

                    <div class="payment-option">
                        <div class="payment-option-header" data-method="pay_on_delivery">
                            <input type="radio" name="payment_method" value="pay_on_delivery">
                            <span class="pay-label">Pay on Delivery</span>
                            <span class="pay-desc">Pay when your order arrives</span>
                        </div>
                        <div class="payment-option-body" id="pay-body-pay_on_delivery">
                            <p><i class="fa fa-info-circle" style="color:#F48201;"></i> You will pay cash to the delivery agent when your order is delivered to your address. Please ensure you have the exact amount ready.</p>
                        </div>
                    </div>

                    ── end Pay on Delivery ── */ ?>

                    <!-- Direct Bank Transfer -->
                    <div class="payment-option">
                        <div class="payment-option-header" data-method="bank_transfer">
                            <input type="radio" name="payment_method" value="bank_transfer">
                            <span class="pay-label">Direct Bank Transfer</span>
                            <span class="pay-desc">Make payment directly to our bank account</span>
                        </div>
                        <div class="payment-option-body" id="pay-body-bank_transfer">
                            <p class="pay-body-heading">Please make payment to the following bank account:</p>
                            <div class="bank-details-box">
                                <div class="bank-row">
                                    <span class="bank-key">Account Name</span>
                                    <span class="bank-val">Cmion Femstar</span>
                                </div>
                                <div class="bank-row acct">
                                    <span class="bank-key">Account Number</span>
                                    <span class="bank-val">8035167873</span>
                                </div>
                                <div class="bank-row">
                                    <span class="bank-key">Bank</span>
                                    <span class="bank-val">Opay Ng Limited</span>
                                </div>
                            </div>
                            <p class="pay-body-heading">Upload Evidence of Payment</p>
                            <div class="receipt-upload-area" id="receipt-drop-area">
                                <?php /* FA4 name — the theme ships Font Awesome 4, where the
                                         FA5 "fa-cloud-upload-alt" renders nothing at all. */ ?>
                                <div class="upload-icon"><i class="fa fa-cloud-upload"></i></div>
                                <p>Click to upload or drag and drop</p>
                                <p class="upload-hint">JPG, PNG, GIF, WebP or PDF (Max 5MB)</p>
                                <input type="file" id="receipt-file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                            </div>
                            <div class="receipt-file-name" id="receipt-file-name"></div>
                        </div>
                    </div>

                    <!-- Paystack (Card) -->
                    <div class="payment-option">
                        <div class="payment-option-header" data-method="paystack">
                            <input type="radio" name="payment_method" value="paystack">
                            <span class="pay-label"><i class="fa fa-credit-card" style="font-size:16px;margin-right:4px;"></i> Pay with Card</span>
                            <span class="pay-desc">Mastercard, Visa &amp; Verve via Paystack</span>
                        </div>
                        <div class="payment-option-body" id="pay-body-paystack">
                            <p class="pay-note">
                                <i class="fa fa-lock"></i>
                                <span>Pay now with your card through our secure payment gateway. Kobo Choice will never store your card details.</span>
                            </p>
                        </div>
                    </div>

                    <!-- Cheque Payment -->
                    <div class="payment-option">
                        <div class="payment-option-header" data-method="cheque">
                            <input type="radio" name="payment_method" value="cheque">
                            <span class="pay-label">Cheque Payment</span>
                            <span class="pay-desc">Pay via bank cheque</span>
                        </div>
                        <div class="payment-option-body" id="pay-body-cheque">
                            <p class="pay-note">
                                <i class="fa fa-info-circle"></i>
                                <span>Please make your cheque payable to <strong>Kobo Choice</strong>. Your order will be processed once the cheque has been received and verified (usually 2-3 business days).</span>
                            </p>
                        </div>
                    </div>

                    <?php /* ── Apple Pay — disabled for now, kept for future use.
                             Re-enable by removing this PHP comment wrapper.

                    <div class="payment-option">
                        <div class="payment-option-header" data-method="apple_pay">
                            <input type="radio" name="payment_method" value="apple_pay">
                            <span class="pay-label"><i class="fa fa-apple" style="font-size:18px;margin-right:4px;"></i> Apple Pay</span>
                            <span class="pay-desc">Fast and secure payment</span>
                        </div>
                        <div class="payment-option-body" id="pay-body-apple_pay">
                            <p><i class="fa fa-info-circle" style="color:#F48201;"></i> Apple Pay is currently in test mode. No real charges will be made. Click "Continue Payment" to simulate a successful Apple Pay transaction.</p>
                            <div id="apple-pay-container" style="margin-top:12px;"></div>
                        </div>
                    </div>

                    ── end Apple Pay ── */ ?>

                </div>
            </div>

            <!-- PLACE ORDER — sits in the same action row as Back, matching steps 1 & 2 -->
            <div class="checkout-step-actions">
                <button type="button" class="step-btn step-btn-back" data-goto-step="2">
                    <i class="fa fa-angle-left"></i> Back
                </button>
                <button type="button" class="li-button li-button-dark" id="place-order-btn">
                    <i class="fa fa-lock"></i> Continue Payment
                </button>
            </div>
            </div><!-- /step 3 -->

        </div>
    </div>
</div>

<!-- Bank Transfer — order placed / awaiting admin payment approval -->
<div class="bt-modal-overlay" id="bankTransferModal">
    <div class="bt-modal-box">
        <svg class="bt-check" viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg">
            <circle class="bt-check-circle" cx="26" cy="26" r="24"/>
            <path class="bt-check-mark" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
        </svg>
        <h3>Order Placed Successfully</h3>
        <p id="bankTransferModalMsg"></p>
        <button type="button" class="bt-ok-btn" id="bankTransferOkBtn">OK</button>
    </div>
</div>

<script>
window.addEventListener('load', function() {
    if (typeof jQuery === 'undefined') { return; }
    (function($) {
    var API = '<?= API_URL ?>';

    // ── Multi-step navigation ─────────────────────────────────
    function goToStep(step) {
        $('.checkout-step').removeClass('active');
        $('.checkout-step[data-step="' + step + '"]').addClass('active');

        $('.step-pill').each(function() {
            var n = parseInt($(this).data('step-pill'), 10);
            $(this).toggleClass('active', n === step).toggleClass('done', n < step);
        });

        // Bring the top of the checkout into view on every step change.
        var $anchor = $('.checkout-steps-nav');
        if ($anchor.length) {
            $('html, body').animate({ scrollTop: $anchor.offset().top - 90 }, 300);
        }
    }

    // Validates the billing form; highlights anything missing.
    function billingDetailsValid() {
        var valid = true;
        $('#checkout-form').find('[required]').each(function() {
            if (!$(this).val().trim()) { valid = false; $(this).css('border-color', '#dc2626'); }
            else { $(this).css('border-color', '#d1d5db'); }
        });
        return valid;
    }

    $(document).on('click', '[data-goto-step]', function() {
        goToStep(parseInt($(this).data('goto-step'), 10));
    });

    $('#step2-continue-btn').on('click', function() {
        if (!billingDetailsValid()) {
            showWarningModal('Please fill in all required fields before continuing.');
            return;
        }
        goToStep(3);
    });

    // ── Payment accordion toggle ──────────────────────────────
    $(document).on('click', '.payment-option-header', function() {
        var $header = $(this);
        var method = $header.data('method');
        // Check the radio
        $header.find('input[type="radio"]').prop('checked', true);
        // Close all bodies, open this one
        $('.payment-option-body').removeClass('active');
        $('#pay-body-' + method).addClass('active');
        // Drives the orange rail + tint on the chosen option.
        $('.payment-option').removeClass('is-selected');
        $header.closest('.payment-option').addClass('is-selected');
    });

    // ── Receipt file upload ───────────────────────────────────
    var receiptFile = null;
    var $dropArea = $('#receipt-drop-area');
    var $fileInput = $('#receipt-file');
    var $fileName = $('#receipt-file-name');

    $dropArea.on('click', function() { $fileInput.trigger('click'); });
    // A class rather than inline colours, so the hover and drag states can't
    // fall out of step with the stylesheet.
    $dropArea.on('dragover', function(e) { e.preventDefault(); $dropArea.addClass('is-dragging'); });
    $dropArea.on('dragleave', function() { $dropArea.removeClass('is-dragging'); });
    $dropArea.on('drop', function(e) {
        e.preventDefault(); $dropArea.removeClass('is-dragging');
        if (e.originalEvent.dataTransfer.files.length) {
            $fileInput[0].files = e.originalEvent.dataTransfer.files;
            showFileName(e.originalEvent.dataTransfer.files[0]);
        }
    });
    $fileInput.on('change', function() {
        if (this.files.length) showFileName(this.files[0]);
    });
    function showFileName(file) {
        receiptFile = file;
        $fileName.html('<i class="fa fa-check-circle"></i> ' + $('<div>').text(file.name).html()).show();
    }

    // ── Coupon apply ──────────────────────────────────────────
    $('#checkout-coupon-btn').on('click', function() {
        var code = $('#checkout-coupon').val().trim();
        if (!code) return;
        var $msg = $('#checkout-coupon-msg');
        $.post(API + '?action=coupon_apply', {code: code}, function(res) {
            if (res.success) { $msg.html('<span class="ok">' + res.message + '</span>'); location.reload(); }
            else { $msg.html('<span class="bad">' + res.message + '</span>'); }
        }, 'json');
    });

    // ── Place order ───────────────────────────────────────────
    $('#place-order-btn').on('click', function() {
        var $btn = $(this);
        var $form = $('#checkout-form');
        var paymentMethod = $('input[name="payment_method"]:checked').val();

        // Re-check the billing step in case it was edited after moving on.
        if (!billingDetailsValid()) {
            goToStep(2);
            showWarningModal('Please fill in all required fields before proceeding to payment.');
            return;
        }
        if (!paymentMethod) { showWarningModal('Please select a payment method before placing your order.'); return; }

        // Build form data
        var fd = new FormData($form[0]);
        fd.append('payment_method', paymentMethod);

        // If bank transfer, check receipt
        if (paymentMethod === 'bank_transfer') {
            if (receiptFile) {
                fd.append('receipt', receiptFile);
            }
        }

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

        function resetBtn() {
            $btn.prop('disabled', false).html('<i class="fa fa-lock"></i> Continue Payment');
        }

        function goToConfirmation(orderId) {
            window.location.href = '<?= base_url('/order-confirmation?id=') ?>' + orderId;
        }

        // Bank transfer needs the customer to acknowledge that the payment
        // still has to be approved by an admin before the order moves on.
        function showBankTransferModal(orderId, email) {
            $('#bankTransferModalMsg').text(
                'Your order #' + orderId + ' has been placed successfully. A confirmation has been recorded '
                + 'and we will contact you at ' + email + ' for successful confirmation of payment.'
            );
            $('#bankTransferModal').css('display', 'flex');
            $('#bankTransferOkBtn').off('click').on('click', function() {
                goToConfirmation(orderId);
            });
        }

        // Card payments continue on a dedicated page (no popup), which then
        // hands off to Paystack's secure hosted checkout.
        function startPaystackPayment(orderId) {
            window.location.href = '<?= base_url('/payment?id=') ?>' + orderId;
        }

        $.ajax({
            url: API + '?action=order_create',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    var orderId = res.order_id;

                    if (paymentMethod === 'paystack') {
                        startPaystackPayment(orderId);
                        return;
                    }

                    if (paymentMethod === 'bank_transfer') {
                        var custEmail = $form.find('[name="email"]').val().trim();

                        // Upload the receipt first (if one was attached), then
                        // ask the customer to acknowledge admin approval.
                        if (receiptFile) {
                            var rfd = new FormData();
                            rfd.append('order_id', orderId);
                            rfd.append('receipt', receiptFile);
                            $.ajax({
                                url: API + '?action=upload_receipt',
                                type: 'POST',
                                data: rfd,
                                processData: false,
                                contentType: false,
                                dataType: 'json',
                                complete: function() {
                                    showBankTransferModal(orderId, custEmail);
                                }
                            });
                        } else {
                            showBankTransferModal(orderId, custEmail);
                        }
                        return;
                    }

                    goToConfirmation(orderId);
                } else {
                    resetBtn();
                    showWarningModal(res.message || 'Failed to place order. Please try again.');
                }
            },
            error: function() {
                resetBtn();
                showWarningModal('An error occurred. Please try again.');
            }
        });
    });

    })(jQuery);
});
</script>
<?php endif; ?>
