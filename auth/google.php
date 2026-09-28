<?php
/**
 * Kobo Choice - Google OAuth: Step 1 (redirect to Google's consent screen)
 */
require_once __DIR__ . '/../includes/auth.php';

if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    set_flash('error', 'Google login is not configured yet. Please use email/password.');
    redirect(base_url('/login'));
}

// CSRF protection for the OAuth round-trip.
$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',
]);

redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
