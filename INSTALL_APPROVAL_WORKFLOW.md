# Gyankunja Note Approval Workflow

## Result

The workflow becomes:

```text
User uploads PDF
        ↓
Database status = pending
        ↓
Faculty opens faculty-review.php
        ↓
Faculty approves or rejects
        ↓
Only approved notes appear on semester pages and the dashboard
```

The PDF itself is saved in:

```text
storage/notes/
```

Its title, uploader, semester, subject, stored path, file size and review
status are stored in the MySQL `notes` table.

Storing the binary PDF directly as a database BLOB is intentionally avoided.
The database stores the controlled file path and metadata, while PHP controls
who may open the PDF.

## 1. Extract the package

Extract this package into:

```text
C:\wamp64\www\gyankunja
```

Allow it to replace:

```text
js\dashboard.js
```

It adds the API endpoints, faculty page and review scripts.

## 2. Apply the HTML patch

Open PowerShell in the Gyankunja folder and run:

```powershell
powershell -ExecutionPolicy Bypass -File .\apply_workflow_patch.ps1
```

This will:

- Add proper `name` attributes to the upload form
- Limit the form to PDF files
- Replace sample Featured Notes with approved database notes
- Add `js/approved-notes.js` to Semester I–VIII pages

Manual snippets are also included if the PowerShell patch cannot find the
expected HTML.

## 3. Import the SQL migration

Open:

```text
http://localhost/phpmyadmin/
```

Choose the `gyankunja` database, select **Import**, and import:

```text
database\approval_workflow.sql
```

## 4. Create or promote a faculty account

First register the faculty member through the normal signup page.

Then run this SQL in phpMyAdmin:

```sql
UPDATE users
SET role = 'faculty'
WHERE email = 'faculty@gandakiuniversity.edu.np';
```

Replace the email with the registered faculty email.

Do not allow users to choose `faculty` from the public signup form. Faculty
role assignment must be done by an administrator.

## 5. Check PHP upload limits

Open the active WAMP `php.ini` and confirm:

```ini
file_uploads = On
upload_max_filesize = 12M
post_max_size = 13M
```

Restart all WAMP services after changing `php.ini`.

The application itself accepts PDF files up to 10 MB.

## 6. Test the complete workflow

### Student

1. Sign in as a student.
2. Open Dashboard.
3. Select **Upload Note**.
4. Upload a PDF.
5. The success message should say it is waiting for faculty approval.
6. In phpMyAdmin, the note should have:

```text
approval_status = pending
```

The note must not appear publicly yet.

### Faculty

1. Sign in using an account with `role = faculty`.
2. Open:

```text
http://localhost/gyankunja/faculty-review.php
```

3. Preview the PDF.
4. Approve it.

The record should now contain:

```text
approval_status = approved
reviewed_by = faculty user ID
reviewed_at = current date and time
```

### Public/student page

Open the matching semester page. The approved note should appear within
30 seconds or immediately after refreshing.

A rejected or pending note will not be returned by:

```text
api/notes/approved.php
```

## Main security rules

- Only authenticated users can upload.
- Students can upload only for their registered semester.
- Only `faculty` and `admin` roles can review.
- Only PDF files are accepted.
- MIME type is checked on the server.
- Stored filenames are random.
- The storage directory is blocked from direct web access.
- Pending files can be opened only by their uploader, faculty or admin.
- Public lists query only `approval_status = 'approved'`.
