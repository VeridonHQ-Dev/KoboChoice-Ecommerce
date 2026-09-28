<?php
/**
 * Kobo Choice - Google OAuth: Step 2 (callback)
 * Exchanges the auth code for the user's profile, then finds-or-creates the
 * customer and logs them in.
 */
require_once __DIR__ . '/../includes/auth.php';

function google_oauth_fail(string $message): void
{
    error_log('[Google OAuth] ' . $message);
    set_flash('error', 'Google sign-in failed. Please try again or use email/password.');
    redirect(base_url('/login'));
}

if (!empty($_GET['error'])) {
    google_oauth_fail('Provider returned error: ' . $_GET['error']);
}

// Verify state to prevent CSRF.
$state = $_GET['state'] ?? '';
if (empty($state) || empty($_SESSION['google_oauth_state']) || !hash_equals($_SESSION['google_oauth_state'], $state)) {
    google_oauth_fail('State mismatch.');
}
unset($_SESSION['google_oauth_state']);

$code = $_GET['code'] ?? '';
if (empty($code)) {
    google_oauth_fail('No authorization code.');
}

// Exchange the code for an access token.
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]),
    CURLOPT_TIMEOUT        => 15,
]);
$tokenResponse = curl_exec($ch);
if ($tokenResponse === false) {
    google_oauth_fail('Token request failed: ' . curl_error($ch));
}
curl_close($ch);

$token = json_decode($tokenResponse, true);
if (empty($token['access_token'])) {
    google_oauth_fail('No access token in response.');
}

// Fetch the user's profile.
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token['access_token']],
    CURLOPT_TIMEOUT        => 15,
]);
$profileResponse = curl_exec($ch);
curl_close($ch);

$profile = json_decode($profileResponse, true);
if (empty($profile['sub']) || empty($profile['email'])) {
    google_oauth_fail('Incomplete profile from Google.');
}

$googleId = $profile['sub'];
$email    = $profile['email'];
$name     = $profile['name'] ?? trim(($profile['given_name'] ?? '') . ' ' . ($profile['family_name'] ?? ''));
if ($name === '') {
    $name = strstr($email, '@', true);
}

// Find by google_id, else by email (link accounts), else create.
$customer = $db->fetch("SELECT * FROM customers WHERE google_id = ?", [$googleId]);

if (!$customer) {
    $existing = $db->fetch("SELECT * FROM customers WHERE email = ?", [$email]);
    if ($existing) {
        $db->update('customers', ['google_id' => $googleId], 'id = ?', [$existing['id']]);
        $customer = $existing;
    } else {
        $newId = $db->insert('customers', [
            'name'      => $name,
            'email'     => $email,
            'google_id' => $googleId,
        ]);
        $customer = $db->fetch("SELECT * FROM customers WHERE id = ?", [$newId]);
    }
}

establish_customer_session($customer);
set_flash('success', 'Welcome, ' . $customer['name'] . '!');
redirect(base_url('/'));
