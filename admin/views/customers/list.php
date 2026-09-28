<?php
global $db;

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = '1';
$params = [];

if (!empty($search)) {
    $where .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (in_array($statusFilter, ['active', 'disabled'], true)) {
    $where .= ' AND c.status = ?';
    $params[] = $statusFilter;
}

$pagination = paginate($db, 'customers c', $where, $params, ADMIN_ITEMS_PER_PAGE);

$customers = $db->fetchAll(
    "SELECT c.*, (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) as order_count
     FROM customers c WHERE $where ORDER BY c.created_at DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

$baseUrl = admin_url('/?view=customers') . (!empty($search) ? '&search=' . urlencode($search) : '');
$baseUrl .= (!empty($statusFilter) ? '&status=' . urlencode($statusFilter) : '');
?>

<div class="admin-toolbar">
    <form method="GET" class="search-box" style="display:flex;gap:8px;align-items:center;">
        <input type="hidden" name="view" value="customers">
        <input type="text" name="search" placeholder="Search by name, email, or phone..." value="<?= sanitize($search) ?>" style="min-width:220px;">
        <select name="status" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;">
            <option value="">All Status</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="disabled" <?= $statusFilter === 'disabled' ? 'selected' : '' ?>>Disabled</option>
        </select>
        <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm"><i class="fa fa-search"></i></button>
        <?php if (!empty($search) || !empty($statusFilter)): ?>
            <a href="<?= admin_url('/?view=customers') ?>" class="admin-btn admin-btn-sm" style="color:#64748b;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Orders</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr><td colspan="8" class="admin-empty"><i class="fa fa-users"></i> No customers found</td></tr>
            <?php else: ?>
                <?php foreach ($customers as $c): ?>
                <tr>
                    <td>#<?= $c['id'] ?></td>
                    <td><strong><?= sanitize($c['name']) ?></strong></td>
                    <td><?= sanitize($c['email']) ?></td>
                    <td><?= sanitize($c['phone'] ?? '--') ?></td>
                    <td><?= (int)$c['order_count'] ?></td>
                    <td>
                        <span class="badge-status <?= $c['status'] === 'active' ? 'badge-active' : 'badge-cancelled' ?>">
                            <?= ucfirst($c['status']) ?>
                        </span>
                    </td>
                    <td><?= format_date($c['created_at']) ?></td>
                    <td class="actions">
                        <a href="<?= admin_url('/?view=customers&sub=view&id=' . $c['id']) ?>" class="admin-btn admin-btn-primary admin-btn-sm" title="View Details">
                            <i class="fa fa-eye"></i>
                        </a>
                        <?php if (is_super_admin()): ?>
                            <?php if ($c['status'] === 'active'): ?>
                                <button class="admin-btn admin-btn-sm btn-toggle"
                                        data-url="<?= API_URL ?>?action=admin_customer_disable"
                                        data-id="<?= $c['id'] ?>"
                                        data-field="status" data-value="disabled"
                                        title="Disable Account" style="color:#ef4444;border-color:#ef4444;">
                                    <i class="fa fa-ban"></i>
                                </button>
                            <?php else: ?>
                                <button class="admin-btn admin-btn-sm btn-toggle"
                                        data-url="<?= API_URL ?>?action=admin_customer_enable"
                                        data-id="<?= $c['id'] ?>"
                                        data-field="status" data-value="active"
                                        title="Reactivate Account" style="color:#10b981;border-color:#10b981;">
                                    <i class="fa fa-check-circle"></i>
                                </button>
                            <?php endif; ?>
                            <button class="admin-btn admin-btn-danger admin-btn-sm btn-delete"
                                    data-url="<?= API_URL ?>?action=admin_customer_delete"
                                    data-id="<?= $c['id'] ?>" data-type="Customer"
                                    title="Delete Permanently">
                                <i class="fa fa-trash"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/pagination.php'; ?>
