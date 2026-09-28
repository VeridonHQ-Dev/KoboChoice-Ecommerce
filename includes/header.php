<?php
/**
 * Kobo Choice - Frontend Header Include
 * Extracted from existing HTML to eliminate duplication.
 * Expects: $pageTitle (string), $bodyClass (string, optional)
 */
if (!isset($pageTitle)) $pageTitle = 'Kobo Choice';
if (!isset($bodyClass)) $bodyClass = '';

require_once __DIR__ . '/auth.php';
$cartCount = cart_count();
$wishlistCount = is_customer_logged_in() ? $db->count('wishlists', 'customer_id = ?', [$_SESSION['customer_id']]) : 0;
$categories = $db->fetchAll("SELECT * FROM categories WHERE show_in_menu = 1 ORDER BY sort_order ASC");

/* Single source of truth for the primary navigation — rendered in both the
   desktop menu bar and the hidden source the mobile (meanmenu) menu clones,
   so the two can never drift apart. */
$mainNav = [
    BASE_URL . '/'     => 'Home',
    BASE_URL . '/shop'      => 'Shop',
    BASE_URL . '/about-us' => 'About Us',
    BASE_URL . '/contact'   => 'Contact',
];
$currentUrl = BASE_URL . '/' . basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?= sanitize($pageTitle) ?> || Kobo Choice</title>
    <meta name="description" content="Kobo Choice - Original African Stuffs">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?= BASE_URL ?>/images/favicon.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/fontawesome-stars.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/meanmenu.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/owl.carousel.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/slick.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/animate.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/jquery-ui.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/venobox.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/nice-select.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/magnific-popup.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/helper.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/style.css?v=<?= @filemtime(__DIR__ . '/../style.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsive.css?v=<?= @filemtime(__DIR__ . '/../css/responsive.css') ?>">
    <script src="<?= BASE_URL ?>/js/vendor/modernizr-2.8.3.min.js"></script>
    <style>
    .hm-dashboard-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        height: 45px;
        line-height: 45px;
        padding: 0 15px;
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 2px;
        background: transparent;
        color: #b0b0b0;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s ease-in-out;
        white-space: nowrap;
        cursor: pointer;
    }
    .hm-dashboard-btn:hover {
        background: #F48201;
        border-color: #F48201;
        color: #fff;
    }
    .hm-dashboard-btn i { font-size: 18px; color: #b0b0b0; transition: all 0.3s ease-in-out; }
    .hm-dashboard-btn:hover i { color: #fff; }
    .hm-dashboard { list-style: none; display: flex; align-items: center; }
    .hb-menu-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .hb-user-greeting {
        color: #ffffff;
        font-size: 16px;
        font-weight: 400;
        white-space: nowrap;
    }
    .hb-user-greeting strong { font-weight: 400; }

    /* ══ Sticky footer ══════════════════════════════════════════════
       On short pages (empty cart, empty checkout) the content did not reach
       the bottom of the screen, so the footer stopped wherever the content
       ended and left a band of page background beneath it. Collapsing the
       footer made this obvious because it removes ~860px of height.
       The wrapper now always fills at least the viewport and the footer is
       pushed to the bottom, so there is never a gap under it. */
    html, body { height: 100%; }
    .body-wrapper {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    .body-wrapper > .footer { margin-top: auto; }
    /* Flex items default to shrinking; keep sections at their natural height. */
    .body-wrapper > * { flex-shrink: 0; }

    /* ══ Mobile app header ══════════════════════════════════════════ */
    /* Side padding is a variable so the dropdowns can cancel it out and sit
       flush against the screen edge without hard-coding the same number twice. */
    .m-header { display: none; --m-pad: 14px; }
    @media (max-width: 991px) {
        /* The logo is absolutely centred in the bar, so its own padding can't
           open the section up — the vertical breathing room comes from here. */
        .m-header { display: block; background: #0D1B2A; padding: 22px var(--m-pad) 20px; }
        .header-mobile { display: none !important; }   /* retire the theme's bar */
    }
    /* The burger and the action cluster have different widths, so the logo is
       centred against the bar itself rather than flexed between them. */
    .m-header-bar { display: flex; align-items: center; justify-content: space-between; position: relative; }

    .m-burger {
        width: 40px; height: 40px; flex-shrink: 0;
        background: none; border: 0; padding: 9px 8px; cursor: pointer;
        display: flex; flex-direction: column; justify-content: space-between;
    }
    .m-burger span {
        display: block; height: 2px; width: 100%; background: #fff; border-radius: 2px;
        transition: transform .25s ease, opacity .25s ease;
    }

    .m-logo {
        position: absolute; left: 50%; top: 50%;
        transform: translate(-50%, -50%);
        display: flex; align-items: center; pointer-events: auto;
    }
    /* max-height + max-width (rather than a fixed height) so the logo scales to
       whichever limit bites first without ever being squashed. The width cap
       steps down so it can never run under the action icons on small phones. */
    .m-logo img { max-height: 44px; max-width: 165px; width: auto; height: auto; display: block; }
    @media (max-width: 420px) { .m-logo img { max-height: 40px; max-width: 128px; } }
    @media (max-width: 375px) { .m-logo img { max-height: 38px; max-width: 116px; } }
    @media (max-width: 350px) { .m-logo img { max-height: 34px; max-width: 92px; } }

    .m-actions { display: flex; align-items: center; gap: 2px; flex-shrink: 0; }
    /* The cart action is a <button>; without these resets the browser paints its
       default button face, which showed up as a white circle behind the icon.
       No hover/focus background either — just the plain white icon. */
    .m-action {
        position: relative; width: 34px; height: 34px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 18px; text-decoration: none;
        background: transparent; background-color: transparent;
        border: 0; border-radius: 0; padding: 0; margin: 0;
        cursor: pointer; -webkit-appearance: none; appearance: none;
        box-shadow: none;
    }
    .m-action:hover,
    .m-action:focus,
    .m-action:active {
        background: transparent; color: #fff; outline: none; box-shadow: none;
    }
    /* The theme styles .cart-item-count / .wishlist-item-count for the desktop
       mini-cart (left:-60px, min-width:26px). Those classes are kept so store.js
       can update the counts, but every positioning property must be reset here
       or the badge stretches between the theme's `left` and this `right`. */
    .m-action .m-badge {
        position: absolute; top: 0; right: 0; bottom: auto; left: auto;
        width: auto; min-width: 16px; height: 16px; padding: 0 4px;
        background: #F48201; color: #fff; border-radius: 10px;
        font-size: 10px; font-weight: 700; line-height: 16px; text-align: center;
        overflow: visible; display: block; box-shadow: 0 0 0 2px #0D1B2A;
    }

    /* ── Mobile mini-cart dropdown ── */
    .m-cart-wrap { position: relative; }
    .m-minicart {
        /* Pulled out by the header's own side padding so the panel finishes
           hard against the right edge of the screen. */
        position: absolute; top: calc(100% + 10px);
        right: calc(var(--m-pad) * -1); z-index: 900;
        width: 84vw; max-width: 320px;
        background: #fff; border-radius: 0;
        box-shadow: 0 12px 34px rgba(0,0,0,.28);
        overflow: hidden; color: #1f2430;
    }
    /* Little arrow pointing back at the cart icon, which now sits one header
       padding further in than the panel's edge. */
    .m-minicart::before {
        content: ''; position: absolute; top: -7px; right: 24px;
        border-left: 7px solid transparent; border-right: 7px solid transparent;
        border-bottom: 7px solid #fff;
    }
    .m-minicart-list { list-style: none; margin: 0; padding: 6px 0; max-height: 44vh; overflow-y: auto; }
    .m-minicart-list > li {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 14px; border-bottom: 1px solid #f1f3f6; position: relative;
    }
    .m-minicart-list > li:last-child { border-bottom: 0; }
    .m-minicart-list img { width: 44px; height: 44px; object-fit: contain; border-radius: 0; background: #f7f8fa; flex-shrink: 0; }
    .m-minicart-list .m-mc-info { flex: 1; min-width: 0; }
    .m-minicart-list .m-mc-name {
        display: block; font-size: 13px; color: #1f2430; line-height: 1.35;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-decoration: none;
    }
    .m-minicart-list .m-mc-meta { font-size: 12px; color: #6b7280; }
    .m-minicart-list .m-mc-remove {
        background: none; border: 0; color: #9ca3af; font-size: 16px; cursor: pointer; padding: 2px 4px;
    }
    .m-minicart-list .m-mc-remove:hover { color: #dc2626; }
    .m-minicart-empty { justify-content: center; color: #6b7280; font-size: 13px; padding: 22px 14px !important; }
    .m-minicart-total {
        display: flex; justify-content: space-between; align-items: center;
        padding: 11px 14px; background: #f8fafc; border-top: 1px solid #eef1f5;
        font-size: 13px; color: #4b5563;
    }
    .m-minicart-total strong { font-size: 15px; color: #1f2430; }
    /* Buttons inherit the theme's .li-button look; only the spacing is set here. */
    .m-minicart-actions { padding: 12px 14px 14px; }
    .m-minicart-actions .li-button { margin-top: 0; margin-bottom: 8px; }
    .m-minicart-actions .li-button:last-child { margin-bottom: 0; }

    /* ── Mobile account dropdown ──
       Same shell as the mini-cart above (position, arrow, shadow, radius) so
       the two panels read as one component; only the contents differ. */
    .m-account-wrap { position: relative; }
    .m-accountmenu {
        /* Anchored to the account icon, which has the wishlist and cart icons
           (34px each, 2px gaps = 72px) to its right, so it has those to clear
           as well as the header padding before it reaches the screen edge. */
        position: absolute; top: calc(100% + 10px);
        right: calc((var(--m-pad) + 72px) * -1); z-index: 900;
        width: 68vw; max-width: 248px;
        background: #fff; border-radius: 0;
        box-shadow: 0 12px 28px rgba(13, 27, 42, .18);
        overflow: hidden; color: #1f2430;
    }
    /* The account icon is the leftmost of the three, so its arrow sits far
       further in than the mini-cart's — it was previously landing over the
       cart icon instead. From the icon row's right edge back to the account
       icon's centre is 34+2+34+2+17 = 89px, plus the header padding the panel
       just cancelled out, less half the arrow's own width. */
    .m-accountmenu::before {
        content: ''; position: absolute; top: -7px;
        right: calc(var(--m-pad) + 89px - 7px);
        border-left: 7px solid transparent; border-right: 7px solid transparent;
        border-bottom: 7px solid #fff;
    }
    .m-accountmenu-head {
        padding: 11px 14px; background: #f8fafc; border-bottom: 1px solid #eef1f5;
        font-size: 11.5px; color: #6b7280; line-height: 1.4;
    }
    .m-accountmenu-head strong {
        display: block; font-size: 13.5px; color: #1f2430; font-weight: 600;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .m-accountmenu ul { list-style: none; margin: 0; padding: 6px 0; }
    .m-accountmenu li { margin: 0; }
    .m-accountmenu a {
        display: flex; align-items: center; gap: 11px;
        padding: 12px 14px; min-height: 46px;
        font-size: 13.5px; color: #1f2430; text-decoration: none;
    }
    .m-accountmenu a > i { width: 17px; text-align: center; color: #9ca3af; font-size: 15px; }
    .m-accountmenu a:hover, .m-accountmenu a:focus { background: #f8fafc; color: #F48201; }
    .m-accountmenu a:hover > i, .m-accountmenu a:focus > i { color: #F48201; }
    /* Marks the section already on screen, so the menu doubles as a position
       indicator rather than four identical links. */
    .m-accountmenu a.is-current { background: #fff7ed; color: #F48201; font-weight: 600; }
    .m-accountmenu a.is-current > i { color: #F48201; }
    .m-accountmenu-sep { border-top: 1px solid #eef1f5; margin-top: 6px; padding-top: 6px; }
    .m-accountmenu-sep a, .m-accountmenu-sep a > i { color: #dc2626; }
    .m-accountmenu-sep a:hover { background: #fef2f2; color: #dc2626; }

    .m-search { margin-top: 11px; }
    .m-search form { position: relative; }
    .m-search input {
        width: 100%; height: 42px; border: 0; border-radius: 22px;
        padding: 0 48px 0 18px; font-size: 14px; color: #1f2430; background: #fff;
    }
    .m-search input:focus { outline: none; box-shadow: 0 0 0 3px rgba(244,130,1,.35); }
    .m-search button {
        position: absolute; right: 4px; top: 4px;
        width: 34px; height: 34px; border: 0; border-radius: 50%;
        background: #F48201; color: #fff; font-size: 14px; cursor: pointer;
    }

    /* ══ Slide-in drawer ════════════════════════════════════════════ */
    .m-drawer-overlay {
        position: fixed; inset: 0; z-index: 9998;
        background: rgba(13,27,42,.5); opacity: 0;
        transition: opacity .28s ease;
    }
    .m-drawer-overlay.is-open { opacity: 1; }

    .m-drawer {
        position: fixed; top: 0; left: 0; bottom: 0; z-index: 9999;
        width: 86%; max-width: 340px; background: #fff;
        display: flex; flex-direction: column;
        transform: translateX(-100%);
        transition: transform .3s cubic-bezier(.4,0,.2,1);
        box-shadow: 4px 0 26px rgba(0,0,0,.22);
    }
    .m-drawer.is-open { transform: translateX(0); }

    .m-drawer-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 18px; border-bottom: 1px solid #eceef2; flex-shrink: 0;
    }
    .m-drawer-logo img { height: 30px; width: auto; }
    .m-drawer-close {
        background: none; border: 0; cursor: pointer;
        width: 36px; height: 36px; border-radius: 50%;
        font-size: 27px; line-height: 1; color: #64748b;
    }
    .m-drawer-close:hover { background: #f1f5f9; color: #0D1B2A; }

    .m-drawer-tabs { display: flex; border-bottom: 1px solid #eceef2; flex-shrink: 0; }
    .m-drawer-tabs button {
        flex: 1; background: none; border: 0; cursor: pointer;
        padding: 14px 8px; font-size: 13px; font-weight: 700;
        letter-spacing: .8px; text-transform: uppercase; color: #94a3b8;
        border-bottom: 3px solid transparent; transition: color .2s, border-color .2s;
    }
    .m-drawer-tabs button.is-active { color: #F48201; border-bottom-color: #F48201; }

    .m-drawer-body { flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; }
    .m-panel { display: none; }
    .m-panel.is-active { display: block; }
    .m-panel-title {
        padding: 16px 18px 8px; font-size: 12px; font-weight: 700;
        letter-spacing: 1px; text-transform: uppercase; color: #94a3b8;
    }

    .m-list { list-style: none; margin: 0; padding: 0; }
    .m-list + .m-list { border-top: 8px solid #f4f5f7; }
    .m-list > li { border-bottom: 1px solid #f1f3f6; }
    .m-list > li:last-child { border-bottom: 0; }
    .m-list > li > a {
        display: flex; align-items: center; gap: 13px;
        padding: 14px 18px; font-size: 15px; color: #1f2430; text-decoration: none;
        transition: background .15s, color .15s;
    }
    .m-list > li > a:hover { background: #fff7ed; color: #F48201; }
    /* ── Product card prices on narrow 2-up grids ────────────────────
       With two cards per row the price box is ~112px, but "$485,000.00"
       plus the struck-through old price needs ~184px, so the pair spilled
       outside the card. Let them wrap and shrink slightly instead. */
    @media (max-width: 767px) {
        .single-product-wrap .price-box {
            display: flex; flex-wrap: wrap; align-items: baseline;
            gap: 2px 7px; overflow: hidden;
        }
        .single-product-wrap .price-box .new-price,
        .single-product-wrap .price-box .old-price {
            float: none; margin: 0; font-size: 13px; line-height: 1.35;
        }
        .single-product-wrap .product_name,
        .single-product-wrap h4 > a {
            font-size: 13px; line-height: 1.35;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden;
        }
    }

    .m-list > li > a > i:first-child { width: 20px; font-size: 17px; color: #64748b; text-align: center; }
    .m-list > li > a:hover > i:first-child { color: #F48201; }
    .m-cat-img { width: 22px; height: 22px; object-fit: contain; border-radius: 3px; flex-shrink: 0; }
    .m-chev { margin-left: auto; color: #cbd5e1; font-size: 19px; }
    .m-lead { font-size: 16px; font-weight: 600; }
    </style>
</head>
<body class="<?= $bodyClass ?>">
<!--[if lt IE 8]>
    <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
<![endif]-->
<script>
// Global safety net: any product/category image that fails to load (missing
// file, deleted upload, bad path) falls back to the placeholder instead of a
// broken-image icon. The data-fallback flag prevents an infinite loop if the
// placeholder itself is ever unavailable.
window.PLACEHOLDER_IMG = '<?= PRODUCT_IMG_URL ?>/placeholder.jpg';
document.addEventListener('error', function (e) {
    var img = e.target;
    if (img && img.tagName === 'IMG' && !img.dataset.fallbackApplied) {
        img.dataset.fallbackApplied = '1';
        img.src = window.PLACEHOLDER_IMG;
    }
}, true);
</script>
<div class="body-wrapper">
<?php /* Pages like login set $hideChrome to render the form on its own,
         without the site header/footer. The <head> assets and the scripts
         in footer.php are still loaded — only the visual chrome is skipped. */ ?>
<?php if (empty($hideChrome)): ?>
    <header>
        <?php
        /* ── Mobile app-style header + slide-in drawer ──────────────────────
           Replaces the theme's meanmenu hamburger, which cloned a hidden <nav>
           and offered no room for account links, categories or badges. */
        $mLoggedIn   = is_customer_logged_in();
        $mAccountUrl = $mLoggedIn ? BASE_URL . '/customer-dashboard' : BASE_URL . '/login';
        $mCustomer   = $mLoggedIn ? get_current_customer() : null;
        /* Highlight the section already on screen. Only meaningful on the
           dashboard itself; elsewhere nothing matches and nothing lights up. */
        $mOnDashboard = strpos($_SERVER['REQUEST_URI'] ?? '', 'customer-dashboard') !== false;
        $mActiveTab   = $mOnDashboard ? ($_GET['tab'] ?? 'orders') : '';
        ?>
        <div class="m-header d-lg-none">
            <div class="m-header-bar">
                <button type="button" class="m-burger" id="mDrawerOpen" aria-label="Open menu" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>

                <a class="m-logo" href="<?= BASE_URL ?>/">
                    <img src="<?= BASE_URL ?>/images/menu/logo/2black.png" alt="<?= sanitize(SITE_NAME) ?>">
                </a>

                <div class="m-actions">
                    <?php /* Signed in, the account icon opens the dashboard sections as a
                             dropdown instead of navigating — it keeps that navigation out
                             of the page body. Signed out there is nothing to list, so it
                             stays a plain link to login. */ ?>
                    <?php if ($mLoggedIn): ?>
                    <div class="m-account-wrap">
                        <button type="button" class="m-action" id="mAccountTrigger"
                                aria-label="My account" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-user-o"></i>
                        </button>

                        <div class="m-accountmenu" id="mAccountMenu" hidden>
                            <div class="m-accountmenu-head">
                                Signed in as
                                <strong><?= sanitize($mCustomer['name'] ?? 'My account') ?></strong>
                            </div>
                            <ul>
                                <?php foreach (customer_dashboard_tabs() as $tabKey => $tab): ?>
                                    <li>
                                        <a href="<?= BASE_URL ?>/customer-dashboard?tab=<?= $tabKey ?>"
                                           class="<?= $mActiveTab === $tabKey ? 'is-current' : '' ?>">
                                            <i class="fa <?= $tab['icon'] ?>"></i> <?= sanitize($tab['label']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <li class="m-accountmenu-sep">
                                    <a href="<?= BASE_URL ?>/logout"><i class="fa fa-sign-out"></i> Logout</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <?php else: ?>
                    <a class="m-action" href="<?= $mAccountUrl ?>" aria-label="Login">
                        <i class="fa fa-user-o"></i>
                    </a>
                    <?php endif; ?>
                    <a class="m-action" href="<?= BASE_URL ?>/wishlist" aria-label="Wishlist">
                        <i class="fa fa-heart-o"></i>
                        <span class="m-badge wishlist-item-count"><?= $wishlistCount ?></span>
                    </a>
                    <?php /* Cart opens a mini-cart panel (like the desktop dropdown)
                             rather than navigating straight to the cart page. */ ?>
                    <div class="m-cart-wrap">
                        <button type="button" class="m-action" id="mCartTrigger" aria-label="Cart" aria-expanded="false">
                            <i class="fa fa-shopping-cart"></i>
                            <span class="m-badge cart-item-count"><?= $cartCount ?></span>
                        </button>

                        <div class="m-minicart" id="mMinicart" hidden>
                            <ul class="m-minicart-list" id="mMinicartList">
                                <li class="m-minicart-empty">Loading...</li>
                            </ul>
                            <div class="m-minicart-total">
                                <span>Subtotal</span>
                                <strong id="mMinicartTotal">--</strong>
                            </div>
                            <?php /* Same markup/classes as the desktop mini-cart so the
                                     buttons look identical (square, uppercase). */ ?>
                            <div class="minicart-button m-minicart-actions">
                                <a href="<?= BASE_URL ?>/shopping-cart" class="li-button li-button-fullwidth li-button-dark">
                                    <span>View Full Cart</span>
                                </a>
                                <a href="<?= BASE_URL ?>/checkout" class="li-button li-button-fullwidth">
                                    <span>Checkout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="m-search">
                <form action="<?= BASE_URL ?>/shop" method="GET">
                    <input type="hidden" name="view" value="grid">
                    <input type="text" name="q" placeholder="Search products, brands and categories">
                    <button type="submit" aria-label="Search"><i class="fa fa-search"></i></button>
                </form>
            </div>
        </div>

        <!-- Slide-in drawer -->
        <div class="m-drawer-overlay" id="mDrawerOverlay" hidden></div>
        <aside class="m-drawer" id="mDrawer" aria-hidden="true" aria-label="Main menu">
            <div class="m-drawer-head">
                <a class="m-drawer-logo" href="<?= BASE_URL ?>/">
                    <img src="<?= BASE_URL ?>/images/menu/logo/2black.png" alt="<?= sanitize(SITE_NAME) ?>">
                </a>
                <button type="button" class="m-drawer-close" id="mDrawerClose" aria-label="Close menu">&times;</button>
            </div>

            <div class="m-drawer-tabs" role="tablist">
                <button type="button" class="is-active" data-panel="menu" role="tab">Menu</button>
                <button type="button" data-panel="cats" role="tab">Categories</button>
            </div>

            <div class="m-drawer-body">
                <!-- ── MENU ── -->
                <div class="m-panel is-active" id="mPanelMenu">
                    <ul class="m-list">
                        <li><a href="<?= BASE_URL ?>/"><i class="fa fa-home"></i> Home</a></li>
                        <li><a href="<?= BASE_URL ?>/shop"><i class="fa fa-shopping-bag"></i> Shop</a></li>
                        <li><a href="<?= BASE_URL ?>/about-us"><i class="fa fa-info-circle"></i> About Us</a></li>
                        <li><a href="<?= BASE_URL ?>/contact"><i class="fa fa-envelope-o"></i> Contact</a></li>
                    </ul>

                    <ul class="m-list m-list-lead">
                        <li>
                            <a href="<?= BASE_URL ?>/contact">
                                <span class="m-lead">Need Help?</span>
                                <i class="fa fa-angle-right m-chev"></i>
                            </a>
                        </li>
                        <li>
                            <a href="<?= $mAccountUrl ?>">
                                <span class="m-lead">My Account</span>
                                <i class="fa fa-angle-right m-chev"></i>
                            </a>
                        </li>
                    </ul>

                    <ul class="m-list">
                        <?php if ($mLoggedIn): ?>
                            <li><a href="<?= BASE_URL ?>/customer-dashboard"><i class="fa fa-user-o"></i> My Dashboard</a></li>
                        <?php else: ?>
                            <li><a href="<?= BASE_URL ?>/login"><i class="fa fa-sign-in"></i> Login / Signup</a></li>
                        <?php endif; ?>
                        <li><a href="<?= BASE_URL ?>/orders"><i class="fa fa-cube"></i> My Orders</a></li>
                        <li><a href="<?= BASE_URL ?>/wishlist"><i class="fa fa-heart-o"></i> Saved Items</a></li>
                        <li><a href="<?= BASE_URL ?>/shopping-cart"><i class="fa fa-shopping-cart"></i> My Cart</a></li>
                        <?php if ($mLoggedIn): ?>
                            <li><a href="<?= BASE_URL ?>/logout"><i class="fa fa-sign-out"></i> Logout</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- ── CATEGORIES ── -->
                <div class="m-panel" id="mPanelCats">
                    <div class="m-panel-title">Our Categories</div>
                    <ul class="m-list">
                        <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="<?= BASE_URL ?>/shop?cat=<?= (int) $cat['id'] ?>">
                                <?php if (!empty($cat['image'])): ?>
                                    <img class="m-cat-img" src="<?= CATEGORY_IMG_URL . '/' . rawurlencode($cat['image']) ?>" alt="">
                                <?php else: ?>
                                    <i class="fa fa-tag"></i>
                                <?php endif; ?>
                                <?= sanitize($cat['name']) ?>
                                <i class="fa fa-angle-right m-chev"></i>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <li>
                            <a href="<?= BASE_URL ?>/shop">
                                <i class="fa fa-th-large"></i> View All Products
                                <i class="fa fa-angle-right m-chev"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <script>
        (function () {
            var drawer  = document.getElementById('mDrawer');
            var overlay = document.getElementById('mDrawerOverlay');
            var openBtn = document.getElementById('mDrawerOpen');
            var closeBtn= document.getElementById('mDrawerClose');
            if (!drawer || !overlay || !openBtn) return;

            function open() {
                overlay.hidden = false;
                // Force a reflow so the browser registers hidden=false before the
                // transition starts, otherwise the fade-in is skipped. A forced
                // reflow is used rather than requestAnimationFrame because rAF
                // does not fire in a background/non-compositing tab, which would
                // leave the drawer stuck closed.
                void overlay.offsetWidth;
                overlay.classList.add('is-open');
                drawer.classList.add('is-open');
                drawer.setAttribute('aria-hidden', 'false');
                openBtn.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
                closeBtn.focus();
            }

            function close() {
                overlay.classList.remove('is-open');
                drawer.classList.remove('is-open');
                drawer.setAttribute('aria-hidden', 'true');
                openBtn.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
                setTimeout(function () {
                    if (!drawer.classList.contains('is-open')) overlay.hidden = true;
                }, 300);
                openBtn.focus();
            }

            openBtn.addEventListener('click', open);
            closeBtn.addEventListener('click', close);
            overlay.addEventListener('click', close);
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && drawer.classList.contains('is-open')) close();
            });

            /* Header dropdowns — mini-cart and account menu. One implementation
               so they share dismissal, and so opening either closes the other
               instead of leaving two panels overlapping on a narrow bar. */
            var dropdowns = [];

            function closeDropdowns(except) {
                dropdowns.forEach(function (d) {
                    if (d.panel === except) return;
                    d.panel.hidden = true;
                    d.btn.setAttribute('aria-expanded', 'false');
                });
            }

            function setupDropdown(btn, panel) {
                if (!btn || !panel) return;
                dropdowns.push({ btn: btn, panel: panel });
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var wasOpen = !panel.hidden;
                    closeDropdowns();
                    if (!wasOpen) {
                        panel.hidden = false;
                        btn.setAttribute('aria-expanded', 'true');
                    }
                });
            }

            setupDropdown(document.getElementById('mCartTrigger'), document.getElementById('mMinicart'));
            setupDropdown(document.getElementById('mAccountTrigger'), document.getElementById('mAccountMenu'));

            // Tapping anywhere else, or pressing Escape, dismisses whichever is open.
            document.addEventListener('click', function (e) {
                var inside = dropdowns.some(function (d) {
                    return d.panel.contains(e.target) || d.btn.contains(e.target);
                });
                if (!inside) closeDropdowns();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeDropdowns();
            });

            // Menu / Categories switcher
            drawer.querySelectorAll('.m-drawer-tabs button').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = this.getAttribute('data-panel');
                    drawer.querySelectorAll('.m-drawer-tabs button').forEach(function (b) {
                        b.classList.toggle('is-active', b === btn);
                    });
                    document.getElementById('mPanelMenu').classList.toggle('is-active', target === 'menu');
                    document.getElementById('mPanelCats').classList.toggle('is-active', target === 'cats');
                    drawer.querySelector('.m-drawer-body').scrollTop = 0;
                });
            });
        })();
        </script>
        <!-- Header Top Marquee -->
        <div class="header-top header-top-marquee">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="header-marquee">
                            <div class="marquee-phase" id="marqueePhase">
                                <div class="marquee-track">
                                    <img src="<?= BASE_URL ?>/images/menu/logo/2black.png" alt="KOBO CHOICE" class="marquee-logo">
                                    <span class="marquee-text">Welcome to KOBO CHOICE STORES... </span>
                                </div>
                            </div>
                            <div class="flash-phase" id="flashPhase">The place of Original Africa Stuffs!!!</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Header Middle -->
        <div class="header-middle pl-sm-0 pr-sm-0 pl-xs-0 pr-xs-0">
            <div class="container">
                <div class="row">
                    <div class="col-lg-3">
                        <div class="logo pb-sm-30 pb-xs-30">
                            <a href="<?= BASE_URL ?>/"><img src="<?= BASE_URL ?>/images/menu/logo/2black.png" alt=""></a>
                        </div>
                    </div>
                    <div class="col-lg-9 pl-0 ml-sm-15 ml-xs-15 header-middle-col">
                        <form action="<?= BASE_URL ?>/shop" method="GET" class="hm-searchbox">
                            <input type="hidden" name="view" value="grid">
                            <select name="cat" class="nice-select select-search-category">
                                <option value="">All</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="q" placeholder="Enter your search key ...">
                            <button class="li-btn" type="submit"><i class="fa fa-search"></i></button>
                        </form>
                        <div class="header-middle-right">
                            <ul class="hm-menu">
                                <li class="hm-login">
                                    <div class="hm-login-trigger ht-setting-trigger">
                                        <i class="fa fa-user-o"></i>
                                        <span><?= is_customer_logged_in() ? 'MY ACCOUNT' : 'LOGIN/REGISTER' ?></span>
                                    </div>
                                    <div class="setting ht-setting">
                                        <ul class="ht-setting-list">
                                            <?php if (is_customer_logged_in()): ?>
                                                <li><a href="<?= BASE_URL ?>/customer-dashboard">Dashboard</a></li>
                                                <li><a href="<?= BASE_URL ?>/orders">My Orders</a></li>
                                                <li><a href="<?= BASE_URL ?>/wishlist">My Wishlist</a></li>
                                                <li><a href="<?= BASE_URL ?>/logout">Logout</a></li>
                                            <?php else: ?>
                                                <li class="active"><a href="<?= BASE_URL ?>/login">Login/Signup</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </li>
                                <li class="hm-wishlist">
                                    <a href="<?= BASE_URL ?>/wishlist">
                                        <span class="cart-item-count wishlist-item-count"><?= $wishlistCount ?></span>
                                        <i class="fa fa-heart-o"></i>
                                    </a>
                                </li>
                                <li class="hm-minicart">
                                    <div class="hm-minicart-trigger">
                                        <span class="item-icon"></span>
                                        <span class="item-text"><?= CURRENCY . number_format(cart_total(), 2) ?>
                                            <span class="cart-item-count"><?= $cartCount ?></span>
                                        </span>
                                    </div>
                                    <span></span>
                                    <div class="minicart" id="headerMinicart">
                                        <ul class="minicart-product-list" id="headerMinicartList">
                                            <li style="text-align:center;padding:20px;">Loading...</li>
                                        </ul>
                                        <p class="minicart-total">SUBTOTAL: <span id="headerMinicartTotal">--</span></p>
                                        <div class="minicart-button">
                                            <a href="<?= BASE_URL ?>/shopping-cart" class="li-button li-button-fullwidth li-button-dark">
                                                <span>View Full Cart</span>
                                            </a>
                                            <a href="<?= BASE_URL ?>/checkout" class="li-button li-button-fullwidth">
                                                <span>Checkout</span>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                                <?php if (is_customer_logged_in()): ?>
                                <!-- Dashboard button beside cart — hidden, kept in MY Account dropdown
                                <li class="hm-dashboard">
                                    <a href="<?= BASE_URL ?>/customer-dashboard" class="hm-dashboard-btn">
                                        <i class="fa fa-tachometer"></i>
                                        <span>DASHBOARD</span>
                                    </a>
                                </li>
                                -->
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Header Bottom -->
        <div class="header-bottom header-sticky d-none d-lg-block d-xl-block">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 hb-menu-row">
                        <div class="hb-menu">
                            <nav>
                                <ul>
                                    <?php foreach ($mainNav as $navUrl => $navLabel): ?>
                                        <li><a href="<?= $navUrl ?>"<?= $navUrl === $currentUrl ? ' class="active"' : '' ?>><?= $navLabel ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php if (is_customer_logged_in()): ?>
                        <div class="hb-user-greeting">
                            Welcome, <strong><?= sanitize(explode(' ', $_SESSION['customer_name'] ?? 'User')[0]) ?></strong>!
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>
<?php endif; ?>
