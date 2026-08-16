-- Shared with Kc_website — form submissions from Connect / Join / Contact forms
CREATE TABLE IF NOT EXISTS form_submissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    form_type VARCHAR(50) NOT NULL,
    campus_id VARCHAR(32) NOT NULL DEFAULT 'nanyuki',
    submitter_name VARCHAR(255) NULL,
    submitter_email VARCHAR(255) NULL,
    submitter_phone VARCHAR(64) NULL,
    payload LONGTEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(512) NULL,
    email_sent TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    portal_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_form_type (form_type),
    KEY idx_status (status),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
