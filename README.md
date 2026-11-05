# GYANKUNJA — Academic Note Sharing Platform

**Less Searching, More Learning!**

GYANKUNJA is an academic note-sharing platform developed by students of Gandaki University to make study materials easily accessible to BIT students.

## Key Features
- Semester-wise resources (Semester I–VIII).
- Syllabi, notes, lab manuals, question banks, books, and references.
- Student registration and login.
- Personalized student dashboard.
- Note uploading and approval system.
- Admin and coordinator dashboard.
- Gandaki University examination notices.
- Light and dark mode.
- Responsive design for mobile and desktop.

## Latest UI & Website Updates
- Redesigned homepage with modern blue-themed graphics.
- Improved semester and subject cards with borders, shadows, and hover effects.
- Enhanced login and signup pages.
- Added background textures to Resources, Notices, and About pages.
- Restored the original student dashboard and developer profile designs.
- Improved navigation, spacing, and mobile responsiveness.
- Added the GYANKUNJA logo to the resource ribbon.
- Introduced clean URLs (`/resources` instead of `/resources.html`).
- Improved CSS organization and fixed frontend UI issues.
- Updated Apache `.htaccess` configuration for clean URL routing.

## Technologies Used
**Frontend:** HTML, CSS, JavaScript  
**Backend:** PHP, MySQL  
**Development Tools:** VS Code, WAMP, Git & GitHub

## Current Development Status
GYANKUNJA is under active development, focusing on frontend improvements, user experience, testing, and future enhancements.

Some dashboard information is sample data, and certain features require further testing before public deployment.

## Developers
- **Prabhab Tiwari** — Full-stack Developer
- **Sujan Shrestha** — Full-stack Developer
- **Madhab Khanal** — Front-end Developer



---

**GYANKUNJA — Built by Students, for Students.**

---

## Database-connected student dashboard (November 2026)

The current student dashboard is **dashboard.html**. It reads the existing
`gyankunja` MySQL database using PHP and the active `files` table.

- **Semester notes:** recently approved uploads from the student's semester,
  linked to the real protected download endpoint.
- **My Uploads:** the student's own notes, approval status and staff remarks.
- **Contributions:** real uploaded/approved/pending totals and monthly activity.
- **Exam Schedule:** only confirmed entries stored in `exam_schedule`.
  No demo notes, dates or invented view counts.
- **Profile and uploads:** continue to use the existing session-protected PHP endpoints.

### Required one-time database migration

Start Apache and MySQL in WAMP, open `http://localhost/phpmyadmin/`,
select **gyankunja**, and import:

`database/dashboard_exams.sql`

This adds one `exam_schedule` table without changing or deleting existing
students, uploaded files or review statuses. Until it is imported, the exam
section will say the schedule is unavailable, while other dashboard data
continues to work.

### Enter verified exams

Sign in as a **coordinator or admin** and open **Admin → Exam Schedule**.
Add the correct semester, subject, exam type, date/time and optional venue.
Staff can also edit and delete exam entries. Only future exams for the
logged-in student's semester appear in their dashboard. Confirm official
exam details before saving them.

### Verify locally

Use `http://localhost/gyankunja/` on WAMP (not VS Code Live Server).
Confirm that `php/auth_me.php` reports a logged-in student before opening
the dashboard. Try uploading a note, approving it from the staff page, and
refreshing the student dashboard. The approved note should show in the
correct semester, while the student's upload count and status update.

**Schema note:** Older `api/notes/*` scripts use a separate legacy
`notes` table and session. The primary student dashboard and semester
library intentionally use the existing `php/*` + `files` system,
not a mixture of both databases. Your local MySQL contents cannot be
accessed or migrated merely by updating GitHub.

