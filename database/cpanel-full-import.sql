-- =============================================================================
-- Kingdomcity Portal — COMPLETE database import for cPanel / phpMyAdmin
-- =============================================================================
--
-- IMPORT INTO: allthin2_Admin  (or your portal database name)
--
-- WARNING: This file DROPS existing portal tables before recreating them.
--          All current data will be erased.
--
-- Prefer the SAFE update (keeps members) if the DB already has live data:
--   database/cpanel-update-allthin2.sql
--
-- Does NOT touch the forms database. Import shared-form-submissions.sql
-- separately into that database if needed.
--
-- After import:
--   1. Set APP_INSTALLED=true in .env
--   2. Log in at /login
--      Email:    admin@kingdomcitychurchnanyuki.org
--      Password: password123
--   3. Change the admin password immediately
--
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop all portal tables (safe if they do not exist)
DROP TABLE IF EXISTS finance_budget_monthly;
DROP TABLE IF EXISTS finance_budget_lines;
DROP TABLE IF EXISTS finance_expense_categories;
DROP TABLE IF EXISTS finance_expense_departments;
DROP TABLE IF EXISTS finance_expense_arrears;
DROP TABLE IF EXISTS finance_weekly_expenses;
DROP TABLE IF EXISTS finance_weekly_collections;
DROP TABLE IF EXISTS finance_collections;
DROP TABLE IF EXISTS finance_collection_methods;
DROP TABLE IF EXISTS finance_sunday_sessions;
DROP TABLE IF EXISTS finance_weekly_categories;
DROP TABLE IF EXISTS staff_member_images;
DROP TABLE IF EXISTS staff_members;
DROP TABLE IF EXISTS inventory_items;
DROP TABLE IF EXISTS attendance_records;
DROP TABLE IF EXISTS ministry_members;
DROP TABLE IF EXISTS cell_group_members;
DROP TABLE IF EXISTS pledges;
DROP TABLE IF EXISTS mobile_money_statements;
DROP TABLE IF EXISTS contributions;
DROP TABLE IF EXISTS pledge_campaigns;
DROP TABLE IF EXISTS communications;
DROP TABLE IF EXISTS sms_logs;
DROP TABLE IF EXISTS onboarding_qr_codes;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS household_children;
DROP TABLE IF EXISTS ministries;
DROP TABLE IF EXISTS cell_groups;
DROP TABLE IF EXISTS members;
DROP TABLE IF EXISTS households;
DROP TABLE IF EXISTS attendance_sessions;
DROP TABLE IF EXISTS funds;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS visitor_feedback;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- CORE SCHEMA (schema.mysql.sql)
-- =============================================================================

CREATE TABLE households (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    phone VARCHAR(30),
    head_member_id INT,
    anniversary_date DATE,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(30),
    gender VARCHAR(100),
    date_of_birth DATE,
    marital_status VARCHAR(30),
    spouse_name VARCHAR(150),
    residence VARCHAR(255),
    county VARCHAR(100),
    occupation VARCHAR(100),
    employer VARCHAR(150),
    emergency_contact_name VARCHAR(150),
    emergency_contact_phone VARCHAR(30),
    how_heard_about_us VARCHAR(100),
    previous_church VARCHAR(200),
    baptized TINYINT(1) DEFAULT 0,
    baptism_date DATE,
    wish_to_be_baptized TINYINT(1) DEFAULT 0,
    ministry_interests TEXT,
    skills_talents TEXT,
    member_notes TEXT,
    photo_url VARCHAR(255),
    is_head_of_household TINYINT(1) DEFAULT 0,
    membership_status VARCHAR(30) DEFAULT 'active',
    joined_date DATE,
    onboarding_token VARCHAR(64) UNIQUE,
    onboarding_completed TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE household_children (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    age INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT,
    username VARCHAR(80) NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    display_name VARCHAR(150) NULL,
    avatar_path VARCHAR(255) NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) DEFAULT 'member',
    magic_link_token VARCHAR(64),
    magic_link_expires DATETIME,
    email_verified_at DATETIME,
    last_login_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE attendance_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    type VARCHAR(50) NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME,
    location VARCHAR(200),
    notes TEXT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    member_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'present',
    checked_in_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    UNIQUE KEY uniq_session_member (session_id, member_id),
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE funds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    household_id INT,
    fund_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'cash',
    transaction_ref VARCHAR(100),
    contribution_date DATE NOT NULL,
    notes TEXT,
    sms_sent TINYINT(1) DEFAULT 0,
    recorded_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (household_id) REFERENCES households(id),
    FOREIGN KEY (fund_id) REFERENCES funds(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pledge_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    target_amount DECIMAL(14,2),
    start_date DATE,
    end_date DATE,
    fund_id INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fund_id) REFERENCES funds(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pledges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    member_id INT NOT NULL,
    pledged_amount DECIMAL(12,2) NOT NULL,
    amount_paid DECIMAL(12,2) DEFAULT 0,
    pledge_date DATE,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES pledge_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ministries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    leader_id INT,
    meeting_day VARCHAR(30),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leader_id) REFERENCES members(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ministry_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ministry_id INT NOT NULL,
    member_id INT NOT NULL,
    role VARCHAR(50) DEFAULT 'member',
    joined_date DATE,
    UNIQUE KEY uniq_ministry_member (ministry_id, member_id),
    FOREIGN KEY (ministry_id) REFERENCES ministries(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cell_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    leader_id INT,
    meeting_day VARCHAR(30),
    meeting_time TIME,
    location TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leader_id) REFERENCES members(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cell_group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cell_group_id INT NOT NULL,
    member_id INT NOT NULL,
    UNIQUE KEY uniq_cell_member (cell_group_id, member_id),
    FOREIGN KEY (cell_group_id) REFERENCES cell_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE communications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    channel VARCHAR(20) NOT NULL,
    audience VARCHAR(50) DEFAULT 'all',
    status VARCHAR(20) DEFAULT 'draft',
    scheduled_at DATETIME,
    sent_at DATETIME,
    sent_count INT DEFAULT 0,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sms_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT,
    phone VARCHAR(30),
    message TEXT,
    type VARCHAR(50),
    status VARCHAR(20),
    provider_ref VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mobile_money_statements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50),
    transaction_ref VARCHAR(100) UNIQUE,
    phone VARCHAR(30),
    amount DECIMAL(12,2),
    transaction_date DATETIME,
    raw_payload TEXT,
    matched_contribution_id INT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (matched_contribution_id) REFERENCES contributions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE onboarding_qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    label VARCHAR(150),
    is_active TINYINT(1) DEFAULT 1,
    scan_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE visitor_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    spouse_name VARCHAR(150) NULL,
    children_names TEXT NULL,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(150) NOT NULL,
    review TEXT NULL,
    how_heard_about_us VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_members_household ON members(household_id);
CREATE INDEX idx_members_email ON members(email);
CREATE INDEX idx_household_children_household ON household_children(household_id);
CREATE INDEX idx_attendance_member ON attendance_records(member_id);
CREATE INDEX idx_contributions_member ON contributions(member_id);
CREATE INDEX idx_contributions_date ON contributions(contribution_date);

-- =============================================================================
-- FINANCE SCHEMA (finance-reconciliation.sql + finance-budget.sql)
-- =============================================================================

CREATE TABLE finance_expense_arrears (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    expense_item VARCHAR(255) NOT NULL,
    category_id INT UNSIGNED NULL,
    month_incurred VARCHAR(120) NOT NULL,
    amount_due DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    date_paid DATE NULL,
    paid_by_ref VARCHAR(255) NULL,
    notes TEXT NULL,
    budget_year SMALLINT UNSIGNED NOT NULL DEFAULT 2026,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_budget_year (budget_year),
    KEY idx_category_id (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE finance_weekly_expenses (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    week_date DATE NOT NULL COMMENT 'Sunday service date',
    category_slug VARCHAR(50) NOT NULL,
    category_label VARCHAR(120) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_week_category (week_date, category_slug),
    KEY idx_week_date (week_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NULL,
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(50) NULL DEFAULT 'pcs',
    location VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE finance_weekly_categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(50) NOT NULL,
    label VARCHAR(120) NOT NULL,
    hint VARCHAR(255) NULL DEFAULT '',
    department_id INT UNSIGNED NULL,
    expense_category_id INT UNSIGNED NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_slug (slug),
    KEY idx_weekly_department_id (department_id),
    KEY idx_weekly_expense_category_id (expense_category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE finance_collection_methods (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(50) NOT NULL,
    label VARCHAR(120) NOT NULL,
    hint VARCHAR(255) NULL DEFAULT '',
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE finance_collections (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    collection_date DATE NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    reference VARCHAR(255) NULL,
    fund_type VARCHAR(100) NULL,
    notes TEXT NULL,
    budget_year SMALLINT UNSIGNED NOT NULL DEFAULT 2026,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_collection_date (collection_date),
    KEY idx_payment_method (payment_method),
    KEY idx_budget_year (budget_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE finance_weekly_collections (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    week_date DATE NOT NULL COMMENT 'Sunday service date',
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_week_method (week_date, payment_method),
    KEY idx_week_date (week_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE finance_sunday_sessions (
    week_date DATE NOT NULL COMMENT 'Sunday service date',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (week_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE finance_budget_lines (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    budget_year SMALLINT UNSIGNED NOT NULL COMMENT 'Start year of FY e.g. 2026 for FY 2026/2027',
    line_type ENUM('income', 'expense') NOT NULL,
    section VARCHAR(80) NOT NULL DEFAULT '',
    label VARCHAR(160) NOT NULL,
    account_code VARCHAR(32) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_budget_year_code (budget_year, account_code),
    KEY idx_budget_year_type (budget_year, line_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE finance_budget_monthly (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    budget_line_id INT UNSIGNED NOT NULL,
    budget_month CHAR(7) NOT NULL COMMENT 'YYYY-MM calendar month',
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uk_line_month (budget_line_id, budget_month),
    CONSTRAINT fk_budget_monthly_line
        FOREIGN KEY (budget_line_id) REFERENCES finance_budget_lines (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE finance_expense_departments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(64) NOT NULL,
    label VARCHAR(160) NOT NULL,
    expense_group VARCHAR(32) NOT NULL DEFAULT 'ministry_departments',
    code_prefix VARCHAR(32) NOT NULL COMMENT 'e.g. 001/2',
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    is_system TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_slug (slug),
    UNIQUE KEY uk_code_prefix (code_prefix),
    KEY idx_expense_group (expense_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE finance_expense_categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    department_id INT UNSIGNED NOT NULL,
    slug VARCHAR(64) NOT NULL,
    label VARCHAR(160) NOT NULL,
    account_code VARCHAR(32) NOT NULL COMMENT 'e.g. 001/2/001',
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    is_system TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_slug (slug),
    UNIQUE KEY uk_account_code (account_code),
    KEY idx_department (department_id),
    CONSTRAINT fk_expense_category_department
        FOREIGN KEY (department_id) REFERENCES finance_expense_departments (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE staff_members (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    role_title VARCHAR(150) NULL,
    department VARCHAR(150) NULL,
    phone VARCHAR(64) NULL,
    email VARCHAR(255) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    bio TEXT NULL,
    staff_code VARCHAR(80) NULL,
    gender VARCHAR(30) NULL,
    date_of_birth DATE NULL,
    national_id VARCHAR(80) NULL,
    marital_status VARCHAR(40) NULL,
    address VARCHAR(255) NULL,
    city VARCHAR(120) NULL,
    campus VARCHAR(50) NULL,
    employment_type VARCHAR(40) NULL DEFAULT 'full_time',
    hire_date DATE NULL,
    end_date DATE NULL,
    reports_to VARCHAR(150) NULL,
    secondary_phone VARCHAR(64) NULL,
    emergency_contact_name VARCHAR(150) NULL,
    emergency_contact_phone VARCHAR(64) NULL,
    emergency_contact_relation VARCHAR(80) NULL,
    skills TEXT NULL,
    ministries TEXT NULL,
    education TEXT NULL,
    languages VARCHAR(255) NULL,
    work_schedule VARCHAR(255) NULL,
    office_location VARCHAR(255) NULL,
    photo_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_staff_status (status),
    KEY idx_staff_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE staff_member_images (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    staff_id INT UNSIGNED NOT NULL,
    path VARCHAR(255) NOT NULL,
    caption VARCHAR(255) NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_staff_images_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED DATA (aligned with allthin2_Admin dump + finance schema v3)
-- Password for all accounts below: password123
-- =============================================================================

INSERT INTO funds (id, name, code, description) VALUES
(1, 'Tithe', 'TITHE', 'Regular tithe contributions'),
(2, 'General Offering', 'OFFERING', 'Sunday and special offerings'),
(3, 'Building Fund', 'BUILDING', 'Church building project'),
(4, 'Missions', 'MISSIONS', 'Missions and outreach'),
(5, 'Youth Ministry', 'YOUTH', 'Youth programs and activities');

INSERT INTO households (id, name, address, city, phone) VALUES
(1, 'Kamau Family', '45 Oak Street', 'Nairobi', '+254712345678'),
(2, 'Ochieng Family', '12 River Road', 'Nairobi', '+254723456789'),
(3, 'Wanjiku Family', '8 Hill View', 'Nairobi', '+254734567890');

-- Members sorted by last_name, first_name (ids preserved for FKs)
INSERT INTO members (id, household_id, first_name, last_name, email, phone, gender, date_of_birth, is_head_of_household, membership_status, joined_date, onboarding_completed) VALUES
(3, 1, 'David', 'Kamau', 'david.kamau@email.com', NULL, 'male', '2010-11-05', 0, 'active', '2020-01-12', 0),
(2, 1, 'Grace', 'Kamau', 'grace.kamau@email.com', '+254712345679', 'female', '1988-07-22', 0, 'active', '2020-01-12', 1),
(1, 1, 'James', 'Kamau', 'james.kamau@email.com', '+254712345678', 'male', '1985-03-15', 1, 'active', '2020-01-12', 1),
(5, 2, 'Mary', 'Ochieng', 'mary.ochieng@email.com', '+254723456790', 'female', '1982-12-14', 0, 'active', '2019-06-20', 1),
(4, 2, 'Peter', 'Ochieng', 'peter.ochieng@email.com', '+254723456789', 'male', '1978-09-30', 1, 'active', '2019-06-20', 1),
(6, 3, 'Faith', 'Wanjiku', 'faith.wanjiku@email.com', '+254734567890', 'female', '1990-05-08', 1, 'active', '2021-03-01', 1);

UPDATE households SET head_member_id = 1 WHERE id = 1;
UPDATE households SET head_member_id = 4 WHERE id = 2;
UPDATE households SET head_member_id = 6 WHERE id = 3;

INSERT INTO users (id, member_id, username, email, password, role, email_verified_at) VALUES
(1, NULL, 'Admin', 'admin@kingdomcitychurchnanyuki.org', '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'admin', NOW()),
(2, 1, 'james.kamau', 'james.kamau@email.com', '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'member', NOW()),
(3, 4, 'peter.ochieng', 'peter.ochieng@email.com', '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'member', NOW()),
(4, 6, 'faith.wanjiku', 'faith.wanjiku@email.com', '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'member', NOW());

INSERT INTO ministries (id, name, description, leader_id, meeting_day) VALUES
(1, 'Praise & Worship', 'Music ministry and worship team', 6, 'Thursday'),
(2, 'Ushers', 'Ushering and guest services', 1, 'Saturday'),
(3, 'Youth Ministry', 'Teens and young adults', 4, 'Friday'),
(4, 'Sunday School', 'Children ministry', 2, 'Sunday');

INSERT INTO ministry_members (ministry_id, member_id, role) VALUES
(1, 6, 'leader'), (1, 2, 'member'),
(2, 1, 'leader'), (2, 4, 'member'),
(3, 4, 'leader'), (3, 3, 'member'),
(4, 2, 'leader'), (4, 5, 'member');

INSERT INTO cell_groups (id, name, leader_id, meeting_day, meeting_time, location) VALUES
(1, 'Faith Cell - Westlands', 1, 'Wednesday', '18:30:00', 'Kamau Residence'),
(2, 'Hope Cell - Karen', 4, 'Tuesday', '19:00:00', 'Ochieng Home');

INSERT INTO cell_group_members (cell_group_id, member_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 6),
(2, 4), (2, 5);

INSERT INTO attendance_sessions (id, title, type, session_date, start_time, location) VALUES
(1, 'Sunday Morning Service', 'service', DATE_SUB(CURDATE(), INTERVAL 7 DAY), '09:00:00', 'Main Sanctuary'),
(2, 'Sunday Morning Service', 'service', CURDATE(), '09:00:00', 'Main Sanctuary'),
(3, 'Faith Cell Meeting', 'cell_group', DATE_SUB(CURDATE(), INTERVAL 3 DAY), '18:30:00', 'Westlands');

INSERT INTO attendance_records (session_id, member_id, status) VALUES
(1, 1, 'present'), (1, 2, 'present'), (1, 4, 'present'), (1, 6, 'absent'),
(2, 1, 'present'), (2, 2, 'present'), (2, 6, 'present'),
(3, 1, 'present'), (3, 2, 'present'), (3, 6, 'present');

INSERT INTO contributions (member_id, household_id, fund_id, amount, payment_method, transaction_ref, contribution_date) VALUES
(1, 1, 1, 15000.00, 'mpesa', 'QHK7X2ABCD', DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
(1, 1, 2, 2000.00, 'mpesa', 'QHK7X2EFGH', DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
(1, 1, 1, 15000.00, 'mpesa', 'QHK8Y3IJKL', DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
(4, 2, 1, 20000.00, 'mpesa', 'QHK8Y3MNOP', DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
(4, 2, 3, 5000.00, 'mpesa', 'QHK9Z4QRST', DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(6, 3, 1, 12000.00, 'cash', 'CASH-001', DATE_SUB(CURDATE(), INTERVAL 14 DAY)),
(6, 3, 2, 1500.00, 'mpesa', 'QHK9Z4UVWX', DATE_SUB(CURDATE(), INTERVAL 1 DAY));

INSERT INTO pledge_campaigns (id, title, description, target_amount, start_date, end_date, fund_id, is_active) VALUES
(1, 'New Worship Instruments', 'Fundraising for new keyboard, drums, and sound equipment', 500000.00, DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 5, 1),
(2, 'Church Van', 'Purchase a van for outreach and member transport', 1500000.00, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 3, 1);

INSERT INTO pledges (campaign_id, member_id, pledged_amount, amount_paid, pledge_date) VALUES
(1, 1, 50000.00, 15000.00, DATE_SUB(CURDATE(), INTERVAL 45 DAY)),
(1, 4, 75000.00, 5000.00, DATE_SUB(CURDATE(), INTERVAL 40 DAY)),
(1, 6, 30000.00, 12000.00, DATE_SUB(CURDATE(), INTERVAL 35 DAY)),
(2, 1, 200000.00, 0.00, DATE_SUB(CURDATE(), INTERVAL 20 DAY)),
(2, 4, 150000.00, 5000.00, DATE_SUB(CURDATE(), INTERVAL 15 DAY));

INSERT INTO onboarding_qr_codes (token, label, is_active) VALUES
('church-onboard-2026', 'Main Entrance QR', 1);

INSERT INTO finance_collection_methods (id, slug, label, hint, is_system, sort_order) VALUES
(1, 'paybill', 'M-Pesa Paybill', 'Paybill 176287', 1, 10),
(2, 'cheque', 'Cheque', 'Bank cheque payments', 1, 20),
(3, 'cash', 'Cash', 'Cash & envelopes', 1, 30);

INSERT INTO finance_expense_departments (id, slug, label, expense_group, code_prefix, sort_order, is_system) VALUES
(1, 'administration', 'Administration', 'admin_expenses', '001/2', 10, 1),
(2, 'k_kids', 'K.Kids', 'ministry_departments', '001/1', 10, 1),
(3, 'worship_services', 'Worship & Services', 'ministry_departments', '001/3', 20, 1),
(4, 'discipleship_programmes', 'Discipleship Programes', 'ministry_departments', '001/4', 30, 1),
(5, 'production_sound_lighting', 'Production, Sound & Lighting', 'ministry_departments', '001/5', 40, 1),
(6, 'wages', 'Wages', 'ministry_departments', '001/6', 50, 1),
(7, 'training_development', 'Training & Development', 'ministry_departments', '001/7', 60, 1),
(8, 'pastoral_allowances_salaries', 'Pastoral Allowances/ Salaries', 'ministry_departments', '001/8', 70, 1),
(9, 'pastoral_care', 'Pastoral Care', 'ministry_departments', '001/9', 80, 1),
(10, 'missions_outreach', 'Missions & Outreach', 'ministry_departments', '001/10', 90, 1),
(11, 'gpm_remittances', 'GPM Remittances', 'ministry_departments', '001/11', 100, 1),
(12, 'honorarium_gifts', 'Honorarium & Gifts', 'ministry_departments', '001/12', 110, 1),
(13, 'benevolent', 'Benovelent', 'ministry_departments', '001/13', 120, 1),
(14, 'finance_costs', 'Finance Costs', 'finance_costs', '001/14', 130, 1);

INSERT INTO finance_expense_categories (id, department_id, slug, label, account_code, sort_order, is_system) VALUES
(1, 1, 'rent', 'Rent', '001/2/001', 10, 1),
(2, 1, 'water', 'Water', '001/2/002', 20, 1),
(3, 1, 'electricity', 'Electricity', '001/2/003', 30, 1),
(4, 1, 'transport', 'Transport', '001/2/004', 40, 1),
(5, 1, 'insurance', 'Insurance', '001/2/005', 50, 1),
(6, 1, 'security', 'Security', '001/2/006', 60, 1),
(7, 1, 'communication_telephone', 'Communication/Telephone', '001/2/007', 70, 1),
(8, 1, 'stationery', 'Stationery', '001/2/008', 80, 1),
(9, 1, 'refreshments', 'Refreshments', '001/2/009', 90, 1),
(10, 1, 'health_safety_fumigation_f_extinguishers', 'Health & Safety (Fumigation/F.Extinguishers)', '001/2/010', 100, 1),
(11, 1, 'hospitality', 'Hospitality', '001/2/011', 110, 1),
(12, 1, 'detergents_toiletries', 'Detergents & Toiletries', '001/2/012', 120, 1),
(13, 1, 'consultancy_fee', 'Consultancy Fee', '001/2/013', 130, 1),
(14, 1, 'repair_maintenance', 'Repair & Maintenance', '001/2/014', 140, 1),
(15, 1, 'licences', 'Licences', '001/2/015', 150, 1),
(16, 1, 'audit_fees', 'Audit Fees', '001/2/016', 160, 1),
(17, 2, 'k_kids', 'K.Kids', '001/1/001', 10, 1),
(18, 3, 'worship_services', 'Worship & Services', '001/3/001', 10, 1),
(19, 4, 'discipleship_programes', 'Discipleship Programes', '001/4/001', 10, 1),
(20, 5, 'production_sound_lighting', 'Production, Sound & Lighting', '001/5/001', 10, 1),
(21, 6, 'wages', 'Wages', '001/6/001', 10, 1),
(22, 7, 'training_development', 'Training & Development', '001/7/001', 10, 1),
(23, 8, 'pastoral_allowances_salaries', 'Pastoral Allowances/ Salaries', '001/8/001', 10, 1),
(24, 9, 'pastoral_care', 'Pastoral Care', '001/9/001', 10, 1),
(25, 10, 'missions_outreach', 'Missions & Outreach', '001/10/001', 10, 1),
(26, 11, 'gpm_remittances', 'GPM Remittances', '001/11/001', 10, 1),
(27, 12, 'honorarium_gifts', 'Honorarium & Gifts', '001/12/001', 10, 1),
(28, 13, 'benovelent', 'Benovelent', '001/13/001', 10, 1),
(29, 3, 'keyboardist', 'Keyboardist', '001/3/002', 20, 1),
(30, 3, 'drummer', 'Drummer', '001/3/003', 30, 1),
(31, 3, 'bassist', 'Bassist', '001/3/004', 40, 1),
(32, 2, 'kids_teacher', 'Kids Teacher', '001/1/002', 20, 1),
(33, 6, 'caretaker', 'Caretaker', '001/6/002', 20, 1),
(34, 1, 'kplc_tokens', 'KPLC Tokens', '001/2/017', 170, 1),
(36, 14, 'bank_charges', 'Bank charges', '001/14/001', 10, 1),
(37, 14, 'mpesa_charges', 'Mpesa charges', '001/14/002', 20, 1);

INSERT INTO finance_weekly_categories (id, slug, label, hint, department_id, expense_category_id, is_system, sort_order) VALUES
(1, 'keyboardist', 'Keyboardist', 'Sunday allowance', 3, 29, 1, 10),
(2, 'drummer', 'Drummer', 'Sunday allowance', 3, 30, 1, 20),
(3, 'bassist', 'Bassist', 'Sunday allowance', 3, 31, 1, 30),
(4, 'kids_teacher', 'Kids Teacher', 'Sunday allowance', 2, 32, 1, 40),
(5, 'caretaker', 'Caretaker', 'Sunday allowance', 6, 33, 1, 50),
(6, 'honorarium_gifts', 'Honorarium & Gifts', '', 12, 27, 1, 60),
(7, 'kplc_tokens', 'KPLC Tokens', 'Weekly usage', 1, 34, 1, 70);

INSERT INTO settings (setting_key, setting_value) VALUES
('finance_schema_version', '3'),
('finance_budget_schema_version', '1'),
('church_name', 'Kingdomcity church Nanyuki'),
('church_address', 'Nanyuki,Kenya'),
('church_phone', ''),
('church_logo_url', ''),
('church_logo_path', 'uploads/branding/logo.png');
