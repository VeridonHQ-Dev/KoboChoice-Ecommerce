<?php
/**
 * Kobo Choice - Homepage banner / picture-ad management (admin only)
 *
 * Handles the CMS screen: creating, editing, reordering and deleting the
 * homepage banners. Deliberately scoped to picture ads — product imagery is
 * managed from the Products screen and is untouched here.
 */
class CmsAPI
{
    /** Sections the CMS knows how to render, and what each one supports. */
    public const SECTIONS = [
        'slider'      => ['label' => 'Hero Slider',                  'text' => true],
        'ads_grid'    => ['label' => 'Ads Grid (8 tiles)',           'text' => false],
        'promo_pair'  => ['label' => 'Promo Banners',                'text' => false],
        'static_home' => ['label' => 'Featured Product Banner',      'text' => true],
        'featured'    => ['label' => 'Featured Products Side Banner','text' => false],
    ];

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    private const MAX_BYTES = 4 * 1024 * 1024; // 4 MB

    public static function save(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $id      = (int) ($_POST['id'] ?? 0);
        $section = (string) ($_POST['section'] ?? '');

        if (!isset(self::SECTIONS[$section])) {
            json_error('Unknown banner section.');
            return;
        }

        $existing = null;
        if ($id > 0) {
            $existing = $db->fetch("SELECT * FROM cms_banners WHERE id = ?", [$id]);
            if (!$existing) {
                json_error('Banner not found.', 404);
                return;
            }
        }

        // ── Image: required when creating, optional when editing ──────
        $imagePath = $existing['image'] ?? '';
        if (!empty($_FILES['image']['name'])) {
            $uploaded = self::storeUpload($_FILES['image']);
            if ($uploaded === null) {
                return; // storeUpload already emitted the error
            }
            $imagePath = $uploaded;

            // Remove the previous file, but only if it was an upload — never
            // delete the bundled theme artwork under images/.
            if ($existing && self::isManagedUpload($existing['image'])) {
                @unlink(ROOT_PATH . '/' . $existing['image']);
            }
        }

        if ($imagePath === '') {
            json_error('Please choose an image for this banner.');
            return;
        }

        $data = [
            'section'     => $section,
            'image'       => $imagePath,
            'caption'     => self::nullIfBlank($_POST['caption'] ?? ''),
            'link_url'    => self::nullIfBlank($_POST['link_url'] ?? '') ?? '/shop',
            'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
            'is_active'   => !empty($_POST['is_active']) ? 1 : 0,
        ];

        // Only the slider carries the headline / price / button copy.
        if (self::SECTIONS[$section]['text']) {
            $data['subheading']  = self::nullIfBlank($_POST['subheading'] ?? '');
            $data['heading']     = self::nullIfBlank($_POST['heading'] ?? '');
            $data['price_text']  = self::nullIfBlank($_POST['price_text'] ?? '');
            $data['button_text'] = self::nullIfBlank($_POST['button_text'] ?? '') ?? 'Order Now';
        }

        if ($id > 0) {
            $db->update('cms_banners', $data, 'id = ?', [$id]);
            $action = 'cms_update';
            $desc   = "Banner #$id updated ($section)";
        } else {
            $id = $db->insert('cms_banners', $data);
            $action = 'cms_create';
            $desc   = "Banner #$id created ($section)";
        }

        self::log($action, $desc);
        json_success('Banner saved.', ['id' => $id]);
    }

    public static function delete(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            json_error('Invalid banner ID.');
            return;
        }

        $banner = $db->fetch("SELECT * FROM cms_banners WHERE id = ?", [$id]);
        if (!$banner) {
            json_error('Banner not found.', 404);
            return;
        }

        $db->delete('cms_banners', 'id = ?', [$id]);

        // Only uploads are removed from disk; bundled theme art stays put so
        // it can be re-selected later.
        if (self::isManagedUpload($banner['image'])) {
            @unlink(ROOT_PATH . '/' . $banner['image']);
        }

        self::log('cms_delete', "Banner #$id deleted ({$banner['section']})");
        json_success('Banner deleted.');
    }

    /** Toggles visibility without deleting the banner. */
    public static function toggle(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $banner = $id ? $db->fetch("SELECT id, is_active, section FROM cms_banners WHERE id = ?", [$id]) : null;
        if (!$banner) {
            json_error('Banner not found.', 404);
            return;
        }

        $new = $banner['is_active'] ? 0 : 1;
        $db->update('cms_banners', ['is_active' => $new], 'id = ?', [$id]);

        self::log('cms_toggle', "Banner #$id " . ($new ? 'shown' : 'hidden'));
        json_success($new ? 'Banner is now visible.' : 'Banner hidden.', ['is_active' => $new]);
    }

    /** Persists a new display order for one section. */
    public static function reorder(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            json_error('Nothing to reorder.');
            return;
        }

        foreach (array_values($ids) as $position => $id) {
            $db->update('cms_banners', ['sort_order' => $position + 1], 'id = ?', [(int) $id]);
        }

        json_success('Order updated.');
    }

    /* ─── Helpers ─────────────────────────────────────────────────── */

    /**
     * Validates and stores an uploaded image, returning its site-relative
     * path, or null after emitting a JSON error.
     *
     * Validation is by actual image content (getimagesize) rather than the
     * client-supplied MIME type or file extension, and the stored filename is
     * generated server-side — so a file named "evil.php" can never land in a
     * web-served directory under its original name.
     */
    private static function storeUpload(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            json_error('Upload failed. Please try again.');
            return null;
        }

        if ($file['size'] > self::MAX_BYTES) {
            json_error('Image must be under 4MB.');
            return null;
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false || empty($info['mime']) || !isset(self::ALLOWED_MIME[$info['mime']])) {
            json_error('Only JPG, PNG, GIF or WebP images are allowed.');
            return null;
        }

        $dir = ROOT_PATH . '/public/uploads/cms';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            json_error('Could not create the upload folder.');
            return null;
        }

        $name = 'banner_' . bin2hex(random_bytes(8)) . '.' . self::ALLOWED_MIME[$info['mime']];

        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            json_error('Could not save the uploaded image.');
            return null;
        }

        return 'public/uploads/cms/' . $name;
    }

    /** True when the path is an admin upload (safe to delete from disk). */
    private static function isManagedUpload(string $path): bool
    {
        return str_starts_with($path, 'public/uploads/cms/');
    }

    private static function nullIfBlank(string $value): ?string
    {
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private static function log(string $action, string $description): void
    {
        global $db;
        $db->insert('admin_logs', [
            'admin_id'    => $_SESSION['admin_id'] ?? null,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    }
}
