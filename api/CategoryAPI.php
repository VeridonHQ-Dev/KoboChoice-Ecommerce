<?php
class CategoryAPI
{
    public static function list(): void
    {
        global $db;
        $categories = $db->fetchAll(
            "SELECT * FROM categories WHERE show_in_menu = 1 ORDER BY sort_order ASC, name ASC"
        );
        json_success('', ['categories' => $categories]);
    }

    public static function listAdmin(): void
    {
        global $db;
        $categories = $db->fetchAll(
            "SELECT c.*, p.name AS parent_name
             FROM categories c
             LEFT JOIN categories p ON c.parent_id = p.id
             ORDER BY c.sort_order ASC, c.name ASC"
        );
        json_success('', ['categories' => $categories]);
    }

    public static function create(): void
    {
        global $db;

        $data = $_POST;
        $errors = required_fields($data, ['name' => 'Name']);

        if (!empty($errors)) {
            json_error(implode(' ', $errors));
            return;
        }

        $slug = slugify($data['name']);
        $existing = $db->fetch("SELECT id FROM categories WHERE slug = ?", [$slug]);
        if ($existing) {
            $slug .= '-' . uniqid();
        }

        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = upload_image($_FILES['image'], CATEGORY_IMG_PATH, 'cat_');
        }

        $categoryId = $db->insert('categories', [
            'parent_id'      => $data['parent_id'] ?: null,
            'name'           => $data['name'],
            'slug'           => $slug,
            'description'    => $data['description'] ?? null,
            'image'          => $image,
            'show_in_menu'   => isset($data['show_in_menu']) ? 1 : 0,
            'show_in_sidebar'=> isset($data['show_in_sidebar']) ? 1 : 0,
            'sort_order'     => $data['sort_order'] ?? 0,
        ]);

        json_success('Category created.', ['id' => $categoryId]);
    }

    public static function update(): void
    {
        global $db;

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            json_error('Category ID required.');
            return;
        }

        $category = $db->fetch("SELECT * FROM categories WHERE id = ?", [$id]);
        if (!$category) {
            json_error('Category not found.', 404);
            return;
        }

        $data = $_POST;
        $updateData = [];

        foreach (['name', 'parent_id', 'description', 'sort_order'] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field] ?: null;
            }
        }

        $updateData['show_in_menu'] = isset($data['show_in_menu']) ? 1 : 0;
        $updateData['show_in_sidebar'] = isset($data['show_in_sidebar']) ? 1 : 0;

        if (!empty($data['name']) && $data['name'] !== $category['name']) {
            $slug = slugify($data['name']);
            $existing = $db->fetch("SELECT id FROM categories WHERE slug = ? AND id != ?", [$slug, $id]);
            if ($existing) $slug .= '-' . uniqid();
            $updateData['slug'] = $slug;
        }

        if (!empty($_FILES['image']['name'])) {
            if ($category['image']) {
                delete_image($category['image'], CATEGORY_IMG_PATH);
            }
            $updateData['image'] = upload_image($_FILES['image'], CATEGORY_IMG_PATH, 'cat_');
        }

        $db->update('categories', $updateData, 'id = ?', [$id]);
        json_success('Category updated.');
    }

    public static function delete(): void
    {
        global $db;

        $id = (int)($_POST['id'] ?? 0);
        $category = $db->fetch("SELECT image FROM categories WHERE id = ?", [$id]);

        if (!$category) {
            json_error('Category not found.', 404);
            return;
        }

        if ($category['image']) {
            delete_image($category['image'], CATEGORY_IMG_PATH);
        }

        $db->delete('categories', 'id = ?', [$id]);
        json_success('Category deleted.');
    }
}
