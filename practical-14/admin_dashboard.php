<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');

require_once __DIR__ . '/db_mysqli.php';

// Dashboard statistics
$totalUsers = 0;
$activeUsers = 0;
$inactiveUsers = 0;
$totalStudents = 0;
$totalEvents = 0;
$totalRegistrations = 0;

// Total users
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($result) {
    $totalUsers = (int) $result->fetch_assoc()['total'];
}

// Active users
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE status = 'active'"
);
if ($result) {
    $activeUsers = (int) $result->fetch_assoc()['total'];
}

// Inactive users
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE status = 'inactive'"
);
if ($result) {
    $inactiveUsers = (int) $result->fetch_assoc()['total'];
}

// Total students
$result = $conn->query("SELECT COUNT(*) AS total FROM students");
if ($result) {
    $totalStudents = (int) $result->fetch_assoc()['total'];
}

// Total events
$result = $conn->query("SELECT COUNT(*) AS total FROM events");
if ($result) {
    $totalEvents = (int) $result->fetch_assoc()['total'];
}

// Total registrations
$result = $conn->query("SELECT COUNT(*) AS total FROM registrations");
if ($result) {
    $totalRegistrations = (int) $result->fetch_assoc()['total'];
}

// Latest registered users
$recentUsers = $conn->query(
    "SELECT id, full_name, username, email, course, role, status, created_at
     FROM users
     ORDER BY id DESC
     LIMIT 8"
);

// Latest event registrations
$recentRegistrations = $conn->query(
    "SELECT
        r.registration_id,
        s.full_name,
        s.email,
        e.title AS event_title,
        r.registered_at
     FROM registrations r
     LEFT JOIN students s ON r.student_id = s.student_id
     LEFT JOIN events e ON r.event_id = e.id
     ORDER BY r.registration_id DESC
     LIMIT 8"
);

// Display messages
$messages = [
    'status_updated' => 'User status updated successfully.',
    'invalid_user' => 'Invalid user selected.',
    'self_change' => 'You cannot change your own account status.',
    'admin_protected' => 'Administrator accounts cannot be changed here.',
    'user_not_found' => 'User not found.',
    'update_failed' => 'Unable to update user status.'
];

$messageKey = $_GET['success'] ?? $_GET['error'] ?? '';
$message = $messages[$messageKey] ?? '';

$messageType = isset($_GET['success']) ? 'success' : 'error';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | StudentHub</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
</head>

<body class="portal-page">

<header>
    <h1>🎓 StudentHub Admin</h1>
    <p>Administration Dashboard</p>
</header>

<main class="portal-main">

    <!-- Dashboard heading -->
    <div class="portal-top">

        <div>
            <p class="eyebrow">ADMINISTRATION</p>

            <h2>
                Welcome,
                <?= sh_escape($_SESSION['full_name'] ?? 'Administrator') ?>
            </h2>

            <p>Manage and monitor StudentHub.</p>
        </div>

        <form method="POST" action="logout.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= sh_escape(sh_csrf_token()) ?>"
            >

            <button class="logout-button" type="submit">
                Logout
            </button>

        </form>

    </div>

    <!-- Notifications -->
    <?php if ($message !== ''): ?>

        <p class="<?= $messageType === 'success' ? 'success-message' : 'error-message' ?>">
            <?= sh_escape($message) ?>
        </p>

    <?php endif; ?>


    <!-- Admin navigation menu -->
    <section class="crud-shortcut">

        <h2>Admin Menu</h2>

        <p>
            Logged-in role:
            <strong><?= sh_escape($_SESSION['role'] ?? 'admin') ?></strong>
        </p>

        <div class="admin-menu">

            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>

                <a href="manage-students.php">
                    Manage Students →
                </a>

                <a href="events-admin.php">
                    Manage Events →
                </a>

                <a href="events-ajax.php">
                    Practical 13 · AJAX Event CRUD →
                </a>

                <a href="audit-logs.php">
                    View Audit Logs →
                </a>

            <?php endif; ?>

        </div>

    </section>


    <!-- Dashboard statistics -->
    <section class="stats-grid">

        <article class="stat-card">
            <p>Total Users</p>
            <strong><?= $totalUsers ?></strong>
        </article>

        <article class="stat-card">
            <p>Active Users</p>
            <strong><?= $activeUsers ?></strong>
        </article>

        <article class="stat-card">
            <p>Inactive Users</p>
            <strong><?= $inactiveUsers ?></strong>
        </article>

        <article class="stat-card">
            <p>Total Students</p>
            <strong><?= $totalStudents ?></strong>
        </article>

        <article class="stat-card">
            <p>Total Events</p>
            <strong><?= $totalEvents ?></strong>
        </article>

        <article class="stat-card">
            <p>Total Registrations</p>
            <strong><?= $totalRegistrations ?></strong>
        </article>

    </section>


    <!-- Recent users -->
    <section class="table-card">

        <h2>Recently Registered Users</h2>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($recentUsers && $recentUsers->num_rows > 0): ?>

                    <?php while ($row = $recentUsers->fetch_assoc()): ?>

                        <tr>

                            <td><?= sh_escape($row['full_name']) ?></td>

                            <td><?= sh_escape($row['username']) ?></td>

                            <td><?= sh_escape($row['email']) ?></td>

                            <td><?= sh_escape($row['course']) ?></td>

                            <td><?= sh_escape($row['role']) ?></td>

                            <td><?= sh_escape($row['status']) ?></td>

                            <td><?= sh_escape($row['created_at']) ?></td>

                            <td>

                                <?php if (
                                    $row['role'] === 'student'
                                    && (int) $row['id'] !== (int) $_SESSION['user_id']
                                ): ?>

                                    <form method="POST" action="admin-actions.php">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= sh_escape(sh_csrf_token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int) $row['id'] ?>"
                                        >

                                        <button type="submit">
                                            <?= $row['status'] === 'active'
                                                ? 'Deactivate'
                                                : 'Activate' ?>
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span>Protected</span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8">No users registered yet.</td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- Recent event registrations -->
    <section class="table-card">

        <h2>Latest Event Registrations</h2>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Registration ID</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Event</th>
                        <th>Registered At</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (
                    $recentRegistrations
                    && $recentRegistrations->num_rows > 0
                ): ?>

                    <?php while ($row = $recentRegistrations->fetch_assoc()): ?>

                        <tr>

                            <td><?= sh_escape($row['registration_id']) ?></td>

                            <td><?= sh_escape($row['full_name'] ?? 'Unknown') ?></td>

                            <td><?= sh_escape($row['email'] ?? 'Unknown') ?></td>

                            <td><?= sh_escape($row['event_title'] ?? 'Unknown') ?></td>

                            <td><?= sh_escape($row['registered_at']) ?></td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">No event registrations found.</td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <p class="session-note">
        Admin pages are restricted to accounts with the admin role.
        Sessions expire after 20 minutes of inactivity.
    </p>

    <a class="back-link" href="index.html">
        ← View StudentHub Website
    </a>

</main>

<footer>
    <p>&copy; 2026 StudentHub Portal. All rights reserved.</p>
</footer>

</body>
</html>
