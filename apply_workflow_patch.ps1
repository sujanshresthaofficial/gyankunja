$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $root

Write-Host "Applying Gyankunja note approval workflow..." -ForegroundColor Cyan

# Add names and file restrictions to the dashboard upload form.
$dashboard = Join-Path $root "dashboard.php"

if (Test-Path $dashboard) {
    $content = Get-Content $dashboard -Raw

    $newForm = @'
<form id="uploadForm" enctype="multipart/form-data">
<div class="form-group"><label for="noteTitle">Note title</label><input class="form-control" id="noteTitle" name="title" placeholder="Enter note title" required></div>
<div class="form-row">
<div class="form-group"><label for="uploadSemester">Semester</label><select class="form-control" id="uploadSemester" name="semester" required></select></div>
<div class="form-group"><label for="noteSubject">Subject</label><input class="form-control" id="noteSubject" name="subject" placeholder="Subject name" required></div>
</div>
<div class="form-group"><label for="noteFile">Choose PDF</label><input accept="application/pdf,.pdf" class="form-control" id="noteFile" name="noteFile" required type="file"><small class="muted">PDF only, maximum 10 MB.</small></div>
<div class="form-group"><label for="noteDescription">Description</label><textarea class="form-control" id="noteDescription" name="description" placeholder="Brief description of the resource"></textarea></div>
<button class="btn btn-primary btn-block" type="submit">SUBMIT FOR REVIEW</button>
</form>
'@

    $pattern = '(?s)<form\s+id="uploadForm".*?</form>'
    $content = [regex]::Replace($content, $pattern, $newForm, 1)

    Set-Content $dashboard $content -Encoding UTF8
    Write-Host "Updated dashboard.php upload form." -ForegroundColor Green
} else {
    Write-Host "dashboard.php was not found. Use dashboard-upload-form.html manually." -ForegroundColor Yellow
}

# Replace sample cards in all semester pages.
$semesterPages = Get-ChildItem $root -Filter "semester*.html" -File

foreach ($page in $semesterPages) {
    $content = Get-Content $page.FullName -Raw

    $newSection = @'
<section class="section" id="featured" style="background:var(--surface-alt)">
<div class="container">
<h2 class="section-title">Faculty-Approved Notes</h2>
<p class="section-subtitle">Only notes reviewed and approved by authorized faculty members appear here.</p>
<div class="card-grid" id="approvedNotes"></div>
</div>
</section>
'@

    $sectionPattern = '(?s)<section\s+class="section"\s+id="featured".*?</section>'
    $content = [regex]::Replace($content, $sectionPattern, $newSection, 1)

    if (-not $content.Contains('js/approved-notes.js')) {
        $content = $content.Replace(
            '<script src="js/semester.js"></script>',
            '<script src="js/semester.js"></script><script src="js/approved-notes.js"></script>'
        )
    }

    $content = $content.Replace('href="dashboard.html"', 'href="dashboard.php"')
    Set-Content $page.FullName $content -Encoding UTF8
}

Write-Host "Updated semester pages." -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Import database\approval_workflow.sql in phpMyAdmin."
Write-Host "2. Promote a registered account to faculty in the users table."
Write-Host "3. Sign in as faculty and open faculty-review.php."
