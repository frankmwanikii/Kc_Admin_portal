-- Grace Church MIS Database Schema

CREATE TABLE IF NOT EXISTS households (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    phone VARCHAR(30),
    head_member_id INTEGER,
    anniversary_date DATE,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(30),
    gender VARCHAR(20),
    date_of_birth DATE,
    marital_status VARCHAR(30),
    occupation VARCHAR(100),
    photo_url VARCHAR(255),
    is_head_of_household BOOLEAN DEFAULT 0,
    membership_status VARCHAR(30) DEFAULT 'active',
    joined_date DATE,
    onboarding_token VARCHAR(64) UNIQUE,
    onboarding_completed BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    member_id INTEGER,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) DEFAULT 'member',
    magic_link_token VARCHAR(64),
    magic_link_expires DATETIME,
    email_verified_at DATETIME,
    last_login_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attendance_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(200) NOT NULL,
    type VARCHAR(50) NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME,
    location VARCHAR(200),
    notes TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS attendance_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id INTEGER NOT NULL,
    member_id INTEGER NOT NULL,
    status VARCHAR(20) DEFAULT 'present',
    checked_in_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    UNIQUE(session_id, member_id),
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS funds (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contributions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    member_id INTEGER NOT NULL,
    household_id INTEGER,
    fund_id INTEGER NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'cash',
    transaction_ref VARCHAR(100),
    contribution_date DATE NOT NULL,
    notes TEXT,
    sms_sent BOOLEAN DEFAULT 0,
    recorded_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (household_id) REFERENCES households(id),
    FOREIGN KEY (fund_id) REFERENCES funds(id)
);

CREATE TABLE IF NOT EXISTS pledge_campaigns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    target_amount DECIMAL(14,2),
    start_date DATE,
    end_date DATE,
    fund_id INTEGER,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fund_id) REFERENCES funds(id)
);

CREATE TABLE IF NOT EXISTS pledges (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER NOT NULL,
    member_id INTEGER NOT NULL,
    pledged_amount DECIMAL(12,2) NOT NULL,
    amount_paid DECIMAL(12,2) DEFAULT 0,
    pledge_date DATE,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES pledge_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ministries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    leader_id INTEGER,
    meeting_day VARCHAR(30),
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leader_id) REFERENCES members(id)
);

CREATE TABLE IF NOT EXISTS ministry_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ministry_id INTEGER NOT NULL,
    member_id INTEGER NOT NULL,
    role VARCHAR(50) DEFAULT 'member',
    joined_date DATE,
    UNIQUE(ministry_id, member_id),
    FOREIGN KEY (ministry_id) REFERENCES ministries(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cell_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    leader_id INTEGER,
    meeting_day VARCHAR(30),
    meeting_time TIME,
    location TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leader_id) REFERENCES members(id)
);

CREATE TABLE IF NOT EXISTS cell_group_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cell_group_id INTEGER NOT NULL,
    member_id INTEGER NOT NULL,
    UNIQUE(cell_group_id, member_id),
    FOREIGN KEY (cell_group_id) REFERENCES cell_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS communications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    channel VARCHAR(20) NOT NULL,
    audience VARCHAR(50) DEFAULT 'all',
    status VARCHAR(20) DEFAULT 'draft',
    scheduled_at DATETIME,
    sent_at DATETIME,
    sent_count INTEGER DEFAULT 0,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sms_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    member_id INTEGER,
    phone VARCHAR(30),
    message TEXT,
    type VARCHAR(50),
    status VARCHAR(20),
    provider_ref VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mobile_money_statements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    provider VARCHAR(50),
    transaction_ref VARCHAR(100) UNIQUE,
    phone VARCHAR(30),
    amount DECIMAL(12,2),
    transaction_date DATETIME,
    raw_payload TEXT,
    matched_contribution_id INTEGER,
    status VARCHAR(20) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (matched_contribution_id) REFERENCES contributions(id)
);

CREATE TABLE IF NOT EXISTS onboarding_qr_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token VARCHAR(64) NOT NULL UNIQUE,
    label VARCHAR(150),
    is_active BOOLEAN DEFAULT 1,
    scan_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_members_household ON members(household_id);
CREATE INDEX IF NOT EXISTS idx_members_email ON members(email);
CREATE INDEX IF NOT EXISTS idx_attendance_member ON attendance_records(member_id);
CREATE INDEX IF NOT EXISTS idx_contributions_member ON contributions(member_id);
CREATE INDEX IF NOT EXISTS idx_contributions_date ON contributions(contribution_date);

CREATE TABLE IF NOT EXISTS visitor_feedback (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    spouse_name VARCHAR(150),
    children_names TEXT,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(150) NOT NULL,
    review TEXT,
    how_heard_about_us VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
