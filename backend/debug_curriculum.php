<?php
require_once __DIR__ . '/config/Env.php';
\App\Config\Env::load();
require_once __DIR__ . '/config/Database.php';

use App\Config\Database;

$db = Database::getConnection();

echo "=== SCHOOL YEARS ===\n";
print_r($db->query("SELECT * FROM school_years")->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== SUBJECTS SUMMARY ===\n";
print_r($db->query("SELECT grade_level_id, category, school_year_id, COUNT(*) as count FROM subjects GROUP BY grade_level_id, category, school_year_id")->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== TRACKS & STRANDS ===\n";
print_r($db->query("SELECT * FROM tracks")->fetchAll(PDO::FETCH_ASSOC));
print_r($db->query("SELECT * FROM strands")->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== SECTIONS SUMMARY ===\n";
print_r($db->query("SELECT grade_level_id, strand_id, COUNT(*) as count, SUM(current_enrolled) as total_enrolled FROM sections GROUP BY grade_level_id, strand_id")->fetchAll(PDO::FETCH_ASSOC));
