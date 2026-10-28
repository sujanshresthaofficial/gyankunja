<?php
// =====================================================
// php/upload.php — receives the "Upload a New Note" form from dashboard.html
// Must be logged in. Students' uploads wait as "pending"; a coordinator/admin's
// own uploads are approved straight away.
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

// --- Is the file simply too big for PHP's own limits? ---
// When that happens PHP throws away ALL form fields, so $_POST is empty. That used to show up
// as the confusing "Title, semester and subject are required." message.
if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    respond(false, 'This file is too big for the server settings (post_max_size = ' . ini_get('post_max_size')
        . ', upload_max_filesize = ' . ini_get('upload_max_filesize') . '). Choose a smaller file, or raise both values in php.ini.');
}

// --- Who is uploading? (from the login session, not from the browser) ---
$isStaff = is_staff_logged_in();
if (!$isStaff && !is_student_logged_in()) {
    http_response_code(401);
    respond(false, 'Please log in again to upload notes.');
}

if ($isStaff) {
    $uploaderName  = $_SESSION['coordinator_name'] ?? 'Coordinator';
    $uploaderLogin = null;
    $userId        = null;
} else {
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $u = $stmt->fetch();
    if (!$u) {
        http_response_code(401);
        respond(false, 'Your account was not found. Please log in again.');
    }
    $uploaderName  = $u['name'];
    $uploaderLogin = $u['email'];
    $userId        = (int) $u['id'];
}

// --- Read text fields ---
$title       = trim($_POST['title'] ?? '');
$semester    = trim($_POST['semester'] ?? '');
$subject     = trim($_POST['subject'] ?? '');
$category    = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($title === '')    respond(false, 'Please enter a note title.');
if ($semester === '') respond(false, 'Please choose a semester.');
if ($subject === '')  respond(false, 'Please choose a subject.');
if ($category === '') respond(false, 'Please choose a category.');
if (!in_array($semester, SEMESTERS, true))  respond(false, 'Unknown semester.');
if (!in_array($category, CATEGORIES, true)) respond(false, 'Unknown category.');
if (strlen($title) > 200 || strlen($subject) > 150) respond(false, 'Title or subject is too long.');

if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    respond(false, 'Please choose a file to upload.');
}

$file = $_FILES['file'];

// --- Validate upload ---
if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
    respond(false, 'This file is bigger than the server allows (upload_max_filesize = ' . ini_get('upload_max_filesize') . ').');
}
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

// --- Insert into database ---
$status = $isStaff ? 'approved' : 'pending';

$stmt = $pdo->prepare(
    "INSERT INTO files
        (uploader_name, uploader_login, user_id, semester, subject, category, title, description,
         original_name, stored_name, file_path, file_size, status, reviewed_by, reviewed_at)
     VALUES
        (:uploader_name, :uploader_login, :user_id, :semester, :subject, :category, :title, :description,
         :original_name, :stored_name, :file_path, :file_size, :status, :reviewed_by, :reviewed_at)"
);

$stmt->execute([
    ':uploader_name'  => $uploaderName,
    ':uploader_login' => $uploaderLogin,
    ':user_id'        => $userId,
    ':semester'       => $semester,
    ':subject'        => $subject,
    ':category'       => $category,
    ':title'          => $title,
    ':description'    => $description,
    ':original_name'  => $file['name'],
    ':stored_name'    => $storedName,
    ':file_path'      => 'uploads/' . $storedName,
    ':file_size'      => $file['size'],
    ':status'         => $status,
    ':reviewed_by'    => $isStaff ? $uploaderName : null,
    ':reviewed_at'    => $isStaff ? date('Y-m-d H:i:s') : null,
]);

respond(
    true,
    $isStaff
        ? 'File uploaded and published.'
        : 'File uploaded successfully. It is now pending approval.',
    ['file_id' => $pdo->lastInsertId(), 'status' => $status]
);