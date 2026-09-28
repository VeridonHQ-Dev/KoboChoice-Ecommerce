<?php
global $db;

$reviews = $db->fetchAll(
    "SELECT r.*, p.name AS product_name, c.name AS customer_name
     FROM reviews r
     LEFT JOIN products p ON r.product_id = p.id
     LEFT JOIN customers c ON r.customer_id = c.id
     ORDER BY r.created_at DESC
     LIMIT 50"
);
?>

<div class="admin-toolbar">
    <h3 style="margin:0;">Product Reviews</h3>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Customer</th>
                <th>Rating</th>
                <th>Comment</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reviews)): ?>
                <tr><td colspan="7" class="admin-empty"><i class="fa fa-star"></i> No reviews yet</td></tr>
            <?php else: ?>
                <?php foreach ($reviews as $r): ?>
                <tr>
                    <td><?= sanitize(truncate($r['product_name'] ?? '', 30)) ?></td>
                    <td><?= sanitize($r['customer_name'] ?? '') ?></td>
                    <td>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fa fa-star" style="color:<?= $i <= $r['rating'] ? '#f59e0b' : '#e2e8f0'; ?>;"></i>
                        <?php endfor; ?>
                    </td>
                    <td><?= sanitize(truncate($r['comment'] ?? '', 50)) ?></td>
                    <td><span class="badge-status <?= $r['is_approved'] ? 'badge-active' : 'badge-pending' ?>"><?= $r['is_approved'] ? 'Approved' : 'Pending' ?></span></td>
                    <td><?= format_date($r['created_at']) ?></td>
                    <td class="actions">
                        <?php if (!$r['is_approved']): ?>
                        <button class="admin-btn admin-btn-success admin-btn-sm"
                                onclick="approveReview(<?= $r['id'] ?>)">
                            <i class="fa fa-check"></i>
                        </button>
                        <?php endif; ?>
                        <button class="admin-btn admin-btn-danger admin-btn-sm btn-delete"
                                data-url="<?= API_URL ?>?action=review_delete"
                                data-id="<?= $r['id'] ?>" data-type="Review">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function approveReview(id) {
    ajaxPost('<?= API_URL ?>?action=review_approve', { id: id }, function(res) {
        if (res.success) location.reload();
        else showAlert('danger', res.message);
    });
}
</script>
