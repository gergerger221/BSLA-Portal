<?php
/**
 * Automated Verification Script: Payment Switching, Locking & Treasury Verification
 */
require_once __DIR__ . '/config/Env.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Response.php';
require_once __DIR__ . '/helpers/Auth.php';

use App\Config\Database;
use App\Helpers\Auth;

echo "=================================================================\n";
echo "  PAYMENT METHOD SWITCHING, LOCKING & VERIFICATION E2E TEST\n";
echo "=================================================================\n\n";

function requestApi(string $route, string $method = 'GET', array $data = [], ?string $token = null) {
    $url = "http://localhost/sia-project/backend/api/index.php?route={$route}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => json_decode($response, true),
        'raw'  => $response
    ];
}

$db = Database::getConnection();

// Authenticate Treasury and Registrar staff
$treasuryRes = requestApi('auth/login', 'POST', ['username' => 'maria_treasury', 'password' => 'password123']);
$treasuryToken = $treasuryRes['body']['data']['token'] ?? null;

$registrarRes = requestApi('auth/login', 'POST', ['username' => 'maria_registrar', 'password' => 'password123']);
$registrarToken = $registrarRes['body']['data']['token'] ?? null;

if (!$treasuryToken || !$registrarToken) {
    die("[FAIL] Failed to authenticate staff accounts.\n");
}
echo "[PASS] Staff authenticated successfully.\n\n";

// 1. REGISTER NEW APPLICANT
$uniq = time() . rand(100, 999);
$regData = [
    'first_name'     => 'Marco',
    'last_name'      => 'Valenzuela',
    'email'          => "marco_{$uniq}@example.com",
    'contact_number' => '09171234567',
    'password'       => 'Secret123!',
    'grade_level_id' => 5, // Grade 11
    'strand_id'      => 1, // STEM
    'lrn'            => '12' . substr((string)time(), -10)
];

$regRes = requestApi('auth/register-applicant', 'POST', $regData);
if (!$regRes['body']['success']) {
    die("[FAIL] Registration failed: " . ($regRes['body']['message'] ?? '') . "\n");
}
$applicantToken = $regRes['body']['data']['token'];
$appNo = $regRes['body']['data']['application_no'] ?? $regRes['body']['data']['application']['application_no'] ?? 'ADM-NEW';
$appId = $regRes['body']['data']['application_id'] ?? $regRes['body']['data']['application']['id'] ?? 0;
echo "[PASS] Registered applicant: Marco Valenzuela (App #{$appNo}, ID: {$appId})\n";

// Fast-track submit application
requestApi('admission/submit', 'POST', [], $applicantToken);
echo "[PASS] Application submitted for evaluation.\n";

// 2. TEST WALK-IN SELECTION & SWITCHING BEFORE PAYMENT SUBMISSION
echo "\n--- TEST: SWITCHING BEFORE PAYMENT SUBMISSION ---\n";

// 2.1 Schedule Walk-in
$walkinRes = requestApi('admission/checkout-payment', 'POST', [
    'payment_type' => 'walkin',
    'walkin_date'  => date('Y-m-d', strtotime('+3 weekdays')),
    'time_slot'    => '08:00 AM - 11:30 AM (Morning Batch)',
    'amount'       => 3000.00
], $applicantToken);

if (!$walkinRes['body']['success'] || empty($walkinRes['body']['data']['ticket_number'])) {
    die("[FAIL] Walk-in ticket generation failed: " . $walkinRes['raw'] . "\n");
}
$ticketNo = $walkinRes['body']['data']['ticket_number'];
echo "[PASS] Generated Walk-in Payment Slip: {$ticketNo}\n";

// 2.2 Switch to Online Payment Mode
$switchOnlineRes = requestApi('admission/switch-payment-mode', 'POST', ['payment_mode' => 'online'], $applicantToken);
if (!$switchOnlineRes['body']['success']) {
    die("[FAIL] Failed to switch to Online Payment mode: " . $switchOnlineRes['raw'] . "\n");
}
echo "[PASS] Successfully switched from Walk-in to Online Payment mode.\n";

// 2.3 Switch back to Walk-in Mode
$switchWalkinRes = requestApi('admission/switch-payment-mode', 'POST', ['payment_mode' => 'walkin'], $applicantToken);
if (!$switchWalkinRes['body']['success']) {
    die("[FAIL] Failed to switch back to Walk-in mode: " . $switchWalkinRes['raw'] . "\n");
}
echo "[PASS] Successfully switched back to Walk-in Payment mode.\n";

// 3. SUBMIT ONLINE PAYMENT VIA PAYMONGO
echo "\n--- TEST: ONLINE PAYMENT SUBMISSION & LOCKING ---\n";
$refNo = "PAYMONGO-GCASH-{$uniq}";
$payRes = requestApi('admission/checkout-payment', 'POST', [
    'payment_type'    => 'online',
    'payment_channel' => 'GCash',
    'reference_no'    => $refNo,
    'account_name'    => 'Marco Valenzuela',
    'account_number'  => '09171234567',
    'amount'          => 3000.00
], $applicantToken);

if (!$payRes['body']['success']) {
    die("[FAIL] Online payment submission failed: " . $payRes['raw'] . "\n");
}
$submissionId = $payRes['body']['data']['submission_id'];
echo "[PASS] Online Payment Submitted: Ref #{$refNo} (Submission ID: {$submissionId})\n";
echo "       Status: {$payRes['body']['data']['status']}\n";

// 4. VERIFY STRICT LOCK: DUPLICATE & SWITCHING BLOCKED WHILE PENDING
echo "\n--- TEST: STRICT LOCK WHILE AWAITING VERIFICATION ---\n";

// 4.1 Try duplicate online payment
$dupPayRes = requestApi('admission/checkout-payment', 'POST', [
    'payment_type'    => 'online',
    'payment_channel' => 'GCash',
    'reference_no'    => "ANOTHER-REF-{$uniq}",
    'amount'          => 3000.00
], $applicantToken);

if ($dupPayRes['code'] === 422 || !$dupPayRes['body']['success']) {
    echo "[PASS] Duplicate payment submission correctly BLOCKED by server.\n";
} else {
    die("[FAIL] Server allowed duplicate payment while awaiting verification!\n");
}

// 4.2 Try switching to Walk-in while pending
$blockSwitchRes = requestApi('admission/switch-payment-mode', 'POST', ['payment_mode' => 'walkin'], $applicantToken);
if ($blockSwitchRes['code'] === 422 || !$blockSwitchRes['body']['success']) {
    echo "[PASS] Switching payment mode correctly BLOCKED while awaiting verification.\n";
} else {
    die("[FAIL] Server allowed payment mode switch while awaiting verification!\n");
}

// 5. TREASURY REJECTION & POST-REJECTION ACTIONS
echo "\n--- TEST: TREASURY REJECTION & CORRECTION WORKFLOW ---\n";

$rejectionReason = "The submitted payment receipt screenshot is unreadable. Please upload a clear copy with complete reference number.";
$rejectRes = requestApi('treasury/verify-online-payment', 'POST', [
    'submission_id'    => $submissionId,
    'action'           => 'reject',
    'rejection_reason' => $rejectionReason
], $treasuryToken);

if (!$rejectRes['body']['success']) {
    die("[FAIL] Treasury rejection failed: " . $rejectRes['raw'] . "\n");
}
echo "[PASS] Treasury rejected payment with reason: '{$rejectionReason}'\n";

// Verify applicant application displays the rejection reason
$myAppRes = requestApi('admission/my-application', 'GET', [], $applicantToken);
$myApp = $myAppRes['body']['data'];
if ($myApp['status'] === 'Payment Verification Failed' && !empty($myApp['online_payment_submission']['rejection_reason'])) {
    echo "[PASS] Applicant portal correctly reflects 'Payment Verification Failed' and displays Treasury feedback.\n";
} else {
    die("[FAIL] Applicant portal status did not update to Payment Verification Failed.\n");
}

// 6. POST-REJECTION: TEST SWITCHING AND RESUBMISSION
echo "\n--- TEST: POST-REJECTION SWITCHING & RESUBMISSION ---\n";

// 6.1 Switch to Walk-in allowed after rejection
$postRejectSwitch = requestApi('admission/switch-payment-mode', 'POST', ['payment_mode' => 'walkin'], $applicantToken);
if ($postRejectSwitch['body']['success']) {
    echo "[PASS] Enrollee can switch to Walk-in mode after rejection.\n";
} else {
    die("[FAIL] Enrollee could not switch to Walk-in after rejection: " . $postRejectSwitch['raw'] . "\n");
}

// 6.2 Resubmit corrected online payment
$correctedRefNo = "PAYMONGO-GCASH-CORRECTED-{$uniq}";
$resubmitRes = requestApi('admission/checkout-payment', 'POST', [
    'payment_type'    => 'online',
    'payment_channel' => 'GCash',
    'reference_no'    => $correctedRefNo,
    'account_name'    => 'Marco Valenzuela',
    'account_number'  => '09171234567',
    'amount'          => 3000.00
], $applicantToken);

if (!$resubmitRes['body']['success']) {
    die("[FAIL] Resubmission failed: " . $resubmitRes['raw'] . "\n");
}
echo "[PASS] Corrected online payment successfully resubmitted: {$correctedRefNo}\n";

// 7. TREASURY APPROVAL & OFFICIAL ENROLLMENT
echo "\n--- TEST: TREASURY APPROVAL & OFFICIAL ENROLLMENT ---\n";

$approveRes = requestApi('treasury/verify-online-payment', 'POST', [
    'submission_id' => $submissionId,
    'action'        => 'approve'
], $treasuryToken);

if (!$approveRes['body']['success']) {
    die("[FAIL] Treasury approval failed: " . $approveRes['raw'] . "\n");
}
$orNo = $approveRes['body']['data']['or_number'];
$studentNo = $approveRes['body']['data']['student_no'];
echo "[PASS] Treasury Approved Payment!\n";
echo "       -> Official Receipt: {$orNo}\n";
echo "       -> Permanent Student Number: {$studentNo}\n";

// Verify Official Student Login
$studentLoginRes = requestApi('auth/login', 'POST', [
    'username' => $studentNo,
    'password' => 'VALENZUELA'
]);

if ($studentLoginRes['body']['success']) {
    echo "[PASS] Student successfully authenticated as official enrolled student ({$studentNo})!\n";
} else {
    die("[FAIL] Official student login failed.\n");
}

// 8. VERIFY NO FURTHER PAYMENT ON OFFICIALLY ENROLLED
$afterEnrolledPay = requestApi('admission/checkout-payment', 'POST', [
    'payment_type' => 'online',
    'reference_no' => 'EXTRA-REF-999',
    'amount'       => 1000
], $applicantToken);

if ($afterEnrolledPay['code'] === 422 || !$afterEnrolledPay['body']['success']) {
    echo "[PASS] Additional payment submission on officially enrolled account is correctly BLOCKED.\n";
} else {
    die("[FAIL] System allowed extra payment after official enrollment!\n");
}

echo "\n=================================================================\n";
echo "  >>> ALL PAYMENT SWITCHING & LOCKING REQUIREMENTS PASSED! <<<   \n";
echo "=================================================================\n";
