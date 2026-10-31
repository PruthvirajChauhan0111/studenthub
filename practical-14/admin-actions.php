<?php
require_once __DIR__ . '/auth.php';

requireRole('admin');

require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/audit.php';

// Only POST requests are allowed.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_dashboard.php');
    exit;
}

// Verify CSRF token.
if (!sh_verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid security token. Please go back and try again.');
}

// Get target user ID.
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$userId || $userId <= 0) {
    header('Location: admin_dashboard.php?error=invalid_user');
    exit;
}

// Prevent changing your own account.
if ($userId === (int) $_SESSION['user_id']) {
    header('Location: admin_dashboard.php?error=self_change');
    exit;
}

// Find the target user.
$stmt = $conn->prepare(
    "SELECT full_name, role, status FROM users WHERE id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    header('Location: admin_dashboard.php?error=user_not_found');
    exit;
}

// Only student accounts can be changed.
if ($user['role'] !== 'student') {
    header('Location: admin_dashboard.php?error=admin_protected');
    exit;
}

// Determine the new status.
$newStatus = $user['status'] === 'active'
    ? 'inactive'
    : 'active';

// Update the account status.
$update = $conn->prepare(
    "UPDATE users SET status = ? WHERE id = ? AND role = 'student'"
);

$update->bind_param("si", $newStatus, $userId);

if (!$update->execute()) {
    error_log($update->error);
    $update->close();

    header('Location: admin_dashboard.php?error=update_failed');
    exit;
}

$update->close();

// Record the action in audit_logs.
$action = $newStatus === 'active'
    ? 'USER_ACTIVATED'
    : 'USER_DEACTIVATED';

$details = "Account status changed for "
    . $user['full_name']
    . " (User ID: "
    . $userId
    . ") to "
    . $newStatus;

logAdminAction($conn, $action, $details);

// Redirect to dashboard.
header('Location: admin_dashboard.php?success=status_updated');
exit;
