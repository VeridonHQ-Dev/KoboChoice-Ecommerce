<?php
global $db;

$categories = $db->fetchAll("SELECT id, name, parent_id FROM categories ORDER BY sort_order ASC, name ASC");

$productId = (int)($_GET['id'] ?? 0);
$product = null;
if ($productId) {
    $product = $db->fetch("SELECT * FROM products WHERE id = ?", [$productId]);
    if (!$product) {
        set_flash('error', 'Product not found.');
        redirect(admin_url('/?view=products'));
    }
    $existingImages = $db->fetchAll("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC", [$productId]);
    $pageTitle = 'Edit Product';
} else {
    $existingImages = [];
    $pageTitle = 'Add Product';
}
?>

<h2 style="margin-bottom:20px;"><?= $product ? 'Edit Product' : 'Add New Product' ?></h2>

<form id="productForm" action="<?= API_URL ?>?action=<?= $product ? 'product_update' : 'product_create' ?>"
      method="POST" enctype="multipart/form-data">

    <?php if ($product): ?>
        <input type="hidden" name="id" value="<?= $product['id'] ?>">
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">
        <div>
            <div class="admin-card">
                <div class="card-header"><h3>General Info</h3></div>
                <div class="admin-form">
                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="name" required value="<?= sanitize($product['name'] ?? '') ?>">
                    </div>
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id">
                                <option value="">-- Select --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Section</label>
                            <?php /* Options come from product_sections() so this dropdown, the
                                     homepage tabs and the stored value can never drift apart. */ ?>
                            <select name="section">
                                <option value="">-- None --</option>
                                <?php foreach (product_sections() as $sectionKey => $sectionLabel): ?>
                                    <option value="<?= $sectionKey ?>" <?= ($product['section'] ?? '') === $sectionKey ? 'selected' : '' ?>>
                                        <?= sanitize($sectionLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-hint">Guarantees the product a slot in that homepage section.</span>
                        </div>
                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" name="sku" value="<?= sanitize($product['sku'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Reference</label>
                            <input type="text" name="reference" value="<?= sanitize($product['reference'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Manufacturer</label>
                            <input type="text" name="manufacturer" value="<?= sanitize($product['manufacturer'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Short Description</label>
                        <input type="text" name="short_description" value="<?= sanitize($product['short_description'] ?? '') ?>" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label>Full Description</label>
                        <textarea name="description" rows="6"><?= sanitize($product['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Pricing & Stock</h3></div>
                <div class="admin-form">
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Price (<?= CURRENCY ?>) *</label>
                            <input type="number" name="price" step="0.01" min="0" required value="<?= $product['price'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label>Old Price (<?= CURRENCY ?>)</label>
                            <input type="number" name="old_price" step="0.01" min="0" value="<?= $product['old_price'] ?? '' ?>">
                            <span class="form-hint">Show as strikethrough price</span>
                        </div>
                        <div class="form-group">
                            <label>Cost Price (<?= CURRENCY ?>)</label>
                            <input type="number" name="cost_price" step="0.01" min="0" value="<?= $product['cost_price'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock" min="0" required value="<?= $product['stock'] ?? 0 ?>">
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="admin-card">
                <div class="card-header"><h3>Image</h3></div>
                <div class="admin-form">
                    <div class="form-group">
                        <label>Main Image</label>
                        <input type="file" name="image" accept="image/*" data-preview="imagePreview">
                        <div class="image-preview" id="imagePreview">
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?= PRODUCT_IMG_URL . '/' . $product['image'] ?>" alt="">
                            <?php else: ?>
                                <i class="fa fa-image"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Additional Images (up to 4)</label>
                        <span class="form-hint">Shown in the product gallery on the details page. Hold Ctrl/Cmd to pick several at once.</span>
                        <input type="file" name="gallery[]" accept="image/*" multiple>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Status</h3></div>
                <div class="admin-form">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?= ($product['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="draft" <?= ($product['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>
                    <?php /* Deliberately nothing else in here. Section placement is chosen
                             under General Info; Deals and Featured are switched from the
                             product list, where you can see the whole catalogue at once. */ ?>
                </div>
            </div>

            <?php if ($product && !empty($existingImages)): ?>
            <div class="admin-card">
                <div class="card-header"><h3>Gallery Images</h3></div>
                <div class="gallery-grid">
                    <?php foreach ($existingImages as $img): ?>
                        <div class="gallery-item">
                            <img src="<?= PRODUCT_IMG_URL . '/' . $img['image'] ?>" alt="">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="admin-btn admin-btn-primary admin-btn-lg" style="flex:1;">
                    <i class="fa fa-save"></i> <?= $product ? 'Update Product' : 'Save Product' ?>
                </button>
                <a href="<?= admin_url('/?view=products') ?>" class="admin-btn admin-btn-outline admin-btn-lg">Cancel</a>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm('productForm', function(res) {
        if (res.success) {
            showAlert('success', res.message);
            setTimeout(function() {
                window.location.href = '<?= admin_url('/?view=products') ?>';
            }, 1000);
        } else {
            showAlert('danger', res.message || 'Save failed.');
        }
    });
});
</script>
