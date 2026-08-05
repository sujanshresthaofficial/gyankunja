<?php
// =====================================================
// php/create_coordinator.php
//
// ONE-TIME SETUP PAGE. Open this once in your browser,
// create your coordinator account, then DELETE this file
// (or move it outside the web root) so no one else can
// use it to create accounts.
// =====================================================

require_once 'config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] === 'admin' ? 'admin' : 'coordinator';

    if ($name === '' || $email === '' || $password === '') {
        $message = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
    } else {
        // Check if email already exists
        $check = $pdo->prepare("SELECT id FROM coordinators WHERE email = :email");
        $check->execute([':email' => $email]);

        if ($check->fetch()) {
            $message = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO coordinators (name, email, password_hash, role) VALUES (:name, :email, :hash, :role)"
            );
            $stmt->execute([
                ':name'  => $name,
                ':email' => $email,
                ':hash'  => $hash,
                ':role'  => $role,
            ]);

            $success = true;
            $message = 'Account created! You can now log in at coordinator_login.php. Please delete this file now.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Create Coordinator Account (one-time setup)</title>
</head>
<body style="font-family: sans-serif; max-width: 480px; margin: 60px auto;">
    <h2>Create Coordinator / Admin Account</h2>
    <p style="color:#c0392b"><strong>Delete this file after creating your account(s).</strong></p>

    <?php if ($message): ?>
        <p style="color: <?= $success ? 'green' : '#c0392b' ?>"><strong><?= htmlspecialchars($message) ?></strong></p>
    <?php endif; ?>

    <form method="POST">
        <p>
            <label>Full Name:<br>
                <input type="text" name="name" required style="width:100%;padding:8px">
            </label>
        </p>
        <p>
            <label>Email:<br>
                <input type="email" name="email" required style="width:100%;padding:8px">
            </label>
        </p>
        <p>
            <label>Password:<br>
                <input type="password" name="password" required style="width:100%;padding:8px">
            </label>
        </p>
        <p>
            <label>Role:<br>
                <select name="role" style="width:100%;padding:8px">
                    <option value="coordinator">Coordinator</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
        </p>
        <button type="submit" style="padding:10px 20px">Create Account</button>
    </form>
</body>
</html>