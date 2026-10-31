<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');

require_once __DIR__ . '/db_mysqli.php';

// Fetch the latest 100 audit records.
$sql = "
    SELECT
        audit_logs.id,
        audit_logs.user_id,
        audit_logs.action,
        audit_logs.details,
        audit_logs.created_at,
        users.full_name AS admin_name
    FROM audit_logs
    LEFT JOIN users ON audit_logs.user_id = users.id
    ORDER BY audit_logs.id DESC
    LIMIT 100
";

$result = $conn->query($sql);

if (!$result) {
    error_log($conn->error);
    exit('Unable to load audit logs.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Audit Logs | StudentHub</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
</head>

<body class="portal-page">

<header>
    <h1>🎓 StudentHub Admin</h1>
    <p>Administrator Activity History</p>
</header>

<main class="portal-main">

    <div class="portal-top">
        <div>
            <p class="eyebrow">ADMINISTRATION</p>
            <h2>Audit Logs</h2>
            <p>View the latest administrator activities.</p>
        </div>

        <a class="back-link" href="admin_dashboard.php">
            ← Back to Dashboard
        </a>
    </div>

    <section class="table-card">

        <h2>Recent Admin Activities</h2>

        <div class="table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>
                            <td><?= sh_escape($row['id']) ?></td>

                            <td>
                                <?= sh_escape(
                                    $row['admin_name'] ?? 'Deleted User'
                                ) ?>
                            </td>

                            <td>
                                <?= sh_escape($row['action']) ?>
                            </td>

                            <td>
                                <?= sh_escape($row['details']) ?>
                            </td>

                            <td>
                                <?= sh_escape($row['created_at']) ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">
                            No administrator activities recorded yet.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </section>

</main>

<footer>
    <p>&copy; 2026 StudentHub Portal. All rights reserved.</p>
</footer>

</body>
</html>
