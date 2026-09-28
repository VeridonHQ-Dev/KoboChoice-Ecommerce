<?php
global $db;

$couponId = (int)($_GET['id'] ?? 0);
$coupon = null;

if ($couponId) {
    $coupon = $db->fetch("SELECT * FROM coupons WHERE id = ?", [$couponId]);
    if (!$coupon) {
        set_flash('error', 'Coupon not found.');
        redirect(admin_url('/?view=coupons'));
    }
}
?>

<h2 style="margin-bottom:20px;"><?= $coupon ? 'Edit Coupon' : 'Add New Coupon' ?></h2>

<div class="admin-card" style="max-width:600px;">
    <form id="couponForm" action="<?= API_URL ?>?action=<?= $coupon ? 'coupon_update' : 'coupon_create' ?>" method="POST">
        <?php if ($coupon): ?>
            <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
        <?php endif; ?>

        <div class="admin-form">
            <div class="form-group">
                <label>Coupon Code *</label>
                <input type="text" name="code" required value="<?= sanitize($coupon['code'] ?? '') ?>" style="text-transform:uppercase;">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Type *</label>
                    <select name="type" required>
                        <option value="percentage" <?= ($coupon['type'] ?? 'percentage') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                        <option value="fixed" <?= ($coupon['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount (<?= CURRENCY ?>)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Value *</label>
                    <input type="number" name="value" step="0.01" min="0" required value="<?= $coupon['value'] ?? '' ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Minimum Order (<?= CURRENCY ?>)</label>
                    <input type="number" name="min_order" step="0.01" min="0" value="<?= $coupon['min_order'] ?? 0 ?>">
                </div>
                <div class="form-group">
                    <label>Max Uses</label>
                    <input type="number" name="max_uses" min="0" value="<?= $coupon['max_uses'] ?? '' ?>">
                    <span class="form-hint">Leave empty for unlimited</span>
                </div>
            </div>
            <div class="form-group">
                <label>Expires At</label>
                <input type="date" name="expires_at" value="<?= $coupon['expires_at'] ? date('Y-m-d', strtotime($coupon['expires_at'])) : '' ?>">
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" <?= ($coupon['is_active'] ?? 1) ? 'checked' : '' ?>> Active
                </label>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="admin-btn admin-btn-primary admin-btn-lg" style="flex:1;">
                    <i class="fa fa-save"></i> <?= $coupon ? 'Update Coupon' : 'Save Coupon' ?>
                </button>
                <a href="<?= admin_url('/?view=coupons') ?>" class="admin-btn admin-btn-outline admin-btn-lg">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('couponForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm('couponForm', function(res) {
        if (res.success) {
            showAlert('success', res.message);
            setTimeout(function() { window.location.href = '<?= admin_url('/?view=coupons') ?>'; }, 1000);
        } else {
            showAlert('danger', res.message || 'Save failed.');
        }
    });
});
</script>
