<?php
// =====================================================
// php/admin_files.php — coordinator/admin: list student uploads
//   GET status=pending|approved|rejected|all   [semester=...]  [q=search text]
// =====================================================

require_once 'config.php';
require_staff_json();

$status = $_GET['status'] ?? 'pending';
$where  = [];
$params = [];

if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}

$semester = trim($_GET['semester'] ?? '');
if ($semester !== '') {
    $where[] = 'semester = :semester';
    $params[':semester'] = $semester;
}

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $where[] = '(title LIKE :q1 OR subject LIKE :q2 OR uploader_name LIKE :q3 OR original_name LIKE :q4)';
    foreach (['q1', 'q2', 'q3', 'q4'] as $k) $params[":$k"] = '%' . $q . '%';
}

$sql = "SELECT id, title, subject, category, semester, description, original_name, file_size,
               uploader_name, uploader_login, status, reviewed_by, review_remarks, reviewed_at, uploaded_at
        FROM files" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . "
        ORDER BY uploaded_at DESC, id DESC LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($pdo->query("SELECT status, COUNT(*) AS n FROM files GROUP BY status") as $row) {
    $counts[$row['status']] = (int) $row['n'];
}

json_out(['success' => true, 'counts' => $counts, 'files' => $stmt->fetchAll()]);
