<?php
// backend/controllers/StudentController.php
namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;
use App\Helpers\Auth;
use PDO;

class StudentController {
    /**
     * Get Student Dashboard Overview (Profile, Enrolled Section, Schedule, Balance, Events).
     */
    public function getDashboard(): void {
        $user = Auth::requireRole(['student', 'applicant']);
        $db = Database::getConnection();

        // 1. Get current active enrollment (by student user ID or linked applicant admission application)
        $enrStmt = $db->prepare("
            SELECT e.*, 
                   gl.name as grade_level_name, gl.category as grade_category,
                   t.name as track_name,
                   s.name as strand_name, s.code as strand_code,
                   sec.name as section_name, sec.room as section_room,
                   sy.name as school_year_name, sy.active_semester,
                   sa.assessment_no, sa.gross_amount, sa.voucher_discount, sa.net_payable, sa.total_paid, sa.remaining_balance, sa.status as payment_status,
                   u.student_id as official_student_no,
                   p.first_name as student_first_name, p.last_name as student_last_name, p.middle_name as student_middle_name
            FROM enrollments e
            JOIN grade_levels gl ON e.grade_level_id = gl.id
            LEFT JOIN tracks t ON e.track_id = t.id
            LEFT JOIN strands s ON e.strand_id = s.id
            JOIN sections sec ON e.section_id = sec.id
            JOIN school_years sy ON e.school_year_id = sy.id
            LEFT JOIN student_assessments sa ON e.id = sa.enrollment_id
            LEFT JOIN users u ON e.student_id = u.id
            LEFT JOIN user_profiles p ON u.id = p.user_id
            WHERE (e.student_id = :stud_id OR e.application_id IN (SELECT id FROM admission_applications WHERE user_id = :app_user_id))
            ORDER BY (CASE WHEN e.status = 'Officially Enrolled' THEN 1 ELSE 2 END) ASC, e.id DESC
            LIMIT 1
        ");
        $enrStmt->execute(['stud_id' => $user['id'], 'app_user_id' => $user['id']]);
        $enrollment = $enrStmt->fetch();

        // Fallback user display names if user profile is empty
        if (empty($user['first_name']) && $enrollment && !empty($enrollment['student_first_name'])) {
            $user['first_name'] = $enrollment['student_first_name'];
            $user['last_name'] = $enrollment['student_last_name'];
            $user['middle_name'] = $enrollment['student_middle_name'];
        }
        if (empty($user['student_id']) && $enrollment && !empty($enrollment['student_no'])) {
            $user['student_id'] = $enrollment['student_no'];
        }

        $subjects = [];
        $payments = [];

        if ($enrollment) {
            // 2. Fetch distinct enrolled subjects
            $subStmt = $db->prepare("
                SELECT MIN(es.id) as enrollment_subject_id,
                       MIN(es.status) as subject_status,
                       sub.id as subject_id,
                       sub.code as subject_code,
                       sub.title as subject_title,
                       sub.category as subject_category,
                       sub.units,
                       sub.semester
                FROM enrollment_subjects es
                JOIN subjects sub ON es.subject_id = sub.id
                WHERE es.enrollment_id = :enr_id
                GROUP BY sub.id
                ORDER BY sub.semester ASC, sub.code ASC
            ");
            $subStmt->execute(['enr_id' => $enrollment['id']]);
            $subjects = $subStmt->fetchAll();

            // Fetch active schedules for this section
            $schStmt = $db->prepare("
                SELECT sch.id as schedule_id, sch.subject_id, sch.semester, sch.day_of_week, sch.time_start, sch.time_end, sch.room,
                       s.code as subject_code, s.title as subject_title, s.category as subject_category, s.semester as subject_semester,
                       u.username as teacher_username,
                       tp.first_name as teacher_first,
                       tp.last_name as teacher_last
                FROM schedules sch
                JOIN subjects s ON sch.subject_id = s.id
                LEFT JOIN users u ON sch.teacher_id = u.id
                LEFT JOIN user_profiles tp ON u.id = tp.user_id
                WHERE sch.section_id = :sec_id AND sch.is_active = 1
                ORDER BY FIELD(sch.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), sch.time_start ASC
            ");
            $schStmt->execute(['sec_id' => $enrollment['section_id']]);
            $sectionSchedules = $schStmt->fetchAll();

            $schedulesBySubject = [];
            foreach ($sectionSchedules as $sch) {
                $schedulesBySubject[$sch['subject_id']][] = $sch;
            }

            foreach ($subjects as &$sub) {
                $subSchedules = $schedulesBySubject[$sub['subject_id']] ?? [];
                $sub['schedules'] = $subSchedules;

                if (!empty($subSchedules)) {
                    $first = $subSchedules[0];
                    $sub['teacher_username'] = $first['teacher_username'] ?: 'faculty';
                    $sub['teacher_first'] = $first['teacher_first'] ?: 'Faculty';
                    $sub['teacher_last'] = $first['teacher_last'] ?: 'Teacher';
                    $sub['room'] = $first['room'] ?: ($enrollment['section_room'] ?: 'Room 101');

                    $days = array_values(array_unique(array_column($subSchedules, 'day_of_week')));
                    $sub['days_list'] = $days;
                    if (count($days) === 5) {
                        $sub['day_of_week'] = 'Mon - Fri';
                    } elseif (count($days) > 1) {
                        $shortDays = array_map(function($d) { return substr($d, 0, 3); }, $days);
                        $sub['day_of_week'] = implode(', ', $shortDays);
                    } else {
                        $sub['day_of_week'] = $days[0] ?? 'Mon-Fri';
                    }

                    $sub['time_start'] = $first['time_start'];
                    $sub['time_end'] = $first['time_end'];
                    $sub['total_sessions'] = count($subSchedules);
                } else {
                    $sub['teacher_first'] = 'Faculty';
                    $sub['teacher_last'] = 'Teacher';
                    $sub['room'] = $enrollment['section_room'] ?: 'Room 101';
                    $sub['day_of_week'] = 'Mon-Fri';
                    $sub['time_start'] = '08:00:00';
                    $sub['time_end'] = '09:00:00';
                    $sub['total_sessions'] = 1;
                }
            }
            unset($sub);

            // 3. Fetch payment receipts
            if (!empty($enrollment['assessment_no'])) {
                $payStmt = $db->prepare("
                    SELECT p.*, u.username as received_by_user
                    FROM payments p
                    JOIN users u ON p.received_by = u.id
                    WHERE p.enrollment_id = :enr_id
                    ORDER BY p.id DESC
                ");
                $payStmt->execute(['enr_id' => $enrollment['id']]);
                $payments = $payStmt->fetchAll();
            }
        }

        // 4. Fetch School Events for Student Calendar Widget
        $events = $db->query("
            SELECT id, school_year_id, title, description, event_category, start_date, end_date, start_time, end_time, location, target_audience
            FROM school_events
            WHERE is_published = 1 AND (target_audience = 'All' OR target_audience = 'Students')
            ORDER BY start_date ASC
            LIMIT 100
        ")->fetchAll();

        // 5. Fetch Dynamic LMS Activity & Teacher Uploads Feed
        $lmsUpdates = [];
        $lmsStats = [
            'total_announcements' => 0,
            'total_modules'       => 0,
            'total_assignments'   => 0,
            'pending_assignments' => 0,
            'recent_uploads_count'=> 0
        ];

        if ($enrollment) {
            $secId = (int)$enrollment['section_id'];
            $activeSem = $enrollment['active_semester'] ?? '1st Semester';
            $studentId = (int)$user['id'];

            // Query Unified Recent LMS Updates Feed
            $lmsFeedStmt = $db->prepare("
                SELECT 
                    'announcement' AS item_type,
                    a.id,
                    a.subject_id,
                    sub.code AS subject_code,
                    sub.name AS subject_name,
                    a.title,
                    a.content AS description,
                    NULL AS task_type,
                    NULL AS max_score,
                    NULL AS due_date,
                    NULL AS file_path,
                    NULL AS file_name,
                    NULL AS file_size_kb,
                    NULL AS external_url,
                    a.is_pinned,
                    a.created_at,
                    u.id AS teacher_id,
                    p.first_name AS teacher_first,
                    p.last_name AS teacher_last,
                    NULL AS submission_status
                FROM lms_announcements a
                JOIN subjects sub ON a.subject_id = sub.id
                JOIN grade_levels gl ON sub.grade_level_id = gl.id
                LEFT JOIN users u ON a.teacher_id = u.id
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE a.section_id = :sec_1
                  AND (gl.category = 'JHS' OR sub.semester = 'Full Year' OR sub.semester = :sem_1)

                UNION ALL

                SELECT 
                    'module' AS item_type,
                    m.id,
                    m.subject_id,
                    sub.code AS subject_code,
                    sub.name AS subject_name,
                    m.title,
                    m.description,
                    NULL AS task_type,
                    NULL AS max_score,
                    NULL AS due_date,
                    m.file_path,
                    m.file_name,
                    m.file_size_kb,
                    m.external_url,
                    0 AS is_pinned,
                    m.created_at,
                    u.id AS teacher_id,
                    p.first_name AS teacher_first,
                    p.last_name AS teacher_last,
                    NULL AS submission_status
                FROM lms_modules m
                JOIN subjects sub ON m.subject_id = sub.id
                JOIN grade_levels gl ON sub.grade_level_id = gl.id
                LEFT JOIN users u ON m.teacher_id = u.id
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE m.section_id = :sec_2
                  AND (gl.category = 'JHS' OR sub.semester = 'Full Year' OR sub.semester = :sem_2)

                UNION ALL

                SELECT 
                    'assignment' AS item_type,
                    asg.id,
                    asg.subject_id,
                    sub.code AS subject_code,
                    sub.name AS subject_name,
                    asg.title,
                    asg.instructions AS description,
                    asg.task_type,
                    asg.max_score,
                    asg.due_date,
                    NULL AS file_path,
                    NULL AS file_name,
                    NULL AS file_size_kb,
                    NULL AS external_url,
                    0 AS is_pinned,
                    asg.created_at,
                    u.id AS teacher_id,
                    p.first_name AS teacher_first,
                    p.last_name AS teacher_last,
                    subm.status AS submission_status
                FROM lms_assignments asg
                JOIN subjects sub ON asg.subject_id = sub.id
                JOIN grade_levels gl ON sub.grade_level_id = gl.id
                LEFT JOIN users u ON asg.teacher_id = u.id
                LEFT JOIN user_profiles p ON u.id = p.user_id
                LEFT JOIN lms_submissions subm ON asg.id = subm.assignment_id AND subm.student_id = :student_id
                WHERE asg.section_id = :sec_3
                  AND (gl.category = 'JHS' OR sub.semester = 'Full Year' OR sub.semester = :sem_3)

                ORDER BY is_pinned DESC, created_at DESC
                LIMIT 20
            ");
            $lmsFeedStmt->execute([
                'sec_1'      => $secId,
                'sem_1'      => $activeSem,
                'sec_2'      => $secId,
                'sem_2'      => $activeSem,
                'sec_3'      => $secId,
                'sem_3'      => $activeSem,
                'student_id' => $studentId
            ]);
            $lmsUpdates = $lmsFeedStmt->fetchAll();

            // Calculate overall LMS Stats & counts
            $annCount = (int)$db->query("
                SELECT COUNT(*) FROM lms_announcements a 
                JOIN subjects s ON a.subject_id = s.id 
                JOIN grade_levels gl ON s.grade_level_id = gl.id 
                WHERE a.section_id = $secId AND (gl.category = 'JHS' OR s.semester = 'Full Year' OR s.semester = '$activeSem')
            ")->fetchColumn();

            $modCount = (int)$db->query("
                SELECT COUNT(*) FROM lms_modules m 
                JOIN subjects s ON m.subject_id = s.id 
                JOIN grade_levels gl ON s.grade_level_id = gl.id 
                WHERE m.section_id = $secId AND (gl.category = 'JHS' OR s.semester = 'Full Year' OR s.semester = '$activeSem')
            ")->fetchColumn();

            $asgCount = (int)$db->query("
                SELECT COUNT(*) FROM lms_assignments a 
                JOIN subjects s ON a.subject_id = s.id 
                JOIN grade_levels gl ON s.grade_level_id = gl.id 
                WHERE a.section_id = $secId AND (gl.category = 'JHS' OR s.semester = 'Full Year' OR s.semester = '$activeSem')
            ")->fetchColumn();

            $pendingCount = (int)$db->query("
                SELECT COUNT(*) FROM lms_assignments a 
                JOIN subjects s ON a.subject_id = s.id 
                JOIN grade_levels gl ON s.grade_level_id = gl.id 
                LEFT JOIN lms_submissions sub ON a.id = sub.assignment_id AND sub.student_id = $studentId
                WHERE a.section_id = $secId 
                  AND (gl.category = 'JHS' OR s.semester = 'Full Year' OR s.semester = '$activeSem')
                  AND sub.id IS NULL
            ")->fetchColumn();

            $oneWeekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
            $recentCount = 0;
            foreach ($lmsUpdates as $u) {
                if ($u['created_at'] >= $oneWeekAgo) {
                    $recentCount++;
                }
            }

            $lmsStats = [
                'total_announcements' => $annCount,
                'total_modules'       => $modCount,
                'total_assignments'   => $asgCount,
                'pending_assignments' => $pendingCount,
                'recent_uploads_count'=> $recentCount
            ];

            // Attach per-subject counts to $subjects array
            $subCountsStmt = $db->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM lms_modules WHERE section_id = :sec_1 AND subject_id = :sub_1) as modules_count,
                    (SELECT COUNT(*) FROM lms_assignments WHERE section_id = :sec_2 AND subject_id = :sub_2) as assignments_count,
                    (SELECT COUNT(*) FROM lms_announcements WHERE section_id = :sec_3 AND subject_id = :sub_3) as announcements_count,
                    (SELECT COUNT(*) FROM lms_assignments a 
                     LEFT JOIN lms_submissions s ON a.id = s.assignment_id AND s.student_id = :stud_id
                     WHERE a.section_id = :sec_4 AND a.subject_id = :sub_4 AND s.id IS NULL
                    ) as pending_assignments_count
            ");
            foreach ($subjects as &$sub) {
                $subCountsStmt->execute([
                    'sec_1' => $secId, 'sub_1' => $sub['subject_id'],
                    'sec_2' => $secId, 'sub_2' => $sub['subject_id'],
                    'sec_3' => $secId, 'sub_3' => $sub['subject_id'],
                    'sec_4' => $secId, 'sub_4' => $sub['subject_id'],
                    'stud_id' => $studentId
                ]);
                $counts = $subCountsStmt->fetch();
                $sub['modules_count'] = (int)($counts['modules_count'] ?? 0);
                $sub['assignments_count'] = (int)($counts['assignments_count'] ?? 0);
                $sub['announcements_count'] = (int)($counts['announcements_count'] ?? 0);
                $sub['pending_assignments_count'] = (int)($counts['pending_assignments_count'] ?? 0);
            }
            unset($sub);
        }

        Response::success('Student dashboard loaded', [
            'user'              => $user,
            'enrollment'        => $enrollment ?: null,
            'subjects'          => $subjects,
            'section_schedules' => $sectionSchedules ?? [],
            'payments'          => $payments,
            'events'            => $events,
            'lms_updates'       => $lmsUpdates,
            'lms_stats'         => $lmsStats
        ]);
    }

    /**
     * Get Student's Admission Requirements & Follow-up Compliance Checklist.
     */
    public function getRequirements(): void {
        $user = Auth::requireRole(['student', 'applicant']);
        $db = Database::getConnection();

        // 1. Find linked admission application
        $appStmt = $db->prepare("
            SELECT aa.id, aa.application_no, aa.applicant_type, aa.grade_level_id, aa.strand_id, aa.status as application_status,
                   gl.category as grade_category, gl.name as grade_level_name, s.code as strand_code
            FROM admission_applications aa
            JOIN grade_levels gl ON aa.grade_level_id = gl.id
            LEFT JOIN strands s ON aa.strand_id = s.id
            WHERE aa.user_id = :uid1 
               OR (aa.student_no IS NOT NULL AND aa.student_no = :stud_id)
               OR aa.id IN (SELECT application_id FROM enrollments WHERE student_id = :uid2)
            ORDER BY aa.id DESC
            LIMIT 1
        ");
        $appStmt->execute([
            'uid1'    => $user['id'],
            'stud_id' => $user['student_id'] ?? '',
            'uid2'    => $user['id']
        ]);
        $app = $appStmt->fetch();

        if (!$app) {
            Response::success('No application linked', [
                'has_application' => false,
                'application'     => null,
                'documents'       => [],
                'stats'           => [
                    'total'      => 0,
                    'verified'   => 0,
                    'pending'    => 0,
                    'to_follow'  => 0,
                    'deficient'  => 0,
                    'compliance_percentage' => 100
                ]
            ]);
            return;
        }

        // 2. Fetch existing uploaded documents
        $docStmt = $db->prepare("
            SELECT id, document_type, submission_mode, target_date, promissory_note, 
                   file_path, original_filename, file_size, status, verification_notes, uploaded_at, verified_at
            FROM admission_documents
            WHERE application_id = :app_id
            ORDER BY id ASC
        ");
        $docStmt->execute(['app_id' => $app['id']]);
        $uploadedDocs = $docStmt->fetchAll();
        $docsByType = [];
        foreach ($uploadedDocs as $d) {
            $docsByType[$d['document_type']] = $d;
        }

        // 3. Define Standard DepEd High School Requirements
        $requiredTypes = [
            'PSA Birth Certificate' => [
                'name' => 'PSA Birth Certificate (Original Copy)',
                'desc' => 'Philippine Statistics Authority (PSA) issued birth certificate or local civil registrar copy.',
                'mandatory' => true
            ],
            'Form 138 (Report Card)' => [
                'name' => 'Form 138 (Official Grade Report Card)',
                'desc' => 'Official Progress Report Card from the previous grade level signed by the principal.',
                'mandatory' => true
            ],
            'Certificate of Good Moral Character' => [
                'name' => 'Certificate of Good Moral Character',
                'desc' => 'Issued by the Guidance Counselor or School Head of the previous school attended.',
                'mandatory' => true
            ],
            '2x2 ID Picture' => [
                'name' => 'Recent 2x2 ID Photo (White Background)',
                'desc' => 'Formal student portrait in white background with nametag.',
                'mandatory' => true
            ],
            'Form 137 / SF10 (Permanent Record)' => [
                'name' => 'Form 137 / SF10 (Learner Permanent Record)',
                'desc' => 'Official school-to-school permanent record transcript with complete grade history.',
                'mandatory' => ($app['applicant_type'] === 'Transferee' || $app['grade_category'] === 'SHS')
            ]
        ];

        if ($app['grade_category'] === 'SHS') {
            $requiredTypes['ESC / DepEd Voucher Certificate'] = [
                'name' => 'ESC / DepEd Senior High Voucher Certificate',
                'desc' => 'Private ESC certificate or DepEd QVR Voucher Billing grant certificate (if applicable).',
                'mandatory' => false
            ];
        }

        // 4. Merge required list with current upload state
        $mergedList = [];
        $verifiedCount = 0;
        $pendingCount = 0;
        $toFollowCount = 0;
        $deficientCount = 0;

        foreach ($requiredTypes as $typeKey => $meta) {
            $doc = $docsByType[$typeKey] ?? null;

            if ($doc) {
                $status = $doc['status'] ?: 'Pending';
                $submissionMode = $doc['submission_mode'] ?: 'Digital Upload';
                $filePath = $doc['file_path'];
                $origFilename = $doc['original_filename'];
                $notes = $doc['verification_notes'];
                $targetDate = $doc['target_date'];
                $promissoryNote = $doc['promissory_note'];
                $docId = (int)$doc['id'];
                $uploadedAt = $doc['uploaded_at'];
            } else {
                $status = 'To Follow Up';
                $submissionMode = 'To Follow Up';
                $filePath = null;
                $origFilename = null;
                $notes = 'Pending document submission';
                $targetDate = null;
                $promissoryNote = null;
                $docId = null;
                $uploadedAt = null;
            }

            if ($status === 'Verified') {
                $verifiedCount++;
            } elseif ($status === 'Pending' || $status === 'Under Review') {
                $pendingCount++;
            } elseif ($status === 'Deficient' || $status === 'Rejected') {
                $deficientCount++;
            } else {
                $toFollowCount++;
            }

            $mergedList[] = [
                'id'                  => $docId,
                'document_type'       => $typeKey,
                'title'               => $meta['name'],
                'description'         => $meta['desc'],
                'is_mandatory'        => $meta['mandatory'],
                'status'              => $status,
                'submission_mode'     => $submissionMode,
                'file_path'           => $filePath,
                'original_filename'   => $origFilename,
                'verification_notes'  => $notes,
                'target_date'         => $targetDate,
                'promissory_note'     => $promissoryNote,
                'uploaded_at'         => $uploadedAt
            ];
        }

        // Include any extra documents uploaded not in standard list
        foreach ($uploadedDocs as $d) {
            if (!isset($requiredTypes[$d['document_type']])) {
                $mergedList[] = [
                    'id'                  => (int)$d['id'],
                    'document_type'       => $d['document_type'],
                    'title'               => $d['document_type'],
                    'description'         => 'Supplemental submitted document',
                    'is_mandatory'        => false,
                    'status'              => $d['status'],
                    'submission_mode'     => $d['submission_mode'],
                    'file_path'           => $d['file_path'],
                    'original_filename'   => $d['original_filename'],
                    'verification_notes'  => $d['verification_notes'],
                    'target_date'         => $d['target_date'],
                    'promissory_note'     => $d['promissory_note'],
                    'uploaded_at'         => $d['uploaded_at']
                ];
            }
        }

        $totalRequired = count($mergedList);
        $compliancePercentage = $totalRequired > 0 ? round(($verifiedCount / $totalRequired) * 100) : 100;

        Response::success('Student requirements loaded', [
            'has_application' => true,
            'application'     => $app,
            'documents'       => $mergedList,
            'stats'           => [
                'total'                 => $totalRequired,
                'verified'              => $verifiedCount,
                'pending'               => $pendingCount,
                'to_follow'             => $toFollowCount,
                'deficient'             => $deficientCount,
                'compliance_percentage' => $compliancePercentage
            ]
        ]);
    }

    /**
     * Upload / Submit Follow-up Document from Enrolled Student Portal.
     */
    public function uploadFollowUpDocument(): void {
        $user = Auth::requireRole(['student', 'applicant']);
        $db = Database::getConnection();

        // 1. Find linked admission application
        $appStmt = $db->prepare("
            SELECT aa.id, aa.application_no, aa.user_id, aa.student_no, aa.lrn, aa.status as application_status
            FROM admission_applications aa
            WHERE aa.user_id = :uid1 
               OR (aa.student_no IS NOT NULL AND aa.student_no = :stud_id)
               OR aa.id IN (SELECT application_id FROM enrollments WHERE student_id = :uid2)
            ORDER BY aa.id DESC
            LIMIT 1
        ");
        $appStmt->execute([
            'uid1'    => $user['id'],
            'stud_id' => $user['student_id'] ?? '',
            'uid2'    => $user['id']
        ]);
        $app = $appStmt->fetch();

        if (!$app) {
            Response::error('Admission application record not found. Please contact the Registrar.');
        }

        $documentType = trim($_POST['document_type'] ?? '');
        if (!$documentType) {
            Response::error('Document type is required.');
        }

        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            Response::error('Please select a valid document file to upload.');
        }

        $uploaded = Auth::handleUpload($_FILES['document'], 'documents');

        // Check if record exists
        $checkDoc = $db->prepare("
            SELECT id, status FROM admission_documents 
            WHERE application_id = :app_id AND document_type = :doc_type
            LIMIT 1
        ");
        $checkDoc->execute(['app_id' => $app['id'], 'doc_type' => $documentType]);
        $existing = $checkDoc->fetch();

        if ($existing) {
            $upStmt = $db->prepare("
                UPDATE admission_documents 
                SET file_path = :fpath,
                    original_filename = :fname,
                    file_size = :fsize,
                    submission_mode = 'Digital Upload',
                    status = 'Pending',
                    verification_notes = 'Follow-up document submitted via Student Portal. Awaiting staff verification.',
                    uploaded_at = NOW()
                WHERE id = :id
            ");
            $upStmt->execute([
                'fpath' => $uploaded['file_path'],
                'fname' => $uploaded['original_name'],
                'fsize' => $uploaded['file_size'],
                'id'    => $existing['id']
            ]);
            $docId = (int)$existing['id'];
        } else {
            $insStmt = $db->prepare("
                INSERT INTO admission_documents (
                    application_id, document_type, file_path, original_filename, file_size, 
                    submission_mode, status, verification_notes, uploaded_at
                ) VALUES (
                    :app_id, :doc_type, :fpath, :fname, :fsize,
                    'Digital Upload', 'Pending', 'Submitted via Student Portal. Awaiting verification.', NOW()
                )
            ");
            $insStmt->execute([
                'app_id'   => $app['id'],
                'doc_type' => $documentType,
                'fpath'    => $uploaded['file_path'],
                'fname'    => $uploaded['original_name'],
                'fsize'    => $uploaded['file_size']
            ]);
            $docId = (int)$db->lastInsertId();
        }

        // If Form 137 / SF10 was submitted, update student_records status for Records Custodian Tracker
        if (stripos($documentType, '137') !== false || stripos($documentType, 'SF10') !== false) {
            $db->prepare("
                UPDATE student_records 
                SET previous_school_f137_status = 'Received / Verified' 
                WHERE student_id = :uid
            ")->execute(['uid' => $user['id']]);
        }

        Auth::logAudit('STUDENT_FOLLOWUP_DOC_UPLOADED', "Student {$user['username']} submitted follow-up requirement '{$documentType}' (Doc ID: {$docId})", $user['id']);

        Response::success("{$documentType} submitted successfully! Your document is now pending verification by the Registrar / Records Custodian.", [
            'document_id' => $docId,
            'file_path'   => $uploaded['file_path'],
            'status'      => 'Pending'
        ]);
    }
}

