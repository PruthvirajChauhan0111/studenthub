<?php
declare(strict_types=1);

function respond(string $title, string $message, int $status = 200): never
{
    http_response_code($status);

    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $safeTitle . ' | StudentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main style="max-width:650px;margin:60px auto;padding:24px;">
        <section class="registration-card">
            <h1>' . $safeTitle . '</h1>
            <p>' . $safeMessage . '</p>
            <p><a href="register.html">Back to registration</a></p>
            <p><a href="students.html">Student directory</a></p>
        </section>
    </main>
</body>
</html>';

    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond('Invalid request', 'Please submit the registration form.', 405);
}

$fullName = trim((string)($_POST['fullName'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$course = trim((string)($_POST['course'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirmPassword'] ?? '');
$termsAccepted = isset($_POST['terms']);

$errors = [];

if (!preg_match("/^[\p{L}][\p{L}\s.'-]{1,59}$/u", $fullName)) {
    $errors[] = 'Enter a valid full name (2–60 characters).';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    $errors[] = 'Enter a valid email address.';
}

if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
    $errors[] = 'Enter a valid 10-digit Indian mobile number.';
}

$allowedCourses = ['BCA', 'BSc IT', 'BTech', 'MCA', 'Other'];

if (!in_array($course, $allowedCourses, true)) {
    $errors[] = 'Please select a valid course.';
}

$passwordIsStrong =
    strlen($password) >= 8 &&
    preg_match('/[a-z]/', $password) &&
    preg_match('/[A-Z]/', $password) &&
    preg_match('/[0-9]/', $password) &&
    preg_match('/[^A-Za-z0-9]/', $password);

if (!$passwordIsStrong) {
    $errors[] = 'Password must have at least 8 characters, uppercase and lowercase letters, a number, and a special character.';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Password and confirmation do not match.';
}

if (!$termsAccepted) {
    $errors[] = 'You must accept the terms and conditions.';
}

if ($errors) {
    respond('Registration not completed', implode(' ', $errors), 422);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

if ($passwordHash === false) {
    respond('Server error', 'Unable to process your registration right now.', 500);
}

/*
 * Use the same JSON file as the student directory.
 */
$storageFile = __DIR__ . DIRECTORY_SEPARATOR . 'storage-registrations.json';

if (!is_file($storageFile)) {
    $created = file_put_contents(
        $storageFile,
        "[]\n",
        LOCK_EX
    );

    if ($created === false) {
        respond('Server error', 'Unable to create the registration storage file.', 500);
    }
}

$file = fopen($storageFile, 'c+');

if ($file === false) {
    respond('Server error', 'Unable to open the registration storage file. Check file permissions.', 500);
}

if (!flock($file, LOCK_EX)) {
    fclose($file);
    respond('Server error', 'Unable to lock the registration storage file.', 500);
}

rewind($file);
$contents = stream_get_contents($file);

if ($contents === false) {
    flock($file, LOCK_UN);
    fclose($file);
    respond('Storage error', 'Unable to read the registration storage file.', 500);
}

$registrations = json_decode($contents, true);

if (
    json_last_error() !== JSON_ERROR_NONE ||
    !is_array($registrations) ||
    ($registrations !== [] && array_keys($registrations) !== range(0, count($registrations) - 1))
) {
    flock($file, LOCK_UN);
    fclose($file);

    respond(
        'Storage error',
        'The registration JSON file is invalid. Please repair it before continuing.',
        500
    );
}

foreach ($registrations as $record) {
    if (!is_array($record)) {
        flock($file, LOCK_UN);
        fclose($file);
        respond('Storage error', 'A registration record has an invalid format.', 500);
    }

    if (
        isset($record['email']) &&
        strcasecmp((string)$record['email'], $email) === 0
    ) {
        flock($file, LOCK_UN);
        fclose($file);
        respond('Email already registered', 'An account with this email already exists.', 409);
    }
}

try {
    $studentId = 'STU' . strtoupper(bin2hex(random_bytes(4)));
} catch (Throwable $exception) {
    flock($file, LOCK_UN);
    fclose($file);
    respond('Server error', 'Unable to generate a student reference.', 500);
}

$newRegistration = [
    'id' => $studentId,
    'fullName' => $fullName,
    'email' => $email,
    'phone' => $phone,
    'course' => $course,
    'passwordHash' => $passwordHash,
    'createdAt' => date(DATE_ATOM)
];

$registrations[] = $newRegistration;

$json = json_encode(
    $registrations,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);

if ($json === false) {
    flock($file, LOCK_UN);
    fclose($file);
    respond('Server error', 'Unable to encode the registration data.', 500);
}

rewind($file);
$written = fwrite($file, $json . PHP_EOL);
$flushed = fflush($file);

if ($written === false || $written !== strlen($json . PHP_EOL) || !$flushed) {
    flock($file, LOCK_UN);
    fclose($file);
    respond('Server error', 'Unable to save the registration. Check file permissions and try again.', 500);
}

if (!ftruncate($file, ftell($file))) {
    flock($file, LOCK_UN);
    fclose($file);
    respond('Server error', 'Unable to finish saving the registration.', 500);
}

flock($file, LOCK_UN);
fclose($file);

respond(
    'Registration successful',
    'Your details have been validated and saved. Your student reference is ' .
    $studentId . '.'
);

