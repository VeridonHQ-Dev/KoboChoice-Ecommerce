            <!-- Begin Footer Area -->
<?php if (empty($hideChrome)): ?>
            <style>
            /* ── Trust badges: compact horizontal strip on mobile ─────────────
               On phones the four badges stacked into a very tall column. Below
               992px they become a single 4-across row of icon + short label;
               the descriptive paragraph is dropped so the row stays one line
               deep and fits down to ~320px without horizontal scroll. */
            @media (max-width: 991px) {
                .footer-shipping {
                    padding-top: 20px !important;
                    padding-bottom: 16px !important;
                }
                .footer-shipping > .row {
                    display: flex;
                    flex-wrap: nowrap;
                    margin-left: 0;
                    margin-right: 0;
                }
                .footer-shipping > .row > [class*="col-"] {
                    flex: 1 1 0;
                    width: 25%;
                    max-width: 25%;
                    padding: 0 3px !important;
                }
                .footer-shipping .li-shipping-inner-box {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                }
                .footer-shipping .shipping-icon {
                    width: auto; height: auto; line-height: 0;
                }
                .footer-shipping .shipping-icon img { height: 26px; width: auto; }
                .footer-shipping .shipping-text { padding-top: 7px; }
                .footer-shipping .shipping-text h2 {
                    font-size: 11px;
                    font-weight: 500;
                    line-height: 1.3;
                    padding-bottom: 0;
                    margin: 0;
                    /* Balances the two-word labels so the row stays even. */
                    max-width: 11ch;
                }
                /* Icon + label only on mobile. */
                .footer-shipping .shipping-text p { display: none; }
            }
            @media (max-width: 400px) {
                .footer-shipping .shipping-icon img { height: 23px; }
                .footer-shipping .shipping-text h2 { font-size: 10px; }
                .footer-shipping > .row > [class*="col-"] { padding: 0 2px !important; }
            }
            @media (max-width: 340px) {
                .footer-shipping .shipping-text h2 { font-size: 9px; }
            }

            /* ── "More Options" collapse (mobile only) ───────────────────── */
            .footer-more-toggle { display: none; }
            @media (max-width: 991px) {
                .footer-more-toggle {
                    display: flex; align-items: center; justify-content: center; gap: 9px;
                    width: 100%; background: none; border: 0; cursor: pointer;
                    padding: 16px 10px;
                    border-top: 1px solid rgba(255,255,255,.10);
                    border-bottom: 1px solid rgba(255,255,255,.10);
                    color: #fff; font-size: 14px; font-weight: 700;
                    letter-spacing: .5px; text-transform: uppercase;
                }
                .footer-more-toggle i { font-size: 20px; transition: transform .25s ease; }
                .footer-more-toggle[aria-expanded="true"] i { transform: rotate(180deg); }
                /* Collapsed by default; the toggle adds .is-open. */
                .footer-static-middle { display: none; }
                .footer-static-middle.is-open { display: block; }
            }
            </style>
            <div class="footer">
                <?php if (empty($hideShipping)): ?>
                <div class="footer-static-top">
                    <div class="container">
                        <div class="footer-shipping pt-60 pb-55 pb-xs-25">
                            <div class="row">
                                <div class="col-lg-3 col-md-6 col-sm-6 pb-sm-55 pb-xs-55">
                                    <div class="li-shipping-inner-box">
                                        <div class="shipping-icon"><img src="<?= BASE_URL ?>/images/shipping-icon/1.png" alt="Shipping Icon"></div>
                                        <div class="shipping-text">
                                            <h2>Free Delivery</h2>
                                            <p>And free returns. See checkout for delivery dates.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-6 pb-sm-55 pb-xs-55">
                                    <div class="li-shipping-inner-box">
                                        <div class="shipping-icon"><img src="<?= BASE_URL ?>/images/shipping-icon/2.png" alt="Shipping Icon"></div>
                                        <div class="shipping-text">
                                            <h2>Safe Payment</h2>
                                            <p>Pay with the world's most popular and secure payment methods.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-6 pb-xs-30">
                                    <div class="li-shipping-inner-box">
                                        <div class="shipping-icon"><img src="<?= BASE_URL ?>/images/shipping-icon/3.png" alt="Shipping Icon"></div>
                                        <div class="shipping-text">
                                            <h2>Shop with Confidence</h2>
                                            <p>Our Buyer Protection covers your purchase from click to delivery.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-6 pb-xs-30">
                                    <div class="li-shipping-inner-box">
                                        <div class="shipping-icon"><img src="<?= BASE_URL ?>/images/shipping-icon/4.png" alt="Shipping Icon"></div>
                                        <div class="shipping-text">
                                            <h2>24/7 Help Center</h2>
                                            <p>Have a question? Call a Specialist or chat online.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php /* Mobile: everything from the logo down to the newsletter is
                         collapsed behind a "More Options" toggle so the footer
                         doesn't run for several screens on a phone. */ ?>
                <button type="button" class="footer-more-toggle" id="footerMoreToggle" aria-expanded="false" aria-controls="footerCollapse">
                    <span>More Options</span>
                    <i class="fa fa-angle-down" id="footerMoreIcon"></i>
                </button>
                <div class="footer-static-middle" id="footerCollapse">
                    <div class="container">
                        <div class="footer-logo-wrap pt-50 pb-35">
                            <div class="row">
                                <div class="col-lg-4 col-md-6">
                                    <div class="footer-logo">
                                        <img src="<?= BASE_URL ?>/images/menu/logo/2black.png" alt="Footer Logo">
                                        <p class="info"><?= sanitize(get_setting('footer_about', 'We are a team of designers and developers that create high quality HTML Template & Woocommerce, Shopify Theme.')) ?></p>
                                    </div>
                                    <ul class="des">
                                        <li><span>Address: </span><?= sanitize(get_setting('address', '6688 Princess Road, London, Greater London BAS 23JK, UK')) ?></li>
                                        <li><span>Phone: </span><a href="tel:<?= sanitize(get_setting('phone', '(+234) 123 321 345')) ?>"><?= sanitize(get_setting('phone', '(+234) 123 321 345')) ?></a></li>
                                        <li><span>Email: </span><a href="mailto:<?= sanitize(get_setting('email', 'info@kobostores.shop')) ?>"><?= sanitize(get_setting('email', 'info@kobostores.shop')) ?></a></li>
                                    </ul>
                                </div>
                                <div class="col-lg-2 col-md-3 col-sm-6">
                                    <div class="footer-block">
                                        <h3 class="footer-block-title">Product</h3>
                                        <ul>
                                            <li><a href="<?= BASE_URL ?>/shop">Prices drop</a></li>
                                            <li><a href="<?= BASE_URL ?>/shop">New products</a></li>
                                            <li><a href="<?= BASE_URL ?>/shop">Best sales</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-3 col-sm-6">
                                    <div class="footer-block">
                                        <h3 class="footer-block-title">Our company</h3>
                                        <ul>
                                            <li><a href="<?= BASE_URL ?>/about-us">About us</a></li>
                                            <li><a href="<?= BASE_URL ?>/contact">Contact us</a></li>
                                            <li><a href="<?= BASE_URL ?>/contact">Delivery</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="footer-block">
                                        <h3 class="footer-block-title">Follow Us</h3>
                                        <ul class="social-link">
                                            <li class="twitter"><a href="https://twitter.com/" data-toggle="tooltip" target="_blank" title="Twitter"><i class="fa fa-twitter"></i></a></li>
                                            <li class="facebook"><a href="https://www.facebook.com/" data-toggle="tooltip" target="_blank" title="Facebook"><i class="fa fa-facebook"></i></a></li>
                                            <li class="youtube"><a href="https://www.youtube.com/" data-toggle="tooltip" target="_blank" title="Youtube"><i class="fa fa-youtube"></i></a></li>
                                            <li class="instagram"><a href="https://www.instagram.com/" data-toggle="tooltip" target="_blank" title="Instagram"><i class="fa fa-instagram"></i></a></li>
                                        </ul>
                                    </div>
                                    <div class="footer-newsletter">
                                        <h4>Sign up to newsletter</h4>
                                        <form id="newsletterForm" method="post">
                                            <div id="mc-form" class="mc-form subscribe-form form-group">
                                                <input id="mc-email" type="email" autocomplete="off" placeholder="Enter your email" />
                                                <button class="btn" type="submit" id="mc-submit">Subscribe</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="footer-static-bottom pt-55 pb-55">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="copyright text-center">
                                    <a href="#"><img src="<?= BASE_URL ?>/images/payment/1.png" alt=""></a>
                                </div>
                                <div class="copyright text-center pt-25">
                                    <span>&copy; <?= date('Y') ?> <a target="_blank" href="<?= BASE_URL ?>"><?= sanitize(get_setting('site_name', 'Kobo Choice')) ?></a>. All rights reserved.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php endif; ?>
        </div>
        <!-- jQuery -->
        <script src="<?= BASE_URL ?>/js/vendor/jquery-1.12.4.min.js"></script>
        <script src="<?= BASE_URL ?>/js/vendor/popper.min.js"></script>
        <script src="<?= BASE_URL ?>/js/bootstrap.min.js"></script>
        <script src="<?= BASE_URL ?>/js/ajax-mail.js"></script>
        <script src="<?= BASE_URL ?>/js/jquery.meanmenu.min.js"></script>
        <script src="<?= BASE_URL ?>/js/wow.min.js"></script>
        <script src="<?= BASE_URL ?>/js/slick.min.js"></script>
        <script src="<?= BASE_URL ?>/js/owl.carousel.min.js"></script>
        <script src="<?= BASE_URL ?>/js/jquery.magnific-popup.min.js"></script>
        <script src="<?= BASE_URL ?>/js/isotope.pkgd.min.js"></script>
        <script src="<?= BASE_URL ?>/js/imagesloaded.pkgd.min.js"></script>
        <script src="<?= BASE_URL ?>/js/jquery.mixitup.min.js"></script>
        <script src="<?= BASE_URL ?>/js/jquery.countdown.min.js"></script>
        <script src="<?= BASE_URL ?>/js/jquery.counterup.min.js"></script>
        <script src="<?= BASE_URL ?>/js/waypoints.min.js"></script>
        <script src="<?= BASE_URL ?>/js/jquery.barrating.min.js"></script>
        <script src="<?= BASE_URL ?>/js/jquery-ui.min.js"></script>
        <script src="<?= BASE_URL ?>/js/venobox.min.js"></script>
        <script src="<?= BASE_URL ?>/js/jquery.nice-select.min.js"></script>
        <script src="<?= BASE_URL ?>/js/scrollUp.min.js"></script>
        <?php /* Versioned like the stylesheets so browsers pick up JS changes
             instead of serving a stale cached copy. */ ?>
        <script src="<?= BASE_URL ?>/js/main.js?v=<?= @filemtime(ROOT_PATH . "/js/main.js") ?>"></script>
        <script>
        var API_URL = '<?= API_URL ?>';
        var BASE = '<?= BASE_URL ?>';
        var CURRENCY_SYM = '<?= CURRENCY_SYMBOL ?>';
        var CSRF_TOKEN = '<?= generate_csrf_token() ?>';
        // Attach the CSRF token to every same-origin AJAX request automatically.
        if (window.jQuery) {
            $.ajaxSetup({ headers: { 'X-CSRF-Token': CSRF_TOKEN } });
        }
        </script>
        <script>
        (function () {
            var btn = document.getElementById('footerMoreToggle');
            var panel = document.getElementById('footerCollapse');
            if (!btn || !panel) return;
            btn.addEventListener('click', function () {
                var open = panel.classList.toggle('is-open');
                btn.setAttribute('aria-expanded', String(open));
                var icon = document.getElementById('footerMoreIcon');
                if (icon) {
                    icon.classList.toggle('fa-angle-down', !open);
                    icon.classList.toggle('fa-angle-up', open);
                }
            });
        })();
        </script>
        <script src="<?= BASE_URL ?>/js/store.js?v=<?= @filemtime(ROOT_PATH . "/js/store.js") ?>"></script>
        <script>
        function showWarningModal(msg) {
            if (!document.getElementById('siteWarningModal')) {
                var d = document.createElement('div');
                d.id = 'siteWarningModal';
                d.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:99999;align-items:center;justify-content:center;';
                d.innerHTML = '<div style="background:#fff;border-radius:6px;max-width:400px;width:90%;padding:28px;text-align:center;position:relative;box-shadow:0 20px 60px rgba(0,0,0,0.3);"><div style="margin-bottom:14px;"><i class="fa fa-exclamation-triangle" style="font-size:36px;color:#F48201;"></i></div><h3 style="margin:0 0 8px;font-size:17px;color:#1f2430;">Notice</h3><p id="siteWarningModalMsg" style="margin:0 0 20px;font-size:14px;color:#4b5563;line-height:1.5;"></p><button onclick="document.getElementById(\'siteWarningModal\').style.display=\'none\'" style="padding:10px 28px;border:none;border-radius:4px;background:#F48201;color:#fff;font-size:14px;font-weight:600;cursor:pointer;">OK</button></div>';
                document.body.appendChild(d);
                d.addEventListener('click', function(e) { if (e.target === d) d.style.display = 'none'; });
            }
            document.getElementById('siteWarningModalMsg').textContent = msg;
            document.getElementById('siteWarningModal').style.display = 'flex';
        }
        </script>
    </body>
</html>
