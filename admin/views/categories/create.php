<?php
global $db;

$parentId = (int)($_GET['id'] ?? 0);
$category = null;
$parentCategories = $db->fetchAll("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC");

if ($parentId) {
    $category = $db->fetch("SELECT * FROM categories WHERE id = ?", [$parentId]);
    if (!$category) {
        set_flash('error', 'Category not found.');
        redirect(admin_url('/?view=categories'));
    }
}
?>

<h2 style="margin-bottom:20px;"><?= $category ? 'Edit Category' : 'Add New Category' ?></h2>

<form id="categoryForm" action="<?= API_URL ?>?action=<?= $category ? 'category_update' : 'category_create' ?>"
      method="POST" enctype="multipart/form-data">

    <?php if ($category): ?>
        <input type="hidden" name="id" value="<?= $category['id'] ?>">
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">
        <div>
            <div class="admin-card">
                <div class="card-header"><h3>Category Info</h3></div>
                <div class="admin-form">
                    <div class="form-group">
                        <label>Category Name *</label>
                        <input type="text" name="name" required value="<?= sanitize($category['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Parent Category</label>
                        <select name="parent_id">
                            <option value="">-- None (Top Level) --</option>
                            <?php foreach ($parentCategories as $pc): ?>
                                <?php if (!$category || $pc['id'] != $category['id']): ?>
                                <option value="<?= $pc['id'] ?>" <?= ($category['parent_id'] ?? '') == $pc['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($pc['name']) ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4"><?= sanitize($category['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="admin-card">
                <div class="card-header"><h3>Image</h3></div>
                <div class="admin-form">
                    <div class="form-group">
                        <label>Category Image</label>
                        <input type="file" name="image" accept="image/*" data-preview="catImagePreview">
                        <div class="image-preview" id="catImagePreview">
                            <?php if (!empty($category['image'])): ?>
                                <img src="<?= CATEGORY_IMG_URL . '/' . $category['image'] ?>" alt="">
                            <?php else: ?>
                                <i class="fa fa-image"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Options</h3></div>
                <div class="admin-form">
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" min="0" value="<?= $category['sort_order'] ?? 0 ?>">
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_in_menu" value="1" <?= ($category['show_in_menu'] ?? 1) ? 'checked' : '' ?>> Show in Menu
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_in_sidebar" value="1" <?= ($category['show_in_sidebar'] ?? 1) ? 'checked' : '' ?>> Show in Sidebar
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="admin-btn admin-btn-primary admin-btn-lg" style="flex:1;">
                    <i class="fa fa-save"></i> <?= $category ? 'Update Category' : 'Save Category' ?>
                </button>
                <a href="<?= admin_url('/?view=categories') ?>" class="admin-btn admin-btn-outline admin-btn-lg">Cancel</a>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('categoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm('categoryForm', function(res) {
        if (res.success) {
            showAlert('success', res.message);
            setTimeout(function() {
                window.location.href = '<?= admin_url('/?view=categories') ?>';
            }, 1000);
        } else {
            showAlert('danger', res.message || 'Save failed.');
        }
    });
});
</script>
