<?php
/**
 * Kobo Choice - 404 Not Found
 *
 * Replaces the old static 404.html, which was never wired to Apache and
 * whose links pointed at theme demo files that do not exist here.
 * Registered via ErrorDocument in .htaccess.
 */
require_once __DIR__ . '/includes/auth.php';

http_response_code(404);

$pageTitle = 'Page Not Found';
$bodyClass = 'inner-page';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb-area">
    <div class="container">
        <div class="breadcrumb-content">
            <ul>
                <li><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="active">404 Error</li>
            </ul>
        </div>
    </div>
</div>

<div class="error404-area pt-30 pb-60">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="error-wrapper text-center ptb-50 pt-xs-20">
                    <div class="error-text">
                        <h1>404</h1>
                        <h2>Oops! Page Not Found</h2>
                        <p>Sorry, the page you are looking for does not exist, has been removed,<br>
                           had its name changed, or is temporarily unavailable.</p>
                    </div>
                    <div class="search-error">
                        <form id="search-form" action="<?= BASE_URL ?>/shop" method="GET">
                            <input type="hidden" name="view" value="grid">
                            <input type="text" name="q" placeholder="Search our products">
                            <button type="submit"><i class="zmdi zmdi-search"></i></button>
                        </form>
                    </div>
                    <div class="error-button">
                        <a href="<?= BASE_URL ?>/">Back to home page</a>
                    </div>
                    <p style="margin-top:26px;font-size:15px;color:#6b7280;">
                        Need a hand? <a href="<?= BASE_URL ?>/contact" style="color:#F48201;">Contact our team</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
