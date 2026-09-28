<?php
class SearchAPI
{
    public static function search(): void
    {
        global $db;

        $term = trim($_GET['q'] ?? '');
        $categoryId = (int)($_GET['category_id'] ?? 0);
        $sortBy = $_GET['sort'] ?? 'newest';
        $minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
        $maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;

        $where = "p.status = 'active'";
        $params = [];

        if (!empty($term)) {
            $where .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.manufacturer LIKE ?)";
            $likeTerm = '%' . $term . '%';
            $params[] = $likeTerm;
            $params[] = $likeTerm;
            $params[] = $likeTerm;
        }

        if ($categoryId) {
            $where .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }

        if ($minPrice !== null) {
            $where .= " AND p.price >= ?";
            $params[] = $minPrice;
        }

        if ($maxPrice !== null) {
            $where .= " AND p.price <= ?";
            $params[] = $maxPrice;
        }

        $orderBy = match ($sortBy) {
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'name'       => 'p.name ASC',
            default      => 'p.created_at DESC',
        };

        $pagination = paginate($db, 'products p', $where, $params);

        $products = $db->fetchAll(
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE $where
             ORDER BY $orderBy
             LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
            $params
        );

        json_success('', [
            'products'   => $products,
            'pagination' => $pagination,
            'query'      => $term,
        ]);
    }

    public static function suggestions(): void
    {
        global $db;

        $term = trim($_GET['q'] ?? '');
        if (strlen($term) < 2) {
            json_success('', ['suggestions' => []]);
            return;
        }

        $products = $db->fetchAll(
            "SELECT id, name, slug, price, image
             FROM products
             WHERE status = 'active' AND name LIKE ?
             ORDER BY name ASC
             LIMIT 8",
            ['%' . $term . '%']
        );

        json_success('', ['suggestions' => $products]);
    }
}
