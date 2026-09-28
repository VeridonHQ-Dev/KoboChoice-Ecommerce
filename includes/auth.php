<?php
/**
 * Kobo Choice - Authentication & Session Guard
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/mailer.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance();

/* ─── Admin Auth ───────────────────────────────────────────────────────── */

function admin_login(array $credentials): array
{
    global $db;

    $admin = $db->fetch(
        "SELECT * FROM admins WHERE email = ? AND is_active = 1",
        [$credentials['email']]
    );

    if (!$admin || !password_verify($credentials['password'], $admin['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

    $db->insert('admin_sessions', [
        'admin_id'    => $admin['id'],
        'token'       => $token,
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'expires_at'  => $expires,
    ]);

    $db->update('admins', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$admin['id']]);

    $db->insert('admin_logs', [
        'admin_id'    => $admin['id'],
        'action'      => 'login',
        'description' => 'Admin logged in',
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_token'] = $token;
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];

    return ['success' => true, 'admin' => $admin];
}

function admin_logout(): void
{
    global $db;

    if (!empty($_SESSION['admin_id']) && !empty($_SESSION['admin_token'])) {
        $db->delete('admin_sessions', 'admin_id = ? AND token = ?', [
            $_SESSION['admin_id'],
            $_SESSION['admin_token'],
        ]);

        $db->insert('admin_logs', [
            'admin_id'    => $_SESSION['admin_id'],
            'action'      => 'logout',
            'description' => 'Admin logged out',
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    }

    session_unset();
    session_destroy();
}

function is_admin_logged_in(): bool
{
    global $db;

    if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_token'])) {
        return false;
    }

    $session = $db->fetch(
        "SELECT * FROM admin_sessions WHERE admin_id = ? AND token = ? AND expires_at > NOW()",
        [$_SESSION['admin_id'], $_SESSION['admin_token']]
    );

    return $session !== null;
}

function get_current_admin(): ?array
{
    global $db;

    if (!is_admin_logged_in()) return null;

    return $db->fetch("SELECT * FROM admins WHERE id = ?", [$_SESSION['admin_id']]);
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        redirect(admin_url('/?action=login'));
    }
}

function require_super_admin(): void
{
    require_admin();
    if (($_SESSION['admin_role'] ?? '') !== 'super_admin') {
        set_flash('error', 'You do not have permission to access this page.');
        redirect(admin_url('/'));
    }
}

function is_super_admin(): bool
{
    return ($_SESSION['admin_role'] ?? '') === 'super_admin';
}

/* ─── Customer Auth ────────────────────────────────────────────────────── */

/**
 * Establish a logged-in session for a customer record (shared by password
 * login and Google OAuth). Creates a session token row and sets $_SESSION.
 */
function establish_customer_session(array $customer): void
{
    global $db;

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

    $db->insert('customer_sessions', [
        'customer_id' => $customer['id'],
        'token'       => $token,
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'expires_at'  => $expires,
    ]);

    $_SESSION['customer_id'] = $customer['id'];
    $_SESSION['customer_token'] = $token;
    $_SESSION['customer_name'] = $customer['name'];
    $_SESSION['customer_email'] = $customer['email'];
}

function customer_login(array $credentials): array
{
    global $db;

    // Accept an email address OR a phone number as the login identifier.
    $identifier = trim($credentials['login'] ?? $credentials['email'] ?? '');

    $customer = $db->fetch(
        "SELECT * FROM customers WHERE email = ? OR phone = ? LIMIT 1",
        [$identifier, $identifier]
    );

    if (!$customer || empty($customer['password_hash'])
        || !password_verify($credentials['password'], $customer['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid login details. Check your email/phone and password.'];
    }

    if (isset($customer['status']) && $customer['status'] === 'disabled') {
        return ['success' => false, 'message' => 'Account Disabled and Under investigation, Please Contact Kobo Choice Admin for resolution.'];
    }

    establish_customer_session($customer);

    return ['success' => true, 'customer' => $customer];
}

function customer_register(array $data): array
{
    global $db;

    $errors = required_fields($data, [
        'first_name' => 'First name',
        'last_name'  => 'Last name',
        'email'      => 'Email',
        'password'   => 'Password',
    ]);

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    if (!is_email($data['email'])) {
        return ['success' => false, 'errors' => ['Please enter a valid email address.']];
    }

    $pwErrors = password_strength_errors($data['password'] ?? '');
    if (!empty($pwErrors)) {
        return ['success' => false, 'errors' => ['Password requirements: ' . implode(', ', $pwErrors) . '.']];
    }

    if (($data['password'] ?? '') !== ($data['confirm_password'] ?? '')) {
        return ['success' => false, 'errors' => ['Passwords do not match.']];
    }

    $existing = $db->fetch("SELECT id FROM customers WHERE email = ?", [$data['email']]);
    if ($existing) {
        return ['success' => false, 'errors' => ['An account with this email already exists.']];
    }

    // Optional phone: reject if it's already registered to someone else.
    $phone = trim($data['phone'] ?? '');
    if ($phone !== '') {
        $phoneTaken = $db->fetch("SELECT id FROM customers WHERE phone = ?", [$phone]);
        if ($phoneTaken) {
            return ['success' => false, 'errors' => ['This phone number is already registered.']];
        }
    }

    // Validate optional gender against the allowed set.
    $gender = $data['gender'] ?? '';
    if (!in_array($gender, ['male', 'female', 'other'], true)) {
        $gender = null;
    }

    // Validate optional date of birth (YYYY-MM-DD).
    $dob = trim($data['date_of_birth'] ?? '');
    if ($dob !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$d || $d->format('Y-m-d') !== $dob || $d > new DateTime()) {
            return ['success' => false, 'errors' => ['Please enter a valid date of birth.']];
        }
    } else {
        $dob = null;
    }

    $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

    $customerId = $db->insert('customers', [
        'name'          => $fullName,
        'email'         => $data['email'],
        'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
        'phone'         => $phone ?: null,
        'date_of_birth' => $dob,
        'gender'        => $gender,
        'address'       => $data['address'] ?? null,
    ]);

    return customer_login([
        'login'    => $data['email'],
        'password' => $data['password'],
    ]);
}

function customer_logout(): void
{
    global $db;

    if (!empty($_SESSION['customer_id']) && !empty($_SESSION['customer_token'])) {
        $db->delete('customer_sessions', 'customer_id = ? AND token = ?', [
            $_SESSION['customer_id'],
            $_SESSION['customer_token'],
        ]);
    }

    unset($_SESSION['customer_id'], $_SESSION['customer_token'], $_SESSION['customer_name'], $_SESSION['customer_email']);
}

/* ─── Password Reset ──────────────────────────────────────────────────── */

/**
 * Step 1: customer submits their email. Generates a 6-digit code, stores its
 * hash with a 10-minute expiry, and emails it. Always returns a generic
 * success message regardless of whether the email exists, so the endpoint
 * can't be used to enumerate registered accounts.
 */
function customer_request_password_reset(string $email): array
{
    global $db;

    $email = trim($email);
    $generic = ['success' => true, 'message' => 'If an account exists for that email, a reset code has been sent.'];

    if (!is_email($email)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    $customer = $db->fetch("SELECT id, name, email FROM customers WHERE email = ?", [$email]);
    if (!$customer) {
        return $generic;
    }

    $recent = $db->fetch(
        "SELECT created_at FROM password_resets WHERE customer_id = ? ORDER BY created_at DESC LIMIT 1",
        [$customer['id']]
    );
    if ($recent && (time() - strtotime($recent['created_at'])) < PASSWORD_RESET_RESEND_COOLDOWN_SECONDS) {
        return ['success' => false, 'message' => 'Please wait a moment before requesting another code.'];
    }

    // Invalidate any still-open codes so only the newest one is usable.
    $db->update(
        'password_resets',
        ['expires_at' => date('Y-m-d H:i:s', time() - 1)],
        'customer_id = ? AND used_at IS NULL',
        [$customer['id']]
    );

    $code = (string)random_int(100000, 999999);
    $db->insert('password_resets', [
        'customer_id' => $customer['id'],
        'code_hash'   => hash('sha256', $code),
        'expires_at'  => date('Y-m-d H:i:s', time() + PASSWORD_RESET_TTL_SECONDS),
    ]);

    send_password_reset_email($customer['email'], $customer['name'], $code);

    return $generic;
}

/**
 * Shared validator used by both the "verify code" step and the final
 * "reset password" step, so brute-force attempts are capped no matter which
 * endpoint an attacker hits.
 */
function verify_password_reset_code(int $customerId, string $code): array
{
    global $db;

    $reset = $db->fetch(
        "SELECT * FROM password_resets WHERE customer_id = ? AND used_at IS NULL ORDER BY created_at DESC LIMIT 1",
        [$customerId]
    );

    if (!$reset) {
        return ['success' => false, 'message' => 'No reset code found. Please request a new one.'];
    }

    if (strtotime($reset['expires_at']) < time()) {
        return ['success' => false, 'message' => 'This reset code has expired. Please request a new one.'];
    }

    if ($reset['attempts'] >= PASSWORD_RESET_MAX_ATTEMPTS) {
        $db->update('password_resets', ['expires_at' => date('Y-m-d H:i:s', time() - 1)], 'id = ?', [$reset['id']]);
        return ['success' => false, 'message' => 'Too many incorrect attempts. Please request a new code.'];
    }

    if (!hash_equals($reset['code_hash'], hash('sha256', trim($code)))) {
        $attempts = $reset['attempts'] + 1;
        $db->update('password_resets', ['attempts' => $attempts], 'id = ?', [$reset['id']]);

        if ($attempts >= PASSWORD_RESET_MAX_ATTEMPTS) {
            $db->update('password_resets', ['expires_at' => date('Y-m-d H:i:s', time() - 1)], 'id = ?', [$reset['id']]);
            return ['success' => false, 'message' => 'Too many incorrect attempts. Please request a new code.'];
        }

        $remaining = PASSWORD_RESET_MAX_ATTEMPTS - $attempts;
        return ['success' => false, 'message' => "Incorrect code. $remaining attempt(s) remaining."];
    }

    return ['success' => true, 'reset_id' => $reset['id']];
}

/**
 * Step 2: customer submits the 6-digit code for a quick validity check
 * before we ask them for a new password.
 */
function customer_verify_reset_code(string $email, string $code): array
{
    global $db;

    $customer = $db->fetch("SELECT id FROM customers WHERE email = ?", [trim($email)]);
    if (!$customer) {
        return ['success' => false, 'message' => 'Invalid or expired code. Please request a new one.'];
    }

    $result = verify_password_reset_code((int)$customer['id'], $code);
    if (!$result['success']) {
        return $result;
    }

    return ['success' => true, 'message' => 'Code verified.'];
}

/**
 * Step 3: re-validates the code (defense in depth — never trust the client),
 * then sets the new password, consumes the code, revokes existing sessions,
 * and logs the customer straight back into their account.
 */
function customer_reset_password(string $email, string $code, string $newPassword, string $confirmPassword): array
{
    global $db;

    $customer = $db->fetch("SELECT * FROM customers WHERE email = ?", [trim($email)]);
    if (!$customer) {
        return ['success' => false, 'message' => 'Invalid or expired code. Please request a new one.'];
    }

    $result = verify_password_reset_code((int)$customer['id'], $code);
    if (!$result['success']) {
        return $result;
    }

    $pwErrors = password_strength_errors($newPassword);
    if (!empty($pwErrors)) {
        return ['success' => false, 'message' => 'Password requirements: ' . implode(', ', $pwErrors) . '.'];
    }

    if ($newPassword !== $confirmPassword) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }

    $db->update('customers', ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)], 'id = ?', [$customer['id']]);
    $db->update('password_resets', ['used_at' => date('Y-m-d H:i:s')], 'id = ?', [$result['reset_id']]);

    // Revoke any existing sessions for security, then log the customer back
    // into their account and order history.
    $db->delete('customer_sessions', 'customer_id = ?', [$customer['id']]);
    establish_customer_session($customer);

    return ['success' => true, 'customer' => $customer];
}

function is_customer_logged_in(): bool
{
    global $db;

    if (empty($_SESSION['customer_id']) || empty($_SESSION['customer_token'])) {
        return false;
    }

    $session = $db->fetch(
        "SELECT * FROM customer_sessions WHERE customer_id = ? AND token = ? AND expires_at > NOW()",
        [$_SESSION['customer_id'], $_SESSION['customer_token']]
    );

    return $session !== null;
}

function get_current_customer(): ?array
{
    global $db;

    if (!is_customer_logged_in()) return null;

    return $db->fetch("SELECT * FROM customers WHERE id = ?", [$_SESSION['customer_id']]);
}

function require_customer(): void
{
    if (!is_customer_logged_in()) {
        set_flash('error', 'Please login to continue.');
        redirect(base_url('/login'));
    }
}

/* ─── Cart Helpers ─────────────────────────────────────────────────────── */

function get_cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function set_cart(array $cart): void
{
    $_SESSION['cart'] = $cart;
}

function cart_count(): int
{
    $cart = get_cart();
    return array_sum(array_column($cart, 'quantity'));
}

function cart_total(): float
{
    global $db;
    $cart = get_cart();
    $total = 0;

    foreach ($cart as $item) {
        $product = $db->fetch("SELECT price FROM products WHERE id = ? AND status = 'active'", [$item['product_id']]);
        if ($product) {
            $total += $product['price'] * $item['quantity'];
        }
    }

    return $total;
}
