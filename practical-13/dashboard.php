<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_mysqli.php';
requireLogin();

if (($_SESSION['role'] ?? 'student') === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

$stmt = $conn->prepare('SELECT full_name, username, email, phone, course, created_at FROM users WHERE id = ? LIMIT 1');
$user = null;
if ($stmt) {
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if (!$user) {
    sh_logout();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard | StudentHub</title><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="auth.css">
</head>
<body class="portal-page">
<header><h1>🎓 StudentHub</h1><p>Your personal academic space</p></header>
<main class="portal-main">
    <div class="portal-top"><div><p class="eyebrow">STUDENT PORTAL</p><h2>Welcome, <?= sh_escape($user['full_name']) ?>!</h2><p>Here is your registered account information.</p></div>
    <form method="POST" action="logout.php"><input type="hidden" name="csrf_token" value="<?= sh_escape(sh_csrf_token()) ?>"><button class="logout-button" type="submit">Logout</button></form></div>
    <section class="profile-grid">
        <article class="profile-card"><span>👤</span><p>Full Name</p><h3><?= sh_escape($user['full_name']) ?></h3></article>
        <article class="profile-card"><span>🪪</span><p>Username</p><h3><?= sh_escape($user['username']) ?></h3></article>
        <article class="profile-card"><span>✉️</span><p>Email Address</p><h3><?= sh_escape($user['email']) ?></h3></article>
        <article class="profile-card"><span>📱</span><p>Mobile Number</p><h3><?= sh_escape($user['phone']) ?></h3></article>
        <article class="profile-card"><span>🎓</span><p>Course</p><h3><?= sh_escape($user['course']) ?></h3></article>
        <article class="profile-card"><span>🛡️</span><p>Account Role</p><h3><?= sh_escape($_SESSION['role']) ?></h3></article>
    </section>
    <p class="session-note">For security, you will be logged out after 20 minutes of inactivity.</p>
    <a class="back-link" href="index.html">← Return to StudentHub Home</a>
</main>
<footer><p>&copy; 2026 StudentHub Portal. All rights reserved.</p></footer>
</body></html>
