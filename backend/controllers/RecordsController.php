<?php
// backend/controllers/RecordsController.php
namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;
use App\Helpers\Auth;
use PDO;

class RecordsController {
    /**
     * Get Masterlist of Permanent Student Records + Portal Summary Statistics.
     */
    public function getStudentRecords(): void {
        Auth::requireRole(['records', 'admin', 'registrar', 'coordinator']);
        $db = Database::getConnection();

        $search = $_GET['search'] ?? '';
        $gradeLevelId = $_GET['grade_level_id'] ?? '';
        $sectionId = $_GET['section_id'] ?? '';
        $syId = $_GET['school_year_id'] ?? '';
        $f137Status = $_GET['f137_status'] ?? '';

        $sql = "
            SELECT sr.*, 
                   u.student_id as student_number, u.username, u.email,
                   p.first_name, p.middle_name, p.last_name, p.suffix, p.gender, p.contact_number, p.birthdate,
                   gl.name as grade_level_name, gl.category as grade_category,
                   s.name as strand_name, s.code as strand_code,
                   sec.name as section_name, sec.room as section_room,
                   sy.name as school_year_name,
                   (SELECT COUNT(*) FROM student_grades sg WHERE sg.student_id = sr.student_id AND sg.school_year_id = sr.school_year_id) as total_graded_subjects
            FROM student_records sr
            JOIN users u ON sr.student_id = u.id
            JOIN user_profiles p ON u.id = p.user_id
            JOIN grade_levels gl ON sr.grade_level_id = gl.id
            LEFT JOIN strands s ON sr.strand_id = s.id
            JOIN sections sec ON sr.section_id = sec.id
            JOIN school_years sy ON sr.school_year_id = sy.id
            WHERE 1=1
        ";
        $params = [];

        if ($search) {
            $sql .= " AND (u.student_id LIKE :search OR p.first_name LIKE :search OR p.last_name LIKE :search OR sr.lrn LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        if ($gradeLevelId) {
            $sql .= " AND sr.grade_level_id = :gl_id";
            $params['gl_id'] = $gradeLevelId;
        }
        if ($sectionId) {
            $sql .= " AND sr.section_id = :sec_id";
            $params['sec_id'] = $sectionId;
        }
        if ($syId) {
            $sql .= " AND sr.school_year_id = :sy_id";
            $params['sy_id'] = $syId;
        }
        if ($f137Status) {
            $sql .= " AND sr.previous_school_f137_status = :f137_status";
            $params['f137_status'] = $f137Status;
        }

        $sql .= " ORDER BY sr.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        // Compute Dashboard Statistics
        $totalStudents = (int)$db->query("SELECT COUNT(*) FROM student_records")->fetchColumn();
        $pendingDocReqs = (int)$db->query("SELECT COUNT(*) FROM document_requests WHERE status IN ('Pending', 'Processing')")->fetchColumn();
        $honorRollCount = (int)$db->query("SELECT COUNT(*) FROM student_records WHERE general_average >= 90.0")->fetchColumn();
        $pendingF137Count = (int)$db->query("SELECT COUNT(*) FROM student_records WHERE previous_school_f137_status = 'Pending Previous School'")->fetchColumn();

        Response::success('Student records loaded', [
            'records' => $records,
            'stats' => [
                'total_students' => $totalStudents,
                'pending_requests' => $pendingDocReqs,
                'honor_roll_count' => $honorRollCount,
                'pending_f137_count' => $pendingF137Count
            ]
        ]);
    }

    /**
     * Get Complete DepEd SF10 / SF9 Academic Transcript for a student.
     */
    public function getStudentTranscript(int $studentId): void {
        $currentUser = Auth::requireRole(['records', 'admin', 'registrar', 'coordinator', 'student']);

        // Prevent IDOR: Enrolled student can only view their own academic records
        if ($currentUser['role_slug'] === 'student' && (int)$currentUser['id'] !== $studentId) {
            Response::error('Forbidden: You can only view your own academic records.', 403);
        }

        $db = Database::getConnection();

        // 1. Fetch Student Demographics & Profile
        $stmt = $db->prepare("
            SELECT u.id, u.student_id as student_number, u.email,
                   p.first_name, p.middle_name, p.last_name, p.suffix, p.gender, p.birthdate, p.contact_number, p.address,
                   a.lrn, a.birthplace, a.nationality, a.guardian_name, a.guardian_contact, a.last_school_attended, a.applicant_type,
                   gl.name as current_grade_level, s.name as current_strand, s.code as current_strand_code, sec.name as current_section, sec.room as current_room
            FROM users u
            JOIN user_profiles p ON u.id = p.user_id
            LEFT JOIN admission_applications a ON u.id = a.user_id
            LEFT JOIN enrollments e ON u.id = e.student_id AND e.status IN ('Officially Enrolled', 'Enrolled')
            LEFT JOIN grade_levels gl ON e.grade_level_id = gl.id
            LEFT JOIN strands s ON e.strand_id = s.id
            LEFT JOIN sections sec ON e.section_id = sec.id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $studentId]);
        $student = $stmt->fetch();

        if (!$student) {
            Response::error('Student record not found.', 404);
        }

        // 2. Fetch all academic history records across school years
        $recStmt = $db->prepare("
            SELECT sr.*, sy.name as school_year_name, gl.name as grade_level_name, gl.category as grade_category,
                   s.code as strand_code, s.name as strand_name, sec.name as section_name, sec.room as section_room
            FROM student_records sr
            JOIN school_years sy ON sr.school_year_id = sy.id
            JOIN grade_levels gl ON sr.grade_level_id = gl.id
            LEFT JOIN strands s ON sr.strand_id = s.id
            JOIN sections sec ON sr.section_id = sec.id
            WHERE sr.student_id = :id
            ORDER BY sr.school_year_id ASC, sr.grade_level_id ASC
        ");
        $recStmt->execute(['id' => $studentId]);
        $academicHistory = $recStmt->fetchAll();

        // 3. For each academic record, fetch subject grades & core values
        $gradeStmt = $db->prepare("
            SELECT sg.*, sub.code as subject_code, sub.title as subject_title, sub.category as subject_category, sub.lecture_hours, sub.lab_hours, sub.units
            FROM student_grades sg
            JOIN subjects sub ON sg.subject_id = sub.id
            WHERE sg.student_id = :student_id AND sg.school_year_id = :sy_id
            ORDER BY sub.category ASC, sub.code ASC
        ");

        foreach ($academicHistory as &$hist) {
            $gradeStmt->execute(['student_id' => $studentId, 'sy_id' => $hist['school_year_id']]);
            $hist['grades'] = $gradeStmt->fetchAll();

            // Default observed core values summary
            $hist['core_values'] = [
                ['core_value' => 'Maka-Diyos', 'behavior_statement' => 'Expresses one\'s spiritual beliefs while respecting others', 'q1' => 'AO', 'q2' => 'AO', 'q3' => 'AO', 'q4' => 'AO'],
                ['core_value' => 'Makatao', 'behavior_statement' => 'Shows empathy, compassion, and respectful manners to all', 'q1' => 'AO', 'q2' => 'AO', 'q3' => 'AO', 'q4' => 'AO'],
                ['core_value' => 'Makakalikasan', 'behavior_statement' => 'Demonstrates care for natural and immediate environment', 'q1' => 'SO', 'q2' => 'SO', 'q3' => 'AO', 'q4' => 'AO'],
                ['core_value' => 'Makabansa', 'behavior_statement' => 'Promotes pride in Philippine culture and national identity', 'q1' => 'AO', 'q2' => 'AO', 'q3' => 'AO', 'q4' => 'AO']
            ];
        }

        $student['academic_history'] = $academicHistory;

        Response::success('Student transcript loaded', $student);
    }

    /**
     * Get Document Requests Queue.
     */
    public function getDocumentRequests(): void {
        $user = Auth::requireRole(['records', 'admin', 'registrar', 'coordinator', 'student']);
        $db = Database::getConnection();

        $sql = "
            SELECT dr.*,
                   u.student_id as student_number, u.email as student_email,
                   p.first_name, p.middle_name, p.last_name, p.suffix, p.contact_number,
                   gl.name as grade_level_name,
                   s.code as strand_code, s.name as strand_name,
                   sec.name as section_name,
                   proc_p.first_name as processor_first, proc_p.last_name as processor_last
            FROM document_requests dr
            JOIN users u ON dr.student_id = u.id
            JOIN user_profiles p ON u.id = p.user_id
            LEFT JOIN enrollments e ON u.id = e.student_id AND e.status IN ('Officially Enrolled', 'Enrolled')
            LEFT JOIN grade_levels gl ON e.grade_level_id = gl.id
            LEFT JOIN strands s ON e.strand_id = s.id
            LEFT JOIN sections sec ON e.section_id = sec.id
            LEFT JOIN users proc ON dr.processed_by = proc.id
            LEFT JOIN user_profiles proc_p ON proc.id = proc_p.user_id
            WHERE 1=1
        ";
        $params = [];

        // If regular student, only show their own requests
        if ($user['role_slug'] === 'student') {
            $sql .= " AND dr.student_id = :sid";
            $params['sid'] = $user['id'];
        }

        $sql .= " ORDER BY dr.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $requests = $stmt->fetchAll();

        Response::success('Document requests loaded', $requests);
    }

    /**
     * Submit a new Document Request (by student or registrar).
     */
    public function saveDocumentRequest(): void {
        $user = Auth::user();
        $db = Database::getConnection();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $studentId = ($user['role'] === 'student') ? $user['id'] : (int)($data['student_id'] ?? $user['id']);
        $docType = trim($data['document_type'] ?? '');
        $purpose = trim($data['purpose'] ?? '');
        $copies = max(1, (int)($data['copies'] ?? 1));
        $remarks = trim($data['remarks'] ?? '');

        if (!$docType || !$purpose) {
            Response::error('Document type and purpose are required.', 422);
        }

        // Generate Control Number: DOC-YYYY-RANDOM
        $controlNumber = 'DOC-' . date('Y') . '-' . str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);

        $stmt = $db->prepare("
            INSERT INTO document_requests (control_number, student_id, document_type, purpose, status, copies, remarks, requested_at)
            VALUES (:ctrl, :sid, :dtype, :purp, 'Pending', :copies, :rem, NOW())
        ");
        $stmt->execute([
            'ctrl'   => $controlNumber,
            'sid'    => $studentId,
            'dtype'  => $docType,
            'purp'   => $purpose,
            'copies' => $copies,
            'rem'    => $remarks
        ]);

        Response::success('Document request submitted successfully', [
            'id' => $db->lastInsertId(),
            'control_number' => $controlNumber
        ]);
    }

    /**
     * Update Status of a Document Request (by records custodian).
     */
    public function updateRequestStatus(): void {
        Auth::requireRole(['records', 'admin', 'registrar', 'coordinator']);
        $user = Auth::user();
        $db = Database::getConnection();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $requestId = (int)($data['id'] ?? 0);
        $status = $data['status'] ?? '';
        $remarks = $data['remarks'] ?? '';

        if (!$requestId || !$status) {
            Response::error('Request ID and status are required.', 422);
        }

        $releasedAt = ($status === 'Released') ? date('Y-m-d H:i:s') : null;
        $processedAt = date('Y-m-d H:i:s');

        $stmt = $db->prepare("
            UPDATE document_requests
            SET status = :status,
                remarks = :remarks,
                processed_by = :proc_by,
                processed_at = :proc_at,
                released_at = COALESCE(:rel_at, released_at)
            WHERE id = :id
        ");
        $stmt->execute([
            'status'  => $status,
            'remarks' => $remarks,
            'proc_by' => $user['id'],
            'proc_at' => $processedAt,
            'rel_at'  => $releasedAt,
            'id'      => $requestId
        ]);

        Response::success('Document request status updated successfully');
    }

    /**
     * Generate Official DepEd School Form 1 (SF1 - School Master Register)
     */
    public function getSchoolForm1(): void {
        Auth::requireRole(['records', 'admin', 'registrar', 'coordinator']);
        $db = Database::getConnection();
        $sectionId = (int)($_GET['section_id'] ?? 0);

        if (!$sectionId) {
            // Default to first section
            $sectionId = (int)$db->query("SELECT id FROM sections LIMIT 1")->fetchColumn();
        }

        // Section Details & Adviser
        $secStmt = $db->prepare("
            SELECT s.*, gl.name as grade_level_name, gl.category as grade_category,
                   str.code as strand_code, str.name as strand_name,
                   sy.name as school_year_name,
                   p.first_name as adviser_first, p.last_name as adviser_last
            FROM sections s
            JOIN grade_levels gl ON s.grade_level_id = gl.id
            LEFT JOIN strands str ON s.strand_id = str.id
            JOIN school_years sy ON s.school_year_id = sy.id
            LEFT JOIN users adv ON s.adviser_id = adv.id
            LEFT JOIN user_profiles p ON adv.id = p.user_id
            WHERE s.id = :id
        ");
        $secStmt->execute(['id' => $sectionId]);
        $section = $secStmt->fetch();

        // Enrolled Students Roster
        $stuStmt = $db->prepare("
            SELECT e.id as enrollment_id, e.enrollment_date,
                   u.student_id as student_number,
                   p.first_name, p.middle_name, p.last_name, p.suffix, p.gender, p.birthdate, p.contact_number, p.address,
                   a.lrn, a.birthplace, a.nationality, a.guardian_name, a.guardian_contact
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            JOIN user_profiles p ON u.id = p.user_id
            LEFT JOIN admission_applications a ON u.id = a.user_id
            WHERE e.section_id = :sec_id AND e.status IN ('Officially Enrolled', 'Enrolled')
            ORDER BY p.gender DESC, p.last_name ASC
        ");
        $stuStmt->execute(['sec_id' => $sectionId]);
        $students = $stuStmt->fetchAll();

        Response::success('SF1 generated', [
            'section' => $section,
            'students' => $students,
            'stats' => [
                'total_learners' => count($students),
                'male_count'     => count(array_filter($students, fn($s) => strtolower($s['gender']) === 'male')),
                'female_count'   => count(array_filter($students, fn($s) => strtolower($s['gender']) === 'female'))
            ]
        ]);
    }

    /**
     * Generate Official DepEd School Form 5 (SF5 - Report on Promotion & Level of Proficiency)
     */
    public function getSchoolForm5(): void {
        Auth::requireRole(['records', 'admin', 'registrar', 'coordinator']);
        $db = Database::getConnection();
        $sectionId = (int)($_GET['section_id'] ?? 0);

        if (!$sectionId) {
            $sectionId = (int)$db->query("SELECT id FROM sections LIMIT 1")->fetchColumn();
        }

        // Section details
        $secStmt = $db->prepare("
            SELECT s.*, gl.name as grade_level_name, str.code as strand_code, sy.name as school_year_name,
                   p.first_name as adviser_first, p.last_name as adviser_last
            FROM sections s
            JOIN grade_levels gl ON s.grade_level_id = gl.id
            LEFT JOIN strands str ON s.strand_id = str.id
            JOIN school_years sy ON s.school_year_id = sy.id
            LEFT JOIN users adv ON s.adviser_id = adv.id
            LEFT JOIN user_profiles p ON adv.id = p.user_id
            WHERE s.id = :id
        ");
        $secStmt->execute(['id' => $sectionId]);
        $section = $secStmt->fetch();

        // Student records in this section with general averages and promotion status
        $recStmt = $db->prepare("
            SELECT sr.*,
                   u.student_id as student_number,
                   p.first_name, p.middle_name, p.last_name, p.gender
            FROM student_records sr
            JOIN users u ON sr.student_id = u.id
            JOIN user_profiles p ON u.id = p.user_id
            WHERE sr.section_id = :sec_id
            ORDER BY p.gender DESC, sr.general_average DESC
        ");
        $recStmt->execute(['sec_id' => $sectionId]);
        $records = $recStmt->fetchAll();

        // Proficiency breakdown
        $promotedCount = count(array_filter($records, fn($r) => $r['promotion_status'] === 'Promoted'));
        $conditionalCount = count(array_filter($records, fn($r) => $r['promotion_status'] === 'Conditional'));
        $retainedCount = count(array_filter($records, fn($r) => $r['promotion_status'] === 'Retained'));

        Response::success('SF5 generated', [
            'section' => $section,
            'records' => $records,
            'summary' => [
                'promoted' => $promotedCount,
                'conditional' => $conditionalCount,
                'retained' => $retainedCount,
                'total' => count($records)
            ]
        ]);
    }

    /**
     * Get Academic Honor Roll & GWA Leaderboard.
     */
    public function getHonorRoll(): void {
        Auth::requireRole(['records', 'admin', 'registrar', 'coordinator', 'teacher']);
        $db = Database::getConnection();

        $gradeLevelId = $_GET['grade_level_id'] ?? '';
        $strandId = $_GET['strand_id'] ?? '';

        $sql = "
            SELECT sr.*,
                   u.student_id as student_number,
                   p.first_name, p.middle_name, p.last_name, p.suffix, p.gender,
                   gl.name as grade_level_name,
                   s.code as strand_code, s.name as strand_name,
                   sec.name as section_name
            FROM student_records sr
            JOIN users u ON sr.student_id = u.id
            JOIN user_profiles p ON u.id = p.user_id
            JOIN grade_levels gl ON sr.grade_level_id = gl.id
            LEFT JOIN strands s ON sr.strand_id = s.id
            JOIN sections sec ON sr.section_id = sec.id
            WHERE sr.general_average >= 90.0
        ";
        $params = [];

        if ($gradeLevelId) {
            $sql .= " AND sr.grade_level_id = :gl_id";
            $params['gl_id'] = $gradeLevelId;
        }
        if ($strandId) {
            $sql .= " AND sr.strand_id = :str_id";
            $params['str_id'] = $strandId;
        }

        $sql .= " ORDER BY sr.general_average DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $honors = $stmt->fetchAll();

        // Categorize into DepEd Honor Roll Tiers
        $highest = []; // 98.00 - 100.00
        $high = [];    // 95.00 - 97.99
        $withHonors = []; // 90.00 - 94.99

        foreach ($honors as $h) {
            $gwa = (float)$h['general_average'];
            if ($gwa >= 98.0) {
                $h['award_title'] = 'With Highest Honors';
                $highest[] = $h;
            } elseif ($gwa >= 95.0) {
                $h['award_title'] = 'With High Honors';
                $high[] = $h;
            } else {
                $h['award_title'] = 'With Honors';
                $withHonors[] = $h;
            }
        }

        Response::success('Honor roll loaded', [
            'all' => $honors,
            'with_highest_honors' => $highest,
            'with_high_honors' => $high,
            'with_honors' => $withHonors,
            'total_honorees' => count($honors)
        ]);
    }

    /**
     * Update Form 137 / Transferee Compliance Status.
     */
    public function updateTransfereeF137Status(): void {
        Auth::requireRole(['records', 'admin', 'registrar']);
        $db = Database::getConnection();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $recordId = (int)($data['id'] ?? 0);
        $status = $data['status'] ?? 'Not Applicable';

        if (!$recordId) {
            Response::error('Student record ID is required.', 422);
        }

        $stmt = $db->prepare("
            UPDATE student_records
            SET previous_school_f137_status = :status
            WHERE id = :id
        ");
        $stmt->execute(['status' => $status, 'id' => $recordId]);

        Response::success('Transferee Form 137 status updated');
    }
}
