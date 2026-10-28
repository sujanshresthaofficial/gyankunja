<?php
// =====================================================
// php/auth_login.php — ONE login for students AND coordinators/admins
//   POST identifier (student ID or email), password, remember
// =====================================================

require_once 'config.php';
require_post();

$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';
$remember   = !empty($_POST['remember']);

if ($identifier === '' || $password === '') {
    json_out(['success' => false, 'message' => 'Please enter your login ID or email and password.'], 400);
}

function start_login_session(bool $remember): void {
    $_SESSION = [];
    session_regenerate_id(true);
    if ($remember) { // keep the cookie for 30 days instead of until the browser closes
        setcookie(session_name(), session_id(), [
            'expires' => time() + 60 * 60 * 24 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        ]);
    }
}

// 1) Student? (matches University ID or email)
$stmt = $pdo->prepare(
    "SELECT id, student_id, name, email, program, semester, password_hash
     FROM users WHERE email = :a OR student_id = :b LIMIT 1"
);
$stmt->execute([':a' => $identifier, ':b' => $identifier]);
$student = $stmt->fetch();

if ($student && password_verify($password, $student['password_hash'])) {
    start_login_session($remember);
    $_SESSION['user_id'] = (int) $student['id'];
    json_out([
        'success'  => true,
        'role'     => 'student',
        'redirect' => 'dashboard.html',
        'user'     => [
            'name' => $student['name'], 'email' => $student['email'], 'studentId' => $student['student_id'],
            'program' => $student['program'], 'semester' => $student['semester'], 'role' => 'student',
        ],
    ]);
}

// 2) Coordinator / admin? (email only)
$stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM coordinators WHERE email = :e LIMIT 1");
$stmt->execute([':e' => $identifier]);
$staff = $stmt->fetch();

if ($staff && password_verify($password, $staff['password_hash'])) {
    start_login_session($remember);
    $_SESSION['coordinator_id']   = (int) $staff['id'];
    $_SESSION['coordinator_name'] = $staff['name'];
    $_SESSION['coordinator_role'] = $staff['role'];
    json_out([
        'success'  => true,
        'role'     => $staff['role'],
        'redirect' => 'admin.html',
        'user'     => ['name' => $staff['name'], 'email' => $staff['email'], 'role' => $staff['role']],
    ]);
}

usleep(400000); // slow down password guessing a little
json_out(['success' => false, 'message' => 'Invalid login ID/email or password.'], 401);
