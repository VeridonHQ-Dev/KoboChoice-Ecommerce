<?php
/**
 * Kobo Choice - Wishlist View
 *
 * Desktop keeps the table layout; below 768px each row collapses into a card,
 * because a seven-column table is unusable on a phone. Both layouts are driven
 * from the same markup so there is only one copy of the data.
 */
$customerId = $_SESSION['customer_id'];
$wishlistItems = $db->fetchAll(
    "SELECT w.id AS wishlist_id, w.product_id, w.created_at, p.name, p.slug, p.price, p.image, p.stock
     FROM wishlists w
     JOIN products p ON w.product_id = p.id
     WHERE w.customer_id = ? AND p.status = 'active'
     ORDER BY w.created_at DESC",
    [$customerId]
);
$wishlistTotal = count($wishlistItems);
?>

<!-- Begin Li's Breadcrumb Area -->
<div class="breadcrumb-area">
    <div class="container">
        <div class="breadcrumb-content">
            <ul>
                <li><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="active">My Wishlist</li>
            </ul>
        </div>
    </div>
</div>
<!-- Li's Breadcrumb Area End Here -->

<style>
/* ── Wishlist ────────────────────────────────────────────────────────
   Same visual language as the rest of the store: square corners, the
   orange/navy button pair from the mini-cart, hairline #e2e5ea borders. */
.wl-area { padding: 34px 0 60px; }

/* Page head: title on the left, "keep shopping" escape hatch on the right. */
.wl-head {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px; margin-bottom: 20px;
}
.wl-head h3 { margin: 0; font-size: 26px; color: #1f2430; font-weight: 600; }
.wl-count {
    display: inline-block; margin-left: 10px; padding: 3px 10px;
    background: #F48201; color: #fff; font-size: 12px; font-weight: 600;
    vertical-align: middle; line-height: 18px;
}
.wl-keep-shopping {
    font-size: 14px; color: #4b5563; text-decoration: none;
    display: inline-flex; align-items: center; gap: 7px;
}
.wl-keep-shopping:hover { color: #F48201; }

/* The table sits in a bordered panel rather than floating on the page. */
.wl-panel { border: 1px solid #e2e5ea; background: #fff; }
.wl-table { width: 100%; margin: 0; border-collapse: collapse; }
.wl-table th {
    padding: 14px 16px; background: #0D1B2A; color: #fff;
    font-size: 12px; font-weight: 600; letter-spacing: .4px;
    text-transform: uppercase; text-align: left; border: 0; white-space: nowrap;
}
.wl-table td {
    padding: 16px; border-top: 1px solid #eef1f5;
    vertical-align: middle; background: #fff;
}
.wl-table tbody tr:first-child td { border-top: 0; }
.wl-table tbody tr:hover td { background: #fbfcfd; }

/* Product cell — thumbnail and name travel together. */
.wl-product { display: flex; align-items: center; gap: 14px; }
.wl-thumb {
    flex-shrink: 0; width: 74px; height: 74px; display: block;
    border: 1px solid #e2e5ea; background: #f7f8fa;
}
.wl-thumb img { width: 100%; height: 100%; object-fit: contain; display: block; }
.wl-name {
    display: block; font-size: 15px; line-height: 1.4; font-weight: 500;
    /* The theme greys table links out to #a5a5a5, which is unreadable here. */
    color: #1f2430; text-decoration: none;
}
.wl-name:hover { color: #F48201; }
.wl-added { display: none; }   /* mobile only — desktop has its own column */

.wl-price { font-size: 16px; font-weight: 600; color: #1f2430; white-space: nowrap; }
.wl-date { font-size: 13.5px; color: #6b7280; white-space: nowrap; }

/* Stock pills, so the two states are distinguishable at a glance. */
.wl-pill {
    display: inline-block; padding: 4px 11px; font-size: 12px; font-weight: 600;
    line-height: 17px; white-space: nowrap;
}
.wl-pill.is-in { background: #e7f6ec; color: #178201; }
.wl-pill.is-out { background: #fdeaea; color: #c0392b; }

/* Buttons mirror the mini-cart pair: navy = add, orange = commit to buying. */
.wl-actions { display: flex; flex-direction: column; gap: 8px; min-width: 138px; }
.wl-btn {
    display: block; width: 100%; padding: 11px 14px; border: 0;
    font-size: 12.5px; font-weight: 600; letter-spacing: .4px;
    text-transform: uppercase; text-align: center; text-decoration: none;
    cursor: pointer; line-height: 1.2; white-space: nowrap;
    transition: background .2s ease, color .2s ease;
}
.wl-btn-dark { background: #000a2e; color: #fff; }
.wl-btn-dark:hover { background: #F48201; color: #fff; }
.wl-btn-primary { background: #F48201; color: #fff; }
.wl-btn-primary:hover { background: #000a2e; color: #fff; }
.wl-unavailable { font-size: 13px; color: #9ca3af; font-style: italic; }

.wl-remove {
    width: 30px; height: 30px; padding: 0; background: #f3f4f6; border: 0;
    color: #6b7280; font-size: 14px; line-height: 1; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s ease, color .2s ease;
}
.wl-remove:hover { background: #fdeaea; color: #c0392b; }

/* Empty state */
.wl-empty { padding: 64px 20px; text-align: center; border: 1px solid #e2e5ea; background: #fff; }
.wl-empty-icon {
    width: 78px; height: 78px; margin: 0 auto 20px; background: #fff4e8;
    display: flex; align-items: center; justify-content: center;
    color: #F48201; font-size: 32px;
}
.wl-empty h4 { margin: 0 0 10px; font-size: 21px; color: #1f2430; font-weight: 600; }
.wl-empty p { margin: 0 auto 24px; max-width: 430px; font-size: 14.5px; color: #6b7280; line-height: 1.6; }
.wl-empty .wl-btn { display: inline-block; width: auto; padding: 13px 34px; }

/* ── Phones: every row becomes a card ─────────────────────────────── */
@media (max-width: 767px) {
    .wl-area { padding: 22px 0 40px; }
    .wl-head h3 { font-size: 21px; }
    .wl-panel { border: 0; background: transparent; }
    .wl-table, .wl-table tbody, .wl-table tr, .wl-table td { display: block; width: 100%; }
    .wl-table thead { display: none; }        /* the labels move onto each cell */
    .wl-table tr {
        border: 1px solid #e2e5ea; background: #fff;
        margin-bottom: 12px; padding: 14px; position: relative;
    }
    .wl-table td, .wl-table tbody tr:first-child td { border-top: 0; padding: 0; }
    .wl-table tbody tr:hover td { background: transparent; }

    /* Every per-cell rule below is scoped through .wl-table so it outranks the
       `.wl-table td { display: block; width: 100% }` above — otherwise the
       cells stay full-width blocks and the remove cell covers the name. */

    /* Thumbnail + name + date stacked in the card's header. */
    .wl-table .wl-cell-product { padding-right: 38px; }
    .wl-thumb { width: 66px; height: 66px; }
    .wl-name { font-size: 14px; }
    .wl-added { display: block; margin-top: 5px; font-size: 12px; color: #9ca3af; }

    /* Price and stock share one line below it; the date column is redundant
       here because it is already printed under the product name. */
    .wl-table .wl-cell-date { display: none; }
    .wl-table .wl-cell-price,
    .wl-table .wl-cell-stock {
        display: inline-block; width: auto; vertical-align: middle; margin-top: 12px;
    }
    .wl-table .wl-cell-price { margin-right: 12px; }

    .wl-table .wl-cell-action { margin-top: 12px; }
    .wl-actions { flex-direction: row; min-width: 0; }
    .wl-actions .wl-btn { flex: 1; padding: 12px 8px; }

    /* Remove button tucks into the card's top-right corner. */
    .wl-table .wl-cell-remove {
        position: absolute; top: 12px; right: 12px; width: auto;
    }
}
@media (max-width: 359px) {
    .wl-actions { flex-direction: column; }
}
</style>

<div class="wl-area">
    <div class="container">
        <div class="row">
            <div class="col-12">

                <div class="wl-head">
                    <h3>My Wishlist<?php if ($wishlistTotal): ?><span class="wl-count"><?= $wishlistTotal ?> <?= $wishlistTotal === 1 ? 'item' : 'items' ?></span><?php endif; ?></h3>
                    <a class="wl-keep-shopping" href="<?= base_url('/shop') ?>">
                        <i class="fa fa-angle-left"></i> Continue Shopping
                    </a>
                </div>

                <?php if (!empty($wishlistItems)): ?>
                <div class="wl-panel">
                    <table class="wl-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit Price</th>
                                <th>Date Added</th>
                                <th>Stock</th>
                                <th>Action</th>
                                <th><span class="sr-only">Remove</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wishlistItems as $item): ?>
                            <?php
                                $imgUrl = !empty($item['image'])
                                    ? PRODUCT_IMG_URL . '/' . rawurlencode($item['image'])
                                    : PRODUCT_IMG_URL . '/placeholder.jpg';
                                $productUrl = base_url('/single-product?slug=' . urlencode($item['slug']));
                                $addedOn = format_date($item['created_at'], 'M d, Y');
                                $inStock = (int)$item['stock'] > 0;
                            ?>
                            <tr id="wishlist-row-<?= (int)$item['product_id'] ?>">
                                <td class="wl-cell-product">
                                    <div class="wl-product">
                                        <a class="wl-thumb" href="<?= $productUrl ?>">
                                            <img src="<?= $imgUrl ?>" alt="<?= sanitize($item['name']) ?>">
                                        </a>
                                        <div>
                                            <a class="wl-name" href="<?= $productUrl ?>"><?= sanitize($item['name']) ?></a>
                                            <span class="wl-added">Added <?= sanitize($addedOn) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="wl-cell-price">
                                    <span class="wl-price"><?= format_price($item['price']) ?></span>
                                </td>
                                <td class="wl-cell-date">
                                    <span class="wl-date"><?= sanitize($addedOn) ?></span>
                                </td>
                                <td class="wl-cell-stock">
                                    <span class="wl-pill <?= $inStock ? 'is-in' : 'is-out' ?>">
                                        <?= $inStock ? 'In Stock' : 'Out of Stock' ?>
                                    </span>
                                </td>
                                <td class="wl-cell-action">
                                    <?php if ($inStock): ?>
                                        <div class="wl-actions">
                                            <a href="#" class="wl-btn wl-btn-dark add-to-cart-wishlist" data-id="<?= (int)$item['product_id'] ?>">Add to Cart</a>
                                            <a href="#" class="wl-btn wl-btn-primary buy-now-wishlist" data-id="<?= (int)$item['product_id'] ?>">Buy Now</a>
                                        </div>
                                    <?php else: ?>
                                        <span class="wl-unavailable">Currently unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td class="wl-cell-remove">
                                    <button type="button" class="wl-remove remove-wishlist-item"
                                            data-id="<?= (int)$item['product_id'] ?>"
                                            aria-label="Remove <?= sanitize($item['name']) ?> from wishlist" title="Remove">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="wl-empty">
                    <div class="wl-empty-icon"><i class="fa fa-heart-o"></i></div>
                    <h4>Your wishlist is empty</h4>
                    <p>Tap the heart on any product to save it here, then come back when you are ready to buy.</p>
                    <a href="<?= base_url('/shop') ?>" class="wl-btn wl-btn-primary">Start Shopping</a>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
// Rendered before jQuery (footer) — defer binding until $ is available.
window.addEventListener('load', function () {
    if (typeof jQuery === 'undefined') { return; }
    (function ($) {
    var API = '<?= API_URL ?>';
    var BASE = '<?= base_url() ?>';

    function notify(message) {
        if (typeof showWarningModal === 'function') { showWarningModal(message); }
        else { alert(message); }
    }

    // Add the wishlist item to the cart (stays in wishlist).
    $(document).on('click', '.add-to-cart-wishlist', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var id = $btn.data('id');
        $btn.text('Adding...');
        $.post(API + '?action=cart_add', { product_id: id, quantity: 1 }, function (res) {
            if (res.success) {
                $btn.text('Added!');
                if (typeof loadMiniCart === 'function') loadMiniCart();
                setTimeout(function () { $btn.text('Add to Cart'); }, 1500);
            } else {
                notify(res.message || 'Could not add to cart.');
                $btn.text('Add to Cart');
            }
        }, 'json');
    });

    // Add to cart, then go straight to checkout.
    $(document).on('click', '.buy-now-wishlist', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post(API + '?action=cart_add', { product_id: id, quantity: 1 }, function (res) {
            if (res.success) {
                // Extensionless URL — /checkout.php is now a 301 away from here.
                window.location.href = BASE + '/checkout';
            } else {
                notify(res.message || 'Could not add to cart.');
            }
        }, 'json');
    });

    // Remove from wishlist (API expects product_id).
    $(document).on('click', '.remove-wishlist-item', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post(API + '?action=wishlist_remove', { product_id: id }, function (res) {
            if (res.success) {
                $('#wishlist-row-' + id).fadeOut(300, function () {
                    $(this).remove();
                    var left = $('.wl-table tbody tr').length;
                    if (left === 0) {
                        // Reload so the server renders the empty state.
                        location.reload();
                        return;
                    }
                    // Keep the header badge honest without a round trip.
                    $('.wl-count').text(left + (left === 1 ? ' item' : ' items'));
                });
                if (typeof loadWishlistCount === 'function') loadWishlistCount();
            } else {
                notify(res.message || 'Could not remove item.');
            }
        }, 'json');
    });
    })(jQuery);
});
</script>
