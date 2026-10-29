<?php
// =====================================================
// php/auth_signup.php — creates a student account in MySQL
//   POST name, email, studentId, program, semester, password, confirmPassword
// =====================================================

require_once 'config.php';
require_post();

$name      = trim($_POST['name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$studentId = trim($_POST['studentId'] ?? '');
$program   = trim($_POST['program'] ?? '');
$semester  = trim($_POST['semester'] ?? '');
$password  = $_POST['password'] ?? '';
$confirm   = $_POST['confirmPassword'] ?? '';

if ($name === '' || $email === '' || $studentId === '' || $program === '' || $semester === '' || $password === '') {
    json_out(['success' => false, 'message' => 'Please complete all required fields.'], 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    json_out(['success' => false, 'message' => 'Please enter a valid email address.'], 400);
}
if (!in_array($semester, SEMESTERS, true)) {
    json_out(['success' => false, 'message' => 'Please choose a valid semester.'], 400);
}
if (strlen($name) > 150 || strlen($studentId) > 60 || strlen($program) > 100) {
    json_out(['success' => false, 'message' => 'One of the fields is too long.'], 400);
}
if (strlen($password) < MIN_PASSWORD_LENGTH) {
    json_out(['success' => false, 'message' => 'Password must contain at least ' . MIN_PASSWORD_LENGTH . ' characters.'], 400);
}
if ($password !== $confirm) {
    json_out(['success' => false, 'message' => 'Passwords do not match.'], 400);
}

$dup = $pdo->prepare("SELECT id FROM users WHERE student_id = :s");
$dup->execute([':s' => $studentId]);
if ($dup->fetch() || email_in_use($pdo, $email)) {
    json_out(['success' => false, 'message' => 'An account already exists with this email or University ID.'], 409);
}

$stmt = $pdo->prepare(
    "INSERT INTO users (student_id, name, email, program, semester, password_hash)
     VALUES (:sid, :name, :email, :program, :semester, :hash)"
);
$stmt->execute([
    ':sid' => $studentId, ':name' => $name, ':email' => $email, ':program' => $program,
    ':semester' => $semester, ':hash' => password_hash($password, PASSWORD_DEFAULT),
]);

json_out(['success' => true, 'message' => "Account created for $semester. You can sign in now."]);
