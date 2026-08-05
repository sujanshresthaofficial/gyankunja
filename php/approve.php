<?php
// =====================================================
// php/approve.php — protected review queue
// =====================================================

require_once 'config.php';
require_coordinator_login(); // redirects to login if not signed in

// Handle approve/reject action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_id'], $_POST['action'])) {
    $fileId = (int) $_POST['file_id'];
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $remarks = trim($_POST['remarks'] ?? '');

    $stmt = $pdo->prepare(
        "UPDATE files
         SET status = :status, reviewed_by = :reviewer, review_remarks = :remarks, reviewed_at = NOW()
         WHERE id = :id"
    );
    $stmt->execute([
        ':status'   => $action,
        ':reviewer' => $_SESSION['coordinator_name'],
        ':remarks'  => $remarks,
        ':id'       => $fileId,
    ]);
}

// Fetch all pending files
$pendingFiles = $pdo->query(
    "SELECT id, title, subject, semester, description, original_name, file_path, uploader_name, uploaded_at
     FROM files
     WHERE status = 'pending'
     ORDER BY uploaded_at ASC"
)->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Review Pending Uploads — Gyankunja</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 0 16px; }
        .file-card { border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        .file-card h3 { margin: 0 0 6px; }
        .meta { color: #666; font-size: 0.85rem; margin-bottom: 10px; }
        .actions input[type=text] { padding: 6px; width: 220px; }
        .actions button { padding: 6px 14px; margin-left: 6px; cursor: pointer; }
        .approve-btn { background: #2e7d32; color: white; border: none; border-radius: 4px; }
        .reject-btn { background: #c0392b; color: white; border: none; border-radius: 4px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>
    <div class="topbar">
        <h2>Pending Uploads (<?= count($pendingFiles) ?>)</h2>
        <div>
            Logged in as <strong><?= htmlspecialchars($_SESSION['coordinator_name']) ?></strong>
            (<?= htmlspecialchars($_SESSION['coordinator_role']) ?>)
            — <a href="coordinator_logout.php">Log out</a>
        </div>
    </div>

    <?php if (!$pendingFiles): ?>
        <p>Nothing waiting for review right now.</p>
    <?php endif; ?>

    <?php foreach ($pendingFiles as $f): ?>
        <div class="file-card">
            <h3><?= htmlspecialchars($f['title']) ?></h3>
            <div class="meta">
                <?= htmlspecialchars($f['semester']) ?> • <?= htmlspecialchars($f['subject']) ?>
                • Uploaded by <?= htmlspecialchars($f['uploader_name'] ?: 'Unknown') ?>
                • <?= htmlspecialchars($f['uploaded_at']) ?>
            </div>
            <p><?= htmlspecialchars($f['description']) ?></p>
            <p><a href="../<?= htmlspecialchars($f['file_path']) ?>" target="_blank">
                View file (<?= htmlspecialchars($f['original_name']) ?>)
            </a></p>

            <form method="POST" class="actions">
                <input type="hidden" name="file_id" value="<?= $f['id'] ?>">
                <input type="text" name="remarks" placeholder="Optional remarks">
                <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
            </form>
        </div>
    <?php endforeach; ?>
</body>
</html>