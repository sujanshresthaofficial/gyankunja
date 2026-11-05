/* Load reusable header/footer before js/app.js. */
(() => {
  const partials = [
    ['[data-site-header]', 'includes/header.html'],
    ['[data-site-footer]', 'includes/footer.html']
  ];
  const load = async (selector, path) => {
    const placeholder = document.querySelector(selector);
    if (!placeholder) return;
    const response = await fetch(path, { cache: 'no-cache' });
    if (!response.ok) throw new Error(`${path}: HTTP ${response.status}`);
    placeholder.outerHTML = await response.text();
  };
  window.GyankunjaLayoutReady = Promise.all(partials.map(([selector, path]) => load(selector, path)))
    .catch(error => {
      console.error('Could not load shared Gyankunja layout. Use a web server.', error);
      const h = document.querySelector('[data-site-header]');
      if (h) h.innerHTML = '<nav class="container" aria-label="Fallback navigation"><a href="index">GYANKUNJA</a> · <a href="resources">Resources</a> · <a href="notices">Notices</a> · <a href="about">About</a></nav>';
      const f = document.querySelector('[data-site-footer]');
      if (f) f.textContent = 'Gyankunja · Shared layout unavailable.';
    });
})();
