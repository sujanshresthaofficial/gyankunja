<?php
declare(strict_types=1);

require __DIR__ . '/../auth_guard.php';
requireMethod('GET');

$user = requireAuthenticatedUser();

$query = $pdo->prepare(
    'SELECT
        id,
        title,
        semester,
        subject,
        original_filename,
        approval_status,
        review_comment,
        created_at,
        reviewed_at
     FROM notes
     WHERE user_id = :user_id
     ORDER BY created_at DESC'
);

$query->execute([
    'user_id' => (int) $user['id'],
]);

$notes = $query->fetchAll();

foreach ($notes as &$note) {
    $note['id'] = (int) $note['id'];
}
unset($note);

jsonResponse([
    'success' => true,
    'notes' => $notes,
    'count' => count($notes),
]);
