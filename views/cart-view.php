<?php
/**
 * Kobo Choice - Shopping Cart View
 *
 * Two columns on desktop (items left, order summary right); below 992px the
 * summary drops under the items, and below 768px each table row collapses
 * into a card. Same visual language as the wishlist page.
 */
$cart = get_cart();
$subtotal = 0;
$itemCount = 0;
$cartItems = [];

foreach ($cart as $item) {
    $product = $db->fetch(
        "SELECT id, name, slug, price, image, stock FROM products WHERE id = ? AND status = 'active'",
        [$item['product_id']]
    );
    if ($product) {
        $product['quantity'] = $item['quantity'];
        $product['line_total'] = $product['price'] * $item['quantity'];
        $subtotal += $product['line_total'];
        $itemCount += (int)$item['quantity'];
        $cartItems[] = $product;
    }
}

$discount = $_SESSION['coupon']['discount'] ?? 0;
$total = max(0, $subtotal - $discount);
?>

<!-- Begin Li's Breadcrumb Area -->
<div class="breadcrumb-area">
    <div class="container">
        <div class="breadcrumb-content">
            <ul>
                <li><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="active">Shopping Cart</li>
            </ul>
        </div>
    </div>
</div>
<!-- Li's Breadcrumb Area End Here -->

<style>
/* ── Shopping cart ───────────────────────────────────────────────────
   Shares its tokens with the wishlist: square corners, #e2e5ea hairlines,
   navy #0D1B2A table head, orange #F48201 for the one committing action. */
.ct-area { padding: 34px 0 60px; }

.ct-head {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px; margin-bottom: 20px;
}
.ct-head h3 { margin: 0; font-size: 26px; color: #1f2430; font-weight: 600; }
.ct-count {
    display: inline-block; margin-left: 10px; padding: 3px 10px;
    background: #F48201; color: #fff; font-size: 12px; font-weight: 600;
    vertical-align: middle; line-height: 18px;
}
.ct-keep-shopping {
    font-size: 14px; color: #4b5563; text-decoration: none;
    display: inline-flex; align-items: center; gap: 7px;
}
.ct-keep-shopping:hover { color: #F48201; }

.ct-panel { border: 1px solid #e2e5ea; background: #fff; }
.ct-table { width: 100%; margin: 0; border-collapse: collapse; }
.ct-table th {
    padding: 14px 16px; background: #0D1B2A; color: #fff;
    font-size: 12px; font-weight: 600; letter-spacing: .4px;
    text-transform: uppercase; text-align: left; border: 0; white-space: nowrap;
}
.ct-table td {
    padding: 16px; border-top: 1px solid #eef1f5;
    vertical-align: middle; background: #fff;
}
.ct-table tbody tr:first-child td { border-top: 0; }
.ct-table tbody tr:hover td { background: #fbfcfd; }

.ct-product { display: flex; align-items: center; gap: 14px; }
.ct-thumb {
    flex-shrink: 0; width: 74px; height: 74px; display: block;
    border: 1px solid #e2e5ea; background: #f7f8fa;
}
.ct-thumb img { width: 100%; height: 100%; object-fit: contain; display: block; }
.ct-name {
    display: block; font-size: 15px; line-height: 1.4; font-weight: 500;
    /* The theme greys table links to #a5a5a5, which is unreadable on white. */
    color: #1f2430; text-decoration: none;
}
.ct-name:hover { color: #F48201; }
.ct-unit-inline { display: none; }   /* mobile only */

.ct-price { font-size: 15px; color: #4b5563; white-space: nowrap; }
.ct-line-total { font-size: 16px; font-weight: 600; color: #1f2430; white-space: nowrap; }

/* Quantity stepper — keeps the .cart-qty-* hooks store.js binds to. */
.ct-qty { display: inline-flex; align-items: stretch; border: 1px solid #d9dde4; background: #fff; }
.ct-qty .cart-qty-btn {
    width: 32px; border: 0; background: #f5f6f8; color: #4b5563;
    font-size: 13px; line-height: 1; cursor: pointer; padding: 0;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s ease, color .2s ease;
}
.ct-qty .cart-qty-btn:hover { background: #F48201; color: #fff; }
.ct-qty .cart-qty-input {
    width: 46px; height: 36px; border: 0; border-left: 1px solid #eceff3;
    border-right: 1px solid #eceff3; text-align: center;
    font-size: 14px; font-weight: 600; color: #1f2430; background: #fff;
    -moz-appearance: textfield;
}
.ct-qty .cart-qty-input::-webkit-outer-spin-button,
.ct-qty .cart-qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.ct-qty .cart-qty-input:focus { outline: none; }

.ct-remove {
    width: 30px; height: 30px; padding: 0; background: #f3f4f6; border: 0;
    color: #6b7280; font-size: 14px; line-height: 1; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s ease, color .2s ease;
}
.ct-remove:hover { background: #fdeaea; color: #c0392b; }

/* ── Order summary ── */
.ct-summary { border: 1px solid #e2e5ea; background: #fff; position: sticky; top: 20px; }
.ct-summary h4 {
    margin: 0; padding: 14px 18px; background: #0D1B2A; color: #fff;
    font-size: 13px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase;
}
.ct-summary-body { padding: 18px; }

/* Coupon sits with the totals it changes, rather than floating above them. */
.ct-coupon { display: flex; gap: 0; margin-bottom: 18px; }
.ct-coupon input[type="text"] {
    flex: 1; min-width: 0; height: 42px; padding: 0 12px;
    border: 1px solid #d9dde4; border-right: 0; background: #fff;
    font-size: 13.5px; color: #1f2430;
}
.ct-coupon input[type="text"]:focus { outline: none; border-color: #F48201; }
.ct-coupon button {
    height: 42px; padding: 0 16px; border: 0; background: #000a2e; color: #fff;
    font-size: 11.5px; font-weight: 600; letter-spacing: .4px;
    text-transform: uppercase; cursor: pointer; white-space: nowrap;
    transition: background .2s ease;
}
.ct-coupon button:hover { background: #F48201; }
.ct-coupon-msg { margin-bottom: 16px; font-size: 13px; line-height: 1.5; }
.ct-coupon-msg:empty { margin-bottom: 0; }
.ct-coupon-msg .ok { color: #178201; }
.ct-coupon-msg .bad { color: #c0392b; }

.ct-lines { list-style: none; margin: 0; padding: 0; }
.ct-lines li {
    display: flex; justify-content: space-between; align-items: baseline; gap: 12px;
    padding: 9px 0; font-size: 14px; color: #4b5563;
}
.ct-lines li span { color: #1f2430; font-weight: 500; white-space: nowrap; }
.ct-lines li.ct-discount span { color: #178201; }
.ct-lines li.ct-grand {
    margin-top: 6px; padding-top: 14px; border-top: 1px solid #e2e5ea;
    font-size: 15px; color: #1f2430; font-weight: 600;
}
.ct-lines li.ct-grand span { font-size: 20px; font-weight: 700; }
/* Tax is applied on the checkout page, so say so rather than let the number
   change without explanation between here and there. */
.ct-taxnote { margin: 4px 0 16px; font-size: 12.5px; color: #9ca3af; }

.ct-btn {
    display: block; width: 100%; padding: 14px; border: 0; margin-bottom: 10px;
    font-size: 13px; font-weight: 600; letter-spacing: .4px;
    text-transform: uppercase; text-align: center; text-decoration: none;
    cursor: pointer; line-height: 1.2;
    transition: background .2s ease, color .2s ease;
}
.ct-btn:last-child { margin-bottom: 0; }
.ct-btn-primary { background: #F48201; color: #fff; }
.ct-btn-primary:hover { background: #000a2e; color: #fff; }
.ct-btn-dark { background: #000a2e; color: #fff; }
.ct-btn-dark:hover { background: #F48201; color: #fff; }

.ct-secure {
    margin: 16px 0 0; padding-top: 14px; border-top: 1px solid #eef1f5;
    font-size: 12.5px; color: #6b7280; line-height: 1.5;
    display: flex; align-items: flex-start; gap: 8px;
}
.ct-secure i { color: #178201; font-size: 14px; margin-top: 1px; }

/* Empty state */
.ct-empty { padding: 64px 20px; text-align: center; border: 1px solid #e2e5ea; background: #fff; }
.ct-empty-icon {
    width: 78px; height: 78px; margin: 0 auto 20px; background: #fff4e8;
    display: flex; align-items: center; justify-content: center;
    color: #F48201; font-size: 32px;
}
.ct-empty h4 { margin: 0 0 10px; font-size: 21px; color: #1f2430; font-weight: 600; }
.ct-empty p { margin: 0 auto 24px; max-width: 430px; font-size: 14.5px; color: #6b7280; line-height: 1.6; }
.ct-empty .ct-btn { display: inline-block; width: auto; padding: 13px 34px; }

@media (max-width: 991px) {
    /* Stacked, so pinning it would just push the items off-screen. */
    .ct-summary { position: static; margin-top: 24px; }
}

/* ── Phones: every row becomes a card ─────────────────────────────── */
@media (max-width: 767px) {
    .ct-area { padding: 22px 0 40px; }
    .ct-head h3 { font-size: 21px; }
    .ct-panel { border: 0; background: transparent; }
    .ct-table, .ct-table tbody, .ct-table tr, .ct-table td { display: block; width: 100%; }
    .ct-table thead { display: none; }
    .ct-table tr {
        border: 1px solid #e2e5ea; background: #fff;
        margin-bottom: 12px; padding: 14px; position: relative;
    }
    .ct-table td, .ct-table tbody tr:first-child td { border-top: 0; padding: 0; }
    .ct-table tbody tr:hover td { background: transparent; }

    /* Scoped through .ct-table so these outrank the display/width rules above. */
    .ct-table .ct-cell-product { padding-right: 38px; }
    .ct-thumb { width: 66px; height: 66px; }
    .ct-name { font-size: 14px; }
    /* The unit price gets its own column on desktop; on a card it reads better
       directly under the name. */
    .ct-unit-inline { display: block; margin-top: 5px; font-size: 12.5px; color: #9ca3af; }
    .ct-table .ct-cell-price { display: none; }

    /* Stepper on the left, line total on the right, one row. */
    .ct-table .ct-cell-qty,
    .ct-table .ct-cell-total {
        display: inline-block; width: auto; vertical-align: middle; margin-top: 14px;
    }
    .ct-table .ct-cell-total { float: right; }
    .ct-table .ct-cell-remove { position: absolute; top: 12px; right: 12px; width: auto; }
    .ct-table tr::after { content: ''; display: block; clear: both; }
}
</style>

<div class="ct-area">
    <div class="container">

        <div class="row">
            <div class="col-12">
                <div class="ct-head">
                    <h3>Shopping Cart<?php if ($itemCount): ?><span class="ct-count"><?= $itemCount ?> <?= $itemCount === 1 ? 'item' : 'items' ?></span><?php endif; ?></h3>
                    <a class="ct-keep-shopping" href="<?= base_url('/shop') ?>">
                        <i class="fa fa-angle-left"></i> Continue Shopping
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($cartItems)): ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="ct-panel">
                    <table class="ct-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th><span class="sr-only">Remove</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $p): ?>
                            <?php
                                $imgUrl = !empty($p['image'])
                                    ? PRODUCT_IMG_URL . '/' . rawurlencode($p['image'])
                                    : PRODUCT_IMG_URL . '/placeholder.jpg';
                                $productUrl = base_url('/single-product?slug=' . urlencode($p['slug']));
                            ?>
                            <tr id="cart-row-<?= (int)$p['id'] ?>">
                                <td class="ct-cell-product">
                                    <div class="ct-product">
                                        <a class="ct-thumb" href="<?= $productUrl ?>">
                                            <img src="<?= $imgUrl ?>" alt="<?= sanitize($p['name']) ?>">
                                        </a>
                                        <div>
                                            <a class="ct-name" href="<?= $productUrl ?>"><?= sanitize($p['name']) ?></a>
                                            <span class="ct-unit-inline"><?= format_price($p['price']) ?> each</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="ct-cell-price li-product-price">
                                    <span class="ct-price amount"><?= format_price($p['price']) ?></span>
                                </td>
                                <td class="ct-cell-qty">
                                    <div class="ct-qty">
                                        <button type="button" class="cart-qty-btn qty-minus" data-id="<?= (int)$p['id'] ?>" aria-label="Decrease quantity">
                                            <i class="fa fa-minus"></i>
                                        </button>
                                        <input class="cart-qty-input" type="number" min="1"
                                               <?php if (!empty($p['stock'])): ?>max="<?= (int)$p['stock'] ?>"<?php endif; ?>
                                               value="<?= (int)$p['quantity'] ?>" data-id="<?= (int)$p['id'] ?>"
                                               aria-label="Quantity for <?= sanitize($p['name']) ?>">
                                        <button type="button" class="cart-qty-btn qty-plus" data-id="<?= (int)$p['id'] ?>" aria-label="Increase quantity">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="ct-cell-total product-subtotal">
                                    <span class="ct-line-total amount"><?= format_price($p['line_total']) ?></span>
                                </td>
                                <td class="ct-cell-remove">
                                    <button type="button" class="ct-remove remove-cart-item"
                                            data-id="<?= (int)$p['id'] ?>"
                                            aria-label="Remove <?= sanitize($p['name']) ?> from cart" title="Remove">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="ct-summary">
                    <h4>Order Summary</h4>
                    <div class="ct-summary-body">

                        <form class="ct-coupon" id="coupon-form">
                            <input id="coupon_code" name="coupon_code" type="text" placeholder="Coupon code" autocomplete="off">
                            <button type="submit" id="apply-coupon-btn">Apply</button>
                        </form>
                        <div class="ct-coupon-msg" id="coupon-message"></div>

                        <ul class="ct-lines">
                            <li>
                                Subtotal <small>(<?= $itemCount ?> <?= $itemCount === 1 ? 'item' : 'items' ?>)</small>
                                <span id="ctSubtotal"><?= format_price($subtotal) ?></span>
                            </li>
                            <?php if ($discount > 0): ?>
                            <li class="ct-discount">
                                Coupon discount
                                <span id="ctDiscount">-<?= format_price($discount) ?></span>
                            </li>
                            <?php endif; ?>
                            <li class="ct-grand">
                                Total
                                <span id="ctTotal"><?= format_price($total) ?></span>
                            </li>
                        </ul>
                        <p class="ct-taxnote">Tax is calculated at checkout.</p>

                        <a href="<?= base_url('/checkout') ?>" class="ct-btn ct-btn-primary">Proceed to Checkout</a>
                        <a href="<?= base_url('/shop') ?>" class="ct-btn ct-btn-dark">Continue Shopping</a>

                        <p class="ct-secure">
                            <i class="fa fa-lock"></i>
                            <span>Secure checkout. Your payment details are handled by our payment provider and never stored on our servers.</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="ct-empty">
                    <div class="ct-empty-icon"><i class="fa fa-shopping-cart"></i></div>
                    <h4>Your cart is empty</h4>
                    <p>Browse the store and add something you like &mdash; your items will show up here.</p>
                    <a href="<?= base_url('/shop') ?>" class="ct-btn ct-btn-primary">Start Shopping</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
window.addEventListener('load', function () {
    if (typeof jQuery === 'undefined') { return; }
    (function ($) {
    var API = '<?= API_URL ?>';

    // A reload keeps the summary, the coupon and the header badge consistent
    // without duplicating the server's totals logic in JS.
    $(document).on('click', '.remove-cart-item', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post(API + '?action=cart_remove', { product_id: id }, function () {
            location.reload();
        });
    });

    // Submitting the form means Enter in the field applies the coupon too.
    $('#coupon-form').on('submit', function (e) {
        e.preventDefault();
        var code = $('#coupon_code').val().trim();
        var $msg = $('#coupon-message');
        if (!code) {
            $msg.html('<span class="bad">Enter a coupon code first.</span>');
            return;
        }
        $.post(API + '?action=coupon_apply', { code: code }, function (res) {
            if (res.success) {
                $msg.html('<span class="ok">' + res.message + '</span>');
                location.reload();
            } else {
                $msg.html('<span class="bad">' + res.message + '</span>');
            }
        }, 'json');
    });
    })(jQuery);
});
</script>
