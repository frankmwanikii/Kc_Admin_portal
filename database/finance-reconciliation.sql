-- Finance reconciliation — expense arrears & weekly Sunday budget
CREATE TABLE IF NOT EXISTS finance_expense_arrears (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    expense_item VARCHAR(255) NOT NULL,
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
    KEY idx_budget_year (budget_year)
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

CREATE TABLE IF NOT EXISTS finance_weekly_categories (
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

CREATE TABLE IF NOT EXISTS finance_collections (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    collection_date DATE NOT NULL,
    payment_method ENUM('paybill', 'cheque', 'cash') NOT NULL,
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
    payment_method ENUM('paybill', 'cheque', 'cash') NOT NULL,
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

CREATE TABLE IF NOT EXISTS staff_members (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    role_title VARCHAR(150) NULL,
    department VARCHAR(150) NULL,
    phone VARCHAR(64) NULL,
    email VARCHAR(255) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_staff_status (status),
    KEY idx_staff_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
