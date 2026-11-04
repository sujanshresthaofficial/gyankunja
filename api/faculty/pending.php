<?php
declare(strict_types=1);

require __DIR__ . '/../auth_guard.php';
requireMethod('GET');

requireAnyRole(['faculty', 'admin']);

$status = trim((string) ($_GET['status'] ?? 'pending'));
$allowedStatuses = ['pending', 'approved', 'rejected', 'all'];

if (!in_array($status, $allowedStatuses, true)) {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid review status.',
    ], 422);
}

$sql = '
    SELECT
        n.id,
        n.title,
        n.semester,
        n.subject,
        n.description,
        n.original_filename,
        n.file_size,
        n.approval_status,
        n.review_comment,
        n.created_at,
        n.reviewed_at,
        uploader.name AS uploader_name,
        uploader.email AS uploader_email,
        reviewer.name AS reviewer_name
    FROM notes n
    INNER JOIN users uploader ON uploader.id = n.user_id
    LEFT JOIN users reviewer ON reviewer.id = n.reviewed_by
';

$params = [];

if ($status !== 'all') {
    $sql .= ' WHERE n.approval_status = :status';
    $params['status'] = $status;
}

$sql .= '
    ORDER BY
        CASE n.approval_status
            WHEN "pending" THEN 0
            WHEN "approved" THEN 1
            ELSE 2
        END,
        n.created_at ASC
';

$query = $pdo->prepare($sql);
$query->execute($params);
$notes = $query->fetchAll();

foreach ($notes as &$note) {
    $note['id'] = (int) $note['id'];
    $note['file_size'] = (int) $note['file_size'];
    $note['preview_url'] = 'api/notes/download.php?id=' . $note['id'];
}
unset($note);

jsonResponse([
    'success' => true,
    'notes' => $notes,
]);
