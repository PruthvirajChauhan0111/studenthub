<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_mysqli.php';

if (!empty($_SESSION['user_id'])) {
    requireLogin();

    header('Location: ' . sh_home_for_role($_SESSION['role'] ?? 'student'));
    exit;
}

$message = '';
$messageType = 'error';

if (isset($_GET['timeout'])) {
    $message = 'Your session expired due to inactivity. Please log in again.';
} elseif (isset($_GET['inactive'])) {
    $message = 'Your account is inactive. Please contact the administrator.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? null;

    if (!sh_verify_csrf($token)) {
        $message = 'Invalid form request. Refresh the page and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $message = 'Enter a valid email address and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, full_name, username, email, password, role, status FROM users WHERE email = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password']) && ($user['status'] ?? 'active') === 'active') {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'] ?: 'student';
                $_SESSION['last_activity'] = time();
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: ' . sh_home_for_role($_SESSION['role']));
                exit;
            }
        }
        $message = 'Invalid email or password, or your account is inactive.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Secure Login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body class="auth-page">
<header>
    <h1>🎓 StudentHub</h1>
    <p>Secure access to your academic portal</p>
    <nav aria-label="Main navigation">
        <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="about.html">About</a></li>
            <li><a href="register.php">Register</a></li>
            <li><a href="login.php" aria-current="page">Login</a></li>
        </ul>
    </nav>
</header>
<main class="auth-main">
    <section class="auth-card">
        <div class="auth-heading"><span class="auth-icon">🔐</span><h2>Welcome Back</h2><p>Sign in to continue to StudentHub</p></div>
        <?php if ($message !== ''): ?>
            <div class="notice <?= $messageType === 'error' ? 'notice-error' : 'notice-success' ?>" role="alert"><?= sh_escape($message) ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= sh_escape(sh_csrf_token()) ?>">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="name@example.com" autocomplete="username" required value="<?= sh_escape($_POST['email'] ?? '') ?>">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
            <button type="submit" class="auth-button">Login to StudentHub</button>
        </form>
        <p class="auth-bottom">New to StudentHub? <a href="register.php">Create an account</a></p>
        <a class="back-link" href="index.html">← Back to Home</a>
    </section>
</main>
<footer><p>&copy; 2026 StudentHub Portal. All rights reserved.</p></footer>
</body>
</html>
