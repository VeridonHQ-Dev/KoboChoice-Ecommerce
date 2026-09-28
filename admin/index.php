<?php
/**
 * Kobo Choice - Admin Panel Entry Point
 */

require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../includes/auth.php';

$view = $_GET['view'] ?? 'dashboard';
$action = $_GET['action'] ?? '';

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = admin_login([
            'email'    => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
        ]);

        if ($result['success']) {
            redirect(admin_url('/'));
        } else {
            $loginError = $result['message'];
        }
    }

    require __DIR__ . '/views/login.php';
    exit;
}

if ($action === 'logout') {
    admin_logout();
    redirect(admin_url('/'));
}

require_admin();

$pageTitles = [
    'dashboard'  => 'Dashboard',
    'products'   => 'Products',
    'categories' => 'Categories',
    'orders'     => 'Orders',
    'order'      => 'Order Details',
    'customers'  => 'Customers',
    'admins'     => 'Admin Users',
    'coupons'    => 'Coupons',
    'reviews'    => 'Reviews',
    'messages'   => 'Messages',
    'cms'        => 'CMS — Banners & Ads',
    'settings'   => 'Settings',
];

$pageTitle = $pageTitles[$view] ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= sanitize($pageTitle) ?> - Kobo Choice Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="<?= BASE_URL ?>/images/favicon.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/bootstrap.min.css">
    <?php /* Versioned so a CSS change is picked up immediately instead of
             being served from the browser cache until a hard refresh. */ ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin.css?v=<?= @filemtime(__DIR__ . '/assets/css/admin.css') ?>">
</head>
<body class="admin-body">
<script>var CSRF_TOKEN = '<?= generate_csrf_token() ?>';</script>
<div class="admin-wrapper">
<?php

switch ($view) {
    case 'products':
        $subView = $_GET['sub'] ?? 'list';
        require __DIR__ . '/views/partials/sidebar.php';
        echo '<div class="admin-main">';
        require __DIR__ . '/views/partials/topbar.php';
        echo '<div class="admin-content">';
        display_flash();
        if ($subView === 'create' || $subView === 'edit') {
            require __DIR__ . '/views/products/' . $subView . '.php';
        } else {
            require __DIR__ . '/views/products/list.php';
        }
        echo '</div></div>';
        break;

    case 'categories':
        $subView = $_GET['sub'] ?? 'list';
        require __DIR__ . '/views/partials/sidebar.php';
        echo '<div class="admin-main">';
        require __DIR__ . '/views/partials/topbar.php';
        echo '<div class="admin-content">';
        display_flash();
        if ($subView === 'create' || $subView === 'edit') {
            require __DIR__ . '/views/categories/' . $subView . '.php';
        } else {
            require __DIR__ . '/views/categories/list.php';
        }
        echo '</div></div>';
        break;

    case 'orders':
        require __DIR__ . '/views/partials/sidebar.php';
        echo '<div class="admin-main">';
        require __DIR__ . '/views/partials/topbar.php';
        echo '<div class="admin-content">';
        display_flash();
        require __DIR__ . '/views/orders/list.php';
        echo '</div></div>';
        break;

    case 'order':
        /* Opening an order is what "attending to" it means for the sidebar
         * badge. Done here rather than inside the view because the sidebar is
         * rendered first — marking it any later would leave the badge showing
         * the old count until the next page load. The `seen_at IS NULL` guard
         * keeps the first view as the one that counts, so re-reading an order
         * never rewrites its timestamp. */
        $viewedOrderId = (int) ($_GET['id'] ?? 0);
        if ($viewedOrderId > 0) {
            $db->query(
                "UPDATE orders SET seen_at = NOW() WHERE id = ? AND seen_at IS NULL",
                [$viewedOrderId]
            );
        }

        require __DIR__ . '/views/partials/sidebar.php';
        echo '<div class="admin-main">';
        require __DIR__ . '/views/partials/topbar.php';
        echo '<div class="admin-content">';
        display_flash();
        require __DIR__ . '/views/orders/view.php';
        echo '</div></div>';
        break;

    case 'customers':
        $subView = $_GET['sub'] ?? 'list';
        require __DIR__ . '/views/partials/sidebar.php';
        echo '<div class="admin-main">';
        require __DIR__ . '/views/partials/topbar.php';
        echo '<div class="admin-content">';
        display_flash();
        if ($subView === 'view') {
            require __DIR__ . '/views/customers/view.php';
        } else {
            require __DIR__ . '/views/customers/list.php';
        }
        echo '</div></div>';
        break;

    case 'admins':
        $subView = $_GET['sub'] ?? 'list';
        require __DIR__ . '/views/partials/sidebar.php';
        echo '<div class="admin-main">';
        require __DIR__ . '/views/partials/topbar.php';
        echo '<div class="admin-content">';
        display_flash();
        if ($subView === 'create' || $subView === 'edit') {
            require __DIR__ . '/views/admins/' . $subView . '.php';
        } else {
            require __DIR__ . '/views/admins/list.php';
        }
        echo '</div></div>';
        break;

    case 'coupons':
        $subView = $_GET['sub'] ?? 'list';
        require __DIR__ . '/views/partials/sidebar.php';
        echo '<div class="admin-main">';
        require __DIR__ . '/views/partials/topbar.php';
        echo '<div class="admin-content">';
        display_flash();
        if ($subView === 'form') {
            require __DIR__ . '/views/coupons/form.php';
        } else {
            require __DIR__ . '/views/coupons/list.php';
        }
        echo '</div></div>';
        break;

    case 'reviews':
        require __DIR__ . '/views/partials/sidebar.php';
        echo '<div class="admin-main">';
        require __DIR__ . '/views/partials/topbar.php';
        echo '<div class="admin-content">';
        display_flash();
        require __DIR__ . '/views/reviews/list.php';
        echo '</div></div>';
        break;

    case 'messages':
        require __DIR__ . '/views/partials/sidebar.php';
        echo '<div class="admin-main">';
        require __DIR__ . '/views/partials/topbar.php';
        echo '<div class="admin-content">';
        display_flash();
        require __DIR__ . '/views/messages/list.php';
        echo '</div></div>';
        break;

    case 'cms':
        require __DIR__ . '/views/partials/sidebar.php';
        echo '<div class="admin-main">';
        require __DIR__ . '/views/partials/topbar.php';
        echo '<div class="admin-content">';
        display_flash();
        require __DIR__ . '/views/cms/list.php';
        echo '</div></div>';
        break;

    case 'settings':
        /* Runs before any markup so its redirect() can still send headers. */
        require __DIR__ . '/views/settings/save.php';

        require __DIR__ . '/views/partials/sidebar.php';
        echo '<div class="admin-main">';
        require __DIR__ . '/views/partials/topbar.php';
        echo '<div class="admin-content">';
        display_flash();
        require __DIR__ . '/views/settings/edit.php';
        echo '</div></div>';
        break;

    default:
        require __DIR__ . '/views/dashboard.php';
        break;
}
?>
</div>
<script src="<?= BASE_URL ?>/admin/assets/js/admin.js?v=<?= @filemtime(__DIR__ . '/assets/js/admin.js') ?>"></script>
</body>
</html>
