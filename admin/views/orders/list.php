<?php
global $db;

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$where = "1";
$params = [];

if (!empty($status)) {
    $where .= " AND o.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $where .= " AND (o.id LIKE ? OR o.email LIKE ? OR o.guest_name LIKE ? OR c.name LIKE ?)";
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$pagination = paginate($db, 'orders o', $where, $params, ADMIN_ITEMS_PER_PAGE);

$orders = $db->fetchAll(
    "SELECT o.*, c.name AS customer_name
     FROM orders o
     LEFT JOIN customers c ON o.customer_id = c.id
     WHERE $where
     ORDER BY o.created_at DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

$baseUrl = admin_url('/?view=orders');
if (!empty($status)) $baseUrl .= '&status=' . urlencode($status);
if (!empty($search)) $baseUrl .= '&search=' . urlencode($search);
?>

<div class="admin-toolbar">
    <form method="GET" class="search-box">
        <input type="hidden" name="view" value="orders">
        <select name="status" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;">
            <option value="">All Status</option>
            <?php foreach (['pending','paid','processing','shipped','delivered','cancelled','completed'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="search" placeholder="Search orders..." value="<?= sanitize($search) ?>">
        <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm"><i class="fa fa-search"></i></button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Total</th>
                <th>Status</th>
                <th>Receipt</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="8" class="admin-empty"><i class="fa fa-shopping-cart"></i> No orders found</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong>#<?= $o['id'] ?></strong></td>
                    <td><?= sanitize($o['customer_name'] ?? $o['guest_name'] ?? 'Guest') ?></td>
                    <td><?= sanitize($o['email'] ?? '') ?></td>
                    <td><strong><?= format_price((float)$o['total']) ?></strong></td>
                    <td>
                        <select class="order-status-select" data-order-id="<?= $o['id'] ?>"
                                style="padding:4px 8px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;">
                            <?php foreach (['pending','paid','processing','shipped','delivered','cancelled','completed'] as $s): ?>
                                <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <?php if (!empty($o['payment_receipt'])):
                            $receiptUrl = PRODUCT_IMG_URL . '/' . rawurlencode($o['payment_receipt']);
                            $receiptExt = strtolower(pathinfo($o['payment_receipt'], PATHINFO_EXTENSION));
                            $receiptName = 'receipt-order-' . $o['id'] . '.' . $receiptExt;
                        ?>
                            <a href="<?= sanitize($receiptUrl) ?>" target="_blank" rel="noopener"
                               class="admin-btn admin-btn-outline admin-btn-sm" title="View receipt">
                                <i class="fa fa-eye"></i>
                            </a>
                            <a href="<?= sanitize($receiptUrl) ?>" download="<?= sanitize($receiptName) ?>"
                               class="admin-btn admin-btn-primary admin-btn-sm" title="Download receipt" style="margin-left:4px;">
                                <i class="fa fa-download"></i>
                            </a>
                        <?php else: ?>
                            <span style="color:#94a3b8;font-size:12px;">&mdash;</span>
                        <?php endif; ?>
                    </td>
                    <td><?= format_date($o['created_at']) ?></td>
                    <td class="actions">
                        <a href="<?= admin_url('/?view=order&id=' . $o['id']) ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fa fa-eye"></i>
                        </a>
                        <button type="button" class="admin-btn admin-btn-danger admin-btn-sm delete-order-btn" data-id="<?= $o['id'] ?>" style="margin-left:4px;">
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

<script>
document.querySelectorAll('.order-status-select').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var orderId = this.getAttribute('data-order-id');
        var status = this.value;
        ajaxPost('<?= API_URL ?>?action=admin_order_update', { id: orderId, status: status }, function(res) {
            if (res.success) showAlert('success', 'Order #' + orderId + ' updated to ' + status);
            else showAlert('danger', res.message);
        });
    });
});

document.querySelectorAll('.delete-order-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var orderId = this.getAttribute('data-id');
        if (!confirm('Delete order #' + orderId + '? This cannot be undone.')) return;
        ajaxPost('<?= API_URL ?>?action=admin_order_delete', { id: orderId }, function(res) {
            if (res.success) {
                showAlert('success', 'Order #' + orderId + ' deleted.');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                showAlert('danger', res.message);
            }
        });
    });
});
</script>
