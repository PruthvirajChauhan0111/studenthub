PRACTICAL 10 - SECURE LOGIN AND LOGOUT USING PHP SESSIONS

AIM
Develop secure login and logout functionality using PHP sessions. Verify passwords with password_verify(), regenerate the session ID after login, restrict dashboard access, implement role-based redirection and session timeout.

NEW FILES
- auth.php: session configuration, CSRF helpers, session timeout, role protection and logout helper.
- login.php: verifies user credentials and starts a secure session.
- logout.php: validates a CSRF token and destroys the session.
- dashboard.php: student-only dashboard.
- admin_dashboard.php: admin-only dashboard and user summary.
- auth.css: responsive styling for login and dashboard pages.
- practical10-admin-role.sql: optional SQL to promote your own account to admin.

DATABASE REQUIREMENT
Uses the existing studenthub database and users table created in Practical 9. The users table must contain id, full_name, username, email, phone, course, password, role, status and created_at. Do not recreate the users table if it already exists.

IMPORTANT
1. Keep db_mysqli.php in this same folder.
2. Register a user through register.php before testing login.
3. New accounts default to role=student. To test admin access, run practical10-admin-role.sql after replacing YOUR_USERNAME with your own username.
4. Login URL: http://localhost/student/practical-10/login.php
5. Home URL: http://localhost/student/practical-10/index.html
6. Sessions expire after 20 minutes of inactivity.
7. Remember-me is optional in the assignment and is not implemented in this version.
8. This practical folder is a complete copy of Practical 9 plus the Practical 10 additions. Practical 9 itself remains unchanged.
