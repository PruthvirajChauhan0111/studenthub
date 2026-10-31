<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !sh_verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid logout request. Return to the dashboard and try again.');
}

sh_logout();
header('Location: login.php?logged_out=1');
exit;
