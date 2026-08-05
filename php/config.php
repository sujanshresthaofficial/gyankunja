<?php
// =====================================================
// php/config.php — database connection (WAMP defaults)
// =====================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'gyankunja');
define('DB_USER', 'root');   // WAMP default
define('DB_PASS', '');       // WAMP default — no password

// Physical f If it is saved dollar underscore cost U and if set only method dollar underscore post dollar direct dollar underscore dollar post dollar underscore post dollar underscore already authentication what are my name post save one let's say password passwordolder where files are stored.
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
