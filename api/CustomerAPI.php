<?php
class CustomerAPI
{
    public static function getProfile(): void
    {
        global $db;

        $customer = $db->fetch(
            "SELECT id, name, email, phone, date_of_birth, gender, address, profile_image,
                    shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_country,
                    billing_name, billing_phone, billing_address, billing_city, billing_state, billing_country, created_at
             FROM customers WHERE id = ?",
            [$_SESSION['customer_id']]
        );

        if (!$customer) {
            json_error('Customer not found.', 404);
            return;
        }

        json_success('', ['customer' => $customer]);
    }

    public static function updateProfile(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $customerId = $_SESSION['customer_id'];
        $data = $_POST;

        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $gender = $data['gender'] ?? null;
        $dob = $data['date_of_birth'] ?? null;

        if (empty($name)) {
            json_error('Name is required.');
            return;
        }

        if (!in_array($gender, ['male', 'female', 'other'], true)) {
            $gender = null;
        }

        if ($dob !== '' && $dob !== null) {
            $d = DateTime::createFromFormat('Y-m-d', $dob);
            if (!$d || $d->format('Y-m-d') !== $dob || $d > new DateTime()) {
                json_error('Please enter a valid date of birth.');
                return;
            }
        } else {
            $dob = null;
        }

        $existing = $db->fetch("SELECT id FROM customers WHERE email = ? AND id != ?", [$data['email'] ?? '', $customerId]);
        if ($existing) {
            json_error('This email is already in use.');
            return;
        }

        $db->update('customers', [
            'name'          => $name,
            'email'         => trim($data['email'] ?? ''),
            'phone'         => $phone ?: null,
            'gender'        => $gender,
            'date_of_birth' => $dob,
        ], 'id = ?', [$customerId]);

        $_SESSION['customer_name'] = $name;
        $_SESSION['customer_email'] = trim($data['email'] ?? '');

        json_success('Profile updated successfully.');
    }

    public static function changePassword(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $customerId = $_SESSION['customer_id'];
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($current) || empty($newPass)) {
            json_error('All password fields are required.');
            return;
        }

        $customer = $db->fetch("SELECT password_hash FROM customers WHERE id = ?", [$customerId]);

        if (!$customer || !password_verify($current, $customer['password_hash'])) {
            json_error('Current password is incorrect.');
            return;
        }

        if ($newPass !== $confirm) {
            json_error('New passwords do not match.');
            return;
        }

        $pwErrors = password_strength_errors($newPass);
        if (!empty($pwErrors)) {
            json_error('Password requirements: ' . implode(', ', $pwErrors) . '.');
            return;
        }

        $db->update('customers', [
            'password_hash' => password_hash($newPass, PASSWORD_DEFAULT),
        ], 'id = ?', [$customerId]);

        json_success('Password changed successfully.');
    }

    public static function updateAddress(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $customerId = $_SESSION['customer_id'];
        $data = $_POST;

        $fields = [
            'shipping_name'    => trim($data['shipping_name'] ?? ''),
            'shipping_phone'   => trim($data['shipping_phone'] ?? ''),
            'shipping_address' => trim($data['shipping_address'] ?? ''),
            'shipping_city'    => trim($data['shipping_city'] ?? ''),
            'shipping_state'   => trim($data['shipping_state'] ?? ''),
            'shipping_country' => trim($data['shipping_country'] ?? 'Nigeria'),
            'billing_name'     => trim($data['billing_name'] ?? ''),
            'billing_phone'    => trim($data['billing_phone'] ?? ''),
            'billing_address'  => trim($data['billing_address'] ?? ''),
            'billing_city'     => trim($data['billing_city'] ?? ''),
            'billing_state'    => trim($data['billing_state'] ?? ''),
            'billing_country'  => trim($data['billing_country'] ?? 'Nigeria'),
        ];

        if (empty($fields['shipping_name']) || empty($fields['shipping_address']) || empty($fields['shipping_city']) || empty($fields['shipping_state'])) {
            json_error('Shipping name, address, city, and state are required.');
            return;
        }

        $db->update('customers', $fields, 'id = ?', [$customerId]);

        json_success('Shipping & billing information saved successfully.');
    }

    public static function orders(): void
    {
        global $db;

        $customerId = $_SESSION['customer_id'];

        $orders = $db->fetchAll(
            "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC",
            [$customerId]
        );

        json_success('', ['orders' => $orders]);
    }

    public static function orderDetail(): void
    {
        global $db;

        $customerId = $_SESSION['customer_id'];
        $orderId = (int)($_GET['id'] ?? 0);

        if (!$orderId) {
            json_error('Invalid order ID.');
            return;
        }

        $order = $db->fetch(
            "SELECT * FROM orders WHERE id = ? AND customer_id = ?",
            [$orderId, $customerId]
        );

        if (!$order) {
            json_error('Order not found.', 404);
            return;
        }

        $order['items'] = $db->fetchAll(
            "SELECT oi.*, p.image, p.slug
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?",
            [$orderId]
        );

        json_success('', ['order' => $order]);
    }

    public static function cancelOrder(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $customerId = $_SESSION['customer_id'];
        $orderId = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['cancel_reason'] ?? '');

        if (!$orderId) {
            json_error('Invalid order ID.');
            return;
        }

        $order = $db->fetch(
            "SELECT * FROM orders WHERE id = ? AND customer_id = ?",
            [$orderId, $customerId]
        );

        if (!$order) {
            json_error('Order not found.', 404);
            return;
        }

        if (in_array($order['status'], ['cancelled', 'delivered', 'completed'])) {
            json_error('This order cannot be cancelled.');
            return;
        }

        $db->update('orders', [
            'status' => 'cancelled',
            'cancel_reason' => $reason ?: null,
        ], 'id = ?', [$orderId]);

        json_success('Order #' . $orderId . ' has been cancelled.');
    }

    /* ─── Admin Customer Management ───────────────────────────────────── */

    public static function adminList(): void
    {
        global $db;

        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 15;

        $where = '1';
        $params = [];

        if ($search !== '') {
            $where .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $term = '%' . $search . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (in_array($status, ['active', 'disabled'], true)) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }

        $total = (int)$db->fetch("SELECT COUNT(*) as c FROM customers WHERE $where", $params)['c'];
        $totalPages = max(1, ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $customers = $db->fetchAll(
            "SELECT c.*, (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) as order_count,
                    (SELECT SUM(total) FROM orders WHERE customer_id = c.id) as total_spent
             FROM customers c WHERE $where ORDER BY c.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        json_success('', [
            'customers' => $customers,
            'total'     => $total,
            'page'      => $page,
            'pages'     => $totalPages,
        ]);
    }

    public static function adminDetail(): void
    {
        global $db;

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            json_error('Invalid customer ID.');
            return;
        }

        $customer = $db->fetch("SELECT * FROM customers WHERE id = ?", [$id]);
        if (!$customer) {
            json_error('Customer not found.', 404);
            return;
        }

        unset($customer['password_hash']);

        $customer['orders'] = $db->fetchAll(
            "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC",
            [$id]
        );

        $customer['order_count'] = count($customer['orders']);
        $customer['total_spent'] = (float)($db->fetch(
            "SELECT COALESCE(SUM(total), 0) as t FROM orders WHERE customer_id = ?",
            [$id]
        )['t'] ?? 0);

        json_success('', ['customer' => $customer]);
    }

    public static function adminDisable(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        if (!is_super_admin()) {
            json_error('Only Super Admin can disable customer accounts.', 403);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            json_error('Invalid customer ID.');
            return;
        }

        $customer = $db->fetch("SELECT id, status FROM customers WHERE id = ?", [$id]);
        if (!$customer) {
            json_error('Customer not found.', 404);
            return;
        }

        if ($customer['status'] === 'disabled') {
            json_error('Customer account is already disabled.');
            return;
        }

        $db->update('customers', ['status' => 'disabled'], 'id = ?', [$id]);
        $db->delete('customer_sessions', 'customer_id = ?', [$id]);

        json_success('Customer account has been disabled.');
    }

    public static function adminEnable(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        if (!is_super_admin()) {
            json_error('Only Super Admin can reactivate customer accounts.', 403);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            json_error('Invalid customer ID.');
            return;
        }

        $customer = $db->fetch("SELECT id, status FROM customers WHERE id = ?", [$id]);
        if (!$customer) {
            json_error('Customer not found.', 404);
            return;
        }

        if ($customer['status'] === 'active') {
            json_error('Customer account is already active.');
            return;
        }

        $db->update('customers', ['status' => 'active'], 'id = ?', [$id]);

        json_success('Customer account has been reactivated.');
    }

    public static function adminDelete(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        if (!is_super_admin()) {
            json_error('Only Super Admin can delete customer accounts.', 403);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            json_error('Invalid customer ID.');
            return;
        }

        $customer = $db->fetch("SELECT id, name FROM customers WHERE id = ?", [$id]);
        if (!$customer) {
            json_error('Customer not found.', 404);
            return;
        }

        $completedOrderCount = (int)$db->fetch(
            "SELECT COUNT(*) as c FROM orders WHERE customer_id = ? AND status IN ('paid','processing','shipped','delivered','completed')",
            [$id]
        )['c'];

        if ($completedOrderCount > 0) {
            json_error('Cannot delete this customer — they have ' . $completedOrderCount . ' completed order(s). Disable the account instead.');
            return;
        }

        // Pending/cancelled orders (e.g. bot signups, abandoned checkouts) don't block
        // deletion — the FK sets their customer_id to NULL, keeping the order record intact.
        $db->delete('customer_sessions', 'customer_id = ?', [$id]);
        $db->delete('customers', 'id = ?', [$id]);

        json_success('Customer "' . $customer['name'] . '" has been permanently deleted.');
    }

    public static function uploadProfileImage(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            json_error('Please select an image to upload.');
            return;
        }

        $file = $_FILES['profile_image'];

        if ($file['size'] > 5 * 1024 * 1024) {
            json_error('Image must be less than 5MB.');
            return;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            json_error('Only JPG, PNG, and WebP images are allowed.');
            return;
        }

        $customerId = $_SESSION['customer_id'];
        $dest = __DIR__ . '/../public/uploads/customers';

        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $ext = match ($mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        $filename = 'profile_' . $customerId . '_' . uniqid() . '.' . $ext;
        $target = $dest . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            json_error('Failed to save image. Please try again.');
            return;
        }

        // Delete old profile image if exists
        $old = $db->fetch("SELECT profile_image FROM customers WHERE id = ?", [$customerId]);
        if ($old && !empty($old['profile_image'])) {
            $oldPath = $dest . '/' . $old['profile_image'];
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $db->update('customers', ['profile_image' => $filename], 'id = ?', [$customerId]);

        $imageUrl = BASE_URL . '/public/uploads/customers/' . $filename;
        json_success('Profile image updated.', ['image_url' => $imageUrl, 'filename' => $filename]);
    }

    public static function removeProfileImage(): void
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $customerId = $_SESSION['customer_id'];
        $customer = $db->fetch("SELECT profile_image FROM customers WHERE id = ?", [$customerId]);

        if (!$customer || empty($customer['profile_image'])) {
            json_error('No profile image to remove.');
            return;
        }

        $dest = __DIR__ . '/../public/uploads/customers';
        $path = $dest . '/' . $customer['profile_image'];
        if (file_exists($path)) {
            @unlink($path);
        }

        $db->update('customers', ['profile_image' => null], 'id = ?', [$customerId]);

        json_success('Profile image removed.');
    }
}
