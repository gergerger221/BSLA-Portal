<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=sia_highschool_db", 'root', '');
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

echo "TABLES LIST:\n" . implode(', ', $tables) . "\n\n";

foreach (['admission_applications', 'admission_documents', 'announcements', 'audit_logs', 'curriculum_versions', 'enrollment_queue', 'enrollments'] as $t) {
    if (in_array($t, $tables)) {
        echo "TABLE: $t\n";
        $cols = $pdo->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo "  - {$c['Field']} ({$c['Type']}) " . ($c['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . ($c['Key'] ? " [{$c['Key']}]" : '') . "\n";
        }
        echo "\n";
    }
}
