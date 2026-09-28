<?php
global $db;

$statusFilter = $_GET['status'] ?? '';
$where  = '1';
$params = [];

if (in_array($statusFilter, ['open', 'ongoing', 'resolved'], true)) {
    $where .= ' AND status = ?';
    $params[] = $statusFilter;
}

$messages = $db->fetchAll(
    "SELECT * FROM messages WHERE $where ORDER BY FIELD(status,'open','ongoing','resolved'), created_at DESC LIMIT 200",
    $params
);

$counts = [];
foreach ($db->fetchAll("SELECT status, COUNT(*) AS total FROM messages GROUP BY status") as $row) {
    $counts[$row['status']] = (int) $row['total'];
}
$totalAll = array_sum($counts);
?>

<style>
.msg-status-select {
    padding: 5px 9px; border: 1px solid #e2e8f0; border-radius: 6px;
    font-size: 12px; font-weight: 500; cursor: pointer; background: #fff;
}
.msg-status-select[data-status="open"]     { border-color: #fbbf24; background: #fffbeb; color: #92400e; }
.msg-status-select[data-status="ongoing"]  { border-color: #60a5fa; background: #eff6ff; color: #1d4ed8; }
.msg-status-select[data-status="resolved"] { border-color: #4ade80; background: #f0fdf4; color: #15803d; }
.msg-filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
.msg-filter-tabs a {
    padding: 6px 14px; border: 1px solid #e2e8f0; border-radius: 6px;
    font-size: 13px; color: #475569; text-decoration: none; background: #fff; transition: all .15s;
}
.msg-filter-tabs a:hover { border-color: #cbd5e1; }
.msg-filter-tabs a.is-active { background: #1f2430; border-color: #1f2430; color: #fff; }
/* Give the message room to breathe and stop the narrow columns wrapping. */
.admin-table th:nth-child(4), .admin-table td.msg-body-cell { min-width: 260px; }
.admin-table td.msg-date, .admin-table th:nth-child(5) { white-space: nowrap; }
.admin-table th:nth-child(1), .admin-table td.msg-from { white-space: nowrap; }
.msg-body-cell { max-width: 380px; }
.msg-toggle { background: none; border: none; padding: 0; color: #F48201; font-size: 12px; cursor: pointer; }
.msg-toggle:hover { text-decoration: underline; }
.msg-reply-link { color: #F48201; text-decoration: none; }
.msg-reply-link:hover { text-decoration: underline; }

/* ── Full-message modal ── */
.msg-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 99999;
    background: rgba(15, 23, 42, 0.55);
    align-items: center; justify-content: center; padding: 20px;
}
.msg-modal-overlay.is-open { display: flex; }
.msg-modal {
    background: #fff; border-radius: 10px; width: 100%; max-width: 620px;
    max-height: 86vh; display: flex; flex-direction: column;
    box-shadow: 0 24px 60px rgba(0,0,0,0.28);
    animation: msgModalIn 0.2s ease-out;
}
@keyframes msgModalIn { from { opacity: 0; transform: translateY(-12px) scale(.98); } to { opacity: 1; transform: none; } }
.msg-modal-head {
    display: flex; align-items: flex-start; gap: 14px;
    padding: 20px 22px; border-bottom: 1px solid #e2e8f0;
}
.msg-modal-head h3 { margin: 0 0 4px; font-size: 17px; font-weight: 600; color: #1e293b; line-height: 1.4; }
.msg-modal-head .msg-modal-sub { font-size: 13px; color: #64748b; line-height: 1.6; }
.msg-modal-head .msg-modal-sub a { color: #F48201; text-decoration: none; }
.msg-modal-head .msg-modal-sub a:hover { text-decoration: underline; }
.msg-modal-close {
    margin-left: auto; flex-shrink: 0; background: none; border: none;
    width: 32px; height: 32px; border-radius: 6px; cursor: pointer;
    font-size: 22px; line-height: 1; color: #94a3b8; transition: background .15s, color .15s;
}
.msg-modal-close:hover { background: #f1f5f9; color: #1e293b; }
.msg-modal-body {
    padding: 22px; overflow-y: auto; white-space: pre-wrap; word-break: break-word;
    font-size: 14.5px; line-height: 1.75; color: #334155;
}
.msg-modal-foot {
    padding: 15px 22px; border-top: 1px solid #e2e8f0; background: #f8fafc;
    display: flex; justify-content: flex-end; gap: 10px; border-radius: 0 0 10px 10px;
}
.msg-modal-badge {
    display: inline-block; padding: 3px 9px; border-radius: 20px;
    font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px;
}
.msg-modal-badge[data-status="open"]     { background: #fffbeb; color: #92400e; }
.msg-modal-badge[data-status="ongoing"]  { background: #eff6ff; color: #1d4ed8; }
.msg-modal-badge[data-status="resolved"] { background: #f0fdf4; color: #15803d; }
</style>

<div class="admin-toolbar" style="align-items:center;">
    <h3 style="margin:0;">Contact Messages</h3>
    <div class="msg-filter-tabs">
        <a href="<?= admin_url('/?view=messages') ?>" class="<?= $statusFilter === '' ? 'is-active' : '' ?>">
            All (<?= $totalAll ?>)
        </a>
        <?php foreach (['open' => 'Open', 'ongoing' => 'Ongoing', 'resolved' => 'Resolved'] as $key => $label): ?>
        <a href="<?= admin_url('/?view=messages&status=' . $key) ?>" class="<?= $statusFilter === $key ? 'is-active' : '' ?>">
            <?= $label ?> (<?= $counts[$key] ?? 0 ?>)
        </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>From</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Date</th>
                <th>Action</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($messages)): ?>
                <tr><td colspan="7" class="admin-empty"><i class="fa fa-envelope"></i> No messages</td></tr>
            <?php else: ?>
                <?php foreach ($messages as $m): ?>
                <tr style="<?= !$m['is_read'] ? 'background:#f0f9ff;' : '' ?>">
                    <td class="msg-from"><strong><?= sanitize($m['name']) ?></strong></td>
                    <td>
                        <a class="msg-reply-link"
                           href="mailto:<?= sanitize($m['email']) ?>?subject=<?= rawurlencode('Re: ' . ($m['subject'] ?? 'Your message')) ?>">
                            <?= sanitize($m['email']) ?>
                        </a>
                    </td>
                    <td><?= sanitize($m['subject'] ?? 'No subject') ?></td>
                    <td class="msg-body-cell">
                        <?= sanitize(truncate($m['message'], 60)) ?>
                        <?php if (mb_strlen($m['message']) > 60): ?>
                            <br><button type="button" class="msg-toggle" data-id="<?= $m['id'] ?>">Read full message</button>
                        <?php endif; ?>
                        <?php /* Payload for the modal. Hidden rather than inlined into
                                 data-attributes so multi-line message text survives intact. */ ?>
                        <div class="msg-data" id="msg-data-<?= $m['id'] ?>" hidden
                             data-name="<?= sanitize($m['name']) ?>"
                             data-email="<?= sanitize($m['email']) ?>"
                             data-subject="<?= sanitize($m['subject'] ?? 'No subject') ?>"
                             data-date="<?= sanitize(format_date($m['created_at'], 'M d, Y g:i A')) ?>"
                             data-status="<?= sanitize($m['status']) ?>"><?= sanitize($m['message']) ?></div>
                    </td>
                    <td class="msg-date"><?= time_ago($m['created_at']) ?></td>
                    <td>
                        <select class="msg-status-select" data-id="<?= $m['id'] ?>" data-status="<?= sanitize($m['status']) ?>">
                            <?php foreach (['open' => 'Open', 'ongoing' => 'Ongoing', 'resolved' => 'Resolved'] as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $m['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="actions">
                        <button class="admin-btn admin-btn-danger admin-btn-sm btn-delete"
                                data-url="<?= API_URL ?>?action=message_delete"
                                data-id="<?= $m['id'] ?>" data-type="Message" title="Delete message">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ── Full-message modal ────────────────────────────────────────── -->
<div class="msg-modal-overlay" id="msgModal" role="dialog" aria-modal="true" aria-labelledby="msgModalSubject">
    <div class="msg-modal">
        <div class="msg-modal-head">
            <div>
                <h3 id="msgModalSubject"></h3>
                <div class="msg-modal-sub">
                    <span id="msgModalFrom"></span> &middot;
                    <a id="msgModalEmail" href="#"></a><br>
                    <span id="msgModalDate"></span>
                    &nbsp;<span class="msg-modal-badge" id="msgModalStatus"></span>
                </div>
            </div>
            <button type="button" class="msg-modal-close" id="msgModalClose" aria-label="Close">&times;</button>
        </div>
        <div class="msg-modal-body" id="msgModalBody"></div>
        <div class="msg-modal-foot">
            <a class="admin-btn admin-btn-primary admin-btn-sm" id="msgModalReply" href="#">
                <i class="fa fa-reply"></i> Reply by Email
            </a>
            <button type="button" class="admin-btn admin-btn-outline admin-btn-sm" id="msgModalCloseBtn">Close</button>
        </div>
    </div>
</div>

<script>
// ── Full-message modal ─────────────────────────────────────────
(function () {
    var overlay = document.getElementById('msgModal');
    if (!overlay) return;

    var lastFocused = null;

    function openModal(id) {
        var data = document.getElementById('msg-data-' + id);
        if (!data) return;

        var subject = data.getAttribute('data-subject');
        var email   = data.getAttribute('data-email');
        var status  = data.getAttribute('data-status');

        document.getElementById('msgModalSubject').textContent = subject;
        document.getElementById('msgModalFrom').textContent    = data.getAttribute('data-name');
        document.getElementById('msgModalDate').textContent    = data.getAttribute('data-date');
        document.getElementById('msgModalBody').textContent    = data.textContent;

        var emailLink = document.getElementById('msgModalEmail');
        emailLink.textContent = email;
        emailLink.href = 'mailto:' + email;

        var badge = document.getElementById('msgModalStatus');
        badge.textContent = status;
        badge.setAttribute('data-status', status);

        document.getElementById('msgModalReply').href =
            'mailto:' + email + '?subject=' + encodeURIComponent('Re: ' + subject);

        lastFocused = document.activeElement;
        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        document.getElementById('msgModalClose').focus();
    }

    function closeModal() {
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
        if (lastFocused) lastFocused.focus();
    }

    document.querySelectorAll('.msg-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () { openModal(this.getAttribute('data-id')); });
    });

    document.getElementById('msgModalClose').addEventListener('click', closeModal);
    document.getElementById('msgModalCloseBtn').addEventListener('click', closeModal);

    // Click the backdrop (but not the dialog itself) to dismiss.
    overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeModal();
    });
})();

// ── Status change ──────────────────────────────────────────────
document.querySelectorAll('.msg-status-select').forEach(function (sel) {
    sel.addEventListener('change', function () {
        var id = this.getAttribute('data-id');
        var status = this.value;
        var el = this;

        ajaxPost('<?= API_URL ?>?action=message_update_status', { id: id, status: status }, function (res) {
            if (res.success) {
                // Recolour the select to match its new state.
                el.setAttribute('data-status', status);
                el.closest('tr').style.background = '';
                showAlert('success', res.message);
            } else {
                showAlert('danger', res.message);
            }
        });
    });
});

</script>
