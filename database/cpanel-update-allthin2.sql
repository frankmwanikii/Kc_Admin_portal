-- =============================================================================
-- Kingdomcity Portal — SAFE UPDATE for existing cPanel DB (allthin2_Admin)
-- =============================================================================
--
-- Import this into your EXISTING database in phpMyAdmin (do NOT drop the DB).
--
-- What this does:
--   1. Upgrades schema to match current code (finance schema v3)
--   2. Adds missing tables/columns (collection methods, staff fields, etc.)
--   3. Seeds / refreshes catalog + demo reference data from your live dump
--   4. NEVER deletes members, households, contributions, or login users
--
-- Demo seed members are inserted with INSERT IGNORE only (ids 1–6), sorted
-- alphabetically by last_name, first_name. Existing uploaded members stay as-is.
--
-- After import:
--   1. Upload the latest code
--   2. Set APP_INSTALLED=true in .env
--   3. Log in: admin@kingdomcitychurchnanyuki.org / password123
--      (change password after first login)
--
-- Safe to re-run.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1) Schema upgrades required by current code
-- -----------------------------------------------------------------------------

-- Collection methods catalog (Sunday collections "Add category")
CREATE TABLE IF NOT EXISTS finance_collection_methods (
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

-- Allow custom payment method slugs (was enum paybill/cheque/cash)
ALTER TABLE finance_weekly_collections
    MODIFY payment_method VARCHAR(50) NOT NULL;

ALTER TABLE finance_collections
    MODIFY payment_method VARCHAR(50) NOT NULL;

-- Arrears → expense category link
SET @kc_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'finance_expense_arrears'
      AND COLUMN_NAME = 'category_id'
);
SET @kc_sql := IF(
    @kc_col = 0,
    'ALTER TABLE finance_expense_arrears ADD COLUMN category_id INT UNSIGNED NULL AFTER expense_item, ADD KEY idx_category_id (category_id)',
    'SELECT 1'
);
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

-- Weekly categories → department / expense category links
SET @kc_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'finance_weekly_categories'
      AND COLUMN_NAME = 'department_id'
);
SET @kc_sql := IF(
    @kc_col = 0,
    'ALTER TABLE finance_weekly_categories ADD COLUMN department_id INT UNSIGNED NULL AFTER hint, ADD KEY idx_weekly_department_id (department_id)',
    'SELECT 1'
);
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

SET @kc_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'finance_weekly_categories'
      AND COLUMN_NAME = 'expense_category_id'
);
SET @kc_sql := IF(
    @kc_col = 0,
    'ALTER TABLE finance_weekly_categories ADD COLUMN expense_category_id INT UNSIGNED NULL AFTER department_id, ADD KEY idx_weekly_expense_category_id (expense_category_id)',
    'SELECT 1'
);
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

-- Expense department grouping
SET @kc_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'finance_expense_departments'
      AND COLUMN_NAME = 'expense_group'
);
SET @kc_sql := IF(
    @kc_col = 0,
    'ALTER TABLE finance_expense_departments ADD COLUMN expense_group VARCHAR(32) NOT NULL DEFAULT ''ministry_departments'' AFTER label, ADD KEY idx_expense_group (expense_group)',
    'SELECT 1'
);
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

-- Core finance / ops tables (no-op if already present)
CREATE TABLE IF NOT EXISTS finance_expense_arrears (
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

CREATE TABLE IF NOT EXISTS finance_weekly_expenses (
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

CREATE TABLE IF NOT EXISTS finance_weekly_categories (
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
    UNIQUE KEY uk_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_collections (
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

CREATE TABLE IF NOT EXISTS finance_weekly_collections (
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

CREATE TABLE IF NOT EXISTS finance_sunday_sessions (
    week_date DATE NOT NULL COMMENT 'Sunday service date',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (week_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_budget_lines (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    budget_year SMALLINT UNSIGNED NOT NULL,
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

CREATE TABLE IF NOT EXISTS finance_budget_monthly (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    budget_line_id INT UNSIGNED NOT NULL,
    budget_month CHAR(7) NOT NULL COMMENT 'YYYY-MM',
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uk_line_month (budget_line_id, budget_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_expense_departments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(64) NOT NULL,
    label VARCHAR(160) NOT NULL,
    expense_group VARCHAR(32) NOT NULL DEFAULT 'ministry_departments',
    code_prefix VARCHAR(32) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    is_system TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_slug (slug),
    UNIQUE KEY uk_code_prefix (code_prefix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_expense_categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    department_id INT UNSIGNED NOT NULL,
    slug VARCHAR(64) NOT NULL,
    label VARCHAR(160) NOT NULL,
    account_code VARCHAR(32) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    is_system TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_slug (slug),
    UNIQUE KEY uk_account_code (account_code),
    KEY idx_department (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_items (
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

CREATE TABLE IF NOT EXISTS staff_members (
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

CREATE TABLE IF NOT EXISTS staff_member_images (
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

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User profile columns (admin profile page)
SET @kc_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'username'
);
SET @kc_sql := IF(@kc_col = 0, 'ALTER TABLE users ADD COLUMN username VARCHAR(80) NULL AFTER member_id', 'SELECT 1');
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

SET @kc_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'phone'
);
SET @kc_sql := IF(@kc_col = 0, 'ALTER TABLE users ADD COLUMN phone VARCHAR(40) NULL AFTER email', 'SELECT 1');
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

SET @kc_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'display_name'
);
SET @kc_sql := IF(@kc_col = 0, 'ALTER TABLE users ADD COLUMN display_name VARCHAR(150) NULL AFTER phone', 'SELECT 1');
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

SET @kc_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'avatar_path'
);
SET @kc_sql := IF(@kc_col = 0, 'ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) NULL AFTER display_name', 'SELECT 1');
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

-- Staff extended columns (add only if missing)
SET @kc_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_members' AND COLUMN_NAME = 'bio');
SET @kc_sql := IF(@kc_col = 0, 'ALTER TABLE staff_members ADD COLUMN bio TEXT NULL AFTER notes', 'SELECT 1');
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

SET @kc_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_members' AND COLUMN_NAME = 'photo_path');
SET @kc_sql := IF(@kc_col = 0, 'ALTER TABLE staff_members ADD COLUMN photo_path VARCHAR(255) NULL', 'SELECT 1');
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

SET @kc_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_members' AND COLUMN_NAME = 'staff_code');
SET @kc_sql := IF(@kc_col = 0, 'ALTER TABLE staff_members ADD COLUMN staff_code VARCHAR(80) NULL AFTER bio', 'SELECT 1');
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

SET @kc_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_members' AND COLUMN_NAME = 'campus');
SET @kc_sql := IF(@kc_col = 0, 'ALTER TABLE staff_members ADD COLUMN campus VARCHAR(50) NULL', 'SELECT 1');
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

SET @kc_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_members' AND COLUMN_NAME = 'employment_type');
SET @kc_sql := IF(@kc_col = 0, 'ALTER TABLE staff_members ADD COLUMN employment_type VARCHAR(40) NULL DEFAULT ''full_time''', 'SELECT 1');
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

SET @kc_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_members' AND COLUMN_NAME = 'hire_date');
SET @kc_sql := IF(@kc_col = 0, 'ALTER TABLE staff_members ADD COLUMN hire_date DATE NULL', 'SELECT 1');
PREPARE kc_stmt FROM @kc_sql; EXECUTE kc_stmt; DEALLOCATE PREPARE kc_stmt;

-- -----------------------------------------------------------------------------
-- 2) Seed / sync reference data from allthin2_Admin dump (INSERT IGNORE)
--    Does not overwrite rows that already exist.
-- -----------------------------------------------------------------------------

-- Collection methods (required for Sunday collections UI)
INSERT IGNORE INTO finance_collection_methods (id, slug, label, hint, is_system, sort_order) VALUES
(1, 'paybill', 'M-Pesa Paybill', 'Paybill 176287', 1, 10),
(2, 'cheque', 'Cheque', 'Bank cheque payments', 1, 20),
(3, 'cash', 'Cash', 'Cash & envelopes', 1, 30);

-- Expense departments (from live DB + finance_costs for schema v3)
INSERT IGNORE INTO finance_expense_departments (id, slug, label, expense_group, code_prefix, sort_order, is_system) VALUES
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

UPDATE finance_expense_departments SET expense_group = 'admin_expenses' WHERE slug = 'administration';
UPDATE finance_expense_departments SET expense_group = 'finance_costs', code_prefix = '001/14', sort_order = 130 WHERE slug = 'finance_costs';

-- Expense categories (from live dump + finance costs)
INSERT IGNORE INTO finance_expense_categories (id, department_id, slug, label, account_code, sort_order, is_system) VALUES
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
(35, 1, 'billboards', 'Billboards', '001/2/018', 180, 0),
(36, 14, 'bank_charges', 'Bank charges', '001/14/001', 10, 1),
(37, 14, 'mpesa_charges', 'Mpesa charges', '001/14/002', 20, 1);

-- Weekly expense categories (Sunday spending lines)
INSERT IGNORE INTO finance_weekly_categories (id, slug, label, hint, department_id, expense_category_id, is_system, sort_order) VALUES
(1, 'keyboardist', 'Keyboardist', 'Sunday allowance', 3, 29, 1, 10),
(2, 'drummer', 'Drummer', 'Sunday allowance', 3, 30, 1, 20),
(3, 'bassist', 'Bassist', 'Sunday allowance', 3, 31, 1, 30),
(4, 'kids_teacher', 'Kids Teacher', 'Sunday allowance', 2, 32, 1, 40),
(5, 'caretaker', 'Caretaker', 'Sunday allowance', 6, 33, 1, 50),
(6, 'honorarium_gifts', 'Honorarium & Gifts', '', 12, 27, 1, 60),
(7, 'kplc_tokens', 'KPLC Tokens', 'Weekly usage', 1, 34, 1, 70);

UPDATE finance_weekly_categories SET department_id = 3, expense_category_id = 29 WHERE slug = 'keyboardist';
UPDATE finance_weekly_categories SET department_id = 3, expense_category_id = 30 WHERE slug = 'drummer';
UPDATE finance_weekly_categories SET department_id = 3, expense_category_id = 31 WHERE slug = 'bassist';
UPDATE finance_weekly_categories SET department_id = 2, expense_category_id = 32 WHERE slug = 'kids_teacher';
UPDATE finance_weekly_categories SET department_id = 6, expense_category_id = 33 WHERE slug = 'caretaker';
UPDATE finance_weekly_categories SET department_id = 12, expense_category_id = 27 WHERE slug = 'honorarium_gifts';
UPDATE finance_weekly_categories SET department_id = 1, expense_category_id = 34 WHERE slug = 'kplc_tokens';

-- Funds
INSERT IGNORE INTO funds (id, name, code, description, is_active) VALUES
(1, 'Tithe', 'TITHE', 'Regular tithe contributions', 1),
(2, 'General Offering', 'OFFERING', 'Sunday and special offerings', 1),
(3, 'Building Fund', 'BUILDING', 'Church building project', 1),
(4, 'Missions', 'MISSIONS', 'Missions and outreach', 1),
(5, 'Youth Ministry', 'YOUTH', 'Youth programs and activities', 1);

-- Church settings (from live dump) — only insert missing keys
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('finance_schema_version', '3'),
('finance_budget_schema_version', '1'),
('church_name', 'Kingdomcity church Nanyuki'),
('church_address', 'Nanyuki,Kenya'),
('church_phone', ''),
('church_logo_url', ''),
('church_logo_path', 'uploads/branding/logo.png');

UPDATE settings SET setting_value = '3' WHERE setting_key = 'finance_schema_version';
UPDATE settings SET setting_value = '1' WHERE setting_key = 'finance_budget_schema_version';

-- Budget line templates (FY 2026) — from live dump
INSERT IGNORE INTO finance_budget_lines (id, budget_year, line_type, section, label, account_code, sort_order) VALUES
(2, 2026, 'income', 'Incomes', 'Tithe', '001/1/010', 20),
(3, 2026, 'income', 'Incomes', 'Donation/Grants-Cash', '001/1/015', 30),
(4, 2026, 'income', 'Incomes', 'Donation/Grants-Kind', '001/1/020', 40),
(5, 2026, 'income', 'Incomes', 'Soko Sales', '001/1/025', 50),
(6, 2026, 'income', 'Incomes', 'Gain on Disposals', '001/1/030', 60),
(7, 2026, 'income', 'Incomes', 'Other Incomes', '001/1/035', 70),
(8, 2026, 'expense', 'Administration', 'Rent', '001/2/001', 80),
(9, 2026, 'expense', 'Administration', 'Water', '001/2/002', 90),
(10, 2026, 'expense', 'Administration', 'Electricity', '001/2/003', 100),
(11, 2026, 'expense', 'Administration', 'Transport', '001/2/004', 110),
(12, 2026, 'expense', 'Administration', 'Insurance', '001/2/005', 120),
(13, 2026, 'expense', 'Administration', 'Security', '001/2/006', 130),
(14, 2026, 'expense', 'Administration', 'Communication/Telephone', '001/2/007', 140),
(15, 2026, 'expense', 'Administration', 'Stationery', '001/2/008', 150),
(16, 2026, 'expense', 'Administration', 'Refreshments', '001/2/009', 160),
(17, 2026, 'expense', 'Administration', 'Health & Safety(Fumigation/F.Extiguishers)', '001/2/010', 170),
(18, 2026, 'expense', 'Administration', 'Hospitality', '001/2/011', 180),
(19, 2026, 'expense', 'Administration', 'Detergents & Toiletries', '001/2/012', 190),
(20, 2026, 'expense', 'Administration', 'Consultancy Fee', '001/2/013', 200),
(21, 2026, 'expense', 'Administration', 'Repair & Maintenance', '001/2/014', 210),
(22, 2026, 'expense', 'Administration', 'Licences', '001/2/015', 220),
(23, 2026, 'expense', 'Administration', 'Audit Fees', '001/2/016', 230),
(24, 2026, 'expense', 'Ministry & Departments', 'K.Kids', '001/3/001', 240),
(25, 2026, 'expense', 'Ministry & Departments', 'Worship & Services', '001/3/002', 250),
(26, 2026, 'expense', 'Ministry & Departments', 'Discipleship Programes', '001/3/003', 260),
(27, 2026, 'expense', 'Ministry & Departments', 'Production, Sound & Lighting', '001/3/004', 270),
(28, 2026, 'expense', 'Ministry & Departments', 'Wages', '001/3/005', 280),
(29, 2026, 'expense', 'Ministry & Departments', 'Training & Development', '001/3/006', 290),
(30, 2026, 'expense', 'Ministry & Departments', 'Pastoral Allowances/ Salaries', '001/3/007', 300),
(31, 2026, 'expense', 'Ministry & Departments', 'Pastoral Care', '001/3/008', 310),
(32, 2026, 'expense', 'Ministry & Departments', 'Missions & Outreach', '001/3/009', 320),
(33, 2026, 'expense', 'Ministry & Departments', 'GPM Remittances', '001/3/010', 330),
(34, 2026, 'expense', 'Ministry & Departments', 'Honorarium & Gifts', '001/3/011', 340),
(35, 2026, 'expense', 'Ministry & Departments', 'Benovelent', '001/3/012', 350),
(36, 2026, 'expense', 'Finance Costs', 'Bank Charges', '001/4/001', 360),
(37, 2026, 'expense', 'Finance Costs', 'Mpesa Charges', '001/4/002', 370);

-- Sample April 2026 monthly budget amounts (from live dump)
INSERT IGNORE INTO finance_budget_monthly (id, budget_line_id, budget_month, amount) VALUES
(1, 8, '2026-04', 55000.00),
(2, 9, '2026-04', 2000.00),
(3, 10, '2026-04', 2000.00),
(4, 14, '2026-04', 2000.00),
(5, 15, '2026-04', 2000.00),
(6, 17, '2026-04', 1000.00),
(7, 19, '2026-04', 2000.00),
(8, 21, '2026-04', 1000.00),
(9, 24, '2026-04', 10000.00),
(10, 25, '2026-04', 20000.00),
(11, 28, '2026-04', 16800.00),
(12, 34, '2026-04', 20000.00),
(13, 36, '2026-04', 1500.00),
(14, 37, '2026-04', 500.00);

-- Sample weekly collections (from live dump) — keeps existing rows
INSERT IGNORE INTO finance_weekly_collections (id, week_date, payment_method, amount, notes) VALUES
(4, '2026-03-01', 'paybill', 40.00, NULL),
(5, '2026-03-08', 'paybill', 50.00, NULL),
(6, '2026-03-15', 'paybill', 60.00, NULL),
(7, '2026-03-22', 'paybill', 70.00, NULL);

-- Onboarding QR
INSERT IGNORE INTO onboarding_qr_codes (token, label, is_active) VALUES
('church-onboard-2026', 'Main Entrance QR', 1);

-- -----------------------------------------------------------------------------
-- 3) Demo households + members (KEEP EXISTING)
--    INSERT IGNORE only — never deletes or overwrites uploaded members.
--    Ordered alphabetically by last_name, first_name for easy reference.
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO households (id, name, address, city, phone) VALUES
(1, 'Kamau Family', '45 Oak Street', 'Nairobi', '+254712345678'),
(2, 'Ochieng Family', '12 River Road', 'Nairobi', '+254723456789'),
(3, 'Wanjiku Family', '8 Hill View', 'Nairobi', '+254734567890');

-- Sorted A→Z by last_name, first_name (ids match live dump / FKs)
INSERT IGNORE INTO members (
    id, household_id, first_name, last_name, email, phone, gender, date_of_birth,
    is_head_of_household, membership_status, joined_date, onboarding_completed
) VALUES
(3, 1, 'David', 'Kamau', 'david.kamau@email.com', NULL, 'male', '2010-11-05', 0, 'active', '2020-01-12', 0),
(2, 1, 'Grace', 'Kamau', 'grace.kamau@email.com', '+254712345679', 'female', '1988-07-22', 0, 'active', '2020-01-12', 1),
(1, 1, 'James', 'Kamau', 'james.kamau@email.com', '+254712345678', 'male', '1985-03-15', 1, 'active', '2020-01-12', 1),
(5, 2, 'Mary', 'Ochieng', 'mary.ochieng@email.com', '+254723456790', 'female', '1982-12-14', 0, 'active', '2019-06-20', 1),
(4, 2, 'Peter', 'Ochieng', 'peter.ochieng@email.com', '+254723456789', 'male', '1978-09-30', 1, 'active', '2019-06-20', 1),
(6, 3, 'Faith', 'Wanjiku', 'faith.wanjiku@email.com', '+254734567890', 'female', '1990-05-08', 1, 'active', '2021-03-01', 1);

UPDATE households SET head_member_id = 1 WHERE id = 1 AND (head_member_id IS NULL OR head_member_id = 0);
UPDATE households SET head_member_id = 4 WHERE id = 2 AND (head_member_id IS NULL OR head_member_id = 0);
UPDATE households SET head_member_id = 6 WHERE id = 3 AND (head_member_id IS NULL OR head_member_id = 0);

-- Login accounts (password for all: password123) — does not wipe extra users
INSERT IGNORE INTO users (id, member_id, username, email, password, role, email_verified_at) VALUES
(1, NULL, 'Admin', 'admin@kingdomcitychurchnanyuki.org', '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'admin', NOW()),
(2, 1, 'james.kamau', 'james.kamau@email.com', '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'member', NOW()),
(3, 4, 'peter.ochieng', 'peter.ochieng@email.com', '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'member', NOW()),
(4, 6, 'faith.wanjiku', 'faith.wanjiku@email.com', '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'member', NOW());

UPDATE users
SET username = COALESCE(NULLIF(username, ''), 'Admin'),
    email = 'admin@kingdomcitychurchnanyuki.org'
WHERE role = 'admin'
LIMIT 1;

-- Ministries + cell groups (demo reference)
INSERT IGNORE INTO ministries (id, name, description, leader_id, meeting_day, is_active) VALUES
(1, 'Praise & Worship', 'Music ministry and worship team', 6, 'Thursday', 1),
(2, 'Ushers', 'Ushering and guest services', 1, 'Saturday', 1),
(3, 'Youth Ministry', 'Teens and young adults', 4, 'Friday', 1),
(4, 'Sunday School', 'Children ministry', 2, 'Sunday', 1);

INSERT IGNORE INTO ministry_members (id, ministry_id, member_id, role) VALUES
(1, 1, 6, 'leader'),
(2, 1, 2, 'member'),
(3, 2, 1, 'leader'),
(4, 2, 4, 'member'),
(5, 3, 4, 'leader'),
(6, 3, 3, 'member'),
(7, 4, 2, 'leader'),
(8, 4, 5, 'member');

INSERT IGNORE INTO cell_groups (id, name, leader_id, meeting_day, meeting_time, location, is_active) VALUES
(1, 'Faith Cell - Westlands', 1, 'Wednesday', '18:30:00', 'Kamau Residence', 1),
(2, 'Hope Cell - Karen', 4, 'Tuesday', '19:00:00', 'Ochieng Home', 1);

INSERT IGNORE INTO cell_group_members (id, cell_group_id, member_id) VALUES
(1, 1, 1), (2, 1, 2), (3, 1, 3), (4, 1, 6),
(5, 2, 4), (6, 2, 5);

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- Done. Existing uploaded members were not deleted.
-- List members sorted for verification:
--   SELECT id, last_name, first_name, email, membership_status
--   FROM members ORDER BY last_name ASC, first_name ASC;
-- =============================================================================
