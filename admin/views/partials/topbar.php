<?php
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<header class="admin-topbar">
    <div class="topbar-left">
        <button class="admin-btn admin-btn-outline admin-btn-sm d-lg-none" id="sidebarToggle">
            <i class="fa fa-bars"></i>
        </button>
        <h4><?= sanitize($pageTitle) ?></h4>
    </div>
    <div class="topbar-right">
        <a href="<?= BASE_URL ?>/" target="_blank" class="admin-btn admin-btn-outline admin-btn-sm" title="View Store">
            <i class="fa fa-external-link"></i> View Store
        </a>
        <div class="admin-user">
            <i class="fa fa-user-circle" style="font-size:20px; color:#64748b;"></i>
            <span><?= sanitize($_SESSION['admin_name'] ?? 'Admin') ?></span>
        </div>
    </div>
</header>
