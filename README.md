# Gyankunja

A note-sharing website for **BIT (Bachelor of Information Technology)** students at **Gandaki University**.
Students browse notes by semester and subject, upload their own and a coordinator or admin approves each
upload before it becomes public.

**Tech stack:** HTML5, CSS3 and JavaScript on the front end · PHP 8 (PDO) on the back end · MySQL · runs on WAMP.

## Features

**Public (no login needed)**
- Semester I–VIII pages, each listing its subjects.
- Every subject has six categories: **Syllabus, Theory Notes, Lab Manuals, Question Bank, Books, References**. Click a category to see the approved notes inside it, with a download button.
- Approved notes appear automatically (page load, every 60 seconds and when you return to the tab).
- University examination notices feed (with a cached fallback), light/dark theme.

**Students**
- Sign up and log in. Accounts are stored in MySQL with hashed passwords.
- Dashboard with notes and exam info for their own semester.
- Upload a note by choosing semester, subject, **category** and file. It waits as *pending* until reviewed.
- **My Uploads** shows each upload as pending, approved or rejected, with the reviewer's remarks.
- **My Profile**: edit name, email, program, semester and password.

**Coordinators and admins** (same login page as students)
- Admin dashboard (`admin.html`) listing every student upload, filtered by status, semester or search text.
- Approve, reject or set back to pending, with optional remarks. Only an approved note is public.
- **Admins only:** delete uploads and create, edit and delete coordinator and admin accounts.
- Change their own name, email and password.

## Project structure

```
index.html, about.html, notices.html, resources.html     public pages
semester1.html to semester8.html                         public semester pages
login.html, signup.html                                  one login for students AND staff
dashboard.html                                           student dashboard (login required)
admin.html                                               admin / coordinator dashboard (staff login required)

css/    styles.css, admin.css
js/     app.js (theme, toast, logout)       auth.js (login + sign-up)        protect.js (guards the dashboard)
        courses.js (course list + categories, shared by semester pages and the upload form)
        semester.js, dashboard.js, admin.js, notices.js

php/    config.php            database connection, session settings, shared helpers
        auth_login.php        log in (students and staff)      auth_signup.php   create a student account
        auth_me.php           who is logged in                 auth_logout.php   log out
        profile_update.php    edit your own profile / password
        upload.php            receive an uploaded note         my_files.php      a student's own uploads
        list_files.php        public JSON of APPROVED notes    download.php      serves a file by id
        admin_files.php       staff: list uploads              admin_review.php  staff: approve / reject / delete
        admin_coordinators.php  admin: manage staff accounts   admin_setup.php   create the FIRST admin (one time)

database/  schema.sql, database_add_coordinators.sql, migration_v2.sql
uploads/   uploaded files (random names; blocked from direct URL access by .htaccess)
```

## Setup (WAMP)

1. Put the project in `C:\wamp64\www\gyankunja` and start WAMP (the icon must be green).
2. Create the database. In phpMyAdmin open the **SQL** tab and run these three files **in this order, once each**:
   1. `database/schema.sql`
   2. `database/database_add_coordinators.sql`
   3. `database/migration_v2.sql`
3. Check `php/config.php`. The defaults are the WAMP ones (`root`, empty password, database `gyankunja`). Change them if yours differ.
4. Create the first admin: open `http://localhost/gyankunja/php/admin_setup.php`, fill in the form, then log in at `login.html`.
   That page switches itself off as soon as an admin exists. Further coordinators and admins are created inside the admin dashboard.
5. Open `http://localhost/gyankunja/`.

> Open the site through `http://localhost/...` (WAMP). Opening the HTML files directly or with Live Server, cannot run the PHP.

### Upload size limit

The website allows up to `MAX_FILE_SIZE` (set in `php/config.php`) and `js/dashboard.js` checks the same number
before uploading. PHP also has its own limit in `php.ini`, which is **2 MB by default on WAMP** and the smaller
of the two wins. To allow larger notes:

1. WAMP icon → **PHP** → **php.ini**, then set `upload_max_filesize = 25M` and `post_max_size = 30M`.
2. WAMP icon → **Restart All Services**.
3. Update the size number and message in `js/dashboard.js` and `MAX_FILE_SIZE` in `php/config.php` to match.

## How a note gets published

1. A student uploads from the dashboard. The server stores the file under a random name in `uploads/` and saves a database row with status `pending`.
2. A coordinator or admin opens `admin.html`, views the file and approves or rejects it.
3. `php/list_files.php` returns only rows with status `approved`. The semester page shows them under the right subject and category.
4. `php/download.php` serves approved files to anyone. Pending and rejected files can only be opened by logged-in staff.

Notes from a coordinator or admin are published immediately, with no review step.

## Course codes and categories

The subject list for each semester lives in **one place: `js/courses.js`**. The semester pages and the upload form both read it, so a note always lands under the subject it was uploaded for. An upload is saved with the subject as `CODE - Title` (for example `ELX 121 - Digital Logic`) and matched by its code.

If you rename a course code, notes uploaded earlier under the old code show up under **Other Notes** on that semester page. The category list is also in `js/courses.js` and in `php/config.php` (`CATEGORIES`). Change both together.

## Security notes

- Passwords are hashed with `password_hash`. Logins use PHP sessions (HttpOnly, SameSite=Lax cookies).
- Whether someone is logged in is decided by the server session, not by the browser.
- Uploaded files have random names, a type whitelist and a size limit and cannot be opened by direct URL.
- All user-submitted text is escaped before it is shown on a page.
- Before putting this on a real server:
  - use a MySQL user with limited rights and a real password instead of `root` with no password,
  - serve the site over HTTPS,
  - add `uploads/*` to `.gitignore` so student files are not pushed to GitHub.

## University notice feed

`js/notices.js` tries the Gandaki University Office of the Controller of Examinations WordPress API
(`https://guexam.edu.np/wp-json/wp/v2/news` and `.../posts`). The browser may block this because of CORS.
When that happens the page shows a small cached list and links to the official notice page
(`https://guexam.edu.np/notice/`). For production, fetch the notices through a PHP proxy and cache them.

## Still to do

- Email-based password reset ("Forgot password" is still a placeholder).
- Dashboard exam dates, view counts and contribution numbers are sample data.
- Syllabus, books and references from the university are not yet loaded automatically; only student and staff uploads appear.
- A student-management page for admins and notifications when an upload is approved or rejected.

## Developers

Second-semester BIT students at Gandaki University:

- <a href="https://www.github.com/PrabhabTiwari" target="_blank">Prabhab Tiwari</a>
- <a href="https://www.github.com/sujanshresthaofficial" target="_blank">Sujan Shrestha</a>
- <a href="https://www.github.com/madhabkhanal" target="_blank">Madhab Khanal</a>