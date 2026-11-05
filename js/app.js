(async () => {
  if (window.GyankunjaLayoutReady) await window.GyankunjaLayoutReady;
  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)];

  const THEME_KEY = 'gyankunjaTheme';
  const systemPrefersDark = () => window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const getTheme = () => localStorage.getItem(THEME_KEY) || (systemPrefersDark() ? 'dark' : 'light');
  const updateThemeControls = (theme) => {
    $$('[data-theme-toggle]').forEach(button => {
      const darkModeIsActive = theme === 'dark';
      const nextMode = darkModeIsActive ? 'light' : 'dark';
      const icon = $('[data-theme-icon]', button);
      const label = $('[data-theme-label]', button);
      if (icon) icon.textContent = darkModeIsActive ? '☀' : '☾';
      // if (label) label.textContent = darkModeIsActive ? 'Light' : 'Dark';
      button.setAttribute('aria-label', `Switch to ${nextMode} mode`);
      button.setAttribute('title', `Switch to ${nextMode} mode`);
      button.setAttribute('aria-pressed', String(darkModeIsActive));
    });
  };
  const applyTheme = (theme, save = false) => {
    const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.theme = normalizedTheme;
    document.documentElement.style.colorScheme = normalizedTheme;
    if (save) localStorage.setItem(THEME_KEY, normalizedTheme);
    updateThemeControls(normalizedTheme);
  };

  window.Gyankunja = {
    getUser() {
      try {
        return JSON.parse(localStorage.getItem('gyankunjaUser')) ||
          JSON.parse(sessionStorage.getItem('gyankunjaUser')) || null;
      } catch {
        return null;
      }
    },
    isAuthenticated() {
      return localStorage.getItem('gyankunjaAuth') === 'true' ||
        sessionStorage.getItem('gyankunjaAuth') === 'true';
    },
    logout(skipConfirm) {
      if (skipConfirm !== true && !confirm('Are you sure you want to log out?')) return;
      localStorage.removeItem('gyankunjaAuth');
      localStorage.removeItem('gyankunjaUser');
      sessionStorage.removeItem('gyankunjaAuth');
      sessionStorage.removeItem('gyankunjaUser');
      // also end the server-side login, then go to the login page
      fetch('php/auth_logout.php', { method: 'POST' })
        .catch(() => { })
        .finally(() => { location.href = 'login'; });
    },
    getTheme,
    setTheme(theme) { applyTheme(theme, true); },
    toggleTheme() { applyTheme(getTheme() === 'dark' ? 'light' : 'dark', true); },
    toast(message) {
      let toast = $('.toast');
      if (!toast) {
        toast = document.createElement('div');
        toast.className = 'toast';
        document.body.appendChild(toast);
      }
      toast.textContent = message;
      toast.classList.add('show');
      clearTimeout(window.__toastTimer);
      window.__toastTimer = setTimeout(() => toast.classList.remove('show'), 2600);
    }
  };

  applyTheme(getTheme());
  $$('[data-theme-toggle]').forEach(button => {
    button.addEventListener('click', () => {
      const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      applyTheme(nextTheme, true);
    });
  });

  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener?.('change', event => {
      if (!localStorage.getItem(THEME_KEY)) applyTheme(event.matches ? 'dark' : 'light');
    });
  }

  const authenticated = Gyankunja.isAuthenticated();
  $$('[data-auth-guest]').forEach(element => element.hidden = authenticated);
  $$('[data-auth-user]').forEach(element => element.hidden = !authenticated);
  $$('[data-logout]').forEach(button => button.addEventListener('click', Gyankunja.logout));

  const user = Gyankunja.getUser();
  if (authenticated && user) {
    $$('[data-nav-semester]').forEach(element => element.textContent = user.semester || 'My Semester');
  }

  const toggle = $('.menu-toggle');
  const links = $('.nav-links');

  // Semester menu beneath the shared Resources navigation.
  const semesterDropdown = links && links.querySelector('.nav-semester-dropdown');
  const resourcesTrigger = semesterDropdown && semesterDropdown.querySelector('a[href="resources"]');
  const setSemesterOpen = open => {
    if (!semesterDropdown || !resourcesTrigger) return;
    semesterDropdown.classList.toggle('is-open', open);
    resourcesTrigger.setAttribute('aria-expanded', String(open));
  };
  if (resourcesTrigger) {
    resourcesTrigger.addEventListener('click', event => {
      // On touch/mobile, the first tap opens the menu; the second follows the link.
      if ((window.matchMedia('(max-width: 1024px)').matches || window.matchMedia('(hover: none)').matches)
          && !semesterDropdown.classList.contains('is-open')) {
        event.preventDefault();
        setSemesterOpen(true);
      }
    });
    resourcesTrigger.addEventListener('keydown', event => {
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        setSemesterOpen(true);
        semesterDropdown.querySelector('.nav-semester-list a')?.focus();
      }
    });
    document.addEventListener('click', event => {
      if (!semesterDropdown.contains(event.target)) setSemesterOpen(false);
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && semesterDropdown.classList.contains('is-open')) {
        setSemesterOpen(false);
        resourcesTrigger.focus();
      }
    });
  }

  if (toggle && links) {
    const closeMenu = () => { links.classList.remove('open'); toggle.setAttribute('aria-expanded', 'false'); toggle.setAttribute('aria-label', 'Open navigation menu'); setSemesterOpen(false); };
    toggle.addEventListener('click', () => {
      const open = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
    });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeMenu(); });
    document.addEventListener('click', event => { if (!links.contains(event.target) && !toggle.contains(event.target)) closeMenu(); });
    $('.nav-links a').forEach(a => { if (a !== resourcesTrigger) a.addEventListener('click', closeMenu); });
  }

  $$('[data-demo-action]').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      Gyankunja.toast(btn.dataset.demoAction || 'This feature will connect to the backend later.');
    });
  });

  // Match both /resources and legacy /resources.html for active navigation.
  const currentPage = (location.pathname.split('/').pop() || 'index').replace(/\.html$/i, '');
  $$('[data-nav]').forEach(link => {
    const linkPage = (link.getAttribute('href') || '').split(/[?#]/)[0].split('/').pop().replace(/\.html$/i, '');
    if (linkPage === currentPage || (linkPage === 'resources' && /^semester[1-8]$/.test(currentPage))) link.classList.add('active');
  });
  $('.nav-semester-list a').forEach(link => {
    if (link.getAttribute('href') === currentPage) {
      link.classList.add('active');
      link.setAttribute('aria-current', 'page');
    }
  });
})();


/* ========================================
   Student Reviews
======================================== */

document.addEventListener("DOMContentLoaded", function () {
  const reviewForm = document.getElementById("reviewForm");
  const reviewsList = document.getElementById("reviewsList");
  const reviewCount = document.getElementById("reviewCount");
  const emptyState = document.getElementById("reviewsEmptyState");
  const formMessage = document.getElementById("reviewFormMessage");

  /* Stop when the current page does not contain the reviews section */
  if (!reviewForm || !reviewsList) {
    return;
  }

  const STORAGE_KEY = "gyankunjaStudentReviews";

  function getSavedReviews() {
    try {
      const savedReviews = localStorage.getItem(STORAGE_KEY);
      return savedReviews ? JSON.parse(savedReviews) : [];
    } catch (error) {
      console.error("Unable to load reviews:", error);
      return [];
    }
  }

  function saveReviews(reviews) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(reviews));
    } catch (error) {
      console.error("Unable to save reviews:", error);
    }
  }

  function createStars(rating) {
    const numberOfStars = Number(rating);
    const filledStars = "★".repeat(numberOfStars);
    const emptyStars = "☆".repeat(5 - numberOfStars);

    return filledStars + emptyStars;
  }

  function getStudentInitial(name) {
    return name.trim().charAt(0).toUpperCase() || "S";
  }

  function formatReviewDate(dateValue) {
    const date = new Date(dateValue);

    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric"
    });
  }

  function renderReviews() {
    const reviews = getSavedReviews();

    /*
      Remove previously generated cards.
      Keep the empty-state element in the HTML.
    */
    reviewsList.querySelectorAll(".review-card").forEach(function (card) {
      card.remove();
    });

    if (reviews.length === 0) {
      emptyState.hidden = false;
      reviewCount.textContent = "0 Reviews";
      return;
    }

    emptyState.hidden = true;

    reviewCount.textContent =
      reviews.length === 1
        ? "1 Review"
        : `${reviews.length} Reviews`;

    /*
      Newest review is displayed first.
    */
    reviews
      .slice()
      .reverse()
      .forEach(function (review) {
        const reviewCard = document.createElement("article");
        reviewCard.className = "review-card";

        const reviewCardHeader = document.createElement("div");
        reviewCardHeader.className = "review-card-header";

        const reviewStudent = document.createElement("div");
        reviewStudent.className = "review-student";

        const reviewAvatar = document.createElement("div");
        reviewAvatar.className = "review-avatar";
        reviewAvatar.textContent = getStudentInitial(review.name);

        const studentInformation = document.createElement("div");

        const studentName = document.createElement("h4");
        studentName.textContent = review.name;

        const studentSemester = document.createElement("span");
        studentSemester.textContent = review.semester;

        studentInformation.appendChild(studentName);
        studentInformation.appendChild(studentSemester);

        reviewStudent.appendChild(reviewAvatar);
        reviewStudent.appendChild(studentInformation);

        const reviewStars = document.createElement("div");
        reviewStars.className = "review-stars";
        reviewStars.setAttribute(
          "aria-label",
          `${review.rating} out of 5 stars`
        );
        reviewStars.textContent = createStars(review.rating);

        reviewCardHeader.appendChild(reviewStudent);
        reviewCardHeader.appendChild(reviewStars);

        const reviewMessage = document.createElement("p");
        reviewMessage.className = "review-card-message";
        reviewMessage.textContent = review.message;

        const reviewFooter = document.createElement("div");
        reviewFooter.className = "review-card-footer";

        const reviewDate = document.createElement("span");
        reviewDate.className = "review-date";
        reviewDate.textContent = formatReviewDate(review.createdAt);

        reviewFooter.appendChild(reviewDate);

        reviewCard.appendChild(reviewCardHeader);
        reviewCard.appendChild(reviewMessage);
        reviewCard.appendChild(reviewFooter);

        reviewsList.appendChild(reviewCard);
      });
  }

  reviewForm.addEventListener("submit", function (event) {
    event.preventDefault();

    const name = document
      .getElementById("reviewName")
      .value
      .trim();

    const semester = document
      .getElementById("reviewSemester")
      .value;

    const rating = document
      .getElementById("reviewRating")
      .value;

    const message = document
      .getElementById("reviewMessage")
      .value
      .trim();

    if (!name || !semester || !rating || !message) {
      formMessage.textContent = "Please complete all review fields.";
      formMessage.className = "review-form-message error";
      return;
    }

    if (message.length > 300) {
      formMessage.textContent =
        "Your review must not exceed 300 characters.";

      formMessage.className = "review-form-message error";
      return;
    }

    const newReview = {
      id: Date.now(),
      name: name,
      semester: semester,
      rating: Number(rating),
      message: message,
      createdAt: new Date().toISOString()
    };

    const reviews = getSavedReviews();

    /*
      Keep a maximum of 50 locally stored reviews.
    */
    reviews.push(newReview);

    const limitedReviews = reviews.slice(-50);

    saveReviews(limitedReviews);
    renderReviews();

    reviewForm.reset();

    formMessage.textContent =
      "Thank you! Your review has been submitted.";

    formMessage.className = "review-form-message success";

    /*
      Automatically scroll to the newly displayed reviews.
    */
    reviewsList.scrollIntoView({
      behavior: "smooth",
      block: "center"
    });

    window.setTimeout(function () {
      formMessage.textContent = "";
      formMessage.className = "review-form-message";
    }, 4000);
  });

  renderReviews();
});

