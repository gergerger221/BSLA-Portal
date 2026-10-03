<?php
// backend/controllers/CoordinatorController.php
namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;
use App\Helpers\Auth;
use PDO;

class CoordinatorController {
    /**
     * Get complete curriculum overview: Tracks, Strands, Grade Levels, and Subjects.
     */
    public function getCurriculum(): void {
        Auth::requireRole(['coordinator', 'admin', 'registrar']);
        $db = Database::getConnection();
        $this->ensureCurriculumSchema($db);

        $schoolYears = $db->query("SELECT id, code, name, is_active, is_locked, curriculum_locked, active_semester FROM school_years ORDER BY id DESC")->fetchAll();
        $activeSy = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();

        // Selected SY defaults to active or first SY
        $selectedSyId = !empty($_GET['school_year_id']) ? (int)$_GET['school_year_id'] : ($activeSy ? (int)$activeSy['id'] : 0);
        $selectedSy = null;
        foreach ($schoolYears as $sy) {
            if ((int)$sy['id'] === $selectedSyId) {
                $selectedSy = $sy;
                break;
            }
        }
        if (!$selectedSy && !empty($schoolYears)) {
            $selectedSy = $schoolYears[0];
            $selectedSyId = (int)$selectedSy['id'];
        }

        $tracks = $db->query("SELECT * FROM tracks ORDER BY id ASC")->fetchAll();
        $strands = $db->query("
            SELECT s.*, t.name as track_name, t.code as track_code,
                   (SELECT COUNT(*) FROM subjects sub WHERE sub.strand_id = s.id AND sub.is_active = 1 AND (sub.school_year_id IS NULL OR sub.school_year_id = {$selectedSyId})) as curriculum_subjects_count,
                   (SELECT COUNT(*) FROM sections sec WHERE sec.strand_id = s.id AND sec.is_active = 1) as active_sections_count,
                   (SELECT COALESCE(SUM(sec.current_enrolled), 0) FROM sections sec WHERE sec.strand_id = s.id AND sec.is_active = 1) as enrolled_students_count
            FROM strands s
            JOIN tracks t ON s.track_id = t.id
            ORDER BY s.track_id ASC, s.id ASC
        ")->fetchAll();
        
        $gradeLevels = $db->query("SELECT * FROM grade_levels ORDER BY sequence_order ASC")->fetchAll();
        
        // Fetch subjects for selected school year (JHS Core is permanent; SHS is per-school year)
        $stmtSub = $db->prepare("
            SELECT sub.*, gl.name as grade_level_name, gl.category as grade_category,
                   s.name as strand_name, s.code as strand_code,
                   pre.code as prerequisite_code, pre.title as prerequisite_title,
                   (SELECT COUNT(*) FROM schedules sch WHERE sch.subject_id = sub.id AND sch.is_active = 1) as schedules_count
            FROM subjects sub
            JOIN grade_levels gl ON sub.grade_level_id = gl.id
            LEFT JOIN strands s ON sub.strand_id = s.id
            LEFT JOIN subjects pre ON sub.prerequisite_id = pre.id
            WHERE (
                sub.grade_level_id <= 4 
                OR sub.category = 'JHS Core'
                OR (
                    sub.grade_level_id >= 5 
                    AND (sub.school_year_id = :sy_id OR (sub.school_year_id IS NULL AND :is_active_sy = 1))
                )
            )
            ORDER BY sub.grade_level_id ASC, sub.strand_id ASC, sub.code ASC
        ");
        $stmtSub->execute([
            'sy_id'        => $selectedSyId,
            'is_active_sy' => ($selectedSy && !empty($selectedSy['is_active'])) ? 1 : 0
        ]);
        $subjects = $stmtSub->fetchAll();

        // Calculate approval breakdown counts
        $totalCount = count($subjects);
        $approvedCount = 0;
        $pendingCount = 0;
        $rejectedCount = 0;
        foreach ($subjects as $sub) {
            $status = strtolower($sub['approval_status'] ?? 'approved');
            if ($status === 'approved') $approvedCount++;
            elseif ($status === 'pending') $pendingCount++;
            elseif ($status === 'rejected') $rejectedCount++;
        }

        Response::success('Curriculum details loaded', [
            'tracks'              => $tracks,
            'strands'             => $strands,
            'grade_levels'        => $gradeLevels,
            'subjects'            => $subjects,
            'school_years'        => $schoolYears,
            'selected_school_year'=> $selectedSy,
            'active_school_year'  => $activeSy ?: null,
            'curriculum_locked'   => !empty($selectedSy['curriculum_locked']) ? 1 : 0,
            'counts'              => [
                'total'    => $totalCount,
                'approved' => $approvedCount,
                'pending'  => $pendingCount,
                'rejected' => $rejectedCount
            ]
        ]);
    }

    /**
     * Declare & Lock / Unlock School Year Curriculum (Admin Executive Action).
     */
    public function toggleCurriculumLock(): void {
        $user = Auth::requireRole(['admin']);
        $db = Database::getConnection();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $activeSy = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
        if (!$activeSy) {
            Response::error('No active school year found.');
        }

        $syId = !empty($input['school_year_id']) ? (int)$input['school_year_id'] : (int)$activeSy['id'];
        $sy = $db->query("SELECT * FROM school_years WHERE id = {$syId}")->fetch();
        if (!$sy) {
            Response::error('School year not found.');
        }

        $newLock = !empty($sy['curriculum_locked']) ? 0 : 1;
        $declaredAt = $newLock ? date('Y-m-d H:i:s') : null;

        $stmt = $db->prepare("
            UPDATE school_years 
            SET curriculum_locked = :lock, 
                curriculum_declared_at = :decl_at, 
                curriculum_declared_by = :user_id 
            WHERE id = :id
        ");
        $stmt->execute([
            'lock'    => $newLock,
            'decl_at' => $declaredAt,
            'user_id' => $user['id'],
            'id'      => $syId
        ]);

        $statusText = $newLock ? "OFFICIALLY DECLARED & LOCKED" : "UNLOCKED (DRAFT SETUP MODE)";
        Auth::logAudit('CURRICULUM_LOCK_TOGGLED', "School Year {$sy['name']} curriculum was {$statusText} by Admin {$user['username']}", $user['id']);

        Response::success("Curriculum for {$sy['name']} is now {$statusText}.", [
            'curriculum_locked'      => $newLock,
            'curriculum_declared_at' => $declaredAt,
            'school_year'            => $sy['name']
        ]);
    }

    /**
     * Officially Declare & Switch the Active Senior High School Semester (Admin Executive Action).
     */
    public function declareShsSemester(): void {
        $user = Auth::requireRole(['admin']);
        $db = Database::getConnection();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $semester = trim($input['semester'] ?? '');
        if (!in_array($semester, ['1st Semester', '2nd Semester'], true)) {
            Response::error('Invalid semester. Must be either "1st Semester" or "2nd Semester".', 422);
        }

        $activeSy = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
        if (!$activeSy) {
            Response::error('No active school year found.', 404);
        }

        $syId = !empty($input['school_year_id']) ? (int)$input['school_year_id'] : (int)$activeSy['id'];

        $stmt = $db->prepare("UPDATE school_years SET active_semester = :sem, updated_at = NOW() WHERE id = :id");
        $stmt->execute([
            'sem' => $semester,
            'id'  => $syId
        ]);

        Auth::logAudit('SHS_SEMESTER_DECLARED', "Active Senior High School Semester declared as {$semester} for {$activeSy['name']} by {$user['username']}", $user['id']);

        Response::success("Active Senior High School semester successfully declared as {$semester}.", [
            'school_year_id'  => $syId,
            'school_year'     => $activeSy['name'],
            'active_semester' => $semester
        ]);
    }

    /**
     * Add or update a Subject in the curriculum.
     */
    public function saveSubject(): void {
        $user = Auth::requireRole(['coordinator', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $code = trim($input['code'] ?? '');
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $rawCategory = $input['category'] ?? 'Core';
        $category = 'Core';
        if (stripos($rawCategory, 'Applied') !== false) {
            $category = 'Applied';
        } elseif (stripos($rawCategory, 'Specialized') !== false) {
            $category = 'Specialized';
        } elseif (stripos($rawCategory, 'Institutional') !== false) {
            $category = 'Institutional';
        }

        $gradeLevelId = (int)($input['grade_level_id'] ?? 1);
        $strandId = !empty($input['strand_id']) ? (int)$input['strand_id'] : null;
        $semester = $input['semester'] ?? 'Full Year';
        $lectureHours = (float)($input['lecture_hours'] ?? 4.0);
        $labHours = (float)($input['lab_hours'] ?? 0.0);
        $units = (float)($input['units'] ?? 1.0);
        $prereqId = !empty($input['prerequisite_id']) ? (int)$input['prerequisite_id'] : null;

        if (!$code || !$title) {
            Response::error('Subject code and title are required.');
        }

        $schoolYearId = !empty($input['school_year_id']) ? (int)$input['school_year_id'] : null;
        $approvalStatus = in_array($input['approval_status'] ?? '', ['Approved', 'Pending', 'Rejected'], true) ? $input['approval_status'] : 'Approved';

        $db = Database::getConnection();

        // 1. Guard against editing existing subjects when curriculum is locked
        if ($id) {
            $existing = $db->query("SELECT * FROM subjects WHERE id = {$id}")->fetch();
            $targetSyId = $existing ? ($existing['school_year_id'] ?: 0) : 0;
            if ($targetSyId) {
                $targetSy = $db->query("SELECT * FROM school_years WHERE id = {$targetSyId}")->fetch();
                if (!empty($targetSy['curriculum_locked'])) {
                    Response::error("Cannot modify subject '{$code}' while {$targetSy['name']} curriculum is officially declared and locked. Mid-year DepEd revisions will take effect in the next school year.");
                }
            }

            $stmt = $db->prepare("
                UPDATE subjects SET
                    code = :code, title = :title, description = :description, category = :category, classification = :classification,
                    grade_level_id = :gl_id, strand_id = :strand_id, semester = :semester,
                    lecture_hours = :lec, lab_hours = :lab, units = :units, prerequisite_id = :prereq,
                    school_year_id = :sy_id, approval_status = :status
                WHERE id = :id
            ");
            $stmt->execute([
                'code'           => $code,
                'title'          => $title,
                'description'    => $description,
                'category'       => $category,
                'classification' => $category,
                'gl_id'          => $gradeLevelId,
                'strand_id'      => $strandId,
                'semester'       => $semester,
                'lec'            => $lectureHours,
                'lab'            => $labHours,
                'units'          => $units,
                'prereq'         => $prereqId,
                'sy_id'          => $schoolYearId,
                'status'         => $approvalStatus,
                'id'             => $id
            ]);
            Auth::logAudit('SUBJECT_UPDATED', "Updated subject {$code}", $user['id']);
            Response::success('Subject updated successfully');
        } else {
            // Default to active school year if not specified
            if (!$schoolYearId) {
                $activeSy = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
                $schoolYearId = $activeSy ? (int)$activeSy['id'] : null;
            }

            if ($schoolYearId) {
                $targetSy = $db->query("SELECT * FROM school_years WHERE id = {$schoolYearId}")->fetch();
                if (!empty($targetSy['curriculum_locked'])) {
                    Response::error("Cannot add subjects to {$targetSy['name']} because its curriculum is officially locked.");
                }
            }

            $stmt = $db->prepare("
                INSERT INTO subjects (code, title, description, category, classification, grade_level_id, strand_id, semester, lecture_hours, lab_hours, units, prerequisite_id, school_year_id, approval_status, proposed_by)
                VALUES (:code, :title, :description, :category, :classification, :gl_id, :strand_id, :semester, :lec, :lab, :units, :prereq, :sy_id, :status, :user_id)
            ");
            $stmt->execute([
                'code'           => $code,
                'title'          => $title,
                'description'    => $description,
                'category'       => $category,
                'classification' => $category,
                'gl_id'          => $gradeLevelId,
                'strand_id'      => $strandId,
                'semester'       => $semester,
                'lec'            => $lectureHours,
                'lab'         => $labHours,
                'units'       => $units,
                'prereq'      => $prereqId,
                'sy_id'       => $schoolYearId,
                'status'      => $approvalStatus,
                'user_id'     => $user['id']
            ]);
            Auth::logAudit('SUBJECT_CREATED', "Created subject {$code} in SY {$schoolYearId}", $user['id']);
            Response::success('Subject added to curriculum successfully', ['id' => $db->lastInsertId()], 201);
        }
    }

    /**
     * Batch Carry Over (clone) subjects from a source school year into a target school year.
     */
    public function carryOverSubjects(): void {
        $user = Auth::requireRole(['coordinator', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $sourceSyId = (int)($input['source_school_year_id'] ?? 0);
        $targetSyId = (int)($input['target_school_year_id'] ?? 0);
        $subjectIds = $input['subject_ids'] ?? [];

        if (!$sourceSyId || !$targetSyId) {
            Response::error('Source and target school years are required.');
        }
        if ($sourceSyId === $targetSyId) {
            Response::error('Source and target school years must be different.');
        }
        if (empty($subjectIds) || !is_array($subjectIds)) {
            Response::error('At least one subject must be selected to carry over.');
        }

        $db = Database::getConnection();

        // 1. Ensure target school year exists and is not locked
        $targetSy = $db->query("SELECT * FROM school_years WHERE id = {$targetSyId}")->fetch();
        if (!$targetSy) {
            Response::error('Target school year not found.');
        }
        if (!empty($targetSy['curriculum_locked'])) {
            Response::error("Cannot carry over subjects into {$targetSy['name']} because its curriculum is officially declared and locked.");
        }

        $sourceSy = $db->query("SELECT * FROM school_years WHERE id = {$sourceSyId}")->fetch();
        $sourceSyName = $sourceSy ? $sourceSy['name'] : "SY #{$sourceSyId}";

        // 2. Fetch existing subject codes in target SY to avoid duplicates
        $stmtExisting = $db->prepare("SELECT code FROM subjects WHERE school_year_id = :target_sy");
        $stmtExisting->execute(['target_sy' => $targetSyId]);
        $existingCodes = $stmtExisting->fetchAll(PDO::FETCH_COLUMN);
        $existingMap = array_fill_keys($existingCodes, true);

        // 3. Fetch subjects to clone
        $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
        $stmt = $db->prepare("SELECT * FROM subjects WHERE id IN ($placeholders) AND (school_year_id = ? OR school_year_id IS NULL)");
        $params = array_map('intval', $subjectIds);
        $params[] = $sourceSyId;
        $stmt->execute($params);
        $sourceSubjects = $stmt->fetchAll();

        if (empty($sourceSubjects)) {
            Response::error('No valid subjects found to carry over from the specified source school year.');
        }

        $insertedCount = 0;
        $skippedCount = 0;

        $insertStmt = $db->prepare("
            INSERT INTO subjects (
                code, name, title, description, category, classification, grade_level_id,
                strand_id, semester, lecture_hours, lab_hours, units, is_active,
                school_year_id, approval_status, proposed_by
            ) VALUES (
                :code, :name, :title, :description, :category, :classification, :gl_id,
                :strand_id, :semester, :lec, :lab, :units, 1,
                :sy_id, :status, :user_id
            )
        ");

        $db->beginTransaction();
        try {
            foreach ($sourceSubjects as $sub) {
                if (isset($existingMap[$sub['code']])) {
                    $skippedCount++;
                    continue;
                }

                $insertStmt->execute([
                    'code'           => $sub['code'],
                    'name'           => $sub['name'] ?? $sub['title'],
                    'title'          => $sub['title'],
                    'description'    => $sub['description'] ?? '',
                    'category'       => $sub['category'] ?? 'Core',
                    'classification' => $sub['classification'] ?? 'Core',
                    'gl_id'          => $sub['grade_level_id'],
                    'strand_id'      => $sub['strand_id'],
                    'semester'       => $sub['semester'],
                    'lec'            => $sub['lecture_hours'],
                    'lab'            => $sub['lab_hours'],
                    'units'          => $sub['units'],
                    'sy_id'          => $targetSyId,
                    'status'         => 'Approved',
                    'user_id'        => $user['id']
                ]);
                $insertedCount++;
                $existingMap[$sub['code']] = true;
            }

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            Response::error('Failed to carry over subjects: ' . $e->getMessage());
        }

        Auth::logAudit(
            'CURRICULUM_CARRIED_OVER',
            "Carried over {$insertedCount} subjects from {$sourceSyName} into {$targetSy['name']} (Skipped {$skippedCount} existing duplicates) by {$user['username']}",
            $user['id']
        );

        Response::success("Successfully carried over {$insertedCount} subject(s) to {$targetSy['name']}." . ($skippedCount > 0 ? " ({$skippedCount} duplicate(s) skipped)" : ""), [
            'inserted_count' => $insertedCount,
            'skipped_count'  => $skippedCount,
            'target_sy'      => $targetSy['name']
        ]);
    }

    /**
     * Update approval status of a subject (Approved, Pending, Rejected).
     */
    public function updateSubjectApproval(): void {
        $user = Auth::requireRole(['coordinator', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = (int)($input['subject_id'] ?? 0);
        $status = trim($input['approval_status'] ?? '');

        if (!$id || !in_array($status, ['Approved', 'Pending', 'Rejected'], true)) {
            Response::error('Valid subject ID and approval status (Approved, Pending, Rejected) are required.');
        }

        $db = Database::getConnection();
        $sub = $db->query("SELECT * FROM subjects WHERE id = {$id}")->fetch();
        if (!$sub) {
            Response::error('Subject not found.');
        }

        $stmt = $db->prepare("UPDATE subjects SET approval_status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);

        Auth::logAudit('SUBJECT_APPROVAL_UPDATED', "Subject {$sub['code']} approval status updated to {$status} by {$user['username']}", $user['id']);

        Response::success("Subject {$sub['code']} marked as {$status}.", [
            'subject_id'      => $id,
            'approval_status' => $status
        ]);
    }

    /**
     * Delete or Archive a Subject.
     */
    public function deleteSubject(): void {
        $user = Auth::requireRole(['coordinator', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            Response::error('Valid subject ID is required.');
        }

        $db = Database::getConnection();

        $sub = $db->query("SELECT * FROM subjects WHERE id = {$id}")->fetch();
        if (!$sub) {
            Response::error('Subject not found.');
        }

        // 1. Guard against deleting subjects when THAT school year's curriculum is locked
        $targetSyId = $sub['school_year_id'];
        if ($targetSyId) {
            $targetSy = $db->query("SELECT * FROM school_years WHERE id = {$targetSyId}")->fetch();
            if ($targetSy && !empty($targetSy['curriculum_locked'])) {
                Response::error("Cannot delete subjects while {$targetSy['name']} curriculum is officially declared and locked. Switch {$targetSy['name']} to Draft Mode in the dashboard first.");
            }
        } else {
            $activeSy = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
            if ($activeSy && !empty($activeSy['curriculum_locked'])) {
                Response::error("Cannot delete subjects while {$activeSy['name']} curriculum is officially declared and locked.");
            }
        }

        // 2. Check if subject is referenced in enrollment_subjects or student_grades
        $chk1 = $db->prepare("SELECT COUNT(*) FROM enrollment_subjects WHERE subject_id = :id");
        $chk1->execute(['id' => $id]);
        $enrolledCount = (int)$chk1->fetchColumn();

        $chk2 = $db->prepare("SELECT COUNT(*) FROM student_grades WHERE subject_id = :id");
        $chk2->execute(['id' => $id]);
        $gradeCount = (int)$chk2->fetchColumn();

        if ($enrolledCount > 0 || $gradeCount > 0) {
            // Cannot hard delete because students have academic history; archive it safely
            $db->prepare("UPDATE subjects SET is_active = 0 WHERE id = :id")->execute(['id' => $id]);
            Auth::logAudit('SUBJECT_ARCHIVED', "Archived subject {$sub['code']} ({$sub['title']}) due to active enrollment/grade records", $user['id']);
            Response::success("Subject {$sub['code']} has active enrollments and has been safely archived (deactivated) to protect student academic records.", ['archived' => true]);
        } else {
            // Clear prerequisite references pointing to this subject
            $db->prepare("UPDATE subjects SET prerequisite_id = NULL WHERE prerequisite_id = :id")->execute(['id' => $id]);
            $db->prepare("DELETE FROM schedules WHERE subject_id = :id")->execute(['id' => $id]);
            $db->prepare("DELETE FROM subjects WHERE id = :id")->execute(['id' => $id]);

            Auth::logAudit('SUBJECT_DELETED', "Deleted subject {$sub['code']} ({$sub['title']})", $user['id']);
            Response::success("Subject {$sub['code']} deleted successfully from curriculum.", ['deleted' => true]);
        }
    }

    /**
     * Batch Delete or Archive multiple Subjects simultaneously.
     */
    public function batchDeleteSubjects(): void {
        $user = Auth::requireRole(['coordinator', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $subjectIds = $input['subject_ids'] ?? [];
        if (empty($subjectIds) || !is_array($subjectIds)) {
            Response::error('Please select at least one subject to delete.');
        }

        $cleanIds = array_filter(array_map('intval', $subjectIds));
        if (empty($cleanIds)) {
            Response::error('No valid subject IDs provided.');
        }

        $db = Database::getConnection();

        // 1. Fetch target subjects and verify curriculum lock status for their school year(s)
        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $stmt = $db->prepare("SELECT s.*, sy.name as sy_name, sy.curriculum_locked FROM subjects s LEFT JOIN school_years sy ON s.school_year_id = sy.id WHERE s.id IN ($placeholders)");
        $stmt->execute($cleanIds);
        $subjects = $stmt->fetchAll();

        if (empty($subjects)) {
            Response::error('No matching subjects found in curriculum.');
        }

        // Check if any subject belongs to a locked school year
        foreach ($subjects as $sub) {
            if (!empty($sub['curriculum_locked'])) {
                Response::error("Cannot delete subjects while {$sub['sy_name']} curriculum is officially declared and locked. Switch to Draft Mode in the dashboard first.");
            }
        }

        $deletedCount = 0;
        $archivedCount = 0;

        $chkEnrolled = $db->prepare("SELECT COUNT(*) FROM enrollment_subjects WHERE subject_id = :id");
        $chkGrades = $db->prepare("SELECT COUNT(*) FROM student_grades WHERE subject_id = :id");
        $archiveStmt = $db->prepare("UPDATE subjects SET is_active = 0 WHERE id = :id");
        $clearPrereq = $db->prepare("UPDATE subjects SET prerequisite_id = NULL WHERE prerequisite_id = :id");
        $delSchedule = $db->prepare("DELETE FROM schedules WHERE subject_id = :id");
        $delSubject = $db->prepare("DELETE FROM subjects WHERE id = :id");

        $db->beginTransaction();
        try {
            foreach ($subjects as $sub) {
                $subId = (int)$sub['id'];

                $chkEnrolled->execute(['id' => $subId]);
                $enrolledCount = (int)$chkEnrolled->fetchColumn();

                $chkGrades->execute(['id' => $subId]);
                $gradesCount = (int)$chkGrades->fetchColumn();

                if ($enrolledCount > 0 || $gradesCount > 0) {
                    $archiveStmt->execute(['id' => $subId]);
                    $archivedCount++;
                } else {
                    $clearPrereq->execute(['id' => $subId]);
                    $delSchedule->execute(['id' => $subId]);
                    $delSubject->execute(['id' => $subId]);
                    $deletedCount++;
                }
            }

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            Response::error('Failed to batch delete subjects: ' . $e->getMessage());
        }

        $totalProcessed = $deletedCount + $archivedCount;
        Auth::logAudit(
            'BATCH_SUBJECTS_DELETED',
            "Batch removed {$totalProcessed} subject(s) ({$deletedCount} deleted, {$archivedCount} safely archived) by {$user['username']}",
            $user['id']
        );

        $msg = "Successfully processed {$totalProcessed} subject(s): {$deletedCount} deleted permanently" . ($archivedCount > 0 ? ", {$archivedCount} safely archived." : ".");
        Response::success($msg, [
            'deleted_count'  => $deletedCount,
            'archived_count' => $archivedCount,
            'total'          => $totalProcessed
        ]);
    }

    /**
     * Create or update an Academic Strand.
     */
    public function saveStrand(): void {
        $user = Auth::requireRole(['coordinator', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $trackId = (int)($input['track_id'] ?? 1);
        $code = strtoupper(trim($input['code'] ?? ''));
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $status = in_array($input['status'] ?? '', ['Active', 'Deactivated', 'Archived']) ? $input['status'] : 'Active';
        $isActive = ($status === 'Active') ? 1 : 0;
        $archivedAt = ($status === 'Archived') ? date('Y-m-d H:i:s') : null;

        if (!$code || !$name) {
            Response::error('Strand code and name are required.');
        }

        $db = Database::getConnection();

        $activeSy = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
        if (!empty($activeSy['curriculum_locked'])) {
            Response::error("Cannot create or modify strands while {$activeSy['name']} curriculum is officially declared and locked. Mid-year DepEd revisions will take effect in the next school year.");
        }

        if ($id) {
            $stmt = $db->prepare("
                UPDATE strands SET
                    track_id = :track_id, code = :code, name = :name, description = :description,
                    is_active = :is_active, status = :status, archived_at = :archived_at
                WHERE id = :id
            ");
            $stmt->execute([
                'track_id'    => $trackId,
                'code'        => $code,
                'name'        => $name,
                'description' => $description,
                'is_active'   => $isActive,
                'status'      => $status,
                'archived_at' => $archivedAt,
                'id'          => $id
            ]);
            Auth::logAudit('STRAND_UPDATED', "Updated strand {$code} (Status: {$status})", $user['id']);
            Response::success('Strand updated successfully');
        } else {
            $stmt = $db->prepare("
                INSERT INTO strands (track_id, code, name, description, is_active, status, archived_at)
                VALUES (:track_id, :code, :name, :description, :is_active, :status, :archived_at)
            ");
            $stmt->execute([
                'track_id'    => $trackId,
                'code'        => $code,
                'name'        => $name,
                'description' => $description,
                'is_active'   => $isActive,
                'status'      => $status,
                'archived_at' => $archivedAt
            ]);
            Auth::logAudit('STRAND_CREATED', "Created strand {$code}", $user['id']);
            Response::success('Strand added successfully', ['id' => $db->lastInsertId()], 201);
        }
    }

    /**
     * Toggle Strand status (Active, Deactivated, Archived).
     */
    public function toggleStrandStatus(): void {
        $user = Auth::requireRole(['coordinator', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = (int)($input['id'] ?? 0);
        $status = $input['status'] ?? 'Active';

        if (!$id || !in_array($status, ['Active', 'Deactivated', 'Archived'])) {
            Response::error('Valid Strand ID and Status are required.');
        }

        $db = Database::getConnection();

        $activeSy = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
        if (!empty($activeSy['curriculum_locked'])) {
            Response::error("Cannot create or modify strands while {$activeSy['name']} curriculum is officially declared and locked. Mid-year DepEd revisions will take effect in the next school year.");
        }

        $isActive = ($status === 'Active') ? 1 : 0;
        $archivedAt = ($status === 'Archived') ? date('Y-m-d H:i:s') : null;

        $stmt = $db->prepare("
            UPDATE strands SET
                status = :status,
                is_active = :is_active,
                archived_at = :archived_at
            WHERE id = :id
        ");
        $stmt->execute([
            'status'      => $status,
            'is_active'   => $isActive,
            'archived_at' => $archivedAt,
            'id'          => $id
        ]);

        $st = $db->query("SELECT code FROM strands WHERE id = {$id}")->fetch();
        $code = $st ? $st['code'] : "ID #{$id}";

        Auth::logAudit('STRAND_STATUS_CHANGED', "Changed strand {$code} status to {$status}", $user['id']);
        Response::success("Strand {$code} status updated to {$status}.", ['status' => $status]);
    }

    /**
     * Delete or Remove a Strand (Guarded against active data references).
     */
    public function deleteStrand(): void {
        $user = Auth::requireRole(['coordinator', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            Response::error('Valid Strand ID is required.');
        }

        $db = Database::getConnection();

        // 1. Guard against deleting strands when curriculum is locked
        $activeSy = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
        if (!empty($activeSy['curriculum_locked'])) {
            Response::error("Cannot delete strands while {$activeSy['name']} curriculum is officially declared and locked. If this strand is being phased out by DepEd, mark it as 'Archived/Phased Out' so current students can complete their requirements.", 400);
        }

        // 2. Check references across applications, enrollments, sections, and subjects
        $chkApp = $db->prepare("SELECT COUNT(*) FROM admission_applications WHERE strand_id = :id");
        $chkApp->execute(['id' => $id]);
        $appCount = (int)$chkApp->fetchColumn();

        $chkEnr = $db->prepare("SELECT COUNT(*) FROM enrollments WHERE strand_id = :id");
        $chkEnr->execute(['id' => $id]);
        $enrCount = (int)$chkEnr->fetchColumn();

        $chkSec = $db->prepare("SELECT COUNT(*) FROM sections WHERE strand_id = :id");
        $chkSec->execute(['id' => $id]);
        $secCount = (int)$chkSec->fetchColumn();

        $chkSub = $db->prepare("SELECT COUNT(*) FROM subjects WHERE strand_id = :id");
        $chkSub->execute(['id' => $id]);
        $subCount = (int)$chkSub->fetchColumn();

        $st = $db->query("SELECT * FROM strands WHERE id = {$id}")->fetch();
        if (!$st) {
            Response::error('Strand not found.');
        }

        $totalRefs = $appCount + $enrCount + $secCount + $subCount;

        if ($totalRefs > 0) {
            Response::error("Cannot delete strand '{$st['code']}' because it is currently linked to {$enrCount} enrolled students, {$secCount} sections, {$subCount} curriculum courses, and {$appCount} applications. Please deactivate or archive the strand instead.", 400);
        } else {
            $db->prepare("DELETE FROM fee_structures WHERE strand_id = :id")->execute(['id' => $id]);
            $db->prepare("DELETE FROM strands WHERE id = :id")->execute(['id' => $id]);

            Auth::logAudit('STRAND_DELETED', "Deleted unreferenced strand {$st['code']} ({$st['name']})", $user['id']);
            Response::success("Strand '{$st['code']}' has been permanently deleted.", ['deleted' => true]);
        }
    }

    /**
     * Get Sections and Schedules.
     */
    public function getSections(): void {
        Auth::requireRole(['coordinator', 'admin', 'registrar']);
        $db = Database::getConnection();

        $sections = $db->query("
            SELECT sec.id, sec.school_year_id, sec.grade_level_id, sec.strand_id, sec.name, sec.room, sec.adviser_id,
                   sec.max_capacity, sec.capacity, sec.is_active, sec.status, sec.created_at,
                   gl.name as grade_level_name, gl.category as grade_category,
                   s.name as strand_name, s.code as strand_code,
                   u.username as adviser_username, p.first_name as adviser_first, p.last_name as adviser_last,
                   (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = sec.id AND e.status IN ('Officially Enrolled', 'Enrolled')) as current_enrolled
            FROM sections sec
            JOIN grade_levels gl ON sec.grade_level_id = gl.id
            LEFT JOIN strands s ON sec.strand_id = s.id
            LEFT JOIN users u ON sec.adviser_id = u.id
            LEFT JOIN user_profiles p ON u.id = p.user_id
            WHERE sec.is_active = 1
            ORDER BY sec.grade_level_id ASC, sec.name ASC
        ")->fetchAll();

        $teachers = $db->query("
            SELECT u.id, u.username, p.first_name, p.last_name, p.contact_number
            FROM users u
            JOIN roles r ON u.role_id = r.id
            JOIN user_profiles p ON u.id = p.user_id
            WHERE r.slug IN ('teacher', 'coordinator') AND u.status = 'Active'
        ")->fetchAll();

        Response::success('Sections loaded', [
            'sections' => $sections,
            'teachers' => $teachers
        ]);
    }

    /**
     * Get Students enrolled in a specific section.
    /**
     * Create or edit a Section.
     */
    public function saveSection(): void {
        $user = Auth::requireRole(['coordinator', 'admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $name = trim($input['name'] ?? '');
        $gradeLevelId = (int)($input['grade_level_id'] ?? 1);
        $strandId = !empty($input['strand_id']) ? (int)$input['strand_id'] : null;
        $maxCapacity = (int)($input['max_capacity'] ?? 45);
        $room = trim($input['room'] ?? '');
        $adviserId = !empty($input['adviser_id']) ? (int)$input['adviser_id'] : null;

        if (!$name) {
            Response::error('Section name is required.');
        }

        $db = Database::getConnection();
        $sy = $db->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
        $syId = $sy ? (int)$sy['id'] : 1;

        if ($id) {
            $stmt = $db->prepare("
                UPDATE sections SET
                    name = :name, grade_level_id = :gl_id, strand_id = :strand_id,
                    max_capacity = :cap, room = :room, adviser_id = :adv
                WHERE id = :id
            ");
            $stmt->execute([
                'name'      => $name,
                'gl_id'     => $gradeLevelId,
                'strand_id' => $strandId,
                'cap'       => $maxCapacity,
                'room'      => $room,
                'adv'       => $adviserId,
                'id'        => $id
            ]);
            Auth::logAudit('SECTION_UPDATED', "Updated section {$name}", $user['id']);
            Response::success('Section updated successfully');
        } else {
            $stmt = $db->prepare("
                INSERT INTO sections (school_year_id, grade_level_id, strand_id, name, max_capacity, room, adviser_id)
                VALUES (:sy_id, :gl_id, :strand_id, :name, :cap, :room, :adv)
            ");
            $stmt->execute([
                'sy_id'     => $syId,
                'gl_id'     => $gradeLevelId,
                'strand_id' => $strandId,
                'name'      => $name,
                'cap'       => $maxCapacity,
                'room'      => $room,
                'adv'       => $adviserId
            ]);
            Auth::logAudit('SECTION_CREATED', "Created section {$name}", $user['id']);
            Response::success('Section created successfully', ['id' => $db->lastInsertId()], 201);
        }
    }

    /**
     * Get list of students enrolled in a specific section.
     */
    public function getSectionStudents(?int $sectionId = null): void {
        Auth::requireRole(['coordinator', 'admin', 'registrar']);
        $sectionId = $sectionId ?: (int)($_GET['section_id'] ?? $_POST['section_id'] ?? 0);
        if (!$sectionId) {
            Response::error('Section ID is required.');
        }
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT e.id as enrollment_id, e.student_id as user_id, e.student_no, e.enrollment_no, e.status as enrollment_status,
                   u.username, u.student_id as official_student_id,
                   p.first_name, p.middle_name, p.last_name, p.gender, p.contact_number,
                   a.lrn, a.application_no, a.voucher_status,
                   sec.name as section_name, sec.room as section_room,
                   gl.name as grade_level_name, s.code as strand_code
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            JOIN user_profiles p ON u.id = p.user_id
            JOIN sections sec ON e.section_id = sec.id
            JOIN grade_levels gl ON e.grade_level_id = gl.id
            LEFT JOIN strands s ON e.strand_id = s.id
            LEFT JOIN admission_applications a ON e.application_id = a.id
            WHERE e.section_id = :sec_id AND e.status IN ('Officially Enrolled', 'Enrolled')
            ORDER BY p.last_name ASC, p.first_name ASC
        ");
        $stmt->execute(['sec_id' => $sectionId]);
        $students = $stmt->fetchAll();

        Response::success('Section students loaded', $students);
    }

    /**
     * Transfer an enrolled student to another eligible section.
     */
    public function transferStudentSection(): void {
        $user = Auth::requireRole(['coordinator', 'admin', 'registrar']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $enrollmentId = (int)($input['enrollment_id'] ?? 0);
        $studentUserId = (int)($input['student_id'] ?? 0);
        $targetSectionId = (int)($input['target_section_id'] ?? 0);
        $reason = trim($input['reason'] ?? 'Requested Section Transfer');

        if ((!$enrollmentId && !$studentUserId) || !$targetSectionId) {
            Response::error('Valid Enrollment/Student ID and Target Section ID are required.');
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // 1. Fetch current active enrollment
            if ($enrollmentId) {
                $enrStmt = $db->prepare("SELECT * FROM enrollments WHERE id = :id FOR UPDATE");
                $enrStmt->execute(['id' => $enrollmentId]);
            } else {
                $enrStmt = $db->prepare("SELECT * FROM enrollments WHERE student_id = :uid ORDER BY id DESC LIMIT 1 FOR UPDATE");
                $enrStmt->execute(['uid' => $studentUserId]);
            }
            $enr = $enrStmt->fetch();

            if (!$enr) {
                Response::error('Active enrollment record not found.');
            }

            $currentSectionId = (int)$enr['section_id'];
            if ($currentSectionId === $targetSectionId) {
                Response::error('Student is already in this section.');
            }

            // 2. Validate Target Section
            $secStmt = $db->prepare("SELECT * FROM sections WHERE id = :id FOR UPDATE");
            $secStmt->execute(['id' => $targetSectionId]);
            $targetSec = $secStmt->fetch();

            if (!$targetSec || !$targetSec['is_active']) {
                Response::error('Target section is invalid or inactive.');
            }

            // Check Grade Level match
            if ((int)$targetSec['grade_level_id'] !== (int)$enr['grade_level_id']) {
                Response::error('Target section does not belong to the student\'s grade level.');
            }

            // Check Strand match for SHS
            if (!empty($enr['strand_id']) && (int)$targetSec['strand_id'] !== (int)$enr['strand_id']) {
                Response::error('Target section does not match the student\'s academic strand.');
            }

            // Check Capacity
            if ((int)$targetSec['current_enrolled'] >= (int)$targetSec['max_capacity']) {
                Response::error("Target section '{$targetSec['name']}' is already at maximum capacity ({$targetSec['max_capacity']} students).");
            }

            // 3. Update Seat Capacities
            if ($currentSectionId) {
                $db->prepare("UPDATE sections SET current_enrolled = GREATEST(0, current_enrolled - 1) WHERE id = :id")
                   ->execute(['id' => $currentSectionId]);
            }
            $db->prepare("UPDATE sections SET current_enrolled = current_enrolled + 1 WHERE id = :id")
               ->execute(['id' => $targetSectionId]);

            // 4. Update Enrollment record
            $db->prepare("UPDATE enrollments SET section_id = :sec_id WHERE id = :id")
               ->execute(['sec_id' => $targetSectionId, 'id' => $enr['id']]);

            // 5. Update Student Records & Queue
            $db->prepare("UPDATE student_records SET section_id = :sec_id WHERE student_id = :uid AND school_year_id = :sy_id")
               ->execute(['sec_id' => $targetSectionId, 'uid' => $enr['student_id'], 'sy_id' => $enr['school_year_id']]);

            $db->prepare("UPDATE enrollment_queues SET assigned_section_id = :sec_id WHERE application_id = :app_id")
               ->execute(['sec_id' => $targetSectionId, 'app_id' => $enr['application_id']]);

            // 6. Re-link Subject Schedules to the new section
            $subStmt = $db->prepare("SELECT id, subject_id FROM enrollment_subjects WHERE enrollment_id = :enr_id");
            $subStmt->execute(['enr_id' => $enr['id']]);
            $enrolledSubs = $subStmt->fetchAll();

            $findSch = $db->prepare("SELECT id FROM schedules WHERE section_id = :sec_id AND subject_id = :sub_id LIMIT 1");
            $upSch = $db->prepare("UPDATE enrollment_subjects SET schedule_id = :sch_id WHERE id = :es_id");

            foreach ($enrolledSubs as $es) {
                $findSch->execute(['sec_id' => $targetSectionId, 'sub_id' => $es['subject_id']]);
                $schId = $findSch->fetchColumn();
                $upSch->execute(['sch_id' => $schId ?: null, 'es_id' => $es['id']]);
            }

            $db->commit();

            Auth::logAudit('SECTION_TRANSFERRED', "Transferred student User #{$enr['student_id']} from Section #{$currentSectionId} to Section #{$targetSectionId} ({$targetSec['name']}). Reason: {$reason}", $user['id']);

            Response::success("Student successfully transferred to {$targetSec['name']}!", [
                'enrollment_id'   => $enr['id'],
                'new_section_id'  => $targetSectionId,
                'new_section_name'=> $targetSec['name']
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            Response::error('Failed to transfer section: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Self-migrating database schema helper for curriculum and subjects table
     */
    private function ensureCurriculumSchema(\PDO $db): void {
        try {
            $cols = [
                'school_year_id'  => "ALTER TABLE subjects ADD COLUMN school_year_id INT(11) NULL DEFAULT NULL AFTER prerequisite_id",
                'approval_status' => "ALTER TABLE subjects ADD COLUMN approval_status ENUM('Approved','Pending','Rejected') DEFAULT 'Approved' AFTER is_active",
                'proposed_by'     => "ALTER TABLE subjects ADD COLUMN proposed_by INT(11) NULL DEFAULT NULL AFTER approval_status",
                'description'     => "ALTER TABLE subjects ADD COLUMN description TEXT NULL AFTER title"
            ];
            foreach ($cols as $col => $sql) {
                $check = $db->query("SHOW COLUMNS FROM subjects LIKE '{$col}'")->fetch();
                if (!$check) {
                    $db->exec($sql);
                }
            }
        } catch (\Throwable $e) {
            error_log("[Curriculum-Schema] Schema ensure error: " . $e->getMessage());
        }
    }
}
