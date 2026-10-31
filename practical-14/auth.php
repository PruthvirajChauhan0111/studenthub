<?php
declare(strict_types=1);

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

const SH_SESSION_TIMEOUT = 1200;

function sh_escape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sh_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function sh_verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function sh_home_for_role(?string $role): string
{
    return $role === 'admin' ? 'admin_dashboard.php' : 'dashboard.php';
}

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    // Check session inactivity timeout.
    $now = time();
    $lastActivity = (int)($_SESSION['last_activity'] ?? $now);

    if ($now - $lastActivity > SH_SESSION_TIMEOUT) {
        sh_logout();
        header('Location: login.php?timeout=1');
        exit;
    }

    // Connect to the database.
    global $conn;
    require_once __DIR__ . '/db_mysqli.php';

    // Check whether the account still exists and is active.
    $stmt = $conn->prepare(
        "SELECT role, status FROM users WHERE id = ? LIMIT 1"
    );

    if (!$stmt) {
        error_log($conn->error);
        http_response_code(500);
        exit('Unable to verify your account. Please try again later.');
    }

    $userId = (int) $_SESSION['user_id'];

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $account = $result->fetch_assoc();

    $stmt->close();

    // Log out if the account was deleted or deactivated.
    if (!$account || $account['status'] !== 'active') {
        sh_logout();
        header('Location: login.php?inactive=1');
        exit;
    }

    // Refresh the role from the database.
    $_SESSION['role'] = $account['role'];

    // Update last activity time.
    $_SESSION['last_activity'] = $now;
}

function requireRole(string $role): void
{
    requireLogin();

    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        $destination = sh_home_for_role($_SESSION['role'] ?? 'student');

        echo '<!DOCTYPE html><html lang="en"><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Access denied</title><body style="font-family:Arial;padding:40px;background:#f4f6f9;color:#172b4d"><h1>Access denied</h1><p>You do not have permission to view this page.</p><a href="' . sh_escape($destination) . '">Return to your dashboard</a></body></html>';
        exit;
    }
}

function sh_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            '',
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
