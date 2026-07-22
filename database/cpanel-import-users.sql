-- Kingdomcity Portal — users-only fix (safe on existing database)
-- Import into database: allthin2_portal
--
-- For a COMPLETE fresh install (all tables + seed data), use instead:
--   database/cpanel-full-import.sql
--
-- After import, sign in at /login with:
--   Email:    admin@kingdomcitychurchnanyuki.org
--   Password: password123
--
-- Change the password immediately after your first login.

-- ---------------------------------------------------------------------------
-- 1. Create users table (skipped if it already exists)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) DEFAULT 'member',
    magic_link_token VARCHAR(64) NULL,
    magic_link_expires DATETIME NULL,
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- 2. Seed login accounts (skipped if email already exists)
--    Password for all accounts below: password123
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO users (id, member_id, email, password, role, email_verified_at) VALUES
(1, NULL, 'admin@kingdomcitychurchnanyuki.org', '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'admin', NOW()),
(2, 1, 'james.kamau@email.com', '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'member', NOW()),
(3, 4, 'peter.ochieng@email.com', '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'member', NOW()),
(4, 6, 'faith.wanjiku@email.com', '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'member', NOW());

-- ---------------------------------------------------------------------------
-- 3. Reset admin email + password if an admin row already exists
-- ---------------------------------------------------------------------------
UPDATE users
SET email = 'admin@kingdomcitychurchnanyuki.org',
    password = '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG'
WHERE role = 'admin'
LIMIT 1;
