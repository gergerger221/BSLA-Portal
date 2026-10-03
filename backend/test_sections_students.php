<?php
require_once __DIR__ . '/config/Env.php';
\App\Config\Env::load();
require_once __DIR__ . '/config/Database.php';

use App\Config\Database;

$db = Database::getConnection();

echo "=======================================================\n";
echo "      SECTION-BY-SECTION STUDENT ROSTER AUDIT         \n";
echo "=======================================================\n\n";

$secs = $db->query("
    SELECT s.id, s.name, gl.name as grade_name,
           (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = s.id AND e.status IN ('Officially Enrolled', 'Enrolled')) as enrolled_count
    FROM sections s
    JOIN grade_levels gl ON s.grade_level_id = gl.id
    WHERE s.is_active = 1
    ORDER BY s.grade_level_id, s.id
")->fetchAll();

$allPass = true;
$totalStudentsTested = 0;
$authPassCount = 0;

foreach ($secs as $s) {
    $count = (int)$s['enrolled_count'];
    $status = ($count >= 10) ? '✅ OK' : '❌ SHORT';
    if ($count < 10) $allPass = false;
    echo sprintf("%s Section [%02d] %-25s (%-10s): %2d Students Enrolled\n", $status, $s['id'], $s['name'], $s['grade_name'], $count);

    // Fetch enrolled students
    $stRows = $db->prepare("
        SELECT u.id, u.username, u.password, r.slug as role_slug
        FROM enrollments e
        JOIN users u ON e.student_id = u.id
        JOIN roles r ON u.role_id = r.id
        WHERE e.section_id = :sec_id AND e.status IN ('Officially Enrolled', 'Enrolled')
    ");
    $stRows->execute(['sec_id' => $s['id']]);
    $studs = $stRows->fetchAll();

    foreach ($studs as $st) {
        $totalStudentsTested++;
        if (password_verify('password123', $st['password'])) {
            $authPassCount++;
        } else {
            echo "  ⚠️ Warning: User '{$st['username']}' password mismatch\n";
            $allPass = false;
        }
    }
}

echo "\n-------------------------------------------------------\n";
echo "Total Active Sections Tested: " . count($secs) . "\n";
echo "Total Enrolled Students Tested: " . $totalStudentsTested . "\n";
echo "Password ('password123') Verified: " . $authPassCount . " / " . $totalStudentsTested . "\n";
echo "-------------------------------------------------------\n";
echo "Final Verdict: " . ($allPass ? "✅ PERFECT (100% OF ALL 33 SECTIONS HAVE >=10 ENROLLED STUDENTS WITH PASSWORD123)" : "❌ FAILED") . "\n";
