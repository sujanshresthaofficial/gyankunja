<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function requireAuthenticatedUser(): array
{
    $sessionUser = $_SESSION['user'] ?? null;

    if (!is_array($sessionUser) || empty($sessionUser['id'])) {
        jsonResponse([
            'success' => false,
            'message' => 'Please sign in first.',
        ], 401);
    }

    global $pdo;

    $query = $pdo->prepare(
        'SELECT id, name, student_id, email, program, semester, role, status
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $query->execute(['id' => (int) $sessionUser['id']]);
    $user = $query->fetch();

    if (!$user || (int) $user['status'] !== 1) {
        $_SESSION = [];
        session_destroy();

        jsonResponse([
            'success' => false,
            'message' => 'Your account is unavailable.',
        ], 401);
    }

    $_SESSION['user'] = publicUser($user);

    return $user;
}

function requireAnyRole(array $allowedRoles): array
{
    $user = requireAuthenticatedUser();
    $role = (string) ($user['role'] ?? 'student');

    if (!in_array($role, $allowedRoles, true)) {
        jsonResponse([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }

    return $user;
}
