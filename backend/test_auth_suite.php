<?php
/**
 * Comprehensive Authentication Test Suite for SIA-Project2
 * Tests live endpoints against http://localhost/sia-project2/backend/api/index.php
 */

require_once __DIR__ . '/config/Env.php';
require_once __DIR__ . '/config/Database.php';

$apiEndpoint = 'http://localhost/sia-project2/backend/api/index.php?route=auth/login';

// Reset any active rate limiter before starting test
try {
    $db = \App\Config\Database::getConnection();
    $db->exec("DELETE FROM login_attempts WHERE ip_address IN ('127.0.0.1', '::1')");
} catch (\Throwable $e) {}

function postLogin($endpoint, $payload) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);

    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'json' => json_decode($body, true) ?: [],
        'raw'  => $body
    ];
}

function assertAuth($passed, $testName, $detail = '') {
    if ($passed) {
        echo " [\033[32mPASS\033[0m] {$testName}\n";
    } else {
        echo " [\033[31mFAIL\033[0m] {$testName} - {$detail}\n";
    }
}

echo "=========================================================================\n";
echo "           SIA-PROJECT2 AUTHENTICATION TEST SUITE                       \n";
echo "=========================================================================\n\n";

// --- PHASE 1: Valid Logins for All Roles ---
echo "--- 1. Testing Valid Authentication Across All Roles ---\n";
$accounts = [
    ['user' => 'admin', 'pass' => 'password123', 'portal' => 'staff', 'role' => 'Super Administrator'],
    ['user' => 'coordinator', 'pass' => 'password123', 'portal' => 'staff', 'role' => 'Academic Coordinator'],
    ['user' => 'registrar', 'pass' => 'password123', 'portal' => 'staff', 'role' => 'Registrar'],
    ['user' => 'treasury', 'pass' => 'password123', 'portal' => 'staff', 'role' => 'Treasury / Cashier'],
    ['user' => 'records', 'pass' => 'password123', 'portal' => 'staff', 'role' => 'School Records Custodian'],
    ['user' => 'teacher', 'pass' => 'password123', 'portal' => 'staff', 'role' => 'Faculty Teacher'],
    ['user' => '2026-SHS-0005', 'pass' => 'password123', 'portal' => 'student', 'role' => 'Enrolled Student (SHS)'],
    ['user' => '2026-JHS-0001', 'pass' => 'password123', 'portal' => 'student', 'role' => 'Enrolled Student (JHS)'],
    ['user' => '2026-SHS-9040', 'pass' => 'TING', 'portal' => 'student', 'role' => 'Enrolled Student (Last Name Password)'],
    ['user' => 'applicant@test.com', 'pass' => 'password123', 'portal' => 'applicant', 'role' => 'Admission Applicant']
];

$tokens = [];
foreach ($accounts as $acc) {
    $res = postLogin($apiEndpoint, [
        'username' => $acc['user'],
        'password' => $acc['pass'],
        'portal_type' => $acc['portal']
    ]);

    $success = ($res['code'] === 200 && !empty($res['json']['data']['token']));
    if ($success) {
        $tokens[$acc['user']] = $res['json']['data']['token'];
        $returnedRole = $res['json']['data']['role_name'] ?? 'OK';
        assertAuth(true, "Login: {$acc['user']} ({$acc['role']}) -> HTTP 200, Role: {$returnedRole}");
    } else {
        $msg = $res['json']['message'] ?? "HTTP {$res['code']}";
        assertAuth(false, "Login: {$acc['user']} ({$acc['role']})", $msg);
    }
}

// --- PHASE 2: Invalid Credentials Rejections ---
echo "\n--- 2. Testing Invalid Credentials Rejections (HTTP 401) ---\n";

// 2.1 Wrong password
$wrongPassRes = postLogin($apiEndpoint, [
    'username' => 'admin',
    'password' => 'definitely_wrong_password_xyz'
]);
assertAuth($wrongPassRes['code'] === 401, "Invalid password for 'admin' returns 401 Unauthorized", "Got HTTP {$wrongPassRes['code']}");

// 2.2 Non-existent user
$unknownUserRes = postLogin($apiEndpoint, [
    'username' => 'non_existent_account_99999',
    'password' => 'password123'
]);
assertAuth($unknownUserRes['code'] === 401, "Non-existent username returns 401 Unauthorized", "Got HTTP {$unknownUserRes['code']}");

// 2.3 Empty credentials
$emptyRes = postLogin($apiEndpoint, [
    'username' => '',
    'password' => ''
]);
assertAuth($emptyRes['code'] === 400 || $emptyRes['code'] === 401, "Empty credentials rejected (HTTP 400/401)", "Got HTTP {$emptyRes['code']}");

// --- PHASE 3: Portal Separation Enforcement (HTTP 403) ---
echo "\n--- 3. Testing Strict Portal Separation Security (HTTP 403) ---\n";

// 3.1 Student trying to authenticate through Staff portal
$stuInStaff = postLogin($apiEndpoint, [
    'username' => '2026-SHS-0005',
    'password' => 'password123',
    'portal_type' => 'staff'
]);
assertAuth($stuInStaff['code'] === 403, "Student blocked from Faculty & Staff Portal (403 Forbidden)", "Got HTTP {$stuInStaff['code']}");

// 3.2 Staff trying to authenticate through Student portal
$staffInStu = postLogin($apiEndpoint, [
    'username' => 'admin',
    'password' => 'password123',
    'portal_type' => 'student'
]);
assertAuth($staffInStu['code'] === 403, "Super Admin blocked from Student Portal (403 Forbidden)", "Got HTTP {$staffInStu['code']}");

// 3.3 Applicant trying to authenticate through Student portal
$appInStu = postLogin($apiEndpoint, [
    'username' => 'applicant@test.com',
    'password' => 'password123',
    'portal_type' => 'student'
]);
assertAuth($appInStu['code'] === 403, "Applicant blocked from Enrolled Student Portal (403 Forbidden)", "Got HTTP {$appInStu['code']}");

// --- PHASE 4: Token Validation on Protected Endpoint ---
echo "\n--- 4. Testing Token Authorization Verification ---\n";
if (!empty($tokens['admin'])) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/sia-project2/backend/api/index.php?route=admin/stats');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $tokens['admin'],
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    $adminStats = curl_exec($ch);
    $statCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    assertAuth($statCode === 200, "Valid Bearer Token successfully unlocks protected route 'admin/stats' (200 OK)", "Got HTTP {$statCode}");
}

// --- PHASE 5: Rate Limiting / Brute Force Lockout (HTTP 429) ---
echo "\n--- 5. Testing Login Rate Limiting Lockout (HTTP 429) ---\n";
$hit429 = false;
for ($i = 1; $i <= 7; $i++) {
    $attempt = postLogin($apiEndpoint, [
        'username' => 'admin',
        'password' => 'bad_pass_' . $i
    ]);
    if ($attempt['code'] === 429) {
        $hit429 = true;
        break;
    }
}
assertAuth($hit429, "Brute force attack blocked with HTTP 429 Too Many Requests within 5 attempts");

// Reset rate limiter after test finishes so system stays ready for use
try {
    $db = \App\Config\Database::getConnection();
    $db->exec("DELETE FROM login_attempts WHERE ip_address IN ('127.0.0.1', '::1')");
} catch (\Throwable $e) {}

echo "\n=========================================================================\n";
echo "             AUTHENTICATION TEST SUITE COMPLETED                         \n";
echo "=========================================================================\n";
