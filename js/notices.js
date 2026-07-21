(() => {
  const OFFICIAL_NOTICE_PAGE = 'https://guexam.edu.np/notice/';
  const API_ENDPOINTS = [
    'https://guexam.edu.np/wp-json/wp/v2/news?per_page=30&_embed=1',
    'https://guexam.edu.np/wp-json/wp/v2/posts?per_page=30&_embed=1'
  ];

  const cachedNotices = [
    { title: 'Retotal Result Published', date: '2026-07-10T00:00:00', categories: ['Notice', 'Retotal'], link: 'https://guexam.edu.np/notice/', excerpt: 'Latest retotal result publication update from the Office of the Controller of Examinations.' },
    { title: 'Result Published (BALLB – VIII & VII) (MIT-AI III & I)', date: '2026-07-10T00:00:00', categories: ['Notice', 'Result'], link: 'https://guexam.edu.np/result/', excerpt: 'Result publication notice for the listed Gandaki University programs and semesters.' },
    { title: 'Transcript & Certificate Application', date: '2026-07-03T00:00:00', categories: ['General Notices', 'Notice'], link: 'https://guexam.edu.np/notice/', excerpt: 'Information regarding transcript and certificate application procedures.' },
    { title: 'विशेष मौका परीक्षाको फारम भर्ने सम्बन्धमा', date: '2026-06-30T00:00:00', categories: ['General Notices', 'Notice'], link: 'https://guexam.edu.np/notice/', excerpt: 'विशेष मौका परीक्षाको आवेदन तथा फारम भर्ने सम्बन्धी विश्वविद्यालयको सूचना।' },
    { title: 'Examinations Schedule of B.Pharm (VI, V, III, I) Semesters', date: '2026-06-26T00:00:00', categories: ['Exam Schedule', 'Notice'], link: 'https://guexam.edu.np/exam-schedule/', excerpt: 'Published examination schedule for the listed B.Pharm semesters.' },
    { title: 'Result Published BBA (VII) and BALLB (I, III, V)', date: '2026-06-26T00:00:00', categories: ['Result', 'Notice'], link: 'https://guexam.edu.np/result/', excerpt: 'Result publication update for BBA Semester VII and BALLB Semesters I, III and V.' },
    { title: 'परीक्षाफल प्रकाशन सम्बन्धमा (BIT/BBA/B.Pharm)', date: '2026-06-11T00:00:00', categories: ['Result', 'Notice'], link: 'https://guexam.edu.np/result/', excerpt: 'BIT, BBA र B.Pharm कार्यक्रमको परीक्षाफल प्रकाशन सम्बन्धी सूचना।' },
    { title: 'Examination Result Publication (BSM-I, III, V, VII, VIII)', date: '2026-06-03T00:00:00', categories: ['Result', 'Notice'], link: 'https://guexam.edu.np/result/', excerpt: 'Result publication notice for multiple BSM semesters.' }
  ];

  const state = { notices: [], source: 'cached' };
  const lists = [...document.querySelectorAll('[data-notice-list]')];
  if (!lists.length) return;

  const decodeHtml = value => {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = value || '';
    return textarea.value;
  };

  const stripHtml = value => {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = value || '';
    return (wrapper.textContent || '').replace(/\s+/g, ' ').trim();
  };

  const safeUrl = value => {
    try {
      const url = new URL(value, OFFICIAL_NOTICE_PAGE);
      return url.protocol === 'https:' && url.hostname.endsWith('guexam.edu.np') ? url.href : OFFICIAL_NOTICE_PAGE;
    } catch { return OFFICIAL_NOTICE_PAGE; }
  };

  const getCategories = post => {
    const terms = post?._embedded?.['wp:term'];
    if (Array.isArray(terms)) {
      const names = terms.flat().map(term => term?.name).filter(Boolean);
      if (names.length) return [...new Set(names)];
    }
    return ['Notice'];
  };

  const normalizePost = post => ({
    title: decodeHtml(post?.title?.rendered || post?.title || 'University Notice'),
    date: post?.date || post?.modified || new Date().toISOString(),
    categories: getCategories(post),
    link: safeUrl(post?.link),
    excerpt: stripHtml(post?.excerpt?.rendered || post?.content?.rendered || '').slice(0, 230) || 'Open the official university notice for complete details.'
  });

  const fetchFromOfficialSite = async () => {
    let lastError;
    for (const endpoint of API_ENDPOINTS) {
      try {
        const response = await fetch(endpoint, { headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (!response.ok) throw new Error(`Official API returned ${response.status}`);
        const data = await response.json();
        if (!Array.isArray(data) || !data.length) throw new Error('The official API returned no notices.');
        return data.map(normalizePost).sort((a, b) => new Date(b.date) - new Date(a.date));
      } catch (error) { lastError = error; }
    }
    throw lastError || new Error('Unable to connect to the official notice feed.');
  };

  const formatDate = value => {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? { day: '--', month: '---', full: 'Date unavailable' } : {
      day: new Intl.DateTimeFormat('en', { day: '2-digit' }).format(date),
      month: new Intl.DateTimeFormat('en', { month: 'short' }).format(date).toUpperCase(),
      full: new Intl.DateTimeFormat('en', { day: 'numeric', month: 'long', year: 'numeric' }).format(date)
    };
  };

  const createNoticeCard = notice => {
    const date = formatDate(notice.date);
    const article = document.createElement('article');
    article.className = 'notice-page-card';

    const dateBox = document.createElement('div');
    dateBox.className = 'notice-date notice-date-large';
    dateBox.innerHTML = `<span>${date.day}</span><small>${date.month}</small>`;

    const content = document.createElement('div');
    content.className = 'notice-page-content';
    const tags = document.createElement('div');
    tags.className = 'notice-tags';
    notice.categories.slice(0, 3).forEach(category => {
      const tag = document.createElement('span');
      tag.className = 'notice-tag';
      tag.textContent = category;
      tags.appendChild(tag);
    });
    const heading = document.createElement('h2');
    heading.textContent = notice.title;
    const meta = document.createElement('p');
    meta.className = 'notice-full-date';
    meta.textContent = date.full;
    const excerpt = document.createElement('p');
    excerpt.className = 'notice-excerpt';
    excerpt.textContent = notice.excerpt;
    content.append(tags, heading, meta, excerpt);

    const link = document.createElement('a');
    link.className = 'btn btn-outline btn-sm notice-read-button';
    link.href = safeUrl(notice.link);
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.textContent = 'Read Official Notice ↗';

    article.append(dateBox, content, link);
    return article;
  };

  const createPreviewItem = notice => {
    const date = formatDate(notice.date);
    const article = document.createElement('article');
    article.className = 'notice-item';
    const dateBox = document.createElement('div');
    dateBox.className = 'notice-date';
    dateBox.innerHTML = `${date.day}<small>${date.month}</small>`;
    const content = document.createElement('div');
    const heading = document.createElement('h4');
    heading.textContent = notice.title;
    const excerpt = document.createElement('p');
    excerpt.textContent = notice.excerpt;
    content.append(heading, excerpt);
    const link = document.createElement('a');
    link.className = 'btn btn-outline btn-sm';
    link.href = safeUrl(notice.link);
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.textContent = 'Read Notice';
    article.append(dateBox, content, link);
    return article;
  };

  const matchesFilters = notice => {
    const search = (document.getElementById('noticeSearch')?.value || '').trim().toLowerCase();
    const category = (document.getElementById('noticeCategory')?.value || 'all').toLowerCase();
    const text = `${notice.title} ${notice.excerpt} ${notice.categories.join(' ')}`.toLowerCase();
    const categoryText = notice.categories.join(' ').toLowerCase();
    return (!search || text.includes(search)) && (category === 'all' || categoryText.includes(category));
  };

  const render = () => {
    const filtered = state.notices.filter(matchesFilters);
    lists.forEach(list => {
      list.replaceChildren();
      const limit = Number(list.dataset.limit || 0);
      const items = limit > 0 ? filtered.slice(0, limit) : filtered;
      items.forEach(notice => list.appendChild(limit > 0 ? createPreviewItem(notice) : createNoticeCard(notice)));
    });
    const empty = document.getElementById('noticeEmpty');
    if (empty) empty.hidden = filtered.length > 0;
  };

  const updateStatus = (message, mode = 'live') => {
    document.querySelectorAll('[data-notice-status]').forEach(status => {
      status.classList.toggle('is-fallback', mode !== 'live');
      status.innerHTML = `<span class="status-dot"></span>${message}`;
    });
  };

  const load = async () => {
    updateStatus('Connecting to official source…', 'loading');
    try {
      state.notices = await fetchFromOfficialSite();
      state.source = 'live';
      updateStatus(`Live official notices • ${state.notices.length} loaded`, 'live');
    } catch (error) {
      state.notices = cachedNotices;
      state.source = 'cached';
      updateStatus('Cached official notices shown • live request unavailable', 'fallback');
      console.warn('Gyankunja notice feed:', error);
    }
    render();
  };

  document.getElementById('noticeSearch')?.addEventListener('input', render);
  document.getElementById('noticeCategory')?.addEventListener('change', render);
  document.getElementById('refreshNotices')?.addEventListener('click', load);
  load();
})();
