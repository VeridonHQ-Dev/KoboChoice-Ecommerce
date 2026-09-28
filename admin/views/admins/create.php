<?php
global $db;

$adminId = (int)($_GET['id'] ?? 0);
$admin = null;

if ($adminId) {
    $admin = $db->fetch("SELECT * FROM admins WHERE id = ?", [$adminId]);
    if (!$admin) {
        set_flash('error', 'Admin not found.');
        redirect(admin_url('/?view=admins'));
    }
}
?>

<h2 style="margin-bottom:20px;"><?= $admin ? 'Edit Admin' : 'Add New Admin' ?></h2>

<div class="admin-card" style="max-width:600px;">
    <form id="adminForm" action="<?= API_URL ?>?action=<?= $admin ? 'admin_update' : 'admin_create' ?>" method="POST">
        <?php if ($admin): ?>
            <input type="hidden" name="id" value="<?= $admin['id'] ?>">
        <?php endif; ?>

        <div class="admin-form">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" required value="<?= sanitize($admin['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" required value="<?= sanitize($admin['email'] ?? '') ?>">
            </div>
            <?php if (!$admin): ?>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <?php else: ?>
            <div class="form-group">
                <label>New Password (leave blank to keep current)</label>
                <input type="password" name="password" minlength="6">
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="admin" <?= ($admin['role'] ?? 'admin') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="super_admin" <?= ($admin['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" <?= ($admin['is_active'] ?? 1) ? 'checked' : '' ?>> Active
                </label>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="admin-btn admin-btn-primary admin-btn-lg" style="flex:1;">
                    <i class="fa fa-save"></i> <?= $admin ? 'Update Admin' : 'Save Admin' ?>
                </button>
                <a href="<?= admin_url('/?view=admins') ?>" class="admin-btn admin-btn-outline admin-btn-lg">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('adminForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm('adminForm', function(res) {
        if (res.success) {
            showAlert('success', res.message);
            setTimeout(function() {
                window.location.href = '<?= admin_url('/?view=admins') ?>';
            }, 1000);
        } else {
            showAlert('danger', res.message || 'Save failed.');
        }
    });
});
</script>
