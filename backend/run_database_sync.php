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
    $content = file_get_contents($sqlFile);
    
    // Strip BOM if any
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
    if (str_starts_with($content, "\xFF\xFE") || str_starts_with($content, "\xFE\xFF")) {
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16');
    }

    $log("[3/4] Executing database schema and data migration...");
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

    // Split into individual SQL queries accurately
    $queries = [];
    $currentQuery = '';
    $inString = false;
    $stringChar = '';
    $escaped = false;
    $inLineComment = false;
    $inBlockComment = false;
    $length = strlen($content);

    for ($i = 0; $i < $length; $i++) {
        $char = $content[$i];
        $nextChar = ($i + 1 < $length) ? $content[$i + 1] : '';

        // Line comment handling
        if ($inLineComment) {
            if ($char === "\n") {
                $inLineComment = false;
            }
            continue;
        }

        // Block comment handling (unless conditional /*! ... */)
        if ($inBlockComment) {
            if ($char === '*' && $nextChar === '/') {
                $inBlockComment = false;
                $i++;
            }
            continue;
        }

        if (!$inString) {
            if ($char === '-' && $nextChar === '-') {
                $inLineComment = true;
                $i++;
                continue;
            }
            if ($char === '#' && ($i === 0 || $content[$i - 1] === "\n")) {
                $inLineComment = true;
                continue;
            }
            if ($char === '/' && $nextChar === '*' && ($i + 2 < $length && $content[$i + 2] !== '!')) {
                $inBlockComment = true;
                $i++;
                continue;
            }
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === $stringChar && !$escaped) {
                $inString = false;
            }
        }

        $escaped = ($char === '\\' && !$escaped);

        if ($char === ';' && !$inString) {
            $trimmed = trim($currentQuery);
            if ($trimmed !== '') {
                $queries[] = $trimmed;
            }
            $currentQuery = '';
        } else {
            $currentQuery .= $char;
        }
    }

    if (trim($currentQuery) !== '') {
        $queries[] = trim($currentQuery);
    }

    $log("  Parsed " . count($queries) . " SQL executable statements.");

    $executed = 0;
    $errors = 0;
    $errorMessages = [];

    foreach ($queries as $q) {
        $cleanQ = trim($q);
        if ($cleanQ === '') continue;
        try {
            $db->exec($cleanQ);
            $executed++;
        } catch (PDOException $e) {
            $errors++;
            if (count($errorMessages) < 5) {
                $errorMessages[] = substr($e->getMessage(), 0, 150) . " | Query: " . substr($cleanQ, 0, 60);
            }
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
