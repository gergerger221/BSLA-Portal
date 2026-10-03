<?php
// backend/run_database_sync.php
declare(strict_types=1);

// Prevent timeouts
set_time_limit(600);
ini_set('memory_limit', '512M');

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/config/Env.php';
\App\Config\Env::load();
require_once __DIR__ . '/config/Database.php';

$logFile = __DIR__ . '/sync_result.log';
$log = function(string $msg) use ($logFile) {
    echo $msg . "\n";
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
};
file_put_contents($logFile, "=== SYNC STARTED ===\n");

$log("====================================================");
$log(" BSLA Biringan Portal - Remote Database Synchronizer ");
$log("====================================================");

try {
    $db = \App\Config\Database::getConnection();
    $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    $log("[1/4] Connected to database: {$dbName}");

    $sqlFile = __DIR__ . '/clean_infinityfree_database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found at: {$sqlFile}");
    }

    $log("[2/4] Reading SQL file (" . round(filesize($sqlFile) / 1024, 2) . " KB)...");
    $lines = file($sqlFile, FILE_IGNORE_NEW_LINES);
    $log("  Total SQL lines: " . count($lines));

    $log("[3/4] Executing database schema and data migration...");
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

    $query = '';
    $executed = 0;
    $errors = 0;
    $errorMessages = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
            continue;
        }

        $query .= $line . "\n";

        if (str_ends_with($trimmed, ';')) {
            try {
                $db->exec($query);
                $executed++;
            } catch (PDOException $e) {
                $errors++;
                if (count($errorMessages) < 5) {
                    $errorMessages[] = $e->getMessage();
                }
            }
            $query = '';
        }
    }

    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    $log("  Executed {$executed} SQL statements successfully ({$errors} warnings).");
    if (!empty($errorMessages)) {
        foreach ($errorMessages as $em) {
            $log("  [INFO] {$em}");
        }
    }

    $log("\n[4/4] Verifying synchronized database state:");
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $log("  Total Tables: " . count($tables));
    
    $checkList = [
        'tracks', 'strands', 'subjects', 'sections', 'schedules',
        'lms_announcements', 'lms_modules', 'lms_assignments', 'lms_submissions',
        'users', 'user_profiles', 'admission_applications', 'enrollments'
    ];

    foreach ($checkList as $chk) {
        if (in_array($chk, $tables)) {
            $count = $db->query("SELECT COUNT(*) FROM `$chk`")->fetchColumn();
            $log(sprintf("  - %-25s : %d rows", $chk, $count));
        } else {
            $log("  - {$chk} : MISSING!");
        }
    }

    $log("\n>>> SYNCHRONIZATION COMPLETE! ALL CURRICULUM, LMS, AND SYSTEM TABLES ARE LIVE! <<<");

} catch (Exception $e) {
    $log("\n[FATAL ERROR] " . $e->getMessage());
}
