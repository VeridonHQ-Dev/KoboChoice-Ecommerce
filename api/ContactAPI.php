<?php
class ContactAPI
{
    public static function submit(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $data = $_POST;
        $errors = required_fields($data, [
            'name'    => 'Name',
            'email'   => 'Email',
            'message' => 'Message',
        ]);

        if (!empty($errors)) {
            json_error(implode(' ', $errors));
            return;
        }

        if (!is_email($data['email'])) {
            json_error('Please enter a valid email address.');
            return;
        }

        // The contact page posts either the general enquiry form or the
        // complaint form; anything else is treated as a general enquiry.
        $type = ($data['enquiry_type'] ?? '') === 'complaint' ? 'complaint' : 'contact';

        $subject = trim((string)($data['subject'] ?? ''));

        if ($type === 'complaint') {
            $orderNumber = trim((string)($data['order_number'] ?? ''));
            if ($orderNumber === '') {
                json_error('Please enter the order number your complaint relates to.');
                return;
            }
            if (!ctype_digit($orderNumber)) {
                json_error('Order number must be a number, e.g. 30.');
                return;
            }

            $subject = 'COMPLAINT — Order #' . $orderNumber
                . ($subject !== '' ? ' — ' . $subject : '');
        } elseif ($subject === '') {
            $subject = 'General Enquiry';
        }

        $db->insert('messages', [
            'name'    => $data['name'],
            'email'   => $data['email'],
            'subject' => mb_substr($subject, 0, 190),
            'message' => $data['message'],
        ]);

        json_success(
            $type === 'complaint'
                ? 'Your complaint has been received. Our team will review it and get back to you shortly.'
                : 'Message sent. We will get back to you soon.'
        );
    }
}
