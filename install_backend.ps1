$ErrorActionPreference = "Stop"

Write-Host "Applying Gyankunja SQL backend patch..." -ForegroundColor Cyan

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectRoot

$htmlFiles = Get-ChildItem -Path $projectRoot -Filter *.html -File

foreach ($file in $htmlFiles) {
    $content = Get-Content -Path $file.FullName -Raw

    $content = $content.Replace("dashboard.html", "dashboard.php")

    if (
        $content.Contains('<script src="js/app.js"></script>') -and
        -not $content.Contains('<script src="js/backend-session.js"></script>')
    ) {
        $content = $content.Replace(
            '<script src="js/app.js"></script>',
            '<script src="js/app.js"></script><script src="js/backend-session.js"></script>'
        )
    }

    Set-Content -Path $file.FullName -Value $content -Encoding UTF8
}

if (Test-Path "$projectRoot\dashboard.html") {
    Write-Host "Keeping dashboard.html as a backup. The active dashboard is dashboard.php." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Patch applied." -ForegroundColor Green
Write-Host "Next: import database\gyankunja.sql in phpMyAdmin."
Write-Host "Then open: http://localhost/gyankunja/"
