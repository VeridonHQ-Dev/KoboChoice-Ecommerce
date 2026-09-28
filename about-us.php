<?php
/**
 * Kobo Choice - About Us
 *
 * Replaces the old static about-us.php, whose navigation pointed at theme
 * demo files (index.html, contact.html, …) that do not exist in this project.
 */
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'About Us';
$bodyClass = 'inner-page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/views/about-view.php';
require_once __DIR__ . '/includes/footer.php';
