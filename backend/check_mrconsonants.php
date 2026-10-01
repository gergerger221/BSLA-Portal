<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=sia_highschool_db", 'root', '');
$res = $pdo->query("
    SELECT u.id, u.username, u.email, up.first_name, up.last_name, aa.application_no, aa.status
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN admission_applications aa ON u.id = aa.user_id
    WHERE u.email = 'mrconsonants@gmail.com'
")->fetch(PDO::FETCH_ASSOC);

print_r($res);
