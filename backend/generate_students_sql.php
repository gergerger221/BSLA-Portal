<?php
require_once __DIR__ . '/config/Env.php';
\App\Config\Env::load();
require_once __DIR__ . '/config/Database.php';

use App\Config\Database;

$db = Database::getConnection();

$usernames = [
    'student.santos', 'student.reyes', 'student.cruz', 'student.bautista', 'student.garcia',
    'student.mendoza', 'student.torres', 'student.aquino', 'student.navarro', 'student.delacruz'
];

$inClause = "'" . implode("','", $usernames) . "'";

// Fetch users
$users = $db->query("SELECT * FROM users WHERE username IN ($inClause)")->fetchAll();
$userIds = array_column($users, 'id');
$uIdsClause = implode(',', $userIds);

// Fetch user_profiles
$profiles = $db->query("SELECT * FROM user_profiles WHERE user_id IN ($uIdsClause)")->fetchAll();

// Fetch enrollments
$enrollments = $db->query("SELECT * FROM enrollments WHERE student_id IN ($uIdsClause)")->fetchAll();
$enrIds = array_column($enrollments, 'id');
$enrIdsClause = implode(',', $enrIds);

// Fetch enrollment_subjects
$enrollmentSubjects = $db->query("SELECT * FROM enrollment_subjects WHERE enrollment_id IN ($enrIdsClause)")->fetchAll();

// Fetch student_assessments
$assessments = $db->query("SELECT * FROM student_assessments WHERE enrollment_id IN ($enrIdsClause)")->fetchAll();

$sql = "-- =========================================================\n";
$sql .= "-- SEED 10 STUDENTS FOR ONLINE PRODUCTION HOSTING\n";
$sql .= "-- Password for all: password123\n";
$sql .= "-- =========================================================\n\n";

// Users
$sql .= "-- Users\n";
foreach ($users as $u) {
    $escPass = addslashes($u['password']);
    $sql .= "INSERT INTO `users` (`role_id`, `username`, `email`, `student_id`, `password`, `status`, `created_at`)\n";
    $sql .= "VALUES ({$u['role_id']}, '{$u['username']}', '{$u['email']}', '{$u['student_id']}', '{$escPass}', 'Active', NOW())\n";
    $sql .= "ON DUPLICATE KEY UPDATE `password` = '{$escPass}', `status` = 'Active';\n\n";
}

// User Profiles
$sql .= "-- User Profiles\n";
foreach ($profiles as $p) {
    $fn = addslashes($p['first_name']);
    $ln = addslashes($p['last_name']);
    $mn = addslashes($p['middle_name']);
    $gen = addslashes($p['gender']);
    $sql .= "INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `middle_name`, `gender`, `contact_number`, `address`)\n";
    $sql .= "SELECT u.id, '{$fn}', '{$ln}', '{$mn}', '{$gen}', '09171234567', 'Biringan City' FROM users u WHERE u.username = '{$users[array_search($p['user_id'], $userIds)]['username']}'\n";
    $sql .= "ON DUPLICATE KEY UPDATE `first_name` = '{$fn}', `last_name` = '{$ln}';\n\n";
}

// Enrollments
$sql .= "-- Enrollments\n";
foreach ($enrollments as $e) {
    $strId = $e['strand_id'] ? $e['strand_id'] : 'NULL';
    $sql .= "INSERT INTO `enrollments` (`enrollment_no`, `student_no`, `student_id`, `school_year_id`, `grade_level_id`, `strand_id`, `section_id`, `lrn`, `status`, `semester`, `created_at`, `enrolled_at`)\n";
    $uName = $users[array_search($e['student_id'], $userIds)]['username'];
    $sql .= "SELECT '{$e['enrollment_no']}', '{$e['student_no']}', u.id, {$e['school_year_id']}, {$e['grade_level_id']}, {$strId}, {$e['section_id']}, '{$e['lrn']}', 'Officially Enrolled', '1st Semester', NOW(), NOW()\n";
    $sql .= "FROM users u WHERE u.username = '{$uName}' AND NOT EXISTS (SELECT 1 FROM enrollments WHERE student_no = '{$e['student_no']}');\n\n";
}

// Enrollment Subjects
$sql .= "-- Enrollment Subjects\n";
foreach ($enrollmentSubjects as $es) {
    $eObj = $enrollments[array_search($es['enrollment_id'], $enrIds)];
    $sql .= "INSERT IGNORE INTO `enrollment_subjects` (`enrollment_id`, `subject_id`, `status`)\n";
    $sql .= "SELECT e.id, {$es['subject_id']}, 'Enrolled' FROM enrollments e WHERE e.student_no = '{$eObj['student_no']}';\n";
}

// Student Assessments
$sql .= "\n-- Student Assessments\n";
foreach ($assessments as $a) {
    $eObj = $enrollments[array_search($a['enrollment_id'], $enrIds)];
    $sql .= "INSERT INTO `student_assessments` (`enrollment_id`, `school_year_id`, `assessment_no`, `total_tuition`, `total_miscellaneous`, `gross_amount`, `voucher_discount`, `net_payable`, `total_paid`, `remaining_balance`, `status`, `created_at`)\n";
    $sql .= "SELECT e.id, 1, '{$a['assessment_no']}', 15000.00, 3500.00, 18500.00, 3500.00, 15000.00, 15000.00, 0.00, 'Paid', NOW()\n";
    $sql .= "FROM enrollments e WHERE e.student_no = '{$eObj['student_no']}' AND NOT EXISTS (SELECT 1 FROM student_assessments sa WHERE sa.enrollment_id = e.id);\n\n";
}

file_put_contents(__DIR__ . '/insert_ten_students.sql', $sql);
echo "Generated backend/insert_ten_students.sql successfully (" . strlen($sql) . " bytes).\n";
