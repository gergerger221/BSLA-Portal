<?php
// backend/controllers/TreasuryController.php
namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;
use App\Helpers\Auth;
use App\Helpers\Mailer;
use PDO;

class TreasuryController {
    /**
     * Get list of student assessments / billing queue.
     */
    public function getAssessments(): void {
        Auth::requireRole(['treasury', 'admin', 'registrar']);
        $db = Database::getConnection();

        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        $sql = "
            SELECT sa.*, 
                   e.enrollment_no, e.status as enrollment_status, e.lrn,
                   a.first_name, a.middle_name, a.last_name, a.contact_number, a.voucher_status,
                   gl.name as grade_level_name, s.code as strand_code, sec.name as section_name,
                   u.student_id as permanent_student_no,
                   ops.id as online_submission_id, ops.status as online_submission_status,
                   ops.reference_no as online_reference_no, ops.payment_channel as online_channel,
                   ops.amount_submitted as online_amount_submitted, ops.receipt_file_path, ops.receipt_original_name
            FROM student_assessments sa
            JOIN enrollments e ON sa.enrollment_id = e.id
            JOIN admission_applications a ON e.application_id = a.id
            JOIN grade_levels gl ON e.grade_level_id = gl.id
            LEFT JOIN strands s ON e.strand_id = s.id
            JOIN sections sec ON e.section_id = sec.id
            LEFT JOIN users u ON e.student_id = u.id
            LEFT JOIN (
                SELECT ops1.* FROM online_payment_submissions ops1
                INNER JOIN (
                    SELECT MAX(id) as max_id FROM online_payment_submissions GROUP BY assessment_id
                ) ops2 ON ops1.id = ops2.max_id
            ) ops ON sa.id = ops.assessment_id
            WHERE 1=1
        ";
        $params = [];

        if ($status) {
            $sql .= " AND sa.status = :status";
            $params['status'] = $status;
        }

        if ($search) {
            $sql .= " AND (sa.assessment_no LIKE :search OR a.first_name LIKE :search OR a.last_name LIKE :search OR e.enrollment_no LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        $sql .= " ORDER BY sa.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $assessments = $stmt->fetchAll();

        Response::success('Assessments loaded', $assessments);
    }

    /**
     * Get specific assessment details including previous payments and fee breakdown.
     */
    public function getAssessmentDetails(int $id): void {
        $user = Auth::requireRole(['treasury', 'admin', 'registrar', 'applicant', 'student']);
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT sa.*, 
                    e.id as enrollment_id, e.enrollment_no, e.status as enrollment_status, e.lrn, e.student_id as student_user_id,
                    a.user_id as applicant_user_id, a.first_name, a.middle_name, a.last_name, a.contact_number, a.email, a.voucher_status,
                    gl.name as grade_level_name, gl.category as grade_category,
                    s.name as strand_name, s.code as strand_code,
                    sec.name as section_name, sec.room as section_room,
                    sy.name as school_year_name, sy.active_semester,
                    u.student_id as permanent_student_no, u.username
            FROM student_assessments sa
            JOIN enrollments e ON sa.enrollment_id = e.id
            JOIN admission_applications a ON e.application_id = a.id
            JOIN grade_levels gl ON e.grade_level_id = gl.id
            LEFT JOIN strands s ON e.strand_id = s.id
            JOIN sections sec ON e.section_id = sec.id
            JOIN school_years sy ON sa.school_year_id = sy.id
            LEFT JOIN users u ON e.student_id = u.id
            WHERE sa.id = :id OR sa.enrollment_id = :id2
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'id2' => $id]);
        $assessment = $stmt->fetch();

        if (!$assessment) {
            Response::error('Assessment not found', 404);
        }

        // IDOR Protection: Applicants and students can only view their own assessment
        if (in_array($user['role_slug'], ['applicant', 'student'])) {
            if ((int)$assessment['applicant_user_id'] !== (int)$user['id'] && (int)$assessment['student_user_id'] !== (int)$user['id']) {
                Response::error('Forbidden: You can only view your own assessment.', 403);
            }
        }

        // Fetch payments made for this assessment
        $payStmt = $db->prepare("
            SELECT p.*, u.username as received_by_user
            FROM payments p
            JOIN users u ON p.received_by = u.id
            WHERE p.assessment_id = :ass_id
            ORDER BY p.id DESC
        ");
        $payStmt->execute(['ass_id' => $assessment['id']]);
        $assessment['payments'] = $payStmt->fetchAll();

        // Fetch enrolled subjects
        $subStmt = $db->prepare("
            SELECT es.*, s.code as subject_code, s.title as subject_title, s.units, s.category,
                    sch.day_of_week, sch.time_start, sch.time_end, sch.room
            FROM enrollment_subjects es
            JOIN subjects s ON es.subject_id = s.id
            LEFT JOIN schedules sch ON es.schedule_id = sch.id
            WHERE es.enrollment_id = :enr_id
        ");
        $subStmt->execute(['enr_id' => $assessment['enrollment_id']]);
        $assessment['subjects'] = $subStmt->fetchAll();

        // Fetch latest online payment submission if any
        $opsStmt = $db->prepare("
            SELECT * FROM online_payment_submissions
            WHERE assessment_id = :ass_id
            ORDER BY id DESC LIMIT 1
        ");
        $opsStmt->execute(['ass_id' => $assessment['id']]);
        $assessment['online_payment_submission'] = $opsStmt->fetch() ?: null;

        Response::success('Assessment details loaded', $assessment);
    }

    /**
     * Step in Workflow:
     * Payment > Enrollment Successful > Student Account Created
     */
    public function processPayment(): void {
        $cashier = Auth::requireRole(['treasury', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $assessmentId = (int)($input['assessment_id'] ?? 0);
        $amountPaid = (float)($input['amount_paid'] ?? 0.0);
        $paymentMethod = $input['payment_method'] ?? 'Cash';
        $referenceNo = trim($input['reference_no'] ?? '');
        $remarks = trim($input['remarks'] ?? 'Enrollment Initial Payment');

        if (!$assessmentId || $amountPaid <= 0) {
            Response::error('Valid assessment ID and payment amount are required.');
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $assStmt = $db->prepare("
                SELECT sa.*, e.application_id, e.section_id, e.grade_level_id, e.strand_id, e.lrn, e.student_no,
                       a.user_id as applicant_user_id, a.first_name, a.middle_name, a.last_name, a.student_no as app_student_no,
                       gl.category as grade_category
                FROM student_assessments sa
                JOIN enrollments e ON sa.enrollment_id = e.id
                JOIN admission_applications a ON e.application_id = a.id
                JOIN grade_levels gl ON e.grade_level_id = gl.id
                WHERE sa.id = :id
                FOR UPDATE
            ");
            $assStmt->execute(['id' => $assessmentId]);
            $ass = $assStmt->fetch();

            if (!$ass) {
                Response::error('Assessment record not found.');
            }

            $remainingBalance = (float)$ass['remaining_balance'];
            if ($amountPaid > $remainingBalance) {
                Response::error("Payment amount (₱" . number_format($amountPaid, 2) . ") cannot exceed the remaining balance of ₱" . number_format($remainingBalance, 2) . ".");
            }

            // Generate Official Receipt (OR) Number
            $payCount = $db->query("SELECT COUNT(*) FROM payments")->fetchColumn();
            $orNumber = 'OR-' . date('Y') . '-' . str_pad((string)((int)$payCount + 1001), 6, '0', STR_PAD_LEFT);

            // Record Payment
            $payIns = $db->prepare("
                INSERT INTO payments (assessment_id, enrollment_id, or_number, payment_date, amount_paid, payment_method, reference_no, remarks, received_by)
                VALUES (:ass_id, :enr_id, :or_num, CURRENT_DATE, :amount, :method, :ref, :remarks, :rec_by)
            ");
            $payIns->execute([
                'ass_id'   => $assessmentId,
                'enr_id'   => $ass['enrollment_id'],
                'or_num'   => $orNumber,
                'amount'   => $amountPaid,
                'method'   => $paymentMethod,
                'ref'      => $referenceNo,
                'remarks'  => $remarks,
                'rec_by'   => $cashier['id']
            ]);

            // Update Assessment totals and status
            $newTotalPaid = (float)$ass['total_paid'] + $amountPaid;
            $newRemaining = max(0.00, (float)$ass['net_payable'] - $newTotalPaid);
            $newStatus = ($newRemaining <= 0.00) ? 'Fully Paid' : 'Partially Paid';

            $upAss = $db->prepare("
                UPDATE student_assessments SET
                    total_paid = :paid,
                    remaining_balance = :rem,
                    status = :status
                WHERE id = :id
            ");
            $upAss->execute([
                'paid'   => $newTotalPaid,
                'rem'    => $newRemaining,
                'status' => $newStatus,
                'id'     => $assessmentId
            ]);

            // If minimum downpayment or full payment met, finalize Official Enrollment and create SEPARATE Student Account
            if ($newTotalPaid >= (float)$ass['minimum_downpayment']) {
                // 1. Update Application record
                $upApp = $db->prepare("UPDATE admission_applications SET status = 'Enrolled' WHERE id = :id");
                $upApp->execute(['id' => $ass['application_id']]);

                // 2. Update Queue status
                $upQ = $db->prepare("UPDATE enrollment_queues SET status = 'Completed' WHERE application_id = :app_id");
                $upQ->execute(['app_id' => $ass['application_id']]);

                // 3. Increment section current_enrolled count
                $upSec = $db->prepare("UPDATE sections SET current_enrolled = current_enrolled + 1 WHERE id = :sec_id");
                $upSec->execute(['sec_id' => $ass['section_id']]);

                // 4. Determine Student Number (generated by Registrar)
                $studentNo = $ass['student_no'] ?: $ass['app_student_no'];
                $prefix = date('Y') . '-' . ($ass['grade_category'] === 'SHS' ? 'SHS' : 'JHS') . '-';

                // Verify if student number already belongs to another person in users table
                $chkConflict = $db->prepare("
                    SELECT u.id, p.last_name, p.first_name 
                    FROM users u 
                    LEFT JOIN user_profiles p ON u.id = p.user_id 
                    WHERE u.student_id = :sid OR u.username = :uname
                ");
                $chkConflict->execute(['sid' => $studentNo, 'uname' => $studentNo]);
                $conflictUser = $chkConflict->fetch();

                // If conflict with a different student, or if studentNo is missing, generate next unique student number
                if (!$studentNo || ($conflictUser && strtolower(trim($conflictUser['last_name'] ?? '')) !== strtolower(trim($ass['last_name'])))) {
                    $stmt1 = $db->prepare("SELECT student_id FROM users WHERE student_id LIKE :prefix");
                    $stmt1->execute(['prefix' => $prefix . '%']);
                    $stmt2 = $db->prepare("SELECT student_no FROM admission_applications WHERE student_no LIKE :prefix");
                    $stmt2->execute(['prefix' => $prefix . '%']);
                    $stmt3 = $db->prepare("SELECT student_no FROM enrollments WHERE student_no LIKE :prefix");
                    $stmt3->execute(['prefix' => $prefix . '%']);
                    
                    $maxSeq = 0;
                    $allIds = array_merge(
                        $stmt1->fetchAll(PDO::FETCH_COLUMN),
                        $stmt2->fetchAll(PDO::FETCH_COLUMN),
                        $stmt3->fetchAll(PDO::FETCH_COLUMN)
                    );
                    
                    foreach ($allIds as $sid) {
                        if ($sid && preg_match('/-(\d+)$/', $sid, $m)) {
                            $seq = (int)$m[1];
                            if ($seq > $maxSeq) $maxSeq = $seq;
                        }
                    }
                    $studentNo = $prefix . str_pad((string)($maxSeq + 1), 4, '0', STR_PAD_LEFT);
                    
                    // Sync back to application and enrollment
                    $db->prepare("UPDATE admission_applications SET student_no = :sno WHERE id = :id")->execute(['sno' => $studentNo, 'id' => $ass['application_id']]);
                    $db->prepare("UPDATE enrollments SET student_no = :sno WHERE id = :id")->execute(['sno' => $studentNo, 'id' => $ass['enrollment_id']]);
                }

                // 5. Create Separate Official Student Account (Default password: Student's Last Name in ALL CAPS)
                $rawPassword = strtoupper(trim($ass['last_name'] ?? 'STUDENT123'));
                $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);
                $studentEmail = strtolower(str_replace('-', '', $studentNo)) . '@student.sia.edu.ph';

                $chkUser = $db->prepare("
                    SELECT u.id FROM users u
                    JOIN user_profiles p ON u.id = p.user_id
                    WHERE u.student_id = :sid AND LOWER(p.last_name) = LOWER(:last_name)
                ");
                $chkUser->execute(['sid' => $studentNo, 'last_name' => $ass['last_name']]);
                $existingStudent = $chkUser->fetch();

                if ($existingStudent) {
                    $studentUserId = (int)$existingStudent['id'];
                    $upUser = $db->prepare("UPDATE users SET password = :pw, status = 'Active' WHERE id = :id");
                    $upUser->execute(['pw' => $hashedPassword, 'id' => $studentUserId]);
                } else {
                    $insUser = $db->prepare("
                        INSERT INTO users (role_id, username, email, password, student_id, status)
                        VALUES (7, :username, :email, :password, :student_id, 'Active')
                    ");
                    $insUser->execute([
                        'username'   => $studentNo,
                        'email'      => $studentEmail,
                        'password'   => $hashedPassword,
                        'student_id' => $studentNo
                    ]);
                    $studentUserId = (int)$db->lastInsertId();

                    $insProf = $db->prepare("
                        INSERT INTO user_profiles (user_id, first_name, middle_name, last_name)
                        VALUES (:user_id, :first_name, :middle_name, :last_name)
                    ");
                    $insProf->execute([
                        'user_id'     => $studentUserId,
                        'first_name'  => $ass['first_name'],
                        'middle_name' => $ass['middle_name'] ?? null,
                        'last_name'   => $ass['last_name']
                    ]);
                }

                // 6. Update Enrollment record to Officially Enrolled and link to the new official student user
                $upEnr = $db->prepare("UPDATE enrollments SET status = 'Officially Enrolled', student_id = :stud_user_id, student_no = :stud_no, approved_by = :app_by WHERE id = :id");
                $upEnr->execute([
                    'stud_user_id' => $studentUserId,
                    'stud_no'      => $studentNo,
                    'app_by'       => $cashier['id'],
                    'id'           => $ass['enrollment_id']
                ]);

                // 7. Initialize DepEd SF9/SF10 Student Record for official student
                $chkRec = $db->prepare("SELECT id FROM student_records WHERE student_id = :sid AND school_year_id = :sy_id LIMIT 1");
                $chkRec->execute(['sid' => $studentUserId, 'sy_id' => $ass['school_year_id']]);
                $existingRec = $chkRec->fetch();

                if ($existingRec) {
                    $db->prepare("
                        UPDATE student_records SET
                            lrn = :lrn,
                            grade_level_id = :gl_id,
                            strand_id = :strand_id,
                            section_id = :sec_id
                        WHERE id = :id
                    ")->execute([
                        'lrn'       => $ass['lrn'] ?? null,
                        'gl_id'     => $ass['grade_level_id'],
                        'strand_id' => $ass['strand_id'],
                        'sec_id'    => $ass['section_id'],
                        'id'        => $existingRec['id']
                    ]);
                } else {
                    $db->prepare("
                        INSERT INTO student_records (
                            student_id, lrn, school_year_id, grade_level_id, strand_id, section_id, promotion_status
                        ) VALUES (
                            :sid, :lrn, :sy_id, :gl_id, :strand_id, :sec_id, 'Pending'
                        )
                    ")->execute([
                        'sid'       => $studentUserId,
                        'lrn'       => $ass['lrn'] ?? null,
                        'sy_id'     => $ass['school_year_id'],
                        'gl_id'     => $ass['grade_level_id'],
                        'strand_id' => $ass['strand_id'],
                        'sec_id'    => $ass['section_id']
                    ]);
                }
            }

            $db->commit();
            Auth::logAudit('PAYMENT_PROCESSED', "OR {$orNumber} for Assessment #{$assessmentId}, Amount: PHP {$amountPaid}", $cashier['id']);

            // Dispatch Official Enrollment & COR Email (Fail-safe)
            Mailer::sendOfficialEnrollment([
                'first_name' => $ass['first_name'] ?? '',
                'last_name'  => $ass['last_name'] ?? '',
                'email'      => $ass['email'] ?? '',
                'student_id' => $studentNo ?? $permanentStudentId ?? ''
            ], [
                'or_number'   => $orNumber,
                'amount_paid' => $amountPaid
            ]);

            Response::success('Payment processed successfully! Student is now Officially Enrolled.', [
                'or_number'            => $orNumber,
                'amount_paid'          => $amountPaid,
                'remaining_balance'    => $newRemaining,
                'assessment_status'    => $newStatus,
                'student_no'           => $studentNo ?? null,
                'student_user_id'      => $studentUserId ?? null,
                // VULN-03 fix: Password should only be communicated via secure email, not API response
                'password_info'        => 'Temporary password has been generated and should be communicated securely to the student.',
                'permanent_student_id' => $permanentStudentId ?? null
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            Response::error('Payment processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Manage Fee Structures (Tuition, Misc, Lab fees)
     */
    public function getFeeStructures(): void {
        Auth::requireRole(['treasury', 'admin', 'coordinator']);
        $db = Database::getConnection();

        $fees = $db->query("
            SELECT fs.*, fc.name as category_name, fc.code as category_code,
                   gl.name as grade_level_name, s.code as strand_code, sy.name as school_year_name
            FROM fee_structures fs
            JOIN fee_categories fc ON fs.fee_category_id = fc.id
            JOIN grade_levels gl ON fs.grade_level_id = gl.id
            LEFT JOIN strands s ON fs.strand_id = s.id
            JOIN school_years sy ON fs.school_year_id = sy.id
            ORDER BY fs.grade_level_id ASC, fs.strand_id ASC, fs.fee_category_id ASC
        ")->fetchAll();

        $categories = $db->query("SELECT * FROM fee_categories")->fetchAll();

        Response::success('Fee structures loaded', [
            'fees'       => $fees,
            'categories' => $categories
        ]);
    }

    /**
     * Get Online Payment Submissions awaiting verification or audit history.
     */
    public function getOnlinePaymentVerifications(): void {
        Auth::requireRole(['treasury', 'admin']);
        $db = Database::getConnection();

        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        $sql = "
            SELECT ops.*,
                   sa.assessment_no, sa.gross_amount, sa.net_payable, sa.total_paid, sa.remaining_balance, sa.minimum_downpayment,
                   e.enrollment_no, e.student_no,
                   a.first_name, a.middle_name, a.last_name, a.contact_number, a.email, a.application_no,
                   gl.name as grade_level_name, s.code as strand_code,
                   u.username as verified_by_username
            FROM online_payment_submissions ops
            JOIN student_assessments sa ON ops.assessment_id = sa.id
            JOIN enrollments e ON ops.enrollment_id = e.id
            JOIN admission_applications a ON ops.application_id = a.id
            JOIN grade_levels gl ON e.grade_level_id = gl.id
            LEFT JOIN strands s ON e.strand_id = s.id
            LEFT JOIN users u ON ops.verified_by = u.id
            WHERE 1=1
        ";
        $params = [];

        if ($status) {
            $sql .= " AND ops.status = :status";
            $params['status'] = $status;
        }

        if ($search) {
            $sql .= " AND (ops.reference_no LIKE :search OR a.first_name LIKE :search OR a.last_name LIKE :search OR a.application_no LIKE :search OR sa.assessment_no LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        $sql .= " ORDER BY ops.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $submissions = $stmt->fetchAll();

        Response::success('Online payment verifications loaded', $submissions);
    }

    /**
     * Treasury Action: Verify (Approve & Officially Enroll) or Reject an Online Payment.
     */
    public function verifyOnlinePayment(): void {
        $staff = Auth::requireRole(['treasury', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $submissionId = (int)($input['submission_id'] ?? 0);
        $action = trim($input['action'] ?? 'approve'); // 'approve' or 'reject'
        $rejectionReason = trim($input['rejection_reason'] ?? '');

        if (!$submissionId) {
            Response::error('Submission ID is required.');
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                SELECT ops.*, 
                       sa.net_payable, sa.total_paid, sa.remaining_balance, sa.minimum_downpayment,
                       e.id as enr_id, e.student_no, e.section_id, e.grade_level_id, e.strand_id, e.school_year_id,
                       a.id as app_id, a.first_name, a.middle_name, a.last_name, a.application_no, a.student_no as app_student_no, a.lrn,
                       gl.category as grade_category
                FROM online_payment_submissions ops
                JOIN student_assessments sa ON ops.assessment_id = sa.id
                JOIN enrollments e ON ops.enrollment_id = e.id
                JOIN admission_applications a ON ops.application_id = a.id
                JOIN grade_levels gl ON e.grade_level_id = gl.id
                WHERE ops.id = :id
                FOR UPDATE
            ");
            $stmt->execute(['id' => $submissionId]);
            $sub = $stmt->fetch();

            if (!$sub) {
                $db->rollBack();
                Response::error('Payment submission record not found.');
            }

            if ($action === 'reject') {
                if (!$rejectionReason) {
                    $db->rollBack();
                    Response::error('Please provide a reason for rejecting or flagging this payment.', 422);
                }

                $upSub = $db->prepare("
                    UPDATE online_payment_submissions SET
                        status = 'Rejected',
                        rejection_reason = :reason,
                        verified_by = :staff_id,
                        verified_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $upSub->execute([
                    'reason'   => $rejectionReason,
                    'staff_id' => $staff['id'],
                    'id'       => $submissionId
                ]);

                // Update Assessment & Application
                $db->prepare("
                    UPDATE student_assessments SET
                        status = 'Unpaid'
                    WHERE id = :id
                ")->execute(['id' => $sub['assessment_id']]);

                $db->prepare("
                    UPDATE admission_applications SET
                        remarks = :reason
                    WHERE id = :id
                ")->execute(['reason' => $rejectionReason, 'id' => $sub['app_id']]);

                $db->commit();
                Auth::logAudit('ONLINE_PAYMENT_REJECTED', "Rejected payment Ref: {$sub['reference_no']} for App #{$sub['application_no']}. Reason: {$rejectionReason}", $staff['id']);

                Response::success('Payment marked as Verification Failed / Needs Review. Student has been notified to re-submit valid payment details.', [
                    'status'           => 'Rejected',
                    'rejection_reason' => $rejectionReason
                ]);
                return;
            }

            // APPROVE PAYMENT
            // 1. Validate that receipt proof exists and is accessible
            if (empty($sub['receipt_file_path'])) {
                $db->rollBack();
                Response::error('Payment proof is missing. Treasury cannot approve an online payment without an uploaded receipt.', 422);
            }

            $diskPath = __DIR__ . '/../' . ltrim($sub['receipt_file_path'], '/');
            if (!file_exists($diskPath)) {
                $db->rollBack();
                Response::error('Payment proof file could not be found on the server. Please ask the applicant to re-upload their receipt.', 422);
            }

            $amountPaid = (float)($sub['amount_paid'] ?? $sub['amount_submitted'] ?? 0);
            if ($amountPaid <= 0) {
                $amountPaid = (float)($sub['minimum_downpayment'] ?? 3000.00);
            }

            // Generate Official Receipt (OR) Number
            $payCount = $db->query("SELECT COUNT(*) FROM payments")->fetchColumn();
            $orNumber = 'OR-' . date('Y') . '-' . str_pad((string)((int)$payCount + 1001), 6, '0', STR_PAD_LEFT);

            // Record into payments table
            $insPay = $db->prepare("
                INSERT INTO payments (
                    assessment_id, enrollment_id, or_number, payment_date,
                    amount_paid, payment_method, reference_no, remarks, received_by
                ) VALUES (
                    :ass_id, :enr_id, :or_num, CURRENT_DATE,
                    :amt, :method, :ref, :remarks, :rec_by
                )
            ");
            $insPay->execute([
                'ass_id'   => $sub['assessment_id'],
                'enr_id'   => $sub['enr_id'],
                'or_num'   => $orNumber,
                'amt'      => $amountPaid,
                'method'   => "Online PayMongo - {$sub['payment_channel']}",
                'ref'      => $sub['reference_no'],
                'remarks'  => "Online Payment via {$sub['payment_channel']} (Verified by Treasury)",
                'rec_by'   => $staff['id']
            ]);

            // Update online_payment_submissions
            $upSub = $db->prepare("
                UPDATE online_payment_submissions SET
                    status = 'Approved',
                    verified_by = :staff_id,
                    verified_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $upSub->execute([
                'staff_id' => $staff['id'],
                'id'       => $submissionId
            ]);

            // Update Assessment
            $newTotalPaid = (float)$sub['total_paid'] + $amountPaid;
            $newRemaining = max(0.00, (float)$sub['net_payable'] - $newTotalPaid);
            $newAssStatus = ($newRemaining <= 0.00) ? 'Fully Paid' : 'Partially Paid';

            $upAss = $db->prepare("
                UPDATE student_assessments SET
                    total_paid = :paid,
                    remaining_balance = :rem,
                    status = :status
                WHERE id = :id
            ");
            $upAss->execute([
                'paid'   => $newTotalPaid,
                'rem'    => $newRemaining,
                'status' => $newAssStatus,
                'id'     => $sub['assessment_id']
            ]);

            // Finalize Official Enrollment
            // 1. Update Application status
            $db->prepare("UPDATE admission_applications SET status = 'Enrolled' WHERE id = :id")->execute(['id' => $sub['app_id']]);

            // 2. Update Queue status
            $db->prepare("UPDATE enrollment_queues SET status = 'Completed' WHERE application_id = :app_id")->execute(['app_id' => $sub['app_id']]);

            // 3. Increment section count
            if (!empty($sub['section_id'])) {
                $db->prepare("UPDATE sections SET current_enrolled = current_enrolled + 1 WHERE id = :sec_id")->execute(['sec_id' => $sub['section_id']]);
            }

            // 4. Student Number resolution
            $studentNo = $sub['student_no'] ?: $sub['app_student_no'];
            $prefix = date('Y') . '-' . ($sub['grade_category'] === 'SHS' ? 'SHS' : 'JHS') . '-';

            if (!$studentNo) {
                $stmt1 = $db->prepare("SELECT student_id FROM users WHERE student_id LIKE :prefix");
                $stmt1->execute(['prefix' => $prefix . '%']);
                $stmt2 = $db->prepare("SELECT student_no FROM admission_applications WHERE student_no LIKE :prefix");
                $stmt2->execute(['prefix' => $prefix . '%']);
                $stmt3 = $db->prepare("SELECT student_no FROM enrollments WHERE student_no LIKE :prefix");
                $stmt3->execute(['prefix' => $prefix . '%']);
                
                $maxSeq = 0;
                $allIds = array_merge(
                    $stmt1->fetchAll(PDO::FETCH_COLUMN),
                    $stmt2->fetchAll(PDO::FETCH_COLUMN),
                    $stmt3->fetchAll(PDO::FETCH_COLUMN)
                );
                
                foreach ($allIds as $sid) {
                    if ($sid && preg_match('/-(\d+)$/', $sid, $m)) {
                        $seq = (int)$m[1];
                        if ($seq > $maxSeq) $maxSeq = $seq;
                    }
                }
                $studentNo = $prefix . str_pad((string)($maxSeq + 1), 4, '0', STR_PAD_LEFT);
                
                $db->prepare("UPDATE admission_applications SET student_no = :sno WHERE id = :id")->execute(['sno' => $studentNo, 'id' => $sub['app_id']]);
                $db->prepare("UPDATE enrollments SET student_no = :sno WHERE id = :id")->execute(['sno' => $studentNo, 'id' => $sub['enr_id']]);
            }

            // 5. Create / update dedicated Official Student Account (Default password: Student's Last Name in ALL CAPS)
            $rawPassword = strtoupper(trim($sub['last_name'] ?? 'STUDENT123'));
            $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);
            $studentEmail = strtolower(str_replace('-', '', $studentNo)) . '@student.bsla.edu.ph';

            $chkUser = $db->prepare("
                SELECT u.id FROM users u
                JOIN user_profiles p ON u.id = p.user_id
                WHERE u.student_id = :sid AND LOWER(p.last_name) = LOWER(:last_name)
            ");
            $chkUser->execute(['sid' => $studentNo, 'last_name' => $sub['last_name']]);
            $existingStudent = $chkUser->fetch();

            if ($existingStudent) {
                $studentUserId = (int)$existingStudent['id'];
                $db->prepare("UPDATE users SET password = :pw, status = 'Active' WHERE id = :id")->execute(['pw' => $hashedPassword, 'id' => $studentUserId]);
            } else {
                $insUser = $db->prepare("
                    INSERT INTO users (role_id, username, email, password, student_id, status)
                    VALUES (7, :username, :email, :password, :student_id, 'Active')
                ");
                $insUser->execute([
                    'username'   => $studentNo,
                    'email'      => $studentEmail,
                    'password'   => $hashedPassword,
                    'student_id' => $studentNo
                ]);
                $studentUserId = (int)$db->lastInsertId();

                $insProf = $db->prepare("
                    INSERT INTO user_profiles (user_id, first_name, middle_name, last_name)
                    VALUES (:user_id, :first_name, :middle_name, :last_name)
                ");
                $insProf->execute([
                    'user_id'     => $studentUserId,
                    'first_name'  => $sub['first_name'],
                    'middle_name' => $sub['middle_name'] ?? null,
                    'last_name'   => $sub['last_name']
                ]);
            }

            // 6. Update Enrollment record to Officially Enrolled
            $upEnr = $db->prepare("
                UPDATE enrollments SET
                    status = 'Officially Enrolled',
                    student_id = :stud_user_id,
                    student_no = :stud_no,
                    approved_by = :app_by
                WHERE id = :id
            ");
            $upEnr->execute([
                'stud_user_id' => $studentUserId,
                'stud_no'      => $studentNo,
                'app_by'       => $staff['id'],
                'id'           => $sub['enr_id']
            ]);

            // 7. Initialize DepEd SF9/SF10 Student Record for official student
            $chkRec = $db->prepare("SELECT id FROM student_records WHERE student_id = :sid AND school_year_id = :sy_id LIMIT 1");
            $chkRec->execute(['sid' => $studentUserId, 'sy_id' => $sub['school_year_id']]);
            $existingRec = $chkRec->fetch();

            if ($existingRec) {
                $db->prepare("
                    UPDATE student_records SET
                        lrn = :lrn,
                        grade_level_id = :gl_id,
                        strand_id = :strand_id,
                        section_id = :sec_id
                    WHERE id = :id
                ")->execute([
                    'lrn'       => $sub['lrn'] ?? null,
                    'gl_id'     => $sub['grade_level_id'],
                    'strand_id' => $sub['strand_id'],
                    'sec_id'    => $sub['section_id'],
                    'id'        => $existingRec['id']
                ]);
            } else {
                $db->prepare("
                    INSERT INTO student_records (
                        student_id, lrn, school_year_id, grade_level_id, strand_id, section_id, promotion_status
                    ) VALUES (
                        :sid, :lrn, :sy_id, :gl_id, :strand_id, :sec_id, 'Pending'
                    )
                ")->execute([
                    'sid'       => $studentUserId,
                    'lrn'       => $sub['lrn'] ?? null,
                    'sy_id'     => $sub['school_year_id'],
                    'gl_id'     => $sub['grade_level_id'],
                    'strand_id' => $sub['strand_id'],
                    'sec_id'    => $sub['section_id']
                ]);
            }

            $db->commit();
            Auth::logAudit('ONLINE_PAYMENT_VERIFIED', "Verified OR {$orNumber} (Ref: {$sub['reference_no']}) for {$sub['first_name']} {$sub['last_name']}, Student No: {$studentNo}", $staff['id']);

            // Dispatch Official Enrollment & COR Email (Fail-safe)
            Mailer::sendOfficialEnrollment([
                'first_name' => $sub['first_name'] ?? '',
                'last_name'  => $sub['last_name'] ?? '',
                'email'      => $sub['email'] ?? '',
                'student_id' => $studentNo ?? ''
            ], [
                'or_number'   => $orNumber,
                'amount_paid' => $amountPaid
            ]);

            Response::success('Online payment verified successfully! Student is now Officially Enrolled.', [
                'or_number'       => $orNumber,
                'student_no'      => $studentNo,
                'amount_paid'     => $amountPaid,
                'student_user_id' => $studentUserId,
                // VULN-03 fix: Password should only be communicated via secure email
                'password_info'   => 'Temporary password has been generated and should be communicated securely to the student.'
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            Response::error('Payment verification failed: ' . $e->getMessage());
        }
    }
}
