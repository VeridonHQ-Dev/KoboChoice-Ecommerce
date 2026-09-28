<?php
class DashboardAPI
{
    public static function stats(): void
    {
        global $db;

        $totalProducts  = $db->count('products');
        $totalOrders    = $db->count('orders');
        $totalCustomers = $db->count('customers');
        $totalAdmins    = $db->count('admins');
        $totalCategories = $db->count('categories');

        $approvedProducts = $db->count('products', "status = 'active'");
        $outOfStock       = $db->count('products', "stock = 0");
        $lowStockCount    = $db->count('products', "stock > 0 AND stock < 5 AND status = 'active'");

        $revenue = $db->fetch("SELECT COALESCE(SUM(total), 0) AS total FROM orders WHERE status != 'cancelled'");

        $cancelledOrders  = $db->count('orders', "status = 'cancelled'");
        $pendingOrders    = $db->count('orders', "status = 'pending'");
        $deliveredOrders  = $db->count('orders', "status = 'delivered'");

        json_success('', [
            'total_products'    => $totalProducts,
            'total_orders'      => $totalOrders,
            'total_customers'   => $totalCustomers,
            'total_admins'      => $totalAdmins,
            'total_categories'  => $totalCategories,
            'approved_products' => $approvedProducts,
            'out_of_stock'      => $outOfStock,
            'low_stock_count'   => $lowStockCount,
            'total_revenue'     => (float)($revenue['total'] ?? 0),
            'cancelled_orders'  => $cancelledOrders,
            'pending_orders'    => $pendingOrders,
            'delivered_orders'  => $deliveredOrders,
        ]);
    }

    public static function recentOrders(): void
    {
        global $db;

        $orders = $db->fetchAll(
            "SELECT o.*, c.name AS customer_name
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             ORDER BY o.created_at DESC
             LIMIT 10"
        );

        json_success('', ['orders' => $orders]);
    }

    public static function lowStock(): void
    {
        global $db;

        $products = $db->fetchAll(
            "SELECT id, name, sku, stock, image
             FROM products
             WHERE stock < 5 AND status = 'active'
             ORDER BY stock ASC
             LIMIT 10"
        );

        json_success('', ['products' => $products]);
    }
}
