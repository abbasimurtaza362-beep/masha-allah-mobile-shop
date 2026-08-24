-- One-time local admin credential reset for Masha Allah Mobile.
-- Run this file in the masha_allah_shop database through phpMyAdmin,
-- then delete this file from the web root.
START TRANSACTION;

UPDATE users
SET email = 'test@gmail.com',
    password_hash = '$2y$10$UrRqH50ju4h7EJ9ZKZkbl.XJAQte3OleatXEmfLQozd9hrj2nIckC',
    status = 'active',
    session_version = session_version + 1
WHERE role = 'admin'
ORDER BY id
LIMIT 1;

-- If no admin exists, create one. The INSERT is skipped when an admin already exists.
INSERT INTO users (name, email, password_hash, role, status)
SELECT 'Shop Administrator',
       'test@gmail.com',
       '$2y$10$UrRqH50ju4h7EJ9ZKZkbl.XJAQte3OleatXEmfLQozd9hrj2nIckC',
       'admin',
       'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'admin');

COMMIT;
