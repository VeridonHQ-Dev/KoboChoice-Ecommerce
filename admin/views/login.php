<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - Kobo Choice</title>
    <link rel="shortcut icon" type="image/x-icon" href="<?= BASE_URL ?>/images/favicon.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin.css?v=<?= @filemtime(ROOT_PATH . '/admin/assets/css/admin.css') ?>">
</head>
<body class="admin-body">
    <div class="admin-login-wrapper">
        <div class="admin-login-logo">
            <img src="<?= BASE_URL ?>/images/menu/logo/login-logo.png" alt="Kobo Choice">
        </div>
        <div class="admin-login-card">
            <div class="logo">
                <h2>Admin Panel</h2>
                <p>Sign in to your account</p>
            </div>

            <?php if (!empty($loginError)): ?>
                <div class="admin-alert admin-alert-danger">
                    <i class="fa fa-exclamation-circle"></i>
                    <?= sanitize($loginError) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= admin_url('/?action=login') ?>">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="admin@kobostores.shop" required
                           value="<?= sanitize($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="admin-btn admin-btn-primary admin-btn-block admin-btn-lg">
                    <i class="fa fa-sign-in"></i> Sign In
                </button>
            </form>

            <div style="text-align:center; margin-top:24px; font-size:13px; color:#64748b;">
                <a href="<?= BASE_URL ?>/" style="color:#64748b; text-decoration:none;">&larr; Back to Store</a>
            </div>
        </div>
    </div>
</body>
</html>
