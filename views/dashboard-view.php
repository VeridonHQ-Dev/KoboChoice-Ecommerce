<?php
$customer = get_current_customer();
$firstName = explode(' ', $customer['name'])[0];
$profileImage = $customer['profile_image'] ?? null;
$activeTab = $_GET['tab'] ?? 'orders';
?>
<style>
/* ─── Premium Dashboard Theme ────────────────────────────────────────── */
:root {
    --dash-primary: #F48201;
    --dash-primary-hover: #d97101;
    --dash-secondary: #64B9CA;
    --dash-bg: #f8fafc;
    --dash-card-bg: #ffffff;
    --dash-border: #e2e8f0;
    --dash-text: #1e293b;
    --dash-text-muted: #64748b;
    --dash-success: #16a34a;
    --dash-danger: #dc2626;
}

/* Scoped reset. Without it, width:100% below would add the padding on top of
   the full width and overflow by exactly the padding. */
.dash-container,
.dash-container *,
.dash-container *::before,
.dash-container *::after {
    box-sizing: border-box;
}

.dash-container {
    /* width:100% is load-bearing, not decoration. The page wraps this in a
       column flexbox, and an auto left/right margin cancels flex stretch —
       leaving the container to size shrink-to-fit against its widest
       unbreakable child (the tab strip), which blew it well past the viewport
       on narrow phones. An explicit width takes that guess away. */
    width: 100%;
    max-width: 1140px;
    margin: 0 auto 50px;
    padding: 30px 15px 60px;
    font-family: 'Rubik', sans-serif;
    color: var(--dash-text);
}

/* Belt and braces: nothing inside may impose a minimum wider than the phone.
   The tab strip is a scroll container, so it scrolls instead of pushing. */
.dash-container > *,
.dash-grid > * {
    min-width: 0;
}

/* ─── Breadcrumb / Welcome Header ───────────────────────────────────── */
.dash-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    background: #fff;
    padding: 20px 24px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    border: 1px solid var(--dash-border);
}

.dash-header-title h1 {
    font-size: 20px;
    font-weight: 700;
    color: var(--dash-text);
    margin: 0 0 4px;
}

.dash-header-title p {
    font-size: 13.5px;
    color: var(--dash-text-muted);
    margin: 0;
}

.dash-header-meta {
    font-size: 13px;
    font-weight: 500;
    color: var(--dash-primary);
    background: #fff7ed;
    padding: 8px 14px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ─── Two-Column Layout ─────────────────────────────────────────────── */
.dash-grid {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 24px;
    align-items: start;
}

/* ─── Sidebar profile + navigation ─────────────────────────────────── */
.dash-sidebar {
    background: var(--dash-card-bg);
    border-radius: 12px;
    border: 1px solid var(--dash-border);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.dash-profile-card {
    text-align: center;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--dash-border);
}

.dash-avatar {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--dash-primary), #ffa040);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    font-weight: 700;
    margin: 0 auto 12px;
    box-shadow: 0 4px 12px rgba(244, 130, 1, 0.25);
    border: 3px solid #fff;
}

.dash-profile-card h3 {
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 4px;
    color: var(--dash-text);
}

.dash-profile-card p {
    font-size: 13px;
    color: var(--dash-text-muted);
    margin: 0;
    word-break: break-all;
}

.dash-menu {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.dash-tab {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: transparent;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #475569;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
}

.dash-tab i {
    font-size: 16px;
    color: #94a3b8;
    transition: color 0.2s ease;
    width: 20px;
    text-align: center;
}

.dash-tab:hover {
    background: #f8fafc;
    color: var(--dash-primary);
}

.dash-tab:hover i {
    color: var(--dash-primary);
}

.dash-tab.active {
    background: #fff7ed;
    color: var(--dash-primary);
    font-weight: 600;
}

.dash-tab.active i {
    color: var(--dash-primary);
}

/* ─── Main Content Panels ───────────────────────────────────────────── */
.dash-panel {
    display: none;
    background: var(--dash-card-bg);
    border-radius: 12px;
    border: 1px solid var(--dash-border);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    padding: 30px;
    animation: fadeIn 0.4s ease-out;
}

.dash-panel.active {
    display: block;
}

.dash-panel h2 {
    font-size: 18px;
    font-weight: 700;
    color: var(--dash-text);
    margin: 0 0 24px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f1f5f9;
}

/* ─── Form Controls & Inputs ────────────────────────────────────────── */
.dash-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.dash-row > .dash-field {
    flex: 1;
    min-width: 200px;
}

.dash-field {
    margin-bottom: 20px;
}

.dash-field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
}

.dash-field input, 
.dash-field select, 
.dash-field textarea {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 14px;
    color: var(--dash-text);
    background: #f8fafc;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.dash-field input:focus, 
.dash-field select:focus, 
.dash-field textarea:focus {
    outline: none;
    border-color: var(--dash-primary);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(244, 130, 1, 0.1);
}

.dash-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 28px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    background: linear-gradient(135deg, var(--dash-primary), #ffa040);
    color: #fff;
    box-shadow: 0 4px 12px rgba(244, 130, 1, 0.2);
}

.dash-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(244, 130, 1, 0.3);
}

.dash-btn:active {
    transform: translateY(0);
}

.dash-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* ─── Messages & Alerts ──────────────────────────────────────────────── */
.dash-msg {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 13.5px;
    display: none;
    font-weight: 500;
}

.dash-msg.success {
    display: block;
    background: #ecfdf5;
    color: var(--dash-success);
    border: 1px solid #a7f3d0;
}

.dash-msg.error {
    display: block;
    background: #fef2f2;
    color: var(--dash-danger);
    border: 1px solid #fecaca;
}

/* ─── Orders list ───────────────────────────────────────────────────────
   One markup, two shapes. On desktop the cards line up on a shared grid so
   they read as table rows under a header strip; on mobile that same grid
   collapses into stacked cards with their own labels. A real <table> can only
   ever scroll sideways on a phone, which is the thing being fixed here. */
.dash-table-wrapper {
    --order-cols: 84px 1fr 132px 118px 186px;
    border: 1px solid var(--dash-border);
    border-radius: 10px;
    overflow: hidden;
}

.order-list-head,
.order-card {
    display: grid;
    grid-template-columns: var(--order-cols);
    gap: 14px;
    align-items: center;
    padding: 13px 16px;
}

.order-list-head {
    background: #f8fafc;
    border-bottom: 2px solid var(--dash-border);
    font-size: 12px;
    font-weight: 600;
    color: var(--dash-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.order-card {
    border-bottom: 1px solid var(--dash-border);
    font-size: 14px;
    transition: background 0.2s ease;
}

.order-card:last-child { border-bottom: none; }
.order-card:hover { background: #f8fafc; }

.order-cell { min-width: 0; }
.order-cell strong { font-weight: 700; }
.order-total { font-weight: 600; }

/* Shown only once the grid collapses — on desktop the header strip names
   the columns instead. */
.order-label { display: none; }

/* ─── Status Badges ─────────────────────────────────────────────────── */
.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
}

.status-pending { background: #fef3c7; color: #d97706; }
.status-paid { background: #dbeafe; color: #2563eb; }
.status-processing { background: #f3e8ff; color: #9333ea; }
.status-shipped { background: #cffafe; color: #0891b2; }
.status-delivered { background: #dcfce7; color: #16a34a; }
.status-completed { background: #ccfbf1; color: #0f766e; }
.status-cancelled { background: #fee2e2; color: #dc2626; }

/* ─── Order Action Buttons ───────────────────────────────────────────── */
.order-cell-actions {
    display: flex;
    gap: 8px;
    white-space: nowrap;
}

.order-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: 1px solid;
    transition: all 0.2s ease;
}

.order-action-btn.view-btn {
    color: var(--dash-primary);
    border-color: #fde8d0;
    background: #fff7ed;
}

.order-action-btn.view-btn:hover {
    background: var(--dash-primary);
    color: #fff;
    border-color: var(--dash-primary);
}

.order-action-btn.cancel-btn {
    color: var(--dash-danger);
    border-color: #fecaca;
    background: #fef2f2;
}

.order-action-btn.cancel-btn:hover {
    background: var(--dash-danger);
    color: #fff;
    border-color: var(--dash-danger);
}

/* ─── Cancel Reason Dialog List ──────────────────────────────────────── */
.cancel-reason-label {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border: 1px solid var(--dash-border);
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    font-size: 13.5px;
    color: var(--dash-text);
    transition: all 0.2s ease;
}

.cancel-reason-label:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}

.cancel-reason-label input[type="radio"] {
    width: 18px;
    height: 18px;
    margin: 0;
    padding: 0;
    accent-color: var(--dash-primary);
    flex-shrink: 0;
    cursor: pointer;
}

.cancel-reason-label input[type="radio"]:checked + span,
.cancel-reason-label:has(input:checked) {
    border-color: var(--dash-primary);
    background: #fff7ed;
    font-weight: 500;
}

.cancel-reason-last {
    margin-bottom: 0;
}

/* ─── Modal styling ─────────────────────────────────────────────────── */
.dash-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.dash-modal-content {
    background: #fff;
    border-radius: 16px;
    max-width: 460px;
    width: 90%;
    padding: 30px;
    position: relative;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid var(--dash-border);
    animation: scaleUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.dash-modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: #f1f5f9;
    border: none;
    font-size: 18px;
    color: var(--dash-text-muted);
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.dash-modal-close:hover {
    background: #e2e8f0;
    color: var(--dash-text);
}

.dash-modal-content h3 {
    margin: 0 0 6px;
    font-size: 18px;
    font-weight: 700;
    color: var(--dash-text);
}

.dash-modal-content p {
    margin: 0 0 20px;
    font-size: 13.5px;
    color: var(--dash-text-muted);
    line-height: 1.4;
}

/* ─── Animations ────────────────────────────────────────────────────── */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes scaleUp {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

/* ─── Joined date, shown inside the profile card on mobile only ─────── */
.dash-profile-joined { display: none; }

/* ─── Profile picture row (Profile panel) ───────────────────────────── */
.dash-avatar-row {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 28px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid var(--dash-border);
}

.dash-avatar-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.dash-avatar-remove {
    padding: 8px 14px;
    font-size: 12.5px;
    border: 1px solid #fecaca;
    border-radius: 6px;
    background: #fef2f2;
    color: var(--dash-danger);
    cursor: pointer;
    font-weight: 600;
}

/* ─── Tablet ────────────────────────────────────────────────────────── */
@media (max-width: 991px) {
    .dash-grid {
        grid-template-columns: 1fr;
    }

    .dash-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}

/* ─── Orders switch to cards as soon as the sidebar stacks ─────────────
   Below 992px the content column is full width but no longer wide enough
   for five aligned columns — the date starts wrapping mid-value. Cards read
   better from here down, and it keeps this switch at the same breakpoint
   where the layout already changes shape. */
@media (max-width: 991px) {
    .dash-table-wrapper {
        border: none;
        border-radius: 0;
        overflow: visible;
        background: transparent;
    }

    .order-list-head { display: none; }

    .order-card {
        grid-template-columns: 1fr 1fr;
        gap: 14px 12px;
        padding: 16px;
        margin-bottom: 12px;
        background: #fff;
        border: 1px solid var(--dash-border);
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }

    .order-card:hover { background: #fff; }

    .order-cell {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .order-label {
        display: block;
        font-size: 10.5px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--dash-text-muted);
    }

    .order-cell-id strong { font-size: 16px; }
    .order-cell-total .order-total { font-size: 15px; color: var(--dash-primary); }
    .order-cell-status { align-items: flex-start; }

    .order-cell-actions {
        grid-column: 1 / -1;
        flex-direction: row;
        gap: 10px;
        padding-top: 12px;
        border-top: 1px solid #f1f5f9;
    }

    .order-cell-actions .order-action-btn {
        flex: 1;
        justify-content: center;
        min-height: 44px;
        font-size: 13.5px;
    }
}

/* ═══ Mobile: the dashboard becomes an app screen ══════════════════════
   Three ideas carry the whole thing:
     1. an account banner instead of a page title,
     2. the sidebar's menu turned into a sticky segmented control that stays
        reachable while a long form scrolls,
     3. every table, row and dialog restated as a stacked, thumb-sized card.
   ═══════════════════════════════════════════════════════════════════════ */
@media (max-width: 767px) {
    .dash-container {
        width: 100%;
        max-width: 100%;
        margin: 0;
        background: var(--dash-bg);
        overflow-x: clip;
        padding-left: 0;
        padding-right: 0;
    }

    /* style.css carries `header ~ :not(.footer) { padding-top: 8px !important;
       padding-bottom: 8px !important }` as a global gutter for inner pages.
       Being !important, no amount of specificity beats it — countering it in
       kind is the only way, and it is why these two are split out rather than
       folded into the shorthand above. The banner is meant to butt straight up
       against the header with no strip of page background between them. */
    .body-wrapper > .dash-container {
        padding-top: 0 !important;
        padding-bottom: 32px !important;
    }

    /* The generic "Customer Portal" strip says nothing the banner below
       doesn't say better, and costs a screenful on a phone. */
    .dash-header { display: none; }

    /* Block, not grid: a sticky element can only travel inside its own grid
       area, so the tab strip below would unstick the moment its row scrolled
       past. As a block the strip is free to follow the whole column. */
    .dash-grid {
        display: block;
        gap: 0;
    }

    /* The sidebar stops being a panel. display:contents dissolves its box so
       the banner, the tab strip and the panels become siblings in one column
       — which is also what gives the strip a tall enough parent to stick to. */
    .dash-sidebar {
        display: contents;
    }

    .dash-profile-card {
        display: flex;
        align-items: center;
        gap: 14px;
        text-align: left;
        padding: 18px 16px calc(18px + env(safe-area-inset-bottom, 0px) * 0);
        border-bottom: none;
        background: linear-gradient(135deg, #0D1B2A 0%, #1b3a5c 100%);
        color: #fff;
    }

    .dash-profile-card .dash-avatar {
        width: 56px;
        height: 56px;
        min-width: 56px;
        margin: 0;
        font-size: 22px;
        border-width: 2px;
    }

    .dash-profile-card h3 {
        color: #fff;
        font-size: 17px;
        margin: 0 0 2px;
    }

    .dash-profile-card p {
        color: rgba(255, 255, 255, 0.72);
        font-size: 12.5px;
        word-break: break-word;
    }

    .dash-profile-joined {
        display: block;
        margin-top: 4px;
        font-size: 11.5px;
        color: rgba(255, 255, 255, 0.6);
    }

    /* The section list lives behind the account icon in the header on mobile,
       so repeating it in the page body would be the same navigation twice. */
    .dash-menu { display: none; }

    /* Panels are the page on mobile — no card inside a card. */
    .dash-panel {
        border: none;
        border-radius: 0;
        box-shadow: none;
        padding: 18px 16px 24px;
        background: transparent;
    }

    .dash-panel h2 {
        font-size: 16px;
        margin-bottom: 18px;
        padding-bottom: 12px;
    }

    /* One field per line. Two half-width inputs on a 375px screen leave
       neither of them usable. */
    .dash-row {
        display: block;
    }

    .dash-row > .dash-field {
        min-width: 0;
    }

    .dash-field { margin-bottom: 16px; }

    /* 16px is the threshold below which iOS Safari zooms the page on focus
       and never zooms back out. Everything tappable clears 44px. */
    .dash-field input,
    .dash-field select,
    .dash-field textarea {
        font-size: 16px;
        padding: 13px 14px;
        min-height: 48px;
        background: #fff;
    }

    .dash-field textarea { min-height: 84px; }

    /* Primary actions span the width and sit at the thumb, app-style. */
    .dash-btn {
        width: 100%;
        padding: 15px 20px;
        font-size: 15px;
        min-height: 50px;
    }

    /* ── Profile picture row ── */
    .dash-avatar-row {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 14px;
        padding: 18px 16px;
    }

    .dash-avatar-actions { justify-content: center; }
    .dash-avatar-actions .dash-btn { width: auto; min-height: 44px; }

    /* ── Dialogs become bottom sheets ── */
    .dash-modal-overlay { align-items: flex-end; }

    .dash-modal-content {
        width: 100%;
        max-width: none;
        max-height: 88vh;
        overflow-y: auto;
        border-radius: 20px 20px 0 0;
        padding: 24px 18px calc(24px + env(safe-area-inset-bottom, 0px));
        animation: sheetUp 0.28s cubic-bezier(0.32, 0.72, 0, 1);
    }

    /* The grab handle that tells a phone user this panel came from the
       bottom edge and can be dismissed. */
    .dash-modal-content::before {
        content: '';
        display: block;
        width: 40px;
        height: 4px;
        margin: -8px auto 16px;
        border-radius: 2px;
        background: #cbd5e1;
    }

    .cancel-reason-label { min-height: 48px; }

    .dash-modal-content form > div:last-child {
        flex-direction: column-reverse;
        gap: 10px;
    }

    .dash-modal-content form > div:last-child button {
        width: 100%;
        min-height: 48px;
    }
}

@keyframes sheetUp {
    from { opacity: 0; transform: translateY(100%); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Very small phones — trim the horizontal padding rather than the type. The
   order card keeps its two columns: they still fit at 320px, and collapsing
   them only makes each card taller for no gain in legibility. */
@media (max-width: 360px) {
    .dash-panel { padding-left: 12px; padding-right: 12px; }
    .dash-profile-card { padding-left: 12px; padding-right: 12px; }
    .order-card { padding: 14px; gap: 12px 10px; }
}

/* Respect a reduced-motion preference rather than animating regardless. */
@media (prefers-reduced-motion: reduce) {
    .dash-panel,
    .dash-modal-content {
        animation: none;
    }
    .dash-btn:hover { transform: none; }
}
</style>

<div class="dash-container">
    
    <!-- ─── Dashboard Header ─── -->
    <div class="dash-header">
        <div class="dash-header-title">
            <h1>Customer Portal</h1>
            <p>Manage your transactions, profiles, and delivery configurations.</p>
        </div>
        <div class="dash-header-meta">
            <i class="fa fa-calendar"></i>
            <span>Joined: <?= date('M Y', strtotime($customer['created_at'] ?? 'now')) ?></span>
        </div>
    </div>

    <div class="dash-grid">
        
        <!-- ─── Sidebar Column ─── -->
        <div class="dash-sidebar">
            <div class="dash-profile-card">
                <div class="dash-avatar" id="dash-avatar">
                    <?php if ($profileImage): ?>
                        <img src="<?= BASE_URL ?>/public/uploads/customers/<?= sanitize($profileImage) ?>" alt="Profile" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        <span><?= strtoupper(substr($firstName, 0, 1)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="dash-profile-text">
                    <h3>Welcome, <?= sanitize($firstName) ?>!</h3>
                    <p><?= sanitize($customer['email'] ?? '') ?></p>
                    <?php /* Duplicated from the header strip, which is hidden on mobile. */ ?>
                    <p class="dash-profile-joined">
                        <i class="fa fa-calendar"></i>
                        Joined <?= date('M Y', strtotime($customer['created_at'] ?? 'now')) ?>
                    </p>
                </div>
            </div>
            
            <?php /* Same source as the header's account dropdown, so the two lists
                     can't drift apart. Hidden on mobile — the header icon carries
                     this navigation there. */ ?>
            <div class="dash-menu">
                <?php foreach (customer_dashboard_tabs() as $tabKey => $tab): ?>
                    <button class="dash-tab <?= $activeTab === $tabKey ? 'active' : '' ?>" data-tab="<?= $tabKey ?>">
                        <i class="fa <?= $tab['icon'] ?>"></i> <?= sanitize($tab['label']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ─── Main Content Column ─── -->
        <div class="dash-main-content">
            
            <!-- ─── ORDERS PANEL ─── -->
            <div class="dash-panel <?= $activeTab === 'orders' ? 'active' : '' ?>" id="panel-orders">
                <h2><i class="fa fa-shopping-bag" style="color:var(--dash-primary);"></i> Order History</h2>
                <div id="orders-loading" style="text-align:center;padding:40px;color:var(--dash-text-muted);font-weight:500;">
                    <i class="fa fa-spinner fa-spin" style="font-size:24px;margin-bottom:10px;display:block;color:var(--dash-primary);"></i> Loading your order history...
                </div>
                <div id="orders-empty" style="display:none;text-align:center;padding:50px 20px;">
                    <i class="fa fa-shopping-basket" style="font-size:48px;color:#cbd5e1;margin-bottom:16px;display:block;"></i>
                    <p style="color:var(--dash-text-muted);font-size:15px;margin:0 0 20px;">You haven't placed any orders yet.</p>
                    <a href="<?= BASE_URL ?>/shop" class="dash-btn"><i class="fa fa-shopping-cart"></i> Start Shopping</a>
                </div>
                <div id="orders-list" style="display:none;" class="dash-table-wrapper"></div>
            </div>

            <!-- ─── PROFILE PANEL ─── -->
            <div class="dash-panel <?= $activeTab === 'profile' ? 'active' : '' ?>" id="panel-profile">
                <h2><i class="fa fa-user" style="color:var(--dash-primary);"></i> Profile Information</h2>
                <div class="dash-msg" id="profile-msg"></div>

                <!-- Profile Image Upload -->
                <div class="dash-avatar-row">
                    <div class="dash-avatar" id="profile-avatar-preview" style="width:80px;height:80px;min-width:80px;font-size:30px;">
                        <?php if ($profileImage): ?>
                            <img src="<?= BASE_URL ?>/public/uploads/customers/<?= sanitize($profileImage) ?>" alt="Profile" style="width:100%;height:100%;border-radius:50%;object-fit:cover;" id="profile-preview-img">
                        <?php else: ?>
                            <span id="profile-preview-letter"><?= strtoupper(substr($firstName, 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p style="margin:0 0 4px;font-size:14px;font-weight:600;color:var(--dash-text);">Profile Picture</p>
                        <p style="margin:0 0 10px;font-size:12.5px;color:var(--dash-text-muted);">Upload a profile photo. JPG, PNG or WebP, max 5MB.</p>
                        <div class="dash-avatar-actions">
                            <label for="profileImageInput" class="dash-btn" style="padding:8px 18px;font-size:12.5px;cursor:pointer;margin:0;">
                                <i class="fa fa-camera"></i> Choose Image
                            </label>
                            <input type="file" id="profileImageInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
                            <?php if ($profileImage): ?>
                                <button type="button" id="removeProfileImage" class="dash-avatar-remove">
                                    <i class="fa fa-trash"></i> Remove
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <form id="profile-form">
                    <div class="dash-row">
                        <div class="dash-field">
                            <label>Full Name</label>
                            <input type="text" name="name" value="<?= sanitize($customer['name'] ?? '') ?>" required>
                        </div>
                        <div class="dash-field">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?= sanitize($customer['email'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="dash-row">
                        <div class="dash-field">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?= sanitize($customer['phone'] ?? '') ?>">
                        </div>
                        <div class="dash-field">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="">Select Gender</option>
                                <option value="male" <?= ($customer['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($customer['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="other" <?= ($customer['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="dash-field">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" value="<?= sanitize($customer['date_of_birth'] ?? '') ?>">
                    </div>
                    <button type="submit" class="dash-btn" id="profile-btn"><i class="fa fa-save"></i> Save Profile Details</button>
                </form>
            </div>

            <!-- ─── PASSWORD PANEL ─── -->
            <div class="dash-panel <?= $activeTab === 'password' ? 'active' : '' ?>" id="panel-password">
                <h2><i class="fa fa-lock" style="color:var(--dash-primary);"></i> Security Credentials</h2>
                <div class="dash-msg" id="password-msg"></div>
                <form id="password-form">
                    <div class="dash-field">
                        <label>Current Password</label>
                        <input type="password" name="current_password" placeholder="Verify current password" required>
                    </div>
                    <div class="dash-row">
                        <div class="dash-field">
                            <label>New Password</label>
                            <input type="password" name="new_password" placeholder="Minimum 8 characters" required>
                        </div>
                        <div class="dash-field">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" placeholder="Repeat new password" required>
                        </div>
                    </div>
                    <button type="submit" class="dash-btn" id="password-btn"><i class="fa fa-key"></i> Update Password</button>
                </form>
            </div>

            <!-- ─── ADDRESS PANEL ─── -->
            <div class="dash-panel <?= $activeTab === 'address' ? 'active' : '' ?>" id="panel-address">
                <h2><i class="fa fa-map-marker" style="color:var(--dash-primary);"></i> Shipping & Billing Addresses</h2>
                <div class="dash-msg" id="address-msg"></div>
                <form id="address-form">
                    <h3 style="font-size:15px;font-weight:600;color:var(--dash-text);margin:0 0 14px;display:flex;align-items:center;gap:6px;"><i class="fa fa-truck" style="color:var(--dash-secondary);"></i> Shipping Destination</h3>
                    <div class="dash-row">
                        <div class="dash-field">
                            <label>Recipient Name</label>
                            <input type="text" name="shipping_name" value="<?= sanitize($customer['shipping_name'] ?? $customer['name'] ?? '') ?>">
                        </div>
                        <div class="dash-field">
                            <label>Contact Phone</label>
                            <input type="text" name="shipping_phone" value="<?= sanitize($customer['shipping_phone'] ?? $customer['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="dash-field">
                        <label>Street Address</label>
                        <textarea name="shipping_address" rows="2"><?= sanitize($customer['shipping_address'] ?? $customer['address'] ?? '') ?></textarea>
                    </div>
                    <div class="dash-row">
                        <div class="dash-field">
                            <label>City</label>
                            <input type="text" name="shipping_city" value="<?= sanitize($customer['shipping_city'] ?? '') ?>">
                        </div>
                        <div class="dash-field">
                            <label>State</label>
                            <input type="text" name="shipping_state" value="<?= sanitize($customer['shipping_state'] ?? '') ?>">
                        </div>
                        <div class="dash-field">
                            <label>Country</label>
                            <input type="text" name="shipping_country" value="<?= sanitize($customer['shipping_country'] ?? 'Nigeria') ?>">
                        </div>
                    </div>

                    <hr style="margin:30px 0;border:none;border-top:1px solid var(--dash-border);">

                    <h3 style="font-size:15px;font-weight:600;color:var(--dash-text);margin:0 0 14px;display:flex;align-items:center;gap:6px;"><i class="fa fa-file-text-o" style="color:var(--dash-secondary);"></i> Billing Information</h3>
                    <div style="margin-bottom:18px;">
                        <label style="font-size:13.5px;color:var(--dash-text-muted);cursor:pointer;display:inline-flex;align-items:center;gap:6px;user-select:none;">
                            <input type="checkbox" id="sameAsShipping" style="width:16px;height:16px;accent-color:var(--dash-primary);"> Copy shipping details
                        </label>
                    </div>
                    <div id="billing-fields">
                        <div class="dash-row">
                            <div class="dash-field">
                                <label>Billing Name</label>
                                <input type="text" name="billing_name" value="<?= sanitize($customer['billing_name'] ?? '') ?>">
                            </div>
                            <div class="dash-field">
                                <label>Contact Phone</label>
                                <input type="text" name="billing_phone" value="<?= sanitize($customer['billing_phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="dash-field">
                            <label>Street Address</label>
                            <textarea name="billing_address" rows="2"><?= sanitize($customer['billing_address'] ?? '') ?></textarea>
                        </div>
                        <div class="dash-row">
                            <div class="dash-field">
                                <label>City</label>
                                <input type="text" name="billing_city" value="<?= sanitize($customer['billing_city'] ?? '') ?>">
                            </div>
                            <div class="dash-field">
                                <label>State</label>
                                <input type="text" name="billing_state" value="<?= sanitize($customer['billing_state'] ?? '') ?>">
                            </div>
                            <div class="dash-field">
                                <label>Country</label>
                                <input type="text" name="billing_country" value="<?= sanitize($customer['billing_country'] ?? 'Nigeria') ?>">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="dash-btn" id="address-btn" style="margin-top:14px;"><i class="fa fa-save"></i> Save Address Settings</button>
                </form>
            </div>

        </div>
    </div>
</div>

<!-- Cancel Order Modal Overlay -->
<div id="cancelModal" class="dash-modal-overlay">
    <div class="dash-modal-content">
        <button onclick="closeCancelModal()" class="dash-modal-close">&times;</button>
        <h3>Cancel Order <span id="cancelOrderNum"></span></h3>
        <p>Please share the reason for requesting order cancellation below.</p>
        <form id="cancelForm">
            <input type="hidden" name="id" id="cancelOrderId">
            <div style="margin-bottom:18px;">
                <label class="cancel-reason-label">
                    <input type="radio" name="cancel_reason" value="Changed my mind">
                    <span>Changed my mind</span>
                </label>
                <label class="cancel-reason-label">
                    <input type="radio" name="cancel_reason" value="Found a better price elsewhere">
                    <span>Found a better price elsewhere</span>
                </label>
                <label class="cancel-reason-label">
                    <input type="radio" name="cancel_reason" value="Ordered by mistake">
                    <span>Ordered by mistake</span>
                </label>
                <label class="cancel-reason-label">
                    <input type="radio" name="cancel_reason" value="Delivery too slow">
                    <span>Delivery too slow</span>
                </label>
                <label class="cancel-reason-label">
                    <input type="radio" name="cancel_reason" value="No longer needed">
                    <span>No longer needed</span>
                </label>
                <label class="cancel-reason-label cancel-reason-last">
                    <input type="radio" name="cancel_reason" value="other">
                    <span>Other (Write reason below)</span>
                </label>
            </div>
            <div id="cancelOtherWrap" style="display:none;margin-bottom:20px;">
                <textarea id="cancelOtherText" placeholder="Please type cancellation reason..." rows="3" style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;resize:vertical;box-sizing:border-box;background:#f8fafc;"></textarea>
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" onclick="closeCancelModal()" style="padding:11px 22px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;font-size:14px;cursor:pointer;color:#475569;font-weight:600;">Keep Order</button>
                <button type="submit" id="cancelSubmitBtn" style="padding:11px 22px;border:none;border-radius:8px;background:var(--dash-danger);color:#fff;font-size:14px;font-weight:600;cursor:pointer;box-shadow: 0 4px 12px rgba(220,38,38,0.2);">Cancel Order</button>
            </div>
        </form>
    </div>
</div>

<script>
window.addEventListener('load', function() {
    if (typeof jQuery === 'undefined') { return; }
    (function($) {
    var API = '<?= API_URL ?>';

    // ── Tab switching ──────────────────────────────────────────────
    $(document).on('click', '.dash-tab', function() {
        var tab = $(this).data('tab');
        $('.dash-tab').removeClass('active');
        $(this).addClass('active');
        $('.dash-panel').removeClass('active');
        $('#panel-' + tab).addClass('active');

        /* On mobile the menu is a horizontal scroller, so a tab that was
           half off-screen when tapped gets pulled fully into view — the
           behaviour a native segmented control has. */
        if (this.scrollIntoView) {
            this.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }

        // Update URL without reload
        var url = new URL(window.location);
        url.searchParams.set('tab', tab);
        history.replaceState(null, '', url);
    });

    // ── Helper: show message ──────────────────────────────────────
    function showDashMsg(el, type, msg) {
        el.removeClass('success error').addClass(type).text(msg).show();
        if (type === 'success') setTimeout(function() { el.fadeOut(); }, 3000);
    }

    // ── Load Orders ───────────────────────────────────────────────
    function loadOrders() {
        $.getJSON(API + '?action=customer_orders', function(res) {
            $('#orders-loading').hide();
            if (res.success && res.orders.length > 0) {
                /* Each order is a grid card. The same cells line up as a table
                   row on desktop and stack into a card on a phone — see the
                   .order-card rules — so there is one markup to maintain and
                   nothing scrolls sideways. */
                var html = '<div class="order-list-head">' +
                    '<span>Order</span><span>Date</span><span>Total</span><span>Status</span><span>Actions</span>' +
                    '</div>';
                $.each(res.orders, function(i, o) {
                    var statusClass = 'status-' + o.status;
                    var canCancel = o.status !== 'cancelled' && o.status !== 'delivered' && o.status !== 'completed';
                    var orderDate = new Date(o.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                    var total = parseFloat(o.total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    html += '<div class="order-card">' +
                        '<div class="order-cell order-cell-id"><span class="order-label">Order</span><strong>#' + o.id + '</strong></div>' +
                        '<div class="order-cell order-cell-date"><span class="order-label">Date</span><span>' + orderDate + '</span></div>' +
                        '<div class="order-cell order-cell-total"><span class="order-label">Total</span><span class="order-total"><?= CURRENCY_SYMBOL ?>' + total + '</span></div>' +
                        '<div class="order-cell order-cell-status"><span class="order-label">Status</span>' +
                            '<span class="status-badge ' + statusClass + '">' + o.status.charAt(0).toUpperCase() + o.status.slice(1) + '</span></div>' +
                        '<div class="order-cell order-cell-actions">' +
                            '<a href="<?= BASE_URL ?>/order-detail?id=' + o.id + '" class="order-action-btn view-btn"><i class="fa fa-eye"></i> View</a>' +
                            (canCancel ? '<button type="button" class="order-action-btn cancel-btn" data-id="' + o.id + '" onclick="openCancelModal(' + o.id + ')"><i class="fa fa-times-circle"></i> Cancel</button>' : '') +
                        '</div>' +
                        '</div>';
                });
                $('#orders-list').html(html).show();
            } else {
                $('#orders-empty').show();
            }
        }).fail(function() {
            $('#orders-loading').text('Failed to load orders.');
        });
    }
    loadOrders();

    // ── Profile form ──────────────────────────────────────────────
    $('#profile-form').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#profile-btn');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        $.post(API + '?action=customer_update_profile', $(this).serialize(), function(res) {
            if (res.success) showDashMsg($('#profile-msg'), 'success', res.message);
            else showDashMsg($('#profile-msg'), 'error', res.message);
            $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save Profile Details');
        }, 'json').fail(function() {
            showDashMsg($('#profile-msg'), 'error', 'An error occurred. Please try again.');
            $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save Profile Details');
        });
    });

    // ── Password form ─────────────────────────────────────────────
    $('#password-form').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#password-btn');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Updating...');
        $.post(API + '?action=customer_change_password', $(this).serialize(), function(res) {
            if (res.success) {
                showDashMsg($('#password-msg'), 'success', res.message);
                $('#password-form')[0].reset();
            } else {
                showDashMsg($('#password-msg'), 'error', res.message);
            }
            $btn.prop('disabled', false).html('<i class="fa fa-key"></i> Update Password');
        }, 'json').fail(function() {
            showDashMsg($('#password-msg'), 'error', 'An error occurred. Please try again.');
            $btn.prop('disabled', false).html('<i class="fa fa-key"></i> Update Password');
        });
    });

    // ── Same as shipping toggle ───────────────────────────────────
    $('#sameAsShipping').on('change', function() {
        if ($(this).is(':checked')) {
            var form = $('#address-form');
            form.find('[name="billing_name"]').val(form.find('[name="shipping_name"]').val());
            form.find('[name="billing_phone"]').val(form.find('[name="shipping_phone"]').val());
            form.find('[name="billing_address"]').val(form.find('[name="shipping_address"]').val());
            form.find('[name="billing_city"]').val(form.find('[name="shipping_city"]').val());
            form.find('[name="billing_state"]').val(form.find('[name="shipping_state"]').val());
            form.find('[name="billing_country"]').val(form.find('[name="shipping_country"]').val());
        }
    });

    // ── Address form ──────────────────────────────────────────────
    $('#address-form').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#address-btn');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        $.post(API + '?action=customer_update_address', $(this).serialize(), function(res) {
            if (res.success) showDashMsg($('#profile-msg'), 'success', res.message);
            else showDashMsg($('#profile-msg'), 'error', res.message);
            $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save Address Settings');
        }, 'json').fail(function() {
            showDashMsg($('#profile-msg'), 'error', 'An error occurred. Please try again.');
            $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save Address Settings');
        });
    });

    // ── Profile Image Upload ────────────────────────────────────
    $('#profileImageInput').on('change', function() {
        var file = this.files[0];
        if (!file) return;

        var fd = new FormData();
        fd.append('profile_image', file);
        fd.append('csrf_token', typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '');

        var $preview = $('#profile-avatar-preview');
        $preview.css('opacity', '0.5');

        fetch(API + '?action=customer_upload_image', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                $preview.css('opacity', '1');
                if (res.success) {
                    showDashMsg($('#profile-msg'), 'success', res.message);
                    var url = res.image_url + '?t=' + Date.now();
                    // Update the main sidebar avatar
                    var $sidebarAvatar = $('.dash-sidebar .dash-avatar');
                    $sidebarAvatar.html('<img src="' + url + '" alt="Profile" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">');
                    // Update the profile panel preview
                    if ($('#profile-preview-img').length) {
                        $('#profile-preview-img').attr('src', url);
                    } else {
                        $preview.html('<img src="' + url + '" alt="Profile" style="width:100%;height:100%;border-radius:50%;object-fit:cover;" id="profile-preview-img">');
                    }
                    // Add remove button if not present
                    if (!$('#removeProfileImage').length) {
                        $('#profileImageInput').siblings('.dash-btn').after(
                            '<button type="button" id="removeProfileImage" style="padding:8px 14px;font-size:12.5px;border:1px solid #fecaca;border-radius:6px;background:#fef2f2;color:var(--dash-danger);cursor:pointer;font-weight:600;"><i class="fa fa-trash"></i> Remove</button>'
                        );
                    }
                } else {
                    showDashMsg($('#profile-msg'), 'error', res.message);
                }
                $('#profileImageInput').val('');
            })
            .catch(function() {
                $preview.css('opacity', '1');
                showDashMsg($('#profile-msg'), 'error', 'Failed to upload image.');
                $('#profileImageInput').val('');
            });
    });

    // ── Profile Image Remove ────────────────────────────────────
    $(document).on('click', '#removeProfileImage', function() {
        if (!confirm('Remove your profile picture?')) return;
        var fd = new FormData();
        fd.append('csrf_token', typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '');

        fetch(API + '?action=customer_remove_image', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    showDashMsg($('#profile-msg'), 'success', res.message);
                    var letter = '<?= strtoupper(substr($firstName, 0, 1)) ?>';
                    // Reset sidebar avatar
                    $('.dash-sidebar .dash-avatar').html('<span>' + letter + '</span>');
                    // Reset profile preview
                    $('#profile-avatar-preview').html('<span id="profile-preview-letter">' + letter + '</span>');
                    // Remove the remove button
                    $('#removeProfileImage').remove();
                } else {
                    showDashMsg($('#profile-msg'), 'error', res.message);
                }
            })
            .catch(function() {
                showDashMsg($('#profile-msg'), 'error', 'Failed to remove image.');
            });
    });
    })(jQuery);
});
</script>
<script>
// ── Cancel Order Modal ──────────────────────────────────────────
function openCancelModal(orderId) {
    document.getElementById('cancelOrderId').value = orderId;
    document.getElementById('cancelOrderNum').textContent = '#' + orderId;
    document.getElementById('cancelForm').reset();
    document.getElementById('cancelOtherWrap').style.display = 'none';
    var modal = document.getElementById('cancelModal');
    modal.style.display = 'flex';
}
function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="cancel_reason"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.getElementById('cancelOtherWrap').style.display = this.value === 'other' ? 'block' : 'none';
        });
    });
    document.getElementById('cancelModal').addEventListener('click', function(e) {
        if (e.target === this) closeCancelModal();
    });
    document.getElementById('cancelForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var orderId = document.getElementById('cancelOrderId').value;
        var reason = document.querySelector('input[name="cancel_reason"]:checked');
        if (!reason) { alert('Please select a reason for cancellation.'); return; }
        var cancelReason = reason.value === 'other' ? document.getElementById('cancelOtherText').value.trim() : reason.value;
        if (reason.value === 'other' && !cancelReason) { alert('Please provide your reason.'); return; }
        if (!confirm('Are you sure you want to cancel order #' + orderId + '?')) return;
        var btn = document.getElementById('cancelSubmitBtn');
        btn.disabled = true; btn.textContent = 'Cancelling...';
        var fd = new FormData();
        fd.append('id', orderId);
        fd.append('cancel_reason', cancelReason);
        fd.append('csrf_token', typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '');
        fetch(API_URL + '?action=customer_cancel_order', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                btn.disabled = false; btn.textContent = 'Cancel Order';
                if (res.success) {
                    closeCancelModal();
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || 'Failed to cancel order.');
                }
            })
            .catch(function() { btn.disabled = false; btn.textContent = 'Cancel Order'; alert('An error occurred.'); });
    });
});
</script>
