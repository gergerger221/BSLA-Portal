<?php
// Script to create mrconsonants@gmail.com account
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=sia_highschool_db", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$email = 'mrconsonants@gmail.com';
$password = 'password123';
$hashed = password_hash($password, PASSWORD_BCRYPT);
$username = 'mrconsonants';

// Check if exists
$chk = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$chk->execute(['email' => $email]);
$existing = $chk->fetch();

if (!$existing) {
    $pdo->beginTransaction();
    $pdo->prepare("
        INSERT INTO users (role_id, username, email, password, status)
        VALUES (8, :uname, :email, :pass, 'Active')
    ")->execute([
        'uname' => $username,
        'email' => $email,
        'pass'  => $hashed
    ]);
    $userId = (int)$pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO user_profiles (user_id, first_name, last_name, contact_number)
        VALUES (:uid, 'Mr', 'Consonants', '09171234567')
    ")->execute(['uid' => $userId]);

    $pdo->prepare("
        INSERT INTO admission_applications (
            application_no, user_id, school_year_id, grade_level_id, strand_id,
            first_name, last_name, gender, birthdate, email, contact_number, status
        ) VALUES (
            'ADM-2026-0008', :uid, 1, 5, 1,
            'Mr', 'Consonants', 'Male', '2008-01-15', :email, '09171234567', 'Draft'
        )
    ")->execute([
        'uid' => $userId,
        'email' => $email
    ]);

    $pdo->commit();
    echo "Account created successfully!\nEmail: {$email}\nUsername: {$username}\nPassword: {$password}\nApplication No: ADM-2026-0008\n";
} else {
    // Update password to password123
    $pdo->prepare("UPDATE users SET password = :pass WHERE email = :email")->execute([
        'pass' => $hashed,
        'email' => $email
    ]);
    echo "Account already exists! Password updated to: {$password}\n";
}
