<?php
// =====================================================
// php/admin_review.php — coordinator/admin: approve / reject / set back to pending / delete
//   POST id, action = approve|reject|pending|delete, remarks (optional)
// delete = admin only
// =====================================================

require_once 'config.php';
require_post();
require_staff_json();

$id     = (int) ($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

$stmt = $pdo->prepare("SELECT id, stored_name FROM files WHERE id = :id");
$stmt->execute([':id' => $id]);
$file = $stmt->fetch();
if (!$file) json_out(['success' => false, 'message' => 'File not found.'], 404);

if ($action === 'delete') {
    require_admin_json();
    $base = realpath(UPLOAD_DIR);
    $path = $base ? realpath($base . DIRECTORY_SEPARATOR . basename($file['stored_name'])) : false;
    if ($path && strpos($path, $base . DIRECTORY_SEPARATOR) === 0 && is_file($path)) {
        @unlink($path);
    }
    $pdo->prepare("DELETE FROM files WHERE id = :id")->execute([':id' => $id]);
    json_out(['success' => true, 'message' => 'File deleted.']);
}

$map = ['approve' => 'approved', 'reject' => 'rejected', 'pending' => 'pending'];
if (!isset($map[$action])) json_out(['success' => false, 'message' => 'Unknown action.'], 400);

$pdo->prepare(
    "UPDATE files SET status = :status, reviewed_by = :by, review_remarks = :remarks, reviewed_at = NOW() WHERE id = :id"
)->execute([
    ':status'  => $map[$action],
    ':by'      => $_SESSION['coordinator_name'],
    ':remarks' => trim($_POST['remarks'] ?? ''),
    ':id'      => $id,
]);

json_out(['success' => true, 'message' => 'Marked as ' . $map[$action] . '.']);
