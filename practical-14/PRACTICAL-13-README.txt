STUDENTHUB - PRACTICAL 13
PHP JSON API and AJAX/Fetch-Based CRUD without Page Reload

PRACTICAL 13 REQUIREMENTS
- PHP JSON API endpoints connected to the MySQL events table.
- Fetch API list, add, update and delete operations.
- No full-page reload during CRUD operations.
- Valid JSON responses and correct GET, POST, PUT and DELETE methods.
- Frontend loading, success and error states.
- Server-side input validation before database operations.

NEW FILES
1. api/events.php - Admin-protected JSON API. GET lists events; POST adds; PUT updates; DELETE removes.
2. events-ajax.php - Practical 13 user interface and CSRF token setup.
3. events-ajax.js - Fetch API requests, rendering, search, filters and asynchronous CRUD.
4. events-ajax.css - Responsive Practical 13 page styling.
5. practical13_events.sql - Creates a separate Practical 13 events table.
6. PRACTICAL-13-README.txt - Setup and testing instructions.

MODIFIED FILE
- admin_dashboard.php - Adds a link to the Practical 13 AJAX Event CRUD page. Existing links remain.

DATABASE
Practical 13 uses its own table: practical13_events. Practical 12 continues to use practical12_events. The two practicals therefore have separate event records.

Import practical13_events.sql into the existing studenthub database using phpMyAdmin. Do not drop, rename, or modify practical12_events. Keep practical12_events.sql for Practical 12.

INSTALLATION
1. Extract the Practical 13 ZIP.
2. Copy the project folder into C:\xampp\htdocs\studenthub (or merge the files into your existing StudentHub project folder).
3. Start Apache and MySQL in XAMPP.
4. Open phpMyAdmin, select the studenthub database, and import practical13_events.sql. Confirm that both practical12_events and practical13_events exist.
5. Check db_pdo.php for your local MySQL username and password. Default XAMPP settings are root with a blank password.
6. Log in with an administrator account.
7. Open http://localhost/studenthub/admin_dashboard.php.
8. Click "Practical 13 - AJAX Event CRUD".

API ENDPOINT
URL: api/events.php
GET    api/events.php                 List all events
GET    api/events.php?search=tech     Search events
POST   api/events.php                 Add event (JSON body)
PUT    api/events.php                 Update event (JSON body with event_id)
DELETE api/events.php                 Delete event (JSON body with event_id)

JSON BODY FOR ADD / UPDATE
{
  "title": "Tech Innovation Day",
  "description": "A student technology event.",
  "category": "Workshop",
  "event_date": "2026-11-15T10:30",
  "location": "CHARUSAT Campus",
  "status": "open"
}
For PUT and DELETE, also send "event_id": 1 (replace 1 with the selected event ID).
Write requests include the CSRF token in the X-CSRF-Token request header. The browser code handles this automatically.

TESTING
1. Open Practical 13 while logged in as admin. Event records should load from MySQL.
2. Add a new event. It should appear in the list without reloading the page.
3. Edit an event and save the changes. Confirm the updated values appear.
4. Delete an event and confirm it disappears without a page reload.
5. Search by event title, description or location.
6. Filter by category and Open/Closed status.
7. Submit empty or over-length fields and confirm server-side validation messages appear.
8. Open browser Developer Tools > Network. Confirm requests use GET, POST, PUT and DELETE and responses have application/json content type.
9. Try the API while logged out or as a non-admin user. The API should return JSON 401 or 403 responses.
10. Check the browser console for JavaScript errors.

IMPORTANT
- Practical 12 events-admin.php and its poster-upload workflow remain unchanged.
- Practical 12 uses practical12_events; Practical 13 uses practical13_events. Adding, editing, or deleting Practical 13 events will not change Practical 12 records.
- Practical 13 edits event text/details. Poster upload remains part of Practical 12, not Practical 13.
- All database operations in this API use PDO prepared statements.
- The API returns appropriate HTTP status codes, including 201 for successful creation, 400 for invalid IDs, 401 for login required, 403 for access/CSRF errors, 404 for missing records, 405 for unsupported methods and 422 for validation errors.

GITHUB
From the project root:
git add .
git commit -m "Practical 13 - PHP JSON API and Fetch CRUD"
git push origin main
