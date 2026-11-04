<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
requireMethod('POST');

$data = readJsonBody();

$identifier = strtolower(trim((string) ($data['identifier'] ?? '')));
$password = (string) ($data['password'] ?? '');
$remember = filter_var($data['remember'] ?? false, FILTER_VALIDATE_BOOLEAN);

if ($identifier === '' || $password === '') {
    jsonResponse([
        'success' => false,
        'message' => 'Please enter your login ID or email and password.',
    ], 422);
}

$query = $pdo->prepare(
    'SELECT
        id,
        name,
        student_id,
        email,
        program,
        semester,
        role,
        status,
        password_hash
     FROM users
     WHERE LOWER(email) = :identifier_email
        OR LOWER(student_id) = :identifier_student
     LIMIT 1'
);

$query->execute([
    'identifier_email' => $identifier,
    'identifier_student' => $identifier,
]);

$user = $query->fetch();

if (
    !$user ||
    (int) $user['status'] !== 1 ||
    !password_verify($password, (string) $user['password_hash'])
) {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid login ID/email or password.',
    ], 401);
}

session_regenerate_id(true);
$_SESSION['user'] = publicUser($user);

if ($remember) {
    $cookieOptions = [
        'expires' => time() + (30 * 24 * 60 * 60),
        'path' => '/',
        'domain' => '',
        'secure' => (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        ),
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    setcookie(session_name(), session_id(), $cookieOptions);
}

jsonResponse([
    'success' => true,
    'message' => 'Login successful.',
    'user' => $_SESSION['user'],
]);
