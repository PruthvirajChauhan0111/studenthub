<?php
session_start();

require_once __DIR__ . '/db_mysqli.php';

if (empty($_SESSION['registration_token'])) {
    $_SESSION['registration_token'] = bin2hex(random_bytes(32));
}

function escape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$success = '';
$fullName = '';
$username = '';
$email = '';
$phone = '';
$course = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['fullName'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $termsAccepted = isset($_POST['terms']);

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['registration_token'], $_POST['csrf_token'])
    ) {
        $errors[] = 'Invalid form request. Please refresh the page and try again.';
    }

    if (!preg_match("/^[\p{L}][\p{L}\s.'-]{1,59}$/u", $fullName)) {
        $errors[] = 'Enter a valid full name (2–60 characters).';
    }

    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $errors[] = 'Username must contain 3–50 letters, numbers or underscores.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Enter a valid email address.';
    }

    if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $errors[] = 'Enter a valid 10-digit Indian mobile number.';
    }

    $allowedCourses = ['BCA', 'BSc IT', 'BTech', 'MCA', 'Other'];

    if (!in_array($course, $allowedCourses, true)) {
        $errors[] = 'Please select a valid course.';
    }

    $passwordStrong =
        strlen($password) >= 8 &&
        preg_match('/[a-z]/', $password) &&
        preg_match('/[A-Z]/', $password) &&
        preg_match('/[0-9]/', $password) &&
        preg_match('/[^A-Za-z0-9]/', $password);

    if (!$passwordStrong) {
        $errors[] = 'Password must have at least 8 characters, uppercase and lowercase letters, a number and a special character.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password and confirm password do not match.';
    }

    if (!$termsAccepted) {
        $errors[] = 'You must accept the terms and conditions.';
    }

    if (empty($errors)) {
        try {
            $check = $conn->prepare(
                "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1"
            );

            if (!$check) {
                throw new Exception('Unable to prepare duplicate check.');
            }

            $check->bind_param("ss", $username, $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $errors[] = 'This username or email address is already registered.';
            }

            $check->close();

            if (empty($errors)) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                if ($passwordHash === false) {
                    throw new Exception('Password processing failed.');
                }

                $insert = $conn->prepare(
                    "INSERT INTO users
                    (full_name, username, email, phone, course, password)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                if (!$insert) {
                    throw new Exception('Unable to prepare registration.');
                }

                $insert->bind_param(
                    "ssssss",
                    $fullName,
                    $username,
                    $email,
                    $phone,
                    $course,
                    $passwordHash
                );

                if ($insert->execute()) {
                    $success = 'Your StudentHub account has been created successfully! You can now log in.';
                    $fullName = '';
                    $username = '';
                    $email = '';
                    $phone = '';
                    $course = '';

                    $_SESSION['registration_token'] = bin2hex(random_bytes(32));
                } else {
                    if ($insert->errno === 1062) {
                        $errors[] = 'This username or email address is already registered.';
                    } else {
                        throw new Exception('Registration could not be saved.');
                    }
                }

                $insert->close();
            }

        } catch (Throwable $exception) {
            error_log($exception->getMessage());

            if (empty($errors)) {
                $errors[] = 'A server error occurred. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Secure StudentHub student registration">
    <title>StudentHub | Secure Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>🎓 StudentHub</h1>
    <p>Create Your Student Account</p>

    <nav aria-label="Main navigation">
        <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="about.html">About</a></li>
            <li><a href="students.html">Students</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php" aria-current="page">Register</a></li>
        </ul>
    </nav>
</header>

<main>
    <h2>Student Registration</h2>

    <section class="registration-section">
        <div class="registration-card">

            <h1>Create Your StudentHub Account</h1>
            <p class="form-description">
                Enter your details to register as a student.
            </p>

            <?php if (!empty($errors)): ?>
                <div class="form-message error-message" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= escape($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="form-message success-message" role="status">
                    <p><?= escape($success) ?></p>
                    <p><a href="login.php">Go to Login</a></p>
                </div>
            <?php endif; ?>

            <form id="registrationForm" action="register.php" method="POST" novalidate>

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escape($_SESSION['registration_token']) ?>"
                >

                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input
                        type="text"
                        id="fullName"
                        name="fullName"
                        placeholder="Enter your full name"
                        autocomplete="name"
                        value="<?= escape($fullName) ?>"
                        required
                    >
                    <small class="field-error" id="fullNameError"></small>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Choose a username"
                        autocomplete="username"
                        value="<?= escape($username) ?>"
                        required
                    >
                    <small class="field-hint">Use 3–50 letters, numbers or underscores.</small>
                    <small class="field-error" id="usernameError"></small>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="student@example.com"
                        autocomplete="email"
                        value="<?= escape($email) ?>"
                        required
                    >
                    <small class="field-error" id="emailError"></small>
                </div>

                <div class="form-group">
                    <label for="phone">Mobile Number</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        placeholder="Enter 10-digit mobile number"
                        autocomplete="tel"
                        value="<?= escape($phone) ?>"
                        required
                    >
                    <small class="field-error" id="phoneError"></small>
                </div>

                <div class="form-group">
                    <label for="course">Select Course</label>
                    <select id="course" name="course" required>
                        <option value="">Choose your course</option>
                        <?php
                        $courses = ['BCA', 'BSc IT', 'BTech', 'MCA', 'Other'];
                        foreach ($courses as $item):
                        ?>
                            <option
                                value="<?= escape($item) ?>"
                                <?= $course === $item ? 'selected' : '' ?>
                            >
                                <?= escape($item) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="field-error" id="courseError"></small>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a strong password"
                        autocomplete="new-password"
                        required
                    >
                    <small class="field-hint">
                        At least 8 characters, with uppercase, lowercase,
                        a number and a special character.
                    </small>
                    <small class="field-error" id="passwordError"></small>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input
                        type="password"
                        id="confirmPassword"
                        name="confirmPassword"
                        placeholder="Re-enter your password"
                        autocomplete="new-password"
                        required
                    >
                    <small class="field-error" id="confirmPasswordError"></small>
                </div>

                <div class="terms-group">
                    <label>
                        <input type="checkbox" id="terms" name="terms" required>
                        I agree to the terms and conditions.
                    </label>
                    <small class="field-error" id="termsError"></small>
                </div>

                <button type="submit" class="btn register-submit">
                    Create Account
                </button>

                <p id="formMessage" class="form-message"
                   role="status" aria-live="polite"></p>

            </form>

            <p class="login-link">
                Already have an account?
                <a href="login.php">Login here</a>
            </p>

        </div>
    </section>
</main>

<footer>
    <p>&copy; 2026 StudentHub Portal. All rights reserved.</p>
</footer>

<script src="script.js" defer></script>
<script src="validation.js" defer></script>

</body>
</html>
