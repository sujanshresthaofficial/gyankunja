<?php
declare(strict_types=1);

define('GYANKUNJA_HTML_PAGE', true);
require __DIR__ . '/api/bootstrap.php';

header('Content-Type: text/html; charset=utf-8', true);

$sessionUser = $_SESSION['user'] ?? null;
$role = is_array($sessionUser) ? (string) ($sessionUser['role'] ?? 'student') : '';

if (!is_array($sessionUser)) {
    header('Location: login.html?required=1');
    exit;
}

if (!in_array($role, ['faculty', 'admin'], true)) {
    http_response_code(403);
    exit('Faculty access only.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Faculty Note Review — Gyankunja</title>
<link href="images/gyankunja-icon.png?v=brand-2" rel="icon" type="image/png">
<link href="css/styles.css?v=brand-2" rel="stylesheet">
<style>
.review-shell{max-width:1200px;margin:0 auto;padding:32px 20px}
.review-head{display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:24px}
.review-filter{display:flex;gap:10px;flex-wrap:wrap}
.review-grid{display:grid;gap:16px}
.review-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px}
.review-meta{display:flex;gap:12px;flex-wrap:wrap;color:var(--muted);font-size:.85rem;margin:8px 0}
.review-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
.review-comment{width:100%;min-height:80px;margin-top:14px}
.status-pending{color:#9a6700}.status-approved{color:#137333}.status-rejected{color:#b3261e}
@media(max-width:700px){.review-head{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<header class="site-header">
<div class="container navbar">
<a class="brand brand-image" href="dashboard.php" aria-label="Gyankunja home"><img class="brand-logo brand-logo-light" src="images/gyankunja-logo.png?v=brand-2" alt="Gyankunja" width="400" height="114"/><img class="brand-logo brand-logo-dark" src="images/gyankunja-logo-dark.png?v=brand-2" alt="Gyankunja" width="400" height="114"/></a>
<div class="nav-actions">
<a class="btn btn-outline btn-sm" href="dashboard.php">Dashboard</a>
<button class="btn btn-primary btn-sm" id="logoutButton">Logout</button>
</div>
</div>
</header>

<main class="review-shell">
<div class="review-head">
<div>
<span class="eyebrow">Faculty moderation</span>
<h1>Review Uploaded Notes</h1>
<p class="muted">Only approved notes become visible on semester pages.</p>
</div>
<div class="review-filter">
<button class="btn btn-primary btn-sm" data-status="pending">Pending</button>
<button class="btn btn-outline btn-sm" data-status="approved">Approved</button>
<button class="btn btn-outline btn-sm" data-status="rejected">Rejected</button>
<button class="btn btn-outline btn-sm" data-status="all">All</button>
</div>
</div>

<div id="reviewMessage"></div>
<div class="review-grid" id="reviewList"></div>
</main>

<script>
(() => {
  const list = document.querySelector('#reviewList');
  const message = document.querySelector('#reviewMessage');
  let currentStatus = 'pending';

  const escapeHtml = value =>
    String(value ?? '').replace(/[&<>"']/g, character => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    })[character]);

  const formatBytes = bytes => {
    const size = Number(bytes || 0);
    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
  };

  const request = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      ...options,
    });
    const data = await response.json();

    if (!response.ok || !data.success) {
      throw new Error(data.message || 'The request failed.');
    }

    return data;
  };

  const showMessage = (text, success = false) => {
    message.innerHTML =
      `<div class="alert show ${success ? 'alert-success' : 'alert-danger'}">${escapeHtml(text)}</div>`;
  };

  const load = async () => {
    list.innerHTML = '<div class="panel"><p class="muted">Loading notes…</p></div>';

    try {
      const data = await request(
        `api/faculty/pending.php?status=${encodeURIComponent(currentStatus)}`
      );

      if (!data.notes.length) {
        list.innerHTML =
          '<div class="panel"><h3>No notes found</h3><p class="muted">There are no notes in this review category.</p></div>';
        return;
      }

      list.innerHTML = data.notes.map(note => `
        <article class="review-card" data-note-id="${note.id}">
          <div>
            <span class="badge ${note.approval_status === 'approved' ? 'badge-green' : 'badge-blue'}">
              ${escapeHtml(note.approval_status.toUpperCase())}
            </span>
            <h2>${escapeHtml(note.title)}</h2>
            <div class="review-meta">
              <span>${escapeHtml(note.subject)}</span>
              <span>${escapeHtml(note.semester)}</span>
              <span>${formatBytes(note.file_size)}</span>
              <span>Uploaded by ${escapeHtml(note.uploader_name)}</span>
            </div>
            <p>${escapeHtml(note.description || 'No description provided.')}</p>
            ${note.review_comment ? `<p><strong>Review comment:</strong> ${escapeHtml(note.review_comment)}</p>` : ''}
          </div>
          <div class="review-actions">
            <a class="btn btn-outline btn-sm"
               href="${escapeHtml(note.preview_url)}"
               target="_blank"
               rel="noopener">Preview PDF</a>
            ${note.approval_status === 'pending' ? `
              <button class="btn btn-primary btn-sm" data-action="approve">Approve</button>
              <button class="btn btn-outline btn-sm" data-action="reject">Reject</button>
            ` : ''}
          </div>
          ${note.approval_status === 'pending' ? `
            <textarea class="form-control review-comment"
              placeholder="Optional approval comment; required when rejecting"></textarea>
          ` : ''}
        </article>
      `).join('');
    } catch (error) {
      list.innerHTML = '';
      showMessage(error.message);
    }
  };

  document.querySelectorAll('[data-status]').forEach(button => {
    button.addEventListener('click', () => {
      currentStatus = button.dataset.status;

      document.querySelectorAll('[data-status]').forEach(item => {
        item.className = item === button
          ? 'btn btn-primary btn-sm'
          : 'btn btn-outline btn-sm';
      });

      load();
    });
  });

  list.addEventListener('click', async event => {
    const actionButton = event.target.closest('[data-action]');
    if (!actionButton) return;

    const card = actionButton.closest('[data-note-id]');
    const noteId = Number(card.dataset.noteId);
    const action = actionButton.dataset.action;
    const comment = card.querySelector('.review-comment')?.value.trim() || '';

    actionButton.disabled = true;

    try {
      const data = await request('api/faculty/review.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({noteId, action, comment}),
      });

      showMessage(data.message, true);
      await load();
    } catch (error) {
      showMessage(error.message);
      actionButton.disabled = false;
    }
  });

  document.querySelector('#logoutButton').addEventListener('click', async () => {
    await fetch('api/logout.php', {
      method: 'POST',
      credentials: 'same-origin',
    });
    localStorage.removeItem('gyankunjaAuth');
    localStorage.removeItem('gyankunjaUser');
    sessionStorage.removeItem('gyankunjaAuth');
    sessionStorage.removeItem('gyankunjaUser');
    location.href = 'login.html';
  });

  load();
})();
</script>
</body>
</html>
