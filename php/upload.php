<?php
// =====================================================
// php/upload.php — receives the "Upload a New Note" form
// from dashboard.html via fetch() and stores it as pending
// =====================================================

require_once 'config.php';

header('Content-Type: application/json');

function respond($success, $message, $extra = [])
{
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Only POST requests are allowed.');
}

// --- Read text fields ---
$title       = trim($_POST['title'] ?? '');
$semester    = trim($_POST['semester'] ?? '');
$subject     = trim($_POST['subject'] ?? '');
$description = trim($_POST['description'] ?? '');
$uploaderName  = trim($_POST['uploader_name'] ?? '');   // optional for now
$uploaderLogin = trim($_POST['uploader_login'] ?? '');  // optional for now

if ($title === '' || $semester === '' || $subject === '') {
    respond(false, 'Title, semester and subject are required.');
}

if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    respond(false, 'Please choose a file to upload.');
}

$file = $_FILES['file'];

// --- Validate upload ---
if ($file['error'] !== UPLOAD_ERR_OK) {
    respond(false, 'Upload error (code ' . $file['error'] . ').');
}

if ($file['size'] > MAX_FILE_SIZE) {
    respond(false, 'File is too large. Max size is ' . (MAX_FILE_SIZE / 1024 / 1024) . ' MB.');
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
    respond(false, 'File type .' . $ext . ' is not allowed.');
}

// --- Save file with a random name so uploads never collide/overwrite ---
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$storedName  = bin2hex(random_bytes(16)) . '.' . $ext;
$destination = UPLOAD_DIR . $storedName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    respond(false, 'Failed to save the uploaded file on the server.');
}

// --- Insert into database as pending ---
$stmt = $pdo->prepare(
    "INSERT INTO files
        (uploader_name, uploader_login, semester, subject, title, description,
         original_name, stored_name, file_path, file_size, status)
     VALUES
        (:uploader_name, :uploader_login, :semester, :subject, :title, :description,
         :original_name, :stored_name, :file_path, :file_size, 'pending')"
);

$stmt->execute([
    ':uploader_name'  => $uploaderName ?: null,
    ':uploader_login' => $uploaderLogin ?: null,
    ':semester'       => $semester,
    ':subject'        => $subject,
    ':title'          => $title,
    ':description'    => $description,
    ':original_name'  => $file['name'],
    ':stored_name'    => $storedName,
    ':file_path'      => 'uploads/' . $storedName,
    ':file_size'      => $file['size'],
]);

respond(true, 'File uploaded successfully. It is now pending approval.', ['file_id' => $pdo->lastInsertId()]);
