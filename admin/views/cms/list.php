<?php
/**
 * Kobo Choice - CMS: homepage banners & picture ads
 *
 * Manages only promotional artwork (hero slider, ads grid, promo pair,
 * featured banner). Product photography lives on the Products screen.
 */
global $db;

require_once ROOT_PATH . '/api/CmsAPI.php';

$sections = CmsAPI::SECTIONS;
$activeSection = $_GET['section'] ?? 'slider';
if (!isset($sections[$activeSection])) {
    $activeSection = 'slider';
}

$banners = $db->fetchAll(
    "SELECT * FROM cms_banners WHERE section = ? ORDER BY sort_order ASC, id ASC",
    [$activeSection]
);

$counts = [];
foreach ($db->fetchAll("SELECT section, COUNT(*) AS n FROM cms_banners GROUP BY section") as $row) {
    $counts[$row['section']] = (int) $row['n'];
}

$supportsText = $sections[$activeSection]['text'];
?>

<style>
.cms-head { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:18px; }
.cms-head h2 { margin:0; font-size:20px; }
.cms-head p { margin:4px 0 0; font-size:13px; color:#64748b; }
.cms-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:22px; }
.cms-tabs a {
    padding:8px 16px; border:1px solid #e2e8f0; border-radius:8px; background:#fff;
    font-size:13px; color:#475569; text-decoration:none; transition:all .15s;
}
.cms-tabs a:hover { border-color:#cbd5e1; }
.cms-tabs a.is-active { background:#1f2430; border-color:#1f2430; color:#fff; }

.cms-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:18px; }
.cms-card {
    background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;
    display:flex; flex-direction:column; transition:box-shadow .15s;
}
.cms-card:hover { box-shadow:0 6px 18px rgba(0,0,0,.07); }
.cms-card.is-hidden { opacity:.55; }
.cms-thumb {
    height:130px; background:#f8fafc url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg"/>');
    display:flex; align-items:center; justify-content:center; border-bottom:1px solid #eef2f6; position:relative;
}
.cms-thumb img { max-width:100%; max-height:130px; object-fit:contain; display:block; }
.cms-order-badge {
    position:absolute; top:8px; left:8px; background:rgba(15,23,42,.82); color:#fff;
    font-size:11px; font-weight:600; padding:3px 8px; border-radius:20px;
}
.cms-hidden-badge {
    position:absolute; top:8px; right:8px; background:#64748b; color:#fff;
    font-size:10px; font-weight:600; padding:3px 8px; border-radius:20px; text-transform:uppercase;
}
.cms-card-body { padding:12px 14px; flex:1; }
.cms-card-body .cms-caption { font-size:14px; font-weight:600; color:#1e293b; margin-bottom:3px; word-break:break-word; }
.cms-card-body .cms-meta { font-size:12px; color:#94a3b8; word-break:break-all; }
.cms-card-foot { padding:10px 14px; border-top:1px solid #f1f5f9; display:flex; gap:6px; flex-wrap:wrap; }

.cms-empty { padding:50px 20px; text-align:center; color:#94a3b8; border:2px dashed #e2e8f0; border-radius:10px; }

/* ── Modal ── */
.cms-modal-overlay {
    display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,.55);
    align-items:flex-start; justify-content:center; padding:30px 20px; overflow-y:auto;
}
.cms-modal-overlay.is-open { display:flex; }
.cms-modal {
    background:#fff; border-radius:10px; width:100%; max-width:560px;
    box-shadow:0 24px 60px rgba(0,0,0,.28); animation:cmsIn .18s ease-out; margin:auto;
}
@keyframes cmsIn { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:none} }
.cms-modal-head { display:flex; align-items:center; padding:18px 22px; border-bottom:1px solid #e2e8f0; }
.cms-modal-head h3 { margin:0; font-size:17px; color:#1e293b; }
.cms-modal-close {
    margin-left:auto; background:none; border:none; width:32px; height:32px; border-radius:6px;
    font-size:22px; line-height:1; color:#94a3b8; cursor:pointer;
}
.cms-modal-close:hover { background:#f1f5f9; color:#1e293b; }
.cms-modal-body { padding:20px 22px; }
.cms-modal-foot { padding:14px 22px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end; gap:10px; border-radius:0 0 10px 10px; }
.cms-field { margin-bottom:15px; }
.cms-field label { display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:5px; }
.cms-field input[type=text], .cms-field input[type=number], .cms-field input[type=file], .cms-field textarea {
    width:100%; padding:9px 12px; border:1px solid #dbe1e8; border-radius:6px; font-size:14px;
    box-sizing:border-box; font-family:inherit;
}
.cms-field input:focus, .cms-field textarea:focus { outline:none; border-color:#F48201; box-shadow:0 0 0 3px rgba(244,130,1,.11); }
.cms-field .cms-hint { font-size:12px; color:#94a3b8; margin-top:4px; }
.cms-checkline { display:flex; align-items:center; gap:8px; font-size:14px; color:#334155; }
.cms-preview { margin-top:10px; }
.cms-preview img { max-width:100%; max-height:120px; border:1px solid #e2e8f0; border-radius:6px; }
.cms-text-fields { display:<?= $supportsText ? 'block' : 'none' ?>; }
</style>

<div class="cms-head">
    <div>
        <h2>Homepage Banners &amp; Picture Ads</h2>
        <p>Manage the promotional artwork on the homepage. Product photos are managed under Products.</p>
    </div>
    <button type="button" class="admin-btn admin-btn-primary" id="cmsAddBtn">
        <i class="fa fa-plus"></i> Add Banner
    </button>
</div>

<div class="cms-tabs">
    <?php foreach ($sections as $key => $meta): ?>
        <a href="<?= admin_url('/?view=cms&section=' . $key) ?>" class="<?= $activeSection === $key ? 'is-active' : '' ?>">
            <?= sanitize($meta['label']) ?> (<?= $counts[$key] ?? 0 ?>)
        </a>
    <?php endforeach; ?>
</div>

<?php if ($activeSection === 'ads_grid'): ?>
<div class="admin-alert admin-alert-info" style="margin-bottom:18px;padding:12px 16px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;font-size:13px;color:#1e40af;">
    <i class="fa fa-info-circle"></i>
    The homepage shows these as a tile row (a swipeable carousel on mobile). Eight tiles is the intended layout &mdash; adding more simply extends the row.
</div>
<?php endif; ?>

<?php if (empty($banners)): ?>
    <div class="cms-empty">
        <i class="fa fa-picture-o" style="font-size:34px;display:block;margin-bottom:10px;"></i>
        No banners in this section yet.
    </div>
<?php else: ?>
<div class="cms-grid">
    <?php foreach ($banners as $b): ?>
    <div class="cms-card <?= $b['is_active'] ? '' : 'is-hidden' ?>">
        <div class="cms-thumb">
            <span class="cms-order-badge"><?= (int) $b['sort_order'] ?></span>
            <?php if (!$b['is_active']): ?><span class="cms-hidden-badge">Hidden</span><?php endif; ?>
            <img src="<?= sanitize(BASE_URL . '/' . ltrim($b['image'], '/')) ?>" alt="">
        </div>
        <div class="cms-card-body">
            <div class="cms-caption"><?= sanitize($b['heading'] ?: ($b['caption'] ?: 'Untitled')) ?></div>
            <div class="cms-meta"><?= sanitize($b['image']) ?></div>
        </div>
        <div class="cms-card-foot">
            <button type="button" class="admin-btn admin-btn-outline admin-btn-sm cms-edit"
                    data-banner='<?= sanitize(json_encode($b, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>
                <i class="fa fa-pencil"></i> Edit
            </button>
            <button type="button" class="admin-btn admin-btn-outline admin-btn-sm cms-toggle" data-id="<?= (int) $b['id'] ?>">
                <i class="fa fa-<?= $b['is_active'] ? 'eye-slash' : 'eye' ?>"></i> <?= $b['is_active'] ? 'Hide' : 'Show' ?>
            </button>
            <button type="button" class="admin-btn admin-btn-danger admin-btn-sm cms-delete" data-id="<?= (int) $b['id'] ?>">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Add / Edit modal ────────────────────────────────────────── -->
<div class="cms-modal-overlay" id="cmsModal" role="dialog" aria-modal="true">
    <div class="cms-modal">
        <form id="cmsForm" enctype="multipart/form-data">
            <div class="cms-modal-head">
                <h3 id="cmsModalTitle">Add Banner</h3>
                <button type="button" class="cms-modal-close" id="cmsModalClose" aria-label="Close">&times;</button>
            </div>

            <div class="cms-modal-body">
                <input type="hidden" name="id" id="cmsId" value="">
                <input type="hidden" name="section" value="<?= sanitize($activeSection) ?>">

                <div class="cms-field">
                    <label>Image <span style="color:#dc2626;">*</span></label>
                    <input type="file" name="image" id="cmsImage" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="cms-hint">JPG, PNG, GIF or WebP &mdash; up to 4MB. Leave empty when editing to keep the current image.</div>
                    <div class="cms-preview" id="cmsPreview"></div>
                </div>

                <div class="cms-text-fields">
                    <div class="cms-field">
                        <label>Small line above the title</label>
                        <input type="text" name="subheading" id="cmsSubheading" placeholder="Sale Offer <span> -20% Off</span> This Week">
                        <div class="cms-hint">Basic HTML like &lt;span&gt; is allowed here for the highlighted words.</div>
                    </div>
                    <div class="cms-field">
                        <label><?= $activeSection === 'static_home' ? 'First title line' : 'Main title' ?></label>
                        <input type="text" name="heading" id="cmsHeading"
                               placeholder="<?= $activeSection === 'static_home' ? 'Featured Product' : 'Chamcham Galaxy S9 | S9+' ?>">
                    </div>
                    <div class="cms-field">
                        <label>Price line</label>
                        <input type="text" name="price_text" id="cmsPriceText" placeholder="Starting at <span>$589.00</span>">
                    </div>
                    <div class="cms-field">
                        <label>Button text</label>
                        <input type="text" name="button_text" id="cmsButtonText" placeholder="Order Now">
                    </div>
                </div>

                <div class="cms-field">
                    <label>
                        <?= $activeSection === 'static_home' ? 'Second title line' : 'Caption' ?>
                        <?= $supportsText ? '' : '<span style="color:#dc2626;">*</span>' ?>
                    </label>
                    <input type="text" name="caption" id="cmsCaption"
                           placeholder="<?= $activeSection === 'static_home' ? 'Apple Accessories 2026' : 'e.g. Call for deals' ?>">
                    <div class="cms-hint">
                        <?php
                        echo match ($activeSection) {
                            'ads_grid'    => 'Shown under the tile on the homepage.',
                            'static_home' => 'Printed directly beneath the main title, as a second heading line.',
                            default       => 'Used as the image description for accessibility.',
                        };
                        ?>
                    </div>
                </div>

                <div class="cms-field">
                    <label>Links to</label>
                    <input type="text" name="link_url" id="cmsLinkUrl" placeholder="/shop">
                    <div class="cms-hint">Defaults to the shop page. Use a path like <code>/shop?cat=7</code> or a full URL.</div>
                </div>

                <div class="cms-field">
                    <label>Display order</label>
                    <input type="number" name="sort_order" id="cmsSortOrder" value="0" min="0">
                    <div class="cms-hint">Lower numbers appear first.</div>
                </div>

                <div class="cms-field">
                    <label class="cms-checkline">
                        <input type="checkbox" name="is_active" id="cmsIsActive" value="1" checked>
                        Visible on the homepage
                    </label>
                </div>
            </div>

            <div class="cms-modal-foot">
                <button type="button" class="admin-btn admin-btn-outline admin-btn-sm" id="cmsCancel">Cancel</button>
                <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm" id="cmsSubmit">
                    <i class="fa fa-save"></i> Save Banner
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var API      = '<?= API_URL ?>';
    var BASE     = '<?= BASE_URL ?>';
    var SECTION  = '<?= $activeSection ?>';
    var overlay  = document.getElementById('cmsModal');
    var form     = document.getElementById('cmsForm');

    function open(banner) {
        form.reset();
        document.getElementById('cmsPreview').innerHTML = '';

        if (banner) {
            document.getElementById('cmsModalTitle').textContent = 'Edit Banner';
            document.getElementById('cmsId').value          = banner.id;
            document.getElementById('cmsCaption').value      = banner.caption      || '';
            document.getElementById('cmsSubheading').value   = banner.subheading   || '';
            document.getElementById('cmsHeading').value      = banner.heading      || '';
            document.getElementById('cmsPriceText').value    = banner.price_text   || '';
            document.getElementById('cmsButtonText').value   = banner.button_text  || '';
            document.getElementById('cmsLinkUrl').value      = banner.link_url     || '';
            document.getElementById('cmsSortOrder').value    = banner.sort_order   || 0;
            document.getElementById('cmsIsActive').checked   = banner.is_active == 1;
            document.getElementById('cmsPreview').innerHTML =
                '<img src="' + BASE + '/' + banner.image.replace(/^\//, '') + '" alt="">';
        } else {
            document.getElementById('cmsModalTitle').textContent = 'Add Banner';
            document.getElementById('cmsId').value = '';
            document.getElementById('cmsIsActive').checked = true;
            document.getElementById('cmsLinkUrl').value = '/shop';
        }

        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    document.getElementById('cmsAddBtn').addEventListener('click', function () { open(null); });
    document.getElementById('cmsModalClose').addEventListener('click', close);
    document.getElementById('cmsCancel').addEventListener('click', close);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) close();
    });

    // Live preview of a newly chosen file
    document.getElementById('cmsImage').addEventListener('change', function () {
        var f = this.files && this.files[0];
        if (!f) return;
        var reader = new FileReader();
        reader.onload = function (ev) {
            document.getElementById('cmsPreview').innerHTML = '<img src="' + ev.target.result + '" alt="">';
        };
        reader.readAsDataURL(f);
    });

    document.querySelectorAll('.cms-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            open(JSON.parse(this.getAttribute('data-banner')));
        });
    });

    // ── Save ──────────────────────────────────────────────────
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = document.getElementById('cmsSubmit');
        var original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';

        var fd = new FormData(form);

        fetch(API + '?action=cms_save', {
            method: 'POST',
            credentials: 'include',
            headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '' },
            body: fd
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            btn.disabled = false;
            btn.innerHTML = original;
            if (res.success) {
                showAlert('success', res.message);
                setTimeout(function () { location.reload(); }, 700);
            } else {
                showAlert('danger', res.message || 'Could not save the banner.');
            }
        })
        .catch(function () {
            btn.disabled = false;
            btn.innerHTML = original;
            showAlert('danger', 'Network error. Please try again.');
        });
    });

    // ── Show / hide ───────────────────────────────────────────
    document.querySelectorAll('.cms-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            ajaxPost(API + '?action=cms_toggle', { id: this.getAttribute('data-id') }, function (res) {
                if (res.success) {
                    showAlert('success', res.message);
                    setTimeout(function () { location.reload(); }, 600);
                } else {
                    showAlert('danger', res.message);
                }
            });
        });
    });

    // ── Delete ────────────────────────────────────────────────
    document.querySelectorAll('.cms-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Delete this banner? This cannot be undone.')) return;
            ajaxPost(API + '?action=cms_delete', { id: this.getAttribute('data-id') }, function (res) {
                if (res.success) {
                    showAlert('success', res.message);
                    setTimeout(function () { location.reload(); }, 600);
                } else {
                    showAlert('danger', res.message);
                }
            });
        });
    });
})();
</script>
