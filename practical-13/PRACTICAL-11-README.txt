PRACTICAL 11 - STUDENT MANAGEMENT CRUD USING PHP AND MYSQLI

AIM
Develop a secure student management module to create, read, update and delete student records using PHP, MySQL and MySQLi prepared statements. Include searching and course filtering.

NEW FILES
- manage-students.php: admin-only add, list, search, filter, edit and delete operations.
- manage-students.css: responsive UI matching the StudentHub navy/orange theme.
- PRACTICAL-11-README.txt: setup and testing instructions.

MODIFIED FILES
- admin_dashboard.php: adds a shortcut to the Student CRUD manager.

DATABASE
Uses the existing studenthub database and the Practical 8 students table. Expected columns: student_id (INT AUTO_INCREMENT PRIMARY KEY), full_name (VARCHAR(100)), email (VARCHAR(150)), course (VARCHAR(100)), created_at (TIMESTAMP DEFAULT CURRENT_TIMESTAMP). Do not recreate the table if it already exists.

If the students table does not exist, run this SQL in phpMyAdmin after selecting studenthub:
CREATE TABLE students (
 student_id INT AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(100) NOT NULL,
 email VARCHAR(150) NOT NULL,
 course VARCHAR(100) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_students_email (email)
);

REQUIREMENTS
- Keep this folder inside C:\xampp\htdocs\student\practical-11\.
- Keep db_mysqli.php in this folder; it connects to studenthub using localhost/root/blank password as configured in your Practical 10 ZIP.
- The students table must use the Practical 8 schema above.
- Only users whose users.role is admin can open manage-students.php.
- Promote your own account to admin only if it is currently a student: UPDATE users SET role='admin' WHERE username='YOUR_USERNAME';

RUN
1. Start Apache and MySQL in XAMPP.
2. Open http://localhost/phpmyadmin/ and confirm the studenthub database and students table exist.
3. Open http://localhost/student/practical-11/login.php and sign in with an admin account.
4. Open http://localhost/student/practical-11/admin_dashboard.php and click Manage Student Records.
5. Test Add, Edit, Delete, search by name/email/ID and course filter.

SECURITY
The CRUD page requires an admin session, applies the existing 20-minute session timeout, validates CSRF tokens for add/update/delete, escapes displayed values and uses prepared statements for all user-supplied database values.

PRACTICAL 10 IS PRESERVED
This folder is a copy of the supplied Practical 10 ZIP with the Practical 11 CRUD module added. The original Practical 10 folder/archive is not modified.
