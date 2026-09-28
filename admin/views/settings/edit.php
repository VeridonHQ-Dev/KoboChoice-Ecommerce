<?php
global $db;

/* The POST is handled by settings/save.php, which admin/index.php runs before
   any markup is echoed — redirect() sends a Location header, and by the time
   this view is included the sidebar and topbar have already been written to
   the response. */

$settings = $db->fetchAll("SELECT * FROM `settings` ORDER BY `key` ASC");
$settingsMap = [];
foreach ($settings as $s) {
    $settingsMap[$s['key']] = $s['value'];
}
?>

<style>
/* The two-column split was an inline style, which no media query can override
   without !important — so on a phone every field was squeezed to ~140px and
   values like the tagline and phone number were cut off mid-word. */
.settings-head { margin-bottom: 20px; }
.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
}
.settings-save { width: 100%; }

@media (max-width: 991px) {
    /* One column: the fields need the full width to show their values. */
    .settings-grid { grid-template-columns: 1fr; gap: 0; }
}

@media (max-width: 576px) {
    .settings-head { font-size: 21px; margin-bottom: 14px; }
    /* "Offices (Contact Page)" was wrapping onto three lines in a narrow
       column; with the full width it fits, and this keeps it tidy. */
    .settings-grid .admin-card .card-header h3 { font-size: 15px; }
    .settings-grid .admin-card { padding: 16px; margin-bottom: 14px; }
    .settings-grid .admin-form .form-group { margin-bottom: 14px; }
    /* iOS zooms the page in when a focused input is under 16px. */
    .settings-grid .admin-form input,
    .settings-grid .admin-form textarea { font-size: 16px; }
    .settings-grid .admin-form textarea { min-height: 88px; }
    /* Currency and its symbol still pair up fine side by side. */
    .settings-grid .admin-form .form-row { grid-template-columns: 1fr 1fr; gap: 10px; }
    /* The form is long on a phone; keep Save reachable without scrolling to
       the very bottom. */
    .settings-save {
        position: sticky;
        bottom: 12px;
        box-shadow: 0 6px 18px rgba(37, 99, 235, .35);
    }
}
</style>

<h2 class="settings-head">Site Settings</h2>

<form method="POST">
    <div class="settings-grid">
        <div>
            <div class="admin-card">
                <div class="card-header"><h3>General</h3></div>
                <div class="admin-form">
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="site_name" value="<?= sanitize($settingsMap['site_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Tagline</label>
                        <input type="text" name="site_tagline" value="<?= sanitize($settingsMap['site_tagline'] ?? '') ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Currency</label>
                            <input type="text" name="currency" value="<?= sanitize($settingsMap['currency'] ?? 'NGN') ?>">
                        </div>
                        <div class="form-group">
                            <label>Currency Symbol</label>
                            <input type="text" name="currency_symbol" value="<?= sanitize($settingsMap['currency_symbol'] ?? '$') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Contact</h3></div>
                <div class="admin-form">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?= sanitize($settingsMap['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= sanitize($settingsMap['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" rows="3"><?= sanitize($settingsMap['address'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="admin-card">
                <div class="card-header"><h3>Footer</h3></div>
                <div class="admin-form">
                    <div class="form-group">
                        <label>About Text</label>
                        <textarea name="footer_about" rows="4"><?= sanitize($settingsMap['footer_about'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Offices (Contact Page)</h3></div>
                <div class="admin-form">
                    <div class="form-group">
                        <label>United States &mdash; Address</label>
                        <textarea name="office_us_address" rows="2"><?= sanitize($settingsMap['office_us_address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>United States &mdash; Phone</label>
                        <input type="text" name="office_us_phone" value="<?= sanitize($settingsMap['office_us_phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Nigeria &mdash; Address</label>
                        <textarea name="office_ng_address" rows="2"><?= sanitize($settingsMap['office_ng_address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Nigeria &mdash; Phone</label>
                        <input type="text" name="office_ng_phone" value="<?= sanitize($settingsMap['office_ng_phone'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header"><h3>Coupon</h3></div>
                <div class="admin-form">
                    <div class="form-group">
                        <label>Default Coupon Code</label>
                        <input type="text" name="coupon_code" value="<?= sanitize($settingsMap['coupon_code'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="admin-btn admin-btn-primary admin-btn-lg settings-save">
                <i class="fa fa-save"></i> Save Settings
            </button>
        </div>
    </div>
</form>
