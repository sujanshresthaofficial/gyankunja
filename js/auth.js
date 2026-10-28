// =====================================================
// js/auth.js — login + sign-up. Accounts now live in MySQL (php/auth_*.php);
// the browser only keeps a small copy of the user's name/semester for the UI.
// =====================================================
(() => {
  const alertBox = document.querySelector('#formAlert');

  const showAlert = (message, type = 'danger') => {
    if (!alertBox) return;
    alertBox.className = `alert show alert-${type}`;
    alertBox.textContent = message;
  };

  // Sends a form to a PHP script and returns the JSON answer.
  const post = async (url, fields) => {
    const res = await fetch(url, { method: 'POST', body: new URLSearchParams(fields) });
    let data;
    try { data = await res.json(); } catch { data = null; }
    if (!data) throw new Error('The server sent an unexpected answer.');
    return data;
  };

  document.querySelectorAll('.password-toggle').forEach(button => {
    button.addEventListener('click', () => {
      const input = document.querySelector(button.dataset.target);
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
      button.textContent = input.type === 'password' ? 'Show' : 'Hide';
    });
  });

  const loginForm = document.querySelector('#loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', async event => {
      event.preventDefault();
      const identifier = loginForm.identifier.value.trim();
      const password = loginForm.password.value;
      const remember = loginForm.remember.checked;

      if (!identifier || !password) {
        showAlert('Please enter your login ID or email and password.');
        return;
      }

      const button = loginForm.querySelector('button[type="submit"]');
      if (button) button.disabled = true;

      try {
        const data = await post('php/auth_login.php', { identifier, password, remember: remember ? '1' : '' });
        if (!data.success) {
          showAlert(data.message || 'Login failed.');
          return;
        }

        // Clear an older login before storing the new one.
        localStorage.removeItem('gyankunjaAuth');
        localStorage.removeItem('gyankunjaUser');
        sessionStorage.removeItem('gyankunjaAuth');
        sessionStorage.removeItem('gyankunjaUser');

        const storage = remember ? localStorage : sessionStorage;
        storage.setItem('gyankunjaAuth', 'true');
        storage.setItem('gyankunjaUser', JSON.stringify(data.user));

        const isStaff = data.role !== 'student';
        showAlert(isStaff ? 'Login successful. Opening the admin dashboard…' : `Login successful. Loading ${data.user.semester} dashboard…`, 'success');

        let redirect = data.redirect;
        if (!isStaff) {
          redirect = sessionStorage.getItem('gyankunjaRedirect') || redirect;
          sessionStorage.removeItem('gyankunjaRedirect');
        }
        setTimeout(() => location.href = redirect, 500);
      } catch (error) {
        showAlert('Could not reach the server. Is WAMP running?');
      } finally {
        if (button) button.disabled = false;
      }
    });
  }

  const signupForm = document.querySelector('#signupForm');
  if (signupForm) {
    signupForm.addEventListener('submit', async event => {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(signupForm).entries());

      if (!data.name || !data.email || !data.studentId || !data.program || !data.semester || !data.password || !data.confirmPassword) {
        showAlert('Please complete all required fields.');
        return;
      }
      if (!/^\S+@\S+\.\S+$/.test(data.email)) {
        showAlert('Please enter a valid student email address.');
        return;
      }
      if (data.password.length < 6) {
        showAlert('Password must contain at least 6 characters.');
        return;
      }
      if (data.password !== data.confirmPassword) {
        showAlert('Passwords do not match.');
        return;
      }
      if (!signupForm.terms.checked) {
        showAlert('Please accept the Terms and Privacy Policy.');
        return;
      }

      const button = signupForm.querySelector('button[type="submit"]');
      if (button) button.disabled = true;

      try {
        const result = await post('php/auth_signup.php', {
          name: data.name, email: data.email, studentId: data.studentId, program: data.program,
          semester: data.semester, password: data.password, confirmPassword: data.confirmPassword
        });
        if (!result.success) {
          showAlert(result.message || 'Could not create the account.');
          return;
        }
        showAlert(result.message || 'Account created. Redirecting to sign in…', 'success');
        signupForm.reset();
        setTimeout(() => location.href = 'login.html', 1000);
      } catch (error) {
        showAlert('Could not reach the server. Is WAMP running?');
      } finally {
        if (button) button.disabled = false;
      }
    });
  }
})();