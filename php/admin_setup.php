<?php
// =====================================================
// php/admin_setup.php — creates the FIRST admin account.
// It only works while there is NO admin in the database; after that it
// refuses to do anything, so it is safe to leave on the server.
// =====================================================

require_once 'config.php';

$adminExists = (int) $pdo->query("SELECT COUNT(*) FROM coordinators WHERE role = 'admin'")->fetchColumn() > 0;
$message = '';
$done = false;

if (!$adminExists && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter your name and a valid email.';
    } elseif (strlen($pass) < MIN_PASSWORD_LENGTH) {
        $message = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters.';
    } elseif (email_in_use($pdo, $email)) {
        $message = 'That email is already used by another account.';
    } else {
        $pdo->prepare("INSERT INTO coordinators (name, email, password_hash, role) VALUES (:n, :e, :h, 'admin')")
            ->execute([':n' => $name, ':e' => $email, ':h' => password_hash($pass, PASSWORD_DEFAULT)]);
        $done = true;
        $adminExists = true;
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>First admin setup — Gyankunja</title></head>
<body style="font-family: sans-serif; max-width: 460px; margin: 60px auto; padding: 0 16px;">
    <h2>Gyankunja — first admin setup</h2>
    <?php if ($done): ?>
        <p style="color:green"><strong>Admin account created.</strong> <a href="../login.html">Go to the login page</a> and sign in with it.</p>
    <?php elseif ($adminExists): ?>
        <p>An admin account already exists, so this page is switched off. <a href="../login.html">Go to the login page</a>.</p>
    <?php else: ?>
        <?php if ($message): ?><p style="color:#c0392b"><strong><?= htmlspecialchars($message) ?></strong></p><?php endif; ?>
        <form method="POST">
            <p><label>Full name<br><input name="name" required style="width:100%;padding:8px"></label></p>
            <p><label>Email<br><input type="email" name="email" required style="width:100%;padding:8px"></label></p>
            <p><label>Password<br><input type="password" name="password" required style="width:100%;padding:8px"></label></p>
            <button type="submit" style="padding:10px 20px">Create admin</button>
        </form>
    <?php endif; ?>
</body>
</html>
