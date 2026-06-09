-- Extended onboarding fields for Kingdomcity member registration

ALTER TABLE members
    ADD COLUMN IF NOT EXISTS residence VARCHAR(255) NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS county VARCHAR(100) NULL AFTER residence,
    ADD COLUMN IF NOT EXISTS spouse_name VARCHAR(150) NULL AFTER marital_status,
    ADD COLUMN IF NOT EXISTS employer VARCHAR(150) NULL AFTER occupation,
    ADD COLUMN IF NOT EXISTS emergency_contact_name VARCHAR(150) NULL AFTER employer,
    ADD COLUMN IF NOT EXISTS emergency_contact_phone VARCHAR(30) NULL AFTER emergency_contact_name,
    ADD COLUMN IF NOT EXISTS how_heard_about_us VARCHAR(100) NULL AFTER emergency_contact_phone,
    ADD COLUMN IF NOT EXISTS previous_church VARCHAR(200) NULL AFTER how_heard_about_us,
    ADD COLUMN IF NOT EXISTS baptized TINYINT(1) DEFAULT 0 AFTER previous_church,
    ADD COLUMN IF NOT EXISTS baptism_date DATE NULL AFTER baptized,
    ADD COLUMN IF NOT EXISTS ministry_interests TEXT NULL AFTER baptism_date,
    ADD COLUMN IF NOT EXISTS skills_talents TEXT NULL AFTER ministry_interests,
    ADD COLUMN IF NOT EXISTS member_notes TEXT NULL AFTER skills_talents;

CREATE TABLE IF NOT EXISTS household_children (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    age INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX IF NOT EXISTS idx_household_children_household ON household_children(household_id);
