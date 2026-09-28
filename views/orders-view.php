<?php
$customerId = $_SESSION['customer_id'];
$pagination = paginate($db, 'orders', 'customer_id = ?', [$customerId]);

$orders = $db->fetchAll(
    "SELECT * FROM orders WHERE customer_id = ?
     ORDER BY created_at DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    [$customerId]
);

$statusClasses = [
    'pending'     => 'badge-warning',
    'paid'        => 'badge-info',
    'processing'  => 'badge-primary',
    'shipped'     => 'badge-secondary',
    'delivered'   => 'badge-success',
    'cancelled'   => 'badge-danger',
];
?>
<style>
.status-badge-custom {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12.5px;
    font-weight: 600;
    text-transform: capitalize;
    text-align: center;
}
.status-badge-custom.pending { background: #fef3c7; color: #d97706; }
.status-badge-custom.paid { background: #dbeafe; color: #2563eb; }
.status-badge-custom.processing { background: #f3e8ff; color: #9333ea; }
.status-badge-custom.shipped { background: #cffafe; color: #0891b2; }
.status-badge-custom.delivered { background: #dcfce7; color: #16a34a; }
.status-badge-custom.completed { background: #ccfbf1; color: #0f766e; }
.status-badge-custom.cancelled { background: #fee2e2; color: #dc2626; }

/* Adjust View button sizing on orders page */
.table .li-button.li-button-dark {
    line-height: 38px;
    height: 38px;
    padding: 0 20px;
    font-size: 13px;
    margin: 0;
}
</style>

<div class=" Shopping-cart-area pt-60 pb-60">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h3 class="mb-30">My Orders</h3>
                <?php if (!empty($orders)): ?>
                <div class="table-content table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo (int)$order['id']; ?></td>
                                <td><?php echo format_date($order['created_at']); ?></td>
                                <td><?php echo format_price((float)$order['total']); ?></td>
                                <td>
                                    <?php $status = $order['status']; ?>
                                    <span class="status-badge-custom <?php echo $status; ?>"><?php echo sanitize(ucfirst($status)); ?></span>
                                </td>
                                <td>
                                    <a href="<?php echo base_url('/order-detail?id=' . (int)$order['id']); ?>" class="li-button li-button-dark">
                                        <span>View</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-30">
                    <?php echo render_pagination($pagination, base_url('/orders')); ?>
                </div>
                <?php else: ?>
                <div class="text-center pt-40 pb-40">
                    <h3>You have no orders yet.</h3>
                    <p class="mt-20"><a href="<?php echo base_url('/shop'); ?>" class="li-button">Start Shopping</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
