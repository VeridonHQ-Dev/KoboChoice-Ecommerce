<?php
/**
 * Kobo Choice - API Router
 *
 * All AJAX requests from the frontend are routed here.
 * Usage: api/index.php?action=<endpoint>
 */

require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (empty($action)) {
    json_error('No action specified.', 400);
}

$customerActions = [
    'wishlist_get', 'wishlist_add', 'wishlist_remove', 'wishlist_check',
    'orders_list', 'order_detail', 'order_create', 'paystack_init', 'paystack_verify',
    'customer_logout', 'cart_apply_coupon', 'coupon_apply',
    'customer_profile', 'customer_update_profile', 'customer_change_password',
    'customer_update_address', 'customer_orders', 'customer_order_detail', 'customer_cancel_order',
    'customer_upload_image', 'customer_remove_image',
];

$adminActions = [
    'admin_login', 'admin_logout', 'dashboard_stats', 'recent_orders',
    'sales_chart', 'low_stock',
    'products_list', 'product_create', 'product_update', 'product_delete', 'product_toggle_flag',
    'categories_list_admin', 'category_create', 'category_update', 'category_delete',
    'admin_orders_list', 'admin_order_update', 'admin_order_delete',
    'customers_list',
    'admin_customer_list', 'admin_customer_detail',
    'admin_customer_disable', 'admin_customer_enable', 'admin_customer_delete',
    'admins_list', 'admin_create', 'admin_update', 'admin_delete', 'admin_reset_password',
    'reviews_list', 'review_approve', 'review_delete',
    'coupons_list', 'coupon_create', 'coupon_update', 'coupon_delete',
    'settings_get', 'settings_update',
    'messages_list', 'message_delete', 'message_update_status',
    'cms_save', 'cms_delete', 'cms_toggle', 'cms_reorder',
];

if (in_array($action, $adminActions) && $action !== 'admin_login' && !is_admin_logged_in()) {
    json_error('Unauthorized.', 401);
}

if (in_array($action, $customerActions) && !is_customer_logged_in()) {
    json_error('Please login to continue.', 401);
}

/*
 * CSRF guard — every state-changing POST must carry a valid token.
 * Auth entry points are exempt (no session token exists before login);
 * they are protected instead by credential checks + rate limiting.
 */
$csrfExempt = ['admin_login', 'customer_login', 'customer_register', 'paystack_webhook'];
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !in_array($action, $csrfExempt, true)
    && !verify_csrf_token($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    // 419 isn't a real HTTP status — this Apache/PHP build can't produce a
    // proper status line for it and silently degrades the whole response to
    // a 500. 403 (Forbidden) is the standard, well-supported code for a
    // rejected CSRF token.
    json_error('Invalid or missing security token. Please refresh the page and try again.', 403);
}

$class = null;
$method = null;

switch ($action) {
    case 'admin_login':       $class = 'AuthAPI'; $method = 'adminLogin'; break;
    case 'admin_logout':      $class = 'AuthAPI'; $method = 'adminLogout'; break;
    case 'customer_login':    $class = 'AuthAPI'; $method = 'customerLogin'; break;
    case 'customer_register': $class = 'AuthAPI'; $method = 'customerRegister'; break;
    case 'customer_logout':   $class = 'AuthAPI'; $method = 'customerLogout'; break;
    case 'customer_forgot_password':    $class = 'AuthAPI'; $method = 'forgotPassword'; break;
    case 'customer_verify_reset_code':  $class = 'AuthAPI'; $method = 'verifyResetCode'; break;
    case 'customer_reset_password':     $class = 'AuthAPI'; $method = 'resetPassword'; break;

    case 'dashboard_stats':   $class = 'DashboardAPI'; $method = 'stats'; break;
    case 'recent_orders':     $class = 'DashboardAPI'; $method = 'recentOrders'; break;
    case 'low_stock':         $class = 'DashboardAPI'; $method = 'lowStock'; break;

    case 'products_list':        $class = 'ProductAPI'; $method = 'list'; break;
    case 'product_detail':       $class = 'ProductAPI'; $method = 'detail'; break;
    case 'product_create':       $class = 'ProductAPI'; $method = 'create'; break;
    case 'product_update':       $class = 'ProductAPI'; $method = 'update'; break;
    case 'product_delete':       $class = 'ProductAPI'; $method = 'delete'; break;
    case 'product_toggle_flag':  $class = 'ProductAPI'; $method = 'toggleFlag'; break;
    case 'products_featured':    $class = 'ProductAPI'; $method = 'featured'; break;
    case 'products_deals':       $class = 'ProductAPI'; $method = 'deals'; break;
    case 'products_new':         $class = 'ProductAPI'; $method = 'newArrivals'; break;
    case 'products_bestseller':  $class = 'ProductAPI'; $method = 'bestsellers'; break;
    case 'products_by_category': $class = 'ProductAPI'; $method = 'byCategory'; break;

    case 'categories_list':       $class = 'CategoryAPI'; $method = 'list'; break;
    case 'categories_list_admin': $class = 'CategoryAPI'; $method = 'listAdmin'; break;
    case 'category_create':       $class = 'CategoryAPI'; $method = 'create'; break;
    case 'category_update':       $class = 'CategoryAPI'; $method = 'update'; break;
    case 'category_delete':       $class = 'CategoryAPI'; $method = 'delete'; break;

    case 'cart_get':          $class = 'CartAPI'; $method = 'get'; break;
    case 'cart_add':          $class = 'CartAPI'; $method = 'add'; break;
    case 'cart_update':       $class = 'CartAPI'; $method = 'update'; break;
    case 'cart_remove':       $class = 'CartAPI'; $method = 'remove'; break;
    case 'cart_count':        $class = 'CartAPI'; $method = 'count'; break;
    case 'cart_apply_coupon':
    case 'coupon_apply':      $class = 'CartAPI'; $method = 'applyCoupon'; break;

    case 'wishlist_get':    $class = 'WishlistAPI'; $method = 'get'; break;
    case 'wishlist_add':    $class = 'WishlistAPI'; $method = 'add'; break;
    case 'wishlist_remove': $class = 'WishlistAPI'; $method = 'remove'; break;
    case 'wishlist_check':  $class = 'WishlistAPI'; $method = 'check'; break;

    case 'order_create':         $class = 'OrderAPI'; $method = 'create'; break;
    case 'orders_list':          $class = 'OrderAPI'; $method = 'list'; break;
    case 'order_detail':         $class = 'OrderAPI'; $method = 'detail'; break;
    case 'admin_orders_list':    $class = 'OrderAPI'; $method = 'adminList'; break;
    case 'admin_order_update':   $class = 'OrderAPI'; $method = 'adminUpdate'; break;
    case 'admin_order_delete':   $class = 'OrderAPI'; $method = 'delete'; break;
    case 'upload_receipt':       $class = 'OrderAPI'; $method = 'uploadPaymentReceipt'; break;

    case 'paystack_init':        $class = 'PaystackAPI'; $method = 'init'; break;
    case 'paystack_verify':      $class = 'PaystackAPI'; $method = 'verify'; break;
    case 'paystack_webhook':     $class = 'PaystackAPI'; $method = 'webhook'; break;

    case 'admin_customer_list':    $class = 'CustomerAPI'; $method = 'adminList'; break;
    case 'admin_customer_detail':  $class = 'CustomerAPI'; $method = 'adminDetail'; break;
    case 'admin_customer_disable': $class = 'CustomerAPI'; $method = 'adminDisable'; break;
    case 'admin_customer_enable':  $class = 'CustomerAPI'; $method = 'adminEnable'; break;
    case 'admin_customer_delete':  $class = 'CustomerAPI'; $method = 'adminDelete'; break;

    case 'search_products':      $class = 'SearchAPI'; $method = 'search'; break;
    case 'search_suggestions':   $class = 'SearchAPI'; $method = 'suggestions'; break;

    case 'newsletter_subscribe': $class = 'NewsletterAPI'; $method = 'subscribe'; break;
    case 'contact_submit':       $class = 'ContactAPI'; $method = 'submit'; break;

    case 'cms_save':      $class = 'CmsAPI'; $method = 'save'; break;
    case 'cms_delete':    $class = 'CmsAPI'; $method = 'delete'; break;
    case 'cms_toggle':    $class = 'CmsAPI'; $method = 'toggle'; break;
    case 'cms_reorder':   $class = 'CmsAPI'; $method = 'reorder'; break;

    case 'messages_list':          $class = 'MessageAPI'; $method = 'adminList'; break;
    case 'message_update_status':  $class = 'MessageAPI'; $method = 'adminUpdateStatus'; break;
    case 'message_delete':         $class = 'MessageAPI'; $method = 'adminDelete'; break;

    case 'customer_profile':          $class = 'CustomerAPI'; $method = 'getProfile'; break;
    case 'customer_update_profile':   $class = 'CustomerAPI'; $method = 'updateProfile'; break;
    case 'customer_change_password':  $class = 'CustomerAPI'; $method = 'changePassword'; break;
    case 'customer_update_address':   $class = 'CustomerAPI'; $method = 'updateAddress'; break;
    case 'customer_orders':           $class = 'CustomerAPI'; $method = 'orders'; break;
    case 'customer_order_detail':     $class = 'CustomerAPI'; $method = 'orderDetail'; break;
    case 'customer_cancel_order':     $class = 'CustomerAPI'; $method = 'cancelOrder'; break;
    case 'customer_upload_image':     $class = 'CustomerAPI'; $method = 'uploadProfileImage'; break;
    case 'customer_remove_image':     $class = 'CustomerAPI'; $method = 'removeProfileImage'; break;
}

if ($class === null || $method === null) {
    json_error('Unknown action: ' . $action, 404);
}

try {
    $class::$method();
} catch (Throwable $e) {
    error_log('[Kobo API] ' . $action . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    json_error('A server error occurred. Please try again.', 500);
}
