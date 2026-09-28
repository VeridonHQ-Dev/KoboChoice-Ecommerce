<?php
/**
 * Kobo Choice - Settings save handler
 *
 * Split out of settings/edit.php and run by admin/index.php before any markup
 * is echoed. The redirect below sets a Location header, which fails once the
 * sidebar and topbar have been written to the response — that produced a
 * "Cannot modify header information" warning and a half-rendered page every
 * time settings were saved.
 */

global $db;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

/* Only keys the form actually offers are writable. The previous version looped
   over the whole of $_POST and INSERTed anything it did not recognise, so any
   extra field in the request became a new settings row. */
$allowed = [
    'site_name',
    'site_tagline',
    'currency',
    'currency_symbol',
    'phone',
    'email',
    'address',
    'footer_about',
    'office_us_address',
    'office_us_phone',
    'office_ng_address',
    'office_ng_phone',
    'coupon_code',
];

foreach ($allowed as $key) {
    if (!array_key_exists($key, $_POST)) {
        continue;
    }
    $value = is_string($_POST[$key]) ? trim($_POST[$key]) : '';

    $existing = $db->fetch("SELECT `key` FROM settings WHERE `key` = ?", [$key]);
    if ($existing) {
        $db->update('settings', ['value' => $value], '`key` = ?', [$key]);
    } else {
        $db->insert('settings', ['key' => $key, 'value' => $value]);
    }
}

set_flash('success', 'Settings updated successfully.');
redirect(admin_url('/?view=settings'));
