<?php
/**
 * Kobo Choice - Customer Login / Register
 * Login shown first; toggles to Register. Body bg (#F3F4F6) comes from the
 * inner-page class set in login.php.
 */
$googleEnabled = defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '';

/* Optional post-login destination (e.g. the order a customer clicked through
 * to from their receipt email). Only same-site paths under BASE_URL are
 * accepted — anything with a scheme or protocol-relative "//host" prefix is
 * discarded so this can never become an open redirect. */
$loginRedirect = '';
if (!empty($_GET['redirect'])) {
    $candidate = (string) $_GET['redirect'];
    if (str_starts_with($candidate, BASE_URL . '/')
        && !preg_match('~^//|^[a-z][a-z0-9+.\-]*:~i', $candidate)) {
        $loginRedirect = $candidate;
    }
}
?>
<style>
.auth-wrap { max-width: 460px; margin: 0 auto 80px; padding: 50px 15px 60px; }
.auth-logo { text-align: center; margin-bottom: 34px; }
.auth-logo img { max-height: 60px; width: auto; }
.auth-card {
    background: #fff; border-radius: 0; padding: 48px 46px;
    box-shadow: 0 12px 40px rgba(16,24,40,.06); border: 1px solid #edeef2;
}
.auth-card h2 { font-size: 26px; font-weight: 700; margin: 0 0 6px; color: #1f2430; letter-spacing: -.3px; text-align: center; }
.auth-card .auth-sub { color: #7a828e; font-size: 14px; margin-bottom: 32px; text-align: center; }
#login-card .auth-field label { font-weight: 400; }
.auth-field { margin-bottom: 22px; }
.auth-field label { display: block; font-size: 13.5px; font-weight: 600; color: #384049; margin-bottom: 9px; }
.auth-field .label-row { display: flex; justify-content: space-between; align-items: center; }
.auth-field .label-row a { font-size: 12.5px; color: #F48201; font-weight: 500; }
.auth-input {
    width: 100%; padding: 14px 16px; border: 1px solid #dcdfe4; border-radius: 8px;
    font-size: 14.5px; color: #1f2430; background: #fff; transition: border-color .15s, box-shadow .15s;
}
.auth-input::placeholder { color: #aeb4bd; }
.auth-input:focus { outline: none; border-color: #F48201; box-shadow: 0 0 0 3px rgba(244,130,1,.12); }
.auth-pass-wrap { position: relative; }
.auth-pass-wrap .auth-input { padding-right: 44px; }
.auth-pass-toggle {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
    cursor: pointer; color: #98a0aa; font-size: 16px; user-select: none;
}
.auth-btn {
    width: 100%; padding: 15px; border: none; border-radius: 8px; cursor: pointer;
    font-size: 15.5px; font-weight: 600; background: #F48201; color: #fff;
    transition: background .15s; margin-top: 4px;
}
.auth-btn:hover { background: #d97101; }
.auth-btn:disabled { opacity: .6; cursor: not-allowed; }
.auth-divider { display: flex; align-items: center; text-align: center; margin: 28px 0; color: #9aa1ab; font-size: 12px; font-weight: 500; }
.auth-divider::before, .auth-divider::after { content: ""; flex: 1; height: 1px; background: #e6e8ec; }
.auth-divider span { padding: 0 16px; }
.auth-social {
    width: 100%; padding: 13px; border: 1px solid #dcdfe4; border-radius: 8px; background: #fff;
    display: flex; align-items: center; justify-content: center; gap: 12px; cursor: pointer;
    font-size: 14.5px; font-weight: 500; color: #384049; margin-bottom: 14px; transition: background .15s, border-color .15s;
}
.auth-social:hover { background: #f7f8fa; border-color: #c4c9d0; }
.auth-social svg { width: 20px; height: 20px; }
.auth-switch { text-align: center; margin-top: 28px; padding-top: 24px; border-top: 1px solid #eef0f3; font-size: 14px; color: #6b727c; }
.auth-switch button {
    background: none; border: none; color: #F48201; font-weight: 600; cursor: pointer;
    font-size: 14px; padding: 0 3px;
}
.auth-switch button:hover { text-decoration: underline; }
.auth-msg:not(:empty) { margin-bottom: 18px; }
.auth-row2 { display: flex; gap: 16px; }
.auth-row2 > .auth-field { flex: 1; }
.pw-checklist { list-style: none; padding: 0; margin: 12px 0 0; }
.pw-checklist li { font-size: 12.5px; color: #98a0aa; margin-bottom: 6px; transition: color .15s; }
.pw-checklist li .ico { display: inline-block; width: 18px; font-weight: 700; }
.pw-checklist li.valid { color: #16a34a; }
.pw-checklist li.invalid { color: #cf3b3b; }
.hidden { display: none; }
.auth-hint { font-size: 12px; color: #7a828e; margin: 8px 0 0; }
.auth-link-btn {
    display: block; width: 100%; background: none; border: none; color: #F48201; font-weight: 600;
    cursor: pointer; font-size: 13px; margin-top: 14px; text-align: center; padding: 0;
}
.auth-link-btn:hover { text-decoration: underline; }
.auth-link-btn:disabled { color: #c7cbd1; cursor: not-allowed; text-decoration: none; }
@media (max-width: 575px) {
    .auth-card { padding: 32px 22px; }
    .auth-row2 { flex-direction: column; gap: 0; }
}
</style>

<div class="auth-wrap">
    <div class="auth-logo">
        <a href="<?= BASE_URL ?>/"><img src="<?= BASE_URL ?>/images/menu/logo/login-logo.png" alt="Kobo Choice"></a>
    </div>

    <!-- ─── LOGIN CARD ─────────────────────────────────────────────── -->
    <div class="auth-card" id="login-card">
        <h2>Welcome back</h2>
        <p class="auth-sub">Sign in to your Kobo Choice account.</p>

        <form id="login-form" novalidate>
            <div class="auth-msg" id="login-msg"></div>

            <div class="auth-field">
                <label for="login-id">Email Address or Phone Number</label>
                <input class="auth-input" type="text" id="login-id" placeholder="Enter Email Address OR Phone Number" autocomplete="username">
            </div>

            <div class="auth-field">
                <div class="label-row">
                    <label for="login-password">Password</label>
                    <a href="#" id="forgot-link">Forgot Password?</a>
                </div>
                <div class="auth-pass-wrap">
                    <input class="auth-input" type="password" id="login-password" placeholder="Enter Password" autocomplete="current-password">
                    <span class="auth-pass-toggle" data-target="login-password"><i class="fa fa-eye"></i></span>
                </div>
            </div>

            <button type="submit" class="auth-btn">Login</button>
        </form>

        <div class="auth-divider"><span>OR</span></div>

        <button type="button" class="auth-social" id="google-login">
            <svg viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
            Login with Google
        </button>

        <button type="button" class="auth-social" id="apple-login">
            <svg viewBox="0 0 24 24"><path fill="#000" d="M17.05 12.04c-.03-2.6 2.13-3.85 2.22-3.91-1.21-1.77-3.09-2.01-3.76-2.04-1.6-.16-3.12.94-3.93.94-.81 0-2.06-.92-3.39-.89-1.74.03-3.35 1.01-4.25 2.57-1.81 3.14-.46 7.79 1.3 10.34.86 1.25 1.89 2.65 3.24 2.6 1.3-.05 1.79-.84 3.36-.84 1.57 0 2.01.84 3.39.81 1.4-.02 2.29-1.27 3.15-2.53.99-1.45 1.4-2.85 1.42-2.93-.03-.01-2.73-1.05-2.76-4.16zM14.53 4.4c.72-.87 1.2-2.08 1.07-3.29-1.03.04-2.28.69-3.02 1.56-.66.77-1.24 2-1.08 3.18 1.15.09 2.32-.58 3.03-1.45z"/></svg>
            Login with Apple
        </button>

        <div class="auth-switch">
            Don't have an account?
            <button type="button" id="show-register">Create an Account</button>
        </div>
    </div>

    <!-- ─── REGISTER CARD ──────────────────────────────────────────── -->
    <div class="auth-card hidden" id="register-card">
        <h2>Create your account</h2>
        <p class="auth-sub">Join Kobo Choice in a few seconds.</p>

        <form id="register-form" novalidate>
            <div class="auth-msg" id="register-msg"></div>

            <div class="auth-row2">
                <div class="auth-field">
                    <label for="reg-first">First Name</label>
                    <input class="auth-input" type="text" id="reg-first" placeholder="First Name">
                </div>
                <div class="auth-field">
                    <label for="reg-last">Last Name</label>
                    <input class="auth-input" type="text" id="reg-last" placeholder="Last Name">
                </div>
            </div>

            <div class="auth-field">
                <label for="reg-email">Email Address</label>
                <input class="auth-input" type="email" id="reg-email" placeholder="Email Address">
            </div>

            <div class="auth-field">
                <label for="reg-phone">Phone Number</label>
                <input class="auth-input" type="text" id="reg-phone" placeholder="Phone Number">
            </div>

            <div class="auth-row2">
                <div class="auth-field">
                    <label for="reg-dob">Date of Birth</label>
                    <input class="auth-input" type="date" id="reg-dob">
                </div>
                <div class="auth-field">
                    <label for="reg-gender">Gender</label>
                    <select class="auth-input" id="reg-gender">
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <div class="auth-field">
                <label for="reg-password">Password</label>
                <div class="auth-pass-wrap">
                    <input class="auth-input" type="password" id="reg-password" placeholder="Create a password">
                    <span class="auth-pass-toggle" data-target="reg-password"><i class="fa fa-eye"></i></span>
                </div>
                <ul class="pw-checklist" id="pw-checklist">
                    <li data-rule="len"><span class="ico">✕</span> Minimum 8 characters</li>
                    <li data-rule="upper"><span class="ico">✕</span> At least one uppercase letter</li>
                    <li data-rule="num"><span class="ico">✕</span> At least one number</li>
                    <li data-rule="special"><span class="ico">✕</span> At least one special character</li>
                </ul>
            </div>

            <div class="auth-field">
                <label for="reg-confirm">Confirm Password</label>
                <div class="auth-pass-wrap">
                    <input class="auth-input" type="password" id="reg-confirm" placeholder="Re-enter your password">
                    <span class="auth-pass-toggle" data-target="reg-confirm"><i class="fa fa-eye"></i></span>
                </div>
            </div>

            <button type="submit" class="auth-btn">Create Account</button>
        </form>

        <div class="auth-switch">
            Already have an account?
            <button type="button" id="show-login">Login</button>
        </div>
    </div>

    <!-- ─── FORGOT PASSWORD CARD ───────────────────────────────────── -->
    <div class="auth-card hidden" id="forgot-card">
        <h2>Reset your password</h2>
        <p class="auth-sub">Enter your email and we'll send you a 6-digit reset code.</p>

        <!-- Stage 1: request code -->
        <form id="forgot-email-form" novalidate>
            <div class="auth-msg" id="forgot-msg"></div>
            <div class="auth-field">
                <label for="forgot-email">Email Address</label>
                <input class="auth-input" type="email" id="forgot-email" placeholder="Enter your registered email">
            </div>
            <button type="submit" class="auth-btn">Send Reset Code</button>
        </form>

        <!-- Stage 2: enter code -->
        <form id="forgot-code-form" class="hidden" novalidate>
            <div class="auth-msg" id="forgot-code-msg"></div>
            <div class="auth-field">
                <label for="forgot-code">6-Digit Code</label>
                <input class="auth-input" type="text" id="forgot-code" placeholder="Enter the 6-digit code" maxlength="6" inputmode="numeric" autocomplete="one-time-code">
                <p class="auth-hint" id="forgot-code-timer"></p>
            </div>
            <button type="submit" class="auth-btn">Verify Code</button>
            <button type="button" class="auth-link-btn" id="forgot-resend">Resend Code</button>
        </form>

        <!-- Stage 3: new password -->
        <form id="forgot-password-form" class="hidden" novalidate>
            <div class="auth-msg" id="forgot-password-msg"></div>
            <div class="auth-field">
                <label for="forgot-new-password">New Password</label>
                <div class="auth-pass-wrap">
                    <input class="auth-input" type="password" id="forgot-new-password" placeholder="Create a new password">
                    <span class="auth-pass-toggle" data-target="forgot-new-password"><i class="fa fa-eye"></i></span>
                </div>
                <ul class="pw-checklist" id="forgot-pw-checklist">
                    <li data-rule="len"><span class="ico">✕</span> Minimum 8 characters</li>
                    <li data-rule="upper"><span class="ico">✕</span> At least one uppercase letter</li>
                    <li data-rule="num"><span class="ico">✕</span> At least one number</li>
                    <li data-rule="special"><span class="ico">✕</span> At least one special character</li>
                </ul>
            </div>
            <div class="auth-field">
                <label for="forgot-confirm-password">Confirm New Password</label>
                <div class="auth-pass-wrap">
                    <input class="auth-input" type="password" id="forgot-confirm-password" placeholder="Re-enter your new password">
                    <span class="auth-pass-toggle" data-target="forgot-confirm-password"><i class="fa fa-eye"></i></span>
                </div>
            </div>
            <button type="submit" class="auth-btn">Reset Password</button>
        </form>

        <div class="auth-switch">
            Remembered your password?
            <button type="button" id="forgot-back-to-login">Login</button>
        </div>
    </div>
</div>

<!-- ─── Reset Code Sent Modal ──────────────────────────────────────── -->
<div id="resetCodeSentModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:99999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:8px;max-width:420px;width:90%;padding:32px 28px;text-align:center;position:relative;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="margin-bottom:14px;"><i class="fa fa-envelope-o" style="font-size:40px;color:#F48201;"></i></div>
        <h3 style="margin:0 0 10px;font-size:18px;color:#1f2430;">Reset Code Sent</h3>
        <p style="margin:0 0 10px;font-size:14px;color:#374151;line-height:1.6;">
            Password reset code sent to <strong id="resetCodeSentEmail"></strong>.
        </p>
        <p style="margin:0 0 24px;font-size:13.5px;color:#6b7280;line-height:1.6;">
            If you can't see it in your inbox, please check your <strong>Spam/Junk folder</strong> and mark it as <strong>"Not Spam"</strong>.
        </p>
        <button type="button" id="resetCodeSentOk" style="padding:11px 34px;border:none;border-radius:6px;background:#F48201;color:#fff;font-size:14px;font-weight:600;cursor:pointer;">OK</button>
    </div>
</div>

<script>
// Rendered before jQuery (footer) — defer until $ is available.
window.addEventListener('load', function () {
    if (typeof jQuery === 'undefined') { return; }
    (function ($) {
        var API = '<?php echo API_URL; ?>';
        var BASE = '<?php echo base_url(); ?>';
        var GOOGLE_ENABLED = <?php echo $googleEnabled ? 'true' : 'false'; ?>;
        // Where to land after a successful login/registration — the page the
        // customer was trying to reach, or the home page by default.
        var AFTER_LOGIN = <?php echo $loginRedirect !== '' ? json_encode($loginRedirect) : 'BASE'; ?>;

        // ── Toggle between Login and Register ──────────────────────────
        $('#show-register').on('click', function () {
            $('#login-card').addClass('hidden');
            $('#register-card').removeClass('hidden');
        });
        $('#show-login').on('click', function () {
            $('#register-card').addClass('hidden');
            $('#login-card').removeClass('hidden');
        });

        // ── Show / hide password ───────────────────────────────────────
        $('.auth-pass-toggle').on('click', function () {
            var input = document.getElementById($(this).data('target'));
            var icon = $(this).find('i');
            if (input.type === 'password') { input.type = 'text'; icon.removeClass('fa-eye').addClass('fa-eye-slash'); }
            else { input.type = 'password'; icon.removeClass('fa-eye-slash').addClass('fa-eye'); }
        });

        // ── Live password checklist ────────────────────────────────────
        function checkPassword(pw) {
            return {
                len:     pw.length >= 8,
                upper:   /[A-Z]/.test(pw),
                num:     /[0-9]/.test(pw),
                special: /[^A-Za-z0-9]/.test(pw)
            };
        }
        $('#reg-password').on('input', function () {
            var r = checkPassword(this.value);
            $('#pw-checklist li').each(function () {
                var ok = r[$(this).data('rule')];
                $(this).toggleClass('valid', ok).toggleClass('invalid', !ok && this.dataset.touched === '1');
                this.dataset.touched = '1';
                $(this).find('.ico').text(ok ? '✓' : '✕');
            });
        });

        // ── Social buttons ─────────────────────────────────────────────
        $('#google-login').on('click', function () {
            if (!GOOGLE_ENABLED) { showWarningModal('Google login is not configured yet. Please add your Google credentials in config, or use email/password.'); return; }
            window.location.href = BASE + '/auth/google.php';
        });
        $('#apple-login').on('click', function () {
            showWarningModal('Login with Apple is coming soon.');
        });
        $('#forgot-link').on('click', function (e) {
            e.preventDefault();
            $('#login-card, #register-card').addClass('hidden');
            $('#forgot-card').removeClass('hidden');
            resetForgotStage1();
        });
        $('#forgot-back-to-login').on('click', function () {
            clearInterval(forgotCountdownTimer);
            clearInterval(forgotResendTimer);
            $('#forgot-card').addClass('hidden');
            $('#login-card').removeClass('hidden');
        });

        // ── Login submit (email OR phone) ──────────────────────────────
        $('#login-form').on('submit', function (e) {
            e.preventDefault();
            var $msg = $('#login-msg'), $btn = $(this).find('button[type="submit"]');
            var login = $('#login-id').val().trim();
            var password = $('#login-password').val();
            if (!login || !password) {
                $msg.html('<div class="alert alert-danger">Please enter your email/phone and password.</div>'); return;
            }
            $btn.prop('disabled', true).text('Logging in...');
            $.post(API + '?action=customer_login', { login: login, password: password }, function (res) {
                if (res.success) {
                    $msg.html('<div class="alert alert-success">Login successful!</div>');
                    setTimeout(function () { window.location.href = AFTER_LOGIN; }, 1000);
                }
                else { $msg.html('<div class="alert alert-danger">' + res.message + '</div>'); $btn.prop('disabled', false).text('Login'); }
            }, 'json').fail(function () {
                $msg.html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
                $btn.prop('disabled', false).text('Login');
            });
        });

        // ── Register submit ────────────────────────────────────────────
        $('#register-form').on('submit', function (e) {
            e.preventDefault();
            var $msg = $('#register-msg'), $btn = $(this).find('button[type="submit"]');
            var data = {
                first_name: $('#reg-first').val().trim(),
                last_name:  $('#reg-last').val().trim(),
                email:      $('#reg-email').val().trim(),
                phone:      $('#reg-phone').val().trim(),
                date_of_birth: $('#reg-dob').val(),
                gender:     $('#reg-gender').val(),
                password:   $('#reg-password').val(),
                confirm_password: $('#reg-confirm').val()
            };
            if (!data.first_name || !data.last_name || !data.email || !data.password) {
                $msg.html('<div class="alert alert-danger">Please fill in the required fields.</div>'); return;
            }
            var r = checkPassword(data.password);
            if (!(r.len && r.upper && r.num && r.special)) {
                $msg.html('<div class="alert alert-danger">Your password does not meet all the requirements.</div>'); return;
            }
            if (data.password !== data.confirm_password) {
                $msg.html('<div class="alert alert-danger">Passwords do not match.</div>'); return;
            }
            $btn.prop('disabled', true).text('Creating account...');
            $.post(API + '?action=customer_register', data, function (res) {
                if (res.success) { window.location.href = AFTER_LOGIN; }
                else {
                    var m = res.errors ? res.errors[0] : res.message;
                    $msg.html('<div class="alert alert-danger">' + m + '</div>');
                    $btn.prop('disabled', false).text('Create Account');
                }
            }, 'json').fail(function () {
                $msg.html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
                $btn.prop('disabled', false).text('Create Account');
            });
        });

        // ── Forgot password flow ─────────────────────────────────────────
        var forgotEmail = '';
        var forgotCountdownTimer = null;
        var forgotResendTimer = null;
        var FORGOT_CODE_TTL_SECONDS = <?php echo (int)PASSWORD_RESET_TTL_SECONDS; ?>;
        var FORGOT_RESEND_COOLDOWN_SECONDS = <?php echo (int)PASSWORD_RESET_RESEND_COOLDOWN_SECONDS; ?>;

        function resetForgotStage1() {
            clearInterval(forgotCountdownTimer);
            clearInterval(forgotResendTimer);
            $('#forgot-email-form').removeClass('hidden');
            $('#forgot-code-form, #forgot-password-form').addClass('hidden');
            $('#forgot-msg, #forgot-code-msg, #forgot-password-msg').empty();
            $('#forgot-email').val('');
            $('#forgot-code').val('');
            $('#forgot-new-password, #forgot-confirm-password').val('');
            $('#forgot-pw-checklist li').removeClass('valid invalid').each(function () {
                this.dataset.touched = '0';
                $(this).find('.ico').text('✕');
            });
        }

        function startForgotCountdown() {
            var remaining = FORGOT_CODE_TTL_SECONDS;
            clearInterval(forgotCountdownTimer);
            function tick() {
                var m = Math.floor(remaining / 60), s = remaining % 60;
                $('#forgot-code-timer').text('Code expires in ' + m + ':' + (s < 10 ? '0' : '') + s);
                if (remaining <= 0) {
                    clearInterval(forgotCountdownTimer);
                    $('#forgot-code-timer').text('Code expired. Please request a new one.');
                    return;
                }
                remaining--;
            }
            tick();
            forgotCountdownTimer = setInterval(tick, 1000);
        }

        function startResendCooldown() {
            var remaining = FORGOT_RESEND_COOLDOWN_SECONDS;
            var $btn = $('#forgot-resend');
            $btn.prop('disabled', true);
            clearInterval(forgotResendTimer);
            function tick() {
                if (remaining <= 0) {
                    clearInterval(forgotResendTimer);
                    $btn.prop('disabled', false).text('Resend Code');
                    return;
                }
                $btn.text('Resend Code (' + remaining + 's)');
                remaining--;
            }
            tick();
            forgotResendTimer = setInterval(tick, 1000);
        }

        // Reset-code-sent modal — proactively points customers at Spam/Junk,
        // since the code can land there depending on their mail provider.
        function openResetCodeSentModal(email) {
            $('#resetCodeSentEmail').text(email);
            $('#resetCodeSentModal').css('display', 'flex');
        }
        $('#resetCodeSentOk').on('click', function () {
            $('#resetCodeSentModal').hide();
        });
        $('#resetCodeSentModal').on('click', function (e) {
            if (e.target === this) $(this).hide();
        });

        // Stage 1: request code
        $('#forgot-email-form').on('submit', function (e) {
            e.preventDefault();
            var $msg = $('#forgot-msg'), $btn = $(this).find('button[type="submit"]');
            var email = $('#forgot-email').val().trim();
            if (!email) {
                $msg.html('<div class="alert alert-danger">Please enter your email address.</div>'); return;
            }
            $btn.prop('disabled', true).text('Sending...');
            $.post(API + '?action=customer_forgot_password', { email: email }, function (res) {
                $btn.prop('disabled', false).text('Send Reset Code');
                if (res.success) {
                    forgotEmail = email;
                    $('#forgot-email-form').addClass('hidden');
                    $('#forgot-code-form').removeClass('hidden');
                    $('#forgot-code-msg').html('<div class="alert alert-success">A 6-digit code has been sent to ' + email + '.</div>');
                    startForgotCountdown();
                    startResendCooldown();
                    openResetCodeSentModal(email);
                } else {
                    $msg.html('<div class="alert alert-danger">' + res.message + '</div>');
                }
            }, 'json').fail(function () {
                $btn.prop('disabled', false).text('Send Reset Code');
                $msg.html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
            });
        });

        // Resend code (same request, restarts both timers)
        $('#forgot-resend').on('click', function () {
            if ($(this).prop('disabled') || !forgotEmail) return;
            $.post(API + '?action=customer_forgot_password', { email: forgotEmail }, function (res) {
                if (res.success) {
                    $('#forgot-code').val('');
                    $('#forgot-code-msg').html('<div class="alert alert-success">A new code has been sent to ' + forgotEmail + '.</div>');
                    startForgotCountdown();
                    startResendCooldown();
                    openResetCodeSentModal(forgotEmail);
                } else {
                    $('#forgot-code-msg').html('<div class="alert alert-danger">' + res.message + '</div>');
                }
            }, 'json');
        });

        // Stage 2: verify code
        $('#forgot-code-form').on('submit', function (e) {
            e.preventDefault();
            var $msg = $('#forgot-code-msg'), $btn = $(this).find('button[type="submit"]');
            var code = $('#forgot-code').val().trim();
            if (!code) {
                $msg.html('<div class="alert alert-danger">Please enter the 6-digit code.</div>'); return;
            }
            $btn.prop('disabled', true).text('Verifying...');
            $.post(API + '?action=customer_verify_reset_code', { email: forgotEmail, code: code }, function (res) {
                $btn.prop('disabled', false).text('Verify Code');
                if (res.success) {
                    clearInterval(forgotCountdownTimer);
                    clearInterval(forgotResendTimer);
                    $('#forgot-code-form').addClass('hidden');
                    $('#forgot-password-form').removeClass('hidden');
                } else {
                    $msg.html('<div class="alert alert-danger">' + res.message + '</div>');
                }
            }, 'json').fail(function () {
                $btn.prop('disabled', false).text('Verify Code');
                $msg.html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
            });
        });

        // Stage 3: live password checklist + submit
        $('#forgot-new-password').on('input', function () {
            var r = checkPassword(this.value);
            $('#forgot-pw-checklist li').each(function () {
                var ok = r[$(this).data('rule')];
                $(this).toggleClass('valid', ok).toggleClass('invalid', !ok && this.dataset.touched === '1');
                this.dataset.touched = '1';
                $(this).find('.ico').text(ok ? '✓' : '✕');
            });
        });
        $('#forgot-password-form').on('submit', function (e) {
            e.preventDefault();
            var $msg = $('#forgot-password-msg'), $btn = $(this).find('button[type="submit"]');
            var pw = $('#forgot-new-password').val();
            var confirmPw = $('#forgot-confirm-password').val();
            var r = checkPassword(pw);
            if (!(r.len && r.upper && r.num && r.special)) {
                $msg.html('<div class="alert alert-danger">Your password does not meet all the requirements.</div>'); return;
            }
            if (pw !== confirmPw) {
                $msg.html('<div class="alert alert-danger">Passwords do not match.</div>'); return;
            }
            $btn.prop('disabled', true).text('Resetting...');
            $.post(API + '?action=customer_reset_password', {
                email: forgotEmail,
                code: $('#forgot-code').val().trim(),
                password: pw,
                confirm_password: confirmPw
            }, function (res) {
                if (res.success) {
                    $msg.html('<div class="alert alert-success">Password reset successful! Logging you in...</div>');
                    setTimeout(function () { window.location.href = AFTER_LOGIN; }, 1200);
                } else {
                    $btn.prop('disabled', false).text('Reset Password');
                    $msg.html('<div class="alert alert-danger">' + res.message + '</div>');
                }
            }, 'json').fail(function () {
                $btn.prop('disabled', false).text('Reset Password');
                $msg.html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
            });
        });
    })(jQuery);
});
</script>
