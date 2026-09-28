<?php
/**
 * Kobo Choice - Application Configuration
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// ─── Environment Configuration (.env loader) ──────────────────────────────
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        if ($val === 'true' || $val === '(true)') return true;
        if ($val === 'false' || $val === '(false)') return false;
        if ($val === 'null' || $val === '(null)') return null;
        if ($val === 'empty' || $val === '(empty)') return '';
        return $val;
    }
}

define('BASE_URL', env('BASE_URL', '/myweb/kobostoresecommerce'));
define('ADMIN_PATH', env('ADMIN_PATH', BASE_URL . '/admin'));
define('API_URL', BASE_URL . '/api/index.php');

/* Absolute site URL, used for links inside emails (which are opened outside
 * the browser, so a relative path would not resolve). Update this to the live
 * domain — e.g. 'https://kobochoice.com' — when the site is deployed. */
define('SITE_URL', env('SITE_URL', 'http://localhost' . BASE_URL));

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'kobo_stores'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

define('UPLOAD_PATH', ROOT_PATH . '/public/uploads');
define('PRODUCT_IMG_PATH', UPLOAD_PATH . '/products');
define('CATEGORY_IMG_PATH', UPLOAD_PATH . '/categories');
define('AVATAR_IMG_PATH', UPLOAD_PATH . '/avatars');
define('PRODUCT_IMG_URL', BASE_URL . '/public/uploads/products');
define('CATEGORY_IMG_URL', BASE_URL . '/public/uploads/categories');

define('SITE_NAME', env('SITE_NAME', 'Kobo Choice'));
define('CURRENCY', env('CURRENCY', 'USD'));
define('CURRENCY_SYMBOL', env('CURRENCY_SYMBOL', '$'));

/* ─── Paystack (card payments) ──────────────────────────────────────────
 * Test-mode keys from https://dashboard.paystack.com/#/settings/developer
 * The secret key must NEVER be sent to the browser or logged — it is only
 * used server-side to initialize/verify transactions over HTTPS.
 *
 * PAYSTACK_CURRENCY must match a currency enabled on the Paystack account
 * AND the actual scale of the numbers stored in `products.price` /
 * `orders.total`. Those numbers were originally entered as Naira amounts
 * (only the on-screen symbol was later changed to "$"), so this is set to
 * NGN to charge the correct real-world amount. Change only if the product
 * prices are converted to real USD/GHS/ZAR figures AND the Paystack
 * account has that currency enabled.
 */
define('PAYSTACK_PUBLIC_KEY', env('PAYSTACK_PUBLIC_KEY', ''));
define('PAYSTACK_SECRET_KEY', env('PAYSTACK_SECRET_KEY', ''));
define('PAYSTACK_CURRENCY', env('PAYSTACK_CURRENCY', 'NGN'));

/* Sales tax applied to the cart subtotal at checkout (0.10 = 10%). */
define('TAX_RATE', (float) env('TAX_RATE', 0.10));

define('ITEMS_PER_PAGE', (int) env('ITEMS_PER_PAGE', 12));
define('ADMIN_ITEMS_PER_PAGE', (int) env('ADMIN_ITEMS_PER_PAGE', 15));

define('MAX_PRODUCT_IMAGES', 6);
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024);

define('SESSION_LIFETIME', 7 * 24 * 60 * 60);

date_default_timezone_set(env('APP_TIMEZONE', 'Africa/Lagos'));

/* ─── Google OAuth (Sign in with Google) ───────────────────────────────
 * Create credentials at https://console.cloud.google.com/apis/credentials
 * (OAuth client ID → Web application). Add this Authorized redirect URI:
 *   http://localhost/myweb/kobostoresecommerce/auth/google-callback.php
 * Then paste the Client ID / Secret in .env. Leaving them blank disables the
 * button gracefully (it shows a "not configured" message).
 */
define('GOOGLE_CLIENT_ID', env('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REDIRECT_URI', env('GOOGLE_REDIRECT_URI', SITE_URL . '/auth/google-callback.php'));

/* Apple Sign In requires a paid Apple Developer account + a public HTTPS
 * domain, so it cannot run on a local http://localhost site. Leave disabled. */
define('APPLE_ENABLED', (bool) env('APPLE_ENABLED', false));

/* ─── SMTP (outbound email — password reset codes, etc.) ────────────────
 * Using Gmail: enable 2-Step Verification on the sending Google account,
 * then create an "App Password" at https://myaccount.google.com/apppasswords
 * and paste the 16-character value in .env (NOT the normal Gmail password).
 * Leaving these blank disables email sending gracefully.
 */
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int) env('SMTP_PORT', 587));
define('SMTP_ENCRYPTION', env('SMTP_ENCRYPTION', 'tls'));
define('SMTP_USERNAME', env('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', ''));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', ''));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', SITE_NAME));

/* ─── Password Reset (forgot password codes) ─────────────────────────── */
define('PASSWORD_RESET_TTL_SECONDS', 10 * 60);
define('PASSWORD_RESET_MAX_ATTEMPTS', 5);
define('PASSWORD_RESET_RESEND_COOLDOWN_SECONDS', 60);

/* ─── Email Deliverability & DKIM ──────────────────────────────────────
 *
 * When SMTP_HOST is smtp.gmail.com, DKIM is handled automatically by
 * Google's outbound servers as long as the SMTP username is a Gmail/Google
 * Workspace address. The settings below let you add your own DKIM signature
 * when you switch to a private SMTP relay (Amazon SES, SendGrid, Mailgun,
 * etc.) or to your own domain's SMTP.
 *
 * DKIM key file paths should be absolute.
 *─────────────────────────────────────────────────────────────────────── */
define('MAIL_DKIM_DOMAIN',      env('MAIL_DKIM_DOMAIN', ''));   // e.g. 'kobostores.com'
define('MAIL_DKIM_PRIVATE_KEY', env('MAIL_DKIM_PRIVATE_KEY', ''));   // absolute path to the private key
define('MAIL_DKIM_SELECTOR',    env('MAIL_DKIM_SELECTOR', 'default'));
define('MAIL_DKIM_PASSPHRASE',  env('MAIL_DKIM_PASSPHRASE', ''));
define('MAIL_DKIM_IDENTITY',    env('MAIL_DKIM_IDENTITY', ''));   // usually the From address or empty

/* Additional headers that help inbox placement */
define('MAIL_USE_PRIORITY_HEADERS', (bool) env('MAIL_USE_PRIORITY_HEADERS', true));   // X-Priority / Importance
define('MAIL_USE_LIST_UNSUBSCRIBE', (bool) env('MAIL_USE_LIST_UNSUBSCRIBE', true));   // List-Unsubscribe header
define('MAIL_UNSUBSCRIBE_EMAIL',    env('MAIL_UNSUBSCRIBE_EMAIL', SMTP_FROM_EMAIL));
