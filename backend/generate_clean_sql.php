<?php
declare(strict_types=1);

// Run mysqldump directly to file to ensure pure UTF-8 without PowerShell UTF-16 encoding
$dumpCmd = 'cmd.exe /c "C:\\xampp\\mysql\\bin\\mysqldump.exe --default-character-set=utf8mb4 -u root sia_highschool_db > sia_highschool_complete_database.sql"';
exec($dumpCmd, $output, $returnVar);

$sqlPath = __DIR__ . '/../sia_highschool_complete_database.sql';
if (!file_exists($sqlPath)) {
    die("Error: $sqlPath not found\n");
}

$raw = file_get_contents($sqlPath);

// Detect and convert UTF-16 if present
if (str_starts_with($raw, "\xFF\xFE") || str_starts_with($raw, "\xFE\xFF")) {
    $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-16');
}
// Strip UTF-8 BOM if present
$raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

// Remove CREATE DATABASE and USE statements
$cleanSql = preg_replace('/CREATE DATABASE.*?;/is', '', $raw);
$cleanSql = preg_replace('/USE `.*?`;/is', '', $cleanSql);
// Also remove any dangling comments
$cleanSql = preg_replace('/-- Current Database:.*?\n/is', '', $cleanSql);

$outPath = __DIR__ . '/clean_infinityfree_database.sql';
file_put_contents($outPath, $cleanSql);

echo "Successfully generated pure UTF-8 clean SQL:\n";
echo "  File: " . realpath($outPath) . "\n";
echo "  Size: " . strlen($cleanSql) . " bytes\n";
echo "  Lines: " . count(explode("\n", $cleanSql)) . "\n";

// Verify first 20 lines
$lines = explode("\n", $cleanSql);
echo "\nFirst 15 lines:\n";
for ($i = 0; $i < min(15, count($lines)); $i++) {
    echo ($i + 1) . ": " . $lines[$i] . "\n";
}
