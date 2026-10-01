<?php
// backend/test_paymongo_service.php
declare(strict_types=1);

require_once __DIR__ . '/config/Env.php';
\App\Config\Env::load();

require_once __DIR__ . '/services/PayMongoService.php';

use App\Services\PayMongoService;

echo "=======================================================\n";
echo "       PAYMONGO API WORKFLOW VERIFICATION TEST\n";
echo "=======================================================\n\n";

echo "1. Checking Environment Keys:\n";
$hasSecret = PayMongoService::isConfigured();
$secretKey = PayMongoService::getSecretKey();
$publicKey = PayMongoService::getPublicKey();

if (!$hasSecret) {
    echo "   [WARNING] PayMongo Secret Key not yet found on disk in .env.\n";
    echo "             Please ensure you press Ctrl + S in your editor on backend/.env!\n";
    exit(1);
}

$maskedSecret = substr($secretKey, 0, 10) . '...' . substr($secretKey, -4);
echo "   [OK] PayMongo Secret Key detected: {$maskedSecret}\n";
if (!empty($publicKey)) {
    $maskedPublic = substr($publicKey, 0, 10) . '...' . substr($publicKey, -4);
    echo "   [OK] PayMongo Public Key detected: {$maskedPublic}\n";
}

echo "\n2. Testing PayMongo Checkout Session Creation:\n";
try {
    $testSession = PayMongoService::createCheckoutSession([
        'amount'          => 3000.00,
        'description'     => 'Verification Test - Grade 11 Downpayment',
        'reference_no'    => 'TEST-REF-' . time(),
        'customer_name'   => 'Juan Dela Cruz',
        'customer_email'  => 'juan.delacruz@test.sia.edu.ph',
        'customer_phone'  => '09171234567',
        'success_url'     => 'http://localhost/sia-project2/frontend/dist/#/applicant/procedure?paymongo_status=success&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'      => 'http://localhost/sia-project2/frontend/dist/#/applicant/procedure?paymongo_status=cancelled',
        'line_item_name'  => 'Senior High School Downpayment (STEM)'
    ]);

    $sessionId = $testSession['id'] ?? null;
    $checkoutUrl = $testSession['attributes']['checkout_url'] ?? null;
    $status = $testSession['attributes']['status'] ?? null;

    echo "   [SUCCESS] Checkout Session Created via PayMongo API!\n";
    echo "   - Session ID:   {$sessionId}\n";
    echo "   - Status:       {$status}\n";
    echo "   - Checkout URL: {$checkoutUrl}\n";

    echo "\n3. Testing PayMongo Checkout Session Retrieval (GET /checkout_sessions/{id}):\n";
    $fetched = PayMongoService::getCheckoutSession($sessionId);
    $fetchedStatus = $fetched['attributes']['status'] ?? 'unknown';
    echo "   [SUCCESS] Retrieved Session Status: {$fetchedStatus}\n";

    echo "\n=======================================================\n";
    echo "  ALL PAYMONGO API SERVICE TESTS PASSED SUCCESSFULLY!  \n";
    echo "=======================================================\n";

} catch (Exception $e) {
    echo "   [FAIL] Error communicating with PayMongo: " . $e->getMessage() . "\n";
    exit(1);
}
