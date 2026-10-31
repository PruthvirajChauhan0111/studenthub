
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db_pdo.php";

if (empty($_SESSION['event_csrf'])) {
    $_SESSION['event_csrf'] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['event_csrf'], $csrfToken)) {
        $message = "Invalid request. Please refresh the page and try again.";
        $messageType = "error";
    } else {
        $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (!$eventId || $fullName === "" || $email === "") {
            $message = "Please fill in all required fields.";
            $messageType = "error";
        } elseif (strlen($fullName) > 100) {
            $message = "Your name must not exceed 100 characters.";
            $messageType = "error";
        } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $messageType = "error";
        } else {
            $checkEvent = $pdo->prepare("
                SELECT event_id
                FROM practical12_events
                WHERE event_id = ? AND status = 'open'
            ");

            $checkEvent->execute([$eventId]);

            if (!$checkEvent->fetch()) {
                $message = "This event is not available for registration.";
                $messageType = "error";
            } else {
                try {
                    $insert = $pdo->prepare("
                        INSERT INTO event_registrations
                        (event_id, student_name, student_email)
                        VALUES (?, ?, ?)
                    ");

                    $insert->execute([$eventId, $fullName, $email]);

                    $message = "Registration successful! You are registered for this event.";
                    $messageType = "success";

                } catch (PDOException $e) {
                    if ($e->getCode() === "23000") {
                        $message = "You have already registered for this event with this email address.";
                        $messageType = "error";
                    } else {
                        error_log($e->getMessage());
                        $message = "Registration could not be completed. Please try again later.";
                        $messageType = "error";
                    }
                }
            }
        }
    }
}

$stmt = $pdo->query("
    SELECT event_id, title, description, category, event_date,
           location, status, poster
    FROM practical12_events
    WHERE status = 'open'
    ORDER BY event_date ASC
");

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$isPost = $_SERVER["REQUEST_METHOD"] === "POST";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Campus Events</title>
    <link rel="stylesheet" href="events-public.css">
</head>
<body>

<header class="header">
    <div class="brand">🎓 StudentHub</div>

    <nav>
        <a href="index.html">Home</a>
        <a href="about.html">About</a>
        <a href="courses.html">Courses</a>
        <a href="events.php" class="active">Events</a>
        <a href="contact.html">Contact</a>
        <a href="login.php" class="login-btn">Login</a>
    </nav>
</header>

<section class="hero">
    <span class="eyebrow">CAMPUS LIFE</span>
    <h1>Discover What's Happening</h1>
    <p>Explore upcoming events, workshops and activities at StudentHub.</p>
</section>

<main class="container">

    <div class="section-heading">
        <div>
            <span class="eyebrow">EXPLORE & PARTICIPATE</span>
            <h2>Upcoming Events</h2>
            <p>Find an event that interests you.</p>
        </div>

        <span class="event-count">
            <?php echo count($events); ?> Events Available
        </span>
    </div>

    <?php if ($message !== ""): ?>
        <div class="message <?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (count($events) > 0): ?>

        <div class="event-grid">

            <?php foreach ($events as $event): ?>

                <article class="event-card">

                    <?php if (!empty($event['poster'])): ?>
                        <img
                            class="event-image"
                            src="uploads/<?php echo rawurlencode(basename($event['poster'])); ?>"
                            alt="<?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    <?php else: ?>
                        <div class="event-placeholder">🎉</div>
                    <?php endif; ?>

                    <div class="event-content">

                        <span class="category">
                            <?php echo htmlspecialchars($event['category'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <h3>
                            <?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </h3>

                        <p class="description">
                            <?php echo nl2br(htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8')); ?>
                        </p>

                        <div class="event-info">
                            <p>
                                📅 <?php echo date('d M Y, h:i A', strtotime($event['event_date'])); ?>
                            </p>

                            <p>
                                📍 <?php echo htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>

                        <span class="status">● Open for Participation</span>

                        <button
                            type="button"
                            class="register-button"
                            onclick="selectEvent(<?php echo (int)$event['event_id']; ?>)"
                        >
                            Register Now →
                        </button>

                    </div>
                </article>

            <?php endforeach; ?>

        </div>

        <section
    class="registration-section"
    id="registration"
    <?php echo !$isPost ? 'hidden' : ''; ?>
>

            <div class="registration-heading">
                <span class="eyebrow">JOIN THE EVENT</span>
                <h2>Event Registration</h2>
                <p>Fill in your details to reserve your participation.</p>
            </div>

            <form method="POST" action="events.php#registration" class="registration-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars($_SESSION['event_csrf'], ENT_QUOTES, 'UTF-8'); ?>"
                >

                <div class="form-group">
                    <label for="event_id">Select Event</label>

                    <select name="event_id" id="event_id" required>
                        <option value="">Choose an event</option>

                        <?php foreach ($events as $event): ?>
                            <option
                                value="<?php echo (int)$event['event_id']; ?>"
                                <?php echo (isset($_POST['event_id']) && (int)$_POST['event_id'] === (int)$event['event_id']) ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name</label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="100"
                        placeholder="Enter your full name"
                        value="<?php echo htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="150"
                        placeholder="Enter your email address"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                </div>

                <button type="submit" class="submit-button">
                    Submit Registration
                </button>

            </form>

        </section>

    <?php else: ?>

        <div class="empty-state">
            <div>📅</div>
            <h3>No Upcoming Events</h3>
            <p>There are currently no open events. Please check again later.</p>
        </div>

    <?php endif; ?>

</main>

<footer>
    <h3>🎓 StudentHub</h3>
    <p>Your Academic Journey, All in One Place.</p>
    <p>© 2026 StudentHub Portal. All rights reserved.</p>
</footer>

<script>
function selectEvent(eventId) {
    const eventSelect = document.getElementById("event_id");
    const registrationSection = document.getElementById("registration");

    if (!eventSelect || !registrationSection) {
        alert("Registration form could not be found.");
        return;
    }

    eventSelect.value = eventId;

    registrationSection.hidden = false;

    registrationSection.scrollIntoView({
        behavior: "smooth",
        block: "center"
    });
}
</script>

</body>
</html>
