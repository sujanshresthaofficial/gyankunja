/* Public library interactions. No authentication or API behavior. */
(() => {
  const search = document.querySelector('#semesterSearch');
  const cards = [...document.querySelectorAll('[data-semester-card]')];
  const empty = document.querySelector('#semesterEmpty');
  if (!search || !cards.length) return;
  const normalize = value => String(value).toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
  const filter = () => {
    const query = normalize(search.value);
    const roman = ['i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii'];
    const semesterMatch = query.match(/^(?:semester\s+)?(viii|vii|vi|iv|iii|ii|v|i|[1-8])$/);
    const wantedSemester = semesterMatch ? (/^[1-8]$/.test(semesterMatch[1]) ? Number(semesterMatch[1]) : roman.indexOf(semesterMatch[1]) + 1) : null;
    const tokens = query.split(' ').filter(Boolean);
    let visible = 0;
    cards.forEach(card => {
      const words = normalize(card.dataset.search).split(' ');
      const match = wantedSemester !== null ? Number(card.dataset.semester) === wantedSemester : tokens.every(token => words.some(word => word === token || (token.length > 2 && word.startsWith(token))));
      card.hidden = !match;
      if (match) visible += 1;
    });
    empty.hidden = visible > 0;
  };
  search.addEventListener('input', filter);
  filter();
})();

/* Keyboard support for existing dialogs and the mobile student sidebar. */
(() => {
  const dialogs = [...document.querySelectorAll('.modal[role="dialog"]')];
  const focusable = 'a[href],button:not([disabled]),input:not([type="hidden"]):not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex="0"]';
  dialogs.forEach(dialog => {
    let returnFocus = null;
    const observer = new MutationObserver(() => {
      if (dialog.classList.contains('open')) {
        returnFocus = document.activeElement;
        dialog.querySelector(focusable)?.focus();
      } else if (returnFocus?.isConnected) {
        returnFocus.focus();
      }
    });
    observer.observe(dialog, { attributes: true, attributeFilter: ['class'] });
    dialog.addEventListener('keydown', event => {
      if (event.key === 'Escape') {
        event.preventDefault();
        dialog.classList.remove('open');
      }
      if (event.key !== 'Tab') return;
      const nodes = [...dialog.querySelectorAll(focusable)].filter(node => node.getClientRects().length);
      const first = nodes[0], last = nodes[nodes.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    });
  });
  const sidebar = document.querySelector('.sidebar');
  const toggle = document.querySelector('.mobile-side-toggle');
  if (sidebar && toggle) {
    sidebar.id = 'student-sidebar';
    toggle.setAttribute('aria-controls', sidebar.id);
    toggle.setAttribute('aria-expanded', 'false');
    new MutationObserver(() => toggle.setAttribute('aria-expanded', String(sidebar.classList.contains('open'))))
      .observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') sidebar.classList.remove('open'); });
    document.addEventListener('click', event => {
      if (!sidebar.contains(event.target) && !toggle.contains(event.target)) sidebar.classList.remove('open');
    });
    sidebar.querySelectorAll('a,button').forEach(control => control.addEventListener('click', () => sidebar.classList.remove('open')));
  }
})();


/* Staff tabs: synchronize accessible state with the existing active class. */
(() => {
  const tabs = [...document.querySelectorAll('.admin-tabs .admin-tab')];
  if (!tabs.length) return;
  const sync = () => tabs.forEach(tab => {
    tab.setAttribute('role', 'tab');
    tab.setAttribute('aria-selected', String(tab.classList.contains('active')));
  });
  tabs.forEach(tab => tab.addEventListener('click', sync));
  sync();
})();
