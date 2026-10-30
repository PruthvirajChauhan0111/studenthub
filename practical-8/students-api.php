<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$file = __DIR__ . DIRECTORY_SEPARATOR . 'storage-registrations.json';

if (!is_file($file) || !is_readable($file)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Student registration storage file is missing or unreadable.'
    ]);
    exit;
}

$contents = file_get_contents($file);

if ($contents === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Could not read student records.'
    ]);
    exit;
}

$records = json_decode($contents, true);

if (!is_array($records) || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Student registration data is invalid.'
    ]);
    exit;
}

$students = [];

foreach ($records as $index => $record) {
    if (!is_array($record)) {
        continue;
    }

    // Support common field names used by registration forms.
    $studentId = $record['studentId']
        ?? $record['student_id']
        ?? $record['studentReference']
        ?? $record['reference']
        ?? $record['id']
        ?? ('STU-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT));

    $name = $record['fullName']
        ?? $record['name']
        ?? $record['full_name']
        ?? '';

    $email = $record['email'] ?? '';

    $course = $record['course'] ?? '';

    $semester = $record['semester']
        ?? $record['sem']
        ?? '—';

    $students[] = [
        'studentId' => (string) $studentId,
        'name' => (string) $name,
        'email' => (string) $email,
        'course' => (string) $course,
        'semester' => (string) $semester
    ];
}

echo json_encode([
    'success' => true,
    'students' => $students
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
