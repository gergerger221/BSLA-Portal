<?php
// backend/database/reassign_section_advisers.php
require_once __DIR__ . '/../config/Database.php';

use App\Config\Database;

try {
    $db = Database::getConnection();
    echo "Starting section adviser 1-to-1 normalization...\n";

    // Fetch all active sections ordered by grade level sequence and name
    $sections = $db->query("
        SELECT s.id, s.name, s.grade_level_id 
        FROM sections s 
        JOIN grade_levels gl ON s.grade_level_id = gl.id 
        WHERE s.is_active = 1 
        ORDER BY gl.sequence_order, s.name
    ")->fetchAll();

    // Fetch all teacher accounts
    $teachers = $db->query("
        SELECT u.id, u.username, p.first_name, p.last_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        LEFT JOIN user_profiles p ON u.id = p.user_id 
        WHERE r.slug = 'teacher' AND u.status = 'Active' 
        ORDER BY u.id ASC
    ")->fetchAll();

    echo "Found " . count($sections) . " sections and " . count($teachers) . " teacher accounts.\n";

    if (count($teachers) < count($sections)) {
        throw new Exception("Not enough teachers (" . count($teachers) . ") for all sections (" . count($sections) . ").");
    }

    // Ensure prof_delacruz (ID 6) is assigned to Section 8: Grade 10 - Pearl
    // or the primary demo class
    $assignedTeacherIds = [];
    $delacruzId = 6;
    $pearlSectionId = 8; // Grade 10 - Pearl

    // Set Pearl to prof_delacruz first
    $updateStmt = $db->prepare("UPDATE sections SET adviser_id = :adviser_id WHERE id = :section_id");
    $updateStmt->execute([
        'adviser_id' => $delacruzId,
        'section_id' => $pearlSectionId
    ]);
    $assignedTeacherIds[] = $delacruzId;

    // Remaining teachers excluding prof_delacruz
    $remainingTeachers = array_values(array_filter($teachers, function($t) use ($delacruzId) {
        return (int)$t['id'] !== (int)$delacruzId;
    }));

    $tIndex = 0;
    foreach ($sections as $sec) {
        if ((int)$sec['id'] === (int)$pearlSectionId) {
            echo "Assigned [{$sec['name']}] (ID: {$sec['id']}) -> prof_delacruz (ID: {$delacruzId}) [PRIMARY HOMEROOM]\n";
            continue;
        }

        $assignedTeacher = $remainingTeachers[$tIndex];
        $updateStmt->execute([
            'adviser_id' => $assignedTeacher['id'],
            'section_id' => $sec['id']
        ]);
        echo "Assigned [{$sec['name']}] (ID: {$sec['id']}) -> {$assignedTeacher['username']} (ID: {$assignedTeacher['id']})\n";
        $tIndex++;
    }

    // Verify 1-to-1 constraint
    $check = $db->query("
        SELECT adviser_id, COUNT(*) as cnt 
        FROM sections 
        WHERE is_active = 1 
        GROUP BY adviser_id 
        HAVING cnt > 1
    ")->fetchAll();

    if (empty($check)) {
        echo "\nSUCCESS: Every active section is now assigned to exactly ONE unique faculty adviser (1-to-1 DepEd standard verified)!\n";
    } else {
        echo "\nWARNING: Duplicate adviser detected: " . print_r($check, true) . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
