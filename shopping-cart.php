<?php
require_once __DIR__ . '/includes/auth.php';
if (!is_customer_logged_in()) {
    redirect(base_url('/login'));
}
$pageTitle = 'Shopping Cart';
$bodyClass = 'inner-page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/views/cart-view.php';
require_once __DIR__ . '/includes/footer.php';
