<?php
require_once __DIR__ . '/config/Env.php';
\App\Config\Env::load();
require_once __DIR__ . '/config/Database.php';

use App\Config\Database;

$db = Database::getConnection();

$hash = password_hash('password123', PASSWORD_BCRYPT);

$roleId = $db->query("SELECT id FROM roles WHERE slug = 'student' OR name = 'student'")->fetchColumn();

$stmt = $db->prepare("UPDATE users SET password = :pwd, status = 'Active' WHERE role_id = :rid");
$stmt->execute(['pwd' => $hash, 'rid' => $roleId]);
$count = $stmt->rowCount();

echo "Updated $count student accounts to have password: password123\n";
