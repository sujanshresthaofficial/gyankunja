<?php
// =====================================================
// php/admin_coordinators.php — ADMIN ONLY: list / create / edit / delete coordinators & admins
//   GET                                   -> list
//   POST action=create  name,email,password,role
//   POST action=update  id,name,email,role,[password]
//   POST action=delete  id
// =====================================================

require_once 'config.php';
require_admin_json();

function admin_count(PDO $pdo): int {
    return (int) $pdo->query("SELECT COUNT(*) FROM coordinators WHERE role = 'admin'")->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $pdo->query("SELECT id, name, email, role, created_at FROM coordinators ORDER BY role ASC, name ASC")->fetchAll();
    json_out(['success' => true, 'me' => (int) $_SESSION['coordinator_id'], 'coordinators' => $rows]);
}

require_post();
$action = $_POST['action'] ?? '';
$id     = (int) ($_POST['id'] ?? 0);
$name   = trim($_POST['name'] ?? '');
$email  = trim($_POST['email'] ?? '');
$role   = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'coordinator';
$pass   = $_POST['password'] ?? '';

if ($action === 'create' || $action === 'update') {
    if ($name === '' || strlen($name) > 150) json_out(['success' => false, 'message' => 'Please enter a name.'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        json_out(['success' => false, 'message' => 'Please enter a valid email address.'], 400);
    }
    if ($action === 'create' || $pass !== '') {
        if (strlen($pass) < MIN_PASSWORD_LENGTH) {
            json_out(['success' => false, 'message' => 'Password must contain at least ' . MIN_PASSWORD_LENGTH . ' characters.'], 400);
        }
    }
}

if ($action === 'create') {
    if (email_in_use($pdo, $email)) json_out(['success' => false, 'message' => 'That email is already used by another account.'], 409);
    $pdo->prepare("INSERT INTO coordinators (name, email, password_hash, role) VALUES (:n, :e, :h, :r)")
        ->execute([':n' => $name, ':e' => $email, ':h' => password_hash($pass, PASSWORD_DEFAULT), ':r' => $role]);
    json_out(['success' => true, 'message' => ucfirst($role) . ' account created.']);
}

$stmt = $pdo->prepare("SELECT id, role FROM coordinators WHERE id = :id");
$stmt->execute([':id' => $id]);
$target = $stmt->fetch();
if (!$target) json_out(['success' => false, 'message' => 'Account not found.'], 404);

if ($action === 'update') {
    if (email_in_use($pdo, $email, 'coordinators', $id)) json_out(['success' => false, 'message' => 'That email is already used by another account.'], 409);
    if ($target['role'] === 'admin' && $role !== 'admin' && admin_count($pdo) <= 1) {
        json_out(['success' => false, 'message' => 'There must be at least one admin.'], 400);
    }
    $sets = ['name = :n', 'email = :e', 'role = :r'];
    $params = [':n' => $name, ':e' => $email, ':r' => $role, ':id' => $id];
    if ($pass !== '') { $sets[] = 'password_hash = :h'; $params[':h'] = password_hash($pass, PASSWORD_DEFAULT); }
    $pdo->prepare("UPDATE coordinators SET " . implode(', ', $sets) . " WHERE id = :id")->execute($params);
    if ($id === (int) $_SESSION['coordinator_id']) {
        $_SESSION['coordinator_name'] = $name;
        $_SESSION['coordinator_role'] = $role;
    }
    json_out(['success' => true, 'message' => 'Account updated.']);
}

if ($action === 'delete') {
    if ($id === (int) $_SESSION['coordinator_id']) json_out(['success' => false, 'message' => 'You cannot delete your own account.'], 400);
    if ($target['role'] === 'admin' && admin_count($pdo) <= 1) json_out(['success' => false, 'message' => 'There must be at least one admin.'], 400);
    $pdo->prepare("DELETE FROM coordinators WHERE id = :id")->execute([':id' => $id]);
    json_out(['success' => true, 'message' => 'Account deleted.']);
}

json_out(['success' => false, 'message' => 'Unknown action.'], 400);
