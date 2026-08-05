<?php
// =====================================================
// php/config.php — database connection (WAMP defaults)
// =====================================================

// Start the session on every page that includes this file.
// This is what lets coordinator_login.php "remember" who is
// logged in across page loads (approve.php checks this).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'gyankunja');
define('DB_USER', 'root');   // WAMP default
define('DB_PASS', '');       // WAMP default — no password

// Physical folder where files are stored.
// __DIR__ = .../gyankunja/php, so ../uploads/ = .../gyankunja/uploads/
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png']);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

// Call this at the top of any page that only coordinators/admins
// should be able to open. It sends anyone not logged in back to
// the coordinator login page.
function require_coordinator_login() {
    if (empty($_SESSION['coordinator_id'])) {
        header('Location: coordinator_login.php');
        exit;
    }
}