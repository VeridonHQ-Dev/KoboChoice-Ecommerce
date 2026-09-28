<?php
$pageTitle = 'Dashboard';
require __DIR__ . '/partials/sidebar.php';
?>

<div class="admin-main">
    <?php require __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">
        <?php display_flash(); ?>

        <!-- ── Dashboard Welcome ──────────────────────────────────────── -->
        <div class="dashboard-welcome" id="dashboardWelcome">
            <div class="dashboard-welcome-text">
                <h2>Welcome back, <?= sanitize($_SESSION['admin_name'] ?? 'Admin') ?> 👋</h2>
                <p>Here's what's happening with your store today.</p>
            </div>
            <div class="dashboard-welcome-date">
                <i class="fa fa-calendar"></i>
                <span><?= date('l, F j, Y') ?></span>
            </div>
        </div>

        <!-- ── Stats Cards Grid (12 Cards) ────────────────────────────── -->
        <div class="dashboard-stats-grid" id="dashboardStatsGrid">

            <!-- Card 1: Total Products -->
            <div class="dashboard-stat-card" style="--card-accent: #2563eb; --card-gradient: linear-gradient(135deg, #1d4ed8 0%, #2563eb 55%, #60a5fa 100%); --card-index: 0;">
                <div class="dashboard-stat-icon">
                    <i class="fa fa-cube"></i>
                </div>
                <div class="dashboard-stat-body">
                    <h3 class="dashboard-stat-number" id="statTotalProducts" data-count="0">--</h3>
                    <p class="dashboard-stat-label">Total Products</p>
                </div>
                <div class="dashboard-stat-accent"></div>
            </div>

            <!-- Card 2: Total Orders -->
            <div class="dashboard-stat-card" style="--card-accent: #7c3aed; --card-gradient: linear-gradient(135deg, #6d28d9 0%, #7c3aed 55%, #a78bfa 100%); --card-index: 1;">
                <div class="dashboard-stat-icon">
                    <i class="fa fa-shopping-cart"></i>
                </div>
                <div class="dashboard-stat-body">
                    <h3 class="dashboard-stat-number" id="statTotalOrders" data-count="0">--</h3>
                    <p class="dashboard-stat-label">Total Orders</p>
                </div>
                <div class="dashboard-stat-accent"></div>
            </div>

            <!-- Card 3: All Customers -->
            <div class="dashboard-stat-card" style="--card-accent: #0891b2; --card-gradient: linear-gradient(135deg, #0e7490 0%, #0891b2 55%, #22d3ee 100%); --card-index: 2;">
                <div class="dashboard-stat-icon">
                    <i class="fa fa-users"></i>
                </div>
                <div class="dashboard-stat-body">
                    <h3 class="dashboard-stat-number" id="statTotalCustomers" data-count="0">--</h3>
                    <p class="dashboard-stat-label">All Customers</p>
                </div>
                <div class="dashboard-stat-accent"></div>
            </div>

            <!-- Card 4: Total Revenue -->
            <div class="dashboard-stat-card" style="--card-accent: #16a34a; --card-gradient: linear-gradient(135deg, #15803d 0%, #16a34a 55%, #4ade80 100%); --card-index: 3;">
                <div class="dashboard-stat-icon">
                    <i class="fa fa-money"></i>
                </div>
                <div class="dashboard-stat-body">
                    <h3 class="dashboard-stat-number" id="statTotalRevenue">--</h3>
                    <p class="dashboard-stat-label">Total Revenue</p>
                </div>
                <div class="dashboard-stat-accent"></div>
            </div>

            <!-- Card 5: Total Admins -->
            <div class="dashboard-stat-card" style="--card-accent: #ea580c; --card-gradient: linear-gradient(135deg, #c2410c 0%, #ea580c 55%, #fb923c 100%); --card-index: 4;">
                <div class="dashboard-stat-icon">
                    <i class="fa fa-user-secret"></i>
                </div>
                <div class="dashboard-stat-body">
                    <h3 class="dashboard-stat-number" id="statTotalAdmins" data-count="0">--</h3>
                    <p class="dashboard-stat-label">Total Admins</p>
                </div>
                <div class="dashboard-stat-accent"></div>
            </div>

            <!-- Card 6: Total Categories -->
            <div class="dashboard-stat-card" style="--card-accent: #d946ef; --card-gradient: linear-gradient(135deg, #a21caf 0%, #d946ef 55%, #f0abfc 100%); --card-index: 5;">
                <div class="dashboard-stat-icon">
                    <i class="fa fa-tags"></i>
                </div>
                <div class="dashboard-stat-body">
                    <h3 class="dashboard-stat-number" id="statTotalCategories" data-count="0">--</h3>
                    <p class="dashboard-stat-label">Total Categories</p>
                </div>
                <div class="dashboard-stat-accent"></div>
            </div>

            <!-- Card 7: Approved Products -->
            <div class="dashboard-stat-card" style="--card-accent: #059669; --card-gradient: linear-gradient(135deg, #047857 0%, #059669 55%, #34d399 100%); --card-index: 6;">
                <div class="dashboard-stat-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <div class="dashboard-stat-body">
                    <h3 class="dashboard-stat-number" id="statApprovedProducts" data-count="0">--</h3>
                    <p class="dashboard-stat-label">Approved Products</p>
                </div>
                <div class="dashboard-stat-accent"></div>
            </div>

            <!-- Card 8: Out of Stock -->
            <div class="dashboard-stat-card" style="--card-accent: #dc2626; --card-gradient: linear-gradient(135deg, #b91c1c 0%, #dc2626 55%, #f87171 100%); --card-index: 7;">
                <div class="dashboard-stat-icon">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <div class="dashboard-stat-body">
                    <h3 class="dashboard-stat-number" id="statOutOfStock" data-count="0">--</h3>
                    <p class="dashboard-stat-label">Out of Stock</p>
                </div>
                <div class="dashboard-stat-accent"></div>
            </div>

            <!-- Card 9: Cancelled Orders -->
            <div class="dashboard-stat-card" style="--card-accent: #be123c; --card-gradient: linear-gradient(135deg, #9f1239 0%, #be123c 55%, #fb7185 100%); --card-index: 8;">
                <div class="dashboard-stat-icon">
                    <i class="fa fa-times-circle"></i>
                </div>
                <div class="dashboard-stat-body">
                    <h3 class="dashboard-stat-number" id="statCancelledOrders" data-count="0">--</h3>
                    <p class="dashboard-stat-label">Cancelled Orders</p>
                </div>
                <div class="dashboard-stat-accent"></div>
            </div>

            <!-- Card 10: Pending Orders -->
            <div class="dashboard-stat-card" style="--card-accent: #d97706; --card-gradient: linear-gradient(135deg, #b45309 0%, #d97706 55%, #fbbf24 100%); --card-index: 9;">
                <div class="dashboard-stat-icon">
                    <i class="fa fa-clock-o"></i>
                </div>
                <div class="dashboard-stat-body">
                    <h3 class="dashboard-stat-number" id="statPendingOrders" data-count="0">--</h3>
                    <p class="dashboard-stat-label">Pending Orders</p>
                </div>
                <div class="dashboard-stat-accent"></div>
            </div>

            <!-- Card 11: Delivered Orders -->
            <div class="dashboard-stat-card" style="--card-accent: #0d9488; --card-gradient: linear-gradient(135deg, #0f766e 0%, #0d9488 55%, #2dd4bf 100%); --card-index: 10;">
                <div class="dashboard-stat-icon">
                    <i class="fa fa-truck"></i>
                </div>
                <div class="dashboard-stat-body">
                    <h3 class="dashboard-stat-number" id="statDeliveredOrders" data-count="0">--</h3>
                    <p class="dashboard-stat-label">Delivered Orders</p>
                </div>
                <div class="dashboard-stat-accent"></div>
            </div>

            <!-- Card 12: Low Stock Alert -->
            <div class="dashboard-stat-card" style="--card-accent: #b45309; --card-gradient: linear-gradient(135deg, #92400e 0%, #b45309 55%, #f59e0b 100%); --card-index: 11;">
                <div class="dashboard-stat-icon">
                    <i class="fa fa-battery-quarter"></i>
                </div>
                <div class="dashboard-stat-body">
                    <h3 class="dashboard-stat-number" id="statLowStock" data-count="0">--</h3>
                    <p class="dashboard-stat-label">Low Stock Products</p>
                </div>
                <div class="dashboard-stat-accent"></div>
            </div>

        </div>

        <!-- ── Recent Orders Table ────────────────────────────────────── -->
        <div class="dashboard-recent-orders" id="dashboardRecentOrders">
            <div class="dashboard-orders-header">
                <div class="dashboard-orders-title">
                    <i class="fa fa-list-alt"></i>
                    <h3>Recent Orders</h3>
                    <span class="dashboard-orders-subtitle">Last 10 orders placed by customers</span>
                </div>
                <a href="<?= admin_url('/?view=orders') ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                    <i class="fa fa-external-link"></i> View All Orders
                </a>
            </div>
            <div class="dashboard-table-wrapper">
                <table class="admin-table dashboard-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="recentOrdersBody">
                        <tr><td colspan="7" class="admin-empty"><i class="fa fa-spinner fa-spin"></i> Loading orders...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    /* ── Animated count-up helper ──────────────────────────────────── */
    function animateCount(element, target, duration) {
        if (isNaN(target)) { element.textContent = target; return; }
        const start = 0;
        const startTime = performance.now();
        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            // Ease-out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(start + (target - start) * eased);
            element.textContent = current.toLocaleString();
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }

    /* ── Fetch dashboard stats ─────────────────────────────────────── */
    fetch('<?= API_URL ?>?action=dashboard_stats')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const mappings = [
                    { id: 'statTotalProducts',    key: 'total_products' },
                    { id: 'statTotalOrders',      key: 'total_orders' },
                    { id: 'statTotalCustomers',   key: 'total_customers' },
                    { id: 'statTotalAdmins',      key: 'total_admins' },
                    { id: 'statTotalCategories',  key: 'total_categories' },
                    { id: 'statApprovedProducts', key: 'approved_products' },
                    { id: 'statOutOfStock',       key: 'out_of_stock' },
                    { id: 'statCancelledOrders',  key: 'cancelled_orders' },
                    { id: 'statPendingOrders',    key: 'pending_orders' },
                    { id: 'statDeliveredOrders',  key: 'delivered_orders' },
                    { id: 'statLowStock',         key: 'low_stock_count' },
                ];

                mappings.forEach(m => {
                    const el = document.getElementById(m.id);
                    if (el) animateCount(el, parseInt(res[m.key]) || 0, 1200);
                });

                // Revenue with currency symbol
                const revEl = document.getElementById('statTotalRevenue');
                if (revEl) {
                    const revValue = Number(res.total_revenue) || 0;
                    revEl.textContent = '<?= CURRENCY_SYMBOL ?>' + revValue.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0});
                }
            }
        })
        .catch(() => {
            document.querySelectorAll('.dashboard-stat-number').forEach(el => {
                el.textContent = '0';
            });
        });

    /* ── Fetch recent orders ───────────────────────────────────────── */
    fetch('<?= API_URL ?>?action=recent_orders')
        .then(r => r.json())
        .then(res => {
            const tbody = document.getElementById('recentOrdersBody');
            if (res.success && res.orders.length > 0) {
                tbody.innerHTML = '';
                res.orders.forEach((o, i) => {
                    const customerName = o.customer_name || o.guest_name || o.email || 'Guest';
                    const paymentMethod = o.payment_method ? o.payment_method.replace(/_/g, ' ') : 'N/A';
                    const orderDate = new Date(o.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                    // data-label feeds the mobile card layout, where the table
                    // head is hidden and each cell prints its own column name.
                    tbody.innerHTML += `
                        <tr style="animation: dashTableRowIn 0.4s ease-out ${i * 0.05}s both;">
                            <td data-label="Order #"><a href="<?= admin_url('/?view=order&id=') ?>${o.id}" class="dashboard-order-link">#${o.id}</a></td>
                            <td data-label="Customer">
                                <div class="dashboard-customer-cell">
                                    <span class="dashboard-customer-avatar">${customerName.charAt(0).toUpperCase()}</span>
                                    <span>${customerName}</span>
                                </div>
                            </td>
                            <td class="dashboard-amount" data-label="Amount"><?= CURRENCY_SYMBOL ?>${Number(o.total).toLocaleString()}</td>
                            <td data-label="Payment"><span class="dashboard-payment-badge">${paymentMethod}</span></td>
                            <td data-label="Status"><span class="badge-status badge-${o.status}">${o.status}</span></td>
                            <td class="dashboard-date" data-label="Date">${orderDate}</td>
                            <td data-label="Action"><a href="<?= admin_url('/?view=order&id=') ?>${o.id}" class="admin-btn admin-btn-outline admin-btn-sm" title="View"><i class="fa fa-eye"></i> View</a></td>
                        </tr>`;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="admin-empty"><i class="fa fa-inbox" style="font-size:32px;margin-bottom:8px;display:block;"></i>No orders yet</td></tr>';
            }
        })
        .catch(() => {
            document.getElementById('recentOrdersBody').innerHTML = '<tr><td colspan="7" class="admin-empty">Failed to load orders</td></tr>';
        });
});
</script>
