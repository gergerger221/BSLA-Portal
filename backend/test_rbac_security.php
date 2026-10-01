<?php
/**
 * Comprehensive RBAC & Backend Hardening Security Test Suite
 * Senior High School Enrollment System
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/Env.php';
require_once __DIR__ . '/config/Database.php';

$baseUrl = 'http://localhost/sia-project2/backend/api/index.php';

// Helper function to send requests
function apiRequest(string $route, string $method = 'GET', array $data = [], ?string $token = null): array {
    global $baseUrl;
    $url = $baseUrl . '?route=' . $route;
    
    $headers = [
        "Content-Type: application/json",
        "Accept: application/json"
    ];
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }
    
    $options = [
        'http' => [
            'method'        => $method,
            'header'        => implode("\r\n", $headers) . "\r\n",
            'ignore_errors' => true,
            'timeout'       => 10
        ]
    ];
    
    if (in_array($method, ['POST', 'PUT', 'DELETE']) && !empty($data)) {
        $options['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    $httpCode = 500;
    if (isset($http_response_header[0]) && preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $matches)) {
        $httpCode = (int)$matches[1];
    }
    
    $json = json_decode($response, true) ?: [];
    return [
        'code' => $httpCode,
        'body' => $json,
        'raw'  => $response
    ];
}

function login(string $username, string $password = 'password123', ?string $portalType = null): ?string {
    $payload = ['username' => $username, 'password' => $password];
    if ($portalType) {
        $payload['portal_type'] = $portalType;
    }
    $res = apiRequest('auth/login', 'POST', $payload);
    if ($res['code'] === 200 && !empty($res['body']['data']['token'])) {
        return $res['body']['data']['token'];
    }
    return null;
}

// Reset rate limiter for automated RBAC verification
try {
    $db = \App\Config\Database::getConnection();
    $db->exec("DELETE FROM login_attempts WHERE ip_address IN ('127.0.0.1', '::1')");
} catch (\Throwable $e) {}

echo "=========================================================================\n";
echo "    PHASE 1: RBAC & BACKEND ROUTE HARDENING VERIFICATION SUITE         \n";
echo "=========================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $testName, string $details = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo " [\033[32mPASS\033[0m] {$testName}\n";
    } else {
        $failCount++;
        echo " [\033[31mFAIL\033[0m] {$testName} - {$details}\n";
    }
}

// 1. Authenticate All 8 Roles
echo "--- 1. Authenticating Demo Accounts Across All 8 Roles ---\n";
$tokens = [
    'admin'       => login('admin', 'password123', 'staff'),
    'coordinator' => login('maria_coordinator', 'password123', 'staff'),
    'registrar'   => login('maria_registrar', 'password123', 'staff'),
    'treasury'    => login('maria_treasury', 'password123', 'staff'),
    'records'     => login('maria_records', 'password123', 'staff'),
    'teacher'     => login('prof_delacruz', 'password123', 'staff'),
    'student'     => login('2026-SHS-0005', 'password123', 'student'),
    'applicant'   => login('applicant@test.com', 'password123', 'applicant')
];

foreach ($tokens as $role => $tok) {
    assertTest(!empty($tok), "Login for Role: {$role}", "Failed to obtain Bearer token");
}

echo "\n--- 2. Testing Unauthenticated Request Rejections (HTTP 401) ---\n";
$protectedRoutes = [
    'admin/stats',
    'admin/users',
    'registrar/applications',
    'registrar/queue',
    'treasury/assessments',
    'coordinator/curriculum',
    'records/students',
    'student/dashboard',
    'schedules/section?section_id=1',
    'admission/my-application'
];

foreach ($protectedRoutes as $r) {
    $res = apiRequest($r, 'GET', [], null);
    assertTest($res['code'] === 401, "Unauthenticated access to '{$r}' returns 401 Unauthorized", "Got HTTP {$res['code']}");
}

echo "\n--- 3. Testing Admin Module RBAC Restrictions ---\n";
// admin/users should be accessible only by admin
assertTest(apiRequest('admin/users', 'GET', [], $tokens['admin'])['code'] === 200, "Admin can access admin/users (200)");
assertTest(apiRequest('admin/users', 'GET', [], $tokens['teacher'])['code'] === 403, "Teacher CANNOT access admin/users (403)");
assertTest(apiRequest('admin/users', 'GET', [], $tokens['student'])['code'] === 403, "Student CANNOT access admin/users (403)");
assertTest(apiRequest('admin/users', 'GET', [], $tokens['applicant'])['code'] === 403, "Applicant CANNOT access admin/users (403)");
assertTest(apiRequest('admin/users', 'GET', [], $tokens['treasury'])['code'] === 403, "Treasury CANNOT access admin/users (403)");
assertTest(apiRequest('admin/users', 'GET', [], $tokens['records'])['code'] === 403, "Records CANNOT access admin/users (403)");

// admin/save-user should be accessible only by admin
assertTest(apiRequest('admin/save-user', 'POST', ['username' => 'hack'], $tokens['student'])['code'] === 403, "Student CANNOT call admin/save-user (403)");
assertTest(apiRequest('admin/save-user', 'POST', ['username' => 'hack'], $tokens['registrar'])['code'] === 403, "Registrar CANNOT call admin/save-user (403)");

echo "\n--- 4. Testing Registrar Module RBAC Restrictions ---\n";
// registrar/verify-document
assertTest(apiRequest('registrar/verify-document', 'POST', ['document_id' => 1], $tokens['student'])['code'] === 403, "Student CANNOT verify documents (403)");
assertTest(apiRequest('registrar/verify-document', 'POST', ['document_id' => 1], $tokens['treasury'])['code'] === 403, "Treasury CANNOT verify documents (403)");
assertTest(apiRequest('registrar/verify-document', 'POST', ['document_id' => 1], $tokens['teacher'])['code'] === 403, "Teacher CANNOT verify documents (403)");

// registrar/approve-and-queue
assertTest(apiRequest('registrar/approve-and-queue', 'POST', ['application_id' => 1], $tokens['applicant'])['code'] === 403, "Applicant CANNOT self-approve application (403)");
assertTest(apiRequest('registrar/approve-and-queue', 'POST', ['application_id' => 1], $tokens['coordinator'])['code'] === 403, "Coordinator CANNOT approve applicant to queue (403)");

echo "\n--- 5. Testing Treasury Module RBAC Restrictions & IDOR ---\n";
// treasury/process-payment
assertTest(apiRequest('treasury/process-payment', 'POST', ['assessment_id' => 1, 'amount_paid' => 100], $tokens['applicant'])['code'] === 403, "Applicant CANNOT call treasury/process-payment (403)");
assertTest(apiRequest('treasury/process-payment', 'POST', ['assessment_id' => 1, 'amount_paid' => 100], $tokens['teacher'])['code'] === 403, "Teacher CANNOT call treasury/process-payment (403)");
assertTest(apiRequest('treasury/process-payment', 'POST', ['assessment_id' => 1, 'amount_paid' => 100], $tokens['registrar'])['code'] === 403, "Registrar CANNOT call treasury/process-payment (403)");

// IDOR on treasury/assessment-details: Student trying to view someone else's assessment
$db = App\Config\Database::getConnection();
$otherAssId = (int)$db->query("SELECT id FROM student_assessments LIMIT 1")->fetchColumn();
if ($otherAssId) {
    // Check with applicant token whose ID does not match this assessment
    $assRes = apiRequest("treasury/assessment-details&id={$otherAssId}", 'GET', [], $tokens['applicant']);
    assertTest($assRes['code'] === 403 || $assRes['code'] === 404, "Applicant cannot view another student's assessment details (IDOR blocked with 403/404)", "Got HTTP {$assRes['code']}");
}

echo "\n--- 6. Testing Academic Coordinator Module RBAC Restrictions ---\n";
// coordinator/save-subject
assertTest(apiRequest('coordinator/save-subject', 'POST', ['code' => 'TEST'], $tokens['student'])['code'] === 403, "Student CANNOT modify curriculum subjects (403)");
assertTest(apiRequest('coordinator/save-subject', 'POST', ['code' => 'TEST'], $tokens['treasury'])['code'] === 403, "Treasury CANNOT modify curriculum subjects (403)");
assertTest(apiRequest('coordinator/save-subject', 'POST', ['code' => 'TEST'], $tokens['registrar'])['code'] === 403, "Registrar CANNOT modify curriculum subjects (403)");

// coordinator/save-strand
assertTest(apiRequest('coordinator/save-strand', 'POST', ['code' => 'TEST'], $tokens['teacher'])['code'] === 403, "Teacher CANNOT modify strands (403)");
assertTest(apiRequest('coordinator/save-strand', 'POST', ['code' => 'TEST'], $tokens['applicant'])['code'] === 403, "Applicant CANNOT modify strands (403)");

// schedules/save
assertTest(apiRequest('schedules/save', 'POST', ['section_id' => 1], $tokens['student'])['code'] === 403, "Student CANNOT alter timetables (403)");
assertTest(apiRequest('schedules/save', 'POST', ['section_id' => 1], $tokens['treasury'])['code'] === 403, "Treasury CANNOT alter timetables (403)");

echo "\n--- 7. Testing Records Custodian Module RBAC Restrictions & IDOR ---\n";
// records/update-request-status
assertTest(apiRequest('records/update-request-status', 'POST', ['id' => 1, 'status' => 'Released'], $tokens['student'])['code'] === 403, "Student CANNOT release their own document requests (403)");
assertTest(apiRequest('records/update-request-status', 'POST', ['id' => 1, 'status' => 'Released'], $tokens['teacher'])['code'] === 403, "Teacher CANNOT release document requests (403)");

// IDOR on records/transcript: Student trying to view another student's transcript
$otherStudentId = (int)$db->query("SELECT id FROM users WHERE role_id = 7 AND username != '2026-SHS-0005' LIMIT 1")->fetchColumn();
if ($otherStudentId) {
    $trRes = apiRequest("records/transcript&student_id={$otherStudentId}", 'GET', [], $tokens['student']);
    assertTest($trRes['code'] === 403, "Student cannot view another student's transcript (IDOR blocked with 403 Forbidden)", "Got HTTP {$trRes['code']}");
}

// Records Custodian / Registrar CAN view student transcript
if ($otherStudentId) {
    $trStaffRes = apiRequest("records/transcript&student_id={$otherStudentId}", 'GET', [], $tokens['records']);
    assertTest($trStaffRes['code'] === 200, "Records Custodian CAN view student transcript (200 OK)", "Got HTTP {$trStaffRes['code']}");
}

echo "\n--- 8. Testing Admission Applicant Module RBAC & IDOR ---\n";
// Admission endpoints should reject unauthorized staff roles (e.g. Teacher, Records)
assertTest(apiRequest('admission/my-application', 'GET', [], $tokens['teacher'])['code'] === 403, "Teacher CANNOT access admission/my-application (403)");
assertTest(apiRequest('admission/my-application', 'GET', [], $tokens['records'])['code'] === 403, "Records CANNOT access admission/my-application (403)");

// Applicant CAN access their own application
$myAppRes = apiRequest('admission/my-application', 'GET', [], $tokens['applicant']);
assertTest($myAppRes['code'] === 200, "Applicant CAN retrieve their own application (200 OK)", "Got HTTP {$myAppRes['code']}");

echo "\n=========================================================================\n";
echo " RESULTS: {$passCount} PASSED, {$failCount} FAILED\n";
echo "=========================================================================\n";
