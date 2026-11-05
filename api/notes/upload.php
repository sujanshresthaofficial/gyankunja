<?php
declare(strict_types=1);

require __DIR__ . '/../auth_guard.php';
requireMethod('POST');

$user = requireAuthenticatedUser();

$title = trim((string) ($_POST['title'] ?? ''));
$semester = trim((string) ($_POST['semester'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));

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

if ($title === '' || $semester === '' || $subject === '') {
    jsonResponse([
        'success' => false,
        'message' => 'Title, semester and subject are required.',
    ], 422);
}

if (!in_array($semester, $allowedSemesters, true)) {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid semester.',
    ], 422);
}

if (
    ($user['role'] ?? 'student') === 'student' &&
    $semester !== (string) $user['semester']
) {
    jsonResponse([
        'success' => false,
        'message' => 'Students may upload notes only for their registered semester.',
    ], 403);
}

if (mb_strlen($title) > 180 || mb_strlen($subject) > 120) {
    jsonResponse([
        'success' => false,
        'message' => 'The title or subject is too long.',
    ], 422);
}

if (!isset($_FILES['noteFile'])) {
    jsonResponse([
        'success' => false,
        'message' => 'Please choose a PDF file.',
    ], 422);
}

$file = $_FILES['noteFile'];

if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    $messages = [
        UPLOAD_ERR_INI_SIZE => 'The file is larger than the server upload limit.',
        UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
        UPLOAD_ERR_PARTIAL => 'The file upload was interrupted.',
        UPLOAD_ERR_NO_FILE => 'Please choose a PDF file.',
        UPLOAD_ERR_NO_TMP_DIR => 'The server temporary folder is missing.',
        UPLOAD_ERR_CANT_WRITE => 'The server could not save the uploaded file.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
    ];

    jsonResponse([
        'success' => false,
        'message' => $messages[$uploadError] ?? 'The file could not be uploaded.',
    ], 422);
}

$maxBytes = 10 * 1024 * 1024;

if ((int) $file['size'] <= 0 || (int) $file['size'] > $maxBytes) {
    jsonResponse([
        'success' => false,
        'message' => 'The PDF must be smaller than 10 MB.',
    ], 422);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file((string) $file['tmp_name']);

if ($mimeType !== 'application/pdf') {
    jsonResponse([
        'success' => false,
        'message' => 'Only genuine PDF files are accepted.',
    ], 422);
}

$originalFilename = basename((string) $file['name']);
$storedFilename = bin2hex(random_bytes(20)) . '.pdf';

$projectRoot = dirname(__DIR__, 2);
$storageDirectory = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'notes';

if (!is_dir($storageDirectory) && !mkdir($storageDirectory, 0775, true) && !is_dir($storageDirectory)) {
    jsonResponse([
        'success' => false,
        'message' => 'The upload storage folder could not be created.',
    ], 500);
}

$absolutePath = $storageDirectory . DIRECTORY_SEPARATOR . $storedFilename;
$databasePath = 'storage/notes/' . $storedFilename;

if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
    jsonResponse([
        'success' => false,
        'message' => 'The server could not store the uploaded PDF.',
    ], 500);
}

$insert = $pdo->prepare(
    'INSERT INTO notes (
        user_id,
        title,
        semester,
        subject,
        description,
        original_filename,
        stored_filename,
        file_path,
        mime_type,
        file_size,
        approval_status
     ) VALUES (
        :user_id,
        :title,
        :semester,
        :subject,
        :description,
        :original_filename,
        :stored_filename,
        :file_path,
        :mime_type,
        :file_size,
        "pending"
     )'
);

try {
    $insert->execute([
        'user_id' => (int) $user['id'],
        'title' => $title,
        'semester' => $semester,
        'subject' => $subject,
        'description' => $description !== '' ? $description : null,
        'original_filename' => $originalFilename,
        'stored_filename' => $storedFilename,
        'file_path' => $databasePath,
        'mime_type' => $mimeType,
        'file_size' => (int) $file['size'],
    ]);
} catch (Throwable $exception) {
    @unlink($absolutePath);
    error_log('Gyankunja note insert failed: ' . $exception->getMessage());

    jsonResponse([
        'success' => false,
        'message' => 'The PDF was uploaded, but its database record could not be saved.',
    ], 500);
}

jsonResponse([
    'success' => true,
    'message' => 'Your note was submitted and is waiting for faculty approval.',
    'noteId' => (int) $pdo->lastInsertId(),
    'status' => 'pending',
], 201);
