<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
requireMethod('POST');

$data = readJsonBody();

$name = trim((string) ($data['name'] ?? ''));
$studentId = trim((string) ($data['studentId'] ?? ''));
$email = strtolower(trim((string) ($data['email'] ?? '')));
$program = trim((string) ($data['program'] ?? ''));
$semester = trim((string) ($data['semester'] ?? ''));
$password = (string) ($data['password'] ?? '');
$confirmPassword = (string) ($data['confirmPassword'] ?? '');

$allowedSemesters = [
    'Semester I',
    'Semester II',
    'Semester III',
    'Semester IV',
    'Semester V',
    'Semester VI',
    'Semester VII',
    'Semester VIII',
];

if (
    $name === '' ||
    $studentId === '' ||
    $email === '' ||
    $program === '' ||
    $semester === '' ||
    $password === '' ||
    $confirmPassword === ''
) {
    jsonResponse([
        'success' => false,
        'message' => 'Please complete all required fields.',
    ], 422);
}

if (mb_strlen($name) > 100 || mb_strlen($studentId) > 50) {
    jsonResponse([
        'success' => false,
        'message' => 'Name or University ID is too long.',
    ], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse([
        'success' => false,
        'message' => 'Please enter a valid email address.',
    ], 422);
}

if (!in_array($semester, $allowedSemesters, true)) {
    jsonResponse([
        'success' => false,
        'message' => 'Please select a valid semester.',
    ], 422);
}

if (strlen($password) < 6) {
    jsonResponse([
        'success' => false,
        'message' => 'Password must contain at least 6 characters.',
    ], 422);
}

if ($password !== $confirmPassword) {
    jsonResponse([
        'success' => false,
        'message' => 'Passwords do not match.',
    ], 422);
}

$duplicateQuery = $pdo->prepare(
    'SELECT id FROM users WHERE email = :email OR student_id = :student_id LIMIT 1'
);
$duplicateQuery->execute([
    'email' => $email,
    'student_id' => $studentId,
]);

if ($duplicateQuery->fetch()) {
    jsonResponse([
        'success' => false,
        'message' => 'An account already exists with this email or University ID.',
    ], 409);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$insert = $pdo->prepare(
    'INSERT INTO users
        (name, student_id, email, program, semester, password_hash)
     VALUES
        (:name, :student_id, :email, :program, :semester, :password_hash)'
);

try {
    $insert->execute([
        'name' => $name,
        'student_id' => $studentId,
        'email' => $email,
        'program' => $program,
        'semester' => $semester,
        'password_hash' => $passwordHash,
    ]);
} catch (PDOException $exception) {
    error_log('Gyankunja signup failed: ' . $exception->getMessage());

    jsonResponse([
        'success' => false,
        'message' => 'Unable to create the account.',
    ], 500);
}

jsonResponse([
    'success' => true,
    'message' => 'Account created successfully. You can now sign in.',
], 201);
