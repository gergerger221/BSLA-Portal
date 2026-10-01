<?php
// backend/test_payment_workflow.php

echo "=================================================================\n";
echo "  JJKINGS ACADEMY - ENHANCED PAYMENT WORKFLOW E2E HTTP VERIFICATION\n";
echo "=================================================================\n\n";

$baseUrl = 'http://localhost/sia-project/backend/api/index.php';

function postJson($url, $data, $token = null) {
    $headers = "Content-Type: application/json\r\n";
    if ($token) {
        $headers .= "Authorization: Bearer {$token}\r\n";
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => $headers,
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ]);
    $res = file_get_contents($url, false, $ctx);
    return json_decode($res, true);
}

function getJson($url, $token = null) {
    $headers = "";
    if ($token) {
        $headers .= "Authorization: Bearer {$token}\r\n";
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => $headers,
            'ignore_errors' => true
        ]
    ]);
    $res = file_get_contents($url, false, $ctx);
    return json_decode($res, true);
}

// Database direct connection for seeding mock files
$db = new PDO("mysql:host=127.0.0.1;dbname=sia_highschool_db;charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

function attachRequiredDocs($appId, $db) {
    $docs = [
        'PSA Birth Certificate',
        'SF9 / Form 138 (Report Card)',
        'Certificate of Good Moral Character',
        '2x2 ID Picture',
        'Certificate of JHS Completion'
    ];
    foreach ($docs as $doc) {
        $db->prepare("
            INSERT INTO admission_documents (application_id, document_type, file_path, original_filename, file_size, status)
            VALUES (:aid, :dt, 'uploads/admission_docs/sample.pdf', 'sample.pdf', 1024, 'Verified')
        ")->execute(['aid' => $appId, 'dt' => $doc]);
    }
}

// 0. Login Treasury Staff & Registrar Staff
$treasuryLogin = postJson("{$baseUrl}?route=auth/login", [
    'username' => 'maria_treasury',
    'password' => 'password123'
]);
if (!($treasuryLogin['success'] ?? false)) {
    die("[FAIL] Treasury staff login failed: " . json_encode($treasuryLogin) . "\n");
}
$treasuryToken = $treasuryLogin['data']['token'];
echo "[PASS] Treasury Staff Authenticated: " . $treasuryLogin['data']['username'] . "\n";

$registrarLogin = postJson("{$baseUrl}?route=auth/login", [
    'username' => 'maria_registrar',
    'password' => 'password123'
]);
if (!($registrarLogin['success'] ?? false)) {
    die("[FAIL] Registrar staff login failed: " . json_encode($registrarLogin) . "\n");
}
$registrarToken = $registrarLogin['data']['token'];
echo "[PASS] Registrar Staff Authenticated: " . $registrarLogin['data']['username'] . "\n\n";

// =========================================================================
// TEST 1: WALK-IN PAYMENT APPOINTMENT & CASHIER COLLECTION WORKFLOW
// =========================================================================
echo "-----------------------------------------------------------------\n";
echo "TEST 1: Walk-in Payment Slip Schedule & Cashier Official Enrollment\n";
echo "-----------------------------------------------------------------\n";

$u1 = substr(md5(uniqid()), 0, 5);
$app1 = postJson("{$baseUrl}?route=auth/register-applicant", [
    'first_name'     => 'Danilo',
    'last_name'      => 'Cruz',
    'email'          => "danilo.{$u1}@example.com",
    'contact_number' => '09181234567',
    'password'       => 'Password123!',
    'lrn'            => '10987654' . rand(1000, 9999)
]);
if (!($app1['success'] ?? false)) {
    die("[FAIL] Applicant 1 Registration failed: " . json_encode($app1) . "\n");
}
$token1 = $app1['data']['token'];
$appNo1 = $app1['data']['application_no'];

$myAppInit1 = getJson("{$baseUrl}?route=admission/my-application", $token1);
$appId1 = $myAppInit1['data']['id'];
echo "[PASS] Registered Enrollee 1: Danilo Cruz (App No: {$appNo1}, ID: {$appId1})\n";

// Update details & program (Grade 11 STEM)
postJson("{$baseUrl}?route=admission/update", [
    'gender' => 'Male', 'birthdate' => '2009-05-12', 'civil_status' => 'Single', 'nationality' => 'Filipino',
    'religion' => 'Roman Catholic', 'grade_level_id' => 5, 'track_id' => 1, 'strand_id' => 1,
    'voucher_status' => 'None', 'guardian_name' => 'Roberto Cruz', 'guardian_contact' => '09181234567'
], $token1);

// Attach 5 mandatory requirements & submit
attachRequiredDocs($appId1, $db);
$subRes1 = postJson("{$baseUrl}?route=admission/submit", [], $token1);
if (!($subRes1['success'] ?? false)) {
    die("[FAIL] Application submission failed: " . json_encode($subRes1) . "\n");
}
echo "[PASS] Uploaded mandatory documents & submitted application for Registrar review.\n";

// Registrar Approves and Queues for Enrollment & Assessment
$apprRes1 = postJson("{$baseUrl}?route=registrar/approve-and-queue", [
    'application_id' => $appId1,
    'voucher_status' => 'None'
], $registrarToken);
if (!($apprRes1['success'] ?? false)) {
    die("[FAIL] Registrar approve & queue failed: " . json_encode($apprRes1) . "\n");
}
echo "[PASS] Registrar Approved & Queued for Enrollment (Section & Assessment initialized).\n";

// Applicant checks application data & fee assessment
$myApp1 = getJson("{$baseUrl}?route=admission/my-application", $token1);
$assId1 = $myApp1['data']['assessment_info']['id'];
$netPayable1 = (float)$myApp1['data']['assessment_info']['net_payable'];
echo "[PASS] Student Assessment Generated: Net Payable = ₱" . number_format($netPayable1, 2) . "\n";

// Applicant generates Walk-in Payment Ticket Slip
$walkinSlip = postJson("{$baseUrl}?route=admission/checkout-payment", [
    'payment_type' => 'walkin',
    'walkin_date'  => '2026-09-02',
    'time_slot'    => '08:00 AM - 11:30 AM (Morning Batch)',
    'amount'       => 3000.00
], $token1);

if (!($walkinSlip['success'] ?? false)) {
    die("[FAIL] Walk-in slip generation failed: " . json_encode($walkinSlip) . "\n");
}
$ticketNo1 = $walkinSlip['data']['ticket_number'];
echo "[PASS] Walk-in Payment Slip Issued!\n";
echo "       -> Ticket No: {$ticketNo1}\n";
echo "       -> Scheduled Date: " . $walkinSlip['data']['scheduled_date'] . " (" . $walkinSlip['data']['time_slot'] . ")\n";
echo "       -> Location: " . $walkinSlip['data']['location'] . "\n";
echo "       -> Status: " . $walkinSlip['data']['status'] . "\n";
echo "       -> Instructions: " . $walkinSlip['data']['instructions'] . "\n";

// Verify that student is NOT yet Enrolled
$chkApp1 = getJson("{$baseUrl}?route=admission/my-application", $token1);
if ($chkApp1['data']['status'] !== 'Walk-in Payment Scheduled') {
    die("[FAIL] Expected status 'Walk-in Payment Scheduled', got '{$chkApp1['data']['status']}'\n");
}
if ($chkApp1['data']['assessment_info']['enrollment_status'] === 'Officially Enrolled') {
    die("[FAIL] Student should NOT be officially enrolled before cashier confirmation!\n");
}
echo "[PASS] Confirmed: Student is waiting for walk-in cashier confirmation.\n";

// Cashier processes cash payment at Window 1/2
$cashierPay = postJson("{$baseUrl}?route=treasury/process-payment", [
    'assessment_id'  => $assId1,
    'amount_paid'    => 3000.00,
    'payment_method' => 'Cash',
    'reference_no'   => $ticketNo1,
    'remarks'        => 'Cashier Window Walk-in Downpayment'
], $treasuryToken);

if (!($cashierPay['success'] ?? false)) {
    die("[FAIL] Cashier payment processing failed: " . json_encode($cashierPay) . "\n");
}
$orNumber1 = $cashierPay['data']['or_number'];
$studentNo1 = $cashierPay['data']['student_no'];
echo "[PASS] Cashier processed payment at window!\n";
echo "       -> Official Receipt: {$orNumber1}\n";
echo "       -> Permanent Student Number Assigned: {$studentNo1}\n";

// Verify final Official Enrollment
$finalApp1 = getJson("{$baseUrl}?route=admission/my-application", $token1);
if ($finalApp1['data']['status'] !== 'Enrolled' || $finalApp1['data']['assessment_info']['enrollment_status'] !== 'Officially Enrolled') {
    die("[FAIL] Student should now be Officially Enrolled. Status: {$finalApp1['data']['status']}\n");
}
echo "[PASS] Walk-in Payment Workflow Complete & Verified!\n\n";

// =========================================================================
// TEST 2: ONLINE PAYMENT QUEUE, VERIFICATION, REJECTION & AUTO-ENROLLMENT
// =========================================================================
echo "-----------------------------------------------------------------\n";
echo "TEST 2: Online Payment Submission -> Treasury Verification Queue\n";
echo "-----------------------------------------------------------------\n";

$u2 = substr(md5(uniqid()), 0, 5);
$app2 = postJson("{$baseUrl}?route=auth/register-applicant", [
    'first_name'     => 'Chloe',
    'last_name'      => 'Bernardo',
    'email'          => "chloe.{$u2}@example.com",
    'contact_number' => '09958765432',
    'password'       => 'Password123!',
    'lrn'            => '10987654' . rand(1000, 9999)
]);
if (!($app2['success'] ?? false)) {
    die("[FAIL] Applicant 2 Registration failed: " . json_encode($app2) . "\n");
}
$token2 = $app2['data']['token'];
$appNo2 = $app2['data']['application_no'];

$myAppInit2 = getJson("{$baseUrl}?route=admission/my-application", $token2);
$appId2 = $myAppInit2['data']['id'];
echo "[PASS] Registered Enrollee 2: Chloe Bernardo (App No: {$appNo2}, ID: {$appId2})\n";

// Set program & upload docs & approve
postJson("{$baseUrl}?route=admission/update", [
    'gender' => 'Female', 'birthdate' => '2009-08-20', 'civil_status' => 'Single', 'nationality' => 'Filipino',
    'grade_level_id' => 5, 'track_id' => 1, 'strand_id' => 2, 'guardian_name' => 'Elena Bernardo', 'guardian_contact' => '09958765432'
], $token2);
attachRequiredDocs($appId2, $db);
postJson("{$baseUrl}?route=admission/submit", [], $token2);
postJson("{$baseUrl}?route=registrar/approve-and-queue", ['application_id' => $appId2, 'voucher_status' => 'None'], $registrarToken);

// Applicant submits Online Payment
$onlineRef1 = 'GCASH-TXN-' . mt_rand(10000000, 99999999);
$onlineSubmit = postJson("{$baseUrl}?route=admission/checkout-payment", [
    'payment_type'    => 'online',
    'payment_channel' => 'GCash',
    'reference_no'    => $onlineRef1,
    'account_name'    => 'Chloe Bernardo',
    'account_number'  => '09958765432',
    'amount'          => 3000.00
], $token2);

if (!($onlineSubmit['success'] ?? false)) {
    die("[FAIL] Online payment submission failed: " . json_encode($onlineSubmit) . "\n");
}
$submissionId2 = $onlineSubmit['data']['submission_id'];
echo "[PASS] Online Payment Submitted & Queued for Verification!\n";
echo "       -> Submission ID: {$submissionId2}\n";
echo "       -> Reference No: {$onlineRef1}\n";
echo "       -> Status: " . $onlineSubmit['data']['status'] . "\n";
echo "       -> Notice: " . $onlineSubmit['data']['notice'] . "\n";

// Verify that student is NOT yet Enrolled
$chkApp2 = getJson("{$baseUrl}?route=admission/my-application", $token2);
if ($chkApp2['data']['status'] !== 'Payment Submitted – Awaiting Verification') {
    die("[FAIL] Expected status 'Payment Submitted – Awaiting Verification', got '{$chkApp2['data']['status']}'\n");
}
if ($chkApp2['data']['assessment_info']['enrollment_status'] === 'Officially Enrolled') {
    die("[FAIL] Student should NOT be officially enrolled before Treasury verification!\n");
}
echo "[PASS] Confirmed: Student account is NOT created yet; status is correctly awaiting verification.\n";

// Treasury staff views Online Payment Queue
$queue = getJson("{$baseUrl}?route=treasury/online-payments", $treasuryToken);
if (!($queue['success'] ?? false) || empty($queue['data'])) {
    die("[FAIL] Failed to load Treasury Online Payment queue: " . json_encode($queue) . "\n");
}
echo "[PASS] Treasury Online Payment Queue loaded. Items in queue: " . count($queue['data']) . "\n";

// Treasury flags/rejects payment for review (Scenario: reference not matching statement)
$rejectRes = postJson("{$baseUrl}?route=treasury/verify-online-payment", [
    'submission_id'    => $submissionId2,
    'action'           => 'reject',
    'rejection_reason' => 'Transaction ID not found in GCash merchant batch. Please check reference number and re-submit.'
], $treasuryToken);

if (!($rejectRes['success'] ?? false)) {
    die("[FAIL] Rejection failed: " . json_encode($rejectRes) . "\n");
}
echo "[PASS] Treasury rejected / flagged payment for review.\n";
echo "       -> Reason: " . $rejectRes['data']['rejection_reason'] . "\n";

// Verify applicant sees rejection status & feedback
$chkRej = getJson("{$baseUrl}?route=admission/my-application", $token2);
if ($chkRej['data']['status'] !== 'Payment Verification Failed') {
    die("[FAIL] Expected applicant status 'Payment Verification Failed', got '{$chkRej['data']['status']}'\n");
}
echo "[PASS] Applicant portal displays 'Payment Verification Failed' with Treasury feedback.\n";

// Applicant re-submits corrected reference number
$correctedRef = 'GCASH-TXN-CORRECTED-' . mt_rand(10000000, 99999999);
$resub = postJson("{$baseUrl}?route=admission/checkout-payment", [
    'payment_type'    => 'online',
    'payment_channel' => 'GCash',
    'reference_no'    => $correctedRef,
    'account_name'    => 'Chloe Bernardo',
    'account_number'  => '09958765432',
    'amount'          => 3000.00
], $token2);

if (!($resub['success'] ?? false)) {
    die("[FAIL] Resubmission failed: " . json_encode($resub) . "\n");
}
echo "[PASS] Applicant re-submitted corrected reference: {$correctedRef}\n";

// Treasury Approves the corrected payment
$approveRes = postJson("{$baseUrl}?route=treasury/verify-online-payment", [
    'submission_id' => $submissionId2,
    'action'        => 'approve'
], $treasuryToken);

if (!($approveRes['success'] ?? false)) {
    die("[FAIL] Treasury approval failed: " . json_encode($approveRes) . "\n");
}
$orNumber2 = $approveRes['data']['or_number'];
$studentNo2 = $approveRes['data']['student_no'];
echo "[PASS] Treasury Approved Online Payment & Officially Enrolled Student!\n";
echo "       -> Official Receipt: {$orNumber2}\n";
echo "       -> Permanent Student Number: {$studentNo2}\n";

// Verify final Enrollment state & Step 5 readiness
$finalApp2 = getJson("{$baseUrl}?route=admission/my-application", $token2);
if ($finalApp2['data']['status'] !== 'Enrolled' || $finalApp2['data']['assessment_info']['enrollment_status'] !== 'Officially Enrolled') {
    die("[FAIL] Final enrollment verification failed. App: {$finalApp2['data']['status']}\n");
}
echo "[PASS] Online Payment Verification & Official Enrollment Complete!\n\n";

// Test Student Login with newly generated credentials
$studentLogin = postJson("{$baseUrl}?route=auth/login", [
    'username' => $studentNo2,
    'password' => 'BERNARDO'
]);
if (!($studentLogin['success'] ?? false)) {
    die("[FAIL] Student portal login with generated ID failed: " . json_encode($studentLogin) . "\n");
}
echo "[PASS] Student Successfully Logged In to Student Portal as {$studentNo2} (Role: {$studentLogin['data']['role_name']})!\n\n";

echo "=================================================================\n";
echo "  >>> ALL WALK-IN & ONLINE PAYMENT VERIFICATION TESTS PASSED! <<<\n";
echo "=================================================================\n";
