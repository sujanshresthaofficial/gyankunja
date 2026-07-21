(() => {
  const alertBox = document.querySelector('#formAlert');

  const DEMO_ACCOUNTS = [
    { id: 'BIT-SEM1-001', password: 'sem1@123', name: 'Semester One Student', email: 'sem1@gyankunja.edu.np', studentId: 'BIT-SEM1-001', program: 'Bachelor of Information Technology', semester: 'Semester I' },
    { id: 'BIT-SEM2-001', password: 'sem2@123', name: 'Semester Two Student', email: 'sem2@gyankunja.edu.np', studentId: 'BIT-SEM2-001', program: 'Bachelor of Information Technology', semester: 'Semester II' },
    { id: 'BIT-SEM3-001', password: 'sem3@123', name: 'Semester Three Student', email: 'sem3@gyankunja.edu.np', studentId: 'BIT-SEM3-001', program: 'Bachelor of Information Technology', semester: 'Semester III' },
    { id: 'BIT-SEM4-001', password: 'sem4@123', name: 'Semester Four Student', email: 'sem4@gyankunja.edu.np', studentId: 'BIT-SEM4-001', program: 'Bachelor of Information Technology', semester: 'Semester IV' },
    { id: 'BIT-SEM5-001', password: 'sem5@123', name: 'Semester Five Student', email: 'sem5@gyankunja.edu.np', studentId: 'BIT-SEM5-001', program: 'Bachelor of Information Technology', semester: 'Semester V' },
    { id: 'BIT-SEM6-001', password: 'sem6@123', name: 'Semester Six Student', email: 'sem6@gyankunja.edu.np', studentId: 'BIT-SEM6-001', program: 'Bachelor of Information Technology', semester: 'Semester VI' },
    { id: 'BIT-SEM7-001', password: 'sem7@123', name: 'Semester Seven Student', email: 'sem7@gyankunja.edu.np', studentId: 'BIT-SEM7-001', program: 'Bachelor of Information Technology', semester: 'Semester VII' },
    { id: 'BIT-SEM8-001', password: 'sem8@123', name: 'Semester Eight Student', email: 'sem8@gyankunja.edu.np', studentId: 'BIT-SEM8-001', program: 'Bachelor of Information Technology', semester: 'Semester VIII' }
  ];

  const showAlert = (message, type = 'danger') => {
    if (!alertBox) return;
    alertBox.className = `alert show alert-${type}`;
    alertBox.textContent = message;
  };

  const getRegisteredUsers = () => {
    let users = [];
    try {
      users = JSON.parse(localStorage.getItem('gyankunjaRegisteredUsers') || '[]');
      if (!Array.isArray(users)) users = [];
    } catch {
      users = [];
    }

    // Keep accounts created with the older prototype compatible.
    try {
      const legacy = JSON.parse(localStorage.getItem('gyankunjaRegisteredUser') || 'null');
      if (legacy && !users.some(user => user.email?.toLowerCase() === legacy.email?.toLowerCase())) {
        users.push(legacy);
      }
    } catch {}
    return users;
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
    loginForm.addEventListener('submit', event => {
      event.preventDefault();
      const identifier = loginForm.identifier.value.trim();
      const password = loginForm.password.value;
      const remember = loginForm.remember.checked;

      if (!identifier || !password) {
        showAlert('Please enter your login ID or email and password.');
        return;
      }

      const normalized = identifier.toLowerCase();
      const demoUser = DEMO_ACCOUNTS.find(account =>
        account.id.toLowerCase() === normalized || account.email.toLowerCase() === normalized
      );
      const registeredUser = getRegisteredUsers().find(user =>
        user.email?.toLowerCase() === normalized || user.studentId?.toLowerCase() === normalized
      );
      const account = demoUser || registeredUser;

      if (!account || account.password !== password) {
        showAlert('Invalid login ID/email or password. Use one of the demo accounts below or an account created from Sign Up.');
        return;
      }

      const user = {
        name: account.name,
        email: account.email,
        studentId: account.studentId || account.id,
        program: account.program || 'Bachelor of Information Technology',
        semester: account.semester
      };

      // Clear an older login before storing the new one.
      localStorage.removeItem('gyankunjaAuth');
      localStorage.removeItem('gyankunjaUser');
      sessionStorage.removeItem('gyankunjaAuth');
      sessionStorage.removeItem('gyankunjaUser');

      const storage = remember ? localStorage : sessionStorage;
      storage.setItem('gyankunjaAuth', 'true');
      storage.setItem('gyankunjaUser', JSON.stringify(user));

      showAlert(`Login successful. Loading ${user.semester} dashboard…`, 'success');
      const redirect = sessionStorage.getItem('gyankunjaRedirect') || 'dashboard.html';
      sessionStorage.removeItem('gyankunjaRedirect');
      setTimeout(() => location.href = redirect, 650);
    });
  }

  const signupForm = document.querySelector('#signupForm');
  if (signupForm) {
    signupForm.addEventListener('submit', event => {
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

      const users = getRegisteredUsers();
      const duplicate = users.some(user =>
        user.email?.toLowerCase() === data.email.toLowerCase() ||
        user.studentId?.toLowerCase() === data.studentId.toLowerCase()
      );
      const demoDuplicate = DEMO_ACCOUNTS.some(user =>
        user.email.toLowerCase() === data.email.toLowerCase() ||
        user.id.toLowerCase() === data.studentId.toLowerCase()
      );
      if (duplicate || demoDuplicate) {
        showAlert('An account already exists with this email or University ID.');
        return;
      }

      users.push({
        name: data.name,
        email: data.email,
        studentId: data.studentId,
        program: data.program,
        semester: data.semester,
        password: data.password
      });
      localStorage.setItem('gyankunjaRegisteredUsers', JSON.stringify(users));
      showAlert(`Account created for ${data.semester}. Sign in using your email or University ID.`, 'success');
      signupForm.reset();
      setTimeout(() => location.href = 'login.html', 1000);
    });
  }
})();
