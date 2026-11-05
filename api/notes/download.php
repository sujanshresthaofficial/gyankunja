<?php
declare(strict_types=1);

define('GYANKUNJA_HTML_PAGE', true);
require __DIR__ . '/../bootstrap.php';

$noteId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$noteId) {
    http_response_code(400);
    exit('Invalid note ID.');
}

$query = $pdo->prepare(
    'SELECT
        id,
        user_id,
        original_filename,
        file_path,
        mime_type,
        approval_status
     FROM notes
     WHERE id = :id
     LIMIT 1'
);
$query->execute(['id' => $noteId]);
$note = $query->fetch();

if (!$note) {
    http_response_code(404);
    exit('Note not found.');
}

$canAccess = $note['approval_status'] === 'approved';
$sessionUser = $_SESSION['user'] ?? null;

if (!$canAccess && is_array($sessionUser)) {
    $role = (string) ($sessionUser['role'] ?? 'student');
    $canAccess =
        (int) ($sessionUser['id'] ?? 0) === (int) $note['user_id'] ||
        in_array($role, ['faculty', 'admin'], true);
}

if (!$canAccess) {
    http_response_code(403);
    exit('This note is waiting for approval.');
}

$projectRoot = dirname(__DIR__, 2);
$absolutePath = $projectRoot . DIRECTORY_SEPARATOR .
    str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $note['file_path']);

$realProjectRoot = realpath($projectRoot);
$realFilePath = realpath($absolutePath);

if (
    $realProjectRoot === false ||
    $realFilePath === false ||
    !str_starts_with($realFilePath, $realProjectRoot . DIRECTORY_SEPARATOR) ||
    !is_file($realFilePath)
) {
    http_response_code(404);
    exit('Stored file not found.');
}

$safeFilename = preg_replace('/[^A-Za-z0-9._ -]/', '_', (string) $note['original_filename']);
$safeFilename = $safeFilename !== '' ? $safeFilename : 'gyankunja-note.pdf';

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($realFilePath));
header(
    'Content-Disposition: inline; filename="' .
    addcslashes($safeFilename, '"\\') .
    '"'
);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($realFilePath);
exit;
