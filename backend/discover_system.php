<?php
require_once __DIR__ . '/config/Database.php';
$db = App\Config\Database::getConnection();

echo "=== ROLES IN DATABASE ===\n";
$roles = $db->query("SELECT id, name, slug, description FROM roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
print_r($roles);

echo "\n=== USERS IN DATABASE ===\n";
$users = $db->query("SELECT u.id, u.username, u.email, u.status, u.student_id, r.slug as role_slug, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id ASC")->fetchAll(PDO::FETCH_ASSOC);
print_r($users);

echo "\n=== GRADE LEVELS ===\n";
$gl = $db->query("SELECT * FROM grade_levels ORDER BY sequence_order ASC")->fetchAll(PDO::FETCH_ASSOC);
print_r($gl);

echo "\n=== TRACKS & STRANDS ===\n";
$strands = $db->query("SELECT s.id, s.code, s.name, s.status, t.name as track_name FROM strands s JOIN tracks t ON s.track_id = t.id")->fetchAll(PDO::FETCH_ASSOC);
print_r($strands);

echo "\n=== ACTIVE SCHOOL YEAR ===\n";
$sy = $db->query("SELECT * FROM school_years")->fetchAll(PDO::FETCH_ASSOC);
print_r($sy);
