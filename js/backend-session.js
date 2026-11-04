(() => {
  const clearBrowserAuth = () => {
    localStorage.removeItem('gyankunjaAuth');
    localStorage.removeItem('gyankunjaUser');
    sessionStorage.removeItem('gyankunjaAuth');
    sessionStorage.removeItem('gyankunjaUser');
  };

  const setBrowserUser = user => {
    const persistentLogin = localStorage.getItem('gyankunjaAuth') === 'true';
    const storage = persistentLogin ? localStorage : sessionStorage;

    storage.setItem('gyankunjaAuth', 'true');
    storage.setItem('gyankunjaUser', JSON.stringify(user));
  };

  const updateNavigation = (authenticated, user = null) => {
    document
      .querySelectorAll('[data-auth-guest]')
      .forEach(element => {
        element.hidden = authenticated;
      });

    document
      .querySelectorAll('[data-auth-user]')
      .forEach(element => {
        element.hidden = !authenticated;
      });

    if (authenticated && user) {
      document
        .querySelectorAll('[data-nav-semester]')
        .forEach(element => {
          element.textContent = user.semester || 'My Semester';
        });
    }
  };

  const refresh = async () => {
    try {
      const response = await fetch('api/session.php', {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
      });

      const data = await response.json();

      if (response.ok && data.authenticated && data.user) {
        setBrowserUser(data.user);
        updateNavigation(true, data.user);
        return data.user;
      }
    } catch (error) {
      console.error('Unable to check the server session:', error);
    }

    clearBrowserAuth();
    updateNavigation(false);
    return null;
  };

  const logout = async () => {
    try {
      await fetch('api/logout.php', {
        method: 'POST',
        credentials: 'same-origin',
      });
    } catch (error) {
      console.error('Unable to close the server session:', error);
    } finally {
      clearBrowserAuth();
      location.href = 'login.html';
    }
  };

  /*
   * Capture logout clicks before the old app.js bubble handler can redirect.
   */
  document.addEventListener(
    'click',
    event => {
      const logoutButton = event.target.closest('[data-logout]');
      if (!logoutButton) return;

      event.preventDefault();
      event.stopImmediatePropagation();
      logout();
    },
    true
  );

  window.GyankunjaSession = {
    refresh,
    logout,
  };

  if (window.Gyankunja) {
    window.Gyankunja.logout = logout;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refresh);
  } else {
    refresh();
  }
})();
