<?php
require_once __DIR__ . '/includes/auth.php';
$slug = $_GET['slug'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$product = null;
if ($slug) {
    $product = $db->fetch("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.slug = ? AND p.status = 'active'", [$slug]);
} elseif ($id) {
    $product = $db->fetch("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.status = 'active'", [$id]);
}
if (!$product) { redirect(BASE_URL . '/shop'); exit; }

// Feeds the "Recently Viewed" tab on the homepage. Session-based, so it works
// for guests as well as signed-in customers.
record_product_view((int)$product['id']);

$pageTitle = $product['name'];
$bodyClass = 'inner-page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/views/product-single.php';
require_once __DIR__ . '/includes/footer.php';
