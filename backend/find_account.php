<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
$dbs = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);

echo "Databases found: " . implode(', ', $dbs) . "\n\n";

foreach ($dbs as $db) {
    if (in_array($db, ['information_schema', 'performance_schema', 'mysql', 'sys'])) continue;
    try {
        $pdo->exec("USE `$db`");
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $t) {
            if (in_array($t, ['users', 'user', 'accounts', 'admission_applications', 'students', 'student_accounts', 'tbl_users'])) {
                echo "--- Database: {$db}, Table: {$t} ---\n";
                $rows = $pdo->query("SELECT * FROM `$t` LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    echo json_encode($r) . "\n";
                }
                echo "\n";
            }
        }
    } catch (Exception $e) {
        echo "Error in {$db}: " . $e->getMessage() . "\n";
    }
}
