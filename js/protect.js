// Protects dashboard.html: the quick browser check avoids a flash of the page,
// then the server session (php/auth_me.php) is the real proof of login.
(() => {
  const page = location.pathname.split('/').pop() || 'dashboard.html';

  const goToLogin = () => {
    ['gyankunjaAuth', 'gyankunjaUser'].forEach(k => { localStorage.removeItem(k); sessionStorage.removeItem(k); });
    sessionStorage.setItem('gyankunjaRedirect', page);
    location.replace('login.html?required=1');
  };

  const authenticated = localStorage.getItem('gyankunjaAuth') === 'true' ||
    sessionStorage.getItem('gyankunjaAuth') === 'true';

  if (!authenticated) {
    goToLogin();
    return;
  }

  fetch('php/auth_me.php', { cache: 'no-store' })
    .then(res => res.json())
    .then(me => {
      if (!me.loggedIn) goToLogin();
      else if (me.role !== 'student') location.replace('admin.html'); // coordinators/admins use the admin dashboard
    })
    .catch(() => { /* server unreachable: dashboard.js shows the error */ });
})();
