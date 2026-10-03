<?php
// backend/inspect_remote.php
header('Content-Type: text/plain');

require_once __DIR__ . '/config/Env.php';
\App\Config\Env::load();
require_once __DIR__ . '/config/Database.php';

try {
    $db = \App\Config\Database::getConnection();
    echo "CONNECTED TO DATABASE: " . $db->query("SELECT DATABASE()")->fetchColumn() . "\n\n";

    echo "--- ALL TABLES & ROW COUNTS ---\n";
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo sprintf("%-35s : %d rows\n", $t, $count);
    }

    echo "\n--- CHECKING CURRICULUM (tracks, strands, subjects) ---\n";
    if (in_array('tracks', $tables)) {
        echo "Tracks: " . $db->query("SELECT COUNT(*) FROM tracks")->fetchColumn() . "\n";
    } else {
        echo "MISSING TABLE: tracks\n";
    }
    if (in_array('strands', $tables)) {
        echo "Strands: " . $db->query("SELECT COUNT(*) FROM strands")->fetchColumn() . "\n";
    } else {
        echo "MISSING TABLE: strands\n";
    }
    if (in_array('subjects', $tables)) {
        echo "Subjects: " . $db->query("SELECT COUNT(*) FROM subjects")->fetchColumn() . "\n";
    } else {
        echo "MISSING TABLE: subjects\n";
    }

    echo "\n--- CHECKING LMS TABLES ---\n";
    $lmsTables = ['lms_announcements', 'lms_modules', 'lms_assignments', 'lms_assignment_submissions'];
    foreach ($lmsTables as $lt) {
        if (in_array($lt, $tables)) {
            echo "$lt: " . $db->query("SELECT COUNT(*) FROM `$lt`")->fetchColumn() . " rows\n";
        } else {
            echo "MISSING TABLE: $lt\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
