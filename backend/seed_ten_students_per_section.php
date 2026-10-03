<?php
require_once __DIR__ . '/config/Env.php';
\App\Config\Env::load();
require_once __DIR__ . '/config/Database.php';

use App\Config\Database;

$db = Database::getConnection();

// 1. Get Student role ID
$roleStmt = $db->query("SELECT id FROM roles WHERE slug = 'student' OR name = 'student' LIMIT 1");
$roleId = (int)$roleStmt->fetchColumn();
if (!$roleId) $roleId = 7;

// 2. Active School Year
$syStmt = $db->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1");
$syId = (int)$syStmt->fetchColumn();
if (!$syId) $syId = 1;

// 3. Fetch all active sections
$secStmt = $db->query("
    SELECT s.id, s.name, s.grade_level_id, s.strand_id, s.room,
           gl.code as grade_code, gl.name as grade_name, gl.category as grade_category,
           st.code as strand_code
    FROM sections s
    JOIN grade_levels gl ON s.grade_level_id = gl.id
    LEFT JOIN strands st ON s.strand_id = st.id
    WHERE s.is_active = 1
    ORDER BY s.grade_level_id ASC, s.id ASC
");
$sections = $secStmt->fetchAll();

echo "Found " . count($sections) . " active sections.\n";

$firstNamesMale = [
    'Juan', 'Carlos', 'Mark', 'Gabriel', 'Miguel', 'Angelo', 'Christian', 'Joshua', 'Daniel', 'John Paul',
    'Rafael', 'Alexander', 'Ethan', 'Nathan', 'Adrian', 'Francis', 'Justin', 'Matthew', 'Patrick', 'Jerome',
    'Dominic', 'Vincent', 'Paolo', 'Lorenzo', 'Manuel', 'Joaquin', 'Diego', 'Sebastian', 'Enzo', 'Anton'
];

$firstNamesFemale = [
    'Maria', 'Ana', 'Patricia', 'Sophia', 'Elena', 'Angela', 'Kristine', 'Nicole', 'Bea', 'Samantha',
    'Camille', 'Alyssa', 'Danielle', 'Chloe', 'Andrea', 'Hannah', 'Jasmine', 'Bianca', 'Katrina', 'Clarissa',
    'Rochelle', 'Stephanie', 'Marianne', 'Gabriela', 'Giselle', 'Teresa', 'Maricris', 'Lourdes', 'Rowena', 'Eileen'
];

$lastNames = [
    'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza', 'Torres', 'Aquino', 'Navarro', 'Dela Cruz',
    'Ramos', 'Flores', 'Castillo', 'Villanueva', 'Gutierrez', 'Morales', 'Alvarez', 'Salazar', 'Pascual', 'Mercado',
    'Lim', 'Sison', 'Velasco', 'Soriano', 'Aguilar', 'Padilla', 'Tan', 'Luna', 'Silang', 'Valdez',
    'Castro', 'Rivera', 'Manalo', 'Tolentino', 'Corpuz', 'Domingo', 'Soriano', 'Enriquez', 'Magat', 'Espiritu'
];

$passwordHash = password_hash('password123', PASSWORD_BCRYPT);
$totalEnrolledNew = 0;
$globalStudentCounter = 100;

foreach ($sections as $sec) {
    $secId = (int)$sec['id'];
    $glId = (int)$sec['grade_level_id'];
    $strandId = $sec['strand_id'] ? (int)$sec['strand_id'] : null;
    $gradeCode = $sec['grade_code'];
    $isSHS = ($sec['grade_category'] === 'SHS');
    $categoryPrefix = $isSHS ? 'SHS' : 'JHS';

    // Check existing officially enrolled students in this section
    $existingEnr = $db->prepare("
        SELECT student_id FROM enrollments 
        WHERE section_id = :sec_id AND status IN ('Officially Enrolled', 'Enrolled')
    ");
    $existingEnr->execute(['sec_id' => $secId]);
    $currentCount = $existingEnr->rowCount();

    $needed = max(0, 10 - $currentCount);
    echo "\n------------------------------------------------------------\n";
    echo "Section: [{$sec['id']}] {$sec['name']} ({$sec['grade_code']}) | Current Enrollees: {$currentCount} | Needed: {$needed}\n";

    if ($needed <= 0) {
        echo "✓ Already has 10+ students.\n";
        continue;
    }

    // Fetch subjects for this grade level & strand
    $subQuery = "SELECT id FROM subjects WHERE grade_level_id = :glid AND is_active = 1";
    if ($isSHS && $strandId) {
        $subQuery .= " AND (strand_id IS NULL OR strand_id = $strandId)";
    }
    $subStmt = $db->prepare($subQuery);
    $subStmt->execute(['glid' => $glId]);
    $subjectIds = $subStmt->fetchAll(PDO::FETCH_COLUMN);

    for ($i = 1; $i <= $needed; $i++) {
        $studentNum = $currentCount + $i;
        $isFemale = ($studentNum % 2 === 0);
        $fn = $isFemale ? $firstNamesFemale[($secId * 7 + $studentNum) % count($firstNamesFemale)] : $firstNamesMale[($secId * 7 + $studentNum) % count($firstNamesMale)];
        $ln = $lastNames[($secId * 5 + $studentNum) % count($lastNames)];
        $mn = $lastNames[($secId * 3 + $studentNum) % count($lastNames)];
        $gender = $isFemale ? 'Female' : 'Male';

        $cleanSecName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $sec['name']));
        $username = "stud.{$cleanSecName}.{$studentNum}";
        $email = "{$username}@student.bsla.edu.ph";
        $studentNo = sprintf("2026-%s-%03d%02d", $categoryPrefix, $secId + 100, $studentNum);
        $lrn = sprintf("109%03d%06d", $secId + 100, $studentNum);
        $enrNo = sprintf("ENR-2026-%03d%03d", $secId + 100, $studentNum);
        $assNo = sprintf("ASS-2026-%03d%03d", $secId + 100, $studentNum);

        // 1. Create or Update User
        $uCheck = $db->prepare("SELECT id FROM users WHERE username = :un OR email = :em");
        $uCheck->execute(['un' => $username, 'em' => $email]);
        $userId = $uCheck->fetchColumn();

        if (!$userId) {
            $insU = $db->prepare("
                INSERT INTO users (role_id, username, email, password, student_id, status, created_at)
                VALUES (:rid, :un, :em, :pwd, :sno, 'Active', NOW())
            ");
            $insU->execute([
                'rid' => $roleId,
                'un'  => $username,
                'em'  => $email,
                'pwd' => $passwordHash,
                'sno' => $studentNo
            ]);
            $userId = (int)$db->lastInsertId();
        } else {
            $updU = $db->prepare("UPDATE users SET password = :pwd, status = 'Active', student_id = :sno WHERE id = :uid");
            $updU->execute(['pwd' => $passwordHash, 'sno' => $studentNo, 'uid' => $userId]);
        }

        // 2. User Profile
        $pCheck = $db->prepare("SELECT id FROM user_profiles WHERE user_id = :uid");
        $pCheck->execute(['uid' => $userId]);
        if (!$pCheck->fetchColumn()) {
            $insP = $db->prepare("
                INSERT INTO user_profiles (user_id, first_name, last_name, middle_name, gender, contact_number, address)
                VALUES (:uid, :fn, :ln, :mn, :gen, '09171234567', 'Biringan City')
            ");
            $insP->execute([
                'uid' => $userId,
                'fn'  => $fn,
                'ln'  => $ln,
                'mn'  => $mn,
                'gen' => $gender
            ]);
        }

        // 3. Enrollment
        $eCheck = $db->prepare("SELECT id FROM enrollments WHERE student_id = :sid");
        $eCheck->execute(['sid' => $userId]);
        $enrId = $eCheck->fetchColumn();

        if (!$enrId) {
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
                'sno'   => $studentNo,
                'sid'   => $userId,
                'syid'  => $syId,
                'glid'  => $glId,
                'strid' => $strandId,
                'secid' => $secId,
                'lrn'   => $lrn
            ]);
            $enrId = (int)$db->lastInsertId();
        }

        // 4. Enrollment Subjects
        if ($enrId && !empty($subjectIds)) {
            $insEs = $db->prepare("
                INSERT IGNORE INTO enrollment_subjects (enrollment_id, subject_id, status)
                VALUES (:eid, :subid, 'Enrolled')
            ");
            foreach ($subjectIds as $subId) {
                $insEs->execute(['eid' => $enrId, 'subid' => $subId]);
            }
        }

        // 5. Student Assessment SOA
        if ($enrId) {
            $assCheck = $db->prepare("SELECT id FROM student_assessments WHERE enrollment_id = :eid");
            $assCheck->execute(['eid' => $enrId]);
            if (!$assCheck->fetchColumn()) {
                $gross = $isSHS ? 22500.00 : 15200.00;
                $insAss = $db->prepare("
                    INSERT INTO student_assessments (
                        enrollment_id, school_year_id, assessment_no, total_tuition, total_miscellaneous, gross_amount, voucher_discount, net_payable, total_paid, remaining_balance, status, created_at
                    ) VALUES (
                        :eid, :syid, :ano, :g1, 0.00, :g2, 0.00, :g3, :g4, 0.00, 'Paid', NOW()
                    )
                ");
                $insAss->execute([
                    'eid'  => $enrId,
                    'syid' => $syId,
                    'ano'  => $assNo,
                    'g1'   => $gross,
                    'g2'   => $gross,
                    'g3'   => $gross,
                    'g4'   => $gross
                ]);
            }
        }

        $totalEnrolledNew++;
        echo "  + [Student {$studentNum}/10] {$username} ({$studentNo}) | Pass: password123 | {$fn} {$ln} ({$gender})\n";
    }

    // Update section's enrolled count
    $db->prepare("UPDATE sections SET current_enrolled = (SELECT COUNT(*) FROM enrollments WHERE section_id = :sec_1 AND status IN ('Officially Enrolled', 'Enrolled')) WHERE id = :sec_2")->execute(['sec_1' => $secId, 'sec_2' => $secId]);
}

echo "\n============================================================\n";
echo "SUMMARY: Successfully seeded and ensured 10 students in all sections! (New additions: $totalEnrolledNew)\n";
echo "All accounts have password: password123\n";
echo "============================================================\n";
