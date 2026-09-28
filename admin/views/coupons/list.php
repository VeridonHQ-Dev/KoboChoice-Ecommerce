<?php
global $db;
$coupons = $db->fetchAll("SELECT * FROM coupons ORDER BY created_at DESC");
?>

<div class="admin-toolbar">
    <h3 style="margin:0;">Coupons (<?= count($coupons) ?>)</h3>
    <a href="<?= admin_url('/?view=coupons&sub=form') ?>" class="admin-btn admin-btn-primary">
        <i class="fa fa-plus"></i> Add Coupon
    </a>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Type</th>
                <th>Value</th>
                <th>Min Order</th>
                <th>Used</th>
                <th>Expires</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($coupons)): ?>
                <tr><td colspan="8" class="admin-empty"><i class="fa fa-ticket"></i> No coupons found</td></tr>
            <?php else: ?>
                <?php foreach ($coupons as $c): ?>
                <tr>
                    <td><strong><?= sanitize($c['code']) ?></strong></td>
                    <td><?= ucfirst($c['type']) ?></td>
                    <td><?= $c['type'] === 'percentage' ? $c['value'] . '%' : format_price((float)$c['value']) ?></td>
                    <td><?= format_price((float)$c['min_order']) ?></td>
                    <td><?= $c['used_count'] ?><?= $c['max_uses'] ? ' / ' . $c['max_uses'] : '' ?></td>
                    <td><?= $c['expires_at'] ? format_date($c['expires_at']) : 'Never' ?></td>
                    <td><span class="badge-status <?= $c['is_active'] ? 'badge-active' : 'badge-cancelled' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td class="actions">
                        <a href="<?= admin_url('/?view=coupons&sub=form&id=' . $c['id']) ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <button class="admin-btn admin-btn-danger admin-btn-sm btn-delete"
                                data-url="<?= API_URL ?>?action=coupon_delete"
                                data-id="<?= $c['id'] ?>" data-type="Coupon">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
