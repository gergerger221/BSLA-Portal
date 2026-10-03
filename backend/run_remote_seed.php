<?php
// backend/run_remote_seed.php
declare(strict_types=1);

require_once __DIR__ . '/config/Database.php';

use App\Config\Database;

try {
    $db = Database::getConnection();

    $sqlFile = __DIR__ . '/insert_ten_students.sql';
    if (!file_exists($sqlFile)) {
        echo json_encode(['success' => false, 'message' => 'insert_ten_students.sql not found']);
        exit;
    }

    $sql = file_get_contents($sqlFile);
    $statements = array_filter(array_map('trim', explode(";\n", $sql)));

    $executed = 0;
    $errors = [];

    foreach ($statements as $stmt) {
        if (empty($stmt) || strpos($stmt, '--') === 0) continue;
        try {
            $db->exec($stmt);
            $executed++;
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Executed $executed SQL statements for 10 student accounts on live database.",
        'errors'  => $errors
    ]);
} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
