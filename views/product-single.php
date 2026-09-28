<?php
/**
 * Kobo Choice - Single Product Detail View
 * Included by root-level single-product.php (header/footer handled there).
 */

global $db;

/* ─── Read GET parameter ──────────────────────────────────────────────── */
$slug = trim($_GET['slug'] ?? '');
$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($slug === '' && $id === 0) {
	redirect(BASE_URL . '/shop');
}

/* ─── Fetch product ───────────────────────────────────────────────────── */
if ($slug !== '') {
	$product = $db->fetch(
		"SELECT p.*, c.name AS category_name, c.slug AS category_slug
		 FROM products p
		 LEFT JOIN categories c ON p.category_id = c.id
		 WHERE p.slug = ? AND p.status = 'active'",
		[$slug]
	);
} else {
	$product = $db->fetch(
		"SELECT p.*, c.name AS category_name, c.slug AS category_slug
		 FROM products p
		 LEFT JOIN categories c ON p.category_id = c.id
		 WHERE p.id = ? AND p.status = 'active'",
		[$id]
	);
}

if (!$product) {
	redirect(BASE_URL . '/shop');
}

/* ─── Fetch gallery images ────────────────────────────────────────────── */
$galleryImages = $db->fetchAll(
	"SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC",
	[$product['id']]
);

/* ─── Fetch related products (same category, different ID) ────────────── */
$relatedProducts = [];
if (!empty($product['category_id'])) {
	$relatedProducts = $db->fetchAll(
		"SELECT p.*, c.name AS category_name
		 FROM products p
		 LEFT JOIN categories c ON p.category_id = c.id
		 WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
		 ORDER BY RAND()
		 LIMIT 4",
		[$product['category_id'], $product['id']]
	);
}

/* ─── Product image URL helper ────────────────────────────────────────── */

/**
 * True when the named upload is actually present on disk.
 *
 * A products/product_images row can outlive its file — an upload removed by
 * hand, a restore from a partial backup — and the row alone is not proof the
 * image can be served.
 */
/* Nullable: products.image and product_images.image are both NULLable, and a
   declared `string` parameter rejects null outright under PHP 8 rather than
   coercing it — which turned every image-less product into a fatal error. */
function product_img_exists(?string $filename): bool {
	$filename = (string) $filename;
	return $filename !== '' && is_file(PRODUCT_IMG_PATH . '/' . $filename);
}

/**
 * Public URL for a product image, falling back to the placeholder whenever the
 * file is missing. The existence check matters here specifically: the global
 * onerror handler in includes/header.php repairs a failed <img src>, but it
 * cannot repair the lightbox's <a href> — that would open a 404 in venobox.
 */
function product_img(?string $filename): string {
	return product_img_exists($filename)
		? PRODUCT_IMG_URL . '/' . rawurlencode((string) $filename)
		: PRODUCT_IMG_URL . '/placeholder.jpg';
}

/* ─── Build image lists ───────────────────────────────────────────────── */
$mainImages = [];

/* Primary product image — always present, so the slider is never empty. */
$mainImages[] = [
	'full'  => product_img($product['image']),
	'thumb' => product_img($product['image']),
];

/* Gallery images. A gallery entry whose file is gone is skipped rather than
   rendered as a placeholder: an extra tile carrying no picture just looks
   like a broken slide to the customer. */
foreach ($galleryImages as $gi) {
	if (!product_img_exists($gi['image'] ?? null)) {
		continue;
	}
	$mainImages[] = [
		'full'  => product_img($gi['image']),
		'thumb' => product_img($gi['image']),
	];
}

/* Fallback: at least one image */
if (empty($mainImages)) {
	$placeholder = product_img('placeholder.jpg');
	$mainImages = [['full' => $placeholder, 'thumb' => $placeholder]];
}

/* ─── Stock status ────────────────────────────────────────────────────── */
$inStock = ((int) $product['stock'] > 0);

/* ─── Update page title via variable if set by parent ─────────────────── */
if (isset($pageTitle)) {
	$pageTitle = sanitize($product['name']);
}
?>
<!-- Begin Li's Breadcrumb Area -->
<div class="breadcrumb-area">
  <div class="container">
    <div class="breadcrumb-content">
      <ul>
        <li><a href="<?= BASE_URL ?>/">Home</a></li>
        <li><a href="<?= BASE_URL ?>/shop">Shop</a></li>
        <?php if (!empty($product['category_name'])): ?>
          <li><a href="<?= BASE_URL ?>/shop?cat=<?= (int) $product['category_id'] ?>"><?= sanitize($product['category_name']) ?></a></li>
        <?php endif; ?>
        <li class="active"><?= sanitize($product['name']) ?></li>
      </ul>
    </div>
  </div>
</div>
<!-- Li's Breadcrumb Area End Here -->

<!-- content-wraper start -->
<div class="content-wraper">
  <div class="container">
    <div class="row single-product-area">
      <!-- Product Details Left -->
      <div class="col-lg-5 col-md-6">
        <div class="product-details-left">
          <div class="product-details-images slider-navigation-1">
            <?php foreach ($mainImages as $idx => $img): ?>
              <div class="lg-image">
                <a class="popup-img venobox vbox-item" href="<?= sanitize($img['full']) ?>" data-gall="myGallery">
                  <img src="<?= sanitize($img['full']) ?>" alt="<?= sanitize($product['name']) ?>">
                </a>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="product-details-thumbs slider-thumbs-1">
            <?php foreach ($mainImages as $img): ?>
              <div class="sm-image">
                <img src="<?= sanitize($img['thumb']) ?>" alt="product image thumb">
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <!-- Product Details Left End -->

      <!-- Product Details Right -->
      <div class="col-lg-7 col-md-6">
        <div class="product-details-view-content pt-60">
          <div class="product-info">
            <h2><?= sanitize($product['name']) ?></h2>

            <?php if (!empty($product['reference'])): ?>
              <span class="product-details-ref">Reference: <?= sanitize($product['reference']) ?></span>
            <?php endif; ?>

            <?php if (!empty($product['manufacturer'])): ?>
              <span class="product-details-ref">Brand: <?= sanitize($product['manufacturer']) ?></span>
            <?php endif; ?>

            <div class="price-box pt-20">
              <span class="new-price new-price-2"><?= format_price((float) $product['price']) ?></span>
              <?php if (!empty($product['old_price']) && (float) $product['old_price'] > 0): ?>
                <span class="old-price"><?= format_price((float) $product['old_price']) ?></span>
                <?php
                  $discount = round(((float) $product['old_price'] - (float) $product['price']) / (float) $product['old_price'] * 100);
                  if ($discount > 0):
                ?>
                  <span class="discount-percentage">-<?= $discount ?>%</span>
                <?php endif; ?>
              <?php endif; ?>
            </div>

            <?php if (!empty($product['short_description'])): ?>
              <div class="product-desc">
                <p><span><?= sanitize($product['short_description']) ?></span></p>
              </div>
            <?php endif; ?>

            <div class="product-add-to-cart">
              <div class="single-add-to-cart">
                <form action="#" class="cart-quantity">
                  <div class="quantity">
                    <label>Quantity</label>
                    <div class="cart-plus-minus">
                      <div class="dec qtybutton"><i class="fa fa-angle-down"></i></div>
                      <input id="product-quantity" class="cart-plus-minus-box" value="1" type="text" inputmode="numeric" pattern="[0-9]*" readonly min="1" max="<?= (int) $product['stock'] ?>">
                      <div class="inc qtybutton"><i class="fa fa-angle-up"></i></div>
                    </div>
                  </div>
                  <?php if ($inStock): ?>
                    <button class="add-to-cart add-to-cart-btn" type="button" data-id="<?= (int) $product['id'] ?>">Add to cart</button>
                  <?php else: ?>
                    <button class="add-to-cart" type="button" disabled>Out of Stock</button>
                  <?php endif; ?>
                </form>
              </div>
            </div>

            <div class="product-additional-info pt-25">
              <a class="wishlist-btn" href="#" data-id="<?= (int) $product['id'] ?>"><i class="fa fa-heart-o"></i>Add to wishlist</a>

              <div class="product-social-sharing pt-25">
                <ul>
                  <li class="facebook"><a href="#"><i class="fa fa-facebook"></i>Facebook</a></li>
                  <li class="twitter"><a href="#"><i class="fa fa-twitter"></i>Twitter</a></li>
                  <li class="instagram"><a href="#"><i class="fa fa-instagram"></i>Instagram</a></li>
                </ul>
              </div>
            </div>

            <div class="block-reassurance">
              <ul>
                <li>
                  <div class="reassurance-item">
                    <div class="reassurance-icon"><i class="fa fa-check-square-o"></i></div>
                    <p>Security policy - Your data is protected</p>
                  </div>
                </li>
                <li>
                  <div class="reassurance-item">
                    <div class="reassurance-icon"><i class="fa fa-truck"></i></div>
                    <p>Delivery policy - Fast & reliable shipping</p>
                  </div>
                </li>
                <li>
                  <div class="reassurance-item">
                    <div class="reassurance-icon"><i class="fa fa-exchange"></i></div>
                    <p>Return policy - Easy returns within 7 days</p>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <!-- Product Details Right End -->
    </div>
  </div>
</div>
<!-- content-wraper end -->

<!-- Begin Product Tab Area -->
<div class="product-area pt-35">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="li-product-tab">
          <ul class="nav li-product-menu">
            <li><a class="active" data-toggle="tab" href="#description"><span>Description</span></a></li>
            <?php if (!empty($product['manufacturer']) || !empty($product['reference']) || !empty($product['sku'])): ?>
              <li><a data-toggle="tab" href="#product-details"><span>Product Details</span></a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
    <div class="tab-content">
      <div id="description" class="tab-pane active show" role="tabpanel">
        <div class="product-description">
          <?php if (!empty($product['description'])): ?>
            <?= $product['description'] ?>
          <?php else: ?>
            <span><?= sanitize($product['short_description'] ?? 'No description available.') ?></span>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($product['manufacturer']) || !empty($product['reference']) || !empty($product['sku'])): ?>
        <div id="product-details" class="tab-pane" role="tabpanel">
          <div class="product-details-manufacturer">
            <table class="table">
              <?php if (!empty($product['manufacturer'])): ?>
                <tr><td><strong>Brand / Manufacturer</strong></td><td><?= sanitize($product['manufacturer']) ?></td></tr>
              <?php endif; ?>
              <?php if (!empty($product['reference'])): ?>
                <tr><td><strong>Reference</strong></td><td><?= sanitize($product['reference']) ?></td></tr>
              <?php endif; ?>
              <?php if (!empty($product['sku'])): ?>
                <tr><td><strong>SKU</strong></td><td><?= sanitize($product['sku']) ?></td></tr>
              <?php endif; ?>
              <tr><td><strong>Stock</strong></td><td><?= $inStock ? 'In Stock (' . (int) $product['stock'] . ' available)' : 'Out of Stock' ?></td></tr>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<!-- Product Tab Area End Here -->

<!-- Begin Related Products Area -->
<?php if (!empty($relatedProducts)): ?>
  <section class="product-area li-laptop-product pt-30 pb-50">
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <div class="li-section-title">
            <h2><span>Related Products:</span></h2>
          </div>
          <div class="row">
            <div class="product-active owl-carousel">
              <?php foreach ($relatedProducts as $rp): ?>
                <?php
                  $rpImg = $rp['image']
                    ? PRODUCT_IMG_URL . '/' . $rp['image']
                    : PRODUCT_IMG_URL . '/placeholder.jpg';
                ?>
                <div class="col-lg-12">
                  <div class="single-product-wrap">
                    <div class="product-image">
                      <a href="<?= BASE_URL ?>/single-product?slug=<?= urlencode($rp['slug']) ?>">
                        <img src="<?= sanitize($rpImg) ?>" alt="<?= sanitize($rp['name']) ?>">
                      </a>
                      <?php if ($rp['is_new']): ?>
                        <span class="sticker">New</span>
                      <?php endif; ?>
                    </div>
                    <div class="product_desc">
                      <div class="product_desc_info">
                        <?php if (!empty($rp['category_name'])): ?>
                          <div class="product-review">
                            <h5 class="manufacturer">
                              <a href="<?= BASE_URL ?>/shop?cat=<?= (int) $rp['category_id'] ?>"><?= sanitize($rp['category_name']) ?></a>
                            </h5>
                          </div>
                        <?php endif; ?>
                        <h4><a class="product_name" href="<?= BASE_URL ?>/single-product?slug=<?= urlencode($rp['slug']) ?>"><?= sanitize($rp['name']) ?></a></h4>
                        <div class="price-box">
                          <span class="new-price"><?= format_price((float) $rp['price']) ?></span>
                          <?php if (!empty($rp['old_price']) && (float) $rp['old_price'] > 0): ?>
                            <span class="old-price"><?= format_price((float) $rp['old_price']) ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="add-actions">
                        <ul class="add-actions-link">
                          <li class="add-cart active"><a href="#" class="add-to-cart-btn" data-id="<?= (int) $rp['id'] ?>">Add to cart</a></li>
                          <li><a class="wishlist-btn links-details" data-id="<?= (int) $rp['id'] ?>" href="#"><i class="fa fa-heart-o"></i></a></li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>
<!-- Related Products Area End Here -->
