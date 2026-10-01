<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=sia_highschool_db", 'root', '');
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

echo "=== TABLES IN sia_highschool_db (" . count($tables) . " tables) ===\n";
foreach ($tables as $t) {
    echo "\nTABLE: $t\n";
    $cols = $pdo->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  - {$c['Field']} ({$c['Type']}) " . ($c['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . ($c['Key'] ? " [{$c['Key']}]" : '') . ($c['Default'] !== null ? " DEFAULT '{$c['Default']}'" : '') . "\n";
    }
}
