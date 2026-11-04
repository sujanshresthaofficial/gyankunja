<?php
// =====================================================
// php/list_files.php — PUBLIC JSON list of APPROVED notes
//
// GET list_files.php                               -> all approved notes
// GET list_files.php?semester=Semester III         -> one semester
// GET list_files.php?semester=...&subject=CIT 213  -> one subject (matched by course code)
// GET list_files.php?...&category=Syllabus         -> one category
//
// Only rows with status = 'approved' are ever returned. Uploader emails and
// server file paths are deliberately NOT exposed.
// =====================================================

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only GET requests are allowed.']);
    exit;
}

$where  = ["status = 'approved'"];
$params = [];

$semester = trim($_GET['semester'] ?? '');
if ($semester !== '') {
    $where[] = 'semester = :semester';
    $params[':semester'] = $semester;
}

$category = trim($_GET['category'] ?? '');
if ($category !== '') {
    $where[] = 'category = :category';
    $params[':category'] = $category;
}

// Subjects are stored as "CODE - Title" (e.g. "ELX 121 - Digital Logic"); the code is the part before " - ".
function subject_code(string $subject): string
{
    $code = explode(' - ', trim($subject), 2)[0];
    return strtoupper(preg_replace('/\s+/', '', $code));
}

try {
    $stmt = $pdo->prepare(
        "SELECT id, title, subject, category, semester, description, original_name,
                file_size, uploader_name, uploaded_at, reviewed_at
         FROM files
         WHERE " . implode(' AND ', $where) . "
         ORDER BY COALESCE(reviewed_at, uploaded_at) DESC, id DESC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load notes.']);
    exit;
}

$wantedSubject = trim($_GET['subject'] ?? '');
if ($wantedSubject !== '') {
    $wanted = subject_code($wantedSubject);
    $rows = array_values(array_filter($rows, fn($r) => subject_code($r['subject']) === $wanted));
}

$files = array_map(fn($r) => [
    'id'            => (int) $r['id'],
    'title'         => $r['title'],
    'subject'       => $r['subject'],
    'subject_code'  => subject_code($r['subject']),
    'category'      => $r['category'],
    'semester'      => $r['semester'],
    'description'   => $r['description'],
    'original_name' => $r['original_name'],
    'ext'           => strtolower(pathinfo($r['original_name'], PATHINFO_EXTENSION)),
    'file_size'     => (int) $r['file_size'],
    'uploader_name' => $r['uploader_name'],
    'uploaded_at'   => $r['uploaded_at'],
    'approved_at'   => $r['reviewed_at'],
], $rows);

echo json_encode(['success' => true, 'count' => count($files), 'files' => $files]);