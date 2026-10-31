<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/db_pdo.php';

/* Send JSON response */
function api_reply(
    bool $ok,
    string $message,
    array $extra = [],
    int $code = 200
): void {
    http_response_code($code);

    echo json_encode(
        array_merge([
            'success' => $ok,
            'message' => $message
        ], $extra),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

/* Read JSON request body */
function api_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        api_reply(false, 'Invalid JSON request body.', [], 400);
    }

    return $data;
}

/* Validate event information */
function api_validate(array $data): array {
    $event = [
        'title' => trim((string)($data['title'] ?? '')),
        'description' => trim((string)($data['description'] ?? '')),
        'category' => trim((string)($data['category'] ?? '')),
        'event_date' => trim((string)($data['event_date'] ?? '')),
        'location' => trim((string)($data['location'] ?? '')),
        'status' => trim((string)($data['status'] ?? 'open'))
    ];

    $errors = [];

    if (
        $event['title'] === '' ||
        mb_strlen($event['title']) > 150
    ) {
        $errors[] = 'Enter an event title (maximum 150 characters).';
    }

    if (
        $event['description'] === '' ||
        mb_strlen($event['description']) > 5000
    ) {
        $errors[] = 'Enter a description (maximum 5000 characters).';
    }

    if (
        $event['category'] === '' ||
        mb_strlen($event['category']) > 80
    ) {
        $errors[] = 'Enter a category (maximum 80 characters).';
    }

    if (
        $event['location'] === '' ||
        mb_strlen($event['location']) > 180
    ) {
        $errors[] = 'Enter a location (maximum 180 characters).';
    }

    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d\TH:i',
        $event['event_date']
    );

    $dateErrors = DateTimeImmutable::getLastErrors();

    if (
        !$date ||
        ($dateErrors !== false &&
        ($dateErrors['warning_count'] > 0 ||
         $dateErrors['error_count'] > 0)) ||
        $date->format('Y-m-d\TH:i') !== $event['event_date']
    ) {
        $errors[] = 'Select a valid event date and time.';
    }

    if (!in_array($event['status'], ['open', 'closed'], true)) {
        $errors[] = 'Select a valid event status.';
    }

    return [$event, $errors, $date];
}

/* Check login */
if (empty($_SESSION['user_id'])) {
    api_reply(false, 'Please log in to continue.', [], 401);
}

/* Check session timeout */
$now = time();
$last = (int)($_SESSION['last_activity'] ?? $now);

if ($now - $last > SH_SESSION_TIMEOUT) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'] ?? '',
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    api_reply(false, 'Your session has expired. Please log in again.', [], 401);
}

$_SESSION['last_activity'] = $now;

/* Only admin can access this API */
if (($_SESSION['role'] ?? '') !== 'admin') {
    api_reply(false, 'Administrator access is required.', [], 403);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {

    /* GET: Fetch events */
    if ($method === 'GET') {

        $search = trim((string)($_GET['search'] ?? ''));
        $category = trim((string)($_GET['category'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(title LIKE ? OR description LIKE ? OR location LIKE ?)';

            $like = '%' . $search . '%';

            array_push($params, $like, $like, $like);
        }

        if ($category !== '') {
            $where[] = 'category = ?';
            $params[] = $category;
        }

        if (in_array($status, ['open', 'closed'], true)) {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        $sql = '
            SELECT
                event_id,
                title,
                description,
                category,
                event_date,
                location,
                status,
                poster,
                created_at,
                updated_at
            FROM practical13_events
        ';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY event_date DESC, event_id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as &$item) {
            $item['event_id'] = (int)$item['event_id'];

            $item['poster_url'] = !empty($item['poster'])
                ? 'uploads/' . rawurlencode($item['poster'])
                : null;
        }

        unset($item);

        $categories = $pdo->query(
            'SELECT DISTINCT category
             FROM practical13_events
             ORDER BY category'
        )->fetchAll(PDO::FETCH_COLUMN);

        api_reply(true, 'Events loaded successfully.', [
            'events' => $events,
            'categories' => $categories,
            'count' => count($events)
        ]);
    }

    /* Allow only POST, PUT and DELETE for other operations */
    if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        header('Allow: GET, POST, PUT, DELETE');

        api_reply(false, 'Method not allowed.', [], 405);
    }

    /* CSRF protection */
    if (!sh_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        api_reply(
            false,
            'Security token is invalid. Refresh the page and try again.',
            [],
            403
        );
    }

    $data = api_body();

    /* Validate POST and PUT data */
    if ($method === 'POST' || $method === 'PUT') {
        [$event, $errors, $date] = api_validate($data);

        if (!empty($errors)) {
            api_reply(
                false,
                implode(' ', $errors),
                ['errors' => $errors],
                422
            );
        }
    }

    /* POST: Add new event */
    if ($method === 'POST') {

        $stmt = $pdo->prepare(
            'INSERT INTO practical13_events
            (title, description, category, event_date, location, status)
            VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $event['title'],
            $event['description'],
            $event['category'],
            $date->format('Y-m-d H:i:s'),
            $event['location'],
            $event['status']
        ]);

        api_reply(
            true,
            'Event added successfully.',
            ['event_id' => (int)$pdo->lastInsertId()],
            201
        );
    }

    /* Validate event ID for PUT and DELETE */
    $id = filter_var(
        $data['event_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$id || $id < 1) {
        api_reply(false, 'A valid event ID is required.', [], 400);
    }

    /* Check whether event exists */
    $find = $pdo->prepare(
        'SELECT event_id
         FROM practical13_events
         WHERE event_id = ?'
    );

    $find->execute([$id]);

    if (!$find->fetch()) {
        api_reply(false, 'Event not found.', [], 404);
    }

    /* PUT: Update event */
    if ($method === 'PUT') {

        $stmt = $pdo->prepare(
            'UPDATE practical13_events
             SET title = ?,
                 description = ?,
                 category = ?,
                 event_date = ?,
                 location = ?,
                 status = ?,
                 updated_at = NOW()
             WHERE event_id = ?'
        );

        $stmt->execute([
            $event['title'],
            $event['description'],
            $event['category'],
            $date->format('Y-m-d H:i:s'),
            $event['location'],
            $event['status'],
            $id
        ]);

        api_reply(
            true,
            'Event updated successfully.',
            ['event_id' => (int)$id]
        );
    }

    /* DELETE: Remove event */
    if ($method === 'DELETE') {

        $stmt = $pdo->prepare(
            'DELETE FROM practical13_events
             WHERE event_id = ?'
        );

        $stmt->execute([$id]);

        api_reply(
            true,
            'Event deleted successfully.',
            ['event_id' => (int)$id]
        );
    }

} catch (PDOException $e) {

    error_log('Practical 13 API Database Error: ' . $e->getMessage());

    api_reply(
        false,
        'A database error occurred. Please try again.',
        [],
        500
    );

} catch (Throwable $e) {

    error_log('Practical 13 API Error: ' . $e->getMessage());

    api_reply(
        false,
        'The request could not be completed.',
        [],
        500
    );
}
