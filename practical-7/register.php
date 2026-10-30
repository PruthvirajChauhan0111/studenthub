<?php
session_start();

$dataFile = __DIR__ . "/registrations.json";

if (!isset($_SESSION["p7_token"])) {
    $_SESSION["p7_token"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "";
$name = "";
$email = "";
$mobile = "";
$course = "";

function escapeOutput($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $token = $_POST["csrf_token"] ?? "";

    $errors = [];

    if (!is_string($token) ||
        !hash_equals($_SESSION["p7_token"], $token)) {
        $errors[] = "Invalid form submission. Please refresh the page.";
    }

    if (!preg_match("/^[a-zA-Z ]{2,60}$/", $name)) {
        $errors[] = "Enter a valid name containing 2 to 60 letters.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    if (!preg_match("/^[0-9]{10}$/", $mobile)) {
        $errors[] = "Mobile number must contain exactly 10 digits.";
    }

    $allowedCourses = ["BTech", "BSc IT", "MCA", "BCA", "MBA"];

    if (!in_array($course, $allowedCourses, true)) {
        $errors[] = "Please select a valid course.";
    }

    if (empty($errors)) {
        if (!file_exists($dataFile)) {
            file_put_contents($dataFile, "[]", LOCK_EX);
        }

        $handle = fopen($dataFile, "c+");

        if ($handle === false) {
            $message = "Unable to open the registration storage file.";
            $messageType = "error";
        } elseif (!flock($handle, LOCK_EX)) {
            fclose($handle);
            $message = "Unable to lock the registration storage file.";
            $messageType = "error";
        } else {
            $contents = stream_get_contents($handle);
            $registrations = json_decode($contents ?: "[]", true);

            if (!is_array($registrations)) {
                $registrations = [];
            }

            $registrations[] = [
                "id" => uniqid("STU"),
                "name" => $name,
                "email" => $email,
                "mobile" => $mobile,
                "course" => $course,
                "submitted_at" => date("Y-m-d H:i:s")
            ];

            $json = json_encode(
                $registrations,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );

            rewind($handle);
            ftruncate($handle, 0);
            $saved = fwrite($handle, $json);

            fflush($handle);
            flock($handle, LOCK_UN);
            fclose($handle);

            if ($saved !== false) {
                $message = "Registration submitted successfully!";
                $messageType = "success";

                $name = "";
                $email = "";
                $mobile = "";
                $course = "";

                $_SESSION["p7_token"] = bin2hex(random_bytes(32));
            } else {
                $message = "Unable to save registration.";
                $messageType = "error";
            }
        }
    } else {
        $message = implode(" ", $errors);
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Student Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>StudentHub</h1>
    <p>Student Registration Portal</p>
</header>

<nav class="main-navigation">
    <a href="index.html">Home</a>
    <a href="register.php">Registration</a>
    <a href="submissions.php">View Registrations</a>
</nav>

<main class="registration-container">
    <section class="registration-card">
        <h2>Student Registration</h2>
        <p>Enter your details to register.</p>

        <?php if ($message !== ""): ?>
            <div class="form-message <?php echo escapeOutput($messageType); ?>">
                <?php echo escapeOutput($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <input type="hidden" name="csrf_token"
                value="<?php echo escapeOutput($_SESSION["p7_token"]); ?>">

            <label for="name">Full Name</label>
            <input type="text" id="name" name="name"
                value="<?php echo escapeOutput($name); ?>"
                placeholder="Enter your full name" required>

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                value="<?php echo escapeOutput($email); ?>"
                placeholder="Enter your email" required>

            <label for="mobile">Mobile Number</label>
            <input type="text" id="mobile" name="mobile"
                value="<?php echo escapeOutput($mobile); ?>"
                placeholder="Enter 10-digit mobile number"
                maxlength="10" required>

            <label for="course">Select Course</label>
            <select id="course" name="course" required>
                <option value="">Choose your course</option>
                <?php
                $courses = ["BTech", "BSc IT", "MCA", "BCA", "MBA"];

                foreach ($courses as $item):
                ?>
                    <option value="<?php echo escapeOutput($item); ?>"
                        <?php echo $course === $item ? "selected" : ""; ?>>
                        <?php echo escapeOutput($item); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Submit Registration</button>
        </form>

        <p>
            <a href="submissions.php">View Submitted Registrations</a>
        </p>
    </section>
</main>

<footer>
    <p>&copy; <?php echo date("Y"); ?> StudentHub Portal</p>
</footer>

</body>
</html>
