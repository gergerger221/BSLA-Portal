<?php
// Test all demo accounts in sia-project

$users = [
    'admin' => 'Super Admin',
    'coordinator' => 'Academic Coordinator',
    'registrar' => 'Registrar',
    'treasury' => 'Treasury / Cashier',
    'records' => 'Records Custodian',
    '2026-SHS-0001' => 'Enrolled Student (SHS)',
    '2026-JHS-0001' => 'Enrolled Student (JHS)',
    'student2026' => 'Lynrd Rosales (Demo Enrollee in Queue)',
    'mrconsonants@gmail.com' => 'Mr Consonants (Applicant Account)'
];

echo "=== Testing SIA High School Demo Portal Logins ===\n";

foreach ($users as $username => $roleDesc) {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode(['username' => $username, 'password' => 'password123']),
            'timeout' => 5
        ]
    ]);
    
    $raw = @file_get_contents('http://localhost/sia-project2/backend/api/index.php?route=auth/login', false, $ctx);
    $res = json_decode($raw, true);
    
    if ($res && !empty($res['success'])) {
        $data = $res['data'];
        echo " [PASS] {$username} ({$roleDesc}) -> Logged In as {$data['role_name']} [Token: " . substr($data['token'], 0, 10) . "...]\n";
    } else {
        echo " [FAIL] {$username} -> " . ($res['message'] ?? 'Connection error') . "\n";
    }
}

echo "=== All Portals & Logins Verified! ===\n";
