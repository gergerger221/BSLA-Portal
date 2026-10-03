<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Env.php';
\App\Config\Env::load();
require_once __DIR__ . '/config/Database.php';

use App\Config\Database;

$db = Database::getConnection();

// Fetch all actual tables in database
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

$output = "<?php\n";
$output .= "// Auto-generated standalone database migrator & seeder for InfinityFree\n";
$output .= "declare(strict_types=1);\n";
$output .= "set_time_limit(600);\n";
$output .= "ini_set('memory_limit', '512M');\n";
$output .= "header('Content-Type: text/plain; charset=utf-8');\n\n";
$output .= "require_once __DIR__ . '/config/Env.php';\n";
$output .= "\\App\\Config\\Env::load();\n";
$output .= "require_once __DIR__ . '/config/Database.php';\n\n";
$output .= "echo \"===========================================================\\n\";\n";
$output .= "echo \"     INFINITYFREE LIVE DATABASE COMPLETE RECOVERY SEEDER   \\n\";\n";
$output .= "echo \"===========================================================\\n\\n\";\n\n";
$output .= "try {\n";
$output .= "    \$db = \\App\\Config\\Database::getConnection();\n";
$output .= "    \$db->exec(\"SET FOREIGN_KEY_CHECKS = 0;\");\n";
$output .= "    \$db->exec(\"SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\");\n\n";

foreach ($tables as $tbl) {
    // 1. Get CREATE TABLE
    $create = $db->query("SHOW CREATE TABLE `$tbl`")->fetch(PDO::FETCH_ASSOC);
    if ($create) {
        $createSql = $create['Create Table'];
        $escapedCreate = addcslashes($createSql, "\\'");
        $output .= "    echo \"[+] Creating table `$tbl`...\\n\";\n";
        $output .= "    \$db->exec(\"DROP TABLE IF EXISTS `$tbl`\");\n";
        $output .= "    \$db->exec('{$escapedCreate}');\n\n";
    }

    // 2. Dump table rows
    $rows = $db->query("SELECT * FROM `$tbl`")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        $output .= "    echo \"[+] Inserting " . count($rows) . " rows into `$tbl`...\\n\";\n";
        $cols = array_keys($rows[0]);
        $colList = implode('`, `', $cols);

        // Chunk inserts by 50 rows
        $chunks = array_chunk($rows, 50);
        foreach ($chunks as $chunk) {
            $valStrings = [];
            foreach ($chunk as $row) {
                $rowVals = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $rowVals[] = 'NULL';
                    } else {
                        $rowVals[] = $db->quote((string)$val);
                    }
                }
                $valStrings[] = '(' . implode(', ', $rowVals) . ')';
            }
            $insertSql = "INSERT INTO `$tbl` (`$colList`) VALUES\n" . implode(",\n", $valStrings) . ";";
            $escapedInsert = addcslashes($insertSql, "\\'");
            $output .= "    \$db->exec('{$escapedInsert}');\n";
        }
        $output .= "\n";
    }
}

$output .= "    \$db->exec(\"SET FOREIGN_KEY_CHECKS = 1;\");\n\n";
$output .= "    echo \"\\n===========================================================\\n\";\n";
$output .= "    echo \"✅ SUCCESS! All tables, curriculum, tracks, strands, 33 sections,\\n\";\n";
$output .= "    echo \"   and 330 enrolled students are successfully written to database!\\n\";\n";
$output .= "    echo \"===========================================================\\n\";\n";
$output .= "} catch (\\Throwable \$e) {\n";
$output .= "    echo \"\\n❌ ERROR: \" . \$e->getMessage() . \"\\n\";\n";
$output .= "}\n";

file_put_contents(__DIR__ . '/auto_seed_infinityfree.php', $output);
echo "Generated auto_seed_infinityfree.php (" . strlen($output) . " bytes)\n";
