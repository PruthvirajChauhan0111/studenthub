<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_pdo.php';
requireRole('admin');

$uploadDir = __DIR__ . '/uploads';
$uploadUrl = 'uploads/';
$maxFileSize = 2 * 1024 * 1024;
$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];
$message = '';
$messageType = 'success';
$editEvent = null;

function p12_remove_poster(?string $filename, string $directory): void
{
    if ($filename && basename($filename) === $filename) {
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}

function p12_validate_text(string $value, int $max): bool
{
    return $value !== '' && mb_strlen($value) <= $max;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sh_verify_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid form request. Refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';
    $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);

    if ($action === 'delete') {
        if (!$eventId || $eventId < 1) {
            $message = 'Invalid event record.';
            $messageType = 'error';
        } else {
            $find = $pdo->prepare('SELECT poster FROM practical12_events WHERE event_id = ?');
            $find->execute([$eventId]);
            $old = $find->fetch();
            if (!$old) {
                $message = 'Event not found.';
                $messageType = 'error';
            } else {
                $delete = $pdo->prepare('DELETE FROM practical12_events WHERE event_id = ?');
                $delete->execute([$eventId]);
                p12_remove_poster($old['poster'], $uploadDir);
                $message = 'Event deleted successfully.';
            }
        }
    } elseif ($action === 'save') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $status = $_POST['status'] ?? 'open';
        $errors = [];

        if (!p12_validate_text($title, 150)) $errors[] = 'Enter an event title (maximum 150 characters).';
        if (!p12_validate_text($description, 5000)) $errors[] = 'Enter an event description (maximum 5000 characters).';
        if (!p12_validate_text($category, 80)) $errors[] = 'Enter an event category (maximum 80 characters).';
        if (!p12_validate_text($location, 180)) $errors[] = 'Enter an event location (maximum 180 characters).';
        $dateObject = DateTime::createFromFormat('Y-m-d\TH:i', $eventDate);
        if (!$dateObject || $dateObject->format('Y-m-d\TH:i') !== $eventDate) $errors[] = 'Select a valid event date and time.';
        if (!in_array($status, ['open', 'closed'], true)) $errors[] = 'Select a valid event status.';

        $newPoster = null;
        $file = $_FILES['poster'] ?? null;
        if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Poster upload failed. Please choose the file again.';
            } elseif ($file['size'] > $maxFileSize) {
                $errors[] = 'Poster must be 2 MB or smaller.';
            } elseif (!is_uploaded_file($file['tmp_name'])) {
                $errors[] = 'Invalid uploaded file.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                if (!isset($allowedMimeTypes[$mime])) {
                    $errors[] = 'Only JPG, PNG and WEBP poster images are allowed.';
                } else {
                    $imageInfo = @getimagesize($file['tmp_name']);
                    if ($imageInfo === false) {
                        $errors[] = 'The uploaded file is not a valid image.';
                    } else {
                        $newPoster = bin2hex(random_bytes(16)) . '.' . $allowedMimeTypes[$mime];
                    }
                }
            }
        }

        if ($errors) {
            $message = implode(' ', $errors);
            $messageType = 'error';
        } else {
            $oldPoster = null;
            if ($eventId) {
                $find = $pdo->prepare('SELECT poster FROM practical12_events WHERE event_id = ?');
                $find->execute([$eventId]);
                $existing = $find->fetch();
                if (!$existing) {
                    $message = 'Event not found. It may have been removed.';
                    $messageType = 'error';
                } else {
                    $oldPoster = $existing['poster'];
                }
            }

            if ($messageType === 'success') {
                if ($newPoster !== null && !move_uploaded_file($file['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $newPoster)) {
                    $message = 'Unable to save the poster. Check uploads folder permissions.';
                    $messageType = 'error';
                } else {
                    if ($eventId) {
                        if ($newPoster !== null) {
                            $sql = 'UPDATE practical12_events SET title=?, description=?, category=?, event_date=?, location=?, status=?, poster=?, updated_at=NOW() WHERE event_id=?';
                            $params = [$title, $description, $category, $dateObject->format('Y-m-d H:i:s'), $location, $status, $newPoster, $eventId];
                        } else {
                            $sql = 'UPDATE practical12_events SET title=?, description=?, category=?, event_date=?, location=?, status=?, updated_at=NOW() WHERE event_id=?';
                            $params = [$title, $description, $category, $dateObject->format('Y-m-d H:i:s'), $location, $status, $eventId];
                        }
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        if ($newPoster !== null) p12_remove_poster($oldPoster, $uploadDir);
                        $message = 'Event updated successfully.';
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO practical12_events (title, description, category, event_date, location, status, poster) VALUES (?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$title, $description, $category, $dateObject->format('Y-m-d H:i:s'), $location, $status, $newPoster]);
                        $message = 'Event added successfully.';
                    }
                }
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId && $editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM practical12_events WHERE event_id = ?');
        $stmt->execute([$editId]);
        $editEvent = $stmt->fetch() ?: null;
    }
}

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$categoryFilter = trim($_GET['category'] ?? '');
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(title LIKE ? OR description LIKE ? OR location LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
if (in_array($statusFilter, ['open', 'closed'], true)) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
if ($categoryFilter !== '') {
    $where[] = 'category = ?';
    $params[] = $categoryFilter;
}
$sql = 'SELECT * FROM practical12_events' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY event_date DESC, event_id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();
$categories = $pdo->query('SELECT DISTINCT category FROM practical12_events ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Events | StudentHub</title>
<link rel="stylesheet" href="style.css"><link rel="stylesheet" href="auth.css"><link rel="stylesheet" href="events-admin.css">
</head>
<body class="portal-page">
<header><h1>🎓 StudentHub Admin</h1><p>Event management and poster uploads</p></header>
<main class="event-admin-main">
<div class="event-admin-top"><div><p class="eyebrow">PRACTICAL 12 · EVENT MANAGEMENT</p><h2>Manage Events</h2><p>Create and maintain StudentHub events and posters.</p></div><div class="event-top-actions"><a href="admin_dashboard.php">← Admin Dashboard</a><form method="POST" action="logout.php"><input type="hidden" name="csrf_token" value="<?= sh_escape(sh_csrf_token()) ?>"><button class="logout-button" type="submit">Logout</button></form></div></div>
<?php if ($message !== ''): ?><div class="event-message <?= $messageType === 'error' ? 'error' : 'success' ?>" role="status"><?= sh_escape($message) ?></div><?php endif; ?>
<section class="event-layout">
<div class="event-panel"><h3><?= $editEvent ? 'Update Event' : 'Add New Event' ?></h3>
<form method="POST" enctype="multipart/form-data" class="event-form">
<input type="hidden" name="csrf_token" value="<?= sh_escape(sh_csrf_token()) ?>"><input type="hidden" name="action" value="save">
<?php if ($editEvent): ?><input type="hidden" name="event_id" value="<?= (int)$editEvent['event_id'] ?>"><?php endif; ?>
<label for="title">Event Title</label><input id="title" name="title" maxlength="150" required value="<?= sh_escape($editEvent['title'] ?? '') ?>" placeholder="Enter event title">
<label for="description">Description</label><textarea id="description" name="description" rows="4" maxlength="5000" required><?= sh_escape($editEvent['description'] ?? '') ?></textarea>
<label for="category">Category</label><input id="category" name="category" maxlength="80" required value="<?= sh_escape($editEvent['category'] ?? '') ?>" placeholder="Workshop, Seminar, Cultural">
<label for="event_date">Date and Time</label><input type="datetime-local" id="event_date" name="event_date" required value="<?= isset($editEvent['event_date']) ? sh_escape(date('Y-m-d\TH:i', strtotime($editEvent['event_date']))) : '' ?>">
<label for="location">Location</label><input id="location" name="location" maxlength="180" required value="<?= sh_escape($editEvent['location'] ?? '') ?>" placeholder="Event venue">
<label for="status">Event Status</label><select id="status" name="status"><option value="open" <?= ($editEvent['status'] ?? 'open') === 'open' ? 'selected' : '' ?>>Open</option><option value="closed" <?= ($editEvent['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option></select>
<label for="poster">Event Poster <span class="muted">(JPG, PNG or WEBP; maximum 2 MB)</span></label><input type="file" id="poster" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
<?php if (!empty($editEvent['poster'])): ?><div class="current-poster"><img src="<?= $uploadUrl . rawurlencode($editEvent['poster']) ?>" alt="Current event poster"><span>Current poster. Choose a new file only if you want to replace it.</span></div><?php endif; ?>
<button type="submit" class="event-primary"><?= $editEvent ? 'Save Event Changes' : 'Add Event' ?></button>
<?php if ($editEvent): ?><a class="cancel-edit" href="events-admin.php">Cancel editing</a><?php endif; ?>
</form></div>
<div class="event-panel"><div class="event-list-heading"><div><h3>Event Records</h3><p><?= count($events) ?> event(s) found</p></div></div>
<form method="GET" class="event-filters"><input type="search" name="search" placeholder="Search title, description or location" value="<?= sh_escape($search) ?>"><select name="category"><option value="">All categories</option><?php foreach ($categories as $cat): ?><option value="<?= sh_escape($cat) ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>><?= sh_escape($cat) ?></option><?php endforeach; ?></select><select name="status"><option value="">All statuses</option><option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option><option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option></select><button type="submit">Filter</button><a href="events-admin.php">Reset</a></form>
<div class="event-cards">
<?php if ($events): foreach ($events as $event): ?>
<article class="event-record">
<?php if ($event['poster']): ?><img class="event-poster" src="<?= $uploadUrl . rawurlencode($event['poster']) ?>" alt="Poster for <?= sh_escape($event['title']) ?>"><?php else: ?><div class="poster-placeholder">🎟️<span>No poster uploaded</span></div><?php endif; ?>
<div class="event-record-body"><div class="event-meta"><span><?= sh_escape($event['category']) ?></span><span class="status-<?= sh_escape($event['status']) ?>"><?= ucfirst(sh_escape($event['status'])) ?></span></div>
<h4><?= sh_escape($event['title']) ?></h4><p><?= nl2br(sh_escape($event['description'])) ?></p><p class="event-detail">📅 <?= sh_escape(date('d M Y, h:i A', strtotime($event['event_date']))) ?></p><p class="event-detail">📍 <?= sh_escape($event['location']) ?></p>
<div class="event-record-actions"><a href="events-admin.php?edit=<?= (int)$event['event_id'] ?>">Edit</a><form method="POST" onsubmit="return confirm('Are you sure you want to delete this event?');"><input type="hidden" name="csrf_token" value="<?= sh_escape(sh_csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="event_id" value="<?= (int)$event['event_id'] ?>"><button type="submit">Delete</button></form></div></div></article>
<?php endforeach; else: ?><p class="no-events">No events found. Add your first event using the form.</p><?php endif; ?>
</div></div></section>
<p class="event-security-note">Admin-only access · PDO prepared statements · Server-side image MIME and size validation · 2 MB upload limit</p>
</main><footer><p>&copy; 2026 StudentHub Portal. All rights reserved.</p></footer>
</body></html>
