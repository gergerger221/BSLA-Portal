<?php
// backend/controllers/RegistrarController.php
namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;
use App\Helpers\Auth;
use App\Helpers\Mailer;
use PDO;

class RegistrarController {
    /**
     * Get list of all admission applications for Registrar review.
     */
    public function getApplications(): void {
        Auth::requireRole(['registrar', 'admin', 'coordinator']);
        $db = Database::getConnection();

        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        $sql = "
            SELECT a.*, 
                   a.birthdate as birth_date,
                   a.birthplace as birth_place,
                   a.address_street as address_line1,
                   a.address_barangay as barangay,
                   a.address_city as city,
                   a.address_province as province,
                   a.address_zip as postal_code,
                   a.guardian_relationship as guardian_relation,
                   gl.name as grade_level_name, gl.category as grade_category,
                   s.name as strand_name, s.code as strand_code,
                   (SELECT COUNT(*) FROM admission_documents WHERE application_id = a.id) as doc_count,
                   (SELECT COUNT(*) FROM admission_documents WHERE application_id = a.id AND status = 'Verified') as verified_doc_count
            FROM admission_applications a
            JOIN grade_levels gl ON a.grade_level_id = gl.id
            LEFT JOIN strands s ON a.strand_id = s.id
            WHERE 1=1
        ";
        $params = [];

        if ($status) {
            $sql .= " AND a.status = :status";
            $params['status'] = $status;
        }

        if ($search) {
            $sql .= " AND (a.application_no LIKE :search OR a.first_name LIKE :search OR a.last_name LIKE :search OR a.lrn LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        $sql .= " ORDER BY a.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $applications = $stmt->fetchAll();

        Response::success('Applications loaded', $applications);
    }

    /**
     * Get specific application details with all documents for evaluation.
     */
    public function getApplicationDetails(int $id): void {
        Auth::requireRole(['registrar', 'admin', 'coordinator', 'treasury']);
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT a.*, 
                   a.birthdate as birth_date,
                   a.birthplace as birth_place,
                   a.address_street as address_line1,
                   a.address_barangay as barangay,
                   a.address_city as city,
                   a.address_province as province,
                   a.address_zip as postal_code,
                   a.guardian_relationship as guardian_relation,
                   gl.name as grade_level_name, gl.category as grade_category,
                   t.name as track_name,
                   s.name as strand_name, s.code as strand_code,
                   sy.name as school_year_name, sy.active_semester,
                   u.username as applicant_username
            FROM admission_applications a
            JOIN grade_levels gl ON a.grade_level_id = gl.id
            LEFT JOIN tracks t ON a.track_id = t.id
            LEFT JOIN strands s ON a.strand_id = s.id
            JOIN school_years sy ON a.school_year_id = sy.id
            JOIN users u ON a.user_id = u.id
            WHERE a.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $app = $stmt->fetch();

        if (!$app) {
            Response::error('Application not found', 404);
        }

        // Fetch documents
        $docStmt = $db->prepare("
            SELECT d.*, u.username as verified_by_user
            FROM admission_documents d
            LEFT JOIN users u ON d.verified_by = u.id
            WHERE d.application_id = :id
            ORDER BY d.id ASC
        ");
        $docStmt->execute(['id' => $id]);
        $app['documents'] = $docStmt->fetchAll();

        // Fetch available sections for this grade level and strand
        $secStmt = $db->prepare("
            SELECT id, name, max_capacity, current_enrolled, room
            FROM sections
            WHERE school_year_id = :sy_id AND grade_level_id = :gl_id
              AND (strand_id = :strand_id OR (strand_id IS NULL AND :strand_id_null IS NULL))
              AND is_active = 1
        ");
        $secStmt->execute([
            'sy_id'          => $app['school_year_id'],
            'gl_id'          => $app['grade_level_id'],
            'strand_id'      => $app['strand_id'],
            'strand_id_null' => $app['strand_id']
        ]);
        $app['available_sections'] = $secStmt->fetchAll();

        // Fetch queue info and assigned section
        $queueStmt = $db->prepare("
            SELECT q.*, sec.name as section_name, sec.room as section_room
            FROM enrollment_queues q
            LEFT JOIN sections sec ON q.assigned_section_id = sec.id
            WHERE q.application_id = :id
            LIMIT 1
        ");
        $queueStmt->execute(['id' => $id]);
        $app['queue_info'] = $queueStmt->fetch() ?: null;

        // Fetch enrollment, enrolled subjects, and assessment
        $enrStmt = $db->prepare("
            SELECT e.*, sec.name as section_name, sec.room as section_room
            FROM enrollments e
            LEFT JOIN sections sec ON e.section_id = sec.id
            WHERE e.application_id = :id
            LIMIT 1
        ");
        $enrStmt->execute(['id' => $id]);
        $enrollment = $enrStmt->fetch();
        $app['enrollment_info'] = $enrollment ?: null;

        if ($enrollment) {
            // Fetch enrolled subjects with schedules
            $subStmt = $db->prepare("
                SELECT es.*, s.code as subject_code, s.title as subject_title, s.units, s.category,
                       sch.day_of_week, sch.time_start, sch.time_end, sch.room
                FROM enrollment_subjects es
                JOIN subjects s ON es.subject_id = s.id
                LEFT JOIN schedules sch ON es.schedule_id = sch.id
                WHERE es.enrollment_id = :enr_id
            ");
            $subStmt->execute(['enr_id' => $enrollment['id']]);
            $app['enrolled_subjects'] = $subStmt->fetchAll();

            // Fetch assessment details
            $assStmt = $db->prepare("
                SELECT * FROM student_assessments
                WHERE enrollment_id = :enr_id
                LIMIT 1
            ");
            $assStmt->execute(['enr_id' => $enrollment['id']]);
            $app['assessment_info'] = $assStmt->fetch() ?: null;
        } else {
            $app['enrolled_subjects'] = [];
            $app['assessment_info'] = null;
        }

        Response::success('Application details loaded', $app);
    }

    /**
     * Verify or reject an uploaded document.
     */
    public function verifyDocument(): void {
        $user = Auth::requireRole(['registrar', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $docId = (int)($input['document_id'] ?? 0);
        $status = $input['status'] ?? 'Verified'; // Verified, Deficient, Rejected
        $notes = trim($input['verification_notes'] ?? '');

        if (!$docId) {
            Response::error('Document ID is required.');
        }

        $db = Database::getConnection();

        // Check if student is already enrolled
        $appIdStmt = $db->prepare("SELECT application_id FROM admission_documents WHERE id = :id");
        $appIdStmt->execute(['id' => $docId]);
        $parentAppId = $appIdStmt->fetchColumn();

        $currStatus = null;
        if ($parentAppId) {
            $chkApp = $db->prepare("SELECT status FROM admission_applications WHERE id = :id");
            $chkApp->execute(['id' => $parentAppId]);
            $currStatus = $chkApp->fetchColumn();
        }

        $stmt = $db->prepare("
            UPDATE admission_documents SET
                status = :status,
                verification_notes = :notes,
                verified_by = :user_id,
                verified_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute([
            'status'  => $status,
            'notes'   => $notes,
            'user_id' => $user['id'],
            'id'      => $docId
        ]);

        if ($parentAppId) {
            if ($status === 'Deficient' || $status === 'Rejected') {
                // If previously queued, release queue and section seat
                $chkApp = $db->prepare("SELECT status FROM admission_applications WHERE id = :id");
                $chkApp->execute(['id' => $parentAppId]);
                $currStatus = $chkApp->fetchColumn();

                if ($currStatus === 'Queued for Enrollment' || $currStatus === 'Approved') {
                    $qStmt = $db->prepare("SELECT assigned_section_id FROM enrollment_queues WHERE application_id = :id");
                    $qStmt->execute(['id' => $parentAppId]);
                    $secId = $qStmt->fetchColumn();
                    if ($secId) {
                        $db->prepare("UPDATE sections SET current_enrolled = GREATEST(0, current_enrolled - 1) WHERE id = :sec_id")
                           ->execute(['sec_id' => $secId]);
                    }
                    $db->prepare("DELETE FROM enrollment_queues WHERE application_id = :id")->execute(['id' => $parentAppId]);
                    $db->prepare("DELETE FROM student_assessments WHERE enrollment_id IN (SELECT id FROM enrollments WHERE application_id = :id) AND status != 'Paid'")->execute(['id' => $parentAppId]);
                    $db->prepare("DELETE FROM enrollment_subjects WHERE enrollment_id IN (SELECT id FROM enrollments WHERE application_id = :id)")->execute(['id' => $parentAppId]);
                    $db->prepare("DELETE FROM enrollments WHERE application_id = :id AND status != 'Enrolled'")->execute(['id' => $parentAppId]);
                }

                $db->prepare("UPDATE admission_applications SET status = 'Requirements Deficient' WHERE id = :id AND status != 'Enrolled'")
                   ->execute(['id' => $parentAppId]);
            } else {
                // If marking as Verified, check if any other documents remain Deficient
                $remDef = $db->prepare("SELECT COUNT(*) FROM admission_documents WHERE application_id = :id AND status IN ('Deficient', 'Rejected')");
                $remDef->execute(['id' => $parentAppId]);
                if ((int)$remDef->fetchColumn() === 0) {
                    $db->prepare("UPDATE admission_applications SET status = 'Under Review' WHERE id = :id AND status = 'Requirements Deficient'")
                       ->execute(['id' => $parentAppId]);
                }

                // If Form 137 / SF10 was verified, sync to student_records archive
                $docTypeStmt = $db->prepare("SELECT document_type FROM admission_documents WHERE id = :id");
                $docTypeStmt->execute(['id' => $docId]);
                $docType = $docTypeStmt->fetchColumn();
                if ($docType && (stripos($docType, '137') !== false || stripos($docType, 'SF10') !== false)) {
                    $db->prepare("
                        UPDATE student_records 
                        SET previous_school_f137_status = 'Received' 
                        WHERE student_id = (SELECT user_id FROM enrollments WHERE application_id = :app_id LIMIT 1)
                    ")->execute(['app_id' => $parentAppId]);
                }
            }
        }

        Auth::logAudit('DOCUMENT_VERIFIED', "Document #{$docId} marked as {$status}", $user['id']);

        Response::success("Document marked as {$status}");
    }

    /**
     * Batch verify all physical submission documents for an application.
     */
    public function batchVerifyDocuments(): void {
        $user = Auth::requireRole(['registrar', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $appId = (int)($input['application_id'] ?? 0);
        $mode = $input['submission_mode'] ?? 'Physical Submission';
        $notes = trim($input['verification_notes'] ?? 'Physical hard copies verified at Window 3');

        if (!$appId) {
            Response::error('Application ID is required.');
        }

        $db = Database::getConnection();

        $chkApp = $db->prepare("SELECT status FROM admission_applications WHERE id = :id");
        $chkApp->execute(['id' => $appId]);
        $currStatus = $chkApp->fetchColumn();

        if ($currStatus === 'Enrolled') {
            Response::error('Cannot modify document verification status because the student is already officially enrolled.');
        }

        $stmt = $db->prepare("
            UPDATE admission_documents SET
                status = 'Verified',
                verification_notes = :notes,
                verified_by = :user_id,
                verified_at = CURRENT_TIMESTAMP
            WHERE application_id = :app_id
              AND submission_mode = :mode
              AND status != 'Verified'
        ");
        $stmt->execute([
            'notes'   => $notes,
            'user_id' => $user['id'],
            'app_id'  => $appId,
            'mode'    => $mode
        ]);
        $affected = $stmt->rowCount();

        // Check if any documents remain deficient
        $remDef = $db->prepare("SELECT COUNT(*) FROM admission_documents WHERE application_id = :id AND status IN ('Deficient', 'Rejected')");
        $remDef->execute(['id' => $appId]);
        if ((int)$remDef->fetchColumn() === 0 && $currStatus === 'Requirements Deficient') {
            $db->prepare("UPDATE admission_applications SET status = 'Under Review' WHERE id = :id")->execute(['id' => $appId]);
        }

        Auth::logAudit('DOCUMENT_BATCH_VERIFIED', "Batch verified {$affected} documents ({$mode}) for App #{$appId}", $user['id']);
        Response::success("Successfully verified {$affected} physical document(s).", ['updated_count' => $affected]);
    }

    /**
     * Step in Workflow:
     * Staff/Admin checks requirements > (If approved) Add to Queue > Enrollment Form will show
     */
    public function approveAndQueue(): void {
        $user = Auth::requireRole(['registrar', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $appId = (int)($input['application_id'] ?? 0);
        $sectionId = (int)($input['section_id'] ?? 0);
        $remarks = trim($input['remarks'] ?? 'Requirements verified and approved.');

        if (!$appId) {
            Response::error('Application ID is required.');
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $appStmt = $db->prepare("SELECT * FROM admission_applications WHERE id = :id FOR UPDATE");
            $appStmt->execute(['id' => $appId]);
            $app = $appStmt->fetch();

            if (!$app) {
                $db->rollBack();
                Response::error('Application not found.');
            }

            // STRICT DEFICIENCY GUARD: Check if any document is Deficient or unverified
            $docCheck = $db->prepare("SELECT document_type, status FROM admission_documents WHERE application_id = :id");
            $docCheck->execute(['id' => $appId]);
            $docs = $docCheck->fetchAll();

            $deficient = [];
            $verifiedTypes = [];
            foreach ($docs as $d) {
                if ($d['status'] === 'Deficient' || $d['status'] === 'Rejected') {
                    $deficient[] = $d['document_type'];
                }
                if ($d['status'] === 'Verified') {
                    $verifiedTypes[] = $d['document_type'];
                }
            }

            if (!empty($deficient)) {
                $db->rollBack();
                Response::error('Cannot approve applicant: The following requirement(s) are marked as Deficient: ' . implode(', ', $deficient) . '. All deficiencies must be resolved before queuing.');
            }

            if (!in_array('PSA Birth Certificate', $verifiedTypes) || !in_array('SF9 / Form 138 (Report Card)', $verifiedTypes)) {
                $db->rollBack();
                Response::error('Cannot approve applicant: Core requirements (PSA Birth Certificate and SF9 Report Card) must be verified first.');
            }

            // If section wasn't explicitly selected, pick the first available section with open slots
            if (!$sectionId) {
                $findSec = $db->prepare("
                    SELECT id FROM sections
                    WHERE school_year_id = :sy_id AND grade_level_id = :gl_id
                      AND (strand_id = :strand_id OR (strand_id IS NULL AND :strand_id_null IS NULL))
                      AND current_enrolled < max_capacity AND is_active = 1
                    LIMIT 1
                ");
                $findSec->execute([
                    'sy_id'          => $app['school_year_id'],
                    'gl_id'          => $app['grade_level_id'],
                    'strand_id'      => $app['strand_id'],
                    'strand_id_null' => $app['strand_id']
                ]);
                $secRow = $findSec->fetch();
                if ($secRow) {
                    $sectionId = (int)$secRow['id'];
                }
            }

            // Check grade category to format Student Number: e.g. 2026-SHS-0001 or 2026-JHS-0001
            $glStmt = $db->prepare("SELECT category FROM grade_levels WHERE id = :gl_id");
            $glStmt->execute(['gl_id' => $app['grade_level_id']]);
            $glRow = $glStmt->fetch();
            $gradeCategory = ($glRow && $glRow['category'] === 'SHS') ? 'SHS' : 'JHS';

            // Generate Student Number if not already assigned
            if (!empty($app['student_no'])) {
                $studentNo = $app['student_no'];
            } else {
                $prefix = date('Y') . '-' . $gradeCategory . '-';
                
                // Find highest numerical sequence across all users, applications, and enrollments
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
            }

            // 1. Update Application status to "Queued for Enrollment" with generated student_no
            $upApp = $db->prepare("
                UPDATE admission_applications SET
                    status = 'Queued for Enrollment',
                    student_no = :student_no,
                    remarks = :remarks,
                    reviewed_by = :user_id,
                    reviewed_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $upApp->execute([
                'student_no' => $studentNo,
                'remarks'    => $remarks,
                'user_id'    => $user['id'],
                'id'         => $appId
            ]);

            // 2. Generate Next Queue Number based on active pending queue only
            $qCount = $db->prepare("
                SELECT COUNT(*) FROM enrollment_queues q
                JOIN admission_applications a ON q.application_id = a.id
                LEFT JOIN enrollments e ON a.id = e.application_id
                WHERE q.school_year_id = :sy_id 
                  AND q.status != 'Completed' 
                  AND (e.status IS NULL OR e.status != 'Officially Enrolled') 
                  AND a.status != 'Enrolled'
                  AND q.application_id != :app_id
            ");
            $qCount->execute(['sy_id' => $app['school_year_id'], 'app_id' => $appId]);
            $queueNum = (int)$qCount->fetchColumn() + 1;

            $queueStmt = $db->prepare("
                INSERT INTO enrollment_queues (application_id, school_year_id, queue_number, assigned_section_id, status, queued_by)
                VALUES (:app_id, :sy_id, :q_num, :sec_id, 'Waiting Assessment', :queued_by)
                ON DUPLICATE KEY UPDATE assigned_section_id = :sec_id2, queue_number = :q_num2, status = 'Waiting Assessment'
            ");
            $queueStmt->execute([
                'app_id'    => $appId,
                'sy_id'     => $app['school_year_id'],
                'q_num'     => $queueNum,
                'sec_id'    => $sectionId ?: null,
                'queued_by' => $user['id'],
                'sec_id2'   => $sectionId ?: null,
                'q_num2'    => $queueNum
            ]);

            // 3. Create or prepare the Pre-Enrollment Record & Subject Assignment
            $enrCount = $db->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
            $enrNo = 'ENR-' . date('Y') . '-' . str_pad((string)((int)$enrCount + 1), 4, '0', STR_PAD_LEFT);

            $chkEnr = $db->prepare("SELECT id FROM enrollments WHERE application_id = :app_id LIMIT 1");
            $chkEnr->execute(['app_id' => $appId]);
            $existingEnr = $chkEnr->fetch();

            if ($existingEnr) {
                $enrId = (int)$existingEnr['id'];
                $upExistingEnr = $db->prepare("UPDATE enrollments SET student_no = :student_no, section_id = :sec_id WHERE id = :id");
                $upExistingEnr->execute(['student_no' => $studentNo, 'sec_id' => $sectionId ?: 1, 'id' => $enrId]);
            } else {
                $insEnr = $db->prepare("
                    INSERT INTO enrollments (
                        enrollment_no, student_no, student_id, application_id, school_year_id,
                        grade_level_id, track_id, strand_id, section_id, lrn,
                        enrollment_date, status, approved_by
                    ) VALUES (
                        :enr_no, :student_no, :student_id, :app_id, :sy_id,
                        :gl_id, :track_id, :strand_id, :sec_id, :lrn,
                        CURRENT_DATE, 'Pending Payment', :approved_by
                    )
                ");
                $insEnr->execute([
                    'enr_no'      => $enrNo,
                    'student_no'  => $studentNo,
                    'student_id'  => $app['user_id'], // Uses temp user_id until permanent student account created at Treasury
                    'app_id'      => $appId,
                    'sy_id'       => $app['school_year_id'],
                    'gl_id'       => $app['grade_level_id'],
                    'track_id'    => $app['track_id'],
                    'strand_id'   => $app['strand_id'],
                    'sec_id'      => $sectionId ?: 1,
                    'lrn'         => $app['lrn'],
                    'approved_by' => $user['id']
                ]);
                $enrId = (int)$db->lastInsertId();

                // 4. Populate DepEd Curriculum subjects and match with section schedules
                $subQuery = "
                    SELECT s.id, MIN(sch.id) as schedule_id 
                    FROM subjects s
                    LEFT JOIN schedules sch ON s.id = sch.subject_id AND sch.section_id = :sec_id AND sch.is_active = 1
                    WHERE s.grade_level_id = :gl_id
                      AND (s.strand_id = :strand_id OR s.strand_id IS NULL)
                      AND s.is_active = 1
                    GROUP BY s.id
                ";
                $subStmt = $db->prepare($subQuery);
                $subStmt->execute([
                    'sec_id'    => $sectionId ?: 1,
                    'gl_id'     => $app['grade_level_id'],
                    'strand_id' => $app['strand_id']
                ]);
                $subjects = $subStmt->fetchAll();

                $insSub = $db->prepare("INSERT INTO enrollment_subjects (enrollment_id, subject_id, schedule_id, status) VALUES (:enr_id, :sub_id, :sch_id, 'Enrolled')");
                foreach ($subjects as $s) {
                    $insSub->execute([
                        'enr_id' => $enrId,
                        'sub_id' => $s['id'],
                        'sch_id' => $s['schedule_id'] ?: null
                    ]);
                }
            }

            // 5. Compute Automated Assessment Fees based on Fee Structures & Vouchers
            $feeStmt = $db->prepare("
                SELECT fc.code as category_code, fs.amount
                FROM fee_structures fs
                JOIN fee_categories fc ON fs.fee_category_id = fc.id
                WHERE fs.school_year_id = :sy_id AND fs.grade_level_id = :gl_id
                  AND (fs.strand_id = :strand_id OR fs.strand_id IS NULL)
            ");
            $feeStmt->execute([
                'sy_id'     => $app['school_year_id'],
                'gl_id'     => $app['grade_level_id'],
                'strand_id' => $app['strand_id']
            ]);
            $fees = $feeStmt->fetchAll();

            $tuition = 0.00;
            $misc = 0.00;
            $lab = 0.00;
            $other = 0.00;

            foreach ($fees as $f) {
                if ($f['category_code'] === 'TUI') $tuition += (float)$f['amount'];
                elseif ($f['category_code'] === 'MISC') $misc += (float)$f['amount'];
                elseif ($f['category_code'] === 'LAB') $lab += (float)$f['amount'];
                else $other += (float)$f['amount'];
            }

            // Default fallbacks if fee structure not customized yet
            if ($tuition === 0.00) $tuition = $app['grade_level_id'] >= 5 ? 12000.00 : 18000.00;
            if ($misc === 0.00) $misc = 4000.00;
            if ($lab === 0.00) $lab = 2500.00;

            $gross = $tuition + $misc + $lab + $other;
            $voucherDiscount = 0.00;

            // Apply DepEd Voucher Subsidy calculations
            if ($app['voucher_status'] === 'Public JHS Completer (100%)') {
                $voucherDiscount = min(17500.00, $gross * 0.80);
            } elseif ($app['voucher_status'] === 'Private ESC Grantee (80%)') {
                $voucherDiscount = min(14000.00, $gross * 0.65);
            } elseif ($app['voucher_status'] === 'Private Non-ESC Voucher (50%)') {
                $voucherDiscount = min(8750.00, $gross * 0.40);
            }

            $netPayable = max(0.00, $gross - $voucherDiscount);
            $assNo = 'ASS-' . date('Y') . '-' . str_pad((string)$enrId, 4, '0', STR_PAD_LEFT);

            $insAss = $db->prepare("
                INSERT INTO student_assessments (
                    enrollment_id, school_year_id, assessment_no,
                    total_tuition, total_miscellaneous, total_laboratory, total_other_fees,
                    gross_amount, voucher_discount, net_payable, minimum_downpayment,
                    remaining_balance, status, assessed_by
                ) VALUES (
                    :enr_id, :sy_id, :ass_no,
                    :tui, :misc, :lab, :oth,
                    :gross, :disc, :net, 3000.00,
                    :rem, 'Unpaid', :ass_by
                ) ON DUPLICATE KEY UPDATE
                    gross_amount = :gross2, net_payable = :net2, remaining_balance = :rem2
            ");
            $insAss->execute([
                'enr_id'  => $enrId,
                'sy_id'   => $app['school_year_id'],
                'ass_no'  => $assNo,
                'tui'     => $tuition,
                'misc'    => $misc,
                'lab'     => $lab,
                'oth'     => $other,
                'gross'   => $gross,
                'disc'    => $voucherDiscount,
                'net'     => $netPayable,
                'rem'     => $netPayable,
                'ass_by'  => $user['id'],
                'gross2'  => $gross,
                'net2'    => $netPayable,
                'rem2'    => $netPayable
            ]);

            $db->commit();
            Auth::logAudit('APPROVE_AND_QUEUE', "Approved App #{$appId}, Queued #{queueNum}, Enr #{$enrId}", $user['id']);

            // Dispatch Approval & Assessment Email (Fail-safe)
            Mailer::sendRegistrarApproval([
                'first_name'   => $app['first_name'] ?? '',
                'last_name'    => $app['last_name'] ?? '',
                'email'        => $app['email'] ?? '',
                'student_no'   => $studentNo,
                'section_name' => $sectionName ?? 'Main Section'
            ], [
                'assessment_no'   => $assNo,
                'net_amount'      => $netPayable,
                'min_downpayment' => 3000.00
            ]);

            Response::success('Applicant successfully approved and added to the Enrollment Queue!', [
                'queue_number'  => $queueNum,
                'enrollment_id' => $enrId,
                'assessment_no' => $assNo
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            Response::error('Failed to process approval and queue: ' . $e->getMessage());
        }
    }

    /**
     * Get Enrollment Queue list for Registrar and Treasury.
     */
    public function getQueue(): void {
        Auth::requireRole(['registrar', 'treasury', 'admin', 'coordinator']);
        $db = Database::getConnection();

        $statusFilter = $_GET['status'] ?? 'active';

        $sql = "
            SELECT q.*, 
                   a.application_no, a.first_name, a.middle_name, a.last_name, a.lrn, a.voucher_status,
                   gl.name as grade_level_name, s.code as strand_code,
                   sec.name as section_name, sec.room as section_room,
                   sa.assessment_no, sa.gross_amount, sa.voucher_discount, sa.net_payable, sa.status as payment_status,
                   e.id as enrollment_id, e.enrollment_no, e.status as enrollment_status
            FROM enrollment_queues q
            JOIN admission_applications a ON q.application_id = a.id
            JOIN grade_levels gl ON a.grade_level_id = gl.id
            LEFT JOIN strands s ON a.strand_id = s.id
            LEFT JOIN sections sec ON q.assigned_section_id = sec.id
            LEFT JOIN enrollments e ON a.id = e.application_id
            LEFT JOIN student_assessments sa ON e.id = sa.enrollment_id
            WHERE 1=1
        ";

        if ($statusFilter === 'active') {
            $sql .= " AND q.status != 'Completed' AND (e.status IS NULL OR e.status != 'Officially Enrolled') AND a.status != 'Enrolled'";
        } elseif ($statusFilter === 'completed') {
            $sql .= " AND (q.status = 'Completed' OR e.status = 'Officially Enrolled' OR a.status = 'Enrolled')";
        }

        $sql .= " ORDER BY q.id ASC";

        $stmt = $db->query($sql);
        $queue = $stmt->fetchAll();

        // Dynamically sequence active queue numbers starting from 1
        if ($statusFilter === 'active') {
            $seq = 1;
            $upQ = $db->prepare("UPDATE enrollment_queues SET queue_number = :q_num WHERE id = :id");
            foreach ($queue as &$item) {
                $item['queue_number'] = $seq;
                $upQ->execute(['q_num' => $seq, 'id' => $item['id']]);
                $seq++;
            }
            unset($item);
        }

        Response::success('Enrollment queue loaded', $queue);
    }

    /**
     * Undo an approval action for an applicant currently in the enrollment queue.
     * Reverts status back to 'Under Review', cancels the pending assessment & queue entry,
     * and releases the reserved section seat.
     */
    public function undoApproval(): void {
        $user = Auth::requireRole(['registrar', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $appId = (int)($input['application_id'] ?? 0);

        if (!$appId) {
            Response::error('Application ID is required.');
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $appStmt = $db->prepare("SELECT * FROM admission_applications WHERE id = :id FOR UPDATE");
            $appStmt->execute(['id' => $appId]);
            $app = $appStmt->fetch();

            if (!$app) {
                $db->rollBack();
                Response::error('Application not found.');
            }

            if ($app['status'] === 'Enrolled') {
                $db->rollBack();
                Response::error('Cannot undo approval: This student has already completed treasury payment and is officially enrolled.');
            }

            // 1. Fetch current queue / assigned section
            $qStmt = $db->prepare("SELECT assigned_section_id FROM enrollment_queues WHERE application_id = :id");
            $qStmt->execute(['id' => $appId]);
            $assignedSecId = $qStmt->fetchColumn();

            if ($assignedSecId) {
                $decSec = $db->prepare("UPDATE sections SET current_enrolled = GREATEST(0, current_enrolled - 1) WHERE id = :sec_id");
                $decSec->execute(['sec_id' => $assignedSecId]);
            }

            // 2. Fetch and remove pending enrollments & assessments
            $enrStmt = $db->prepare("SELECT id FROM enrollments WHERE application_id = :id");
            $enrStmt->execute(['id' => $appId]);
            $enrIds = $enrStmt->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($enrIds as $eId) {
                // Delete student assessments for this enrollment
                $delAss = $db->prepare("DELETE FROM student_assessments WHERE enrollment_id = :enr_id AND status != 'Paid'");
                $delAss->execute(['enr_id' => $eId]);

                // Delete enrollment subjects
                $delSubs = $db->prepare("DELETE FROM enrollment_subjects WHERE enrollment_id = :enr_id");
                $delSubs->execute(['enr_id' => $eId]);
            }

            $delEnr = $db->prepare("DELETE FROM enrollments WHERE application_id = :id AND status != 'Enrolled'");
            $delEnr->execute(['id' => $appId]);

            // 3. Remove queue entry
            $delQ = $db->prepare("DELETE FROM enrollment_queues WHERE application_id = :id");
            $delQ->execute(['id' => $appId]);

            // 4. Update application status back to 'Under Review'
            $upApp = $db->prepare("
                UPDATE admission_applications SET
                    status = 'Under Review',
                    remarks = 'Approval revoked by Registrar - Returned to Under Review',
                    reviewed_by = :user_id,
                    reviewed_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $upApp->execute([
                'user_id' => $user['id'],
                'id'      => $appId
            ]);

            $db->commit();
            Auth::logAudit('APPROVAL_REVOKED', "Revoked queue approval for App #{$appId} ({$app['application_no']})", $user['id']);

            Response::success("Approval for {$app['application_no']} has been undone. Application returned to 'Under Review'.");
        } catch (\Exception $e) {
            $db->rollBack();
            Response::error('Failed to undo approval: ' . $e->getMessage());
        }
    }

    /**
     * Get officially enrolled students with their admission & to-follow documents for evaluation.
     */
    public function getEnrolledStudentsDocuments(): void {
        Auth::requireRole(['registrar', 'admin']);
        $db = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $filter = $_GET['filter'] ?? 'all'; // all, needs_review, to_follow, compliant

        $sql = "
            SELECT 
                e.id as enrollment_id,
                e.enrollment_no,
                e.student_id as user_id,
                e.application_id,
                e.status as enrollment_status,
                e.enrolled_at,
                COALESCE(e.student_no, a.student_no, a.application_no) as student_no,
                COALESCE(e.lrn, a.lrn) as lrn,
                a.first_name,
                a.last_name,
                a.middle_name,
                a.email,
                a.contact_number,
                a.application_no,
                a.strand_id,
                s.code as strand_code,
                s.name as strand_name,
                sec.id as section_id,
                sec.name as section_name,
                sec.room as section_room,
                gl.id as grade_level_id,
                gl.name as grade_level_name,
                gl.category as grade_category
            FROM enrollments e
            JOIN admission_applications a ON e.application_id = a.id
            LEFT JOIN sections sec ON e.section_id = sec.id
            LEFT JOIN grade_levels gl ON gl.id = COALESCE(e.grade_level_id, sec.grade_level_id, a.grade_level_id)
            LEFT JOIN strands s ON s.id = COALESCE(e.strand_id, sec.strand_id, a.strand_id)
            WHERE e.status IN ('Officially Enrolled', 'Enrolled') OR a.status = 'Enrolled'
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (COALESCE(e.student_no, a.student_no) LIKE :search OR COALESCE(e.lrn, a.lrn) LIKE :search OR a.first_name LIKE :search OR a.last_name LIKE :search OR e.enrollment_no LIKE :search OR sec.name LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        $sql .= " ORDER BY a.last_name ASC, a.first_name ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $enrolledList = $stmt->fetchAll();

        $docStmt = $db->prepare("
            SELECT id, application_id, document_type, submission_mode, target_date, 
                   promissory_note, file_path, original_filename, file_size, status, 
                   verification_notes, verified_by, verified_at, uploaded_at
            FROM admission_documents
            WHERE application_id = :app_id
            ORDER BY id ASC
        ");

        $results = [];
        $totalEnrolled = count($enrolledList);
        $needsReviewCount = 0;
        $hasToFollowCount = 0;
        $fullyCompliantCount = 0;

        foreach ($enrolledList as $student) {
            $docStmt->execute(['app_id' => $student['application_id']]);
            $docs = $docStmt->fetchAll();

            $totalDocs = count($docs);
            $verifiedDocs = 0;
            $pendingDocs = 0;
            $toFollowDocs = 0;
            $deficientDocs = 0;

            foreach ($docs as $d) {
                $st = $d['status'];
                if ($st === 'Verified') {
                    $verifiedDocs++;
                } elseif ($st === 'Pending' || $st === 'Under Review') {
                    $pendingDocs++;
                } elseif ($st === 'Deficient' || $st === 'Rejected') {
                    $deficientDocs++;
                } else {
                    $toFollowDocs++;
                }
            }

            $hasPendingUploads = ($pendingDocs > 0);
            $isFullyCompliant = ($totalDocs > 0 && $verifiedDocs === $totalDocs);
            $hasOutstanding = ($toFollowDocs > 0 || $deficientDocs > 0);

            if ($hasPendingUploads) $needsReviewCount++;
            if ($hasOutstanding) $hasToFollowCount++;
            if ($isFullyCompliant) $fullyCompliantCount++;

            // Filter condition
            if ($filter === 'needs_review' && !$hasPendingUploads) continue;
            if ($filter === 'to_follow' && !$hasOutstanding) continue;
            if ($filter === 'compliant' && !$isFullyCompliant) continue;

            $student['documents'] = $docs;
            $student['stats'] = [
                'total'               => $totalDocs,
                'verified'            => $verifiedDocs,
                'pending'             => $pendingDocs,
                'to_follow'           => $toFollowDocs,
                'deficient'           => $deficientDocs,
                'has_pending_uploads' => $hasPendingUploads,
                'is_fully_compliant'  => $isFullyCompliant
            ];

            $results[] = $student;
        }

        Response::success('Enrolled students documents loaded', [
            'students' => $results,
            'summary'  => [
                'total_enrolled'       => $totalEnrolled,
                'needs_review_count'   => $needsReviewCount,
                'has_to_follow_count'  => $hasToFollowCount,
                'fully_compliant_count'=> $fullyCompliantCount
            ]
        ]);
    }
}

