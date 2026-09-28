<?php
/**
 * Kobo Choice - Homepage View
 * Included by index.php
 */

$db = Database::getInstance();

/*
 * Homepage product feeds.
 *
 * Every carousel on this page is driven from data, never a hand-kept list:
 *   - "Deals" and "Featured" come from the switches on the admin product list
 *   - Fashion / Electronics / Powders / Roots & Bark / Supplements come from
 *     the Section dropdown on the admin product form
 *   - New Arrival, Top Selling and the three Recommended tabs are derived from
 *     the catalogue and from real customer behaviour
 *
 * CAROUSEL_CAP is a safety rail for the carousels rather than a curation
 * limit — the carousel auto-rotates, so a very long strip is never fully seen.
 */
const CAROUSEL_CAP = 20;

// Kept at 4: the Featured block splits its results into two stacked bundles
// sized against the banner column beside it, so a larger number makes that
// block taller than the banner and breaks the alignment.
$featured = $db->fetchAll(
    "SELECT * FROM products WHERE is_featured = 1 AND status = 'active'
     ORDER BY sort_order ASC, created_at DESC LIMIT 4"
);

$deals = $db->fetchAll(
    "SELECT * FROM products WHERE is_deal = 1 AND status = 'active'
     ORDER BY sort_order ASC, created_at DESC LIMIT " . CAROUSEL_CAP
);

/*
 * New Arrival shows the whole active catalogue, shuffled, so the strip looks
 * different on each visit and no product is permanently buried. RAND() is
 * unindexable and sorts the whole table, which is fine at this catalogue's
 * size; if the shop grows past a few thousand products, switch to picking
 * random IDs in PHP and fetching those by primary key.
 */
$newArrivals = $db->fetchAll(
    "SELECT * FROM products WHERE status = 'active' ORDER BY RAND() LIMIT " . CAROUSEL_CAP
);

/*
 * Section-backed strips. One query, grouped in PHP, rather than five near
 * identical round trips.
 */
$sectionProducts = array_fill_keys(array_keys(product_sections()), []);
$sectionRows = $db->fetchAll(
    "SELECT * FROM products
     WHERE section <> '' AND status = 'active'
     ORDER BY sort_order ASC, created_at DESC"
);
foreach ($sectionRows as $row) {
    if (isset($sectionProducts[$row['section']]) && count($sectionProducts[$row['section']]) < CAROUSEL_CAP) {
        $sectionProducts[$row['section']][] = $row;
    }
}

$fashion     = $sectionProducts['fashion'];
$electronics = $sectionProducts['electronics'];
$powders     = $sectionProducts['powders'];
$rootsBark   = $sectionProducts['roots_bark'];
$supplements = $sectionProducts['supplements'];

/*
 * Top Selling — actual units shipped, not a manual flag. Only orders that
 * reached a paid state count, so abandoned and cancelled baskets can't inflate
 * the ranking. Joined back to products so a deleted or drafted product drops
 * out on its own.
 */
$topSelling = $db->fetchAll(
    "SELECT p.*, SUM(oi.quantity) AS units_sold
     FROM order_items oi
     JOIN orders o   ON o.id = oi.order_id
     JOIN products p ON p.id = oi.product_id
     WHERE p.status = 'active'
       AND o.status IN ('paid','processing','shipped','delivered','completed')
     GROUP BY p.id
     ORDER BY units_sold DESC, p.created_at DESC
     LIMIT " . CAROUSEL_CAP
);

// Before the shop has sales history there is nothing to rank, so fall back to
// the newest stock rather than showing an empty best-sellers strip.
if (empty($topSelling)) {
    $topSelling = $db->fetchAll(
        "SELECT * FROM products WHERE status = 'active'
         ORDER BY created_at DESC LIMIT " . CAROUSEL_CAP
    );
}

/*
 * Recently Viewed — this visitor's own history, newest first. The IDs live in
 * the session (see record_product_view()), and FIELD() replays that order back
 * out of the database, which returns rows in its own order otherwise.
 */
$recentlyViewed = [];
$recentIds = recently_viewed_ids(CAROUSEL_CAP);
if (!empty($recentIds)) {
    $placeholders = implode(',', array_fill(0, count($recentIds), '?'));
    $recentlyViewed = $db->fetchAll(
        "SELECT * FROM products
         WHERE id IN ($placeholders) AND status = 'active'
         ORDER BY FIELD(id, $placeholders)",
        array_merge($recentIds, $recentIds)
    );
}

/*
 * Customer Favorites — what customers themselves signalled they want, ranked
 * by wishlist saves first and review rating second. Both are earned, so this
 * strip needs no admin upkeep.
 */
$customerFavorites = $db->fetchAll(
    "SELECT p.*,
            COUNT(DISTINCT w.id)                       AS saves,
            COALESCE(AVG(r.rating), 0)                 AS avg_rating
     FROM products p
     LEFT JOIN wishlists w ON w.product_id = p.id
     LEFT JOIN reviews  r  ON r.product_id = p.id AND r.is_approved = 1
     WHERE p.status = 'active'
     GROUP BY p.id
     HAVING saves > 0 OR avg_rating > 0
     ORDER BY saves DESC, avg_rating DESC, p.created_at DESC
     LIMIT " . CAROUSEL_CAP
);

// No wishlists or reviews yet — show what is selling instead of nothing.
if (empty($customerFavorites)) {
    $customerFavorites = $topSelling;
}

/*
 * You May Also Like — same categories as the products this visitor has been
 * looking at, minus the ones they have already seen. That is the cheapest
 * recommendation that is actually personal; with no history to work from it
 * degrades to a shuffle of the catalogue.
 */
$youMayAlsoLike = [];
if (!empty($recentIds)) {
    $idPlaceholders = implode(',', array_fill(0, count($recentIds), '?'));
    $youMayAlsoLike = $db->fetchAll(
        "SELECT * FROM products
         WHERE status = 'active'
           AND id NOT IN ($idPlaceholders)
           AND category_id IN (
               SELECT category_id FROM products
               WHERE id IN ($idPlaceholders) AND category_id IS NOT NULL
           )
         ORDER BY RAND() LIMIT " . CAROUSEL_CAP,
        array_merge($recentIds, $recentIds)
    );
}
if (empty($youMayAlsoLike)) {
    $youMayAlsoLike = $db->fetchAll(
        "SELECT * FROM products WHERE status = 'active' ORDER BY RAND() LIMIT " . CAROUSEL_CAP
    );
}

$categories = $db->fetchAll("SELECT * FROM categories WHERE show_in_menu = 1 ORDER BY sort_order ASC");

function product_card($p, $show_countdown = false) {
    $img = !empty($p['image']) ? PRODUCT_IMG_URL.'/'.$p['image'] : BASE_URL.'/images/product/large-size/1.jpg';
    $link = BASE_URL.'/single-product?slug='.$p['slug'];
    $html = '<div class="col-lg-12"><div class="single-product-wrap">';
    $html .= '<div class="product-image"><a href="'.$link.'"><img src="'.$img.'" alt="'.$p['name'].'"></a>';
    if ($p['is_new']) $html .= '<span class="sticker">New</span>';
    if ($p['old_price']) $html .= '<span class="sticker sticker-sale">Sale</span>';
    if ($show_countdown) {
        $deadline = date('Y/m/d', strtotime('tomorrow midnight'));
        $html .= '<div class="product-countdown-wrap"><div class="li-countdown" data-countdown="'.$deadline.'"></div></div>';
    }
    $html .= '</div>';
    $html .= '<div class="product_desc"><div class="product_desc_info">';
    $html .= '<h4><a class="product_name" href="'.$link.'">'.sanitize($p['name']).'</a></h4>';
    $html .= '<div class="price-box">';
    $html .= '<span class="new-price">'.format_price((float)$p['price']).'</span>';
    if ($p['old_price']) $html .= '<span class="old-price">'.format_price((float)$p['old_price']).'</span>';
    $html .= '</div></div>';
    $html .= '<div class="add-actions"><ul class="add-actions-link">';
    $html .= '<li class="add-cart active"><a href="#" class="add-to-cart-btn" data-id="'.$p['id'].'">Add to cart</a></li>';
    $html .= '<li><a class="wishlist-btn links-details" data-id="'.$p['id'].'" href="#"><i class="fa fa-heart-o"></i></a></li>';
    $html .= '</ul></div></div></div></div>';
    return $html;
}
?>

<?php /* Homepage-only spacing overrides. Kept here rather than in style.css so
         inner pages keep the theme's original rhythm. */ ?>
<style>
/* Hero sits flush against the nav bar (theme adds 20px below .header-bottom
   and 8px above the slider). */
.header-bottom { margin-bottom: 0 !important; }
.slider-with-banner { padding-top: 0 !important; margin-top: 0 !important; }

/* Featured Products + the promo banner strip above it — the theme leaves a
   48px margin and generous padding around these, which reads as dead space.
   NOTE: style.css has `header ~ *:not(.footer) { padding: 8px 0 !important }`,
   which is *more specific* than a single class — so these selectors double the
   class name to outrank it. */
.featured-pro-with-banner { margin-top: 0 !important; }
/* Small breathing space above the section (the theme's original 48px margin
   plus 8px padding was far too wide). */
.featured-pro-with-banner.featured-pro-with-banner { padding-top: 12px !important; }
.featured-pro-with-banner .featured-product.featured-product { padding-top: 0 !important; }

/* ── Featured Products cards ─────────────────────────────────────────
   The theme leaves these borderless (1px transparent) with only a faint grey
   shadow on hover, so the four boxes read as one undivided block. Give them a
   permanent outline, more room, and a hover that picks up the section colour. */
.featured-pro-with-banner .featured-pro-wrapper {
    border: 1px solid #e4e7ee !important;
    border-radius: 10px;
    background: #fff;
    transition: border-color .25s ease, box-shadow .25s ease, transform .25s ease;
}
.featured-pro-with-banner .featured-pro-wrapper:hover {
    border-color: #ED017F !important;
    box-shadow: 0 10px 26px rgba(237, 1, 127, .20) !important;
    transform: translateY(-4px);
}
/* Bigger boxes — more breathing room around the image and the copy. */
.featured-pro-with-banner .featured-pro-wrapper .product-img { padding: 24px 18px; }
.featured-pro-with-banner .featured-pro-wrapper .featured-pro-content { padding: 24px 22px; }

/* Even vertical rhythm: the theme only spaced alternate cards, which left the
   pairs unevenly gapped. */
.featured-pro-with-banner .featured-pro-wrapper { margin-bottom: 20px !important; }
.featured-pro-with-banner .featured-product-bundle > .featured-pro-wrapper:last-child {
    margin-bottom: 0 !important;
}
/* Matching gap between the two columns of the 2×2 grid. */
.featured-pro-with-banner .featured-product-bundle { padding: 0 10px; }
.featured-pro-with-banner .featured-product-active .owl-stage { padding-bottom: 4px; }

/* Clamp the product name to two lines so every card is the same height —
   otherwise a long name makes one box taller and the 2×2 grid looks lopsided. */
.featured-pro-with-banner .featured-pro-content h4 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.7em;
    margin-bottom: 6px;
}

@media (max-width: 575px) {
    .featured-pro-with-banner .featured-pro-wrapper .product-img { padding: 18px 12px; }
    .featured-pro-with-banner .featured-pro-wrapper .featured-pro-content { padding: 18px 14px; }
}

/* ── Section header bar (Jumia/Konga style) ──────────────────────────
   Any section carrying .has-section-bar drops its container's top padding. The
   bar's negative side margins bleed it past the container's 15px padding so it
   spans the full card width.

   The container must NOT use overflow:hidden — that also clips the product
   cards' hover "Add to cart" panel, which sits outside the card bounds. The
   bar rounds its own top corners instead. */
.has-section-bar > .container {
    padding-top: 0 !important;
}
.featured-pro-with-banner > .container { padding-bottom: 0 !important; }

/* Each section sets --bar; everything inside the bar derives from it, so an
   active pill automatically picks up the right colour without extra rules. */
.section-bar {
    --bar: #F48201;
    display: flex; align-items: center; justify-content: space-between; gap: 14px;
    background: var(--bar);
    margin: 0 -15px 16px -15px;
    padding: 13px 20px;
    flex-wrap: wrap;
    /* Matches the card's own top radius, so no container clipping is needed. */
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}
.sb-orange .section-bar { --bar: #F06405; }
.sb-green  .section-bar { --bar: #178201; }
.sb-pink   .section-bar { --bar: #ED017F; }
.sb-purple .section-bar { --bar: #6A1B9A; }
.sb-dark   .section-bar { --bar: #0D1B2A; }
.section-bar h2 {
    margin: 0; font-size: 20px; font-weight: 600; line-height: 1.2;
    color: #fff; letter-spacing: -.2px;
}
.section-bar h2 > span { color: #fff; }
.section-bar-link {
    display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
    color: #fff; font-size: 14px; text-decoration: none; opacity: .95;
}
.section-bar-link:hover { color: #fff; opacity: 1; text-decoration: underline; }

/* Sub-navigation (category links / tab switchers) living inside the bar */
.section-bar-tabs {
    display: flex; align-items: center; gap: 4px;
    list-style: none; margin: 0; padding: 0; flex-wrap: wrap;
}
.section-bar-tabs > li { margin: 0; padding: 0; float: none; border: 0; }
.section-bar-tabs > li > a {
    display: block; padding: 5px 12px; border-radius: 20px;
    color: #fff; font-size: 13.5px; text-decoration: none;
    opacity: .9; transition: background .2s, opacity .2s;
    background: transparent; border: 0;
}
.section-bar-tabs > li > a > span { color: inherit; }
.section-bar-tabs > li > a:hover { opacity: 1; background: rgba(255,255,255,.18); color: #fff; }
.section-bar-tabs > li > a.active,
.section-bar-tabs > li.active > a { background: #fff; color: var(--bar); opacity: 1; }
.section-bar-tabs > li > a.active > span,
.section-bar-tabs > li.active > a > span { color: var(--bar); }

/* Placeholder for a tab whose flag has not been ticked on any product yet */
.section-empty {
    margin: 0; padding: 34px 15px; text-align: center;
    color: #7b8794; font-size: 14.5px;
}

/* Countdown timer sitting in the Top Deals bar */
.section-bar .deals-countdown { display: flex; gap: 8px; flex-wrap: wrap; }
.section-bar .deals-countdown .countdown-section {
    background: rgba(255,255,255,.16); border-radius: 6px;
    padding: 4px 10px; text-align: center; min-width: 50px;
    color: #fff; font-size: 10.5px; letter-spacing: .4px; text-transform: uppercase;
}
.section-bar .deals-countdown .countdown-number {
    display: block; color: #fff; font-size: 16px; font-weight: 700; line-height: 1.25;
}

@media (max-width: 575px) {
    .section-bar { padding: 11px 15px; gap: 8px; }
    .section-bar h2 { font-size: 17px; }
    .section-bar-link { font-size: 13px; }
    .section-bar-tabs > li > a { padding: 4px 9px; font-size: 12.5px; }
}

/* ── Promo divider strips ────────────────────────────────────────────
   Same flush, full-bleed treatment as the section bars: the strip fills its
   white card edge to edge instead of floating inside it with a white border.
   The card's padding (15px) and the strip's own 20px top margin are cleared,
   and the column padding is dropped so it reaches the card's side edges. */
.static-top-wrap > .container {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}
.static-top-wrap .col-lg-12 { padding-left: 0 !important; padding-right: 0 !important; }
.static-top-wrap .static-top-content {
    margin-top: 0 !important;
    /* Rounds itself rather than relying on the container clipping it. */
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}


/* The divider above "Shop by Popular Category" carries margin-bottom:-30px,
   which pulled the "New Arrival / Fashion / Electronics" tabs flush against
   the orange strip. Give them room to breathe. */
.li-product-tab { padding-top: 18px; }

/* ── Category tabs as pills ──────────────────────────────────────────
   Same pill language as the section-bar tabs, adapted for a white card:
   the active pill is filled orange instead of white-on-orange. */
.pill-tabs.pill-tabs {
    display: flex; flex-wrap: wrap; gap: 8px;
    border: 0; margin: 0 0 4px; padding: 0; float: none;
}
.pill-tabs > li { float: none; margin: 0; padding: 0; border: 0; }
.pill-tabs > li > a {
    display: block; padding: 8px 18px; border-radius: 22px;
    background: #f1f3f6; border: 1px solid transparent;
    color: #4b5563; font-size: 14px; font-weight: 500;
    text-decoration: none; transition: all .18s ease;
}
.pill-tabs > li > a::before,
.pill-tabs > li > a::after { display: none !important; }
.pill-tabs > li > a > span { color: inherit; font-size: inherit; font-weight: inherit; }
.pill-tabs > li > a:hover { background: #ffe9d2; color: #C46600; }
.pill-tabs > li > a:hover > span { color: #C46600; }
.pill-tabs > li > a.active,
.pill-tabs > li.active > a {
    background: #F48201; border-color: #F48201; color: #fff;
    box-shadow: 0 3px 10px rgba(244,130,1,.28);
}
.pill-tabs > li > a.active > span,
.pill-tabs > li.active > a > span { color: #fff; }
@media (max-width: 575px) {
    .pill-tabs > li > a { padding: 7px 14px; font-size: 13px; }
}

/* ── Flash-sales call to action ──────────────────────────────────────
   Replaces the plain text divider. Bleeds to the card edges like the other
   strips, so it keeps the same rounded top corners. */
.home-cta {
    display: flex; align-items: center; justify-content: space-between;
    gap: 22px; flex-wrap: wrap;
    margin: 0 -15px;
    padding: 40px 30px;
    /* Deep navy rather than orange: it separates the CTA from the coloured
       section bars around it and lets the brand orange own the button. */
    background: linear-gradient(120deg, #0D1B2A 0%, #112240 52%, #1B3A6B 100%);
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    position: relative; overflow: hidden;
}
/* Soft light bloom on the right, purely decorative */
.home-cta::after {
    content: ''; position: absolute; right: -60px; top: -70px;
    width: 260px; height: 260px; border-radius: 50%;
    background: rgba(255,255,255,.13); pointer-events: none;
}
.home-cta-text { position: relative; z-index: 1; }
.home-cta-eyebrow {
    display: inline-flex; align-items: center; gap: 7px;
    background: rgba(244,130,1,.95); color: #fff;
    font-size: 12px; font-weight: 600; letter-spacing: .6px; text-transform: uppercase;
    padding: 5px 12px; border-radius: 20px; margin-bottom: 10px;
}
.home-cta h2 {
    margin: 0 0 6px; color: #fff; font-size: 27px; font-weight: 700;
    line-height: 1.25; letter-spacing: -.3px;
}
.home-cta h2 > span { color: #FFA94D; text-decoration: underline; text-underline-offset: 4px; }
.home-cta p { margin: 0; color: rgba(255,255,255,.85); font-size: 15px; line-height: 1.6; }
.home-cta-btn {
    position: relative; z-index: 1; flex-shrink: 0;
    display: inline-flex; align-items: center; gap: 9px; white-space: nowrap;
    background: #F48201; color: #fff;
    padding: 13px 30px; border-radius: 30px;
    font-size: 15px; font-weight: 600; text-decoration: none;
    box-shadow: 0 6px 18px rgba(244,130,1,.38);
    transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
}
.home-cta-btn:hover {
    background: #ff9528; color: #fff; transform: translateY(-2px);
    box-shadow: 0 9px 24px rgba(244,130,1,.5);
}
/* Sit close to the banner below it — the theme's 8px wrapper padding plus a
   20px inline margin left far too much dead space under the CTA. */
.home-cta-wrap.home-cta-wrap { padding-bottom: 0 !important; margin-bottom: 10px !important; }

@media (max-width: 767px) {
    .home-cta { padding: 30px 20px; gap: 16px; }
    .home-cta h2 { font-size: 21px; }
    .home-cta p { font-size: 14px; }
    .home-cta-btn { width: 100%; justify-content: center; }
}

/* ── Rounded top corners on every section card ───────────────────────
   Matches the selector style.css uses for these white cards so it reliably
   overrides the flat 2px radius. Sections whose header bar or promo strip
   runs edge-to-edge already clip via overflow:hidden, so the bar follows
   the curve instead of squaring it off. */
header ~ *:not(.footer) .container {
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}

/* Promo banner pair directly above Featured Products */
.li-banner-2 { padding-top: 0 !important; }
.li-laptop-product-2.li-laptop-product-2 { padding-top: 0 !important; padding-bottom: 8px !important; }

/* The slide strip directly above Featured Products — close the seam between them */
.li-static-home.li-static-home { padding-top: 0 !important; padding-bottom: 0 !important; }
</style>

<!-- Begin Slider With Category Menu Area -->
<div class="slider-with-banner">
    <div class="container">
        <div class="row">
            <!-- Begin Category Menu Area -->
            <div class="col-lg-3">
                <div class="category-menu">
                    <div class="category-heading">
                        <h2 class="categories-toggle"><span>categories</span></h2>
                    </div>
                    <div id="cate-toggle" class="category-menu-list">
                        <ul>
                            <?php foreach ($categories as $i => $cat): ?>
                            <li class="<?= $i >= 8 ? 'rx-child' : '' ?>"><a href="<?= BASE_URL ?>/shop?cat=<?= (int) $cat['id'] ?>"><?= sanitize($cat['name']) ?></a></li>
                            <?php endforeach; ?>
                            <?php if (count($categories) > 8): ?>
                            <li class="rx-parent">
                                <a class="rx-default">More Categories</a>
                                <a class="rx-show">Less Categories</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Category Menu Area End Here -->

            <!-- Begin Slider Area -->
            <div class="col-lg-9">
                <?php /* The theme's bg-4/5/6 classes carried the slide's sizing as well as
                         its image. Since the image now comes from the CMS, .cms-slide
                         supplies that same sizing — without it the slide collapses to
                         zero height and nothing shows. */ ?>
                <style>
                .cms-slide {
                    background-repeat: no-repeat;
                    background-position: center center;
                    background-size: cover;
                    min-height: 475px;
                    width: 100%;
                }
                /* Mirrors the height ladder responsive.css defined for the theme's
                   .bg-4/5/6 classes. Those classes are gone now that the image is
                   CMS-driven, so without this the slide stayed tall on phones and
                   `cover` cropped the banner heavily. */
                @media (max-width: 991px) { .cms-slide { min-height: 240px; } }
                @media (max-width: 575px) { .cms-slide { min-height: 200px; } }
                @media (max-width: 479px) { .cms-slide { min-height: 180px; } }
                @media (max-width: 360px) { .cms-slide { min-height: 150px; } }
                </style>
                <div class="slider-area pt-sm-30 pt-xs-30">
                    <div class="slider-active owl-carousel">
                        <?php foreach (cms_banners('slider') as $i => $slide): ?>
                        <div class="single-slide cms-slide align-center-left animation-style-0<?= ($i % 2) + 1 ?>"
                             style="background-image:url('<?= sanitize(cms_image_url($slide['image'])) ?>');">
                            <div class="slider-progress"></div>
                            <div class="slider-content">
                                <?php if (!empty($slide['subheading'])): ?>
                                    <h5><?= $slide['subheading'] ?></h5>
                                <?php endif; ?>
                                <?php if (!empty($slide['heading'])): ?>
                                    <h2><?= sanitize($slide['heading']) ?></h2>
                                <?php endif; ?>
                                <?php if (!empty($slide['price_text'])): ?>
                                    <h3><?= $slide['price_text'] ?></h3>
                                <?php endif; ?>
                                <div class="default-btn slide-btn">
                                    <a class="links" href="<?= sanitize(cms_link_url($slide['link_url'])) ?>">
                                        <?= sanitize($slide['button_text'] ?: 'Order Now') ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <!-- Slider Area End Here -->
        </div>
    </div>
</div>
<!-- Slider With Category Menu Area End Here -->

<!-- Begin Ads Tile Row (managed in Admin → CMS) -->
<?php $adsTiles = cms_banners('ads_grid'); ?>
<?php if (!empty($adsTiles)): ?>
<style>
/* Tile row: a fixed 8-across grid on desktop, a swipeable strip on mobile.
   Using overflow scroll rather than a JS carousel keeps it dependency-free
   and lets the number of tiles change from the CMS without re-init. */
.ads-tile-row { padding: 22px 0 4px; }

/* The strip scrolls horizontally at every breakpoint so the arrows behave
   consistently; on wide screens all eight tiles still fit without scrolling. */
.ads-tile-wrap { position: relative; }
.ads-tile-grid {
    display: flex; gap: 12px; overflow-x: auto; scroll-behavior: smooth;
    scroll-snap-type: x proximity; -webkit-overflow-scrolling: touch;
    padding-bottom: 6px; scrollbar-width: none;
}
.ads-tile-grid::-webkit-scrollbar { display: none; }

.ads-tile {
    flex: 0 0 calc((100% - (7 * 12px)) / 8);   /* 8 across, minus the gaps */
    scroll-snap-align: start;
    text-align: center; text-decoration: none; color: #1f2430;
}
.ads-tile-img {
    display: block; width: 100%; aspect-ratio: 1 / 1; object-fit: cover;
    border-radius: 8px; background: #f4f5f7;
    transition: transform .25s ease, box-shadow .25s ease;
}
.ads-tile:hover .ads-tile-img { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.12); }
.ads-tile-caption {
    display: block; margin-top: 7px; font-size: 13px; font-weight: 400;
    color: #1f2430; line-height: 1.35;
}
.ads-tile:hover .ads-tile-caption { color: #F48201; }

/* ── Scroll arrows ── */
.ads-arrow {
    position: absolute; top: calc(50% - 16px); transform: translateY(-50%);
    width: 38px; height: 38px; border-radius: 50%;
    background: #fff; border: 1px solid #e2e5ea; color: #1f2430;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; cursor: pointer; z-index: 5;
    box-shadow: 0 3px 12px rgba(0,0,0,.14);
    transition: background .2s, color .2s, opacity .2s;
}
.ads-arrow:hover { background: #F48201; border-color: #F48201; color: #fff; }
.ads-arrow.prev { left: -14px; }
.ads-arrow.next { right: -14px; }
/* Stay visible at the ends — just dimmed — so the control is always
   discoverable rather than vanishing when there is nothing left to scroll. */
.ads-arrow[disabled] { opacity: .38; pointer-events: none; box-shadow: none; }

@media (max-width: 1199px) { .ads-tile { flex-basis: calc((100% - (5 * 12px)) / 6); } }
@media (max-width: 991px)  { .ads-tile { flex-basis: calc((100% - (3 * 12px)) / 4); } }
@media (max-width: 767px)  {
    /* Show ~3.5 tiles so the row reads as scrollable even on a 344px screen.
       The gap tightens to 8px here to buy back horizontal room. */
    .ads-tile-grid { gap: 8px; }
    .ads-tile { flex-basis: calc((100% - 24px) / 3.5); }
    .ads-tile-caption { font-size: 11px; margin-top: 5px; line-height: 1.3; }
    /* Tuck the arrows inside the strip so they cannot overflow a narrow screen. */
    .ads-arrow { width: 32px; height: 32px; font-size: 15px; }
    .ads-arrow.prev { left: 2px; }
    .ads-arrow.next { right: 2px; }
}
</style>
<div class="ads-tile-row">
    <div class="container">
        <div class="ads-tile-wrap">
            <button type="button" class="ads-arrow prev" id="adsPrev" aria-label="Scroll left" disabled>
                <i class="fa fa-angle-left"></i>
            </button>

            <div class="ads-tile-grid" id="adsTileGrid">
                <?php foreach ($adsTiles as $tile): ?>
                <a class="ads-tile" href="<?= sanitize(cms_link_url($tile['link_url'])) ?>">
                    <img class="ads-tile-img"
                         src="<?= sanitize(cms_image_url($tile['image'])) ?>"
                         alt="<?= sanitize($tile['caption'] ?? '') ?>">
                    <?php if (!empty($tile['caption'])): ?>
                        <span class="ads-tile-caption"><?= sanitize($tile['caption']) ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <button type="button" class="ads-arrow next" id="adsNext" aria-label="Scroll right">
                <i class="fa fa-angle-right"></i>
            </button>
        </div>
    </div>
</div>
<script>
(function () {
    var grid = document.getElementById('adsTileGrid');
    var prev = document.getElementById('adsPrev');
    var next = document.getElementById('adsNext');
    if (!grid || !prev || !next) return;

    function step() {
        var tile = grid.querySelector('.ads-tile');
        // Scroll by roughly one screenful of tiles, never less than one tile.
        return tile ? Math.max(tile.offsetWidth + 12, grid.clientWidth * 0.8) : 240;
    }

    // Hide an arrow once there is nothing further to scroll in that direction.
    function sync() {
        var max = grid.scrollWidth - grid.clientWidth;
        prev.disabled = grid.scrollLeft <= 2;
        next.disabled = grid.scrollLeft >= max - 2;
    }

    prev.addEventListener('click', function () { grid.scrollBy({ left: -step(), behavior: 'smooth' }); });
    next.addEventListener('click', function () { grid.scrollBy({ left:  step(), behavior: 'smooth' }); });
    grid.addEventListener('scroll', sync);
    window.addEventListener('resize', sync);

    // The tile images have no intrinsic width until they load, so at first
    // paint scrollWidth === clientWidth and "next" would be disabled with no
    // way to re-enable it. Re-sync as the strip actually grows.
    if (window.ResizeObserver) {
        new ResizeObserver(sync).observe(grid);
    }
    grid.querySelectorAll('img').forEach(function (img) {
        if (!img.complete) img.addEventListener('load', sync, { once: true });
    });
    window.addEventListener('load', sync);

    sync();
})();
</script>
<?php endif; ?>
<!-- Ads Tile Row End Here -->

<!-- DIVIDER 1 -->
<div class="static-top-wrap" style="margin-bottom: -30px;">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="static-top-content mt-sm-30">
                    HOT!!! Get 20% off on all today's deal with coupon code "KoboStoresSaleoff"
                    <span>- 24 Hours Time Offer!</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Deals of the Day Product Section -->
<?php /* Nothing ticked in admin means no section at all — an empty carousel
         under a live countdown reads as a broken page. */ ?>
<?php if (!empty($deals)): ?>
<section class="product-area li-laptop-product Special-product pt-60 pb-10">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="li-section-title deals-title">
                    <h2><span>Top Deals of the Day</span></h2>
                    <?php /* data-countdown-daily re-arms the clock at midnight instead of
                             letting it sit at zero. The products themselves are not
                             cleared — only the admin switch takes them out. */ ?>
                    <div class="deals-countdown li-countdown"
                         data-countdown="<?= date('Y/m/d', strtotime('tomorrow midnight')) ?>"
                         data-countdown-daily="1"></div>
                </div>
                <div class="row">
                    <div class="special-product-active owl-carousel">
                        <?php foreach ($deals as $p): ?>
                            <?= product_card($p) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- DIVIDER 2 -->
<div class="static-top-wrap" style="margin-bottom: -30px;">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="static-top-content mt-sm-30">
                    Shop by Popular <span>Category</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Begin Product Area -->
<div class="product-area pt-10 pb-50">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <?php
                /* New Arrival is the whole shuffled catalogue; the other two are
                   whatever carries that Section on the admin product form. */
                $popularTabs = [
                    'pc-new-arrival' => ['label' => 'New Arrival', 'products' => $newArrivals],
                    'pc-fashion'     => ['label' => 'Fashion',     'products' => $fashion],
                    'pc-electronics' => ['label' => 'Electronics', 'products' => $electronics],
                ];
                ?>
                <div class="li-product-tab">
                    <ul class="nav li-product-menu pill-tabs" role="tablist">
                        <?php $first = true; ?>
                        <?php foreach ($popularTabs as $tabId => $tab): ?>
                            <li>
                                <a<?= $first ? ' class="active"' : '' ?> data-toggle="tab" href="#<?= $tabId ?>" role="tab">
                                    <span><?= sanitize($tab['label']) ?></span>
                                </a>
                            </li>
                            <?php $first = false; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="tab-content">
            <?php $first = true; ?>
            <?php foreach ($popularTabs as $tabId => $tab): ?>
            <div id="<?= $tabId ?>" class="tab-pane<?= $first ? ' active show' : '' ?>" role="tabpanel">
                <div class="row">
                    <?php if (!empty($tab['products'])): ?>
                        <div class="product-active owl-carousel">
                            <?php foreach ($tab['products'] as $p): ?>
                                <?= product_card($p) ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="col-lg-12">
                            <p class="section-empty">No products in this category yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php $first = false; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<!-- Product Area End Here -->

<!-- Begin Li's Laptops Product | Home V2 Area -->
<section class="product-area li-laptop-product li-laptop-product-2 pb-45">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="li-banner-2 pt-15">
                    <div class="row">
                        <?php foreach (cms_banners('promo_pair') as $i => $promo): ?>
                        <div class="col-lg-6 col-md-6">
                            <div class="single-banner <?= $i > 0 ? 'pt-xs-30' : '' ?>">
                                <a href="<?= sanitize(cms_link_url($promo['link_url'])) ?>">
                                    <img src="<?= sanitize(cms_image_url($promo['image'])) ?>"
                                         alt="<?= sanitize($promo['caption'] ?? '') ?>">
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Health & Wellness Herbs product Section -->
<section class="product-area li-laptop-product has-section-bar sb-green pt-60 pb-45">
    <div class="container">
        <div class="section-bar">
            <h2>Health &amp; Wellness Herbs</h2>
            <?php /* `nav` is the ancestor Bootstrap's tab JS walks up to in order to
                     clear the previously active pill — without it the panes switch but
                     the old pill stays highlighted. Pill styling stays on
                     .section-bar-tabs, so the bar looks exactly as before. */ ?>
            <ul class="nav section-bar-tabs" role="tablist">
                <li><a class="active" data-toggle="tab" href="#hw-powders" role="tab">Powders</a></li>
                <li><a data-toggle="tab" href="#hw-roots-bark" role="tab">Roots &amp; Bark</a></li>
                <li><a data-toggle="tab" href="#hw-supplements" role="tab">Supplements</a></li>
            </ul>
        </div>
        <?php
        /* Each pill maps to the matching product flag set on the admin product form
           (Powder / Roots & Bark / Supplement), so what an upload is ticked as is
           what decides the tab it lands in. */
        $herbTabs = [
            'hw-powders'     => $powders,
            'hw-roots-bark'  => $rootsBark,
            'hw-supplements' => $supplements,
        ];
        $herbFirst = true;
        ?>
        <div class="tab-content">
            <?php foreach ($herbTabs as $herbId => $herbProducts): ?>
            <div id="<?= $herbId ?>" class="tab-pane<?= $herbFirst ? ' active show' : '' ?>" role="tabpanel">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="row">
                            <?php if (!empty($herbProducts)): ?>
                                <div class="product-active owl-carousel">
                                    <?php foreach ($herbProducts as $p): ?>
                                        <?= product_card($p) ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <?php /* An empty owl carousel collapses to nothing and reads as a
                                         broken tab, so say plainly that the tab is unstocked. */ ?>
                                <div class="col-lg-12">
                                    <p class="section-empty">No products in this category yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php $herbFirst = false; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Flash Sales call to action -->
<div class="static-top-wrap home-cta-wrap">
    <div class="container">
        <div class="home-cta">
            <div class="home-cta-text">
                <span class="home-cta-eyebrow"><i class="fa fa-bolt"></i> Limited time offer</span>
                <h2>Flash Sales on <span>KoboChoice Stores</span></h2>
                <p>Featured products at their lowest prices of the season &mdash; while stocks last.</p>
            </div>
            <a class="home-cta-btn" href="<?= BASE_URL ?>/shop">
                Shop Flash Sales <i class="fa fa-angle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Begin Li's Static Home Area -->
<?php foreach (cms_banners('static_home') as $sh): ?>
<div class="li-static-home">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <?php /* Background comes from the CMS record, overriding the theme's
                         hardcoded images/bg-banner/1.jpg so it can be swapped in admin. */ ?>
                <div class="li-static-home-image"
                     style="background-image:url('<?= sanitize(cms_image_url($sh['image'])) ?>');"></div>
                <div class="li-static-home-content">
                    <?php if (!empty($sh['subheading'])): ?>
                        <p><?= $sh['subheading'] ?></p>
                    <?php endif; ?>
                    <?php if (!empty($sh['heading'])): ?>
                        <h2><?= sanitize($sh['heading']) ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($sh['caption'])): ?>
                        <h2><?= sanitize($sh['caption']) ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($sh['price_text'])): ?>
                        <p class="schedule"><?= $sh['price_text'] ?></p>
                    <?php endif; ?>
                    <div class="default-btn">
                        <a href="<?= sanitize(cms_link_url($sh['link_url'])) ?>" class="links">
                            <?= sanitize($sh['button_text'] ?: 'Order Now') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Begin Featured Product With Banner Area -->
<div class="featured-pro-with-banner has-section-bar sb-pink mt-sm-5 pb-sm-10 mt-xs-5 pb-xs-10">
    <div class="container">
        <?php /* Full-bleed section header sitting flush against the top edge of the
                 white card — the storefront convention used by Jumia/Konga. */ ?>
        <div class="section-bar">
            <h2>Featured Products</h2>
            <a class="section-bar-link" href="<?= BASE_URL ?>/shop">See All <i class="fa fa-angle-right"></i></a>
        </div>
        <div class="row">
            <div class="col-lg-3 text-center">
                <?php foreach (cms_banners('featured') as $fb): ?>
                <div class="single-banner featured-banner">
                    <a href="<?= sanitize(cms_link_url($fb['link_url'])) ?>">
                        <img src="<?= sanitize(cms_image_url($fb['image'])) ?>"
                             alt="<?= sanitize($fb['caption'] ?? '') ?>">
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="col-lg-9">
                <div class="featured-product">
                    <div class="row">
                        <div class="featured-product-active owl-carousel">
                            <div class="featured-product-bundle">
                                <?php if (!empty($featured)): ?>
                                    <?php $half = ceil(count($featured) / 2); $i = 0; ?>
                                    <?php foreach ($featured as $p): ?>
                                        <?php if ($i > 0 && $i % $half === 0): ?>
                            </div>
                            <div class="featured-product-bundle">
                                        <?php endif; ?>
                                <div class="featured-pro-wrapper <?= ($i % $half === 0) ? '' : 'mb-30 mb-sm-25' ?>">
                                    <div class="product-img">
                                        <a href="<?= BASE_URL ?>/single-product?slug=<?= $p['slug'] ?>">
                                            <img alt="" src="<?= !empty($p['image']) ? PRODUCT_IMG_URL.'/'.$p['image'] : BASE_URL.'/images/featured-product/1.jpg' ?>">
                                        </a>
                                    </div>
                                    <div class="featured-pro-content">
                                        <div class="rating-box">
                                            <ul class="rating">
                                                <li><i class="fa fa-star-o"></i></li>
                                                <li><i class="fa fa-star-o"></i></li>
                                                <li><i class="fa fa-star-o"></i></li>
                                                <li class="no-star"><i class="fa fa-star-o"></i></li>
                                                <li class="no-star"><i class="fa fa-star-o"></i></li>
                                            </ul>
                                        </div>
                                        <h4><a class="featured-product-name" href="<?= BASE_URL ?>/single-product?slug=<?= $p['slug'] ?>"><?= sanitize($p['name']) ?></a></h4>
                                        <div class="featured-price-box">
                                            <span class="new-price<?= $p['old_price'] ? ' new-price-2' : '' ?>"><?= format_price((float)$p['price']) ?></span>
                                            <?php if ($p['old_price']): ?>
                                                <span class="old-price"><?= format_price((float)$p['old_price']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="featured-product-action">
                                            <ul class="add-actions-link">
                                                <li class="add-cart active"><a href="#" class="add-to-cart-btn" data-id="<?= $p['id'] ?>">Add to cart</a></li>
                                                <li><a class="wishlist-btn links-details" data-id="<?= $p['id'] ?>" href="#"><i class="fa fa-heart-o"></i></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                        <?php $i++; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recommended For You product Section -->
<section class="product-area li-trending-product has-section-bar sb-purple pt-60 pb-45">
    <div class="container">
        <?php /* The tab links keep their data-toggle/href so Bootstrap still
                 drives the panels below — only their position and styling move. */ ?>
        <?php
        /* All three are derived, not curated: this visitor's own browsing
           history, what customers have saved and rated, and what sits in the
           categories they've been looking at. Each has a sensible fallback
           built into its query above, so no tab can come up empty. */
        $recommendedTabs = [
            'rec-recently-viewed' => ['label' => 'Recently Viewed',   'products' => $recentlyViewed],
            'rec-favorites'       => ['label' => 'Customer Favorites', 'products' => $customerFavorites],
            'rec-also-like'       => ['label' => 'You May Also Like',  'products' => $youMayAlsoLike],
        ];
        ?>
        <div class="section-bar">
            <h2>Recommended For You</h2>
            <ul class="nav section-bar-tabs" role="tablist">
                <?php $first = true; ?>
                <?php foreach ($recommendedTabs as $tabId => $tab): ?>
                    <li>
                        <a<?= $first ? ' class="active"' : '' ?> data-toggle="tab" href="#<?= $tabId ?>" role="tab">
                            <span><?= sanitize($tab['label']) ?></span>
                        </a>
                    </li>
                    <?php $first = false; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="tab-content li-tab-content li-trending-product-content">
                    <?php $first = true; ?>
                    <?php foreach ($recommendedTabs as $tabId => $tab): ?>
                    <div id="<?= $tabId ?>" class="tab-pane fade<?= $first ? ' show in active' : '' ?>" role="tabpanel">
                        <div class="row">
                            <?php if (!empty($tab['products'])): ?>
                                <div class="product-active owl-carousel">
                                    <?php foreach ($tab['products'] as $p): ?>
                                        <?= product_card($p) ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="col-lg-12">
                                    <p class="section-empty">Nothing to show here yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php $first = false; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php /* The standalone Powders / Roots & Bark / Supplements strips that used to
         sit here were removed: the Health & Wellness Herbs tabs above now render
         exactly the same three feeds, so keeping both would print every herb
         product on the page twice. */ ?>

<!-- Top Selling Products Section -->
<section class="product-area li-laptop-product li-trendding-products best-sellers has-section-bar sb-dark pb-45">
    <div class="container">
        <div class="section-bar">
            <h2>Top Selling Products</h2>
            <a class="section-bar-link" href="<?= BASE_URL ?>/shop">See All <i class="fa fa-angle-right"></i></a>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="row">
                    <div class="product-active owl-carousel">
                        <?php foreach ($topSelling as $p): ?>
                            <?= product_card($p) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
