
<?php
require_once __DIR__ . "/db.php";

try {
    $db = getDatabase();

    echo "Database connection successful!";

    $result = $db->query("SELECT COUNT(*) AS total FROM events");
    $row = $result->fetch_assoc();

    echo "<br>Total events: " . $row["total"];

    $db->close();
} catch (Throwable $e) {
    http_response_code(500);
    echo "Database error: " . htmlspecialchars($e->getMessage());
}
