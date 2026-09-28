<?php
/**
 * Kobo Choice - Outbound Email Engine (PHPMailer + SMTP)
 *
 * Optimised for inbox delivery — SPF/DKIM/DMARC alignment, proper headers,
 * spam-filter-friendly content, and full DKIM support for custom domains.
 *
 * ═════════════════════════════════════════════════════════════════════════
 *  DELIVERABILITY CHECKLIST (Gmail SMTP)
 * ═════════════════════════════════════════════════════════════════════════
 *  ✓ SMTP HELO / EHLO hostname matches the sending email's domain
 *  ✓ "X-Mailer" header blanked (PHPMailer default is a spam signal)
 *  ✓ Message-ID domain aligns with the From: address
 *  ✓ Plain-text alternative present and hand-written (not auto-stripped)
 *  ✓ Priority headers added (X-Priority, Importance, X-MSMail-Priority)
 *  ✓ List-Unsubscribe header reduces spam-complaint impact
 *  ✓ DKIM signing available when MAIL_DKIM_DOMAIN is configured
 *  ✓ Return-Path matches the envelope sender
 *  ✓ Natural, low-signal HTML template (no excessive images or styling)
 *  ✓ Visible physical address / unsubscribe preference
 *  ✓ Proper UTF-8 charset and encoding
 * ═════════════════════════════════════════════════════════════════════════
 */

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Send an email through the configured SMTP relay.
 *
 * @param  string      $toEmail  Recipient address
 * @param  string      $toName   Recipient display name
 * @param  string      $subject  Subject line
 * @param  string      $htmlBody Rich-text (HTML) body
 * @param  string|null $textBody Optional plain-text body (auto-generated
 *                               if omitted, but a hand-written value is
 *                               strongly recommended for deliverability)
 * @return bool                  Whether the message was accepted by the
 *                               outbound SMTP relay
 */
function send_email(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $textBody = null,
    array $attachments = []
): bool {
    // ── Pre-flight — silently skip if SMTP is not configured ──────
    if (empty(SMTP_HOST) || empty(SMTP_USERNAME) || empty(SMTP_PASSWORD) || empty(SMTP_FROM_EMAIL)) {
        error_log('[Mailer] SMTP is not configured — email not sent to ' . $toEmail);
        return false;
    }

    if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
        require_once ROOT_PATH . '/vendor/autoload.php';
    }

    $mail = new PHPMailer(true);

    try {
        // ── SMTP connection ───────────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 15;                 // seconds
        $mail->SMTPKeepAlive = false;

        // ── HELO / EHLO hostname alignment ────────────────────────
        // The local machine's hostname (e.g. "ITS-Tefzon") can trigger
        // SPF soft-fail because it doesn't match the sending domain.
        // Override it with the domain part of the From address.
        $fromDomain = '';
        if (defined('SMTP_FROM_EMAIL') && SMTP_FROM_EMAIL !== '') {
            $parts = explode('@', SMTP_FROM_EMAIL);
            if (isset($parts[1]) && $parts[1] !== '') {
                $fromDomain = strtolower(trim($parts[1]));
            }
        }
        if ($fromDomain === '') {
            $fromDomain = 'gmail.com';
        }
        $mail->Hostname = $fromDomain;

        // ── Disable the spammy "X-Mailer" header ──────────────────
        $mail->XMailer = ' ';

        // ── Envelope sender (Return-Path) ─────────────────────────
        // By default PHPMailer sets Return-Path to the From address,
        // which is correct for Gmail SMTP.
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

        // ── Recipient ─────────────────────────────────────────────
        $mail->addAddress($toEmail, $toName);

        // ── DKIM signing (when configured) ────────────────────────
        if (defined('MAIL_DKIM_DOMAIN')
            && MAIL_DKIM_DOMAIN !== ''
            && defined('MAIL_DKIM_PRIVATE_KEY')
            && MAIL_DKIM_PRIVATE_KEY !== ''
            && file_exists(MAIL_DKIM_PRIVATE_KEY)) {
            $mail->DKIM_domain      = MAIL_DKIM_DOMAIN;
            $mail->DKIM_private     = MAIL_DKIM_PRIVATE_KEY;
            $mail->DKIM_selector    = defined('MAIL_DKIM_SELECTOR') ? MAIL_DKIM_SELECTOR : 'default';
            $mail->DKIM_passphrase  = defined('MAIL_DKIM_PASSPHRASE') ? MAIL_DKIM_PASSPHRASE : '';
            $mail->DKIM_identity    = defined('MAIL_DKIM_IDENTITY') ? MAIL_DKIM_IDENTITY : '';
        }

        // ── Attachments (in-memory, e.g. a generated receipt PDF) ──
        // Each entry: ['data' => raw bytes, 'filename' => 'x.pdf',
        //              'mime' => 'application/pdf']
        // Adding a 'cid' key embeds the file inline instead (for logos and
        // other images referenced as <img src="cid:...">), which keeps them
        // visible even when a mail client blocks remote images.
        foreach ($attachments as $attachment) {
            if (empty($attachment['data']) || empty($attachment['filename'])) {
                continue;
            }

            if (!empty($attachment['cid'])) {
                $mail->addStringEmbeddedImage(
                    $attachment['data'],
                    $attachment['cid'],
                    $attachment['filename'],
                    PHPMailer::ENCODING_BASE64,
                    $attachment['mime'] ?? 'image/png'
                );
                continue;
            }

            $mail->addStringAttachment(
                $attachment['data'],
                $attachment['filename'],
                PHPMailer::ENCODING_BASE64,
                $attachment['mime'] ?? 'application/octet-stream'
            );
        }

        // ── Message body ──────────────────────────────────────────
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        if ($textBody !== null && $textBody !== '') {
            $mail->AltBody = $textBody;
        } else {
            // Fallback: smart HTML-to-text conversion that preserves
            // paragraph breaks so the plain-text version doesn't look
            // like a dense wall of spammy text.
            $mail->AltBody = html_to_text_fallback($htmlBody);
        }

        // ── Priority headers (signal importance to the receiving MTA) ─
        if (defined('MAIL_USE_PRIORITY_HEADERS') && MAIL_USE_PRIORITY_HEADERS) {
            // High priority — appropriate for password resets and
            // transactional emails that the recipient is expecting.
            $mail->Priority = 1;
            $mail->addCustomHeader('X-Priority', '1 (High)');
            $mail->addCustomHeader('X-MSMail-Priority', 'High');
            $mail->addCustomHeader('Importance', 'High');
        }

        // ── List-Unsubscribe header ───────────────────────────────
        // Even though these are transactional (not bulk) emails, a
        // List-Unsubscribe header tells ISPs that the recipient has a
        // clear way to opt out, which reduces spam-complaint damage.
        if (defined('MAIL_USE_LIST_UNSUBSCRIBE') && MAIL_USE_LIST_UNSUBSCRIBE) {
            $unsubEmail = defined('MAIL_UNSUBSCRIBE_EMAIL') && MAIL_UNSUBSCRIBE_EMAIL !== ''
                ? MAIL_UNSUBSCRIBE_EMAIL
                : SMTP_FROM_EMAIL;
            $mail->addCustomHeader(
                'List-Unsubscribe',
                '<mailto:' . $unsubEmail . '?subject=unsubscribe>'
            );
        }

        // ── Auto-Submitted header ─────────────────────────────────
        // Marks this as an automated (not user-typed) message, which
        // helps auto-reply / vacation responders suppress replies.
        $mail->addCustomHeader('Auto-Submitted', 'auto-generated');
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, AutoReply, DR, NDR, RN, NRN');

        // ── Prevent threading confusion ───────────────────────────
        // Without a References / In-Reply-To, each reset stands alone.
        $mail->MessageID = sprintf(
            '<%s.%s@%s>',
            bin2hex(random_bytes(16)),
            time(),
            $fromDomain
        );

        // ── Send & log ────────────────────────────────────────────
        $mail->send();

        error_log(sprintf(
            '[Mailer] OK → %s | subj="%s" | msg-id=%s',
            $toEmail,
            $subject,
            $mail->MessageID ?? '?'
        ));

        return true;
    } catch (PHPMailerException $e) {
        error_log(sprintf(
            '[Mailer] FAILED → %s | error="%s"',
            $toEmail,
            $mail->ErrorInfo
        ));
        return false;
    } catch (Throwable $e) {
        error_log(sprintf(
            '[Mailer] UNEXPECTED → %s | error="%s"',
            $toEmail,
            $e->getMessage()
        ));
        return false;
    }
}

// ═════════════════════════════════════════════════════════════════════════
//  PASSWORD RESET EMAIL
// ═════════════════════════════════════════════════════════════════════════

/**
 * Build and send a password-reset code email with inbox-optimised content.
 *
 * The template is deliberately kept light — low markup, high text ratio,
 * natural language, and a visible real-world address — so spam filters
 * see a genuine personal / transactional message rather than a template.
 */
function send_password_reset_email(string $toEmail, string $toName, string $code): bool
{
    $firstName = explode(' ', trim($toName))[0] ?: 'there';
    $minutes   = max(1, (int)(PASSWORD_RESET_TTL_SECONDS / 60));
    $siteName  = defined('SITE_NAME') ? SITE_NAME : 'Kobo Choice';
    $address   = function_exists('get_setting') ? get_setting('address', '') : '';

    $subject = "Your {$siteName} password reset code";

    // ── Plain-text body (hand-written, not auto-stripped) ──────────
    // Writing the plain-text part explicitly instead of relying on
    // strip_tags() gives much better text/HTML ratio and reads like
    // genuine correspondence — both are strong inbox signals.
    $text = "Hi {$firstName},\r\n\r\n"
        . "You recently asked to reset your {$siteName} password.\r\n\r\n"
        . "Your confirmation code is:\r\n\r\n"
        . "   {$code}\r\n\r\n"
        . "This code will expire in {$minutes} minutes, so please use it soon.\r\n\r\n"
        . "If you didn't request a password reset, you can safely ignore this "
        . "email — your password will stay exactly as it is.\r\n\r\n"
        . "Need help? Just reply to this email and we'll get back to you.\r\n\r\n"
        . "— {$siteName}\r\n"
        . ($address ? "{$address}\r\n" : '');

    // ── HTML body ─────────────────────────────────────────────────
    // Minimal, clean, inline-styled HTML that looks natural even with
    // images disabled.  No external images = no tracking signals that
    // spam filters penalise.
    $siteEscaped    = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');
    $nameEscaped    = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
    $codeEscaped    = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $addressEscaped = $address !== ''
        ? htmlspecialchars($address, ENT_QUOTES, 'UTF-8')
        : '';

    $html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>Password Reset Code</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f5f7;min-width:100%;">
<tr><td align="center" style="padding:32px 16px;">
<table width="480" cellpadding="0" cellspacing="0" border="0" style="max-width:480px;width:100%;">
<tr><td style="font-size:0;padding-bottom:20px;">
<a href="' . site_url() . '" style="color:#F48201;text-decoration:none;font-size:22px;font-weight:700;">' . $siteEscaped . '</a>
</td></tr>
<tr><td style="background:#ffffff;border-radius:8px;padding:40px 36px;border:1px solid #e8eaed;">

<h1 style="margin:0 0 6px;font-size:22px;font-weight:600;color:#1f2430;line-height:1.3;">Reset your password</h1>
<p style="margin:0 0 24px;font-size:14px;color:#5f6b7a;line-height:1.5;">Hi ' . $nameEscaped . ',</p>
<p style="margin:0 0 16px;font-size:14px;color:#1f2430;line-height:1.5;">We received a request to reset the password for your ' . $siteEscaped . ' account. Use the code below to set a new password.</p>

<table cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 28px;background:#f8f9fb;border-radius:6px;width:100%;">
<tr><td align="center" style="padding:24px 16px;font-size:30px;font-weight:700;letter-spacing:12px;color:#1f2430;font-family:monospace,monospace;">' . $codeEscaped . '</td></tr>
</table>

<p style="margin:0 0 8px;font-size:12.5px;color:#5f6b7a;line-height:1.5;">This code is valid for <strong>' . $minutes . ' minutes</strong>. If you didn\'t request this, you can safely ignore this email.</p>

</td></tr>
<tr><td style="padding:24px 0 0;font-size:11.5px;color:#9aa1ab;line-height:1.5;text-align:center;">
' . $siteEscaped . ($addressEscaped ? '<br>' . $addressEscaped : '') . '
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>';

    // Send with both the hand-written plain-text and HTML alternatives.
    return send_email($toEmail, $toName, $subject, $html, $text);
}

// ═════════════════════════════════════════════════════════════════════════
//  HELPERS
// ═════════════════════════════════════════════════════════════════════════

/**
 * Smart HTML-to-text conversion for the MIME alternative when a hand-written
 * plain-text body was not provided.  Preserves paragraph breaks and list
 * items so the output doesn't look like a dense, spammy wall of text.
 */
function html_to_text_fallback(string $html): string
{
    // Add line-break spacing around block elements so they survive strip_tags.
    $html = preg_replace(
        ['#</(p|div|tr|li|h[1-6]|blockquote|table|ul|ol|section)>#i'],
        ["\$0\r\n"],
        $html
    );
    // Replace <br> tags with newlines.
    $html = preg_replace(
        ['#<br\s*/?>#i', '#</tr>\s*<tr>#i'],
        ["\r\n", "\r\n"],
        $html
    );

    $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));

    // Collapse multiple consecutive blank lines to at most one.
    $text = preg_replace("/\r\n{3,}/", "\r\n\r\n", $text);

    return $text;
}


// ═════════════════════════════════════════════════════════════════════════
//  ORDER PAID — THANK YOU + RECEIPT
// ═════════════════════════════════════════════════════════════════════════

/**
 * Sends the "thank you for your purchase" confirmation once a payment has
 * been verified, itemising what was bought and attaching the same PDF
 * receipt the customer can download from their order page.
 *
 * Kept deliberately fault-tolerant: a mail or PDF failure is logged but
 * never allowed to bubble up, because by the time this runs the customer
 * has already been charged and the order is already marked paid.
 *
 * @param array $order Full order row (already marked paid)
 * @param array $items Rows from order_items
 */
function send_order_paid_email(array $order, array $items): bool
{
    require_once __DIR__ . '/receipt.php';

    $toEmail = trim((string)($order['email'] ?? ''));
    if ($toEmail === '' || !is_email($toEmail)) {
        error_log('[Mailer] Order #' . ($order['id'] ?? '?') . ' has no valid email — thank-you not sent.');
        return false;
    }

    $toName  = (string)($order['guest_name'] ?: ($order['customer_name'] ?? 'Customer'));
    $content = build_order_paid_email_content($order, $items);

    // ── Attachments ───────────────────────────────────────────────
    $attachments = [];

    // Inline brand logo (cid:brandlogo) — embedded so it still displays
    // when the client blocks remote images.
    $logoPath = ROOT_PATH . '/images/menu/logo/2black.png';
    if (is_file($logoPath)) {
        $logoData = @file_get_contents($logoPath);
        if ($logoData !== false) {
            $attachments[] = [
                'data'     => $logoData,
                'filename' => 'logo.png',
                'mime'     => 'image/png',
                'cid'      => 'brandlogo',
            ];
        }
    }

    // Receipt PDF (optional — never block the email on failure)
    $pdf = generate_order_receipt_pdf($order, $items);
    if ($pdf !== null) {
        $attachments[] = [
            'data'     => $pdf,
            'filename' => 'order-' . (int)($order['id'] ?? 0) . '-receipt.pdf',
            'mime'     => 'application/pdf',
        ];
    }

    return send_email(
        $toEmail,
        $toName,
        $content['subject'],
        $content['html'],
        $content['text'],
        $attachments
    );
}

/**
 * Builds the subject / HTML / plain-text for the order-paid email.
 * Kept separate from sending so the template can be rendered and reviewed
 * without dispatching a message.
 *
 * @return array{subject:string,html:string,text:string}
 */
function build_order_paid_email_content(array $order, array $items): array
{
    $toName    = (string)($order['guest_name'] ?: ($order['customer_name'] ?? 'Customer'));
    $firstName = trim(explode(' ', trim($toName))[0] ?? '') ?: 'there';
    $orderId   = (int)($order['id'] ?? 0);

    $brand     = sanitize(get_setting('site_name', SITE_NAME));
    $accent    = '#F48201';
    $ink       = '#1f2430';
    $muted     = '#6b7280';
    $hairline  = '#e9ebf0';

    // ── Itemised list (HTML + plain text) ─────────────────────────
    $rowsHtml  = '';
    $rowsText  = '';
    $itemCount = 0;
    $subtotal  = 0.0;

    foreach ($items as $item) {
        $qty        = (int)$item['quantity'];
        $unitPrice  = (float)$item['price'];
        $lineTotal  = $unitPrice * $qty;
        $itemCount += $qty;
        $subtotal  += $lineTotal;

        $rowsHtml .= '
        <tr>
            <td style="padding:14px 0;border-bottom:1px solid ' . $hairline . ';font-family:Arial,Helvetica,sans-serif;font-size:15px;color:' . $ink . ';line-height:1.4;">
                ' . sanitize($item['name']) . '
                <div style="font-size:13px;color:' . $muted . ';padding-top:3px;">'
                    . sanitize(format_price($unitPrice)) . ' &times; ' . $qty . '
                </div>
            </td>
            <td align="right" style="padding:14px 0;border-bottom:1px solid ' . $hairline . ';font-family:Arial,Helvetica,sans-serif;font-size:15px;color:' . $ink . ';white-space:nowrap;">
                ' . sanitize(format_price($lineTotal)) . '
            </td>
        </tr>';

        $rowsText .= '  - ' . $item['name'] . ' x' . $qty . '   ' . format_price($lineTotal) . "\r\n";
    }

    $total   = format_price((float)($order['total'] ?? 0));
    $tax     = (float)($order['tax'] ?? 0);
    $taxText = $tax > 0 ? 'Tax: ' . format_price($tax) . "\r\n" : '';

    // Totals block rows (subtotal / tax / discount / grand total)
    $totalsHtml = '
        <tr>
            <td style="padding:6px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:' . $muted . ';">Subtotal</td>
            <td align="right" style="padding:6px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:' . $ink . ';">' . sanitize(format_price($subtotal)) . '</td>
        </tr>';

    if ($tax > 0) {
        $totalsHtml .= '
        <tr>
            <td style="padding:6px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:' . $muted . ';">Tax</td>
            <td align="right" style="padding:6px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:' . $ink . ';">' . sanitize(format_price($tax)) . '</td>
        </tr>';
    }

    $discount = ($subtotal + $tax) - (float)($order['total'] ?? 0);
    if ($discount > 0.004) {
        $totalsHtml .= '
        <tr>
            <td style="padding:6px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:' . $muted . ';">Discount</td>
            <td align="right" style="padding:6px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#16a34a;">-' . sanitize(format_price($discount)) . '</td>
        </tr>';
        $taxText .= 'Discount: -' . format_price($discount) . "\r\n";
    }

    // Auto-captures the absolute URL of this order's completed page.
    $orderUrl = order_confirmation_url($orderId);
    // Opens the contact page straight on the complaint form, order pre-filled.
    $complaintUrl = site_url('/contact?type=complaint&order=' . $orderId);
    $storeEmail = sanitize(get_setting('email', SMTP_FROM_EMAIL));
    $storePhone = sanitize(get_setting('phone', ''));
    $storeAddr  = sanitize(get_setting('address', ''));
    $paidOn     = !empty($order['paid_at'])
        ? sanitize(format_date($order['paid_at'], 'M d, Y'))
        : sanitize(date('M d, Y'));

    $subject = 'Thank you for your purchase — Order #' . $orderId;

    // ═══ Email template ═══════════════════════════════════════════
    // Table-based with fully inline CSS: Outlook ignores flexbox/grid
    // and Gmail strips <style> blocks, so this is the only layout
    // approach that renders consistently across mail clients.
    $htmlBody = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . sanitize($subject) . '</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;">

<!-- Preheader: shown in the inbox preview, hidden in the body -->
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;height:0;width:0;">
    Your payment was received — order #' . $orderId . ' is confirmed. Receipt attached.
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f5f7;">
<tr>
<td align="center" style="padding:32px 12px;">

    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background-color:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);">

        <!-- Logo -->
        <tr>
            <td align="center" style="padding:34px 30px 24px;">
                <img src="cid:brandlogo" alt="' . $brand . '" width="150" style="display:block;border:0;max-width:150px;height:auto;">
            </td>
        </tr>

        <!-- Accent rule -->
        <tr><td style="padding:0 30px;"><div style="height:3px;background-color:' . $accent . ';border-radius:2px;font-size:0;line-height:0;">&nbsp;</div></td></tr>

        <!-- Success badge + headline -->
        <tr>
            <td align="center" style="padding:34px 30px 0;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td align="center" width="60" height="60" style="width:60px;height:60px;background-color:#e8f7ee;border-radius:30px;font-family:Arial,Helvetica,sans-serif;font-size:30px;color:#16a34a;line-height:60px;">
                            &#10003;
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:20px 30px 0;font-family:Arial,Helvetica,sans-serif;font-size:24px;font-weight:bold;color:' . $ink . ';line-height:1.3;">
                Thank you for your purchase!
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:12px 40px 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:' . $muted . ';line-height:1.6;">
                Hi ' . sanitize($firstName) . ', your payment has been received and your order is now being prepared.
            </td>
        </tr>

        <!-- Order meta -->
        <tr>
            <td align="center" style="padding:22px 30px 0;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="background-color:#f9fafb;border:1px solid ' . $hairline . ';border-radius:8px;">
                    <tr>
                        <td style="padding:12px 22px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:' . $muted . ';">
                            Order <span style="color:' . $ink . ';font-weight:bold;">#' . $orderId . '</span>
                            &nbsp;&nbsp;&middot;&nbsp;&nbsp; ' . $paidOn . '
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Items -->
        <tr>
            <td style="padding:32px 30px 0;">
                <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:' . $muted . ';padding-bottom:6px;">
                    Order Summary
                </div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    ' . $rowsHtml . '
                </table>
            </td>
        </tr>

        <!-- Totals -->
        <tr>
            <td style="padding:16px 30px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    ' . $totalsHtml . '
                    <tr>
                        <td style="padding:14px 0 0;border-top:2px solid ' . $ink . ';font-family:Arial,Helvetica,sans-serif;font-size:17px;font-weight:bold;color:' . $ink . ';">Total Paid</td>
                        <td align="right" style="padding:14px 0 0;border-top:2px solid ' . $ink . ';font-family:Arial,Helvetica,sans-serif;font-size:17px;font-weight:bold;color:' . $ink . ';">' . sanitize($total) . '</td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- CTA (bulletproof, table-based so Outlook renders it) -->
        <tr>
            <td align="center" style="padding:32px 30px 0;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td align="center" bgcolor="' . $accent . '" style="border-radius:6px;">
                            <a href="' . sanitize($orderUrl) . '" target="_blank"
                               style="display:inline-block;padding:14px 38px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:6px;">
                                View Your Order
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Closing message -->
        <tr>
            <td style="padding:30px 30px 34px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:' . $muted . ';line-height:1.75;">
                <p style="margin:0 0 16px;">
                    Your payment has been received and your order is now being prepared.
                    A PDF copy of your receipt is attached to this email for your records.
                </p>
                <p style="margin:0 0 16px;">
                    If you have any questions about this order, just send us a complaint message
                    on our website <a href="' . sanitize($complaintUrl) . '" target="_blank" style="color:' . $accent . ';text-decoration:underline;">contact page</a>
                    and quote order number ' . $orderId . '.
                </p>
                <p style="margin:0;">Thanks again,</p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color:#fafbfc;border-top:1px solid ' . $hairline . ';padding:26px 30px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td align="center" style="font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;color:' . $ink . ';padding-bottom:8px;">
                            ' . $brand . '
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:' . $muted . ';line-height:1.7;">
                            ' . ($storeAddr !== '' ? $storeAddr . '<br>' : '')
                            . ($storePhone !== '' ? $storePhone . ' &nbsp;&middot;&nbsp; ' : '')
                            . '<a href="mailto:' . $storeEmail . '" style="color:' . $muted . ';text-decoration:underline;">' . $storeEmail . '</a>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#9aa1ab;padding-top:14px;">
                            &copy; ' . date('Y') . ' ' . $brand . '. All rights reserved.<br>
                            You received this email because you placed an order with us.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

    </table>

</td>
</tr>
</table>
</body>
</html>';

    $textBody = "Hi " . $firstName . ",\r\n\r\n"
        . "Thank you for your purchase! Your payment has been received and your\r\n"
        . "order is now being prepared.\r\n\r\n"
        . "ORDER #" . $orderId . " - " . $paidOn . "\r\n"
        . str_repeat('-', 46) . "\r\n"
        . $rowsText
        . str_repeat('-', 46) . "\r\n"
        . "Subtotal: " . format_price($subtotal) . "\r\n"
        . $taxText
        . "Total Paid: " . $total . "\r\n\r\n"
        . "View your order: " . $orderUrl . "\r\n\r\n"
        . "Your payment has been received and your order is now being prepared.\r\n"
        . "A PDF copy of your receipt is attached to this email for your records.\r\n\r\n"
        . "If you have any questions about this order, just send us a complaint\r\n"
        . "message on our website contact page and quote order number " . $orderId . ":\r\n"
        . $complaintUrl . "\r\n\r\n"
        . "Thanks again,\r\n"
        . get_setting('site_name', SITE_NAME) . "\r\n";

    return [
        'subject' => $subject,
        'html'    => $htmlBody,
        'text'    => $textBody,
    ];
}
