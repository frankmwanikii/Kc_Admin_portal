-- Expense departments & categories for outstanding bills (arrears)
CREATE TABLE IF NOT EXISTS finance_expense_departments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(64) NOT NULL,
    label VARCHAR(160) NOT NULL,
    code_prefix VARCHAR(32) NOT NULL COMMENT 'e.g. 001/2',
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

-- Link arrears to catalog (safe if column already exists on re-run)
SET @kc_arrear_cat := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'finance_expense_arrears'
      AND COLUMN_NAME = 'category_id'
);
SET @kc_arrear_sql := IF(
    @kc_arrear_cat = 0,
    'ALTER TABLE finance_expense_arrears
        ADD COLUMN category_id INT UNSIGNED NULL AFTER expense_item,
        ADD KEY idx_category_id (category_id)',
    'SELECT 1'
);
PREPARE kc_arrear_stmt FROM @kc_arrear_sql;
EXECUTE kc_arrear_stmt;
DEALLOCATE PREPARE kc_arrear_stmt;

-- Link weekly expense categories to departments
SET @kc_weekly_dept := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'finance_weekly_categories'
      AND COLUMN_NAME = 'department_id'
);
SET @kc_weekly_sql := IF(
    @kc_weekly_dept = 0,
    'ALTER TABLE finance_weekly_categories
        ADD COLUMN department_id INT UNSIGNED NULL AFTER hint,
        ADD KEY idx_weekly_department_id (department_id)',
    'SELECT 1'
);
PREPARE kc_weekly_stmt FROM @kc_weekly_sql;
EXECUTE kc_weekly_stmt;
DEALLOCATE PREPARE kc_weekly_stmt;

-- Top-level expense grouping: Admin Expenses | Ministry & Departments
SET @kc_exp_group := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'finance_expense_departments'
      AND COLUMN_NAME = 'expense_group'
);
SET @kc_exp_group_sql := IF(
    @kc_exp_group = 0,
    'ALTER TABLE finance_expense_departments
        ADD COLUMN expense_group VARCHAR(32) NOT NULL DEFAULT \'ministry_departments\' AFTER label,
        ADD KEY idx_expense_group (expense_group)',
    'SELECT 1'
);
PREPARE kc_exp_group_stmt FROM @kc_exp_group_sql;
EXECUTE kc_exp_group_stmt;
DEALLOCATE PREPARE kc_exp_group_stmt;

-- Optional admin budget line on weekly categories
SET @kc_weekly_ecat := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'finance_weekly_categories'
      AND COLUMN_NAME = 'expense_category_id'
);
SET @kc_weekly_ecat_sql := IF(
    @kc_weekly_ecat = 0,
    'ALTER TABLE finance_weekly_categories
        ADD COLUMN expense_category_id INT UNSIGNED NULL AFTER department_id,
        ADD KEY idx_weekly_expense_category_id (expense_category_id)',
    'SELECT 1'
);
PREPARE kc_weekly_ecat_stmt FROM @kc_weekly_ecat_sql;
EXECUTE kc_weekly_ecat_stmt;
DEALLOCATE PREPARE kc_weekly_ecat_stmt;
