<?php
// Update account details for Frnklynrd Rosales
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=sia_highschool_db", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$email = 'mrconsonants@gmail.com';
$firstName = 'Frnklynrd';
$lastName = 'Rosales';

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$userId = $stmt->fetchColumn();

if ($userId) {
    // Update user_profiles
    $pdo->prepare("
        UPDATE user_profiles 
        SET first_name = :fn, last_name = :ln
        WHERE user_id = :uid
    ")->execute([
        'fn' => $firstName,
        'ln' => $lastName,
        'uid' => $userId
    ]);

    // Update admission_applications
    $pdo->prepare("
        UPDATE admission_applications 
        SET first_name = :fn, last_name = :ln
        WHERE user_id = :uid
    ")->execute([
        'fn' => $firstName,
        'ln' => $lastName,
        'uid' => $userId
    ]);

    echo "Successfully updated name to: {$firstName} {$lastName} for account {$email}!\n";
} else {
    echo "User not found\n";
}
