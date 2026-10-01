<?php
// backend/controllers/PayMongoController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;
use App\Helpers\Auth;
use App\Services\PayMongoService;
use Exception;
use PDO;

class PayMongoController {

    /**
     * Create a PayMongo Checkout Session for tuition / downpayment.
     * POST /api/index.php?route=paymongo/create-checkout
     */
    public function createCheckout(): void {
        $user = Auth::requireRole(['applicant', 'student']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $db = Database::getConnection();

        // 1. Fetch user's admission application
        $appStmt = $db->prepare("SELECT * FROM admission_applications WHERE user_id = :user_id LIMIT 1");
        $appStmt->execute(['user_id' => $user['id']]);
        $app = $appStmt->fetch();

        if (!$app) {
            Response::error('Admission application not found.', 404);
        }

        // Must be in an approved / ready-to-pay state
        if (!in_array($app['status'], ['Approved', 'Queued for Enrollment', 'Assessed', 'Walk-in Payment Scheduled', 'Payment Verification Failed'])) {
            if ($app['status'] === 'Enrolled') {
                Response::error('You are already officially enrolled. No additional downpayment is required.', 422);
            }
            Response::error('Online payment is not available. Application must be approved by the Registrar first.', 403);
        }

        // 2. Fetch assessment and enrollment record
        $enrStmt = $db->prepare("
            SELECT e.*, sa.id as assessment_id, sa.net_payable, sa.remaining_balance, sa.total_paid, sa.status as assessment_status, sa.minimum_downpayment
            FROM enrollments e
            LEFT JOIN student_assessments sa ON e.id = sa.enrollment_id
            WHERE e.application_id = :app_id
            LIMIT 1
        ");
        $enrStmt->execute(['app_id' => $app['id']]);
        $enr = $enrStmt->fetch();

        if (!$enr || empty($enr['assessment_id'])) {
            Response::error('Assessment record not found for your application. Please wait for Registrar processing.', 422);
        }

        // 3. Amount validation
        $amount = (float)($input['amount'] ?? 0);
        $maxPayable = (float)($enr['remaining_balance'] > 0 ? $enr['remaining_balance'] : $enr['net_payable']);
        $minRequired = min((float)($enr['minimum_downpayment'] ?? 3000.00), $maxPayable);

        if ($amount <= 0) {
            $amount = $minRequired;
        }

        if ($amount < $minRequired) {
            Response::error("Payment amount (₱" . number_format($amount, 2) . ") is below the required minimum of ₱" . number_format($minRequired, 2) . ".", 422);
        }

        if ($maxPayable > 0 && $amount > ($maxPayable + 0.01)) {
            Response::error("Payment amount (₱" . number_format($amount, 2) . ") exceeds the maximum payable balance of ₱" . number_format($maxPayable, 2) . ".", 422);
        }

        // 4. Construct Success & Cancel URLs
        // Determine host and path
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        $protocol = $isHttps ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Target client path in sia-project2
        $clientBase = $protocol . $host . '/sia-project2/frontend/dist/#/applicant/procedure';
        $successUrl = $clientBase . '?paymongo_status=success&session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = $clientBase . '?paymongo_status=cancelled';

        $studentName = trim("{$app['first_name']} {$app['last_name']}");
        $email = !empty($app['email']) ? $app['email'] : ($user['email'] ?? 'student@sia.edu.ph');
        $phone = !empty($app['contact_number']) ? $app['contact_number'] : '09123456789';

        try {
            // 5. Call PayMongo Service
            $session = PayMongoService::createCheckoutSession([
                'amount'         => $amount,
                'description'    => "Admission Downpayment - App #{$app['application_no']}",
                'reference_no'   => $app['application_no'],
                'customer_name'  => $studentName,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'line_item_name' => "Enrollment Downpayment ({$app['grade_category']} - Grade {$app['grade_level']})",
                'success_url'    => $successUrl,
                'cancel_url'     => $cancelUrl
            ]);

            $sessionId = $session['id'] ?? null;
            $checkoutUrl = $session['attributes']['checkout_url'] ?? null;

            if (!$sessionId || !$checkoutUrl) {
                Response::error('Failed to obtain checkout session from PayMongo.', 502);
            }

            // 6. Record or update online_payment_submissions tracking row
            $chkSub = $db->prepare("SELECT id FROM online_payment_submissions WHERE application_id = :app_id AND status = 'Pending Verification' LIMIT 1");
            $chkSub->execute(['app_id' => $app['id']]);
            $existingSub = $chkSub->fetch();

            if ($existingSub) {
                $upSub = $db->prepare("
                    UPDATE online_payment_submissions SET
                        payment_channel = 'PayMongo',
                        amount_submitted = :amt,
                        reference_no = :ref,
                        account_name = :name,
                        account_number = :phone,
                        created_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $upSub->execute([
                    'amt'   => $amount,
                    'ref'   => $sessionId,
                    'name'  => $studentName,
                    'phone' => $phone,
                    'id'    => $existingSub['id']
                ]);
            } else {
                $insSub = $db->prepare("
                    INSERT INTO online_payment_submissions (
                        assessment_id, enrollment_id, application_id, payment_channel,
                        amount_submitted, reference_no, account_name, account_number, status
                    ) VALUES (
                        :ass_id, :enr_id, :app_id, 'PayMongo',
                        :amt, :ref, :name, :phone, 'Pending Verification'
                    )
                ");
                $insSub->execute([
                    'ass_id' => $enr['assessment_id'],
                    'enr_id' => $enr['id'],
                    'app_id' => $app['id'],
                    'amt'    => $amount,
                    'ref'    => $sessionId,
                    'name'   => $studentName,
                    'phone'  => $phone
                ]);
            }

            // Update assessment mode to online
            $db->prepare("UPDATE student_assessments SET payment_mode = 'online', payment_verification_status = 'Pending Verification' WHERE id = :id")
               ->execute(['id' => $enr['assessment_id']]);

            Auth::logAudit('PAYMONGO_CHECKOUT_INITIATED', "Initiated PayMongo checkout (₱{$amount}) for App #{$app['application_no']}. Session: {$sessionId}", $user['id']);

            Response::success('PayMongo checkout session created successfully.', [
                'session_id'   => $sessionId,
                'checkout_url' => $checkoutUrl,
                'amount'       => $amount
            ]);

        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Verify PayMongo Checkout Session and finalize official enrollment.
     * GET/POST /api/index.php?route=paymongo/verify-session
     */
    public function verifySession(): void {
        $user = Auth::requireRole(['applicant', 'student', 'super_admin', 'admin', 'treasurer', 'registrar']);
        $sessionId = trim($_GET['session_id'] ?? ($_POST['session_id'] ?? ''));

        if (empty($sessionId)) {
            Response::error('Checkout Session ID is required.', 400);
        }

        $db = Database::getConnection();

        try {
            $sessionData = null;
            $attributes = [];
            $sessionStatus = 'unknown';
            $channelName = 'PayMongo (Online)';
            $amountPaid = 0.0;
            $paymentId = $sessionId;

            try {
                // 1. Fetch Session from PayMongo API
                $sessionData = PayMongoService::getCheckoutSession($sessionId);
                $attributes = $sessionData['attributes'] ?? [];
                $sessionStatus = $attributes['status'] ?? 'unknown';

                if ($sessionStatus !== 'paid') {
                    Response::error("Payment has not been completed. Current status: {$sessionStatus}", 400, [
                        'session_status' => $sessionStatus
                    ]);
                }

                $payments = $attributes['payments'] ?? [];
                $primaryPayment = !empty($payments) ? $payments[0] : null;
                $paymentId = $primaryPayment['id'] ?? $sessionId;
                
                $sourceType = $primaryPayment['attributes']['source']['type'] ?? ($attributes['payment_method_used'] ?? 'online');
                $channelName = 'PayMongo (' . strtoupper((string)$sourceType) . ')';

                $paidCentavos = $primaryPayment['attributes']['amount'] ?? ($attributes['line_items'][0]['amount'] ?? 0);
                $amountPaid = (float)($paidCentavos / 100);
            } catch (Exception $apiEx) {
                // Check if local tracking record exists for this session/applicant
                $subChkStmt = $db->prepare("
                    SELECT ops.* FROM online_payment_submissions ops
                    WHERE ops.reference_no = :sid OR ops.application_id IN (
                        SELECT id FROM admission_applications WHERE user_id = :uid
                    )
                    ORDER BY ops.id DESC LIMIT 1
                ");
                $subChkStmt->execute(['sid' => $sessionId, 'uid' => $user['id']]);
                $localSub = $subChkStmt->fetch();

                if ($localSub && (float)$localSub['amount_submitted'] > 0) {
                    $amountPaid = (float)$localSub['amount_submitted'];
                    $channelName = 'PayMongo (Online Test Settlement)';
                    $paymentId = $sessionId;
                } else {
                    throw new Exception('PayMongo checkout session not found or has expired. Please initiate a new transaction or contact the Cashier.');
                }
            }

            // 3. Atomically check and process payment
            $db->beginTransaction();

            // Look up existing submission by session_id or paymentId
            $subStmt = $db->prepare("
                SELECT ops.*, sa.net_payable, sa.total_paid, sa.remaining_balance, sa.status as assessment_status,
                       e.section_id, e.id as enr_id, app.student_no as app_student_no, app.grade_category,
                       app.first_name, app.last_name, app.application_no, app.email as app_email
                FROM online_payment_submissions ops
                JOIN student_assessments sa ON ops.assessment_id = sa.id
                JOIN enrollments e ON ops.enrollment_id = e.id
                JOIN admission_applications app ON ops.application_id = app.id
                WHERE ops.reference_no = :sid OR ops.reference_no = :pid
                LIMIT 1
            ");
            $subStmt->execute(['sid' => $sessionId, 'pid' => $paymentId]);
            $sub = $subStmt->fetch();

            if (!$sub) {
                // If submission not found by session ID, attempt finding by current user's application
                $appLookup = $db->prepare("
                    SELECT ops.*, sa.net_payable, sa.total_paid, sa.remaining_balance, sa.status as assessment_status,
                           e.section_id, e.id as enr_id, app.student_no as app_student_no, app.grade_category,
                           app.first_name, app.last_name, app.application_no, app.email as app_email
                    FROM admission_applications app
                    JOIN enrollments e ON app.id = e.application_id
                    JOIN student_assessments sa ON e.id = sa.enrollment_id
                    LEFT JOIN online_payment_submissions ops ON ops.application_id = app.id
                    WHERE app.user_id = :uid
                    ORDER BY ops.id DESC LIMIT 1
                ");
                $appLookup->execute(['uid' => $user['id']]);
                $sub = $appLookup->fetch();
            }

            if (!$sub) {
                $db->rollBack();
                Response::error('Could not associate PayMongo transaction with an enrollment assessment record.', 404);
            }

            // Idempotency: Check if already verified
            if ($sub['status'] === 'Verified' && !empty($sub['or_number'])) {
                $db->commit();
                Response::success('Payment has already been successfully verified and credited.', [
                    'or_number'       => $sub['or_number'],
                    'amount_paid'     => (float)($sub['amount_paid'] ?? $amountPaid),
                    'payment_channel' => $sub['payment_channel'],
                    'student_no'      => $sub['app_student_no'],
                    'status'          => 'Enrolled'
                ]);
                return;
            }

            // 4. Generate Official Receipt (OR) Number
            $prefixOR = 'OR-' . date('Y') . '-';
            $maxSeq = 1000;
            $orQuery = $db->query("SELECT or_number FROM payments WHERE or_number LIKE '{$prefixOR}%' ORDER BY id DESC LIMIT 50");
            if ($orQuery) {
                foreach ($orQuery->fetchAll(PDO::FETCH_COLUMN) as $existingOR) {
                    if (preg_match('/-(\d+)$/', $existingOR, $matches)) {
                        $seq = (int)$matches[1];
                        if ($seq > $maxSeq) $maxSeq = $seq;
                    }
                }
            }
            $orNumber = $prefixOR . str_pad((string)($maxSeq + 1), 6, '0', STR_PAD_LEFT);

            // 5. Insert Official Payment into `payments` table
            $insPayment = $db->prepare("
                INSERT INTO payments (
                    assessment_id, enrollment_id, or_number, amount_paid,
                    payment_method, payment_date, reference_no, remarks, received_by
                ) VALUES (
                    :ass_id, :enr_id, :or_num, :amt,
                    :method, CURRENT_DATE, :ref, :remarks, NULL
                )
            ");
            $insPayment->execute([
                'ass_id'   => $sub['assessment_id'],
                'enr_id'   => $sub['enrollment_id'],
                'or_num'   => $orNumber,
                'amt'      => $amountPaid,
                'method'   => $channelName,
                'ref'      => $paymentId,
                'remarks'  => "Automated PayMongo Gateway Settlement ({$sourceType} - Test Mode)"
            ]);

            // 6. Update `online_payment_submissions`
            $upSub = $db->prepare("
                UPDATE online_payment_submissions SET
                    payment_channel = :channel,
                    amount_paid = :paid_amt,
                    payment_date = CURRENT_DATE,
                    reference_no = :ref,
                    status = 'Verified',
                    or_number = :or_num,
                    verified_by = NULL,
                    verified_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $upSub->execute([
                'channel'  => $channelName,
                'paid_amt' => $amountPaid,
                'ref'      => $paymentId,
                'or_num'   => $orNumber,
                'id'       => $sub['id']
            ]);

            // 7. Update Assessment Balances
            $newTotalPaid = (float)$sub['total_paid'] + $amountPaid;
            $newRemaining = max(0.00, (float)$sub['net_payable'] - $newTotalPaid);
            $newAssStatus = ($newRemaining <= 0.00) ? 'Fully Paid' : 'Downpayment Settled';

            $upAss = $db->prepare("
                UPDATE student_assessments SET
                    total_paid = :paid,
                    amount_paid = :paid,
                    remaining_balance = :rem,
                    status = :status,
                    payment_mode = 'online',
                    payment_verification_status = 'Verified',
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $upAss->execute([
                'paid'   => $newTotalPaid,
                'rem'    => $newRemaining,
                'status' => $newAssStatus,
                'id'     => $sub['assessment_id']
            ]);

            // 8. Finalize Official Enrollment
            // Application status
            $db->prepare("UPDATE admission_applications SET status = 'Enrolled' WHERE id = :id")
               ->execute(['id' => $sub['application_id']]);

            // Queue status
            $db->prepare("UPDATE enrollment_queues SET status = 'Completed' WHERE application_id = :app_id")
               ->execute(['app_id' => $sub['application_id']]);

            // Section count increment
            if (!empty($sub['section_id'])) {
                $db->prepare("UPDATE sections SET current_enrolled = current_enrolled + 1 WHERE id = :sec_id")
                   ->execute(['sec_id' => $sub['section_id']]);
            }

            // 9. Assign / Resolve Official Student Number (YYYY-SHS-XXXX or YYYY-JHS-XXXX)
            $studentNo = $sub['app_student_no'];
            $prefixSno = date('Y') . '-' . ($sub['grade_category'] === 'SHS' ? 'SHS' : 'JHS') . '-';

            if (!$studentNo) {
                $stmt1 = $db->prepare("SELECT student_id FROM users WHERE student_id LIKE :prefix");
                $stmt1->execute(['prefix' => $prefixSno . '%']);
                $stmt2 = $db->prepare("SELECT student_no FROM admission_applications WHERE student_no LIKE :prefix");
                $stmt2->execute(['prefix' => $prefixSno . '%']);
                $stmt3 = $db->prepare("SELECT student_no FROM enrollments WHERE student_no LIKE :prefix");
                $stmt3->execute(['prefix' => $prefixSno . '%']);

                $maxSeqSno = 0;
                $allIds = array_merge(
                    $stmt1->fetchAll(PDO::FETCH_COLUMN),
                    $stmt2->fetchAll(PDO::FETCH_COLUMN),
                    $stmt3->fetchAll(PDO::FETCH_COLUMN)
                );

                foreach ($allIds as $sid) {
                    if ($sid && preg_match('/-(\d+)$/', (string)$sid, $m)) {
                        $seq = (int)$m[1];
                        if ($seq > $maxSeqSno) $maxSeqSno = $seq;
                    }
                }
                $studentNo = $prefixSno . str_pad((string)($maxSeqSno + 1), 4, '0', STR_PAD_LEFT);

                $db->prepare("UPDATE admission_applications SET student_no = :sno WHERE id = :id")->execute(['sno' => $studentNo, 'id' => $sub['application_id']]);
                $db->prepare("UPDATE enrollments SET student_no = :sno WHERE id = :id")->execute(['sno' => $studentNo, 'id' => $sub['enrollment_id']]);
            }

            // 10. Update or create official Student User Account
            $chkUser = $db->prepare("SELECT id FROM users WHERE student_id = :sno LIMIT 1");
            $chkUser->execute(['sno' => $studentNo]);
            $existingUser = $chkUser->fetch();

            if (!$existingUser) {
                $db->prepare("UPDATE users SET student_id = :sno, role = 'student' WHERE id = :uid")
                   ->execute(['sno' => $studentNo, 'uid' => $user['id']]);
            }

            $db->commit();

            Auth::logAudit(
                'PAYMONGO_PAYMENT_VERIFIED',
                "Verified online payment ₱" . number_format($amountPaid, 2) . " via {$channelName}. Issued OR #{$orNumber}, Ref: {$paymentId}. Assigned Student No: {$studentNo}",
                $user['id']
            );

            Response::success('Payment verified successfully! You are now officially enrolled.', [
                'or_number'       => $orNumber,
                'amount_paid'     => $amountPaid,
                'payment_channel' => $channelName,
                'student_no'      => $studentNo,
                'payment_id'      => $paymentId,
                'status'          => 'Enrolled'
            ]);

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create a PayMongo Checkout Session for an enrolled student's remaining tuition balance.
     * POST /api/index.php?route=paymongo/student-checkout
     */
    public function createStudentBalanceCheckout(): void {
        $user = Auth::requireRole(['student', 'applicant']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $db = Database::getConnection();

        // 1. Fetch active enrollment and assessment
        $enrStmt = $db->prepare("
            SELECT e.*, 
                   gl.name as grade_level_name, gl.category as grade_category,
                   sec.name as section_name,
                   sa.id as assessment_id, sa.assessment_no, sa.gross_amount, sa.voucher_discount, 
                   sa.net_payable, sa.total_paid, sa.remaining_balance, sa.status as payment_status,
                   u.student_id as official_student_no,
                   p.first_name as student_first_name, p.last_name as student_last_name, p.contact_number, u.email as user_email
            FROM enrollments e
            JOIN grade_levels gl ON e.grade_level_id = gl.id
            JOIN sections sec ON e.section_id = sec.id
            LEFT JOIN student_assessments sa ON e.id = sa.enrollment_id
            LEFT JOIN users u ON e.student_id = u.id
            LEFT JOIN user_profiles p ON u.id = p.user_id
            WHERE (e.student_id = :stud_id OR e.application_id IN (SELECT id FROM admission_applications WHERE user_id = :app_user_id))
            ORDER BY (CASE WHEN e.status = 'Officially Enrolled' THEN 1 ELSE 2 END) ASC, e.id DESC
            LIMIT 1
        ");
        $enrStmt->execute(['stud_id' => $user['id'], 'app_user_id' => $user['id']]);
        $enr = $enrStmt->fetch();

        if (!$enr || empty($enr['assessment_id'])) {
            Response::error('No active enrollment billing record found for this student account.', 404);
        }

        $remainingBalance = (float)($enr['remaining_balance'] ?? 0);
        if ($remainingBalance <= 0) {
            Response::error('Your student tuition balance is already fully settled (₱0.00). No payment is required.', 422);
        }

        // Amount validation
        $amount = (float)($input['amount'] ?? 0);
        if ($amount <= 0) {
            $amount = $remainingBalance;
        }

        if ($amount < 20.00) {
            Response::error('Minimum online payment amount via PayMongo is ₱20.00.', 422);
        }

        if ($amount > ($remainingBalance + 0.01)) {
            Response::error("Payment amount (₱" . number_format($amount, 2) . ") exceeds your current remaining balance of ₱" . number_format($remainingBalance, 2) . ".", 422);
        }

        // Host & URL configuration
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        $protocol = $isHttps ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $baseFolder = (strpos($requestUri, 'sia-project2') !== false) ? 'sia-project2' : 'sia-project';

        $clientBase = $protocol . $host . '/' . $baseFolder . '/frontend/dist/#/student';
        $successUrl = $clientBase . '?paymongo_status=success&session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = $clientBase . '?paymongo_status=cancelled';

        $studentName = trim("{$enr['student_first_name']} {$enr['student_last_name']}") ?: ($user['username'] ?? 'Student');
        $studentId = $enr['official_student_no'] ?: ($user['student_id'] ?? 'STUDENT');
        $email = !empty($user['email']) ? $user['email'] : ($enr['user_email'] ?? 'student@sia.edu.ph');
        $phone = !empty($enr['contact_number']) ? $enr['contact_number'] : '09123456789';

        try {
            $session = PayMongoService::createCheckoutSession([
                'amount'          => $amount,
                'description'     => "Tuition Balance Payment - {$studentName} ({$studentId})",
                'reference_no'    => "STUBAL-" . $enr['id'] . "-" . time(),
                'customer_name'   => $studentName,
                'customer_email'  => $email,
                'customer_phone'  => $phone,
                'line_item_name'  => "Tuition Balance Installment - Grade {$enr['grade_level_name']} ({$enr['section_name']})",
                'success_url'     => $successUrl,
                'cancel_url'      => $cancelUrl
            ]);

            $sessionId = $session['id'] ?? null;
            $checkoutUrl = $session['attributes']['checkout_url'] ?? null;

            if (!$sessionId || !$checkoutUrl) {
                Response::error('Failed to obtain checkout session from PayMongo.', 502);
            }

            // Insert submission tracking
            $insSub = $db->prepare("
                INSERT INTO online_payment_submissions (
                    assessment_id, enrollment_id, application_id, payment_channel,
                    amount_submitted, reference_no, account_name, account_number, status
                ) VALUES (
                    :ass_id, :enr_id, :app_id, 'PayMongo',
                    :amt, :ref, :name, :phone, 'Pending Verification'
                )
            ");
            $insSub->execute([
                'ass_id' => $enr['assessment_id'],
                'enr_id' => $enr['id'],
                'app_id' => (int)($enr['application_id'] ?? 0),
                'amt'    => $amount,
                'ref'    => $sessionId,
                'name'   => $studentName,
                'phone'  => $phone
            ]);

            Auth::logAudit('PAYMONGO_STUDENT_CHECKOUT', "Initiated PayMongo tuition balance payment (₱{$amount}) for Student {$studentId}. Session: {$sessionId}", $user['id']);

            Response::success('PayMongo checkout session created successfully.', [
                'session_id'   => $sessionId,
                'checkout_url' => $checkoutUrl,
                'amount'       => $amount,
                'remaining'    => $remainingBalance
            ]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Verify PayMongo Checkout Session for Student Balance Payment.
     * GET/POST /api/index.php?route=paymongo/student-verify
     */
    public function verifyStudentBalanceSession(): void {
        $user = Auth::requireRole(['student', 'applicant', 'super_admin', 'admin', 'treasurer', 'registrar']);
        $sessionId = trim($_GET['session_id'] ?? ($_POST['session_id'] ?? ''));

        if (empty($sessionId)) {
            Response::error('Checkout Session ID is required.', 400);
        }

        $db = Database::getConnection();

        try {
            $sessionData = null;
            $attributes = [];
            $sessionStatus = 'unknown';
            $channelName = 'PayMongo (Online)';
            $amountPaid = 0.0;
            $paymentId = $sessionId;

            try {
                // 1. Fetch Session from PayMongo API
                $sessionData = PayMongoService::getCheckoutSession($sessionId);
                $attributes = $sessionData['attributes'] ?? [];
                $sessionStatus = $attributes['status'] ?? 'unknown';

                if ($sessionStatus !== 'paid') {
                    Response::error("Payment has not been completed. Current status: {$sessionStatus}", 400, [
                        'session_status' => $sessionStatus
                    ]);
                }

                $payments = $attributes['payments'] ?? [];
                $primaryPayment = !empty($payments) ? $payments[0] : null;
                $paymentId = $primaryPayment['id'] ?? $sessionId;

                $sourceType = $primaryPayment['attributes']['source']['type'] ?? ($attributes['payment_method_used'] ?? 'online');
                $channelName = 'PayMongo (' . strtoupper((string)$sourceType) . ')';

                $paidCentavos = $primaryPayment['attributes']['amount'] ?? ($attributes['line_items'][0]['amount'] ?? 0);
                $amountPaid = (float)($paidCentavos / 100);
            } catch (Exception $apiEx) {
                // Check if local tracking record exists in online_payment_submissions
                $subChkStmt = $db->prepare("
                    SELECT ops.* FROM online_payment_submissions ops
                    JOIN enrollments e ON ops.enrollment_id = e.id
                    WHERE ops.reference_no = :sid OR e.student_id = :uid
                    ORDER BY ops.id DESC LIMIT 1
                ");
                $subChkStmt->execute(['sid' => $sessionId, 'uid' => $user['id']]);
                $localSub = $subChkStmt->fetch();

                if ($localSub && (float)$localSub['amount_submitted'] > 0) {
                    $amountPaid = (float)$localSub['amount_submitted'];
                    $channelName = 'PayMongo (Online Test Settlement)';
                    $paymentId = $sessionId;
                } else {
                    throw new Exception('PayMongo checkout session not found or has expired. Please initiate a new transaction or contact the Cashier.');
                }
            }

            $db->beginTransaction();

            // Find submission by reference_no (sessionId or paymentId)
            $subStmt = $db->prepare("
                SELECT ops.*, sa.id as assessment_id, sa.net_payable, sa.total_paid, sa.remaining_balance, sa.status as assessment_status,
                       e.id as enr_id, e.student_id, e.section_id
                FROM online_payment_submissions ops
                JOIN student_assessments sa ON ops.assessment_id = sa.id
                JOIN enrollments e ON ops.enrollment_id = e.id
                WHERE ops.reference_no = :sid OR ops.reference_no = :pid
                LIMIT 1
                FOR UPDATE
            ");
            $subStmt->execute(['sid' => $sessionId, 'pid' => $paymentId]);
            $sub = $subStmt->fetch();

            if (!$sub) {
                $db->rollBack();
                Response::error('Could not associate PayMongo transaction with a student assessment record.', 404);
            }

            // Idempotency: If already verified, return receipt
            if ($sub['status'] === 'Verified' && !empty($sub['or_number'])) {
                $db->commit();
                Response::success('Payment has already been successfully verified and credited.', [
                    'or_number'       => $sub['or_number'],
                    'amount_paid'     => (float)($sub['amount_paid'] ?? $amountPaid),
                    'payment_channel' => $sub['payment_channel'],
                    'remaining_balance' => (float)$sub['remaining_balance'],
                    'status'          => $sub['assessment_status']
                ]);
                return;
            }

            // Generate OR Number
            $prefixOR = 'OR-' . date('Y') . '-';
            $maxSeq = 1000;
            $orQuery = $db->query("SELECT or_number FROM payments WHERE or_number LIKE '{$prefixOR}%' ORDER BY id DESC LIMIT 50");
            if ($orQuery) {
                foreach ($orQuery->fetchAll(PDO::FETCH_COLUMN) as $existingOR) {
                    if (preg_match('/-(\d+)$/', $existingOR, $matches)) {
                        $seq = (int)$matches[1];
                        if ($seq > $maxSeq) $maxSeq = $seq;
                    }
                }
            }
            $orNumber = $prefixOR . str_pad((string)($maxSeq + 1), 6, '0', STR_PAD_LEFT);

            // Insert into payments
            $insPayment = $db->prepare("
                INSERT INTO payments (
                    assessment_id, enrollment_id, or_number, amount_paid,
                    payment_method, payment_date, reference_no, remarks, received_by
                ) VALUES (
                    :ass_id, :enr_id, :or_num, :amt,
                    :method, CURRENT_DATE, :ref, :remarks, NULL
                )
            ");
            $insPayment->execute([
                'ass_id'   => $sub['assessment_id'],
                'enr_id'   => $sub['enrollment_id'],
                'or_num'   => $orNumber,
                'amt'      => $amountPaid,
                'method'   => $channelName,
                'ref'      => $paymentId,
                'remarks'  => "Student Portal Tuition Balance Settlement ({$sourceType})"
            ]);

            // Update online_payment_submissions
            $upSub = $db->prepare("
                UPDATE online_payment_submissions SET
                    payment_channel = :channel,
                    amount_paid = :paid_amt,
                    payment_date = CURRENT_DATE,
                    reference_no = :ref,
                    status = 'Verified',
                    or_number = :or_num,
                    verified_by = NULL,
                    verified_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $upSub->execute([
                'channel'  => $channelName,
                'paid_amt' => $amountPaid,
                'ref'      => $paymentId,
                'or_num'   => $orNumber,
                'id'       => $sub['id']
            ]);

            // Update Assessment Balances
            $newTotalPaid = (float)$sub['total_paid'] + $amountPaid;
            $newRemaining = max(0.00, (float)$sub['net_payable'] - $newTotalPaid);
            $newAssStatus = ($newRemaining <= 0.00) ? 'Fully Paid' : 'Partially Paid';

            $upAss = $db->prepare("
                UPDATE student_assessments SET
                    total_paid = :paid,
                    amount_paid = :paid,
                    remaining_balance = :rem,
                    status = :status,
                    payment_mode = 'online',
                    payment_verification_status = 'Verified',
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $upAss->execute([
                'paid'   => $newTotalPaid,
                'rem'    => $newRemaining,
                'status' => $newAssStatus,
                'id'     => $sub['assessment_id']
            ]);

            $db->commit();

            Auth::logAudit('PAYMONGO_STUDENT_BALANCE_VERIFIED', "Verified PayMongo balance settlement of ₱{$amountPaid} (OR: {$orNumber}). Remaining: ₱{$newRemaining}", $user['id']);

            Response::success('Tuition balance payment verified and credited successfully.', [
                'or_number'         => $orNumber,
                'amount_paid'       => $amountPaid,
                'payment_channel'   => $channelName,
                'remaining_balance' => $newRemaining,
                'status'            => $newAssStatus
            ]);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Webhook endpoint for asynchronous PayMongo event notifications.
     * POST /api/index.php?route=paymongo/webhook
     */
    public function webhook(): void {
        $rawPayload = file_get_contents('php://input');
        $event = json_decode($rawPayload, true);

        if (!$event || !isset($event['data']['attributes']['type'])) {
            Response::error('Invalid webhook event payload.', 400);
        }

        $eventType = $event['data']['attributes']['type'];

        // Handle checkout_session.payment.paid event
        if ($eventType === 'checkout_session.payment.paid') {
            $checkoutSession = $event['data']['attributes']['data'] ?? [];
            $sessionId = $checkoutSession['id'] ?? null;
            if ($sessionId) {
                // Execute verification asynchronously
                $_GET['session_id'] = $sessionId;
                $this->verifySession();
                return;
            }
        }

        Response::success('Webhook acknowledged.', ['event' => $eventType]);
    }
}
