/**
 * Kobo Choice - Frontend Store JavaScript (AJAX Cart/Wishlist/Search)
 */
$(document).ready(function() {

    // ─── HTML-escape helper (prevents stored XSS from product data) ────
    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    // ─── Load Header Mini Cart ─────────────────────────────────────────
    function loadMiniCart() {
        $.getJSON(API_URL + '?action=cart_get', function(res) {
            if (res.success) {
                var html = '';
                if (res.items.length === 0) {
                    html = '<li style="text-align:center;padding:20px;">Your cart is empty</li>';
                } else {
                    $.each(res.items.slice(0, 3), function(i, item) {
                        var img = item.image ? BASE + '/public/uploads/products/' + encodeURIComponent(item.image) : BASE + '/images/product/small-size/1.jpg';
                        var slug = encodeURIComponent(item.slug);
                        html += '<li>' +
                            '<a href="' + BASE + '/single-product?slug=' + slug + '" class="minicart-product-image">' +
                            '<img src="' + img + '" alt="cart products"></a>' +
                            '<div class="minicart-product-details">' +
                            '<h6><a href="' + BASE + '/single-product?slug=' + slug + '">' + esc(item.name) + '</a></h6>' +
                            '<span>' + CURRENCY_SYM + esc(item.price) + ' x ' + parseInt(item.quantity, 10) + '</span></div>' +
                            '<button class="close remove-cart-item" data-id="' + parseInt(item.id, 10) + '" title="Remove"><i class="fa fa-close"></i></button></li>';
                    });
                }
                $('#headerMinicartList').html(html);
                $('#headerMinicartTotal').text(CURRENCY_SYM + res.subtotal.toFixed(2));
                $('.cart-item-count').text(res.count);

                // ── Mobile mini-cart (own markup, so it needs its own build) ──
                if ($('#mMinicartList').length) {
                    var mHtml = '';
                    if (res.items.length === 0) {
                        mHtml = '<li class="m-minicart-empty">Your cart is empty</li>';
                    } else {
                        $.each(res.items.slice(0, 4), function (i, item) {
                            var mImg = item.image
                                ? BASE + '/public/uploads/products/' + encodeURIComponent(item.image)
                                : BASE + '/images/product/small-size/1.jpg';
                            var mSlug = encodeURIComponent(item.slug);
                            mHtml += '<li>' +
                                '<img src="' + mImg + '" alt="">' +
                                '<div class="m-mc-info">' +
                                '<a class="m-mc-name" href="' + BASE + '/single-product?slug=' + mSlug + '">' + esc(item.name) + '</a>' +
                                '<span class="m-mc-meta">' + CURRENCY_SYM + esc(item.price) + ' &times; ' + parseInt(item.quantity, 10) + '</span>' +
                                '</div>' +
                                '<button class="m-mc-remove remove-cart-item" data-id="' + parseInt(item.id, 10) + '" title="Remove">&times;</button>' +
                                '</li>';
                        });
                        if (res.items.length > 4) {
                            mHtml += '<li class="m-minicart-empty">+ ' + (res.items.length - 4) + ' more item(s)</li>';
                        }
                    }
                    $('#mMinicartList').html(mHtml);
                    $('#mMinicartTotal').text(CURRENCY_SYM + res.subtotal.toFixed(2));
                }
            }
        });
    }
    // Exposed like loadWishlistCount below, so pages that add to the cart from
    // their own scripts (the wishlist, for one) can refresh the header badge.
    window.loadMiniCart = loadMiniCart;
    loadMiniCart();

    // ─── Load Header Wishlist Count ────────────────────────────────────
    function loadWishlistCount() {
        $.getJSON(API_URL + '?action=wishlist_get', function(res) {
            if (res.success) {
                $('.wishlist-item-count').text(res.items.length);
            }
        });
    }
    window.loadWishlistCount = loadWishlistCount;

    // ─── Add to Cart ───────────────────────────────────────────────────
    $(document).on('click', '.add-to-cart-btn', function(e) {
        e.preventDefault();
        var btn = $(this);
        var productId = btn.data('id');
        var qty = parseInt($('#product-quantity').val()) || 1;

        btn.text('Adding...').css('opacity', 0.6);

        $.post(API_URL + '?action=cart_add', { product_id: productId, quantity: qty }, function(res) {
            btn.text('Add to cart').css('opacity', 1);
            if (res.success) {
                loadMiniCart();
                btn.text('Added!').addClass('added');
                setTimeout(function() { btn.text('Add to cart').removeClass('added'); }, 1500);
            } else {
                showWarningModal(res.message);
            }
        }, 'json');
    });

    // ─── Remove from Cart ──────────────────────────────────────────────
    $(document).on('click', '.remove-cart-item', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post(API_URL + '?action=cart_remove', { product_id: id }, function(res) {
            if (res.success) loadMiniCart();
        }, 'json');
    });

    // ─── Update Cart Quantity ──────────────────────────────────────────
    $(document).on('click', '.cart-qty-btn', function(e) {
        e.preventDefault();
        var input = $(this).siblings('.cart-qty-input');
        var val = parseInt(input.val()) || 1;
        if ($(this).hasClass('qty-plus')) val++;
        else if (val > 1) val--;
        input.val(val).trigger('change');
    });

    $(document).on('change', '.cart-qty-input', function() {
        var id = $(this).data('id');
        var qty = parseInt($(this).val()) || 1;
        var $input = $(this);
        $.post(API_URL + '?action=cart_update', { product_id: id, quantity: qty }, function(res) {
            if (res.success) {
                loadMiniCart();
                var $row = $input.closest('tr');
                if ($row.length) {
                    // Money is formatted with thousands separators, so strip
                    // commas as well as the currency symbol before parsing.
                    function money(text) {
                        return parseFloat(String(text).replace(/[^0-9.]/g, '')) || 0;
                    }
                    function show(amount) {
                        return CURRENCY_SYM + amount.toLocaleString('en-US', {
                            minimumFractionDigits: 2, maximumFractionDigits: 2
                        });
                    }

                    var price = money($row.find('.li-product-price .amount').text());
                    $row.find('.product-subtotal .amount').text(show(price * qty));

                    var newSubtotal = 0;
                    $('.ct-table tbody tr').each(function() {
                        newSubtotal += money($(this).find('.product-subtotal .amount').text());
                    });

                    // Named hooks rather than list positions — the discount row
                    // only exists when a coupon is applied.
                    $('#ctSubtotal').text(show(newSubtotal));
                    var discount = money($('#ctDiscount').text());
                    $('#ctTotal').text(show(Math.max(0, newSubtotal - discount)));

                    // The header chip and the "Subtotal (n items)" label are
                    // rendered server-side, so they go stale without this.
                    var units = 0;
                    $('.ct-table .cart-qty-input').each(function() {
                        units += parseInt($(this).val(), 10) || 0;
                    });
                    var label = units + (units === 1 ? ' item' : ' items');
                    $('.ct-count').text(label);
                    $('.ct-lines li').first().find('small').text('(' + label + ')');
                }
            }
        }, 'json');
    });

    // ─── Toggle Wishlist ───────────────────────────────────────────────
    $(document).on('click', '.wishlist-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var btn = $(this);
        var productId = btn.data('id');

        $.post(API_URL + '?action=wishlist_add', { product_id: productId }, function(res) {
            if (res.success) {
                btn.addClass('active').find('i').removeClass('fa-heart-o').addClass('fa-heart');
                if (typeof loadWishlistCount === 'function') loadWishlistCount();
            } else {
                if (res.message && res.message.indexOf('already') >= 0) {
                    $.post(API_URL + '?action=wishlist_remove', { product_id: productId }, function(r) {
                        btn.removeClass('active').find('i').removeClass('fa-heart').addClass('fa-heart-o');
                        if (typeof loadWishlistCount === 'function') loadWishlistCount();
                    }, 'json');
                } else {
                    showWarningModal(res.message || 'Please login to use your wishlist.');
                }
            }
        }, 'json').fail(function(xhr) {
            if (xhr.status === 401) {
                showWarningModal('Please login to save items to your wishlist.');
                window.location.href = BASE + '/login';
            }
        });
    });

    // ─── Newsletter Subscribe ──────────────────────────────────────────
    $('#newsletterForm, #mc-embedded-subscribe-form').on('submit', function(e) {
        e.preventDefault();
        var email = $(this).find('input[type="email"]').val();
        $.post(API_URL + '?action=newsletter_subscribe', { email: email }, function(res) {
            showWarningModal(res.message);
        }, 'json');
    });

    // ─── Search Suggestions ────────────────────────────────────────────
    var searchTimer;
    $('.hm-searchbox input[name="q"]').on('input', function() {
        var q = $(this).val();
        clearTimeout(searchTimer);
        if (q.length < 2) return;
        searchTimer = setTimeout(function() {
            $.getJSON(API_URL + '?action=search_suggestions&q=' + encodeURIComponent(q), function(res) {
                // Could populate a dropdown here; keeping it simple for now
            });
        }, 300);
    });

    // ─── Product Quantity on Single Product Page ───────────────────────
    $(document).on('click', '.dec', function(e) {
        e.preventDefault();
        var input = $('#product-quantity');
        var val = parseInt(input.val(), 10) || 1;
        if (val > 1) input.val(val - 1);
    });

    $(document).on('click', '.inc', function(e) {
        e.preventDefault();
        var input = $('#product-quantity');
        var val = parseInt(input.val(), 10) || 1;
        var max = parseInt(input.attr('max'), 10);
        var next = val + 1;
        if (!isNaN(max) && next > max) next = max;
        input.val(next);
    });
});
