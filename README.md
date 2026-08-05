# gyankunja
A note sharing web application.
=======
# Gyankunja Front-End Website

A responsive HTML, CSS and JavaScript front-end for the Gyankunja academic note-sharing platform.

## Main pages

- `index.html` — landing page with a latest-notice preview
- `notices.html` — dedicated Gandaki University examination notice page
- `about.html` — project information and the three student developers
- `resources.html` — public semester resource directory
- `semester1.html` to `semester8.html` — public semester notes; no login required
- `login.html` — semester-based student login
- `signup.html` — student account registration
- `dashboard.html` — protected, semester-personalized student dashboard

## University notice feed

`js/notices.js` tries to fetch public notice data from the Gandaki University Office of the Controller of Examinations WordPress REST API:

- `https://guexam.edu.np/wp-json/wp/v2/news`
- `https://guexam.edu.np/wp-json/wp/v2/posts`

The official page is `https://guexam.edu.np/notice/`.

Because this project is front-end only, the university server must allow cross-origin browser requests. If the live request is unavailable or blocked by CORS, the page clearly switches to a small cached notice list and still links users to the official website. For production, route the request through your own PHP/Node backend proxy and cache the official data.

## Student developers

The About page identifies the project as developed by three second-semester Information Technology students:

- Prabhab Tiwari
- Sujan Shreshtha
- Madhab Khanal

## Demo authentication

Use the accounts in `DEMO-LOGIN-CREDENTIALS.txt`. Examples:

- Semester I: `BIT-SEM1-001` / `sem1@123`
- Semester II: `BIT-SEM2-001` / `sem2@123`
- Semester VIII: `BIT-SEM8-001` / `sem8@123`

The authentication is a browser-storage demonstration only. Replace it with server-side authentication, password hashing, sessions or secure tokens, database authorization and input validation before production use.

## Behaviour

- All Semester I–VIII resource pages are public.
- The dashboard redirects unauthenticated visitors to login.
- The dashboard displays notes and exam information based on the logged-in student's semester.
- Theme selection is saved in `localStorage` under `gyankunjaTheme`.
- Every page uses `images/fav.png` as its favicon.

## Run

Open the folder in VS Code and run `index.html` with Live Server. Internet access is needed for the live university notice request.