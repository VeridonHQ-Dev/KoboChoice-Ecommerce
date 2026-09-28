<?php
global $db;

$admins = $db->fetchAll("SELECT * FROM admins ORDER BY created_at DESC");
?>

<div class="admin-toolbar">
    <h3 style="margin:0;">Admin Users (<?= count($admins) ?>)</h3>
    <?php if (is_super_admin()): ?>
    <a href="<?= admin_url('/?view=admins&sub=create') ?>" class="admin-btn admin-btn-primary">
        <i class="fa fa-plus"></i> Add Admin
    </a>
    <?php endif; ?>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Login</th>
                <?php if (is_super_admin()): ?>
                <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($admins as $a): ?>
            <tr>
                <td>#<?= $a['id'] ?></td>
                <td><strong><?= sanitize($a['name']) ?></strong></td>
                <td><?= sanitize($a['email']) ?></td>
                <td><span class="badge-status <?= $a['role'] === 'super_admin' ? 'badge-active' : 'badge-paid' ?>"><?= ucfirst(str_replace('_', ' ', $a['role'])) ?></span></td>
                <td><span class="badge-status <?= $a['is_active'] ? 'badge-active' : 'badge-cancelled' ?>"><?= $a['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td><?= $a['last_login'] ? time_ago($a['last_login']) : 'Never' ?></td>
                <?php if (is_super_admin()): ?>
                <td class="actions">
                    <?php if ($a['email'] !== $_SESSION['admin_email']): ?>
                    <a href="<?= admin_url('/?view=admins&sub=edit&id=' . $a['id']) ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                        <i class="fa fa-pencil"></i>
                    </a>
                    <button class="admin-btn admin-btn-danger admin-btn-sm btn-delete"
                            data-url="<?= API_URL ?>?action=admin_delete"
                            data-id="<?= $a['id'] ?>" data-type="Admin">
                        <i class="fa fa-trash"></i>
                    </button>
                    <?php else: ?>
                    <span style="color:#94a3b8;font-size:12px;">Current user</span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
