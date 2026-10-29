// =====================================================
// js/admin.js — admin / coordinator dashboard
// Talks to: php/auth_me.php, admin_files.php, admin_review.php, admin_coordinators.php, profile_update.php
// =====================================================
(async () => {
  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => Array.from(document.querySelectorAll(sel));
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, ch => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]
  ));

  const alertBox = $('#adminAlert');
  const notify = (message, type = 'success') => {
    alertBox.className = `alert show alert-${type}`;
    alertBox.textContent = message;
    clearTimeout(notify.t);
    notify.t = setTimeout(() => { alertBox.className = 'alert'; }, 4000);
  };

  const api = async (url, fields) => {
    const options = fields ? { method: 'POST', body: new URLSearchParams(fields) } : { cache: 'no-store' };
    const res = await fetch(url, options);
    let data = null;
    try { data = await res.json(); } catch { /* not JSON */ }
    if (res.status === 401) { Gyankunja.logout(true); throw new Error('Session expired'); }
    if (!data) throw new Error('Unexpected server answer');
    return data;
  };

  // ---------- who am I? ----------
  let me;
  try {
    me = await api('php/auth_me.php');
  } catch { me = null; }
  if (!me || !me.loggedIn || me.role === 'student') {
    sessionStorage.setItem('gyankunjaRedirect', 'admin.html');
    location.replace('login.html?required=1');
    return;
  }
  const isAdmin = me.role === 'admin';
  $('#adminName').textContent = me.user.name;
  $('#adminRole').textContent = me.role;
  if (isAdmin) $('#tabCoordinators').hidden = false;

  // ---------- tabs ----------
  const showTab = (name) => {
    $$('.admin-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === name));
    $$('.admin-panel').forEach(p => { p.hidden = p.dataset.panel !== name; });
    if (name === 'coordinators') loadStaff();
    if (name === 'account') fillAccount();
  };
  $$('.admin-tab').forEach(t => t.addEventListener('click', () => showTab(t.dataset.tab)));

  // ---------- uploads ----------
  let status = 'pending';
  const fmtSize = (b) => b >= 1048576 ? `${(b / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(b / 1024))} KB`;

  const fileCard = (f) => `
    <article class="file-card-admin" data-id="${f.id}">
      <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap">
        <h3>${esc(f.title)}</h3>
        <span class="status-pill status-${esc(f.status)}">${esc(f.status)}</span>
      </div>
      <div class="file-meta">
        <span class="tag">${esc(f.semester)}</span><span class="tag">${esc(f.category)}</span>
        ${esc(f.subject)} · ${esc(f.original_name)} (${fmtSize(f.file_size)})<br>
        Uploaded by <strong>${esc(f.uploader_name || 'Unknown')}</strong>${f.uploader_login ? ` &lt;${esc(f.uploader_login)}&gt;` : ''} · ${esc(f.uploaded_at)}
      </div>
      ${f.description ? `<p class="file-desc">${esc(f.description)}</p>` : ''}
      ${f.reviewed_by ? `<div class="review-line">Reviewed by ${esc(f.reviewed_by)} · ${esc(f.reviewed_at || '')}${f.review_remarks ? ` — “${esc(f.review_remarks)}”` : ''}</div>` : ''}
      <div class="file-actions">
        <a class="btn btn-outline btn-sm" href="php/download.php?id=${f.id}" rel="noopener" target="_blank">View file</a>
        <input maxlength="200" placeholder="Remarks (optional)" type="text" />
        ${f.status !== 'approved' ? '<button class="btn btn-approve btn-sm" data-act="approve" type="button">Approve</button>' : ''}
        ${f.status !== 'rejected' ? '<button class="btn btn-reject btn-sm" data-act="reject" type="button">Reject</button>' : ''}
        ${f.status !== 'pending' ? '<button class="btn btn-outline btn-sm" data-act="pending" type="button">Set to pending</button>' : ''}
        ${isAdmin ? '<button class="btn btn-danger-outline btn-sm" data-act="delete" type="button">Delete</button>' : ''}
      </div>
    </article>`;

  const loadFiles = async () => {
    const params = new URLSearchParams({ status, semester: $('#filterSemester').value, q: $('#filterSearch').value.trim() });
    try {
      const data = await api(`php/admin_files.php?${params}`);
      if (!data.success) throw new Error(data.message);
      $('#cPending').textContent = data.counts.pending;
      $('#cApproved').textContent = data.counts.approved;
      $('#cRejected').textContent = data.counts.rejected;
      const badge = $('#pendingBadge');
      badge.hidden = !data.counts.pending;
      badge.textContent = data.counts.pending;
      $('#fileList').innerHTML = data.files.length
        ? data.files.map(fileCard).join('')
        : `<div class="admin-empty">No ${status === 'all' ? '' : esc(status) + ' '}uploads${$('#filterSearch').value || $('#filterSemester').value ? ' match these filters' : ' here'}.</div>`;
    } catch (e) {
      notify(e.message || 'Could not load uploads.', 'danger');
    }
  };

  $$('#statusChips .chip').forEach(chip => chip.addEventListener('click', () => {
    status = chip.dataset.status;
    $$('#statusChips .chip').forEach(c => c.classList.toggle('active', c === chip));
    loadFiles();
  }));
  $('#filterSemester').addEventListener('change', loadFiles);
  let searchTimer;
  $('#filterSearch').addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(loadFiles, 300); });

  $('#fileList').addEventListener('click', async (event) => {
    const btn = event.target.closest('[data-act]');
    if (!btn) return;
    const card = btn.closest('.file-card-admin');
    const act = btn.dataset.act;
    if (act === 'delete' && !confirm('Delete this file permanently? This cannot be undone.')) return;
    btn.disabled = true;
    try {
      const data = await api('php/admin_review.php', {
        id: card.dataset.id, action: act, remarks: card.querySelector('input[type=text]').value
      });
      notify(data.message, data.success ? 'success' : 'danger');
      if (data.success) loadFiles();
      else btn.disabled = false;
    } catch (e) {
      notify(e.message, 'danger');
      btn.disabled = false;
    }
  });

  // ---------- coordinators & admins (admin only) ----------
  const staffForm = $('#staffForm');
  let staffList = [];

  const loadStaff = async () => {
    if (!isAdmin) return;
    try {
      const data = await api('php/admin_coordinators.php');
      if (!data.success) throw new Error(data.message);
      staffList = data.coordinators;
      $('#staffRows').innerHTML = staffList.map(s => `
        <tr data-id="${s.id}">
          <td>${esc(s.name)}${s.id === data.me ? ' <span class="muted">(you)</span>' : ''}</td>
          <td>${esc(s.email)}</td>
          <td><span class="role-badge">${esc(s.role)}</span></td>
          <td>${esc(String(s.created_at || '').slice(0, 10))}</td>
          <td><button class="btn btn-outline btn-sm" data-edit="${s.id}" type="button">Edit</button>
              ${s.id === data.me ? '' : `<button class="btn btn-danger-outline btn-sm" data-del="${s.id}" type="button">Delete</button>`}</td>
        </tr>`).join('');
    } catch (e) { notify(e.message, 'danger'); }
  };

  const openStaffForm = (s) => {
    staffForm.hidden = false;
    staffForm.reset();
    staffForm.id.value = s ? s.id : '';
    $('#staffFormTitle').textContent = s ? `Edit ${s.name}` : 'Add account';
    $('#staffPasswordLabel').textContent = s ? 'New password (leave empty to keep)' : 'Password';
    $('#staffPassword').required = !s;
    if (s) { staffForm.name.value = s.name; staffForm.email.value = s.email; staffForm.role.value = s.role; }
    staffForm.name.focus();
  };
  $('#newStaffBtn').addEventListener('click', () => openStaffForm(null));
  $('#staffCancel').addEventListener('click', () => { staffForm.hidden = true; });

  $('#staffRows').addEventListener('click', async (event) => {
    const edit = event.target.closest('[data-edit]');
    const del = event.target.closest('[data-del]');
    if (edit) openStaffForm(staffList.find(s => String(s.id) === edit.dataset.edit));
    if (del) {
      const s = staffList.find(x => String(x.id) === del.dataset.del);
      if (!confirm(`Delete the account of ${s.name}?`)) return;
      try {
        const data = await api('php/admin_coordinators.php', { action: 'delete', id: s.id });
        notify(data.message, data.success ? 'success' : 'danger');
        if (data.success) loadStaff();
      } catch (e) { notify(e.message, 'danger'); }
    }
  });

  staffForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const f = Object.fromEntries(new FormData(staffForm).entries());
    f.action = f.id ? 'update' : 'create';
    try {
      const data = await api('php/admin_coordinators.php', f);
      notify(data.message, data.success ? 'success' : 'danger');
      if (data.success) { staffForm.hidden = true; loadStaff(); }
    } catch (e) { notify(e.message, 'danger'); }
  });

  // ---------- my account ----------
  const fillAccount = () => {
    $('#accName').value = me.user.name;
    $('#accEmail').value = me.user.email;
    $('#accCurrent').value = '';
    $('#accNew').value = '';
  };
  $('#accountForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const f = Object.fromEntries(new FormData(event.target).entries());
    try {
      const data = await api('php/profile_update.php', f);
      notify(data.message, data.success ? 'success' : 'danger');
      if (data.success) {
        me.user = data.user;
        $('#adminName').textContent = data.user.name;
        try {
          const s = localStorage.getItem('gyankunjaAuth') ? localStorage : sessionStorage;
          s.setItem('gyankunjaUser', JSON.stringify(data.user));
        } catch { /* ignore */ }
        fillAccount();
      }
    } catch (e) { notify(e.message, 'danger'); }
  });

  loadFiles();
  setInterval(() => { if (!document.hidden && !$('[data-panel="uploads"]').hidden) loadFiles(); }, 60000);
})();
