<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
requireMethod('GET');

if (
    empty($_SESSION['user']) ||
    empty($_SESSION['user']['id'])
) {
    jsonResponse([
        'success' => true,
        'authenticated' => false,
        'user' => null,
    ]);
}

$userId = (int) $_SESSION['user']['id'];

$query = $pdo->prepare(
    'SELECT
        id,
        name,
        student_id,
        email,
        program,
        semester,
        role,
        status
     FROM users
     WHERE id = :id
     LIMIT 1'
);

$query->execute([
    'id' => $userId,
]);

$user = $query->fetch();

if (!$user || (int) $user['status'] !== 1) {
    $_SESSION = [];
    session_destroy();

    jsonResponse([
        'success' => true,
        'authenticated' => false,
        'user' => null,
    ]);
}

$_SESSION['user'] = publicUser($user);

jsonResponse([
    'success' => true,
    'authenticated' => true,
    'user' => $_SESSION['user'],
]);