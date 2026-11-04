<?php
// =====================================================
// php/my_files.php — the logged-in student's OWN uploads, with their review status
// =====================================================

require_once 'config.php';

if (!is_student_logged_in()) {
    json_out(['success' => false, 'message' => 'Please log in.'], 401);
}

$stmt = $pdo->prepare(
    "SELECT id, title, semester, subject, category, status, review_remarks, uploaded_at
     FROM files WHERE user_id = :id ORDER BY uploaded_at DESC, id DESC"
);
$stmt->execute([':id' => $_SESSION['user_id']]);

json_out(['success' => true, 'files' => $stmt->fetchAll()]);
