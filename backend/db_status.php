<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/Env.php';
\App\Config\Env::load();
require_once __DIR__ . '/config/Database.php';

try {
    $db = \App\Config\Database::getConnection();
    $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $counts = [];
    foreach ($tables as $t) {
        $counts[$t] = (int)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    }
    echo json_encode([
        'status' => 'connected',
        'database' => $dbName,
        'total_tables' => count($tables),
        'counts' => $counts
    ], JSON_PRETTY_PRINT);
} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
}
