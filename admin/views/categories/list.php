<?php
global $db;

$categories = $db->fetchAll(
    "SELECT c.*, p.name AS parent_name,
     (SELECT COUNT(*) FROM products WHERE category_id = c.id) AS product_count
     FROM categories c
     LEFT JOIN categories p ON c.parent_id = p.id
     ORDER BY c.sort_order ASC, c.name ASC"
);
?>

<div class="admin-toolbar">
    <h3 style="margin:0;">All Categories (<?= count($categories) ?>)</h3>
    <a href="<?= admin_url('/?view=categories&sub=create') ?>" class="admin-btn admin-btn-primary">
        <i class="fa fa-plus"></i> Add Category
    </a>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Parent</th>
                <th>Products</th>
                <th>Menu</th>
                <th>Sidebar</th>
                <th>Sort</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
                <tr><td colspan="7" class="admin-empty"><i class="fa fa-tags"></i> No categories found</td></tr>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><strong><?= sanitize($cat['name']) ?></strong></td>
                    <td><?= $cat['parent_name'] ? sanitize($cat['parent_name']) : '<span style="color:#94a3b8;">--</span>' ?></td>
                    <td><?= $cat['product_count'] ?></td>
                    <td><?= $cat['show_in_menu'] ? '<i class="fa fa-check" style="color:#16a34a;"></i>' : '<i class="fa fa-times" style="color:#dc2626;"></i>' ?></td>
                    <td><?= $cat['show_in_sidebar'] ? '<i class="fa fa-check" style="color:#16a34a;"></i>' : '<i class="fa fa-times" style="color:#dc2626;"></i>' ?></td>
                    <td><?= $cat['sort_order'] ?></td>
                    <td class="actions">
                        <a href="<?= admin_url('/?view=categories&sub=edit&id=' . $cat['id']) ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <button class="admin-btn admin-btn-danger admin-btn-sm btn-delete"
                                data-url="<?= API_URL ?>?action=category_delete"
                                data-id="<?= $cat['id'] ?>" data-type="Category">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
