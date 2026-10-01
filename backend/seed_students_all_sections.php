<?php
// backend/seed_students_all_sections.php
require_once __DIR__ . '/config/Env.php';
require_once __DIR__ . '/config/Database.php';

use App\Config\Database;

$db = Database::getConnection();

echo "========================================================\n";
echo " BIRINGAN HIGH SCHOOL - COMPLETE STUDENT SEEDER\n";
echo " Goal: Ensure EVERY section has AT LEAST 10 students\n";
echo "========================================================\n\n";

// Name pools
$maleFirstNames = [
    'Juan', 'Jose', 'Mark', 'John', 'Christian', 'Gabriel', 'Angelo', 'Paolo', 'Miguel', 'Joshua',
    'Daniel', 'Rafael', 'Francis', 'David', 'Kyle', 'James', 'Ethan', 'Nathan', 'Adrian', 'Kenneth',
    'Carl', 'Anthony', 'Dominic', 'Vincent', 'Matthew', 'Ian', 'Jerome', 'Patrick', 'Alexander', 'Jethro',
    'Timothy', 'Sean', 'Bryan', 'Justin', 'Aaron', 'Leo', 'Noel', 'Marcus', 'Samuel', 'Elijah',
    'Carlos', 'Joaquin', 'Alonzo', 'Lorenzo', 'Sebastian', 'Mateo', 'Derrick', 'Kiel', 'Zack', 'Alden'
];

$femaleFirstNames = [
    'Maria', 'Andrea', 'Sophia', 'Alyssa', 'Patricia', 'Angelica', 'Bea', 'Chloe', 'Nicole', 'Hannah',
    'Camille', 'Denise', 'Samantha', 'Kaye', 'Bianca', 'Jasmine', 'Mariel', 'Ella', 'Princess', 'Rhea',
    'Grace', 'Katrina', 'Joy', 'Clarisse', 'Danica', 'Faith', 'Geline', 'Bernadette', 'Kristine', 'Janine',
    'Christine', 'Abigail', 'Stephanie', 'Paula', 'Irish', 'Mikaela', 'Caitlin', 'Rowena', 'Leah', 'Carla',
    'Angel', 'Gabrielle', 'Althea', 'Cheska', 'Trisha', 'Erika', 'Maxine', 'Isabella', 'Marjorie', 'Kyla'
];

$middleNames = [
    'Santos', 'Reyes', 'Cruz', 'Bautista', 'Del Rosario', 'Garcia', 'Mendoza', 'Torres', 'Tomas', 'Aquino',
    'Flores', 'Gonzales', 'Ramos', 'Castillo', 'Villanueva', 'Fernandez', 'Valdez', 'Navarro', 'Perez', 'Salazar'
];

$lastNames = [
    'Dela Cruz', 'Santos', 'Reyes', 'Villanueva', 'Bautista', 'Mendoza', 'Aquino', 'Garcia', 'Torres', 'Flores',
    'Gonzales', 'Ramos', 'Fernandez', 'Castillo', 'Valdez', 'Navarro', 'Perez', 'Salazar', 'Alcantara', 'Magbanua',
    'Rivera', 'Santiago', 'Corpuz', 'Ocampo', 'Tolentino', 'Mercado', 'Soriano', 'Manalo', 'Domingo', 'Guerrero',
    'Pascual', 'Abad', 'Robles', 'Velasco', 'De Leon', 'Rosario', 'Ignacio', 'Aguilar', 'Chavez', 'Cortez',
    'Estrada', 'Pineda', 'Serrano', 'Fajardo', 'Beltran', 'Miranda', 'Padilla', 'Cabrera', 'Silverio', 'Macaraeg'
];

$streets = [
    '124 Rizal St.', '45 Bonifacio Ave.', '78 Mabini St.', '12 Luna St.', '89 Del Pilar St.',
    '230 Quezon Blvd.', '56 Aguinaldo Highway', '14 Burgos St.', '67 Tandang Sora Ave.', '90 Macapagal Ave.',
    '33 Malvar St.', '102 Basa St.', '15 Escolta St.', '44 Roxas Blvd.', '88 Katipunan Ave.'
];

$barangays = [
    'Barangay San Roque', 'Barangay Central', 'Barangay Poblacion', 'Barangay Santa Cruz',
    'Barangay Bagong Silang', 'Barangay Sto. Niño', 'Barangay San Jose', 'Barangay Immaculate',
    'Barangay Fatima', 'Barangay Calanipawan', 'Barangay Marasbaras', 'Barangay Caibaan'
];

$cities = [
    ['city' => 'Tacloban City', 'province' => 'Leyte', 'zip' => '6500'],
    ['city' => 'Borongan City', 'province' => 'Eastern Samar', 'zip' => '6800'],
    ['city' => 'Catbalogan City', 'province' => 'Samar', 'zip' => '6700'],
    ['city' => 'Ormoc City', 'province' => 'Leyte', 'zip' => '6541'],
    ['city' => 'Calbayog City', 'province' => 'Samar', 'zip' => '6710'],
    ['city' => 'Guiuan', 'province' => 'Eastern Samar', 'zip' => '6809'],
    ['city' => 'Quezon City', 'province' => 'Metro Manila', 'zip' => '1100'],
    ['city' => 'Pasig City', 'province' => 'Metro Manila', 'zip' => '1600']
];

$passwordHash = password_hash('password123', PASSWORD_BCRYPT);

// Find active School Year
$syStmt = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1");
$sy = $syStmt->fetch(PDO::FETCH_ASSOC);
if (!$sy) {
    die("Error: No active school year found.\n");
}
$schoolYearId = $sy['id'];

// Get highest existing sequence numbers to prevent collision
$maxUserStmt = $db->query("SELECT MAX(id) as max_id FROM users");
$maxUserId = (int)($maxUserStmt->fetch(PDO::FETCH_ASSOC)['max_id'] ?? 100);

$maxLrnStmt = $db->query("SELECT MAX(lrn) as max_lrn FROM admission_applications WHERE lrn LIKE '108923%'");
$lastLrn = $maxLrnStmt->fetch(PDO::FETCH_ASSOC)['max_lrn'] ?? null;
$lrnCounter = $lastLrn ? (int)substr($lastLrn, -5) + 1 : 10001;

$seqCounter = $maxUserId + 100;

// Fetch all sections
$sectionsStmt = $db->query("
    SELECT s.id, s.name, s.grade_level_id, g.name as grade_name, g.category as grade_category, g.sequence_order,
           s.strand_id, st.code as strand_code, st.track_id
    FROM sections s
    JOIN grade_levels g ON s.grade_level_id = g.id
    LEFT JOIN strands st ON s.strand_id = st.id
    ORDER BY s.grade_level_id, s.id
");
$sections = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($sections) . " sections. Seeding at least 10 students per section...\n\n";

$totalCreated = 0;

$stmtInsertUser = $db->prepare("
    INSERT INTO users (role_id, username, email, student_id, password, status, created_at, updated_at)
    VALUES (7, :username, :email, :student_id, :password, 'Active', NOW(), NOW())
");

$stmtInsertProfile = $db->prepare("
    INSERT INTO user_profiles (user_id, first_name, middle_name, last_name, gender, birthdate, contact_number, address, created_at, updated_at)
    VALUES (:user_id, :first_name, :middle_name, :last_name, :gender, :birthdate, :contact_number, :address, NOW(), NOW())
");

$stmtInsertApplication = $db->prepare("
    INSERT INTO admission_applications (
        application_no, user_id, school_year_id, applicant_type, lrn, first_name, middle_name, last_name,
        gender, birthdate, dob, birthplace, pob, civil_status, nationality, religion, contact_number, phone,
        email, address_street, address_barangay, address_city, address_province, address_zip, address,
        guardian_name, guardian_relationship, guardian_contact, guardian_occupation,
        father_name, father_contact, mother_name, mother_contact,
        last_school_attended, last_school_type, last_school_year, last_grade_completed,
        grade_level_id, track_id, strand_id, voucher_status, voucher_type, gwa, student_no,
        assigned_section_id, status, reviewed_by, reviewed_at, submitted_at, created_at, updated_at
    ) VALUES (
        :application_no, :user_id, :school_year_id, 'New Student', :lrn, :first_name, :middle_name, :last_name,
        :gender, :birthdate, :dob, :birthplace, :pob, 'Single', 'Filipino', 'Roman Catholic', :contact_number, :phone,
        :email, :address_street, :address_barangay, :address_city, :address_province, :address_zip, :address,
        :guardian_name, :guardian_relationship, :guardian_contact, 'Employed / Self-Employed',
        :father_name, :father_contact, :mother_name, :mother_contact,
        :last_school_attended, 'Public', '2025-2026', :last_grade_completed,
        :grade_level_id, :track_id, :strand_id, :voucher_status, :voucher_type, :gwa, :student_no,
        :assigned_section_id, 'Enrolled', 1, NOW(), NOW(), NOW(), NOW()
    )
");

$stmtInsertEnrollment = $db->prepare("
    INSERT INTO enrollments (
        enrollment_no, student_no, student_id, application_id, school_year_id,
        grade_level_id, track_id, strand_id, section_id, lrn, semester,
        enrollment_date, status, approved_by, enrolled_at, created_at
    ) VALUES (
        :enrollment_no, :student_no, :student_id, :application_id, :school_year_id,
        :grade_level_id, :track_id, :strand_id, :section_id, :lrn, :semester,
        '2026-08-15', 'Officially Enrolled', 1, NOW(), NOW()
    )
");

$stmtInsertSubject = $db->prepare("
    INSERT INTO enrollment_subjects (enrollment_id, subject_id, status, created_at)
    VALUES (:enrollment_id, :subject_id, 'Enrolled', NOW())
");

$stmtInsertAssessment = $db->prepare("
    INSERT INTO student_assessments (
        enrollment_id, school_year_id, assessment_no, payment_ticket,
        payment_mode, payment_verification_status, total_tuition, total_miscellaneous, total_misc,
        total_laboratory, total_lab, total_other_fees, gross_amount, total_assessed,
        voucher_discount, net_payable, minimum_downpayment, downpayment, total_paid,
        amount_paid, remaining_balance, status, assessed_by, created_at, updated_at
    ) VALUES (
        :enrollment_id, :school_year_id, :assessment_no, :payment_ticket,
        'Online Bank Transfer', 'Verified', :total_tuition, :total_miscellaneous, :total_misc,
        :total_laboratory, :total_lab, :total_other_fees, :gross_amount, :total_assessed,
        :voucher_discount, :net_payable, 3000.00, 3000.00, :total_paid,
        :amount_paid, 0.00, 'Paid', 1, NOW(), NOW()
    )
");

$stmtInsertRecord = $db->prepare("
    INSERT INTO student_records (
        student_id, lrn, school_year_id, grade_level_id, strand_id, section_id,
        general_average, previous_school_f137_status, promotion_status, remarks, created_at, updated_at
    ) VALUES (
        :student_id, :lrn, :school_year_id, :grade_level_id, :strand_id, :section_id,
        :general_average, 'Received / Verified', 'Under Evaluation', 'Regular Student in good standing', NOW(), NOW()
    )
");

$stmtUpdateSectionCount = $db->prepare("
    UPDATE sections 
    SET current_enrolled = (SELECT COUNT(*) FROM enrollments WHERE section_id = :sid AND status = 'Enrolled')
    WHERE id = :sid2
");

foreach ($sections as $section) {
    $sectionId = $section['id'];
    $gradeLevelId = $section['grade_level_id'];
    $gradeName = $section['grade_name'];
    $gradeCategory = $section['grade_category'];
    $strandId = $section['strand_id'];
    $strandCode = $section['strand_code'] ?: 'N/A';
    $trackId = $section['track_id'];
    $sectionName = $section['name'];

    // Check current count
    $cntStmt = $db->prepare("SELECT COUNT(*) as count FROM enrollments WHERE section_id = :sid AND status = 'Enrolled'");
    $cntStmt->execute(['sid' => $sectionId]);
    $currentCount = (int)$cntStmt->fetch(PDO::FETCH_ASSOC)['count'];

    $targetCount = 10;
    $toCreate = max(0, $targetCount - $currentCount);

    echo "Section #{$sectionId}: {$sectionName} ({$gradeName} - {$strandCode}) | Current: {$currentCount} | Adding: {$toCreate}\n";

    if ($toCreate === 0) {
        continue;
    }

    // Determine subjects to enroll
    if ($gradeCategory === 'JHS') {
        $subStmt = $db->prepare("SELECT id FROM subjects WHERE grade_level_id = :gid AND is_active = 1");
        $subStmt->execute(['gid' => $gradeLevelId]);
        $subjectIds = $subStmt->fetchAll(PDO::FETCH_COLUMN);
        $semester = 'Full Year';
    } else {
        $subStmt = $db->prepare("
            SELECT id FROM subjects 
            WHERE grade_level_id = :gid 
              AND (strand_id IS NULL OR strand_id = :sid)
              AND (semester = '1st Semester' OR semester = 'Full Year')
              AND is_active = 1
        ");
        $subStmt->execute(['gid' => $gradeLevelId, 'sid' => $strandId]);
        $subjectIds = $subStmt->fetchAll(PDO::FETCH_COLUMN);
        $semester = '1st Semester';
    }

    for ($i = 0; $i < $toCreate; $i++) {
        $seqCounter++;
        $lrnCounter++;

        $isMale = ($i % 2 === 0);
        $firstName = $isMale 
            ? $maleFirstNames[array_rand($maleFirstNames)] 
            : $femaleFirstNames[array_rand($femaleFirstNames)];
        $middleName = $middleNames[array_rand($middleNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        $gender = $isMale ? 'Male' : 'Female';

        // Birthdate based on grade level
        $baseYear = 2026 - (12 + (int)$section['sequence_order']);
        $month = str_pad(mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
        $day = str_pad(mt_rand(1, 28), 2, '0', STR_PAD_LEFT);
        $birthdate = "{$baseYear}-{$month}-{$day}";

        // Student numbers
        $studentNo = "2026-STU-" . str_pad($seqCounter, 4, '0', STR_PAD_LEFT);
        $lrn = "108923" . str_pad($lrnCounter, 6, '0', STR_PAD_LEFT);
        $appNo = "APP-2026-" . str_pad($seqCounter, 4, '0', STR_PAD_LEFT);
        $enrNo = "ENR-2026-" . str_pad($seqCounter, 4, '0', STR_PAD_LEFT);
        $assNo = "ASS-2026-" . str_pad($seqCounter, 4, '0', STR_PAD_LEFT);
        $ticket = "TKT-" . strtoupper(substr(md5(uniqid($seqCounter, true)), 0, 8));

        $username = $studentNo; // Actual student account username
        $cleanFirst = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName));
        $cleanLast = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $lastName));
        $email = "{$cleanFirst}.{$cleanLast}.{$seqCounter}@bsla.edu.ph";
        $contactNumber = "09" . mt_rand(100000000, 999999999);

        $loc = $cities[array_rand($cities)];
        $street = $streets[array_rand($streets)];
        $brgy = $barangays[array_rand($barangays)];
        $fullAddress = "{$street}, {$brgy}, {$loc['city']}, {$loc['province']} {$loc['zip']}";

        $fatherName = "Eduardo {$lastName}";
        $motherName = "Elena {$middleName} {$lastName}";
        $guardianName = $fatherName;

        $hasVoucher = ($gradeCategory === 'SHS' && ($i % 3 !== 0));
        $voucherStatus = $hasVoucher ? 'DepEd Voucher (QVR / ESC)' : 'None';
        $voucherType = $hasVoucher ? 'Private ESC / QVR' : null;
        $voucherDiscount = $hasVoucher ? 14000.00 : 0.00;

        $totalTuition = ($gradeCategory === 'SHS') ? 15000.00 : 12000.00;
        $totalMisc = 3500.00;
        $totalLab = ($strandCode === 'STEM' || $strandCode === 'TVL-ICT' || $strandCode === 'TVL-HE') ? 2500.00 : 1000.00;
        $totalOther = 1500.00;
        $grossAmount = $totalTuition + $totalMisc + $totalLab + $totalOther;
        $netPayable = max(0.00, $grossAmount - $voucherDiscount);

        $lastGradeCompleted = "Grade " . ((int)$section['sequence_order'] + 5);
        $lastSchool = ($i % 2 === 0) ? 'Biringan National High School' : 'Eastern Visayas Science Academy';

        // 1. Create User
        $stmtInsertUser->execute([
            'username'   => $username,
            'email'      => $email,
            'student_id' => $studentNo,
            'password'   => $passwordHash
        ]);
        $userId = (int)$db->lastInsertId();

        // 2. Create Profile
        $stmtInsertProfile->execute([
            'user_id'        => $userId,
            'first_name'     => $firstName,
            'middle_name'    => $middleName,
            'last_name'      => $lastName,
            'gender'         => $gender,
            'birthdate'      => $birthdate,
            'contact_number' => $contactNumber,
            'address'        => $fullAddress
        ]);

        // 3. Create Application
        $stmtInsertApplication->execute([
            'application_no'       => $appNo,
            'user_id'              => $userId,
            'school_year_id'       => $schoolYearId,
            'lrn'                  => $lrn,
            'first_name'           => $firstName,
            'middle_name'          => $middleName,
            'last_name'            => $lastName,
            'gender'               => $gender,
            'birthdate'            => $birthdate,
            'dob'                  => $birthdate,
            'birthplace'           => $loc['city'],
            'pob'                  => $loc['city'],
            'contact_number'       => $contactNumber,
            'phone'                => $contactNumber,
            'email'                => $email,
            'address_street'       => $street,
            'address_barangay'     => $brgy,
            'address_city'         => $loc['city'],
            'address_province'     => $loc['province'],
            'address_zip'          => $loc['zip'],
            'address'              => $fullAddress,
            'guardian_name'        => $guardianName,
            'guardian_relationship'=> 'Father',
            'guardian_contact'     => "09" . mt_rand(100000000, 999999999),
            'father_name'          => $fatherName,
            'father_contact'       => "09" . mt_rand(100000000, 999999999),
            'mother_name'          => $motherName,
            'mother_contact'       => "09" . mt_rand(100000000, 999999999),
            'last_school_attended' => $lastSchool,
            'last_grade_completed' => $lastGradeCompleted,
            'grade_level_id'       => $gradeLevelId,
            'track_id'             => $trackId,
            'strand_id'            => $strandId,
            'voucher_status'       => $voucherStatus,
            'voucher_type'         => $voucherType,
            'gwa'                  => round(mt_rand(880, 970) / 10, 2),
            'student_no'           => $studentNo,
            'assigned_section_id'  => $sectionId
        ]);
        $appId = (int)$db->lastInsertId();

        // 4. Create Enrollment
        $stmtInsertEnrollment->execute([
            'enrollment_no'  => $enrNo,
            'student_no'     => $studentNo,
            'student_id'     => $userId,
            'application_id' => $appId,
            'school_year_id' => $schoolYearId,
            'grade_level_id' => $gradeLevelId,
            'track_id'       => $trackId,
            'strand_id'      => $strandId,
            'section_id'     => $sectionId,
            'lrn'            => $lrn,
            'semester'       => $semester
        ]);
        $enrollmentId = (int)$db->lastInsertId();

        // 5. Enroll in Subjects
        foreach ($subjectIds as $subId) {
            $stmtInsertSubject->execute([
                'enrollment_id' => $enrollmentId,
                'subject_id'    => $subId
            ]);
        }

        // 6. Create Assessment
        $stmtInsertAssessment->execute([
            'enrollment_id'       => $enrollmentId,
            'school_year_id'      => $schoolYearId,
            'assessment_no'       => $assNo,
            'payment_ticket'      => $ticket,
            'total_tuition'       => $totalTuition,
            'total_miscellaneous' => $totalMisc,
            'total_misc'          => $totalMisc,
            'total_laboratory'    => $totalLab,
            'total_lab'           => $totalLab,
            'total_other_fees'    => $totalOther,
            'gross_amount'        => $grossAmount,
            'total_assessed'      => $grossAmount,
            'voucher_discount'    => $voucherDiscount,
            'net_payable'         => $netPayable,
            'total_paid'          => $netPayable,
            'amount_paid'         => $netPayable
        ]);

        // 7. Create Student Record
        $stmtInsertRecord->execute([
            'student_id'      => $userId,
            'lrn'             => $lrn,
            'school_year_id'  => $schoolYearId,
            'grade_level_id'  => $gradeLevelId,
            'strand_id'       => $strandId,
            'section_id'      => $sectionId,
            'general_average' => round(mt_rand(880, 960) / 10, 2)
        ]);

        $totalCreated++;
    }

    // Update section count
    $stmtUpdateSectionCount->execute([
        'sid'  => $sectionId,
        'sid2' => $sectionId
    ]);
}

echo "\n========================================================\n";
echo " SEEDING COMPLETE! Successfully created {$totalCreated} students!\n";
echo " Default Password for all student accounts: password123\n";
echo "========================================================\n";
