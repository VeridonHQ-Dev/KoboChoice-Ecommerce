<?php
$pageTitle = 'Login / Register';
$bodyClass = 'inner-page';
$hideShipping = true;
// Standalone auth page: no site header/footer, just the form card + logo.
$hideChrome = true;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/views/login-view.php';
require_once __DIR__ . '/includes/footer.php';
