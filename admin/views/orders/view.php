<?php
global $db;

$orderId = (int)($_GET['id'] ?? 0);
$order = $db->fetch(
    "SELECT o.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
     FROM orders o
     LEFT JOIN customers c ON o.customer_id = c.id
     WHERE o.id = ?",
    [$orderId]
);

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect(admin_url('/?view=orders'));
}

$items = $db->fetchAll(
    "SELECT oi.*, p.image, p.slug
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?",
    [$orderId]
);

$statusSteps = ['pending' => 'Pending', 'paid' => 'Paid', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
$stepIcons   = ['pending' => 'fa-clock-o', 'paid' => 'fa-money', 'processing' => 'fa-cogs', 'shipped' => 'fa-truck', 'delivered' => 'fa-check'];
$statusKeys  = array_keys($statusSteps);
$currentStepIndex = array_search($order['status'], $statusKeys, true);

$customerName  = $order['customer_name'] ?? $order['guest_name'] ?? 'Guest';
$customerInitial = strtoupper(substr($customerName, 0, 1));

$paymentIcons = [
    'pay_on_delivery' => 'fa-truck',
    'bank_transfer'   => 'fa-university',
    'cheque'          => 'fa-file-text-o',
    'apple_pay'       => 'fa-apple',
];
$paymentIcon = $paymentIcons[$order['payment_method'] ?? ''] ?? 'fa-credit-card';

$receiptIsImage = false;
if (!empty($order['payment_receipt'])) {
    $receiptExt = strtolower(pathinfo($order['payment_receipt'], PATHINFO_EXTENSION));
    $receiptIsImage = in_array($receiptExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
}
?>

<!-- ── Order Hero ─────────────────────────────────────────────────────── -->
<div class="order-hero">
    <div class="order-hero-left">
        <a href="<?= admin_url('/?view=orders') ?>" class="order-hero-back"><i class="fa fa-arrow-left"></i> Back to Orders</a>
        <div class="order-hero-title-row">
            <h2>Order #<?= $order['id'] ?></h2>
            <span class="order-status-pill st-<?= sanitize($order['status']) ?>"><i class="fa fa-circle"></i> <?= $order['status'] === 'completed' ? 'Completed/Closed' : ucfirst($order['status']) ?></span>
        </div>
        <div class="order-hero-meta">
            Placed on <?= format_date($order['created_at'], 'M d, Y \a\t h:i A') ?> &middot; <?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?> &middot; <?= sanitize($customerName) ?>
        </div>
    </div>
    <div class="order-hero-right">
        <div class="order-hero-label">Order Total</div>
        <div class="order-hero-amount"><?= format_price((float)$order['total']) ?></div>
    </div>
</div>

<?php /* Single column from here down: every card is full width and read top to
         bottom, in the order an admin works through one — what was bought,
         then the action, then who/where/how, then any note. */ ?>
<div class="order-stack">

        <!-- ── Order Items ────────────────────────────────────────────── -->
        <div class="order-card">
            <div class="order-card-header">
                <div class="order-card-icon ic-blue"><i class="fa fa-cube"></i></div>
                <h3>Order Items</h3>
            </div>
            <table class="order-items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="order-item-product">
                                <?php if ($item['image']): ?>
                                    <img src="<?= PRODUCT_IMG_URL . '/' . $item['image'] ?>" class="order-item-thumb" alt="">
                                <?php else: ?>
                                    <div class="order-item-thumb-placeholder"><i class="fa fa-cube"></i></div>
                                <?php endif; ?>
                                <div>
                                    <span class="order-item-name"><?= sanitize($item['name']) ?></span>
                                    <?php if ($item['slug']): ?>
                                        <a href="../single-product.html?slug=<?= $item['slug'] ?>" target="_blank" class="order-item-link">View product</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="text-right"><?= format_price((float)$item['price']) ?></td>
                        <td class="text-right"><?= $item['quantity'] ?></td>
                        <td class="text-right"><strong><?= format_price((float)$item['price'] * $item['quantity']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <?php if ((float)($order['tax'] ?? 0) > 0): ?>
                    <tr>
                        <td colspan="3">Tax</td>
                        <td class="text-right"><?= format_price((float)$order['tax']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="order-total-row">
                        <td colspan="3">Order Total</td>
                        <td class="text-right"><span class="order-total-amount"><?= format_price((float)$order['total']) ?></span></td>
                    </tr>
                </tfoot>
            </table>

            <!-- ── Status Tracker ─────────────────────────────────────── -->
            <?php if ($order['status'] === 'cancelled'): ?>
                <div class="order-cancelled-banner">
                    <i class="fa fa-times-circle"></i>
                    <span>This order has been cancelled.</span>
                </div>
            <?php elseif ($order['status'] === 'completed'): ?>
                <div class="order-completed-banner">
                    <i class="fa fa-check-circle"></i>
                    <span>This order has been completed and closed.</span>
                </div>
            <?php else: ?>
                <div class="order-tracker">
                    <?php foreach ($statusSteps as $key => $label):
                        $stepIndex = array_search($key, $statusKeys, true);
                        $stateClass = '';
                        if ($currentStepIndex !== false) {
                            if ($stepIndex < $currentStepIndex) $stateClass = 'done';
                            elseif ($stepIndex === $currentStepIndex) $stateClass = 'current';
                        }
                    ?>
                        <div class="order-tracker-step <?= $stateClass ?>">
                            <div class="order-tracker-circle">
                                <i class="fa <?= $stateClass === 'done' ? 'fa-check' : $stepIcons[$key] ?>"></i>
                            </div>
                            <div class="order-tracker-label"><?= $label ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── Status Update ──────────────────────────────────────────── -->
        <div class="order-card">
            <div class="order-card-header">
                <div class="order-card-icon ic-indigo"><i class="fa fa-refresh"></i></div>
                <h3>Update Status</h3>
            </div>
            <div class="order-card-body">
                <select id="orderStatus" class="order-status-select">
                    <?php foreach (['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'completed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= $s === 'completed' ? 'Completed/Closed' : ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <button onclick="updateOrderStatus()" class="admin-btn admin-btn-primary order-update-btn">
                    <i class="fa fa-save"></i> Update Status
                </button>
            </div>
        </div>

        <!-- ── Customer ────────────────────────────────────────────────── -->
        <div class="order-card">
            <div class="order-card-header">
                <div class="order-card-icon ic-blue"><i class="fa fa-user"></i></div>
                <h3>Customer</h3>
            </div>
            <div class="order-card-body">
                <div class="order-customer-head">
                    <div class="order-customer-avatar"><?= sanitize($customerInitial) ?></div>
                    <div>
                        <div class="order-customer-name"><?= sanitize($customerName) ?></div>
                        <div class="order-customer-sub"><?= $order['customer_id'] ? 'Registered Customer' : 'Guest Checkout' ?></div>
                    </div>
                </div>
                <div class="order-info-row"><i class="fa fa-envelope"></i> <?= sanitize($order['email'] ?? '--') ?></div>
                <div class="order-info-row"><i class="fa fa-phone"></i> <?= sanitize($order['phone'] ?? '--') ?></div>
                <?php if ($order['customer_id']): ?>
                    <a href="<?= admin_url('/?view=customers&sub=view&id=' . $order['customer_id']) ?>" class="order-view-link">
                        <i class="fa fa-external-link"></i> View Full Profile
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Shipping ────────────────────────────────────────────────── -->
        <div class="order-card">
            <div class="order-card-header">
                <div class="order-card-icon ic-green"><i class="fa fa-map-marker"></i></div>
                <h3>Shipping Address</h3>
            </div>
            <div class="order-card-body">
                <div class="order-address-block">
                    <?= nl2br(sanitize($order['shipping_address'] ?? '--')) ?><br>
                    <?= sanitize($order['city'] ?? '') ?><?= $order['city'] && $order['state'] ? ', ' : '' ?><?= sanitize($order['state'] ?? '') ?><br>
                    <strong><?= sanitize($order['country'] ?? '') ?></strong>
                </div>
            </div>
        </div>

        <!-- ── Payment ─────────────────────────────────────────────────── -->
        <div class="order-card">
            <div class="order-card-header">
                <div class="order-card-icon ic-purple"><i class="fa fa-credit-card"></i></div>
                <h3>Payment</h3>
            </div>
            <div class="order-card-body">
                <span class="order-payment-method">
                    <i class="fa <?= $paymentIcon ?>"></i>
                    <?= sanitize(ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'N/A'))) ?>
                </span>

                <?php if (!empty($order['payment_receipt'])): ?>
                    <?php if ($receiptIsImage): ?>
                        <div class="order-receipt-preview">
                            <a href="<?= PRODUCT_IMG_URL . '/' . $order['payment_receipt'] ?>" target="_blank">
                                <img src="<?= PRODUCT_IMG_URL . '/' . $order['payment_receipt'] ?>" alt="Payment receipt">
                            </a>
                            <a href="<?= PRODUCT_IMG_URL . '/' . $order['payment_receipt'] ?>" target="_blank" class="admin-btn admin-btn-outline admin-btn-sm admin-btn-block">
                                <i class="fa fa-external-link"></i> Open Full Size
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="order-receipt-doc">
                            <i class="fa fa-file-pdf-o"></i>
                            <span>Payment receipt (PDF)</span>
                            <a href="<?= PRODUCT_IMG_URL . '/' . $order['payment_receipt'] ?>" target="_blank" class="admin-btn admin-btn-primary admin-btn-sm">
                                <i class="fa fa-download"></i> View
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Order Note ──────────────────────────────────────────────── -->
        <?php if ($order['note']): ?>
        <div class="order-card">
            <div class="order-card-header">
                <div class="order-card-icon ic-amber"><i class="fa fa-sticky-note"></i></div>
                <h3>Order Note</h3>
            </div>
            <div class="order-card-body">
                <p class="order-note-text"><?= nl2br(sanitize($order['note'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
</div>

<script>
function updateOrderStatus() {
    var status = document.getElementById('orderStatus').value;
    ajaxPost('<?= API_URL ?>?action=admin_order_update', { id: <?= $order['id'] ?>, status: status }, function(res) {
        if (res.success) showAlert('success', 'Order updated successfully.');
        else showAlert('danger', res.message);
    });
}
</script>
