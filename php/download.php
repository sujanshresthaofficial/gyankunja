<?php
// php/download.php — serves an uploaded file by id
// Approved: anyone. Pending/rejected: coordinators only (others get 404).

require_once 'config.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT status, original_name, stored_name FROM files WHERE id = :id');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();

$canView = $row && ($row['status'] === 'approved' || !empty($_SESSION['coordinator_id']));
if (!$canView) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found.';
    exit;
}

$base = realpath(UPLOAD_DIR);
$path = $base ? realpath($base . DIRECTORY_SEPARATOR . basename($row['stored_name'])) : false;
if (!$path || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File is missing on the server.';
    exit;
}

$ext = strtolower(pathinfo($row['stored_name'], PATHINFO_EXTENSION));
$types = [
    'pdf'  => ['application/pdf', true],
    'jpg'  => ['image/jpeg', true],
    'jpeg' => ['image/jpeg', true],
    'png'  => ['image/png', true],
    'txt'  => ['text/plain; charset=utf-8', true],
    'doc'  => ['application/msword', false],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', false],
    'ppt'  => ['application/vnd.ms-powerpoint', false],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', false],
];
[$mime, $inlineOk] = $types[$ext] ?? ['application/octet-stream', false];

$disposition = ($inlineOk && empty($_GET['download'])) ? 'inline' : 'attachment';
$asciiName   = preg_replace('/[^A-Za-z0-9._-]+/', '_', $row['original_name']) ?: ('note.' . $ext);

session_write_close();

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . $disposition . '; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($row['original_name']));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0');

readfile($path);