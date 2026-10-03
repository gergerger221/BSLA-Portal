<?php
require_once __DIR__ . '/config/Env.php';
\App\Config\Env::load();
require_once __DIR__ . '/config/Database.php';

$db = \App\Config\Database::getConnection();

echo "Testing LMS queries with section_id = 6 (Grade 10 - Pearl) and subject_id = 18...\n";

// 1. Verify Class Info
$sectionId = 6;
$subjectId = 18;

try {
    $classStmt = $db->prepare("
        SELECT sec.id as section_id, sec.name as section_name, sec.room as section_room,
               gl.name as grade_level_name, gl.code as grade_level_code,
               sub.id as subject_id, sub.code as subject_code, sub.name as subject_name, sub.units,
               u.id as teacher_id, u.username as teacher_username,
               p.first_name as teacher_first_name, p.last_name as teacher_last_name
        FROM schedules s
        JOIN sections sec ON s.section_id = sec.id
        JOIN grade_levels gl ON sec.grade_level_id = gl.id
        JOIN subjects sub ON s.subject_id = sub.id
        LEFT JOIN users u ON s.teacher_id = u.id
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE s.section_id = :sec_id AND s.subject_id = :sub_id AND s.is_active = 1
        LIMIT 1
    ");
    $classStmt->execute(['sec_id' => $sectionId, 'sub_id' => $subjectId]);
    $classInfo = $classStmt->fetch();
    echo "Class Info: " . json_encode($classInfo) . "\n";
} catch (Exception $e) {
    echo "ERROR in Class Info query: " . $e->getMessage() . "\n";
}

// 2. Fetch Announcements
try {
    $annStmt = $db->prepare("
        SELECT a.*, u.username as author_username,
               p.first_name as author_first_name, p.last_name as author_last_name
        FROM lms_announcements a
        LEFT JOIN users u ON a.teacher_id = u.id
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE a.section_id = :sec_id AND a.subject_id = :sub_id
        ORDER BY a.is_pinned DESC, a.created_at DESC
    ");
    $annStmt->execute(['sec_id' => $sectionId, 'sub_id' => $subjectId]);
    $announcements = $annStmt->fetchAll();
    echo "Announcements: " . count($announcements) . " items\n";
} catch (Exception $e) {
    echo "ERROR in Announcements query: " . $e->getMessage() . "\n";
}

// 3. Fetch Learning Modules
try {
    $modStmt = $db->prepare("
        SELECT m.*, u.username as uploader_username,
               p.first_name as uploader_first_name, p.last_name as uploader_last_name
        FROM lms_modules m
        LEFT JOIN users u ON m.teacher_id = u.id
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE m.section_id = :sec_id AND m.subject_id = :sub_id
        ORDER BY FIELD(m.quarter, '1st Quarter', '2nd Quarter', '3rd Quarter', '4th Quarter'), m.week_label ASC, m.id DESC
    ");
    $modStmt->execute(['sec_id' => $sectionId, 'sub_id' => $subjectId]);
    $modules = $modStmt->fetchAll();
    echo "Modules: " . count($modules) . " items\n";
} catch (Exception $e) {
    echo "ERROR in Modules query: " . $e->getMessage() . "\n";
}

// 4. Fetch Assignments
try {
    $asgStmt = $db->prepare("
        SELECT asg.*,
               (SELECT COUNT(*) FROM lms_submissions sub WHERE sub.assignment_id = asg.id) as total_submissions,
               (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = asg.section_id AND e.status IN ('Officially Enrolled', 'Enrolled')) as total_learners
        FROM lms_assignments asg
        WHERE asg.section_id = :sec_id AND asg.subject_id = :sub_id
        ORDER BY (CASE WHEN asg.status = 'draft' THEN 0 ELSE 1 END) ASC, asg.due_date DESC
    ");
    $asgStmt->execute(['sec_id' => $sectionId, 'sub_id' => $subjectId]);
    $assignments = $asgStmt->fetchAll();
    echo "Assignments: " . count($assignments) . " items\n";
} catch (Exception $e) {
    echo "ERROR in Assignments query: " . $e->getMessage() . "\n";
}
