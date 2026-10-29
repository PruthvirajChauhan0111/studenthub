<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/db.php';

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = getDatabase();

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? 'events';

    // 1. Load all events
    if ($method === 'GET' && $action === 'events') {
        $result = $db->query(
            "SELECT id, title, event_date, venue, description
             FROM events
             ORDER BY event_date ASC, id DESC"
        );

        $events = [];

        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        respond([
            'success' => true,
            'events' => $events
        ]);
    }

    // 2. Register a student for an event
    if ($method === 'POST' && $action === 'register') {
        $eventId = filter_var(
            $_POST['event_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $name = trim($_POST['student_name'] ?? '');
        $email = trim($_POST['student_email'] ?? '');

        if (!$eventId || $eventId < 1 || $name === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond([
                'success' => false,
                'message' => 'Enter a valid event, student name and email.'
            ], 400);
        }

        // Check whether the event exists
        $check = $db->prepare("SELECT id FROM events WHERE id = ?");
        $check->bind_param('i', $eventId);
        $check->execute();

        if (!$check->get_result()->fetch_assoc()) {
            respond([
                'success' => false,
                'message' => 'The selected event does not exist.'
            ], 404);
        }

        // Save the registration
        $stmt = $db->prepare(
            "INSERT INTO event_registrations
             (event_id, student_name, student_email)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param('iss', $eventId, $name, $email);
        $stmt->execute();

        respond([
            'success' => true,
            'message' => 'Event registration successful!'
        ], 201);
    }

    // 3. Display all event registrations
    if ($method === 'GET' && $action === 'registrations') {
        $result = $db->query(
            "SELECT
                er.id,
                er.event_id,
                er.student_name,
                er.student_email,
                er.registered_at,
                e.title AS event_title
             FROM event_registrations er
             INNER JOIN events e ON er.event_id = e.id
             ORDER BY er.registered_at DESC"
        );

        $registrations = [];

        while ($row = $result->fetch_assoc()) {
            $registrations[] = $row;
        }

        respond([
            'success' => true,
            'registrations' => $registrations
        ]);
    }

    // 4. Add a new event
    if ($method === 'POST' && $action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $description = trim($_POST['description'] ?? '');

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $eventDate);
        $dateErrors = DateTimeImmutable::getLastErrors();

        if (
            $title === '' ||
            $venue === '' ||
            !$date ||
            ($dateErrors !== false &&
                ($dateErrors['warning_count'] > 0 ||
                 $dateErrors['error_count'] > 0)) ||
            $date->format('Y-m-d') !== $eventDate
        ) {
            respond([
                'success' => false,
                'message' => 'Enter a valid title, date and venue.'
            ], 400);
        }

        $stmt = $db->prepare(
            "INSERT INTO events (title, event_date, venue, description)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param('ssss', $title, $eventDate, $venue, $description);
        $stmt->execute();

        respond([
            'success' => true,
            'message' => 'Event added successfully!',
            'event_id' => $db->insert_id
        ], 201);
    }

    respond([
        'success' => false,
        'message' => 'Unknown action or unsupported HTTP method.'
    ], 400);

} catch (mysqli_sql_exception $e) {
    if (isset($db)) {
        $db->close();
    }

    // MySQL error 1062 means a duplicate value violated a unique index.
    if ($e->getCode() === 1062) {
        respond([
            'success' => false,
            'message' => 'This student email is already registered for this event.'
        ], 409);
    }

   respond([
    'success' => false,
    'message' => $e->getMessage()
], 500);
}
