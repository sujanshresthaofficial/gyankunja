(() => {
  // Course list lives in js/courses.js (shared with the dashboard upload form)
  const semesterData = window.GyankunjaCourses || {};
  const romans = window.GyankunjaRoman || ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII'];

  // The 6 resource categories of every subject (same list the upload form uses)
  const CATEGORIES = window.GyankunjaCategories || [
    'Syllabus', 'Theory Notes', 'Lab Manuals', 'Question Bank', 'Books', 'References'
  ];
  const CATEGORY_ICONS = {
    'Syllabus': '▣', 'Theory Notes': '▤', 'Lab Manuals': '⌘',
    'Question Bank': '?', 'Books': '▥', 'References': '↗'
  };

  const page = document.body.dataset.semester;
  const number = Number(page || new URLSearchParams(location.search).get('semester') || 1);
  const roman = romans[number - 1];
  const courses = semesterData[number] || semesterData[1] || [];
  const semesterLabel = `Semester ${roman}`; // exactly what upload.php stores in files.semester

  document.querySelectorAll('[data-semester-title]').forEach(el => el.textContent = semesterLabel);
  document.querySelectorAll('[data-semester-number]').forEach(el => el.textContent = roman);

  // ---------- helpers ----------
  // Notes are user-submitted text, so everything is escaped before going into innerHTML.
  const esc = (value) => String(value ?? '').replace(/[&<>"']/g, ch => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]
  ));
  const key = (code) => String(code).replace(/\s+/g, '').toUpperCase(); // "ELX 121" -> "ELX121"

  const formatSize = (bytes) => {
    if (bytes >= 1024 * 1024) return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
  };

  const formatDate = (value) => {
    const d = new Date(String(value || '').replace(' ', 'T'));
    return isNaN(d) ? '' : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
  };

  const noteItem = (n, showCategory = false) => {
    const view = `php/download.php?id=${n.id}`;
    const by = n.uploader_name ? ` · by ${esc(n.uploader_name)}` : '';
    const when = formatDate(n.approved_at || n.uploaded_at);
    const cat = showCategory ? ` · ${esc(n.category)}` : '';
    return `
      <li class="note-item">
        <span class="note-ext">${esc((n.ext || 'file').toUpperCase())}</span>
        <div class="note-info">
          <a href="${view}" target="_blank" rel="noopener">${esc(n.title)}</a>
          <small>${formatSize(n.file_size)}${by}${when ? ` · ${esc(when)}` : ''}${cat}</small>
          ${n.description ? `<small class="note-desc">${esc(n.description)}</small>` : ''}
        </div>
        <a class="note-download" href="${view}&download=1" title="Download" aria-label="Download ${esc(n.title)}">↓</a>
      </li>`;
  };

  // ---------- state ----------
  let notes = [];          // approved notes for this semester, from the database
  let notesSignature = ''; // used to skip re-rendering when nothing changed
  let loadFailed = false;
  let searchTerm = '';
  const openCategory = {}; // course key -> category currently opened on that card

  const knownKeys = new Set(courses.map(([code]) => key(code)));
  const notesFor = (code, category) => notes.filter(n =>
    n.subject_code === key(code) && (!category || n.category === category));
  const otherNotes = () => notes.filter(n => !knownKeys.has(n.subject_code));

  // ---------- rendering ----------
  const grid = document.querySelector('#courseGrid');

  let statusEl = null;
  let otherEl = null;
  if (grid) {
    statusEl = document.createElement('p');
    statusEl.className = 'muted notes-status';
    statusEl.hidden = true;
    grid.before(statusEl);

    otherEl = document.createElement('div');
    otherEl.className = 'other-notes';
    grid.after(otherEl);
  }

  const renderPanel = (code) => {
    const cat = openCategory[key(code)];
    if (!cat) return '<p class="muted note-empty">Choose a category above to see its notes.</p>';
    const items = notesFor(code, cat);
    return `
      <div class="course-notes-head">${esc(cat)} <span class="count">${items.length}</span></div>
      ${items.length
        ? `<ul class="note-list">${items.map(n => noteItem(n)).join('')}</ul>`
        : `<p class="muted note-empty">${loadFailed ? 'Notes unavailable right now.' : `No approved ${esc(cat)} yet.`}</p>`}`;
  };

  const renderCourses = () => {
    if (!grid) return;
    const term = searchTerm.trim().toLowerCase();
    const list = courses.filter(([code, title]) => {
      if (!term) return true;
      if (`${code} ${title}`.toLowerCase().includes(term)) return true;
      return notesFor(code).some(n => n.title.toLowerCase().includes(term));
    });

    grid.innerHTML = list.map(([code, title]) => {
      const open = openCategory[key(code)];
      return `
      <article class="course-card" data-course="${esc(code)} ${esc(title)}" data-code="${esc(code)}">
        <div class="course-head">
          <small>${esc(code)}</small>
          <h3>${esc(title)}</h3>
        </div>
        <div class="course-resources">
          ${CATEGORIES.map(cat => {
        const count = notesFor(code, cat).length;
        return `<button class="resource-btn${open === cat ? ' active' : ''}" data-category="${esc(cat)}" type="button">
              ${CATEGORY_ICONS[cat] || '•'} ${esc(cat)}${count ? ` <span class="cat-count">${count}</span>` : ''}
            </button>`;
      }).join('')}
        </div>
        <div class="course-notes">${renderPanel(code)}</div>
      </article>`;
    }).join('');
  };

  // One click handler for every category button (cards are re-drawn, so we delegate)
  if (grid) grid.addEventListener('click', (event) => {
    const btn = event.target.closest('.resource-btn');
    if (!btn) return;
    const card = btn.closest('.course-card');
    const k = key(card.dataset.code);
    openCategory[k] = openCategory[k] === btn.dataset.category ? null : btn.dataset.category;
    renderCourses();
  });

  const renderOther = () => {
    if (!otherEl) return;
    const items = otherNotes();
    otherEl.innerHTML = items.length ? `
      <div class="course-card other-card">
        <div class="course-head"><small>${esc(semesterLabel)}</small><h3>Other Notes</h3></div>
        <div class="course-notes"><ul class="note-list">${items.map(n => noteItem(n, true)).join('')}</ul></div>
      </div>` : '';
  };

  const renderStatus = () => {
    if (!statusEl) return;
    statusEl.hidden = !loadFailed;
    statusEl.textContent = loadFailed
      ? 'Could not load student-uploaded notes right now (is the PHP server running?). Subjects are still listed below.'
      : '';
  };

  const renderAll = () => { renderStatus(); renderCourses(); renderOther(); };

  // ---------- auto-sync with the database ----------
  // Anything a coordinator approves appears here automatically:
  // on page load, every 60 seconds, and whenever the tab becomes visible again.
  const loadNotes = async () => {
    try {
      const res = await fetch(`php/list_files.php?semester=${encodeURIComponent(semesterLabel)}`, { cache: 'no-store' });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Request failed');
      loadFailed = false;
      const signature = JSON.stringify(data.files);
      if (signature !== notesSignature) {
        notesSignature = signature;
        notes = data.files;
        renderAll();
      } else {
        renderStatus();
      }
    } catch (error) {
      if (!loadFailed) {
        loadFailed = true;
        renderAll();
      }
    }
  };

  renderAll();   // show the subject cards immediately
  loadNotes();   // then fill in approved notes
  setInterval(() => { if (!document.hidden) loadNotes(); }, 60000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) loadNotes(); });

  const search = document.querySelector('#courseSearch');
  if (search) search.addEventListener('input', () => {
    searchTerm = search.value;
    renderCourses();
  });

  const select = document.querySelector('#semesterSelect');
  if (select) {
    select.value = String(number);
    select.addEventListener('change', () => location.href = `semester${select.value}.html`);
  }
})();
