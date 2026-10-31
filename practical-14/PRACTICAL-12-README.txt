STUDENTHUB - PRACTICAL 12
Event Management CRUD with Poster Upload and File Validation

WHAT IS INCLUDED
This folder contains the complete Practical 11 project as its starting point, plus the Practical 12 event management module. Practical 11 is not changed in your existing local folder; this ZIP contains a separate copy named practical-12.

NEW FILES
1. db_pdo.php - PDO connection to the existing studenthub database.
2. events-admin.php - Admin-only event Create, Read, Update and Delete module.
3. events-admin.css - Responsive StudentHub styling.
4. practical12_events.sql - SQL table definition.
5. uploads/.htaccess - Prevents directory listing and blocks script execution in uploads.

MODIFIED FILE
admin_dashboard.php - Adds a link to Manage Events while retaining the Student CRUD link.

DATABASE SETUP
1. Start Apache and MySQL in XAMPP.
2. Open http://localhost/phpmyadmin/ and select the studenthub database.
3. Open the SQL tab and run the contents of practical12_events.sql.
4. The new table is practical12_events. Existing students and users tables are not modified.

INSTALLATION
1. Extract this ZIP.
2. Copy the practical-12 folder into your XAMPP htdocs project folder.
   If your existing project is C:\xampp\htdocs\student, copy it to:
   C:\xampp\htdocs\student\practical-12
3. Ensure db_pdo.php uses your local MySQL username/password. Defaults are root and blank password.
4. Ensure practical-12/uploads exists and is writable by PHP. It is included in this ZIP.
5. Login with an account whose users.role is admin.

URLS (for C:\xampp\htdocs\student\practical-12)
Home: http://localhost/student/practical-12/index.html
Login: http://localhost/student/practical-12/login.php
Admin dashboard: http://localhost/student/practical-12/admin_dashboard.php
Event manager: http://localhost/student/practical-12/events-admin.php
Student manager: http://localhost/student/practical-12/manage-students.php

FEATURES
- Admin-only access using the existing session role check.
- Add, list, edit and delete events.
- Event title, description, category, date/time, location and open/closed status.
- Poster uploads accept JPG, PNG and WEBP only.
- Maximum poster size: 2 MB.
- Server-side MIME detection with finfo and image validation with getimagesize.
- Random server-generated poster filenames; uploaded original names are not trusted.
- Prepared statements with PDO.
- CSRF checks for create, update and delete.
- Search, category filter and open/closed status filter.
- Replacing or deleting an event removes its old poster file.

TESTING
1. Login as admin and open Manage Events from the dashboard.
2. Add an event without a poster.
3. Add an event with a valid JPG/PNG/WEBP poster under 2 MB.
4. Try a PDF or a file larger than 2 MB; it should be rejected.
5. Edit an event and replace its poster.
6. Filter by category and status; search by title or location.
7. Delete an event and confirm its poster file is removed.
8. Login as a student and try events-admin.php; access should be denied.

GITHUB
From your project root, add and commit only the new folder:
git add practical-12
git commit -m "Practical 12 - Event Management CRUD with Poster Upload"
git push origin main

NOTES
- The Practical 11 MySQLi files remain included for the existing login/dashboard/student management features.
- Practical 12 uses a separate PDO connection file so it does not break Practical 11.
- Existing tables are not dropped or altered.
