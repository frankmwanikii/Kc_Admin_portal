-- Seed data for Grace Church MIS (MySQL)

INSERT IGNORE INTO funds (id, name, code, description) VALUES
(1, 'Tithe', 'TITHE', 'Regular tithe contributions'),
(2, 'General Offering', 'OFFERING', 'Sunday and special offerings'),
(3, 'Building Fund', 'BUILDING', 'Church building project'),
(4, 'Missions', 'MISSIONS', 'Missions and outreach'),
(5, 'Youth Ministry', 'YOUTH', 'Youth programs and activities');

INSERT IGNORE INTO households (id, name, address, city, phone) VALUES
(1, 'Kamau Family', '45 Oak Street', 'Nairobi', '+254712345678'),
(2, 'Ochieng Family', '12 River Road', 'Nairobi', '+254723456789'),
(3, 'Wanjiku Family', '8 Hill View', 'Nairobi', '+254734567890');

INSERT IGNORE INTO members (id, household_id, first_name, last_name, email, phone, gender, date_of_birth, is_head_of_household, membership_status, joined_date, onboarding_completed) VALUES
(1, 1, 'James', 'Kamau', 'james.kamau@email.com', '+254712345678', 'male', '1985-03-15', 1, 'active', '2020-01-12', 1),
(2, 1, 'Grace', 'Kamau', 'grace.kamau@email.com', '+254712345679', 'female', '1988-07-22', 0, 'active', '2020-01-12', 1),
(3, 1, 'David', 'Kamau', 'david.kamau@email.com', NULL, 'male', '2010-11-05', 0, 'active', '2020-01-12', 0),
(4, 2, 'Peter', 'Ochieng', 'peter.ochieng@email.com', '+254723456789', 'male', '1978-09-30', 1, 'active', '2019-06-20', 1),
(5, 2, 'Mary', 'Ochieng', 'mary.ochieng@email.com', '+254723456790', 'female', '1982-12-14', 0, 'active', '2019-06-20', 1),
(6, 3, 'Faith', 'Wanjiku', 'faith.wanjiku@email.com', '+254734567890', 'female', '1990-05-08', 1, 'active', '2021-03-01', 1);

UPDATE households SET head_member_id = 1 WHERE id = 1;
UPDATE households SET head_member_id = 4 WHERE id = 2;
UPDATE households SET head_member_id = 6 WHERE id = 3;

INSERT IGNORE INTO users (id, member_id, username, email, password, role, email_verified_at) VALUES
(1, NULL, 'Admin', 'admin@church.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW()),
(2, 1, 'james.kamau', 'james.kamau@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', NOW()),
(3, 4, 'peter.ochieng', 'peter.ochieng@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', NOW()),
(4, 6, 'faith.wanjiku', 'faith.wanjiku@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', NOW());

INSERT IGNORE INTO ministries (id, name, description, leader_id, meeting_day) VALUES
(1, 'Praise & Worship', 'Music ministry and worship team', 6, 'Thursday'),
(2, 'Ushers', 'Ushering and guest services', 1, 'Saturday'),
(3, 'Youth Ministry', 'Teens and young adults', 4, 'Friday'),
(4, 'Sunday School', 'Children ministry', 2, 'Sunday');

INSERT IGNORE INTO ministry_members (ministry_id, member_id, role) VALUES
(1, 6, 'leader'), (1, 2, 'member'),
(2, 1, 'leader'), (2, 4, 'member'),
(3, 4, 'leader'), (3, 3, 'member'),
(4, 2, 'leader'), (4, 5, 'member');

INSERT IGNORE INTO cell_groups (id, name, leader_id, meeting_day, meeting_time, location) VALUES
(1, 'Faith Cell - Westlands', 1, 'Wednesday', '18:30:00', 'Kamau Residence'),
(2, 'Hope Cell - Karen', 4, 'Tuesday', '19:00:00', 'Ochieng Home');

INSERT IGNORE INTO cell_group_members (cell_group_id, member_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 6),
(2, 4), (2, 5);

INSERT IGNORE INTO attendance_sessions (id, title, type, session_date, start_time, location) VALUES
(1, 'Sunday Morning Service', 'service', DATE_SUB(CURDATE(), INTERVAL 7 DAY), '09:00:00', 'Main Sanctuary'),
(2, 'Sunday Morning Service', 'service', CURDATE(), '09:00:00', 'Main Sanctuary'),
(3, 'Faith Cell Meeting', 'cell_group', DATE_SUB(CURDATE(), INTERVAL 3 DAY), '18:30:00', 'Westlands');

INSERT IGNORE INTO attendance_records (session_id, member_id, status) VALUES
(1, 1, 'present'), (1, 2, 'present'), (1, 4, 'present'), (1, 6, 'absent'),
(2, 1, 'present'), (2, 2, 'present'), (2, 6, 'present'),
(3, 1, 'present'), (3, 2, 'present'), (3, 6, 'present');

INSERT IGNORE INTO contributions (member_id, household_id, fund_id, amount, payment_method, transaction_ref, contribution_date) VALUES
(1, 1, 1, 15000.00, 'mpesa', 'QHK7X2ABCD', DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
(1, 1, 2, 2000.00, 'mpesa', 'QHK7X2EFGH', DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
(1, 1, 1, 15000.00, 'mpesa', 'QHK8Y3IJKL', DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
(4, 2, 1, 20000.00, 'mpesa', 'QHK8Y3MNOP', DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
(4, 2, 3, 5000.00, 'mpesa', 'QHK9Z4QRST', DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(6, 3, 1, 12000.00, 'cash', 'CASH-001', DATE_SUB(CURDATE(), INTERVAL 14 DAY)),
(6, 3, 2, 1500.00, 'mpesa', 'QHK9Z4UVWX', DATE_SUB(CURDATE(), INTERVAL 1 DAY));

INSERT IGNORE INTO pledge_campaigns (id, title, description, target_amount, start_date, end_date, fund_id, is_active) VALUES
(1, 'New Worship Instruments', 'Fundraising for new keyboard, drums, and sound equipment', 500000.00, DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 5, 1),
(2, 'Church Van', 'Purchase a van for outreach and member transport', 1500000.00, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 3, 1);

INSERT IGNORE INTO pledges (campaign_id, member_id, pledged_amount, amount_paid, pledge_date) VALUES
(1, 1, 50000.00, 15000.00, DATE_SUB(CURDATE(), INTERVAL 45 DAY)),
(1, 4, 75000.00, 5000.00, DATE_SUB(CURDATE(), INTERVAL 40 DAY)),
(1, 6, 30000.00, 12000.00, DATE_SUB(CURDATE(), INTERVAL 35 DAY)),
(2, 1, 200000.00, 0.00, DATE_SUB(CURDATE(), INTERVAL 20 DAY)),
(2, 4, 150000.00, 5000.00, DATE_SUB(CURDATE(), INTERVAL 15 DAY));

INSERT IGNORE INTO onboarding_qr_codes (token, label, is_active) VALUES
('church-onboard-2026', 'Main Entrance QR', 1);
