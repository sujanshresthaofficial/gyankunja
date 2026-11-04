# Gyankunja MySQL Backend Installation

This package connects the existing Gyankunja signup and login pages to a
MySQL database using PHP, PDO, password hashing and PHP sessions.

## 1. Put the project inside WAMP

Copy or clone the project to:

```text
C:\wamp64\www\gyankunja
```

Extract this package into the **root of that project**. Allow it to replace:

```text
js\auth.js
```

It will also add:

```text
api\
database\
js\backend-session.js
dashboard.php
install_backend.ps1
```

## 2. Apply the page-link patch

Open PowerShell in the Gyankunja project folder and run:

```powershell
powershell -ExecutionPolicy Bypass -File .\install_backend.ps1
```

This changes `dashboard.html` links to `dashboard.php` and loads
`js/backend-session.js` after `js/app.js`.

## 3. Start WAMP

Make sure the WAMP icon is green and these services are running:

- Apache
- MySQL

## 4. Import the database

Open:

```text
http://localhost/phpmyadmin/
```

Choose **Import**, select:

```text
database\gyankunja.sql
```

Then click **Import/Go**.

The SQL creates:

- `gyankunja` database
- `users` table
- `notes` table

## 5. Check database credentials

Open:

```text
api\config.php
```

WAMP commonly uses:

```php
'username' => 'root',
'password' => '',
```

Change them when your MySQL account uses another password.

## 6. Run the website correctly

Do not use VS Code Live Server for PHP.

Open:

```text
http://localhost/gyankunja/
```

## 7. Test

1. Open Sign Up.
2. Create a new student account.
3. Check the `users` table in phpMyAdmin.
4. Sign in using the email or University ID.
5. Confirm that `dashboard.php` opens.
6. Click Logout and confirm the session is closed.

## Important

The SQL connection in this package is implemented for user registration,
login, server-side session protection and logout. The `notes` table is ready,
but the existing Upload Note form still needs a separate PHP upload endpoint
to save PDF files and note records.
