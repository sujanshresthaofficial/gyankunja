<?php
// =====================================================
// php/auth_me.php — who is logged in right now? (read from the server session)
// =====================================================

require_once 'config.php';

if (is_student_logged_in()) {
    $stmt = $pdo->prepare("SELECT student_id, name, email, program, semester FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $u = $stmt->fetch();
    if ($u) {
        json_out(['loggedIn' => true, 'role' => 'student', 'user' => [
            'name' => $u['name'], 'email' => $u['email'], 'studentId' => $u['student_id'],
            'program' => $u['program'], 'semester' => $u['semester'], 'role' => 'student',
        ]]);
    }
}

if (is_staff_logged_in()) {
    $stmt = $pdo->prepare("SELECT name, email, role FROM coordinators WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['coordinator_id']]);
    $c = $stmt->fetch();
    if ($c) {
        $_SESSION['coordinator_name'] = $c['name'];
        $_SESSION['coordinator_role'] = $c['role'];
        json_out(['loggedIn' => true, 'role' => $c['role'], 'user' => [
            'name' => $c['name'], 'email' => $c['email'], 'role' => $c['role'],
        ]]);
    }
}

// Account was deleted or nobody is logged in
$_SESSION = [];
json_out(['loggedIn' => false]);
