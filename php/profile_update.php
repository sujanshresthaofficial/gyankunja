<?php
// =====================================================
// php/profile_update.php — a logged-in student OR coordinator/admin edits their OWN details
//   POST name, email, [program, semester — students only], [current_password + new_password]
// =====================================================

require_once 'config.php';
require_post();

$isStudent = is_student_logged_in();
if (!$isStudent && !is_staff_logged_in()) {
    json_out(['success' => false, 'message' => 'Please log in first.'], 401);
}

$table = $isStudent ? 'users' : 'coordinators';
$id    = (int) ($isStudent ? $_SESSION['user_id'] : $_SESSION['coordinator_id']);

$stmt = $pdo->prepare("SELECT * FROM $table WHERE id = :id");
$stmt->execute([':id' => $id]);
$me = $stmt->fetch();
if (!$me) json_out(['success' => false, 'message' => 'Account not found.'], 404);

$name  = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
if ($name === '' || strlen($name) > 150) json_out(['success' => false, 'message' => 'Please enter your name.'], 400);
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    json_out(['success' => false, 'message' => 'Please enter a valid email address.'], 400);
}
if (email_in_use($pdo, $email, $table, $id)) {
    json_out(['success' => false, 'message' => 'That email is already used by another account.'], 409);
}

$fields = [':name' => $name, ':email' => $email, ':id' => $id];
$sets   = ['name = :name', 'email = :email'];

if ($isStudent) {
    $program  = trim($_POST['program'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    if ($program === '' || strlen($program) > 100) json_out(['success' => false, 'message' => 'Please choose your program.'], 400);
    if (!in_array($semester, SEMESTERS, true)) json_out(['success' => false, 'message' => 'Please choose a valid semester.'], 400);
    $sets[] = 'program = :program';   $fields[':program']  = $program;
    $sets[] = 'semester = :semester'; $fields[':semester'] = $semester;
    $sets[] = 'updated_at = NOW()';
}

$newPassword = $_POST['new_password'] ?? '';
if ($newPassword !== '') {
    if (!password_verify($_POST['current_password'] ?? '', $me['password_hash'])) {
        json_out(['success' => false, 'message' => 'Your current password is incorrect.'], 403);
    }
    if (strlen($newPassword) < MIN_PASSWORD_LENGTH) {
        json_out(['success' => false, 'message' => 'New password must contain at least ' . MIN_PASSWORD_LENGTH . ' characters.'], 400);
    }
    $sets[] = 'password_hash = :hash';
    $fields[':hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
}

$pdo->prepare("UPDATE $table SET " . implode(', ', $sets) . " WHERE id = :id")->execute($fields);

if (!$isStudent) $_SESSION['coordinator_name'] = $name;

$user = $isStudent
    ? ['name' => $name, 'email' => $email, 'studentId' => $me['student_id'], 'program' => $fields[':program'], 'semester' => $fields[':semester'], 'role' => 'student']
    : ['name' => $name, 'email' => $email, 'role' => $me['role']];

json_out(['success' => true, 'message' => 'Profile updated.', 'user' => $user]);
