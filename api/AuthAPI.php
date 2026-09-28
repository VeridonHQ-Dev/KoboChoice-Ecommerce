<?php
class AuthAPI
{
    public static function adminLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            json_error('Email and password are required.');
        }

        $result = admin_login(['email' => $email, 'password' => $password]);

        if ($result['success']) {
            json_success('Login successful.', ['admin' => [
                'id'    => $result['admin']['id'],
                'name'  => $result['admin']['name'],
                'email' => $result['admin']['email'],
                'role'  => $result['admin']['role'],
            ]]);
        } else {
            json_error($result['message']);
        }
    }

    public static function adminLogout(): void
    {
        admin_logout();
        json_success('Logged out.');
    }

    public static function customerLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $login = trim($_POST['login'] ?? $_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login) || empty($password)) {
            json_error('Email/phone and password are required.');
        }

        $result = customer_login(['login' => $login, 'password' => $password]);

        if ($result['success']) {
            json_success('Login successful.', ['customer' => [
                'id'    => $result['customer']['id'],
                'name'  => $result['customer']['name'],
                'email' => $result['customer']['email'],
            ]]);
        } else {
            json_error($result['message']);
        }
    }

    public static function customerRegister(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $result = customer_register($_POST);

        if ($result['success']) {
            json_success('Registration successful.', ['customer' => [
                'id'    => $result['customer']['id'],
                'name'  => $result['customer']['name'],
                'email' => $result['customer']['email'],
            ]]);
        } else {
            json_error($result['errors'][0] ?? 'Registration failed.');
        }
    }

    public static function customerLogout(): void
    {
        customer_logout();
        json_success('Logged out.');
    }

    public static function forgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            // Kept at HTTP 200 (like the other branches below) so the
            // frontend's success-callback can branch on res.success instead
            // of jQuery routing 4xx responses to the generic .fail() handler.
            json_response(['success' => false, 'message' => 'Please enter your email address.']);
            return;
        }

        $result = customer_request_password_reset($email);
        json_response($result);
    }

    public static function verifyResetCode(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $email = trim($_POST['email'] ?? '');
        $code = trim($_POST['code'] ?? '');

        if (empty($email) || empty($code)) {
            json_response(['success' => false, 'message' => 'Please enter the code sent to your email.']);
            return;
        }

        $result = customer_verify_reset_code($email, $code);
        json_response($result);
    }

    public static function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('POST required.', 405);
        }

        $email = trim($_POST['email'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($email) || empty($code) || empty($password) || empty($confirmPassword)) {
            json_response(['success' => false, 'message' => 'Please fill in all fields.']);
            return;
        }

        $result = customer_reset_password($email, $code, $password, $confirmPassword);

        if ($result['success']) {
            json_response([
                'success' => true,
                'message' => 'Your password has been reset successfully.',
                'customer' => [
                    'id'    => $result['customer']['id'],
                    'name'  => $result['customer']['name'],
                    'email' => $result['customer']['email'],
                ],
            ]);
        } else {
            json_response($result);
        }
    }
}
