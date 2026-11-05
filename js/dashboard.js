/* Student dashboard: only actual PHP/MySQL records, no demo notes or schedules. */
(async () => {
  const $ = selector => document.querySelector(selector);
  const $$ = selector => [...document.querySelectorAll(selector)];
  const esc = value => String(value ?? '').replace(/[&<>"']/g, ch =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
  const request = async (url, options = {}) => {
    const response = await fetch(url, { cache: 'no-store', credentials: 'same-origin', ...options });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Request failed.');
    return data;
  };

  let user;
  try {
    const response = await fetch('php/auth_me.php', { cache: 'no-store', credentials: 'same-origin' });
    const me = await response.json();
    if (me.loggedIn && me.role !== 'student') {
      location.replace('admin');
      return;
    }
    if (me.loggedIn) user = me.user;
  } catch (error) {
    console.error('Student session unavailable:', error);
  }
  if (!user) {
    ['gyankunjaAuth', 'gyankunjaUser'].forEach(key => {
      localStorage.removeItem(key);
      sessionStorage.removeItem(key);
    });
    location.replace('login?required=1');
    return;
  }

  try {
    const storage = localStorage.getItem('gyankunjaAuth') === 'true' ? localStorage : sessionStorage;
    storage.setItem('gyankunjaAuth', 'true');
    storage.setItem('gyankunjaUser', JSON.stringify(user));
  } catch { /* Browsers may disable storage. The session is still authoritative. */ }

  const roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII'];
  const semesterNumber = Math.max(1, roman.findIndex(n => user.semester === 'Semester ' + n) + 1);
  const firstName = String(user.name || 'Student').trim().split(/\s+/)[0];
  const initials = String(user.name || 'Student').trim().split(/\s+/).slice(0, 2)
    .map(part => part[0]).join('').toUpperCase();

  $$('[data-user-name]').forEach(el => { el.textContent = user.name; });
  $$('[data-user-firstname]').forEach(el => { el.textContent = firstName; });
  $$('[data-user-email]').forEach(el => { el.textContent = user.email; });
  $$('[data-user-semester]').forEach(el => { el.textContent = user.semester; });
  $$('[data-user-initials]').forEach(el => { el.textContent = initials; });
  $$('[data-dashboard-semester]').forEach(el => { el.textContent = user.semester; });
  $$('[data-my-notes]').forEach(link => { link.href = 'semester' + semesterNumber; });

  const notesContainer = $('#dashboardNotes');
  const myUploads = $('#myUploads');
  const examContainer = $('#dashboardExams');
  const searchInput = $('#dashboardSearch');
  let latestNotes = [];

  const renderNotes = () => {
    if (!notesContainer) return;
    const query = (searchInput?.value || '').trim().toLowerCase();
    const list = latestNotes.filter(note =>
      [note.title, note.subject, note.category].some(value => String(value || '').toLowerCase().includes(query)));
    if (!list.length) {
      notesContainer.innerHTML = '<div class="panel dashboard-data-empty">' +
        (query ? 'No approved notes match your search.' : 'No approved notes for your semester yet. New notes will appear after staff approval.') +
        '</div>';
      return;
    }
    notesContainer.innerHTML = list.map(note => {
      const id = Number(note.id);
      if (!Number.isSafeInteger(id) || id <= 0) return '';
      const ext = String(note.extension || 'FILE').slice(0, 5).toUpperCase();
      const url = 'php/download.php?id=' + id;
      return `
        <article class="note-card">
          <div class="note-cover">
            <div class="file-icon">${esc(ext)}</div><span class="badge badge-green">Approved</span>
          </div>
          <div class="note-body">
            <h3>${esc(note.title)}</h3>
            <div class="note-meta"><span>${esc(note.subject)}</span><span>• ${esc(note.category)}</span></div>
            <a class="btn btn-outline btn-sm dashboard-open-file" href="${url}" target="_blank" rel="noopener">Open file</a>
          </div>
        </article>`;
    }).join('');
  };
  searchInput?.addEventListener('input', renderNotes);

  const renderUploads = uploads => {
    if (!myUploads) return;
    if (!uploads.length) {
      myUploads.innerHTML = '<p class="muted" style="margin:0">You have not uploaded any notes yet. Use “Upload Note” to contribute.</p>';
      return;
    }
    myUploads.innerHTML = uploads.map(file => {
      const status = ['approved', 'pending', 'rejected'].includes(file.status) ? file.status : 'pending';
      const remarks = file.review_remarks
        ? `<div class="muted dashboard-upload-remarks">Staff feedback: ${esc(file.review_remarks)}</div>` : '';
      return `<div class="dashboard-upload-row">
        <div><strong>${esc(file.title)}</strong>
          <div class="muted dashboard-upload-meta">${esc(file.semester)} · ${esc(file.subject)} · ${esc(file.category)}</div>
          ${remarks}
        </div><span class="badge ${status === 'approved' ? 'badge-green' : 'badge-blue'}">${esc(status)}</span>
      </div>`;
    }).join('');
  };

  const renderContributions = data => {
    const counts = data.counts || {};
    const values = {
      '[data-contrib-total]': counts.total || 0,
      '[data-contrib-approved]': counts.approved || 0,
      '[data-contrib-pending]': counts.pending || 0,
      '[data-upload-count]': counts.total || 0,
    };
    for (const [selector, value] of Object.entries(values)) {
      $$(selector).forEach(el => { el.textContent = String(value); });
    }
    const chart = $('#dashboardActivityChart');
    if (!chart) return;
    const activity = Array.isArray(data.activity) ? data.activity : [];
    if (!activity.length) {
      chart.innerHTML = '<p class="muted">No upload activity recorded yet.</p>';
      return;
    }
    const countsByMonth = activity.map(point => Math.max(0, Number(point.count) || 0));
    const maximum = Math.max(1, ...countsByMonth);
    const coordinates = countsByMonth.map((value, index) => {
      const x = 24 + index * (552 / Math.max(1, countsByMonth.length - 1));
      const y = 145 - value / maximum * 110;
      return [x, y];
    });
    const points = coordinates.map(([x, y]) => `${x.toFixed(1)},${y.toFixed(1)}`).join(' ');
    const first = coordinates[0], last = coordinates[coordinates.length - 1];
    const area = `M ${points.replaceAll(' ', ' L ')} L ${last[0]},155 L ${first[0]},155 Z`;
    const labels = activity.map((point, index) => {
      const month = /^\d{4}-\d{2}$/.test(point.month) ? point.month.slice(5) + '/' + point.month.slice(2, 4) : '';
      return `<text x="${coordinates[index][0].toFixed(1)}" y="175" text-anchor="middle" font-size="11" fill="currentColor">${esc(month)}</text>`;
    }).join('');
    chart.innerHTML = `
      <svg class="chart-svg" viewBox="0 0 600 185" preserveAspectRatio="none" role="img" aria-label="Monthly uploaded notes over the last six months">
        <path d="${area}" fill="rgba(36, 88, 207, .13)"/>
        <polyline points="${points}" fill="none" stroke="#2458cf" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        ${coordinates.map(([x,y]) => `<circle cx="${x}" cy="${y}" r="4.5" fill="#2458cf"/>`).join('')}
        ${labels}
      </svg>`;
    const caption = $('#dashboardActivityCaption');
    if (caption) caption.textContent = 'Actual uploads from your account · last six months';
  };

  const loadDashboard = async () => {
    try {
      const data = await request('php/dashboard_data.php');
      latestNotes = data.notes || [];
      renderNotes();
      renderUploads(data.uploads || []);
      renderContributions(data);
    } catch (error) {
      console.error('Dashboard data unavailable:', error);
      if (notesContainer) notesContainer.innerHTML = '<div class="panel dashboard-data-empty">Could not load approved notes. Check the MySQL connection.</div>';
      if (myUploads) myUploads.textContent = 'Could not load your uploads right now.';
      const chart = $('#dashboardActivityChart');
      if (chart) chart.textContent = 'Contribution data is temporarily unavailable.';
    }
  };

  const renderExams = exams => {
    if (!examContainer) return;
    if (!exams.length) {
      examContainer.innerHTML = '<p class="muted">No upcoming exams scheduled for your semester.</p>';
      return;
    }
    examContainer.innerHTML = exams.map(exam => {
      const starts = new Date(String(exam.starts_at).replace(' ', 'T'));
      const validDate = !Number.isNaN(starts.getTime());
      const date = validDate ? starts.toLocaleDateString(undefined, { day: '2-digit' }) : '—';
      const month = validDate ? starts.toLocaleDateString(undefined, { month: 'short' }) : '';
      const time = validDate ? starts.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' }) : '';
      return `<div class="exam-item">
        <div class="exam-date">${esc(date)}<small>${esc(month)}</small></div>
        <div><strong>${esc(exam.subject)}</strong><div class="muted dashboard-exam-sub">${esc(exam.exam_type)}${exam.venue ? ' · ' + esc(exam.venue) : ''}</div></div>
        <strong>${esc(time)}</strong>
      </div>`;
    }).join('');
  };

  const loadExams = async () => {
    if (!examContainer) return;
    try {
      const data = await request('php/exams.php');
      renderExams(data.exams || []);
    } catch (error) {
      console.error('Exam schedule request failed:', error);
      examContainer.innerHTML = '<p class="muted">Exam schedule is temporarily unavailable. Please try again later.</p>';
    }
  };

  // Sidebar and modal controls.
  const sidebar = $('.sidebar');
  const mobileToggle = $('.mobile-side-toggle');
  if (sidebar && mobileToggle) mobileToggle.addEventListener('click', () => sidebar.classList.toggle('open'));

  const uploadModal = $('#uploadModal');
  $$('[data-open-upload]').forEach(button => button.addEventListener('click', event => {
    event.preventDefault();
    uploadModal?.classList.add('open');
  }));
  $('[data-close-modal]')?.addEventListener('click', () => uploadModal?.classList.remove('open'));
  uploadModal?.addEventListener('click', event => { if (event.target === uploadModal) uploadModal.classList.remove('open'); });

  const uploadSemester = $('#uploadSemester');
  if (uploadSemester) {
    const option = document.createElement('option');
    option.value = user.semester;
    option.textContent = user.semester;
    uploadSemester.replaceChildren(option);
  }
  const uploadSubject = $('#uploadSubject');
  const fillSubjects = () => {
    if (!uploadSubject) return;
    const subjects = (window.GyankunjaCourses || {})[semesterNumber] || [];
    uploadSubject.replaceChildren(new Option('Select a subject', '', true, true));
    uploadSubject.options[0].disabled = true;
    for (const [code, title] of subjects) {
      const value = window.GyankunjaSubjectValue ? window.GyankunjaSubjectValue(code, title) : code + ' - ' + title;
      uploadSubject.add(new Option(code + ' — ' + title, value));
    }
  };
  fillSubjects();
  const uploadCategory = $('#uploadCategory');
  const categories = window.GyankunjaCategories || ['Syllabus', 'Theory Notes', 'Lab Manuals', 'Question Bank', 'Books', 'References'];
  if (uploadCategory) {
    uploadCategory.replaceChildren(new Option('Select a category', '', true, true));
    uploadCategory.options[0].disabled = true;
    categories.forEach(category => uploadCategory.add(new Option(category, category)));
  }

  const uploadForm = $('#uploadForm');
  const uploadStatusMsg = $('#uploadStatusMsg');
  uploadForm?.addEventListener('submit', async event => {
    event.preventDefault();
    const file = uploadForm.elements.namedItem('file')?.files?.[0];
    const button = uploadForm.querySelector('button[type="submit"]');
    if (file && file.size > 25 * 1024 * 1024) {
      if (uploadStatusMsg) uploadStatusMsg.textContent = 'Maximum upload size is 25 MB.';
      return;
    }
    if (button) button.disabled = true;
    if (uploadStatusMsg) uploadStatusMsg.textContent = 'Uploading…';
    try {
      const response = await request('php/upload.php', { method: 'POST', body: new FormData(uploadForm) });
      uploadForm.reset();
      if (uploadSemester) uploadSemester.value = user.semester;
      fillSubjects();
      uploadModal?.classList.remove('open');
      if (uploadStatusMsg) uploadStatusMsg.textContent = '';
      await loadDashboard();
      window.Gyankunja?.toast(response.message || 'Your note was submitted for review.');
    } catch (error) {
      if (uploadStatusMsg) uploadStatusMsg.textContent = error.message || 'Upload failed.';
    } finally {
      if (button) button.disabled = false;
    }
  });

  const profileModal = $('#profileModal');
  const profileForm = $('#profileForm');
  const profileMsg = $('#profileMsg');
  const profileField = name => profileForm?.elements.namedItem(name);
  const openProfile = () => {
    if (!profileForm) return;
    for (const [name, value] of Object.entries({
      name: user.name, studentId: user.studentId || '', email: user.email,
      program: user.program, semester: user.semester, current_password: '', new_password: ''
    })) {
      const input = profileField(name);
      if (input) input.value = value;
    }
    if (profileMsg) profileMsg.textContent = '';
    profileModal?.classList.add('open');
  };
  $$('[data-open-profile]').forEach(button => button.addEventListener('click', event => {
    event.preventDefault();
    openProfile();
  }));
  $('[data-close-profile]')?.addEventListener('click', () => profileModal?.classList.remove('open'));
  profileModal?.addEventListener('click', event => { if (event.target === profileModal) profileModal.classList.remove('open'); });
  profileForm?.addEventListener('submit', async event => {
    event.preventDefault();
    if (profileMsg) profileMsg.textContent = 'Saving…';
    try {
      await request('php/profile_update.php', {
        method: 'POST',
        body: new URLSearchParams(new FormData(profileForm))
      });
      location.reload();
    } catch (error) {
      if (profileMsg) profileMsg.textContent = error.message || 'Could not save profile.';
    }
  });

  await Promise.all([loadDashboard(), loadExams()]);
  window.setInterval(() => {
    if (!document.hidden) { loadDashboard(); loadExams(); }
  }, 60000);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) { loadDashboard(); loadExams(); }
  });
})();
