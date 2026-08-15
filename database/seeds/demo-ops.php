<?php

declare(strict_types=1);

/**
 * Seed demo Staff, Inventory, and Members for presentations.
 *
 * Clears existing rows in those areas, then loads realistic Nanyuki campus samples.
 *
 * Usage: php database/seeds/demo-ops.php
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Core\Database;
use App\Services\FormSubmissionService;

$root = dirname(__DIR__, 2);
if (is_file($root . '/.env')) {
    Dotenv\Dotenv::createMutable($root)->safeLoad();
}

putenv('APP_INSTALLED=true');
$_ENV['APP_INSTALLED'] = 'true';

echo "Seeding staff, inventory & members demo data…\n";

FormSubmissionService::ensureFinanceTables();
FormSubmissionService::ensureTable();

$main = Database::connection();
$forms = Database::formsConnection();

/* ─── Staff ─────────────────────────────────────────────────────────────── */

echo "  Clearing staff_members…\n";
$main->exec('DELETE FROM staff_members');

$staff = [
    ['Pastor James Mwangi', 'Lead Pastor', 'Pastoral', '+254712345001', 'james.mwangi@kingdomcitynanyuki.org', 'active', 'Oversees Sunday services and pastoral care'],
    ['Grace Wanjiru', 'Worship Leader', 'Worship', '+254722456002', 'grace.wanjiru@kingdomcitynanyuki.org', 'active', 'Sunday worship & midweek rehearsals'],
    ['Daniel Otieno', 'Media Coordinator', 'Media', '+254733567003', null, 'active', 'Live stream, cameras, and slides'],
    ['Mercy Njeri', 'Admin Assistant', 'Administration', '+254744678004', 'mercy.njeri@kingdomcitynanyuki.org', 'active', 'Front desk, visitors, and scheduling'],
    ['Samuel Kariuki', 'Youth Pastor', 'Youth', '+254755789005', 'samuel.kariuki@kingdomcitynanyuki.org', 'on_leave', 'K-Teens and young adults'],
    ['Faith Akinyi', 'Kids Pastor', 'K-Kids', '+254766890006', null, 'active', 'Sunday kids programme'],
    ['Joseph Maina', 'Caretaker', 'Facilities', '+254777901007', null, 'active', 'Campus grounds and security liaison'],
    ['Esther Chebet', 'Hospitality Lead', 'Hospitality', '+254788012008', 'esther.chebet@kingdomcitynanyuki.org', 'active', 'Welcome team and visitor follow-up'],
    ['Brian Odhiambo', 'Sound Engineer', 'Production', '+254799123009', null, 'inactive', 'Contract ended — archive kept for reference'],
    ['Hannah Wambui', 'Discipleship Coordinator', 'Discipleship', '+254701234010', 'hannah.wambui@kingdomcitynanyuki.org', 'active', 'Kingdom Groups and new believers'],
];

$staffStmt = $main->prepare('
    INSERT INTO staff_members (name, role_title, department, phone, email, status, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?)
');

echo "  Seeding staff…\n";
foreach ($staff as $row) {
    $staffStmt->execute($row);
    echo "    · {$row[0]}\n";
}

/* ─── Inventory ─────────────────────────────────────────────────────────── */

echo "  Clearing inventory_items…\n";
$main->exec('DELETE FROM inventory_items');

$inventory = [
    ['Shure SM58 microphone', 'Sound', 8, 'pcs', 'Main auditorium', 'Handheld mics for worship team'],
    ['Yamaha MG12XU mixer', 'Sound', 1, 'pcs', 'Sound booth', 'Front-of-house mixing console'],
    ['Folding chairs', 'Furniture', 120, 'pcs', 'Main auditorium', 'Grey plastic — Sunday seating'],
    ['Communion trays', 'Sanctuary', 12, 'pcs', 'Sacristy cupboard', 'Stainless steel'],
    ['Epson EB-X06 projector', 'Media', 2, 'pcs', 'Media room', 'Sunday slides and announcements'],
    ['Children\'s play mats', 'K-Kids', 15, 'pcs', 'K-Kids room', 'Soft play area'],
    ['Acoustic drum kit', 'Worship', 1, 'set', 'Stage / store room', 'Pearl export series'],
    ['Keyboard (Yamaha PSR)', 'Worship', 2, 'pcs', 'Stage', 'One primary, one backup'],
    ['Bass guitar', 'Worship', 1, 'pcs', 'Instrument store', 'Church-owned — strings replaced Jul 2026'],
    ['HDMI cables (10m)', 'Media', 6, 'pcs', 'Media room', 'Projector and camera runs'],
    ['First-aid kit', 'Safety', 3, 'pcs', 'Admin office / Kids / Kitchen', 'Checked monthly'],
    ['Detergent & toiletries stock', 'Facilities', 24, 'packs', 'Caretaker store', 'Weekly restock from town'],
    ['Guest welcome cards', 'Hospitality', 200, 'pcs', 'Welcome desk', 'New Here follow-up packs'],
    ['Offering baskets', 'Sanctuary', 8, 'pcs', 'Usher cupboard', 'Woven — used every Sunday'],
    ['Extension cables', 'Facilities', 10, 'pcs', 'Caretaker store', 'Heavy-duty for events'],
];

$invStmt = $main->prepare('
    INSERT INTO inventory_items (name, category, quantity, unit, location, notes)
    VALUES (?, ?, ?, ?, ?, ?)
');

echo "  Seeding inventory…\n";
foreach ($inventory as $row) {
    $invStmt->execute($row);
    echo "    · {$row[0]}\n";
}

/* ─── Members (forms DB) ────────────────────────────────────────────────── */

echo "  Clearing member form_submissions…\n";
$types = FormSubmissionService::MEMBER_FORM_TYPES;
$placeholders = implode(',', array_fill(0, count($types), '?'));
$del = $forms->prepare("DELETE FROM form_submissions WHERE form_type IN ($placeholders)");
$del->execute($types);

$members = [
    [
        'form_type' => 'join',
        'campus' => 'nanyuki',
        'name' => 'Anne Wairimu Kamau',
        'phone' => '+254712890123',
        'email' => 'anne.kamau@gmail.com',
        'date_of_birth' => '1992-03-15',
        'gender' => 'female',
        'marital_status' => 'married',
        'address' => 'Nanyuki Town, along Nyeri–Nanyuki Rd',
        'attending_duration' => '6-12-months',
        'has_spouse' => 'yes',
        'spouse_name' => 'Peter Kamau',
        'spouse_phone' => '+254723901234',
        'spouse_attends' => 'yes',
        'has_children' => 'yes',
        'children_details' => 'Brian Kamau, 7; Faith Kamau, 4',
        'children_attend' => 'yes',
        'has_dependents' => 'no',
        'household_size' => 4,
        'born_again' => 'yes',
        'water_baptised' => 'yes',
        'other_church_member' => 'no',
        'emergency_name' => 'Mary Wairimu',
        'emergency_phone' => '+254734012345',
        'emergency_relationship' => 'Mother',
        'kingdom_group_interest' => 'yes',
        'ministry_serve' => ['worship-service', 'k-kids'],
        'occupation' => 'Teacher, Nanyuki Primary',
        'gifts_skills' => 'Children ministry, hospitality',
        'commit_member' => 'yes',
        'notes' => 'Met at Sunday welcome desk',
    ],
    [
        'form_type' => 'join',
        'campus' => 'nanyuki',
        'name' => 'David Kiprono Langat',
        'phone' => '+254701456789',
        'email' => 'david.langat@outlook.com',
        'date_of_birth' => '1988-11-02',
        'gender' => 'male',
        'marital_status' => 'single',
        'address' => 'Nanyuki Airbase area',
        'attending_duration' => 'over-1-year',
        'has_spouse' => 'no',
        'has_children' => 'no',
        'has_dependents' => 'no',
        'household_size' => 1,
        'born_again' => 'yes',
        'water_baptised' => 'planning',
        'other_church_member' => 'yes',
        'other_church_details' => 'Previously AIC Nanyuki — relocated for work',
        'faith_story' => 'Came to faith in university; looking for a church family in Nanyuki.',
        'emergency_name' => 'Jane Langat',
        'emergency_phone' => '+254712567890',
        'emergency_relationship' => 'Sister',
        'kingdom_group_interest' => 'already',
        'ministry_serve' => ['impact', 'k-hub'],
        'occupation' => 'Software engineer',
        'gifts_skills' => 'Media, mentoring young adults',
        'commit_member' => 'yes',
        'notes' => 'Strong candidate for media team',
    ],
    [
        'form_type' => 'new-here',
        'campus' => 'nanyuki',
        'name' => 'Lucy Muthoni',
        'phone' => '+254722334455',
        'email' => 'lucy.muthoni@gmail.com',
        'first_time' => 'yes',
        'age_range' => '25-34',
        'gender' => 'female',
        'marital_status' => 'Single',
        'heard_about' => 'friend_family',
        'experience' => 'Warm welcome — loved the worship and the message.',
        'notes' => 'Follow up with hospitality team',
    ],
    [
        'form_type' => 'new-here',
        'campus' => 'nanyuki',
        'name' => 'Kevin Ochieng',
        'phone' => '+254733445566',
        'email' => 'kevin.ochieng@yahoo.com',
        'first_time' => 'yes',
        'age_range' => '18-24',
        'gender' => 'male',
        'heard_about' => 'social_media',
        'experience' => 'Came with a college friend. Interested in youth hangouts.',
    ],
    [
        'form_type' => 'new-beginning',
        'campus' => 'nanyuki',
        'name' => 'Ruth Chepkemoi',
        'phone' => '+254744556677',
        'email' => 'ruth.chepkemoi@gmail.com',
        'first_time' => 'yes',
        'age_range' => '35-44',
        'gender' => 'female',
        'decision' => 'first_time',
        'water_baptised' => 'yes',
        'signup' => ['water_baptism', 'new_believers_class', 'kingdom_group'],
        'notes' => 'Prayed with altar team on 12 Jul 2026',
    ],
    [
        'form_type' => 'new-beginning',
        'campus' => 'nanyuki',
        'name' => 'Michael Njoroge',
        'phone' => '+254755667788',
        'email' => 'michael.njoroge@gmail.com',
        'age_range' => '25-34',
        'gender' => 'male',
        'decision' => 'rededicate',
        'signup' => ['kingdom_group'],
        'notes' => 'Returning after time away',
    ],
    [
        'form_type' => 'kingdom-groups',
        'campus' => 'nanyuki',
        'name' => 'Christine Achieng',
        'phone' => '+254766778899',
        'email' => 'christine.achieng@gmail.com',
        'ministry_interest' => 'kingdom_group',
        'age_range' => '25-34',
        'speak_to_pastor' => 'yes',
        'address' => 'Likii area, Nanyuki',
        'notes' => 'Prefers a ladies midweek group',
    ],
    [
        'form_type' => 'kingdom-groups',
        'campus' => 'nanyuki',
        'name' => 'Paul Mutua',
        'phone' => '+254777889900',
        'email' => 'paul.mutua@gmail.com',
        'ministry_interest' => 'worship-service',
        'age_range' => '18-24',
        'address' => 'Nanyuki CBD',
        'notes' => 'Interested in joining the worship team KG',
    ],
    [
        'form_type' => 'join',
        'campus' => 'nairobi',
        'name' => 'Sharon Atieno',
        'phone' => '+254788990011',
        'email' => 'sharon.atieno@gmail.com',
        'date_of_birth' => '1995-07-21',
        'gender' => 'female',
        'marital_status' => 'single',
        'address' => 'Westlands, Nairobi',
        'attending_duration' => '3-6-months',
        'has_spouse' => 'no',
        'has_children' => 'no',
        'has_dependents' => 'no',
        'household_size' => 2,
        'born_again' => 'yes',
        'water_baptised' => 'yes',
        'other_church_member' => 'no',
        'emergency_name' => 'George Atieno',
        'emergency_phone' => '+254799001122',
        'emergency_relationship' => 'Brother',
        'kingdom_group_interest' => 'yes',
        'ministry_serve' => ['waridi', 'k-kids'],
        'occupation' => 'Bank officer',
        'commit_member' => 'yes',
        'notes' => 'Visiting Nanyuki campus often — keep on Nairobi list',
    ],
    [
        'form_type' => 'new-here',
        'campus' => 'nanyuki',
        'name' => 'Tom and family — Irene Wanjiku',
        'phone' => '+254710112233',
        'email' => 'irene.wanjiku@gmail.com',
        'age_range' => '35-44',
        'gender' => 'female',
        'marital_status' => 'Married',
        'heard_about' => 'drove_walked_by',
        'experience' => 'First family visit. Kids enjoyed K-Kids.',
        'notes' => 'Send K-Kids info pack',
    ],
];

echo "  Seeding members…\n";
foreach ($members as $member) {
    $id = FormSubmissionService::createManual($member);
    $label = $member['name'] . ' (' . $member['form_type'] . ')';
    echo "    · #{$id} {$label}\n";
}

/* Leave a couple as "new" for badge demo */
$newIds = $forms->query("
    SELECT id FROM form_submissions
    WHERE form_type IN ('new-here', 'new-beginning', 'kingdom-groups')
    ORDER BY id DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_COLUMN);

if ($newIds) {
    $upd = $forms->prepare("UPDATE form_submissions SET status = 'new', updated_at = NOW() WHERE id = ?");
    foreach ($newIds as $id) {
        $upd->execute([(int) $id]);
    }
    echo "  Marked " . count($newIds) . " recent submissions as status=new (sidebar badge).\n";
}

$staffCount = (int) $main->query('SELECT COUNT(*) FROM staff_members')->fetchColumn();
$invCount = (int) $main->query('SELECT COUNT(*) FROM inventory_items')->fetchColumn();
$memStmt = $forms->prepare("SELECT COUNT(*) FROM form_submissions WHERE form_type IN ($placeholders)");
$memStmt->execute($types);
$memCount = (int) $memStmt->fetchColumn();

echo "\nDone.\n";
echo "  Staff: {$staffCount}\n";
echo "  Inventory: {$invCount}\n";
echo "  Members: {$memCount}\n";
echo "\nOpen Admin → Members / Staff / Inventory to demo.\n";
