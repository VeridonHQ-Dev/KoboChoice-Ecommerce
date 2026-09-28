<?php
class NewsletterAPI
{
    public static function subscribe(): void
    {
        global $db;

        $email = trim($_POST['email'] ?? '');
        if (empty($email) || !is_email($email)) {
            json_error('Please enter a valid email address.');
            return;
        }

        $existing = $db->fetch("SELECT id, is_active FROM subscribers WHERE email = ?", [$email]);

        if ($existing) {
            if ($existing['is_active']) {
                json_error('You are already subscribed.');
            } else {
                $db->update('subscribers', ['is_active' => 1], 'id = ?', [$existing['id']]);
                json_success('Welcome back! You have been re-subscribed.');
            }
            return;
        }

        $db->insert('subscribers', ['email' => $email]);
        json_success('Thank you for subscribing!');
    }
}
