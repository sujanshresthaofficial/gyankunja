(() => {
  const container = document.querySelector('#approvedNotes');
  if (!container) return;

  const semesterNumber = Number(document.body.dataset.semester || 0);
  const semesters = [
    '',
    'Semester I',
    'Semester II',
    'Semester III',
    'Semester IV',
    'Semester V',
    'Semester VI',
    'Semester VII',
    'Semester VIII',
  ];
  const semester = semesters[semesterNumber];

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

  const load = async () => {
    container.innerHTML =
      '<div class="panel"><p class="muted">Loading faculty-approved notes…</p></div>';

    try {
      const response = await fetch(
        `api/notes/approved.php?semester=${encodeURIComponent(semester)}`,
        { cache: 'no-store' }
      );
      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Unable to load notes.');
      }

      if (!data.notes.length) {
        container.innerHTML =
          '<div class="panel"><h3>No approved notes yet</h3><p class="muted">Notes submitted by users will appear here only after faculty approval.</p></div>';
        return;
      }

      container.innerHTML = data.notes.map(note => `
        <article class="note-card">
          <div class="note-cover">
            <div class="file-icon">PDF</div>
            <span class="badge badge-green">Faculty Approved</span>
          </div>
          <div class="note-body">
            <h3>${escapeHtml(note.title)}</h3>
            <div class="note-meta">
              <span>${escapeHtml(note.subject)}</span>
              <span>• ${formatBytes(note.file_size)}</span>
            </div>
            <p class="muted">
              Uploaded by ${escapeHtml(note.uploader_name)}
            </p>
            <a class="btn btn-primary btn-sm"
               href="${escapeHtml(note.download_url)}"
               target="_blank"
               rel="noopener">Open PDF</a>
          </div>
        </article>
      `).join('');
    } catch (error) {
      container.innerHTML =
        `<div class="panel"><p>${escapeHtml(error.message)}</p></div>`;
    }
  };

  load();
  window.setInterval(load, 30000);
})();
