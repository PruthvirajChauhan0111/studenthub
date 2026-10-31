<?php
require_once __DIR__ . '/db_mysqli.php';

function logAdminAction(
    mysqli $conn,
    string $action,
    string $details = ''
): bool {
    $userId = isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : null;

    $stmt = $conn->prepare(
        "INSERT INTO audit_logs (user_id, action, details)
         VALUES (?, ?, ?)"
    );

    if (!$stmt) {
        error_log($conn->error);
        return false;
    }

    $stmt->bind_param("iss", $userId, $action, $details);

    $success = $stmt->execute();

    if (!$success) {
        error_log($stmt->error);
    }

    $stmt->close();

    return $success;
}
