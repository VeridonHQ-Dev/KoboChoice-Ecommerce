<?php
/**
 * Kobo Choice - Contact Messages (admin)
 *
 * The admin action names `messages_list` / `message_delete` were already
 * whitelisted in the API router but never had a route or implementation,
 * so the delete button on the messages page silently 404'd. This class
 * provides both, plus the Open / Ongoing / Resolved status workflow.
 */
class MessageAPI
{
    public const STATUSES = ['open', 'ongoing', 'resolved'];

    public static function adminList(): void
    {
        global $db;

        $where  = '1';
        $params = [];

        if (!empty($_GET['status']) && in_array($_GET['status'], self::STATUSES, true)) {
            $where .= ' AND status = ?';
            $params[] = $_GET['status'];
        }

        $messages = $db->fetchAll(
            "SELECT * FROM messages WHERE $where ORDER BY created_at DESC LIMIT 200",
            $params
        );

        json_success('', ['messages' => $messages]);
    }

    public static function adminUpdateStatus(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $id     = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');

        if (!$id || !in_array($status, self::STATUSES, true)) {
            json_error('Invalid message ID or status.');
            return;
        }

        $message = $db->fetch("SELECT id FROM messages WHERE id = ?", [$id]);
        if (!$message) {
            json_error('Message not found.', 404);
            return;
        }

        // Touching the status counts as having seen the message.
        $db->update('messages', ['status' => $status, 'is_read' => 1], 'id = ?', [$id]);

        $db->insert('admin_logs', [
            'admin_id'    => $_SESSION['admin_id'] ?? null,
            'action'      => 'message_status',
            'description' => "Message #$id marked as $status",
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        json_success('Message marked as ' . $status . '.');
    }

    public static function adminDelete(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            json_error('Invalid message ID.');
            return;
        }

        $message = $db->fetch("SELECT id FROM messages WHERE id = ?", [$id]);
        if (!$message) {
            json_error('Message not found.', 404);
            return;
        }

        $db->delete('messages', 'id = ?', [$id]);

        $db->insert('admin_logs', [
            'admin_id'    => $_SESSION['admin_id'] ?? null,
            'action'      => 'message_delete',
            'description' => "Message #$id deleted",
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        json_success('Message deleted.');
    }
}
