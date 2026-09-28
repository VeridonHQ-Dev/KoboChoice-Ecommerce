<?php
class WishlistAPI
{
    public static function get(): void
    {
        global $db;

        $customerId = $_SESSION['customer_id'];
        $items = $db->fetchAll(
            "SELECT w.id AS wishlist_id, p.*
             FROM wishlists w
             JOIN products p ON w.product_id = p.id
             WHERE w.customer_id = ?
             ORDER BY w.created_at DESC",
            [$customerId]
        );

        json_success('', ['items' => $items]);
    }

    public static function add(): void
    {
        global $db;

        $productId = (int)($_POST['product_id'] ?? 0);
        $customerId = $_SESSION['customer_id'];

        if (!$productId) {
            json_error('Product ID required.');
            return;
        }

        $existing = $db->fetch(
            "SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?",
            [$customerId, $productId]
        );

        if ($existing) {
            json_error('Product already in wishlist.');
            return;
        }

        $db->insert('wishlists', [
            'customer_id' => $customerId,
            'product_id'  => $productId,
        ]);

        json_success('Added to wishlist.');
    }

    public static function remove(): void
    {
        global $db;

        $productId = (int)($_POST['product_id'] ?? 0);
        $customerId = $_SESSION['customer_id'];

        $db->delete('wishlists', 'customer_id = ? AND product_id = ?', [$customerId, $productId]);
        json_success('Removed from wishlist.');
    }

    public static function check(): void
    {
        global $db;

        $productId = (int)($_GET['product_id'] ?? 0);
        $customerId = $_SESSION['customer_id'];

        $exists = $db->fetch(
            "SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?",
            [$customerId, $productId]
        );

        json_success('', ['in_wishlist' => $exists !== null]);
    }
}
