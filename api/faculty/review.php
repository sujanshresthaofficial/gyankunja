<?php
declare(strict_types=1);

require __DIR__ . '/../auth_guard.php';
requireMethod('POST');

$faculty = requireAnyRole(['faculty', 'admin']);
$data = readJsonBody();

$noteId = filter_var($data['noteId'] ?? null, FILTER_VALIDATE_INT);
$action = trim((string) ($data['action'] ?? ''));
$comment = trim((string) ($data['comment'] ?? ''));

if (!$noteId || !in_array($action, ['approve', 'reject'], true)) {
    jsonResponse([
        'success' => false,
        'message' => 'A valid note and review action are required.',
    ], 422);
}

if (mb_strlen($comment) > 500) {
    jsonResponse([
        'success' => false,
        'message' => 'The review comment must be 500 characters or fewer.',
    ], 422);
}

if ($action === 'reject' && $comment === '') {
    jsonResponse([
        'success' => false,
        'message' => 'Please explain why the note is being rejected.',
    ], 422);
}

$newStatus = $action === 'approve' ? 'approved' : 'rejected';

$update = $pdo->prepare(
    'UPDATE notes
     SET
        approval_status = :status,
        reviewed_by = :reviewed_by,
        reviewed_at = NOW(),
        review_comment = :review_comment
     WHERE id = :id
       AND approval_status = "pending"'
);

$update->execute([
    'status' => $newStatus,
    'reviewed_by' => (int) $faculty['id'],
    'review_comment' => $comment !== '' ? $comment : null,
    'id' => $noteId,
]);

if ($update->rowCount() !== 1) {
    jsonResponse([
        'success' => false,
        'message' => 'This note was already reviewed or does not exist.',
    ], 409);
}

jsonResponse([
    'success' => true,
    'message' => $newStatus === 'approved'
        ? 'The note was approved and is now visible to users.'
        : 'The note was rejected.',
    'status' => $newStatus,
]);
