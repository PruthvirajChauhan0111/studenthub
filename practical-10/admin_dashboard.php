<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_mysqli.php';
requireRole('admin');

$totalUsers = 0;
$activeUsers = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($result) { $totalUsers = (int)$result->fetch_assoc()['total']; }
$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE status = 'active'");
if ($result) { $activeUsers = (int)$result->fetch_assoc()['total']; }
$recentUsers = $conn->query('SELECT full_name, username, email, course, role, created_at FROM users ORDER BY id DESC LIMIT 8');
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Admin Dashboard | StudentHub</title><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="auth.css"></head>
<body class="portal-page"><header><h1>🎓 StudentHub Admin</h1><p>Administration overview</p></header>
<main class="portal-main">
<div class="portal-top"><div><p class="eyebrow">ADMINISTRATION</p><h2>Welcome, <?= sh_escape($_SESSION['full_name'] ?? 'Administrator') ?></h2><p>Manage and monitor StudentHub accounts.</p></div><form method="POST" action="logout.php"><input type="hidden" name="csrf_token" value="<?= sh_escape(sh_csrf_token()) ?>"><button class="logout-button" type="submit">Logout</button></form></div>
<section class="stats-grid"><article class="stat-card"><p>Total registered users</p><strong><?= $totalUsers ?></strong></article><article class="stat-card"><p>Active accounts</p><strong><?= $activeUsers ?></strong></article></section>
<section class="table-card"><h2>Recently Registered Users</h2><div class="table-wrap"><table><thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Course</th><th>Role</th><th>Created</th></tr></thead><tbody>
<?php if ($recentUsers && $recentUsers->num_rows > 0): while ($row = $recentUsers->fetch_assoc()): ?>
<tr><td><?= sh_escape($row['full_name']) ?></td><td><?= sh_escape($row['username']) ?></td><td><?= sh_escape($row['email']) ?></td><td><?= sh_escape($row['course']) ?></td><td><?= sh_escape($row['role']) ?></td><td><?= sh_escape($row['created_at']) ?></td></tr>
<?php endwhile; else: ?><tr><td colspan="6">No users registered yet.</td></tr><?php endif; ?>
</tbody></table></div></section>
<p class="session-note">Admin pages are restricted to accounts with the admin role. Sessions expire after 20 minutes of inactivity.</p>
<a class="back-link" href="index.html">← View StudentHub Website</a>
</main><footer><p>&copy; 2026 StudentHub Portal. All rights reserved.</p></footer></body></html>
