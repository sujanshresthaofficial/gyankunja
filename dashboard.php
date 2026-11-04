<?php
declare(strict_types=1);

define('GYANKUNJA_HTML_PAGE', true);

require __DIR__ . '/api/bootstrap.php';

header('Content-Type: text/html; charset=utf-8', true);

if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: login.html?required=1');
    exit;
}

$userJson = json_encode(
    $_SESSION['user'],
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta content="light dark" name="color-scheme"/>
<script>
  (() => {
    try {
      const savedTheme = localStorage.getItem('gyankunjaTheme');
      const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light';
      const theme = savedTheme || systemTheme;
      document.documentElement.dataset.theme = theme;
      document.documentElement.style.colorScheme = theme;

      sessionStorage.setItem('gyankunjaAuth', 'true');
      sessionStorage.setItem('gyankunjaUser', JSON.stringify(<?= $userJson ?>));
    } catch {
      document.documentElement.dataset.theme = 'light';
    }
  })();
</script>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Dashboard â€” Gyankunja</title>
<link href="images/fav.png" rel="icon" type="image/png"/>
<link href="css/styles.css" rel="stylesheet"/>
</head>
<body class="dashboard-body">
<div class="dashboard-layout">
<aside class="sidebar">
<a class="brand" href="dashboard.php"><span class="brand-mark">G</span><span class="brand-name">GYANKUNJA</span></a>
<nav class="side-nav">
<a class="side-link active" href="dashboard.php"><span class="side-icon">âŒ‚</span><span>My Home</span></a>
<a class="side-link" data-my-notes="" href="semester1.html"><span class="side-icon">â–¤</span><span>My Notes</span></a>
<a class="side-link" data-open-upload="" href="#"><span class="side-icon">â†¥</span><span>Uploads</span></a>
<a class="side-link" data-demo-action="Saved notes will load from the database later." href="#"><span class="side-icon">â˜…</span><span>Saved</span></a>
<a class="side-link" data-demo-action="Group management will connect to the backend later." href="#"><span class="side-icon">â—Ž</span><span>Groups</span></a>
<a class="side-link" href="notices.html"><span class="side-icon">â—‰</span><span>Notices</span></a>
<a class="side-link" href="about.html"><span class="side-icon">i</span><span>About</span></a>
</nav>
<div class="sidebar-bottom">
<button class="side-link" data-logout="" style="width:100%;border:0;background:transparent">
<span class="side-icon">â†ª</span><span>Logout</span>
</button>
</div>
</aside>

<div class="dashboard-main">
<header class="dashboard-topbar">
<button aria-label="Open sidebar" class="mobile-side-toggle">â˜°</button>
<div class="dashboard-search"><input id="dashboardSearch" placeholder="Search your recent notesâ€¦" type="search"/></div>
<button aria-label="Switch to dark mode" class="theme-toggle" data-theme-toggle="" title="Switch to dark mode" type="button"><span aria-hidden="true" data-theme-icon="">â˜¾</span></button>
<div class="user-menu"><div><strong data-user-firstname="">Student</strong><div class="muted" data-user-semester="" style="font-size:.72rem">Semester I</div></div><div class="user-avatar" data-user-initials="">ST</div></div>
</header>

<main class="dashboard-content">
<section class="welcome-card">
<div class="welcome-user"><div class="user-avatar" data-user-initials="">ST</div><div><h1>Welcome, <span data-user-name="">Student</span>!</h1><p data-user-email="">student@gyankunja.edu.np</p><p style="font-size:.84rem;margin-top:5px">Your personalized study dashboard is ready.</p></div></div>
<button class="btn btn-primary" data-open-upload="">Upload Note</button>
</section>

<section class="dashboard-section">
<div class="dashboard-section-head"><div><h2><span data-dashboard-semester="">Semester I</span> Notes</h2><p>Only resources for your registered semester are shown inside your dashboard.</p></div><a class="btn btn-outline btn-sm" data-my-notes="" href="semester1.html">See all</a></div>
<div class="dashboard-note-grid" id="dashboardNotes"></div>
</section>

<section class="dashboard-section exam-contrib-grid">
<div class="panel"><div class="dashboard-section-head"><div><h2>Exam Schedule</h2><p>Your upcoming academic events.</p></div></div><div class="exam-list" id="dashboardExams"></div></div>
<div class="panel"><div class="dashboard-section-head"><div><h2>My Contributions</h2><p>Track the value you add to the community.</p></div></div><div class="contribution-grid"><div class="contribution-card"><strong>1,842</strong><span class="muted">Total reputation</span></div><div class="contribution-card"><strong data-upload-count="">18</strong><span class="muted">Resources uploaded</span></div><div class="contribution-card"><strong>96</strong><span class="muted">Helpful votes</span></div></div><div class="chart-box"><svg class="chart-svg" preserveaspectratio="none" viewbox="0 0 600 180"><defs><lineargradient id="fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#174ea6" stop-opacity=".30"></stop><stop offset="1" stop-color="#174ea6" stop-opacity=".03"></stop></lineargradient></defs><path d="M0,70 C55,45 80,160 145,130 S215,30 275,95 S350,130 405,72 S475,115 525,55 S575,60 600,42 L600,180 L0,180Z" fill="url(#fill)"></path><path d="M0,70 C55,45 80,160 145,130 S215,30 275,95 S350,130 405,72 S475,115 525,55 S575,60 600,42" fill="none" stroke="#174ea6" stroke-width="3"></path></svg></div></div>
</section>
</main>
</div>
</div>

<div class="modal" id="uploadModal">
<div class="modal-card">
<div class="modal-head"><h2>Upload a New Note</h2><button class="icon-button" data-close-modal="">âœ•</button></div>
<form
    id="uploadForm"
    action="api/notes/upload.php"
    method="post"
    enctype="multipart/form-data"
>
<div class="form-group"><label for="noteTitle">Note title</label><input class="form-control" id="noteTitle" name="title" placeholder="Enter note title" required></div>
<div class="form-row">
<div class="form-group"><label for="uploadSemester">Semester</label><select class="form-control" id="uploadSemester" name="semester" required></select></div>
<div class="form-group"><label for="noteSubject">Subject</label><input class="form-control" id="noteSubject" name="subject" placeholder="Subject name" required></div>
</div>
<div class="form-group"><label for="noteFile">Choose PDF</label><input accept="application/pdf,.pdf" class="form-control" id="noteFile" name="noteFile" required type="file"><small class="muted">PDF only, maximum 10 MB.</small></div>
<div class="form-group"><label for="noteDescription">Description</label><textarea class="form-control" id="noteDescription" name="description" placeholder="Brief description of the resource"></textarea></div>
<button
    class="btn btn-primary btn-block"
    id="uploadNoteButton"
    type="submit"
>
    SUBMIT FOR REVIEW
</button>
</form>
</div>
</div>

<script src="js/app.js"></script>
<script src="js/backend-session.js"></script>
<script src="js/dashboard.js?v=20"></script>

<script>
(() => {
  
    const uploadSemester = document.getElementById('uploadSemester');
    const uploadModal = document.getElementById('uploadModal');

    const currentUser = <?= $userJson ?>;

    if (!uploadForm) {
        console.error('Upload form was not found.');
        return;
    }

    // Ensure semester has a value
    if (uploadSemester && uploadSemester.options.length === 0) {
        const option = document.createElement('option');
        option.value = currentUser.semester;
        option.textContent = currentUser.semester;
        uploadSemester.appendChild(option);
    }

    uploadForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        // Stop an older dashboard.js handler from interfering
        event.stopImmediatePropagation();

        const submitButton = uploadForm.querySelector(
            'button[type="submit"]'
        );

        const formData = new FormData(uploadForm);

        console.log('Sending upload to backend...');
        console.log('Title:', formData.get('title'));
        console.log('Semester:', formData.get('semester'));
        console.log('Subject:', formData.get('subject'));
        console.log('File:', formData.get('noteFile'));

        submitButton.disabled = true;
        submitButton.textContent = 'UPLOADING...';

        try {
            const response = await fetch('api/notes/upload.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            const responseText = await response.text();

            console.log('Upload status:', response.status);
            console.log('Upload response:', responseText);

            let data;

            try {
                data = JSON.parse(responseText);
            } catch {
                throw new Error(
                    'PHP returned an invalid response: ' + responseText
                );
            }

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || 'The note could not be uploaded.'
                );
            }

            alert(data.message);

            uploadForm.reset();

            if (uploadSemester) {
                uploadSemester.value = currentUser.semester;
            }

            uploadModal?.classList.remove('open');

        } catch (error) {
            console.error('Upload error:', error);
            alert(error.message);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'SUBMIT FOR REVIEW';
        }
    }, true);
})();
</script>

<script src="js/app.js"></script>
<script src="js/backend-session.js"></script>
<script src="js/dashboard.js?v=21"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('uploadForm');
    const button = document.getElementById('uploadNoteButton');
    const modal = document.getElementById('uploadModal');

    if (!form || !button) {
        console.error('Upload form or button was not found.');
        return;
    }

    console.log('Gyankunja upload handler loaded.');

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        if (!form.reportValidity()) {
            return;
        }

        const fileInput = document.getElementById('noteFile');
        const semesterInput = document.getElementById('uploadSemester');

        if (!semesterInput || !semesterInput.value) {
            alert('Semester is missing.');
            return;
        }

        if (!fileInput || !fileInput.files.length) {
            alert('Please select a PDF file.');
            return;
        }

        const file = fileInput.files[0];

        if (
            file.type !== 'application/pdf' &&
            !file.name.toLowerCase().endsWith('.pdf')
        ) {
            alert('Only PDF files are allowed.');
            return;
        }

        const formData = new FormData(form);

        console.log('Sending note to api/notes/upload.php');
        console.log('Title:', formData.get('title'));
        console.log('Semester:', formData.get('semester'));
        console.log('Subject:', formData.get('subject'));
        console.log('File:', formData.get('noteFile'));

        button.disabled = true;
        button.textContent = 'UPLOADING...';

        try {
            const response = await fetch('api/notes/upload.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            const responseText = await response.text();

            console.log('Status:', response.status);
            console.log('Response:', responseText);

            let data;

            try {
                data = JSON.parse(responseText);
            } catch (error) {
                throw new Error(
                    'Invalid PHP response: ' + responseText
                );
            }

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || 'The note could not be uploaded.'
                );
            }

            alert(data.message);

            form.reset();

            semesterInput.value = <?= json_encode(
                $_SESSION['user']['semester']
            ) ?>;

            modal?.classList.remove('open');

            location.reload();
        } catch (error) {
            console.error('Upload failed:', error);
            alert(error.message);
        } finally {
            button.disabled = false;
            button.textContent = 'SUBMIT FOR REVIEW';
        }
    }, true);
});
</script>
</body>
</html>
</body>
</html>

