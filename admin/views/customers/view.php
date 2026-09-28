<?php
global $db;

$customerId = (int)($_GET['id'] ?? 0);

if (!$customerId) {
    set_flash('error', 'Invalid customer ID.');
    redirect(admin_url('/?view=customers'));
}

$customer = $db->fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);

if (!$customer) {
    set_flash('error', 'Customer not found.');
    redirect(admin_url('/?view=customers'));
}

$orders = $db->fetchAll(
    "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC",
    [$customerId]
);

$orderCount = count($orders);
$totalSpent = (float)($db->fetch(
    "SELECT COALESCE(SUM(total), 0) as t FROM orders WHERE customer_id = ?",
    [$customerId]
)['t'] ?? 0);
?>

<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
    <h2 style="margin:0;"><i class="fa fa-user"></i> <?= sanitize($customer['name']) ?></h2>
    <a href="<?= admin_url('/?view=customers') ?>" class="admin-btn admin-btn-outline"><i class="fa fa-arrow-left"></i> Back to Customers</a>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
    <div class="admin-card">
        <div class="card-header"><h3>Account Info</h3></div>
        <div style="font-size:13px;">
            <p><strong>ID:</strong> #<?= $customer['id'] ?></p>
            <p><strong>Name:</strong> <?= sanitize($customer['name']) ?></p>
            <p><strong>Email:</strong> <?= sanitize($customer['email']) ?></p>
            <p><strong>Phone:</strong> <?= sanitize($customer['phone'] ?? '--') ?></p>
            <p><strong>Gender:</strong> <?= sanitize(ucfirst($customer['gender'] ?? '--')) ?></p>
            <p><strong>Date of Birth:</strong> <?= $customer['date_of_birth'] ? format_date($customer['date_of_birth'], 'M d, Y') : '--' ?></p>
            <p>
                <strong>Status:</strong>
                <span class="badge-status <?= $customer['status'] === 'active' ? 'badge-active' : 'badge-cancelled' ?>">
                    <?= ucfirst($customer['status']) ?>
                </span>
            </p>
            <p><strong>Registered:</strong> <?= format_date($customer['created_at'], 'M d, Y h:i A') ?></p>
            <p><strong>Google Account:</strong> <?= $customer['google_id'] ? 'Yes' : 'No' ?></p>
        </div>

        <?php if (is_super_admin()): ?>
        <div style="margin-top:15px; display:flex; gap:8px;">
            <?php if ($customer['status'] === 'active'): ?>
                <button class="admin-btn admin-btn-sm btn-toggle"
                        data-url="<?= API_URL ?>?action=admin_customer_disable"
                        data-id="<?= $customer['id'] ?>"
                        data-field="status" data-value="disabled"
                        style="color:#ef4444;border-color:#ef4444;">
                    <i class="fa fa-ban"></i> Disable Account
                </button>
            <?php else: ?>
                <button class="admin-btn admin-btn-sm btn-toggle"
                        data-url="<?= API_URL ?>?action=admin_customer_enable"
                        data-id="<?= $customer['id'] ?>"
                        data-field="status" data-value="active"
                        style="color:#10b981;border-color:#10b981;">
                    <i class="fa fa-check-circle"></i> Reactivate Account
                </button>
            <?php endif; ?>
            <button class="admin-btn admin-btn-danger admin-btn-sm btn-delete"
                    data-url="<?= API_URL ?>?action=admin_customer_delete"
                    data-id="<?= $customer['id'] ?>" data-type="Customer">
                <i class="fa fa-trash"></i> Delete
            </button>
        </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="admin-card">
            <div class="card-header"><h3>Order Stats</h3></div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; font-size:13px;">
                <div>
                    <p style="color:#64748b;margin-bottom:4px;">Total Orders</p>
                    <p style="font-size:22px;font-weight:700;color:#1e293b;"><?= $orderCount ?></p>
                </div>
                <div>
                    <p style="color:#64748b;margin-bottom:4px;">Total Spent</p>
                    <p style="font-size:22px;font-weight:700;color:#2563eb;"><?= format_price($totalSpent) ?></p>
                </div>
            </div>
        </div>

        <?php if (!empty($customer['shipping_address'])): ?>
        <div class="admin-card">
            <div class="card-header"><h3>Shipping Address</h3></div>
            <div style="font-size:13px;">
                <?php if ($customer['shipping_name']): ?>
                    <p><strong><?= sanitize($customer['shipping_name']) ?></strong></p>
                <?php endif; ?>
                <?php if ($customer['shipping_phone']): ?>
                    <p><?= sanitize($customer['shipping_phone']) ?></p>
                <?php endif; ?>
                <p><?= nl2br(sanitize($customer['shipping_address'])) ?></p>
                <p><?= sanitize($customer['shipping_city'] ?? '') ?>, <?= sanitize($customer['shipping_state'] ?? '') ?></p>
                <p><?= sanitize($customer['shipping_country'] ?? '') ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($customer['billing_address'])): ?>
        <div class="admin-card">
            <div class="card-header"><h3>Billing Address</h3></div>
            <div style="font-size:13px;">
                <?php if ($customer['billing_name']): ?>
                    <p><strong><?= sanitize($customer['billing_name']) ?></strong></p>
                <?php endif; ?>
                <?php if ($customer['billing_phone']): ?>
                    <p><?= sanitize($customer['billing_phone']) ?></p>
                <?php endif; ?>
                <p><?= nl2br(sanitize($customer['billing_address'])) ?></p>
                <p><?= sanitize($customer['billing_city'] ?? '') ?>, <?= sanitize($customer['billing_state'] ?? '') ?></p>
                <p><?= sanitize($customer['billing_country'] ?? '') ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">
        <h3>Order History (<?= $orderCount ?>)</h3>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Products</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="7" class="admin-empty"><i class="fa fa-shopping-bag"></i> No orders yet</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong>#<?= $o['id'] ?></strong></td>
                    <td style="font-size:12px;max-width:250px;"><?= nl2br(sanitize($o['product_names'] ?? '')) ?></td>
                    <td><strong style="color:#2563eb;"><?= format_price((float)$o['total']) ?></strong></td>
                    <td><span style="font-size:12px;"><?= sanitize(ucwords(str_replace('_', ' ', $o['payment_method'] ?? 'N/A'))) ?></span></td>
                    <td>
                        <?php
                        $statusColors = [
                            'pending' => '#f59e0b', 'paid' => '#2563eb', 'processing' => '#8b5cf6',
                            'shipped' => '#0ea5e9', 'delivered' => '#10b981', 'completed' => '#0f766e',
                            'cancelled' => '#ef4444',
                        ];
                        $color = $statusColors[$o['status']] ?? '#64748b';
                        ?>
                        <span style="background:<?= $color ?>;color:#fff;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;"><?= ucfirst($o['status']) ?></span>
                    </td>
                    <td style="font-size:12px;"><?= format_date($o['created_at'], 'M d, Y') ?></td>
                    <td class="actions">
                        <a href="<?= admin_url('/?view=order&id=' . $o['id']) ?>" class="admin-btn admin-btn-primary admin-btn-sm" title="View Order">
                            <i class="fa fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
