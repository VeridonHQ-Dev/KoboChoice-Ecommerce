<?php
/**
 * Kobo Choice - Helper Functions
 */

require_once __DIR__ . '/../config/config.php';

/* ─── Site Settings ───────────────────────────────────────────────────── */

/**
 * Fetch a site setting (from the `settings` key/value table) with an optional
 * fallback. Values are loaded once per request and cached in memory.
 */
function get_setting(string $key, string $default = ''): string
{
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        try {
            $rows = Database::getInstance()->fetchAll("SELECT `key`, `value` FROM `settings`");
            foreach ($rows as $row) {
                $cache[$row['key']] = $row['value'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }

    $value = $cache[$key] ?? null;
    return ($value === null || $value === '') ? $default : $value;
}

/* ─── String & Output ─────────────────────────────────────────────────── */

function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

function truncate(string $text, int $length = 100): string
{
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '…';
}

/* ─── Currency ────────────────────────────────────────────────────────── */

function format_price(float $amount): string
{
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

/* ─── Date/Time ───────────────────────────────────────────────────────── */

function time_ago(string $datetime): string
{
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

function format_date(string $datetime, string $format = 'M d, Y'): string
{
    return (new DateTime($datetime))->format($format);
}

/* ─── URL ──────────────────────────────────────────────────────────────── */

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function base_url(string $path = ''): string
{
    return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
}

function admin_url(string $path = ''): string
{
    return ADMIN_PATH . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Absolute site URL — required by emails, where a relative path like
 * "/order-confirmation.php" cannot resolve because the mail client has no
 * idea which site it belongs to.
 *
 * The configured SITE_URL always wins once it points at a real domain, so a
 * forged Host header can never rewrite the links inside outgoing email (a
 * classic phishing vector). Auto-detection from the current request is used
 * only while SITE_URL is still the localhost default, which keeps links
 * working in development and on whatever host the site is reached by before
 * the constant has been updated for production.
 */
function site_url(string $path = ''): string
{
    $configured = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
    $isLocalDefault = $configured === ''
        || preg_match('~^https?://(localhost|127\.0\.0\.1)([:/]|$)~i', $configured) === 1;

    $base = $configured;

    if ($isLocalDefault && !empty($_SERVER['HTTP_HOST'])) {
        $host = (string) $_SERVER['HTTP_HOST'];
        // Only accept a plain host[:port] token — anything else is discarded
        // rather than trusted into a link.
        if (preg_match('~^[A-Za-z0-9.\-]+(:\d{1,5})?$~', $host) === 1) {
            $https  = $_SERVER['HTTPS'] ?? '';
            $scheme = ($https !== '' && strtolower((string) $https) !== 'off') ? 'https' : 'http';
            $base   = $scheme . '://' . $host . BASE_URL;
        }
    }

    if ($base === '') {
        $base = 'http://localhost' . BASE_URL;
    }

    return rtrim($base, '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

/** Absolute link to the "order completed" page for a given order. */
function order_confirmation_url(int $orderId): string
{
    return site_url('/order-confirmation?id=' . $orderId);
}

/* ─── Product placement sections ───────────────────────────────────────── */

/**
 * Canonical list of homepage placement sections, keyed by the value stored in
 * products.section. A product belongs to at most one — it is picked from the
 * "Section" dropdown on the admin product form, and the homepage tabs read
 * straight off it. Add an entry here and the admin dropdown grows with it.
 *
 * A section placement is a guarantee, not an exclusion: a product filed under
 * Electronics still shows up in New Arrival, Top Deals or Top Selling if it
 * qualifies for those on its own merits.
 */
function product_sections(): array
{
    return [
        'fashion'     => 'Fashion',
        'electronics' => 'Electronics',
        'powders'     => 'Powders',
        'roots_bark'  => 'Roots & Bark',
        'supplements' => 'Supplements',
    ];
}

/**
 * Normalise a submitted section to a known key, or '' for "no section".
 * Anything unrecognised is discarded rather than stored.
 */
function normalize_product_section(?string $section): string
{
    $section = trim((string)$section);

    return array_key_exists($section, product_sections()) ? $section : '';
}

/* ─── Customer account navigation ──────────────────────────────────────── */

/**
 * The customer dashboard's sections, keyed by the ?tab= value the dashboard
 * reads. Shared by the sidebar on desktop and the account dropdown in the
 * mobile header, so the two can never list different things.
 */
function customer_dashboard_tabs(): array
{
    return [
        'orders'   => ['label' => 'My Orders',       'icon' => 'fa-shopping-bag'],
        'profile'  => ['label' => 'Profile Details', 'icon' => 'fa-user'],
        'password' => ['label' => 'Change Password', 'icon' => 'fa-lock'],
        'address'  => ['label' => 'Addresses',       'icon' => 'fa-truck'],
    ];
}

/* ─── Recently viewed products ─────────────────────────────────────────── */

/**
 * Remember that this visitor opened a product page. Held in the session rather
 * than the database so it works for guests too, and so it costs nothing to
 * write. Most recent first, de-duplicated, and capped so a long browsing run
 * can't grow the session without bound.
 */
function record_product_view(int $productId, int $cap = 20): void
{
    if ($productId <= 0) {
        return;
    }

    $seen = array_map('intval', $_SESSION['recently_viewed'] ?? []);

    // Re-visiting a product moves it back to the front instead of duplicating.
    $seen = array_values(array_filter($seen, static fn(int $id): bool => $id !== $productId));
    array_unshift($seen, $productId);

    $_SESSION['recently_viewed'] = array_slice($seen, 0, $cap);
}

/**
 * Product IDs this visitor has viewed, most recent first.
 */
function recently_viewed_ids(int $limit = 12): array
{
    $seen = array_map('intval', $_SESSION['recently_viewed'] ?? []);

    return array_slice(array_filter($seen), 0, $limit);
}

/* ─── CMS banners / picture ads ────────────────────────────────────────── */

/**
 * Active banners for a homepage section, in display order.
 * Sections: slider | ads_grid | promo_pair | featured
 */
function cms_banners(string $section): array
{
    global $db;

    static $cache = [];
    if (isset($cache[$section])) {
        return $cache[$section];
    }

    try {
        $cache[$section] = $db->fetchAll(
            "SELECT * FROM cms_banners
             WHERE section = ? AND is_active = 1
             ORDER BY sort_order ASC, id ASC",
            [$section]
        );
    } catch (Throwable $e) {
        // A missing table must not take the homepage down.
        error_log('[CMS] Could not load banners for "' . $section . '": ' . $e->getMessage());
        $cache[$section] = [];
    }

    return $cache[$section];
}

/**
 * Public URL for a banner image. Paths are stored relative to the site root
 * so they work for both bundled theme art and admin uploads.
 */
function cms_image_url(string $path): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Where a banner points. Stored links beginning with "/" are treated as
 * site-relative; everything else is passed through. Defaults to the shop.
 */
function cms_link_url(?string $link): string
{
    $link = trim((string) $link);
    if ($link === '') {
        return BASE_URL . '/shop';
    }
    if (preg_match('~^https?://~i', $link)) {
        return $link;
    }
    return BASE_URL . '/' . ltrim($link, '/');
}

/* ─── Response Helpers ─────────────────────────────────────────────────── */

function json_response(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function json_success($message = 'Success', $data = []): void
{
    json_response(array_merge(['success' => true, 'message' => $message], $data));
}

/*
 * Default status stays 200 so plain validation failures (wrong password,
 * "passwords do not match", etc.) still hit jQuery's success callback and
 * show their real message — $.post()/.fail() only fires on non-2xx, and
 * generic .fail() handlers across the frontend show a hardcoded message.
 * Pass an explicit statusCode for genuine HTTP-level failures (401, 403,
 * 404, 405, 419, 500) that callers intentionally want routed to .fail().
 */
function json_error(string $message, int $statusCode = 200): void
{
    json_response(['success' => false, 'message' => $message], $statusCode);
}

/* ─── Validation ───────────────────────────────────────────────────────── */

function is_email(string $value): bool
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength. Returns an array of failing rule messages
 * (empty array = strong enough). Mirrored by the client-side checklist.
 */
function password_strength_errors(string $password): array
{
    $errors = [];
    if (strlen($password) < 8)               $errors[] = 'Minimum 8 characters';
    if (!preg_match('/[A-Z]/', $password))   $errors[] = 'At least one uppercase letter';
    if (!preg_match('/[0-9]/', $password))   $errors[] = 'At least one number';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'At least one special character';
    return $errors;
}

function required_fields(array $data, array $fields): array
{
    $errors = [];
    foreach ($fields as $field => $label) {
        $value = $data[$field] ?? '';
        if (empty(trim($value))) {
            $errors[] = "$label is required.";
        }
    }
    return $errors;
}

/* ─── Image Upload ─────────────────────────────────────────────────────── */

function upload_image(array $file, string $destination, string $prefix = ''): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        return null;
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg',
    };

    $filename = $prefix . uniqid() . '_' . time() . '.' . $ext;
    $target = $destination . '/' . $filename;

    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $filename;
    }

    return null;
}

function delete_image(string $filename, string $directory): bool
{
    if (empty($filename)) return false;
    $path = $directory . '/' . $filename;
    if (file_exists($path)) {
        return unlink($path);
    }
    return false;
}

/* ─── Pagination ───────────────────────────────────────────────────────── */

function paginate(Database $db, string $table, string $where = '1', array $params = [], int $perPage = ITEMS_PER_PAGE): array
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    $total = $db->count($table, $where, $params);
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    return [
        'page'       => $page,
        'per_page'   => $perPage,
        'total'      => $total,
        'total_pages'=> $totalPages,
        'offset'     => $offset,
    ];
}

function render_pagination(array $pagination, string $baseUrl): string
{
    if ($pagination['total_pages'] <= 1) return '';

    $html = '<ul class="pagination justify-content-center">';
    $p = $pagination['page'];
    $tp = $pagination['total_pages'];
    $sep = str_contains($baseUrl, '?') ? '&' : '?';

    if ($p > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $sep . 'page=' . ($p - 1) . '">&laquo;</a></li>';
    }

    $start = max(1, $p - 2);
    $end = min($tp, $p + 2);

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $p ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . $sep . 'page=' . $i . '">' . $i . '</a></li>';
    }

    if ($p < $tp) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $sep . 'page=' . ($p + 1) . '">&raquo;</a></li>';
    }

    $html .= '</ul>';
    return $html;
}

/* ─── Paystack ─────────────────────────────────────────────────────────── */

/**
 * Calls Paystack's server-side "verify transaction" endpoint. This is the
 * only source of truth for whether a card payment actually succeeded — the
 * browser's popup callback is never trusted on its own, since client-side
 * JS (and the amount it sends) can be tampered with.
 *
 * Returns the decoded response array, or null if the request itself failed
 * (network error, malformed response, provider outage). Callers must still
 * check $result['data']['status'], ['amount'], and ['currency'] themselves.
 */
/**
 * Creates a Paystack transaction and returns the decoded response, which
 * carries data.authorization_url — the full-page hosted checkout the browser
 * is sent to. Used instead of the inline popup so card entry happens on a
 * real page. Returns null if the request itself failed.
 *
 * The amount passed here is always derived server-side from the order total;
 * it is never taken from the browser.
 */
function paystack_initialize_transaction(array $payload): ?array
{
    $ch = curl_init('https://api.paystack.co/transaction/initialize');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrNo = curl_errno($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlErrNo !== 0 || $response === false) {
        error_log('[Paystack] cURL error initializing transaction: ' . $curlError);
        return null;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded) || $httpCode >= 500) {
        error_log("[Paystack] Unexpected initialize response (HTTP $httpCode): " . substr((string) $response, 0, 300));
        return null;
    }

    return $decoded;
}

function paystack_verify_transaction(string $reference): ?array
{
    $ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET        => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Accept: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrNo = curl_errno($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlErrNo !== 0 || $response === false) {
        error_log("[Paystack] cURL error verifying $reference: $curlError");
        return null;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded) || $httpCode >= 500) {
        error_log("[Paystack] Unexpected verify response (HTTP $httpCode) for $reference: " . substr((string) $response, 0, 300));
        return null;
    }

    return $decoded;
}

/* ─── CSRF Token ───────────────────────────────────────────────────────── */

function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}

function verify_csrf_token(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/* ─── Flash Messages ───────────────────────────────────────────────────── */

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function display_flash(): void
{
    $flash = get_flash();
    if ($flash) {
        $type = $flash['type'] === 'error' ? 'danger' : $flash['type'];
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo sanitize($flash['message']);
        echo '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>';
        echo '</div>';
    }
}
