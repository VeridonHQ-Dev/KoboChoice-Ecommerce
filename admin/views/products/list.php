<?php
global $db;

$search = $_GET['search'] ?? '';
$where = "1";
$params = [];

if (!empty($search)) {
    $where .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
}

$pagination = paginate($db, 'products p', $where, $params, ADMIN_ITEMS_PER_PAGE);

$products = $db->fetchAll(
    "SELECT p.*, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE $where
     ORDER BY p.created_at DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

$baseUrl = admin_url('/?view=products') . (!empty($search) ? '&search=' . urlencode($search) : '');
?>

<div class="admin-toolbar">
    <form method="GET" class="search-box">
        <input type="hidden" name="view" value="products">
        <input type="text" name="search" placeholder="Search products..." value="<?= sanitize($search) ?>">
        <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm"><i class="fa fa-search"></i></button>
    </form>
    <a href="<?= admin_url('/?view=products&sub=create') ?>" class="admin-btn admin-btn-primary">
        <i class="fa fa-plus"></i> Add Product
    </a>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Deals</th>
                <th>Featured</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="9" class="admin-empty"><i class="fa fa-cube"></i> No products found</td></tr>
            <?php else: ?>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <?php if ($p['image']): ?>
                            <img src="<?= PRODUCT_IMG_URL . '/' . $p['image'] ?>" class="product-thumb" alt="">
                        <?php else: ?>
                            <div class="product-thumb" style="background:#f1f5f9;display:flex;align-items:center;justify-content:center;">
                                <i class="fa fa-image" style="color:#94a3b8;"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= sanitize($p['name']) ?></strong>
                        <?php if ($p['sku']): ?><br><small style="color:#64748b;">SKU: <?= sanitize($p['sku']) ?></small><?php endif; ?>
                    </td>
                    <td>
                        <?= sanitize($p['category_name'] ?? 'N/A') ?>
                        <?php /* Section placement is set on the edit form, so surface it here —
                                 otherwise you'd have to open every product to see where it sits. */ ?>
                        <?php $sectionLabel = product_sections()[$p['section'] ?? ''] ?? null; ?>
                        <?php if ($sectionLabel): ?>
                            <br><small class="section-tag"><?= sanitize($sectionLabel) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= format_price((float)$p['price']) ?>
                        <?php if ($p['old_price']): ?>
                            <br><small style="color:#dc2626;text-decoration:line-through;"><?= format_price((float)$p['old_price']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($p['stock'] <= 0): ?>
                            <span style="color:#dc2626;font-weight:600;">Out</span>
                        <?php elseif ($p['stock'] < 5): ?>
                            <span style="color:#d97706;font-weight:600;"><?= $p['stock'] ?></span>
                        <?php else: ?>
                            <?= $p['stock'] ?>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge-status badge-<?= $p['status'] ?>"><?= $p['status'] ?></span></td>
                    <td>
                        <?php /* Ticking this puts the product in "Top Deals of the Day" on the
                                 landing page immediately — no save step, no edit form. */ ?>
                        <label class="flag-switch" title="Show in Top Deals of the Day">
                            <input type="checkbox" class="flag-toggle"
                                   data-url="<?= API_URL ?>?action=product_toggle_flag"
                                   data-id="<?= $p['id'] ?>"
                                   data-field="is_deal"
                                   data-label="Top Deals of the Day"
                                   aria-label="Show <?= sanitize($p['name']) ?> in Top Deals of the Day"
                                   <?= !empty($p['is_deal']) ? 'checked' : '' ?>>
                            <span class="flag-switch-track"><span class="flag-switch-thumb"></span></span>
                        </label>
                    </td>
                    <td>
                        <label class="flag-switch" title="Show in Featured Products">
                            <input type="checkbox" class="flag-toggle"
                                   data-url="<?= API_URL ?>?action=product_toggle_flag"
                                   data-id="<?= $p['id'] ?>"
                                   data-field="is_featured"
                                   data-label="Featured Products"
                                   aria-label="Show <?= sanitize($p['name']) ?> in Featured Products"
                                   <?= !empty($p['is_featured']) ? 'checked' : '' ?>>
                            <span class="flag-switch-track"><span class="flag-switch-thumb"></span></span>
                        </label>
                    </td>
                    <td class="actions">
                        <a href="<?= admin_url('/?view=products&sub=edit&id=' . $p['id']) ?>" class="admin-btn admin-btn-primary admin-btn-sm" title="Edit">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <button class="admin-btn admin-btn-danger admin-btn-sm btn-delete"
                                data-url="<?= API_URL ?>?action=product_delete"
                                data-id="<?= $p['id'] ?>" data-type="Product" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/pagination.php'; ?>
