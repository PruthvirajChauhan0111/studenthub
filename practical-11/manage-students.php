<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_mysqli.php';
requireRole('admin');

$message = '';
$messageType = 'success';
$editStudent = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sh_verify_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid request token. Please go back and refresh the page.');
    }

    $action = $_POST['action'] ?? '';
    $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);

    if ($action === 'delete') {
        if (!$studentId || $studentId < 1) {
            $message = 'Invalid student record.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare('DELETE FROM students WHERE student_id = ?');
            $stmt->bind_param('i', $studentId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $message = 'Student record deleted successfully.';
            } else {
                $message = 'Student record was not found or could not be deleted.';
                $messageType = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'save') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $course = trim($_POST['course'] ?? '');

        if ($name === '' || mb_strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150 || $course === '' || mb_strlen($course) > 100) {
            $message = 'Enter a valid name, email address and course.';
            $messageType = 'error';
        } elseif ($studentId) {
            $stmt = $conn->prepare('UPDATE students SET full_name = ?, email = ?, course = ? WHERE student_id = ?');
            $stmt->bind_param('sssi', $name, $email, $course, $studentId);
            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                $message = 'Student record updated successfully.';
            } else {
                $message = 'Unable to update the student record.';
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare('INSERT INTO students (full_name, email, course) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $name, $email, $course);
            if ($stmt->execute()) {
                $message = 'Student added successfully.';
            } else {
                $message = 'Unable to add the student. Please check the details.';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId && $editId > 0) {
        $stmt = $conn->prepare('SELECT student_id, full_name, email, course FROM students WHERE student_id = ?');
        $stmt->bind_param('i', $editId);
        $stmt->execute();
        $editStudent = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$search = trim($_GET['search'] ?? '');
$courseFilter = trim($_GET['course'] ?? '');
$sql = 'SELECT student_id, full_name, email, course, created_at FROM students WHERE (full_name LIKE ? OR email LIKE ? OR CAST(student_id AS CHAR) LIKE ?) AND (? = \'\' OR course = ?) ORDER BY student_id DESC';
$like = '%' . $search . '%';
$stmt = $conn->prepare($sql);
$stmt->bind_param('sssss', $like, $like, $like, $courseFilter, $courseFilter);
$stmt->execute();
$students = $stmt->get_result();
$coursesResult = $conn->query('SELECT DISTINCT course FROM students ORDER BY course');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Students | StudentHub</title><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="auth.css"><link rel="stylesheet" href="manage-students.css">
</head>
<body class="portal-page">
<header><h1>🎓 StudentHub Admin</h1><p>Student record management</p></header>
<main class="manage-main">
<div class="manage-top"><div><p class="eyebrow">PRACTICAL 11 · STUDENT CRUD</p><h2>Manage Students</h2><p>Add, view, edit, delete, search and filter student records.</p></div><div class="manage-actions"><a class="secondary-link" href="admin_dashboard.php">← Admin Dashboard</a><form method="POST" action="logout.php"><input type="hidden" name="csrf_token" value="<?= sh_escape(sh_csrf_token()) ?>"><button class="logout-button" type="submit">Logout</button></form></div></div>
<?php if ($message !== ''): ?><div class="crud-alert <?= $messageType === 'error' ? 'alert-error' : 'alert-success' ?>" role="status"><?= sh_escape($message) ?></div><?php endif; ?>
<section class="crud-grid">
<div class="crud-card form-card"><h3><?= $editStudent ? 'Update Student' : 'Add New Student' ?></h3>
<form method="POST" action="manage-students.php" class="crud-form">
<input type="hidden" name="csrf_token" value="<?= sh_escape(sh_csrf_token()) ?>"><input type="hidden" name="action" value="save">
<?php if ($editStudent): ?><input type="hidden" name="student_id" value="<?= (int)$editStudent['student_id'] ?>"><?php endif; ?>
<label for="full_name">Full Name</label><input id="full_name" name="full_name" maxlength="100" required value="<?= sh_escape($editStudent['full_name'] ?? '') ?>" placeholder="Enter student name">
<label for="email">Email Address</label><input type="email" id="email" name="email" maxlength="150" required value="<?= sh_escape($editStudent['email'] ?? '') ?>" placeholder="student@example.com">
<label for="course">Course</label><input id="course" name="course" maxlength="100" required value="<?= sh_escape($editStudent['course'] ?? '') ?>" placeholder="e.g. BCA">
<button type="submit" class="primary-action"><?= $editStudent ? 'Save Changes' : 'Add Student' ?></button>
<?php if ($editStudent): ?><a class="cancel-link" href="manage-students.php">Cancel editing</a><?php endif; ?>
</form></div>
<div class="crud-card records-card"><div class="records-heading"><div><h3>Student Records</h3><p>Records stored in the StudentHub database</p></div><span class="record-pill"><?= $students->num_rows ?> records</span></div>
<form method="GET" action="manage-students.php" class="filter-form"><input type="search" name="search" value="<?= sh_escape($search) ?>" placeholder="Search name, email or ID"><select name="course"><option value="">All courses</option><?php if ($coursesResult): while ($courseRow = $coursesResult->fetch_assoc()): ?><option value="<?= sh_escape($courseRow['course']) ?>" <?= $courseFilter === $courseRow['course'] ? 'selected' : '' ?>><?= sh_escape($courseRow['course']) ?></option><?php endwhile; endif; ?></select><button type="submit">Apply</button><a href="manage-students.php">Reset</a></form>
<div class="records-table-wrap"><table><thead><tr><th>ID</th><th>Student</th><th>Email</th><th>Course</th><th>Created</th><th>Actions</th></tr></thead><tbody>
<?php if ($students->num_rows > 0): while ($row = $students->fetch_assoc()): ?><tr><td>#<?= (int)$row['student_id'] ?></td><td><?= sh_escape($row['full_name']) ?></td><td><?= sh_escape($row['email']) ?></td><td><?= sh_escape($row['course']) ?></td><td><?= sh_escape($row['created_at']) ?></td><td class="row-actions"><a href="manage-students.php?edit=<?= (int)$row['student_id'] ?>">Edit</a><form method="POST" action="manage-students.php" onsubmit="return confirm('Delete this student record?');"><input type="hidden" name="csrf_token" value="<?= sh_escape(sh_csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="student_id" value="<?= (int)$row['student_id'] ?>"><button type="submit">Delete</button></form></td></tr><?php endwhile; else: ?><tr><td colspan="6" class="empty-state">No student records found. Add a student or change your search.</td></tr><?php endif; ?>
</tbody></table></div></div></section>
<p class="security-note">Only authenticated administrators can manage student records. All database operations use MySQLi prepared statements and all changes require a valid CSRF token.</p>
</main><footer><p>&copy; 2026 StudentHub Portal. All rights reserved.</p></footer>
</body></html>
<?php $stmt->close(); ?>
