<?php
// =====================================================
// php/config.php — database connection (WAMP defaults)
// =====================================================

// Start the session on every page that includes this file.
// This is what lets coordinator_login.php "remember" who is
// logged in across page loads (approve.php checks this).
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', (string) (60 * 60 * 24 * 30)); // keep logins up to 30 days
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_start();
}
define('DB_HOST', 'localhost');
define('DB_NAME', 'gyankunja');
define('DB_USER', 'root');
define('DB_PASS', '');

// __DIR__ = .../gyankunja/php, so ../uploads/ = .../gyankunja/uploads/
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

define('MAX_FILE_SIZE', 25 * 1024 * 1024); // 25 MB
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

// Call this at the top of any page that only coordinators/admins should be able to open. It sends anyone not logged in back to the coordinator login page.
function require_coordinator_login()
{
    if (empty($_SESSION['coordinator_id'])) {
        header('Location: coordinator_login.php');
        exit;
    }
}

define('CATEGORIES', ['Syllabus', 'Theory Notes', 'Lab Manuals', 'Question Bank', 'Books', 'References']);
define('SEMESTERS', ['Semester I', 'Semester II', 'Semester III', 'Semester IV', 'Semester V', 'Semester VI', 'Semester VII', 'Semester VIII']);
define('MIN_PASSWORD_LENGTH', 6);

function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data);
    exit;
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['success' => false, 'message' => 'Only POST requests are allowed.'], 405);
    }
}

function is_student_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}
function is_staff_logged_in(): bool
{
    return !empty($_SESSION['coordinator_id']);
}
function is_admin_logged_in(): bool
{
    return is_staff_logged_in() && ($_SESSION['coordinator_role'] ?? '') === 'admin';
}

function require_staff_json(): void
{
    if (!is_staff_logged_in()) json_out(['success' => false, 'message' => 'Please log in as a coordinator or admin.'], 401);
}
function require_admin_json(): void
{
    require_staff_json();
    if (!is_admin_logged_in()) json_out(['success' => false, 'message' => 'Only an admin can do this.'], 403);
}

// True if this email is already used by a student OR a coordinator/admin.
function email_in_use(PDO $pdo, string $email, string $ignoreTable = '', int $ignoreId = 0): bool
{
    foreach (['users', 'coordinators'] as $table) {
        $sql = "SELECT id FROM $table WHERE email = :e" . ($ignoreTable === $table ? ' AND id <> :id' : '');
        $params = [':e' => $email];
        if ($ignoreTable === $table) $params[':id'] = $ignoreId;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) return true;
    }
    return false;
}
