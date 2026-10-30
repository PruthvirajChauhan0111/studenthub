<?php
declare(strict_types=1);

$clientAddress = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($clientAddress, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Access denied. This practical viewer is for local development only.');
}

$file = __DIR__ . '/storage-registrations.json';
$registrations = [];

if (is_file($file)) {
    $contents = file_get_contents($file);

    if ($contents === false) {
        http_response_code(500);
        exit('Unable to read the registration storage file.');
    }

    $decoded = json_decode($contents, true);

    if (
        json_last_error() !== JSON_ERROR_NONE ||
        !is_array($decoded) ||
        ($decoded !== [] && array_keys($decoded) !== range(0, count($decoded) - 1))
    ) {
        http_response_code(500);
        exit('The registration storage file contains invalid JSON.');
    }

    $registrations = $decoded;
}

function escapeHtml(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Registrations | StudentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="student-directory">
        <section class="directory-heading">
            <h1>Saved Registrations</h1>
            <p>Local Practical 7 demonstration records</p>
        </section>

        <section class="directory-panel">
            <?php if (count($registrations) === 0): ?>
                <p>No registrations have been saved yet.</p>
            <?php else: ?>
                <div class="student-table-wrapper">
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Registered At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $record): ?>
                                <?php if (!is_array($record)) continue; ?>
                                <tr>
                                    <td><?= escapeHtml($record['id'] ?? '') ?></td>
                                    <td><?= escapeHtml($record['fullName'] ?? '') ?></td>
                                    <td><?= escapeHtml($record['email'] ?? '') ?></td>
                                    <td><?= escapeHtml($record['course'] ?? '') ?></td>
                                    <td><?= escapeHtml($record['createdAt'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <p><a href="register.html">Back to registration</a></p>
        </section>
    </main>
</body>
</html>

