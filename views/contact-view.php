<?php
/**
 * Kobo Choice - Contact View
 *
 * Two regional offices (US + Nigeria) whose values come from the settings
 * table, so they can be changed in Admin → Settings without touching code.
 * The map is derived from each office address, so updating an address moves
 * the map with it.
 */
$offices = [
    'us' => [
        'label'   => 'United States',
        // A text badge rather than a flag emoji — Windows has no flag glyphs,
        // so 🇺🇸 would degrade to bare "US" letters there anyway.
        'code'    => 'US',
        'address' => get_setting('office_us_address', '1234 Market Street, Suite 500, San Francisco, CA 94103, United States'),
        'phone'   => get_setting('office_us_phone', '+1 (415) 555-0142'),
    ],
    'ng' => [
        'label'   => 'Lagos, Nigeria',
        'code'    => 'NG',
        'address' => get_setting('office_ng_address', '12 Adeola Odeku Street, Victoria Island, Lagos, Nigeria'),
        'phone'   => get_setting('office_ng_phone', '(+234) 803 516 7873'),
    ],
];

$storeEmail = get_setting('email', 'info@kobostores.shop');

/** Google Maps embed for an address — no API key required. */
function office_map_src(string $address): string
{
    return 'https://maps.google.com/maps?q=' . rawurlencode($address) . '&t=&z=15&ie=UTF8&iwloc=&output=embed';
}

// Pre-fill the complaint order number when arriving from an order page.
$prefillOrder = isset($_GET['order']) ? (int) $_GET['order'] : 0;
$openComplaint = $prefillOrder > 0 || ($_GET['type'] ?? '') === 'complaint';

$customer = get_current_customer();
?>

<style>
.contact-page { padding: 55px 0 75px; }
.contact-head { text-align: center; max-width: 620px; margin: 0 auto 46px; }
.contact-head h1 { font-size: 34px; font-weight: 700; color: #1f2430; margin: 0 0 12px; letter-spacing: -0.4px; }
.contact-head p { font-size: 16px; font-weight: 400; color: #6b7280; line-height: 1.7; margin: 0; }

/* ── Office cards ── */
.office-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-bottom: 46px; }
.office-card {
    background: #fff; border: 1px solid #e9ebf0; border-radius: 10px; padding: 26px 24px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04), 0 12px 32px rgba(0,0,0,0.05);
    transition: border-color .2s, box-shadow .2s;
}
.office-card.is-active { border-color: #F48201; box-shadow: 0 2px 6px rgba(244,130,1,.10), 0 12px 32px rgba(244,130,1,.12); }
.office-card h3 {
    font-size: 18px; font-weight: 600; color: #1f2430; margin: 0 0 18px;
    display: flex; align-items: center; gap: 9px;
}
.office-card h3 .country-code {
    display: inline-block; background: #1f2430; color: #fff;
    font-size: 11px; font-weight: 600; letter-spacing: 0.6px;
    padding: 4px 9px; border-radius: 4px; line-height: 1;
}
.office-card.is-active h3 .country-code { background: #F48201; }
.office-row { display: flex; gap: 13px; align-items: flex-start; margin-bottom: 15px; font-size: 15px; font-weight: 400; color: #4b5563; line-height: 1.65; }
.office-row:last-of-type { margin-bottom: 0; }
.office-row .ic {
    width: 34px; height: 34px; flex-shrink: 0; border-radius: 50%;
    background: #fff7ed; color: #F48201; display: flex; align-items: center; justify-content: center; font-size: 15px;
}
.office-row a { color: #4b5563; text-decoration: none; }
.office-row a:hover { color: #F48201; }
.office-map-btn {
    margin-top: 18px; background: none; border: none; padding: 0; cursor: pointer;
    font-size: 14px; font-weight: 500; color: #F48201;
}
.office-map-btn:hover { text-decoration: underline; }

/* ── Map ── */
.contact-map { border-radius: 10px; overflow: hidden; border: 1px solid #e9ebf0; margin-bottom: 50px; line-height: 0; }
.contact-map iframe { width: 100%; height: 380px; border: 0; display: block; }

/* ── Form toggle ── */
.form-shell { max-width: 760px; margin: 0 auto; }
.form-tabs { display: flex; gap: 10px; justify-content: center; margin-bottom: 30px; flex-wrap: wrap; }
.form-tab {
    background: #fff; border: 1px solid #dcdfe4; border-radius: 8px;
    padding: 13px 30px; cursor: pointer; font-size: 15px; font-weight: 500; color: #6b7280;
    display: flex; align-items: center; gap: 9px; transition: all .2s;
}
.form-tab:hover { border-color: #b9bec6; color: #374151; }
.form-tab.is-active { background: #1f2430; border-color: #1f2430; color: #fff; }
.form-tab.is-active.complaint { background: #F48201; border-color: #F48201; }
.form-tab i { font-size: 16px; }

.form-panel {
    display: none; background: #fff; border: 1px solid #e9ebf0; border-radius: 10px;
    padding: 36px 34px; box-shadow: 0 2px 6px rgba(0,0,0,0.04), 0 12px 32px rgba(0,0,0,0.05);
}
.form-panel.is-active { display: block; }
.form-panel-head { margin-bottom: 26px; padding-bottom: 18px; border-bottom: 2px solid #F48201; }
.form-panel-head h2 { font-size: 22px; font-weight: 600; color: #1f2430; margin: 0 0 7px; }
.form-panel-head p { font-size: 15px; font-weight: 400; color: #6b7280; margin: 0; line-height: 1.6; }

.cf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.cf-full { grid-column: 1 / -1; }
.cf-field label { display: block; font-size: 15px; font-weight: 400; color: #374151; margin-bottom: 6px; }
.cf-field label .req { color: #dc2626; }
.cf-field input, .cf-field textarea, .cf-field select {
    width: 100%; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 6px;
    font-size: 16px; font-weight: 400; color: #1f2430; background: #fff; box-sizing: border-box;
    transition: border-color .15s, box-shadow .15s; font-family: inherit;
}
.cf-field input:focus, .cf-field textarea:focus, .cf-field select:focus {
    outline: none; border-color: #F48201; box-shadow: 0 0 0 3px rgba(244,130,1,.11);
}
.cf-field textarea { resize: vertical; min-height: 130px; }
.cf-hint { font-size: 13px; color: #9ca3af; margin-top: 5px; }
.cf-msg:not(:empty) { margin-bottom: 20px; }
.cf-alert { padding: 13px 16px; border-radius: 6px; font-size: 15px; line-height: 1.55; }
.cf-alert.ok { background: #ecfdf3; border: 1px solid #b7e4c7; color: #15803d; }
.cf-alert.err { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
.cf-submit { margin-top: 24px; }

@media (max-width: 767px) {
    .office-grid, .cf-grid { grid-template-columns: 1fr; }
    .contact-head h1 { font-size: 27px; }
    .form-panel { padding: 26px 20px; }
    .contact-map iframe { height: 300px; }
}
</style>

<div class="contact-page">
    <div class="container">

        <div class="contact-head">
            <h1>Get in Touch</h1>
            <p>Have a question about a product, an order, or something that went wrong?
               Reach us at either of our offices, or send a message using the forms below.</p>
        </div>

        <!-- ── Offices ─────────────────────────────────────────── -->
        <div class="office-grid">
            <?php foreach ($offices as $key => $office): ?>
            <div class="office-card <?= $key === 'us' ? 'is-active' : '' ?>" id="office-card-<?= $key ?>">
                <h3><span class="country-code"><?= sanitize($office['code']) ?></span> <?= sanitize($office['label']) ?></h3>

                <div class="office-row">
                    <span class="ic"><i class="fa fa-map-marker"></i></span>
                    <span><?= sanitize($office['address']) ?></span>
                </div>
                <div class="office-row">
                    <span class="ic"><i class="fa fa-phone"></i></span>
                    <span><a href="tel:<?= sanitize(preg_replace('/[^0-9+]/', '', $office['phone'])) ?>"><?= sanitize($office['phone']) ?></a></span>
                </div>
                <div class="office-row">
                    <span class="ic"><i class="fa fa-envelope-o"></i></span>
                    <span><a href="mailto:<?= sanitize($storeEmail) ?>"><?= sanitize($storeEmail) ?></a></span>
                </div>

                <button type="button" class="office-map-btn" data-office="<?= $key ?>"
                        data-map="<?= sanitize(office_map_src($office['address'])) ?>">
                    <i class="fa fa-map-o"></i> Show on map
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ── Map ─────────────────────────────────────────────── -->
        <div class="contact-map">
            <iframe id="office-map"
                    src="<?= sanitize(office_map_src($offices['us']['address'])) ?>"
                    title="Office location map"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen></iframe>
        </div>

        <!-- ── Forms ───────────────────────────────────────────── -->
        <div class="form-shell">

            <div class="form-tabs">
                <button type="button" class="form-tab <?= $openComplaint ? '' : 'is-active' ?>" data-panel="contact">
                    <i class="fa fa-paper-plane-o"></i> Contact Us
                </button>
                <button type="button" class="form-tab complaint <?= $openComplaint ? 'is-active' : '' ?>" data-panel="complaint">
                    <i class="fa fa-exclamation-circle"></i> Make a Complaint
                </button>
            </div>

            <!-- General enquiry -->
            <div class="form-panel <?= $openComplaint ? '' : 'is-active' ?>" id="panel-contact">
                <div class="form-panel-head">
                    <h2>Send Us a Message</h2>
                    <p>General questions about products, delivery, or your account. We usually reply within one business day.</p>
                </div>

                <form id="contact-form" novalidate>
                    <div class="cf-msg" id="contact-msg"></div>
                    <input type="hidden" name="enquiry_type" value="contact">

                    <div class="cf-grid">
                        <div class="cf-field">
                            <label>Full Name <span class="req">*</span></label>
                            <input type="text" name="name" value="<?= $customer ? sanitize($customer['name']) : '' ?>" placeholder="Your name">
                        </div>
                        <div class="cf-field">
                            <label>Email Address <span class="req">*</span></label>
                            <input type="email" name="email" value="<?= $customer ? sanitize($customer['email']) : '' ?>" placeholder="you@example.com">
                        </div>
                        <div class="cf-field cf-full">
                            <label>Subject</label>
                            <input type="text" name="subject" placeholder="What is this about?">
                        </div>
                        <div class="cf-field cf-full">
                            <label>Message <span class="req">*</span></label>
                            <textarea name="message" rows="6" placeholder="How can we help?"></textarea>
                        </div>
                    </div>

                    <div class="cf-submit">
                        <button type="submit" class="li-button li-button-dark">
                            <i class="fa fa-paper-plane-o"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>

            <!-- Complaint -->
            <div class="form-panel <?= $openComplaint ? 'is-active' : '' ?>" id="panel-complaint">
                <div class="form-panel-head">
                    <h2>Make a Complaint</h2>
                    <p>Something wrong with an order? Quote your order number and tell us what happened &mdash;
                       our team will investigate and get back to you.</p>
                </div>

                <form id="complaint-form" novalidate>
                    <div class="cf-msg" id="complaint-msg"></div>
                    <input type="hidden" name="enquiry_type" value="complaint">

                    <div class="cf-grid">
                        <div class="cf-field">
                            <label>Full Name <span class="req">*</span></label>
                            <input type="text" name="name" value="<?= $customer ? sanitize($customer['name']) : '' ?>" placeholder="Your name">
                        </div>
                        <div class="cf-field">
                            <label>Email Address <span class="req">*</span></label>
                            <input type="email" name="email" value="<?= $customer ? sanitize($customer['email']) : '' ?>" placeholder="you@example.com">
                        </div>
                        <div class="cf-field">
                            <label>Order Number <span class="req">*</span></label>
                            <input type="text" name="order_number" inputmode="numeric"
                                   value="<?= $prefillOrder > 0 ? (int) $prefillOrder : '' ?>" placeholder="e.g. 30">
                            <div class="cf-hint">The number shown on your receipt email.</div>
                        </div>
                        <div class="cf-field">
                            <label>Type of Complaint</label>
                            <select name="subject">
                                <option value="Order not delivered">Order not delivered</option>
                                <option value="Wrong item received">Wrong item received</option>
                                <option value="Damaged or faulty item">Damaged or faulty item</option>
                                <option value="Payment or refund issue">Payment or refund issue</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="cf-field cf-full">
                            <label>What happened? <span class="req">*</span></label>
                            <textarea name="message" rows="6" placeholder="Please describe the problem in as much detail as you can."></textarea>
                        </div>
                    </div>

                    <div class="cf-submit">
                        <button type="submit" class="li-button li-button-dark">
                            <i class="fa fa-exclamation-circle"></i> Submit Complaint
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    if (typeof jQuery === 'undefined') { return; }
    (function ($) {
        var API = '<?= API_URL ?>';

        // ── Form toggle ───────────────────────────────────────
        $('.form-tab').on('click', function () {
            var panel = $(this).data('panel');
            $('.form-tab').removeClass('is-active');
            $(this).addClass('is-active');
            $('.form-panel').removeClass('is-active');
            $('#panel-' + panel).addClass('is-active');
        });

        // ── Map switcher ──────────────────────────────────────
        $('.office-map-btn').on('click', function () {
            var office = $(this).data('office');
            $('#office-map').attr('src', $(this).data('map'));
            $('.office-card').removeClass('is-active');
            $('#office-card-' + office).addClass('is-active');
            $('html, body').animate({ scrollTop: $('.contact-map').offset().top - 90 }, 300);
        });

        // ── Shared submit handler ─────────────────────────────
        function wireForm(formSel, msgSel, sendingLabel) {
            $(formSel).on('submit', function (e) {
                e.preventDefault();
                var $form = $(this);
                var $btn = $form.find('button[type="submit"]');
                var $msg = $(msgSel);
                var original = $btn.html();

                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + sendingLabel);
                $msg.empty();

                $.post(API + '?action=contact_submit', $form.serialize(), function (res) {
                    $btn.prop('disabled', false).html(original);
                    if (res.success) {
                        $msg.html('<div class="cf-alert ok">' + res.message + '</div>');
                        $form[0].reset();
                    } else {
                        $msg.html('<div class="cf-alert err">' + res.message + '</div>');
                    }
                    $('html, body').animate({ scrollTop: $msg.offset().top - 110 }, 300);
                }, 'json').fail(function () {
                    $btn.prop('disabled', false).html(original);
                    $msg.html('<div class="cf-alert err">Sorry, something went wrong. Please try again.</div>');
                });
            });
        }

        wireForm('#contact-form', '#contact-msg', 'Sending...');
        wireForm('#complaint-form', '#complaint-msg', 'Submitting...');
    })(jQuery);
});
</script>
