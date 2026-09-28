<?php
require_once __DIR__ . '/includes/auth.php';
customer_logout();
header('Location: ' . BASE_URL . '/');
exit;
