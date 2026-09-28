<?php
class ProductAPI
{
    public static function list(): void
    {
        global $db;

        $where = "status = 'active'";
        $params = [];

        if (is_admin_logged_in() && isset($_GET['all'])) {
            $where = "1";
        }

        if (!empty($_GET['category_id'])) {
            $where .= " AND category_id = ?";
            $params[] = (int) $_GET['category_id'];
        }

        if (!empty($_GET['search'])) {
            $where .= " AND (name LIKE ? OR description LIKE ?)";
            $term = '%' . $_GET['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $pagination = paginate($db, 'products', $where, $params);

        $products = $db->fetchAll(
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE $where
             ORDER BY p.sort_order ASC, p.created_at DESC
             LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
            $params
        );

        json_success('', ['products' => $products, 'pagination' => $pagination]);
    }

    public static function detail(): void
    {
        global $db;

        $id = (int)($_GET['id'] ?? 0);
        $slug = $_GET['slug'] ?? '';

        if ($id) {
            $product = $db->fetch("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?", [$id]);
        } elseif ($slug) {
            $product = $db->fetch("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.slug = ?", [$slug]);
        } else {
            json_error('Product ID or slug required.');
            return;
        }

        if (!$product) {
            json_error('Product not found.', 404);
            return;
        }

        $images = $db->fetchAll(
            "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC",
            [$product['id']]
        );

        $product['images'] = $images;
        json_success('', ['product' => $product]);
    }

    public static function featured(): void
    {
        global $db;
        $products = $db->fetchAll(
            "SELECT * FROM products WHERE is_featured = 1 AND status = 'active' ORDER BY sort_order ASC, created_at DESC LIMIT 8"
        );
        json_success('', ['products' => $products]);
    }

    public static function deals(): void
    {
        global $db;
        $products = $db->fetchAll(
            "SELECT * FROM products WHERE is_deal = 1 AND status = 'active' ORDER BY sort_order ASC, created_at DESC LIMIT 8"
        );
        json_success('', ['products' => $products]);
    }

    public static function newArrivals(): void
    {
        global $db;
        $products = $db->fetchAll(
            "SELECT * FROM products WHERE is_new = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 8"
        );
        json_success('', ['products' => $products]);
    }

    public static function bestsellers(): void
    {
        global $db;
        $products = $db->fetchAll(
            "SELECT * FROM products WHERE is_bestseller = 1 AND status = 'active' ORDER BY sort_order ASC LIMIT 8"
        );
        json_success('', ['products' => $products]);
    }

    public static function byCategory(): void
    {
        global $db;

        $categoryId = (int)($_GET['category_id'] ?? 0);
        if (!$categoryId) {
            json_error('Category ID required.');
            return;
        }

        $pagination = paginate($db, 'products', "category_id = ? AND status = 'active'", [$categoryId]);

        $products = $db->fetchAll(
            "SELECT * FROM products WHERE category_id = ? AND status = 'active'
             ORDER BY sort_order ASC, created_at DESC
             LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
            [$categoryId]
        );

        json_success('', ['products' => $products, 'pagination' => $pagination]);
    }

    public static function create(): void
    {
        global $db;

        $data = $_POST;
        $errors = required_fields($data, ['name' => 'Name', 'price' => 'Price']);

        if (!empty($errors)) {
            json_error(implode(' ', $errors));
            return;
        }

        $slug = slugify($data['name']);
        $existing = $db->fetch("SELECT id FROM products WHERE slug = ?", [$slug]);
        if ($existing) {
            $slug .= '-' . uniqid();
        }

        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = upload_image($_FILES['image'], PRODUCT_IMG_PATH, 'prod_');
        }

        $productId = $db->insert('products', [
            'category_id'       => $data['category_id'] ?? null,
            'section'           => normalize_product_section($data['section'] ?? ''),
            'name'              => $data['name'],
            'slug'              => $slug,
            'reference'         => $data['reference'] ?? null,
            'manufacturer'      => $data['manufacturer'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'description'       => $data['description'] ?? null,
            'price'             => $data['price'],
            'old_price'         => $data['old_price'] ?: null,
            'cost_price'        => $data['cost_price'] ?: null,
            'stock'             => $data['stock'] ?? 0,
            'sku'               => $data['sku'] ?? null,
            'image'             => $image,
            // A freshly uploaded product is new by definition; the rest are
            // merchandising decisions made afterwards from the product list.
            'is_new'            => 1,
            'is_featured'       => isset($data['is_featured']) ? 1 : 0,
            'is_bestseller'     => isset($data['is_bestseller']) ? 1 : 0,
            'is_deal'           => isset($data['is_deal']) ? 1 : 0,
            'status'            => $data['status'] ?? 'active',
            'sort_order'        => $data['sort_order'] ?? 0,
        ]);

        self::saveGalleryImages($productId);

        json_success('Product created.', ['id' => $productId]);
    }

    /**
     * Save uploaded gallery images (multi-file input named gallery[]) into
     * product_images. Silently skips empty slots and enforces the overall cap
     * of MAX_PRODUCT_IMAGES (main image + gallery).
     */
    private static function saveGalleryImages(int $productId): void
    {
        global $db;

        if (empty($_FILES['gallery']) || !is_array($_FILES['gallery']['name'])) {
            return;
        }

        $existing = $db->count('product_images', 'product_id = ?', [$productId]);
        $slots = count($_FILES['gallery']['name']);

        for ($i = 0; $i < $slots; $i++) {
            if (($existing + 1) >= MAX_PRODUCT_IMAGES) {
                break; // +1 accounts for the main product image
            }
            if (empty($_FILES['gallery']['name'][$i]) || $_FILES['gallery']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $file = [
                'name'     => $_FILES['gallery']['name'][$i],
                'type'     => $_FILES['gallery']['type'][$i],
                'tmp_name' => $_FILES['gallery']['tmp_name'][$i],
                'error'    => $_FILES['gallery']['error'][$i],
                'size'     => $_FILES['gallery']['size'][$i],
            ];

            $filename = upload_image($file, PRODUCT_IMG_PATH, 'prod_');
            if ($filename) {
                $db->insert('product_images', [
                    'product_id' => $productId,
                    'image'      => $filename,
                    'sort_order' => $existing + $i,
                ]);
                $existing++;
            }
        }
    }

    public static function update(): void
    {
        global $db;

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            json_error('Product ID required.');
            return;
        }

        $product = $db->fetch("SELECT * FROM products WHERE id = ?", [$id]);
        if (!$product) {
            json_error('Product not found.', 404);
            return;
        }

        $data = $_POST;
        $updateData = [];

        foreach (['name', 'category_id', 'reference', 'manufacturer', 'short_description', 'description', 'price', 'old_price', 'cost_price', 'stock', 'sku', 'status', 'sort_order'] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field] ?: null;
            }
        }

        if (array_key_exists('section', $data)) {
            $updateData['section'] = normalize_product_section($data['section']);
        }

        /*
         * Only touch a flag the caller actually sent. These are set from the
         * switches on the product list rather than this form, so treating an
         * absent key as "unchecked" would silently clear a product's Deal or
         * Featured placement every time someone edited its price.
         */
        foreach (['is_new', 'is_featured', 'is_bestseller', 'is_deal'] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field] ? 1 : 0;
            }
        }

        if (!empty($data['name']) && $data['name'] !== $product['name']) {
            $slug = slugify($data['name']);
            $existing = $db->fetch("SELECT id FROM products WHERE slug = ? AND id != ?", [$slug, $id]);
            if ($existing) $slug .= '-' . uniqid();
            $updateData['slug'] = $slug;
        }

        if (!empty($_FILES['image']['name'])) {
            if ($product['image']) {
                delete_image($product['image'], PRODUCT_IMG_PATH);
            }
            $updateData['image'] = upload_image($_FILES['image'], PRODUCT_IMG_PATH, 'prod_');
        }

        $db->update('products', $updateData, 'id = ?', [$id]);

        self::saveGalleryImages($id);

        json_success('Product updated.');
    }

    /**
     * Flip a single homepage placement flag (is_deal, is_featured, …) on one
     * product. Powers the inline switches on the admin product list so
     * merchandising a section never means opening the full edit form.
     */
    public static function toggleFlag(): void
    {
        global $db;

        // Whitelisted: a forged POST can only ever reach a placement flag,
        // never price, stock or status. The old herb booleans are gone from
        // here — those placements now live in products.section.
        $allowed = ['is_new', 'is_featured', 'is_bestseller', 'is_deal'];

        $id    = (int)($_POST['id'] ?? 0);
        $field = $_POST['field'] ?? '';
        $value = (!empty($_POST['value']) && $_POST['value'] !== '0') ? 1 : 0;

        if (!$id) {
            json_error('Product ID required.');
            return;
        }

        if (!in_array($field, $allowed, true)) {
            json_error('Unknown product flag.');
            return;
        }

        if (!$db->fetch("SELECT id FROM products WHERE id = ?", [$id])) {
            json_error('Product not found.', 404);
            return;
        }

        $db->update('products', [$field => $value], 'id = ?', [$id]);

        json_success('Product updated.', ['id' => $id, 'field' => $field, 'value' => $value]);
    }

    public static function delete(): void
    {
        global $db;

        $id = (int)($_POST['id'] ?? 0);
        $product = $db->fetch("SELECT image FROM products WHERE id = ?", [$id]);

        if (!$product) {
            json_error('Product not found.', 404);
            return;
        }

        if ($product['image']) {
            delete_image($product['image'], PRODUCT_IMG_PATH);
        }

        // Remove gallery image files too (DB rows cascade automatically).
        $gallery = $db->fetchAll("SELECT image FROM product_images WHERE product_id = ?", [$id]);
        foreach ($gallery as $g) {
            delete_image($g['image'], PRODUCT_IMG_PATH);
        }

        $db->delete('products', 'id = ?', [$id]);
        json_success('Product deleted.');
    }
}
