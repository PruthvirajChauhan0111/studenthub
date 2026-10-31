-- Run this only after registering your own account through register.php.
-- Replace YOUR_USERNAME with the exact username you registered.
UPDATE users SET role = 'admin' WHERE username = 'YOUR_USERNAME';
