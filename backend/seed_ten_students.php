<?php
require_once __DIR__ . '/config/Env.php';
\App\Config\Env::load();
require_once __DIR__ . '/config/Database.php';

use App\Config\Database;

$db = Database::getConnection();

// 1. Get Student role ID
$roleStmt = $db->query("SELECT id FROM roles WHERE slug = 'student' OR name = 'student' LIMIT 1");
$roleId = (int)$roleStmt->fetchColumn();
if (!$roleId) {
    $roleId = 7;
}

// 2. Active School Year
$syStmt = $db->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1");
$syId = (int)$syStmt->fetchColumn();
if (!$syId) {
    $syId = 1;
}

// 3. Define 10 Students
$studentsData = [
    [
        'username'    => 'student.santos',
        'email'       => 'juan.santos@bsla.edu.ph',
        'first_name'  => 'Juan',
        'last_name'   => 'Santos',
        'middle_name' => 'Dela Cruz',
        'gender'      => 'Male',
        'student_no'  => '2026-JHS-0101',
        'lrn'         => '109876543201',
        'grade_code'  => 'G7',
        'section_name'=> 'Grade 7 - Diamond'
    ],
    [
        'username'    => 'student.reyes',
        'email'       => 'maria.reyes@bsla.edu.ph',
        'first_name'  => 'Maria',
        'last_name'   => 'Reyes',
        'middle_name' => 'Aquino',
        'gender'      => 'Female',
        'student_no'  => '2026-JHS-0102',
        'lrn'         => '109876543202',
        'grade_code'  => 'G7',
        'section_name'=> 'Grade 7 - Emerald'
    ],
    [
        'username'    => 'student.cruz',
        'email'       => 'carlos.cruz@bsla.edu.ph',
        'first_name'  => 'Carlos',
        'last_name'   => 'Cruz',
        'middle_name' => 'Bautista',
        'gender'      => 'Male',
        'student_no'  => '2026-JHS-0103',
        'lrn'         => '109876543203',
        'grade_code'  => 'G8',
        'section_name'=> 'Grade 8 - Sapphire'
    ],
    [
        'username'    => 'student.bautista',
        'email'       => 'ana.bautista@bsla.edu.ph',
        'first_name'  => 'Ana',
        'last_name'   => 'Bautista',
        'middle_name' => 'Mendoza',
        'gender'      => 'Female',
        'student_no'  => '2026-JHS-0104',
        'lrn'         => '109876543204',
        'grade_code'  => 'G8',
        'section_name'=> 'Grade 8 - Topaz'
    ],
    [
        'username'    => 'student.garcia',
        'email'       => 'mark.garcia@bsla.edu.ph',
        'first_name'  => 'Mark',
        'last_name'   => 'Garcia',
        'middle_name' => 'Torres',
        'gender'      => 'Male',
        'student_no'  => '2026-JHS-0105',
        'lrn'         => '109876543205',
        'grade_code'  => 'G9',
        'section_name'=> 'Grade 9 - Ruby'
    ],
    [
        'username'    => 'student.mendoza',
        'email'       => 'patricia.mendoza@bsla.edu.ph',
        'first_name'  => 'Patricia',
        'last_name'   => 'Mendoza',
        'middle_name' => 'Navarro',
        'gender'      => 'Female',
        'student_no'  => '2026-JHS-0106',
        'lrn'         => '109876543206',
        'grade_code'  => 'G9',
        'section_name'=> 'Grade 9 - Garnet'
    ],
    [
        'username'    => 'student.torres',
        'email'       => 'gabriel.torres@bsla.edu.ph',
        'first_name'  => 'Gabriel',
        'last_name'   => 'Torres',
        'middle_name' => 'Flores',
        'gender'      => 'Male',
        'student_no'  => '2026-JHS-0107',
        'lrn'         => '109876543207',
        'grade_code'  => 'G10',
        'section_name'=> 'Grade 10 - Pearl'
    ],
    [
        'username'    => 'student.aquino',
        'email'       => 'sophia.aquino@bsla.edu.ph',
        'first_name'  => 'Sophia',
        'last_name'   => 'Aquino',
        'middle_name' => 'Ramos',
        'gender'      => 'Female',
        'student_no'  => '2026-JHS-0108',
        'lrn'         => '109876543208',
        'grade_code'  => 'G10',
        'section_name'=> 'Grade 10 - Jade'
    ],
    [
        'username'    => 'student.navarro',
        'email'       => 'miguel.navarro@bsla.edu.ph',
        'first_name'  => 'Miguel',
        'last_name'   => 'Navarro',
        'middle_name' => 'Castillo',
        'gender'      => 'Male',
        'student_no'  => '2026-SHS-0109',
        'lrn'         => '109876543209',
        'grade_code'  => 'G11',
        'section_name'=> 'Grade 11 - STEM A'
    ],
    [
        'username'    => 'student.delacruz',
        'email'       => 'elena.delacruz@bsla.edu.ph',
        'first_name'  => 'Elena',
        'last_name'   => 'Dela Cruz',
        'middle_name' => 'Villanueva',
        'gender'      => 'Female',
        'student_no'  => '2026-SHS-0110',
        'lrn'         => '109876543210',
        'grade_code'  => 'G12',
        'section_name'=> 'Grade 12 - ABM A'
    ]
];

$passwordHash = password_hash('password123', PASSWORD_BCRYPT);
$createdCount = 0;
$sqlStatements = [];

foreach ($studentsData as $idx => $st) {
    // Check if user exists
    $uStmt = $db->prepare("SELECT id FROM users WHERE username = :un OR email = :em");
    $uStmt->execute(['un' => $st['username'], 'em' => $st['email']]);
    $userId = $uStmt->fetchColumn();

    if (!$userId) {
        $insU = $db->prepare("
            INSERT INTO users (role_id, username, email, password, student_id, status, created_at)
            VALUES (:rid, :un, :em, :pwd, :sno, 'Active', NOW())
        ");
        $insU->execute([
            'rid' => $roleId,
            'un'  => $st['username'],
            'em'  => $st['email'],
            'pwd' => $passwordHash,
            'sno' => $st['student_no']
        ]);
        $userId = (int)$db->lastInsertId();
    } else {
        $updU = $db->prepare("UPDATE users SET password = :pwd, status = 'Active', student_id = :sno WHERE id = :uid");
        $updU->execute(['pwd' => $passwordHash, 'sno' => $st['student_no'], 'uid' => $userId]);
    }

    // User Profile
    $pCheck = $db->prepare("SELECT id FROM user_profiles WHERE user_id = :uid");
    $pCheck->execute(['uid' => $userId]);
    if (!$pCheck->fetchColumn()) {
        $insP = $db->prepare("
            INSERT INTO user_profiles (user_id, first_name, last_name, middle_name, gender, contact_number, address)
            VALUES (:uid, :fn, :ln, :mn, :gen, '09171234567', 'Biringan City')
        ");
        $insP->execute([
            'uid' => $userId,
            'fn'  => $st['first_name'],
            'ln'  => $st['last_name'],
            'mn'  => $st['middle_name'],
            'gen' => $st['gender']
        ]);
    }

    // Resolve Grade Level & Section
    $glStmt = $db->prepare("SELECT id FROM grade_levels WHERE code = :code LIMIT 1");
    $glStmt->execute(['code' => $st['grade_code']]);
    $glId = (int)$glStmt->fetchColumn();

    $secStmt = $db->prepare("SELECT id, strand_id FROM sections WHERE name = :sname LIMIT 1");
    $secStmt->execute(['sname' => $st['section_name']]);
    $sec = $secStmt->fetch();

    if (!$sec) {
        $secStmt2 = $db->prepare("SELECT id, strand_id FROM sections WHERE grade_level_id = :glid LIMIT 1");
        $secStmt2->execute(['glid' => $glId]);
        $sec = $secStmt2->fetch();
    }

    $secId = $sec ? (int)$sec['id'] : 1;
    $strandId = $sec ? $sec['strand_id'] : null;

    // Check Enrollment
    $enrCheck = $db->prepare("SELECT id FROM enrollments WHERE student_id = :sid");
    $enrCheck->execute(['sid' => $userId]);
    $enrId = $enrCheck->fetchColumn();

    if (!$enrId) {
        $enrNo = 'ENR-2026-' . str_pad((string)(100 + $idx + 1), 4, '0', STR_PAD_LEFT);
        $insE = $db->prepare("
            INSERT INTO enrollments (
                enrollment_no, student_no, student_id, school_year_id, grade_level_id, strand_id, section_id, lrn,
                status, semester, created_at, enrolled_at
            ) VALUES (
                :eno, :sno, :sid, :syid, :glid, :strid, :secid, :lrn,
                'Officially Enrolled', '1st Semester', NOW(), NOW()
            )
        ");
        $insE->execute([
            'eno'   => $enrNo,
            'sno'   => $st['student_no'],
            'sid'   => $userId,
            'syid'  => $syId,
            'glid'  => $glId,
            'strid' => $strandId,
            'secid' => $secId,
            'lrn'   => $st['lrn']
        ]);
        $enrId = (int)$db->lastInsertId();
    }

    // Attach Enrolled Subjects
    if ($enrId && $glId) {
        $subjList = $db->prepare("SELECT id FROM subjects WHERE grade_level_id = :glid");
        $subjList->execute(['glid' => $glId]);
        $subs = $subjList->fetchAll(PDO::FETCH_COLUMN);

        $insEs = $db->prepare("
            INSERT IGNORE INTO enrollment_subjects (enrollment_id, subject_id, status)
            VALUES (:eid, :subid, 'Enrolled')
        ");
        foreach ($subs as $subId) {
            $insEs->execute(['eid' => $enrId, 'subid' => $subId]);
        }
    }

    // Attach Assessment
    if ($enrId) {
        $assCheck = $db->prepare("SELECT id FROM student_assessments WHERE enrollment_id = :eid");
        $assCheck->execute(['eid' => $enrId]);
        if (!$assCheck->fetchColumn()) {
            $assNo = 'ASS-2026-' . str_pad((string)(100 + $idx + 1), 4, '0', STR_PAD_LEFT);
            $insAss = $db->prepare("
                INSERT INTO student_assessments (
                    enrollment_id, school_year_id, assessment_no, total_tuition, total_miscellaneous, gross_amount, voucher_discount, net_payable, total_paid, remaining_balance, status, created_at
                ) VALUES (
                    :eid, :syid, :ano, 15000.00, 3500.00, 18500.00, 3500.00, 15000.00, 15000.00, 0.00, 'Paid', NOW()
                )
            ");
            $insAss->execute(['eid' => $enrId, 'syid' => $syId, 'ano' => $assNo]);
        }
    }

    $createdCount++;
    echo "✓ Student #{$createdCount}: {$st['username']} (ID: {$st['student_no']}) | Pass: password123 | Name: {$st['first_name']} {$st['last_name']} | Section: {$st['section_name']}\n";
}

echo "\nSuccessfully seeded $createdCount students with password: password123\n";
