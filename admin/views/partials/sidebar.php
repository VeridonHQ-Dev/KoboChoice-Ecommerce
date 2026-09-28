<?php
$currentView = $_GET['view'] ?? 'dashboard';
$currentSub = $_GET['sub'] ?? 'list';

/* Orders badge = orders no admin has opened yet.
 *
 * This used to count status = 'pending', which never emptied: an order
 * awaiting a bank transfer sits at 'pending' indefinitely, so the badge showed
 * the same number forever no matter how often it had been reviewed. "Nobody
 * has looked at this yet" is the question a notification badge should answer,
 * and orders.seen_at records it — see db/migration_order_seen.sql. */
$unseenOrders = $db->count('orders', 'seen_at IS NULL');
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <h3>Kobo Choice</h3>
            <p>Admin Panel</p>
        </div>
        <?php /* Only shown below 992px, where the sidebar slides over the page
                 and needs a way out. On desktop it is permanent, so there is
                 nothing to close. */ ?>
        <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close menu">
            <i class="fa fa-times"></i>
        </button>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="<?= admin_url('/') ?>" class="nav-link <?= $currentView === 'dashboard' ? 'active' : '' ?>">
                <i class="fa fa-tachometer"></i> Dashboard
            </a>
            <a href="<?= admin_url('/?view=cms') ?>" class="nav-link <?= $currentView === 'cms' ? 'active' : '' ?>">
                <i class="fa fa-picture-o"></i> CMS
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Catalog</div>
            <a href="<?= admin_url('/?view=products') ?>" class="nav-link <?= $currentView === 'products' ? 'active' : '' ?>">
                <i class="fa fa-cube"></i> Products
            </a>
            <a href="<?= admin_url('/?view=categories') ?>" class="nav-link <?= $currentView === 'categories' ? 'active' : '' ?>">
                <i class="fa fa-tags"></i> Categories
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Sales</div>
            <a href="<?= admin_url('/?view=orders') ?>" class="nav-link <?= $currentView === 'orders' || $currentView === 'order' ? 'active' : '' ?>">
                <i class="fa fa-shopping-cart"></i> Orders
                <?php if ($unseenOrders > 0): ?>
                    <span class="badge" title="<?= $unseenOrders ?> new order<?= $unseenOrders === 1 ? '' : 's' ?> not opened yet"><?= $unseenOrders ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= admin_url('/?view=coupons') ?>" class="nav-link <?= $currentView === 'coupons' ? 'active' : '' ?>">
                <i class="fa fa-ticket"></i> Coupons
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Users</div>
            <a href="<?= admin_url('/?view=customers') ?>" class="nav-link <?= $currentView === 'customers' ? 'active' : '' ?>">
                <i class="fa fa-users"></i> Customers
            </a>
            <?php if (is_super_admin()): ?>
            <a href="<?= admin_url('/?view=admins') ?>" class="nav-link <?= $currentView === 'admins' ? 'active' : '' ?>">
                <i class="fa fa-user-shield"></i> Admins
            </a>
            <?php endif; ?>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Content</div>
            <a href="<?= admin_url('/?view=reviews') ?>" class="nav-link <?= $currentView === 'reviews' ? 'active' : '' ?>">
                <i class="fa fa-star"></i> Reviews
            </a>
            <a href="<?= admin_url('/?view=messages') ?>" class="nav-link <?= $currentView === 'messages' ? 'active' : '' ?>">
                <i class="fa fa-envelope"></i> Messages
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">System</div>
            <?php if (is_super_admin()): ?>
            <a href="<?= admin_url('/?view=settings') ?>" class="nav-link <?= $currentView === 'settings' ? 'active' : '' ?>">
                <i class="fa fa-cog"></i> Settings
            </a>
            <?php endif; ?>
            <a href="<?= admin_url('/?action=logout') ?>" class="nav-link">
                <i class="fa fa-sign-out"></i> Logout
            </a>
        </div>
    </nav>
</aside>
