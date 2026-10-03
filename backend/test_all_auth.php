<?php
require_once __DIR__ . '/config/Env.php';
\App\Config\Env::load();
require_once __DIR__ . '/config/Database.php';

use App\Config\Database;

$db = Database::getConnection();

echo "=======================================================\n";
echo "       BSLA PORTAL AUTHENTICATION & ROLE TEST         \n";
echo "=======================================================\n\n";

$testAccounts = [
    // Staff & Faculty
    ['role' => 'Admin',       'username' => 'admin',             'password' => 'password123'],
    ['role' => 'Coordinator', 'username' => 'maria_coordinator', 'password' => 'password123'],
    ['role' => 'Registrar',   'username' => 'maria_registrar',   'password' => 'password123'],
    ['role' => 'Treasury',    'username' => 'maria_treasury',    'password' => 'password123'],
    ['role' => 'Records',     'username' => 'maria_records',     'password' => 'password123'],
    ['role' => 'Teacher',     'username' => 'prof_lovelace',     'password' => 'password123'],
    ['role' => 'Teacher',     'username' => 'prof_delacruz',     'password' => 'password123'],
    ['role' => 'Teacher',     'username' => 'prof_santos',       'password' => 'password123'],

    // 10 New Students
    ['role' => 'Student G7',  'username' => 'student.santos',    'password' => 'password123'],
    ['role' => 'Student G7',  'username' => 'student.reyes',     'password' => 'password123'],
    ['role' => 'Student G8',  'username' => 'student.cruz',      'password' => 'password123'],
    ['role' => 'Student G8',  'username' => 'student.bautista',  'password' => 'password123'],
    ['role' => 'Student G9',  'username' => 'student.garcia',    'password' => 'password123'],
    ['role' => 'Student G9',  'username' => 'student.mendoza',   'password' => 'password123'],
    ['role' => 'Student G10', 'username' => 'student.torres',    'password' => 'password123'],
    ['role' => 'Student G10', 'username' => 'student.aquino',    'password' => 'password123'],
    ['role' => 'Student G11', 'username' => 'student.navarro',   'password' => 'password123'],
    ['role' => 'Student G12', 'username' => 'student.delacruz',  'password' => 'password123'],
];

$passCount = 0;
$failCount = 0;

foreach ($testAccounts as $acc) {
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.password, u.status, r.name as role_name, r.slug as role_slug
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.username = :un1 OR u.email = :un2
        LIMIT 1
    ");
    $stmt->execute(['un1' => $acc['username'], 'un2' => $acc['username']]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "❌ [FAIL] User '{$acc['username']}' not found in database.\n";
        $failCount++;
        continue;
    }

    if (!password_verify($acc['password'], $user['password'])) {
        echo "❌ [FAIL] User '{$acc['username']}' password mismatch.\n";
        $failCount++;
        continue;
    }

    if ($user['status'] !== 'Active') {
        echo "❌ [FAIL] User '{$acc['username']}' account is inactive ({$user['status']}).\n";
        $failCount++;
        continue;
    }

    // Check Enrollment for students
    $extra = '';
    if ($user['role_slug'] === 'student') {
        $enr = $db->prepare("
            SELECT e.enrollment_no, sec.name as section_name, gl.name as grade_name
            FROM enrollments e
            LEFT JOIN sections sec ON e.section_id = sec.id
            LEFT JOIN grade_levels gl ON e.grade_level_id = gl.id
            WHERE e.student_id = :uid
            LIMIT 1
        ");
        $enr->execute(['uid' => $user['id']]);
        $eData = $enr->fetch();
        if ($eData) {
            $extra = " | {$eData['grade_name']} ({$eData['section_name']}) | Enr: {$eData['enrollment_no']}";
        }
    }

    echo "✅ [PASS] {$acc['role']} -> Username: '{$user['username']}' | Pass: '{$acc['password']}'{$extra}\n";
    $passCount++;
}

echo "\n-------------------------------------------------------\n";
echo "Total Passed: $passCount / " . count($testAccounts) . "\n";
echo "Total Failed: $failCount / " . count($testAccounts) . "\n";
echo "-------------------------------------------------------\n";
