<?php
declare(strict_types=1);

/**
 * Student dashboard data from the active php/config.php + files schema.
 * The separate api/notes schema is legacy and is not mixed into this view.
 */
require_once __DIR__ . '/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    json_out(['success' => false, 'message' => 'Only GET is allowed.'], 405);
}
if (!is_student_logged_in()) {
    json_out(['success' => false, 'message' => 'Please sign in.'], 401);
}

$userId = (int) $_SESSION['user_id'];

try {
    $userStatement = $pdo->prepare('SELECT semester FROM users WHERE id = :id LIMIT 1');
    $userStatement->execute([':id' => $userId]);
    $user = $userStatement->fetch();
    if (!$user || !in_array($user['semester'], SEMESTERS, true)) {
        json_out(['success' => false, 'message' => 'Student account or semester not found.'], 404);
    }

    $notesStatement = $pdo->prepare(
        "SELECT id, title, subject, category, semester, original_name, file_size, uploaded_at
         FROM files
         WHERE status = 'approved' AND semester = :semester
         ORDER BY COALESCE(reviewed_at, uploaded_at) DESC, id DESC
         LIMIT 6"
    );
    $notesStatement->execute([':semester' => $user['semester']]);
    $notes = [];
    foreach ($notesStatement->fetchAll() as $note) {
        $id = (int) $note['id'];
        $notes[] = [
            'id' => $id,
            'title' => $note['title'],
            'subject' => $note['subject'],
            'category' => $note['category'],
            'semester' => $note['semester'],
            'extension' => strtolower(pathinfo($note['original_name'], PATHINFO_EXTENSION)),
            'file_size' => (int) $note['file_size'],
            'uploaded_at' => $note['uploaded_at'],
            'download_url' => 'php/download.php?id=' . $id,
        ];
    }

    $uploadsStatement = $pdo->prepare(
        'SELECT id, title, semester, subject, category, status, review_remarks, uploaded_at
         FROM files WHERE user_id = :id ORDER BY uploaded_at DESC, id DESC LIMIT 100'
    );
    $uploadsStatement->execute([':id' => $userId]);
    $uploads = $uploadsStatement->fetchAll();

    $counts = ['total' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0];
    $countsStatement = $pdo->prepare('SELECT status, COUNT(*) AS total FROM files WHERE user_id = :id GROUP BY status');
    $countsStatement->execute([':id' => $userId]);
    foreach ($countsStatement->fetchAll() as $row) {
        $count = (int) $row['total'];
        $counts['total'] += $count;
        if (isset($counts[$row['status']])) $counts[$row['status']] = $count;
    }

    $monthlyStatement = $pdo->prepare(
        "SELECT DATE_FORMAT(uploaded_at, '%Y-%m') AS ym, COUNT(*) AS total
         FROM files
         WHERE user_id = :id AND uploaded_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
         GROUP BY DATE_FORMAT(uploaded_at, '%Y-%m')"
    );
    $monthlyStatement->execute([':id' => $userId]);
    $monthlyCount = [];
    foreach ($monthlyStatement->fetchAll() as $row) {
        $monthlyCount[$row['ym']] = (int) $row['total'];
    }

    $startMonth = new DateTimeImmutable('first day of this month');
    $activity = [];
    for ($i = 5; $i >= 0; $i--) {
        $ym = $startMonth->modify('-' . $i . ' months')->format('Y-m');
        $activity[] = ['month' => $ym, 'count' => $monthlyCount[$ym] ?? 0];
    }

    json_out([
        'success' => true,
        'semester' => $user['semester'],
        'notes' => $notes,
        'uploads' => $uploads,
        'counts' => $counts,
        'activity' => $activity,
    ]);
} catch (Throwable $e) {
    error_log('Gyankunja dashboard query failed: ' . $e->getMessage());
    json_out(['success' => false, 'message' => 'Could not load dashboard data. Check the files database table.'], 500);
}
