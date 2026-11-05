<?php
declare(strict_types=1);

/**
 * Students can read only their own semester's upcoming exam schedule.
 * Coordinators/admins can maintain the schedule using a session CSRF token.
 * Run database/dashboard_exams.sql before using this endpoint.
 */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!is_student_logged_in() && !is_staff_logged_in()) {
    json_out(['success' => false, 'message' => 'Please sign in.'], 401);
}

if ($method === 'GET') {
    try {
        if (is_staff_logged_in()) {
            if (empty($_SESSION['exam_schedule_csrf'])) {
                $_SESSION['exam_schedule_csrf'] = bin2hex(random_bytes(32));
            }
            $stmt = $pdo->query(
                'SELECT id, semester, subject, exam_type, starts_at, venue
                 FROM exam_schedule ORDER BY starts_at DESC, id DESC LIMIT 200'
            );
            json_out([
                'success' => true,
                'exams' => $stmt->fetchAll(),
                'csrf_token' => $_SESSION['exam_schedule_csrf'],
            ]);
        }

        $student = $pdo->prepare('SELECT semester FROM users WHERE id = :id LIMIT 1');
        $student->execute([':id' => (int) $_SESSION['user_id']]);
        $current = $student->fetch();
        if (!$current) json_out(['success' => false, 'message' => 'Student not found.'], 404);

        $stmt = $pdo->prepare(
            'SELECT id, semester, subject, exam_type, starts_at, venue
             FROM exam_schedule
             WHERE semester = :semester AND starts_at >= NOW()
             ORDER BY starts_at ASC, id ASC LIMIT 8'
        );
        $stmt->execute([':semester' => $current['semester']]);
        json_out(['success' => true, 'exams' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        error_log('Exam schedule read failed: ' . $e->getMessage());
        if ($e->getCode() === '42S02') {
            json_out([
                'success' => false,
                'setup_needed' => true,
                'message' => 'The exam timetable is currently unavailable. Please contact the site administrator.',
            ], 503);
        }
        json_out(['success' => false, 'message' => 'Could not load exam schedule.'], 500);
    }
}

if ($method !== 'POST') {
    json_out(['success' => false, 'message' => 'Method not allowed.'], 405);
}
require_staff_json();
$givenToken = (string) ($_POST['csrf_token'] ?? '');
$serverToken = (string) ($_SESSION['exam_schedule_csrf'] ?? '');
if ($serverToken === '' || !hash_equals($serverToken, $givenToken)) {
    json_out(['success' => false, 'message' => 'Session token expired. Refresh the exam schedule tab.'], 403);
}

$action = (string) ($_POST['action'] ?? '');
$id = (int) ($_POST['id'] ?? 0);

if ($action === 'delete') {
    if ($id <= 0) json_out(['success' => false, 'message' => 'Invalid exam ID.'], 400);
    try {
        $stmt = $pdo->prepare('DELETE FROM exam_schedule WHERE id = :id');
        $stmt->execute([':id' => $id]);
        json_out(['success' => $stmt->rowCount() > 0, 'message' => $stmt->rowCount() ? 'Exam removed.' : 'Exam not found.']);
    } catch (PDOException $e) {
        error_log('Exam deletion failed: ' . $e->getMessage());
        json_out(['success' => false, 'message' => 'Could not delete exam.'], 500);
    }
}

if (!in_array($action, ['create', 'update'], true)) {
    json_out(['success' => false, 'message' => 'Unknown action.'], 400);
}
$semester = trim((string) ($_POST['semester'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$type = trim((string) ($_POST['exam_type'] ?? ''));
$when = trim((string) ($_POST['starts_at'] ?? ''));
$venue = trim((string) ($_POST['venue'] ?? ''));

if (!in_array($semester, SEMESTERS, true)) {
    json_out(['success' => false, 'message' => 'Choose a valid semester.'], 400);
}
if ($subject === '' || strlen($subject) > 150 || $type === '' || strlen($type) > 100 || strlen($venue) > 200) {
    json_out(['success' => false, 'message' => 'Enter a valid subject, exam type and venue.'], 400);
}
$parsed = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $when);
if (!$parsed || $parsed->format('Y-m-d\TH:i') !== $when) {
    json_out(['success' => false, 'message' => 'Select a valid exam date and time.'], 400);
}
if ($action === 'update' && $id <= 0) {
    json_out(['success' => false, 'message' => 'Invalid exam ID.'], 400);
}
$values = [
    ':semester' => $semester,
    ':subject' => $subject,
    ':type' => $type,
    ':starts_at' => $parsed->format('Y-m-d H:i:s'),
    ':venue' => $venue !== '' ? $venue : null,
];
try {
    if ($action === 'create') {
        $pdo->prepare(
            'INSERT INTO exam_schedule (semester, subject, exam_type, starts_at, venue)
             VALUES (:semester, :subject, :type, :starts_at, :venue)'
        )->execute($values);
    } else {
        $values[':id'] = $id;
        $pdo->prepare(
            'UPDATE exam_schedule
             SET semester = :semester, subject = :subject, exam_type = :type,
                 starts_at = :starts_at, venue = :venue
             WHERE id = :id'
        )->execute($values);
    }
    json_out(['success' => true, 'message' => $action === 'create' ? 'Exam added.' : 'Exam updated.']);
} catch (PDOException $e) {
    error_log('Exam write failed: ' . $e->getMessage());
    json_out(['success' => false, 'message' => 'Could not save exam. Check the exam_schedule table.'], 500);
}
