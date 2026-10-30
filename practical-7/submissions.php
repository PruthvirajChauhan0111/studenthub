<?php
$dataFile = __DIR__ . "/registrations.json";
$registrations = [];

function escapeOutput($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

if (file_exists($dataFile)) {
    $handle = fopen($dataFile, "r");

    if ($handle !== false) {
        if (flock($handle, LOCK_SH)) {
            $contents = stream_get_contents($handle);
            $decoded = json_decode($contents ?: "[]", true);

            if (is_array($decoded)) {
                $registrations = $decoded;
            }

            flock($handle, LOCK_UN);
        }

        fclose($handle);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Registration Records</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>StudentHub</h1>
    <p>Submitted Student Registrations</p>
</header>

<nav class="main-navigation">
    <a href="index.html">Home</a>
    <a href="register.php">Registration</a>
    <a href="submissions.php">View Registrations</a>
</nav>

<main class="registration-container">
    <section class="registration-card">
        <h2>Registration Records</h2>

        <?php if (empty($registrations)): ?>
            <p>No registrations have been submitted yet.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Course</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($registrations as $student): ?>
                            <tr>
                                <td><?php echo escapeOutput($student["id"] ?? ""); ?></td>
                                <td><?php echo escapeOutput($student["name"] ?? ""); ?></td>
                                <td><?php echo escapeOutput($student["email"] ?? ""); ?></td>
                                <td><?php echo escapeOutput($student["mobile"] ?? ""); ?></td>
                                <td><?php echo escapeOutput($student["course"] ?? ""); ?></td>
                                <td><?php echo escapeOutput($student["submitted_at"] ?? ""); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p>
            <a href="register.php">Register Another Student</a>
        </p>
    </section>
</main>

<footer>
    <p>&copy; <?php echo date("Y"); ?> StudentHub Portal</p>
</footer>

</body>
</html>
