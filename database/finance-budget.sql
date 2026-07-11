-- Annual budget lines and monthly amounts (church financial year: April–March)
CREATE TABLE IF NOT EXISTS finance_budget_lines (
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

CREATE TABLE IF NOT EXISTS finance_budget_monthly (
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
