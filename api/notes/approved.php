<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';
requireMethod('GET');

$semester = trim((string) ($_GET['semester'] ?? ''));

$allowedSemesters = [
    'Semester I',
    'Semester II',
    'Semester III',
    'Semester IV',
    'Semester V',
    'Semester VI',
    'Semester VII',
    'Semester VIII',
];

$sql = '
    SELECT
        n.id,
        n.title,
        n.semester,
        n.subject,
        n.description,
        n.original_filename,
        n.file_size,
        n.created_at,
        n.reviewed_at,
        uploader.name AS uploader_name,
        reviewer.name AS reviewer_name
    FROM notes n
    INNER JOIN users uploader ON uploader.id = n.user_id
    LEFT JOIN users reviewer ON reviewer.id = n.reviewed_by
    WHERE n.approval_status = "approved"
';

$params = [];

if ($semester !== '') {
    if (!in_array($semester, $allowedSemesters, true)) {
        jsonResponse([
            'success' => false,
            'message' => 'Invalid semester.',
        ], 422);
    }

    $sql .= ' AND n.semester = :semester';
    $params['semester'] = $semester;
}

$sql .= ' ORDER BY COALESCE(n.reviewed_at, n.updated_at) DESC LIMIT 100';

$query = $pdo->prepare($sql);
$query->execute($params);
$notes = $query->fetchAll();

foreach ($notes as &$note) {
    $note['id'] = (int) $note['id'];
    $note['file_size'] = (int) $note['file_size'];
    $note['download_url'] = 'api/notes/download.php?id=' . $note['id'];
}
unset($note);

jsonResponse([
    'success' => true,
    'notes' => $notes,
]);
