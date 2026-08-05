<?php
// =====================================================
// php/coordinator_login.php — real session-based login
// =====================================================

require_once 'config.php';

// If already logged in, skip straight to the approval page
if (!empty($_SESSION['coordinator_id'])) {
    header('Location: approve.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, name, password_hash, role FROM coordinators WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $coordinator = $stmt->fetch();

        // password_verify checks the plain password against the stored hash.
        // We never store or compare plain-text passwords.
        if ($coordinator && password_verify($password, $coordinator['password_hash'])) {
            $_SESSION['coordinator_id']   = $coordinator['id'];
            $_SESSION['coordinator_name'] = $coordinator['name'];
            $_SESSION['coordinator_role'] = $coordinator['role'];

            header('Location: approve.php');
            exit;
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Coordinator Login — Gyankunja</title>
</head>
<body style="font-family: sans-serif; max-width: 420px; margin: 80px auto;">
    <h2>Coordinator / Admin Login</h2>

    <?php if ($error): ?>
        <p style="color:#c0392b"><strong><?= htmlspecialchars($error) ?></strong></p>
    <?php endif; ?>

    <form method="POST">
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
        <button type="submit" style="padding:10px 20px">Log In</button>
    </form>
</body>
</html>