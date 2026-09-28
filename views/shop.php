<?php
/**
 * Kobo Choice - Shop / Product Listing View
 * Included by root-level shop.php (header/footer handled there).
 */

global $db;

/* ─── Read GET parameters ─────────────────────────────────────────────── */
$search    = trim($_GET['q'] ?? '');
$catId     = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;
$sortBy    = $_GET['sort'] ?? 'default';

/* ─── Fetch sidebar categories ────────────────────────────────────────── */
$sidebarCategories = $db->fetchAll(
	"SELECT * FROM categories WHERE show_in_sidebar = 1 ORDER BY sort_order ASC"
);

/* ─── Build product query ─────────────────────────────────────────────── */
$where  = "p.status = 'active'";
$params = [];

if ($search !== '') {
	$where .= " AND (p.name LIKE ? OR p.short_description LIKE ?)";
	$params[] = "%$search%";
	$params[] = "%$search%";
}

if ($catId > 0) {
	$where .= " AND p.category_id = ?";
	$params[] = $catId;
}

/* ─── Sorting ─────────────────────────────────────────────────────────── */
$orderClause = match ($sortBy) {
	'price_asc'  => 'p.price ASC',
	'price_desc' => 'p.price DESC',
	'newest'     => 'p.created_at DESC',
	default      => 'p.sort_order ASC, p.created_at DESC',
};

/* ─── Pagination ──────────────────────────────────────────────────────── */
$pagination = paginate($db, 'products p', $where, $params, ITEMS_PER_PAGE);

$products = $db->fetchAll(
	"SELECT p.*, c.name AS category_name
	 FROM products p
	 LEFT JOIN categories c ON p.category_id = c.id
	 WHERE $where
	 ORDER BY $orderClause
	 LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
	$params
);

/* ─── Build base URL for pagination links ─────────────────────────────── */
$paginationUrl = BASE_URL . '/shop';
$paginationParams = [];
if ($search !== '') $paginationParams['q'] = $search;
if ($catId > 0)    $paginationParams['cat'] = $catId;
if ($sortBy !== 'default') $paginationParams['sort'] = $sortBy;

/* Ends with the correct separator so links can simply append "page=N".
   Without this, an unfiltered shop produced "/shop.php&page=2" (a 404)
   because there was no "?" to start the query string. */
$paginationUrl .= empty($paginationParams)
	? '?'
	: '?' . http_build_query($paginationParams) . '&';

/* ─── Sort label helper ───────────────────────────────────────────────── */
$sortLabels = [
	'default'    => 'Default sorting',
	'price_asc'  => 'Price: Low to High',
	'price_desc' => 'Price: High to Low',
	'newest'     => 'Newest First',
];

/* ─── Determine active category name ──────────────────────────────────── */
$activeCategoryName = '';
if ($catId > 0) {
	$activeCat = $db->fetch("SELECT name FROM categories WHERE id = ?", [$catId]);
	$activeCategoryName = $activeCat['name'] ?? '';
}

/* ─── Price range across the current filter set (slider bounds/defaults) ── */
/* Min always starts at 0; Max is the highest product price in this category. */
$priceRange = $db->fetch("SELECT MAX(price) AS max_price FROM products p WHERE $where", $params);
$priceMin = 0;
$priceMax = (int) ceil((float) ($priceRange['max_price'] ?? 0));
if ($priceMax <= $priceMin) {
	$priceMax = $priceMin + 1; // guard against a zero-width slider
}
?>
<style>
/* ─── Sidebar Containers Visibility ─── */
.sidebar-categores-box {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.015) !important;
    padding: 24px 20px !important;
    margin-bottom: 24px !important;
}

.sidebar-title {
    border-bottom: 1px solid #f1f5f9 !important;
    margin-bottom: 18px !important;
    padding-bottom: 8px !important;
}

.sidebar-title h2 {
    font-size: 15px !important;
    font-weight: 700 !important;
    color: #1e293b !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    margin-bottom: 0 !important;
    position: relative !important;
}

/* Category item link styling */
.category-sub-menu ul li a {
    color: #475569 !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
    display: inline-block !important;
}

.category-sub-menu ul li a:hover,
.category-sub-menu ul li a.active-cat {
    color: #F48201 !important;
    padding-left: 4px !important;
}

/* ─── Price Slider Customization ─── */
#price-slider.ui-slider {
    height: 5px !important;
    background: #cbd5e1 !important;
    border: none !important;
    border-radius: 3px !important;
    margin: 25px 8px 20px !important;
    position: relative !important;
}

#price-slider .ui-slider-range {
    background: #F48201 !important;
    border-radius: 3px !important;
    position: absolute !important;
    height: 100% !important;
}

#price-slider .ui-slider-handle {
    width: 14px !important;
    height: 14px !important;
    border-radius: 50% !important;
    background-color: #0D1B2A !important;
    border: none !important;
    top: -5px !important;
    margin-left: -7px !important;
    cursor: pointer !important;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2) !important;
    position: absolute !important;
    z-index: 2 !important;
    outline: none !important;
    transition: transform 0.2s ease, background-color 0.2s ease !important;
}

#price-slider .ui-slider-handle:hover,
#price-slider .ui-slider-handle:active {
    transform: scale(1.15) !important;
    background-color: #0d1b2a !important;
}
</style>
<!-- Begin Li's Breadcrumb Area -->
<div class="breadcrumb-area">
  <div class="container">
    <div class="breadcrumb-content">
      <ul>
        <li><a href="<?= BASE_URL ?>/">Home</a></li>
        <li class="active">Shop</li>
        <?php if ($activeCategoryName): ?>
          <li class="active"><?= sanitize($activeCategoryName) ?></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>
<!-- Li's Breadcrumb Area End Here -->

<!-- Begin Li's Content Wraper Area -->
<div class="content-wraper pt-60 pb-60 pt-sm-30">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <h2 class="shop-category-title" style="font-weight:700;margin-bottom:20px;">
          <?= $activeCategoryName ? sanitize($activeCategoryName) : ($search !== '' ? 'Search results for "' . sanitize($search) . '"' : 'All Products') ?>
        </h2>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-9 order-1 order-lg-2">

        <!-- shop-top-bar start -->
        <div class="shop-top-bar mt-30">
          <div class="shop-bar-inner">
            <div class="product-view-mode">
              <ul class="nav shop-item-filter-list" role="tablist">
                <li class="active" role="presentation"><a aria-selected="true" class="active show" data-toggle="tab" role="tab" aria-controls="grid-view" href="#grid-view"><i class="fa fa-th"></i></a></li>
                <li role="presentation"><a data-toggle="tab" role="tab" aria-controls="list-view" href="#list-view"><i class="fa fa-th-list"></i></a></li>
              </ul>
            </div>
            <div class="toolbar-amount">
              <span>Showing <?= $pagination['offset'] + 1 ?> to <?= min($pagination['offset'] + $pagination['per_page'], $pagination['total']) ?> of <?= $pagination['total'] ?> item(s)</span>
            </div>
          </div>
          <div class="product-select-box">
            <div class="product-short">
              <p>Sort By:</p>
              <select class="nice-select" id="sort-select">
                <?php foreach ($sortLabels as $key => $label): ?>
                  <option value="<?= $key ?>"<?= $sortBy === $key ? ' selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <!-- shop-top-bar end -->

        <!-- shop-products-wrapper start -->
        <div class="shop-products-wrapper">
          <div class="tab-content">
            <!-- Grid View -->
            <div id="grid-view" class="tab-pane fade active show" role="tabpanel">
              <div class="product-area shop-product-area">
                <div class="row">
                  <?php if (empty($products)): ?>
                    <div class="col-12 text-center pt-40">
                      <p>No products found matching your criteria.</p>
                    </div>
                  <?php else: ?>
                    <?php foreach ($products as $product): ?>
                      <?php
                        $imgUrl = $product['image']
                          ? PRODUCT_IMG_URL . '/' . $product['image']
                          : PRODUCT_IMG_URL . '/placeholder.jpg';
                      ?>
                      <div class="col-lg-4 col-md-4 col-sm-6 mt-40 js-product-item" data-name="<?= sanitize(strtolower($product['name'])) ?>" data-price="<?= (float) $product['price'] ?>">
                        <div class="single-product-wrap">
                          <div class="product-image">
                            <a href="<?= BASE_URL ?>/single-product?slug=<?= urlencode($product['slug']) ?>">
                              <img src="<?= sanitize($imgUrl) ?>" alt="<?= sanitize($product['name']) ?>">
                            </a>
                            <?php if ($product['is_new']): ?>
                              <span class="sticker">New</span>
                            <?php endif; ?>
                          </div>
                          <div class="product_desc">
                            <div class="product_desc_info">
                              <?php if (!empty($product['category_name'])): ?>
                                <div class="product-review">
                                  <h5 class="manufacturer">
                                    <a href="<?= BASE_URL ?>/shop?cat=<?= (int) $product['category_id'] ?>"><?= sanitize($product['category_name']) ?></a>
                                  </h5>
                                </div>
                              <?php endif; ?>
                              <h4><a class="product_name" href="<?= BASE_URL ?>/single-product?slug=<?= urlencode($product['slug']) ?>"><?= sanitize($product['name']) ?></a></h4>
                              <div class="price-box">
                                <span class="new-price"><?= format_price((float) $product['price']) ?></span>
                                <?php if (!empty($product['old_price']) && (float) $product['old_price'] > 0): ?>
                                  <span class="old-price"><?= format_price((float) $product['old_price']) ?></span>
                                <?php endif; ?>
                              </div>
                            </div>
                            <div class="add-actions">
                              <ul class="add-actions-link">
                                <li class="add-cart active"><a href="#" class="add-to-cart-btn" data-id="<?= (int) $product['id'] ?>">Add to cart</a></li>
                                <li><a class="wishlist-btn links-details" data-id="<?= (int) $product['id'] ?>" href="#"><i class="fa fa-heart-o"></i></a></li>
                              </ul>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <!-- Grid View End -->

            <!-- List View -->
            <div id="list-view" class="tab-pane fade product-list-view" role="tabpanel">
              <div class="row">
                <div class="col">
                  <?php if (empty($products)): ?>
                    <div class="text-center pt-40">
                      <p>No products found matching your criteria.</p>
                    </div>
                  <?php else: ?>
                    <?php foreach ($products as $product): ?>
                      <?php
                        $imgUrl = $product['image']
                          ? PRODUCT_IMG_URL . '/' . $product['image']
                          : PRODUCT_IMG_URL . '/placeholder.jpg';
                      ?>
                      <div class="row product-layout-list js-product-item" data-name="<?= sanitize(strtolower($product['name'])) ?>" data-price="<?= (float) $product['price'] ?>">
                        <div class="col-lg-3 col-md-5">
                          <div class="product-image">
                            <a href="<?= BASE_URL ?>/single-product?slug=<?= urlencode($product['slug']) ?>">
                              <img src="<?= sanitize($imgUrl) ?>" alt="<?= sanitize($product['name']) ?>">
                            </a>
                            <?php if ($product['is_new']): ?>
                              <span class="sticker">New</span>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div class="col-lg-5 col-md-7">
                          <div class="product_desc">
                            <div class="product_desc_info">
                              <?php if (!empty($product['category_name'])): ?>
                                <div class="product-review">
                                  <h5 class="manufacturer">
                                    <a href="<?= BASE_URL ?>/shop?cat=<?= (int) $product['category_id'] ?>"><?= sanitize($product['category_name']) ?></a>
                                  </h5>
                                </div>
                              <?php endif; ?>
                              <h4><a class="product_name" href="<?= BASE_URL ?>/single-product?slug=<?= urlencode($product['slug']) ?>"><?= sanitize($product['name']) ?></a></h4>
                              <div class="price-box">
                                <span class="new-price"><?= format_price((float) $product['price']) ?></span>
                                <?php if (!empty($product['old_price']) && (float) $product['old_price'] > 0): ?>
                                  <span class="old-price"><?= format_price((float) $product['old_price']) ?></span>
                                <?php endif; ?>
                              </div>
                              <?php if (!empty($product['short_description'])): ?>
                                <p><?= sanitize(truncate($product['short_description'], 200)) ?></p>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                        <div class="col-lg-4">
                          <div class="shop-add-action mb-xs-30">
                            <ul class="add-actions-link">
                              <li class="add-cart"><a href="#" class="add-to-cart-btn" data-id="<?= (int) $product['id'] ?>">Add to cart</a></li>
                              <li class="wishlist"><a class="wishlist-btn" data-id="<?= (int) $product['id'] ?>" href="#"><i class="fa fa-heart-o"></i>Add to wishlist</a></li>
                            </ul>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <!-- List View End -->

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
              <div class="paginatoin-area">
                <div class="row">
                  <div class="col-lg-6 col-md-6 pt-xs-15">
                    <p>Showing <?= $pagination['offset'] + 1 ?>-<?= min($pagination['offset'] + $pagination['per_page'], $pagination['total']) ?> of <?= $pagination['total'] ?> item(s)</p>
                  </div>
                  <div class="col-lg-6 col-md-6">
                    <ul class="pagination-box pt-xs-20 pb-xs-15">
                      <?php if ($pagination['page'] > 1): ?>
                        <li><a href="<?= $paginationUrl ?>page=<?= $pagination['page'] - 1 ?>" class="Previous"><i class="fa fa-chevron-left"></i> Previous</a></li>
                      <?php endif; ?>

                      <?php
                        $start = max(1, $pagination['page'] - 2);
                        $end   = min($pagination['total_pages'], $pagination['page'] + 2);
                      ?>
                      <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="<?= $i === $pagination['page'] ? 'active' : '' ?>">
                          <a href="<?= $paginationUrl ?>page=<?= $i ?>"><?= $i ?></a>
                        </li>
                      <?php endfor; ?>

                      <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                        <li><a href="<?= $paginationUrl ?>page=<?= $pagination['page'] + 1 ?>" class="Next">Next <i class="fa fa-chevron-right"></i></a></li>
                      <?php endif; ?>
                    </ul>
                  </div>
                </div>
              </div>
            <?php endif; ?>
            <!-- Pagination End -->

          </div>
        </div>
        <!-- shop-products-wrapper end -->
      </div>

      <!-- Sidebar -->
      <div class="col-lg-3 order-2 order-lg-1">

        <!-- Filter: Search -->
        <div class="sidebar-categores-box mt-sm-30 mt-xs-30">
          <div class="sidebar-title">
            <h2>Search</h2>
          </div>
          <div class="sidebar-search">
            <input type="text" id="sidebar-search-input" class="sidebar-search-input"
                   placeholder="Search products..." autocomplete="off"
                   value="<?= sanitize($search) ?>"
                   style="width:100%;padding:10px 12px;border:1px solid #e1e1e1;border-radius:3px;">
          </div>
        </div>

        <!-- Filter: Price -->
        <div class="sidebar-categores-box">
          <div class="sidebar-title">
            <h2>Filter by Price</h2>
          </div>
          <div class="price-filter">
            <div id="price-slider" style="margin:15px 5px 20px;"></div>
            <div class="price-inputs" style="display:flex;gap:10px;">
              <div style="flex:1;">
                <label style="font-size:12px;display:block;margin-bottom:4px;">Min. Price</label>
                <input type="number" id="price-min-input" min="<?= $priceMin ?>" max="<?= $priceMax ?>"
                       value="<?= $priceMin ?>" style="width:100%;padding:8px;border:1px solid #e1e1e1;border-radius:3px;">
              </div>
              <div style="flex:1;">
                <label style="font-size:12px;display:block;margin-bottom:4px;">Max. Price</label>
                <input type="number" id="price-max-input" min="<?= $priceMin ?>" max="<?= $priceMax ?>"
                       value="<?= $priceMax ?>" style="width:100%;padding:8px;border:1px solid #e1e1e1;border-radius:3px;">
              </div>
            </div>
          </div>
        </div>

        <div class="sidebar-categores-box">
          <div class="sidebar-title">
            <h2>Categories</h2>
          </div>
          <div class="category-sub-menu">
            <ul>
              <?php
                /* Build parent => children map */
                $parentMap = [];
                foreach ($sidebarCategories as $sc) {
                  $pid = (int) ($sc['parent_id'] ?? 0);
                  $parentMap[$pid][] = $sc;
                }

                function render_sidebar_menu(array $parentMap, int $parentId, int $activeCatId): void {
                  if (empty($parentMap[$parentId])) return;
                  echo '<ul>';
                  foreach ($parentMap[$parentId] as $cat) {
                    $hasChildren = isset($parentMap[(int) $cat['id']]);
                    $active = ((int) $cat['id'] === $activeCatId) ? ' class="active-cat"' : '';
                    echo '<li' . ($hasChildren ? ' class="has-sub"' : '') . '>';
                    echo '<a href="' . BASE_URL . '/shop?cat=' . (int) $cat['id'] . '"' . $active . '>' . sanitize($cat['name']) . '</a>';
                    if ($hasChildren) {
                      render_sidebar_menu($parentMap, (int) $cat['id'], $activeCatId);
                    }
                    echo '</li>';
                  }
                  echo '</ul>';
                }

                render_sidebar_menu($parentMap, 0, $catId);
              ?>
            </ul>
          </div>
        </div>

        <!-- Price tags / category quick links -->
        <div class="sidebar-categores-box mb-sm-0 mb-xs-0">
          <div class="sidebar-title">
            <h2>Quick Links</h2>
          </div>
          <div class="category-tags">
            <ul>
              <li><a href="<?= BASE_URL ?>/shop">All Products</a></li>
              <li><a href="<?= BASE_URL ?>/shop?sort=newest">New Arrivals</a></li>
              <li><a href="<?= BASE_URL ?>/shop?sort=price_asc">Price: Low to High</a></li>
              <li><a href="<?= BASE_URL ?>/shop?sort=price_desc">Price: High to Low</a></li>
            </ul>
          </div>
        </div>
      </div>
      <!-- Sidebar End -->
    </div>
  </div>
</div>
<!-- Content Wraper Area End Here -->

<script>
document.addEventListener('DOMContentLoaded', function () {
  var sortSelect = document.getElementById('sort-select');
  if (sortSelect) {
    sortSelect.addEventListener('change', function () {
      var url = new URL(window.location.href);
      if (this.value === 'default') {
        url.searchParams.delete('sort');
      } else {
        url.searchParams.set('sort', this.value);
      }
      url.searchParams.delete('page');
      window.location.href = url.toString();
    });
  }
});
</script>

<script>
// Live sidebar filters (search + price slider). Deferred to window.load because
// jQuery + jQuery UI are loaded in the footer, after this view renders.
window.addEventListener('load', function () {
  if (typeof jQuery === 'undefined' || !jQuery.fn.slider) { return; }
  (function ($) {
    var MIN = <?= $priceMin ?>, MAX = <?= $priceMax ?>;
    var $min = $('#price-min-input');
    var $max = $('#price-max-input');
    var $search = $('#sidebar-search-input');
    var $items = $('.js-product-item');

    // "No results" notices (one per view), created once and toggled.
    var $gridNote = $('<div class="col-12 text-center pt-40 js-no-results" style="display:none;"><p>No products match your filters.</p></div>').appendTo('#grid-view .product-area .row');
    var $listNote = $('<div class="text-center pt-40 js-no-results" style="display:none;"><p>No products match your filters.</p></div>').appendTo('#list-view .col');

    function applyFilter() {
      var q = ($search.val() || '').trim().toLowerCase();
      var lo = parseFloat($min.val()); if (isNaN(lo)) lo = MIN;
      var hi = parseFloat($max.val()); if (isNaN(hi)) hi = MAX;

      var gridVisible = 0, listVisible = 0;
      $items.each(function () {
        var $it = $(this);
        var name = ($it.attr('data-name') || '');
        var price = parseFloat($it.attr('data-price')) || 0;
        var match = (q === '' || name.indexOf(q) !== -1) && price >= lo && price <= hi;
        $it.toggle(match);
        if (match) {
          if ($it.closest('#list-view').length) { listVisible++; } else { gridVisible++; }
        }
      });
      $gridNote.toggle(gridVisible === 0);
      $listNote.toggle(listVisible === 0);
    }

    // Draggable range slider (jQuery UI). Bounds + defaults come from PHP:
    // the min/max price of the products in the current category/search.
    $('#price-slider').slider({
      range: true,
      min: MIN,
      max: MAX,
      values: [MIN, MAX],
      slide: function (e, ui) {
        $min.val(ui.values[0]);
        $max.val(ui.values[1]);
        applyFilter();
      }
    });

    // Typing in the boxes moves the slider (and empty boxes fall back to bounds).
    $min.add($max).on('input change', function () {
      var lo = parseInt($min.val(), 10); if (isNaN(lo)) lo = MIN;
      var hi = parseInt($max.val(), 10); if (isNaN(hi)) hi = MAX;
      lo = Math.max(MIN, Math.min(lo, MAX));
      hi = Math.max(MIN, Math.min(hi, MAX));
      if (lo > hi) { var t = lo; lo = hi; hi = t; }
      $('#price-slider').slider('values', [lo, hi]);
      applyFilter();
    });

    // Live search as the user types.
    $search.on('input', applyFilter);

    applyFilter();
  })(jQuery);
});
</script>
