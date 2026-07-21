(() => {
  const authenticated = localStorage.getItem('gyankunjaAuth') === 'true' ||
    sessionStorage.getItem('gyankunjaAuth') === 'true';

  if (!authenticated) {
    const page = location.pathname.split('/').pop() || 'dashboard.html';
    sessionStorage.setItem('gyankunjaRedirect', page);
    location.replace('login.html?required=1');
  }
})();
