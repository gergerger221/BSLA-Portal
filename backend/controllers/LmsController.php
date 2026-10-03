<?php
// backend/controllers/LmsController.php
namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;
use App\Helpers\Auth;
use PDO;

class LmsController {

    /**
     * Retrieve all LMS Content (Announcements, Learning Modules, Assignments) for a Class
     */
    public function getClassContent(): void {
        $user = Auth::requireAuth();
        $db = Database::getConnection();
        $this->ensureQuizSchema($db);

        $sectionId = (int)($_GET['section_id'] ?? $_GET['sectionId'] ?? $_REQUEST['section_id'] ?? 0);
        $subjectId = (int)($_GET['subject_id'] ?? $_GET['subjectId'] ?? $_REQUEST['subject_id'] ?? 0);

        if (!$sectionId || !$subjectId) {
            Response::error('section_id and subject_id are required.', 400);
        }

        // Verify Class Info
        $classInfo = null;
        try {
            $classStmt = $db->prepare("
                SELECT sec.id as section_id, sec.name as section_name, sec.room as section_room,
                       gl.name as grade_level_name, gl.code as grade_level_code,
                       sub.id as subject_id, sub.code as subject_code, sub.name as subject_name, sub.units,
                       u.id as teacher_id, u.username as teacher_username,
                       p.first_name as teacher_first_name, p.last_name as teacher_last_name
                FROM schedules s
                JOIN sections sec ON s.section_id = sec.id
                JOIN grade_levels gl ON sec.grade_level_id = gl.id
                JOIN subjects sub ON s.subject_id = sub.id
                LEFT JOIN users u ON s.teacher_id = u.id
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE s.section_id = :sec_id AND s.subject_id = :sub_id AND s.is_active = 1
                LIMIT 1
            ");
            $classStmt->execute(['sec_id' => $sectionId, 'sub_id' => $subjectId]);
            $classInfo = $classStmt->fetch();
        } catch (\Throwable $e) {
            error_log("[LMS] Class info query warning: " . $e->getMessage());
        }

        if (!$classInfo) {
            // Fallback: Check if section & subject exist even if schedule is unassigned
            try {
                $sec = $db->query("SELECT name FROM sections WHERE id = $sectionId")->fetch();
                $sub = $db->query("SELECT name, code FROM subjects WHERE id = $subjectId")->fetch();
                $classInfo = [
                    'section_id' => $sectionId,
                    'section_name' => $sec['name'] ?? 'Section',
                    'subject_id' => $subjectId,
                    'subject_code' => $sub['code'] ?? '',
                    'subject_name' => $sub['name'] ?? 'Subject',
                    'teacher_id' => null,
                    'teacher_first_name' => 'Faculty',
                    'teacher_last_name' => 'Instructor'
                ];
            } catch (\Throwable $e) {
                $classInfo = [
                    'section_id' => $sectionId,
                    'section_name' => 'Section',
                    'subject_id' => $subjectId,
                    'subject_code' => '',
                    'subject_name' => 'Subject',
                    'teacher_id' => null,
                    'teacher_first_name' => 'Faculty',
                    'teacher_last_name' => 'Instructor'
                ];
            }
        }

        // 1. Fetch Announcements
        $announcements = [];
        try {
            $annStmt = $db->prepare("
                SELECT a.*, u.username as author_username,
                       p.first_name as author_first_name, p.last_name as author_last_name
                FROM lms_announcements a
                LEFT JOIN users u ON a.teacher_id = u.id
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE a.section_id = :sec_id AND a.subject_id = :sub_id
                ORDER BY a.is_pinned DESC, a.created_at DESC
            ");
            $annStmt->execute(['sec_id' => $sectionId, 'sub_id' => $subjectId]);
            $announcements = $annStmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            error_log("[LMS] Announcements fetch warning: " . $e->getMessage());
            $announcements = [];
        }

        // 2. Fetch Learning Modules
        $modules = [];
        try {
            $modStmt = $db->prepare("
                SELECT m.*, u.username as uploader_username,
                       p.first_name as uploader_first_name, p.last_name as uploader_last_name
                FROM lms_modules m
                LEFT JOIN users u ON m.teacher_id = u.id
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE m.section_id = :sec_id AND m.subject_id = :sub_id
                ORDER BY FIELD(m.quarter, '1st Quarter', '2nd Quarter', '3rd Quarter', '4th Quarter'), m.week_label ASC, m.id DESC
            ");
            $modStmt->execute(['sec_id' => $sectionId, 'sub_id' => $subjectId]);
            $modules = $modStmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            error_log("[LMS] Modules fetch warning: " . $e->getMessage());
            $modules = [];
        }

        // 3. Fetch Assignments
        $assignments = [];
        try {
            $asgWhere = "WHERE asg.section_id = :sec_id AND asg.subject_id = :sub_id";
            if ($user['role_slug'] === 'student') {
                $asgWhere .= " AND (asg.status = 'published' OR asg.status IS NULL)";
            }

            $asgStmt = $db->prepare("
                SELECT asg.*,
                       (SELECT COUNT(*) FROM lms_submissions lsub WHERE lsub.assignment_id = asg.id) as total_submissions,
                       (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = asg.section_id AND e.status IN ('Officially Enrolled', 'Enrolled')) as total_learners
                FROM lms_assignments asg
                $asgWhere
                ORDER BY (CASE WHEN asg.status = 'draft' THEN 0 ELSE 1 END) ASC, asg.due_date DESC
            ");
            $asgStmt->execute(['sec_id' => $sectionId, 'sub_id' => $subjectId]);
            $assignments = $asgStmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            error_log("[LMS] Assignments fetch warning: " . $e->getMessage());
            $assignments = [];
        }

        // Process quiz questions and security sanitization for students
        foreach ($assignments as &$asg) {
            $asg['status'] = $asg['status'] ?? 'published';
            $asg['submission_format'] = $asg['submission_format'] ?? 'standard';
            if (!empty($asg['quiz_questions'])) {
                $parsed = json_decode($asg['quiz_questions'], true);
                if (is_array($parsed)) {
                    if ($user['role_slug'] === 'student') {
                        // Strip correct answer key from student payload to prevent devtools inspection!
                        foreach ($parsed as &$q) {
                            unset($q['correct_answer']);
                        }
                        unset($q);
                    }
                    $asg['quiz_questions'] = $parsed;
                } else {
                    $asg['quiz_questions'] = [];
                }
            } else {
                $asg['quiz_questions'] = [];
            }
        }
        unset($asg);

        // If user is a student, attach their own submission to each assignment
        if ($user['role_slug'] === 'student' && !empty($assignments)) {
            try {
                $subStmt = $db->prepare("
                    SELECT * FROM lms_submissions 
                    WHERE student_id = :sid AND assignment_id = :asg_id 
                    LIMIT 1
                ");
                foreach ($assignments as &$a) {
                    $subStmt->execute(['sid' => $user['id'], 'asg_id' => $a['id']]);
                    $mySub = $subStmt->fetch();
                    if ($mySub) {
                        if (!empty($mySub['quiz_answers'])) {
                            $mySub['quiz_answers'] = json_decode($mySub['quiz_answers'], true) ?: [];
                        }
                        $a['my_submission'] = $mySub;
                    } else {
                        $a['my_submission'] = null;
                    }
                }
                unset($a);
            } catch (\Throwable $e) {
                error_log("[LMS] Submissions fetch warning: " . $e->getMessage());
            }
        }

        Response::success('Class LMS content retrieved', [
            'class_info'    => $classInfo,
            'announcements' => $announcements,
            'modules'       => $modules,
            'assignments'   => $assignments
        ]);
    }

    /**
     * Helper to parse target sections from array, json string, or comma-separated pairs
     * Returns unique array of ['section_id' => int, 'subject_id' => int]
     */
    private function parseTargetSections($targetSections, int $defaultSecId, int $defaultSubId): array {
        $targets = [];
        if ($defaultSecId > 0 && $defaultSubId > 0) {
            $targets["{$defaultSecId}-{$defaultSubId}"] = [
                'section_id' => $defaultSecId,
                'subject_id' => $defaultSubId
            ];
        }

        if (is_string($targetSections)) {
            $decoded = json_decode($targetSections, true);
            if (is_array($decoded)) {
                $targetSections = $decoded;
            } else {
                $targetSections = array_filter(array_map('trim', explode(',', $targetSections)));
            }
        }

        if (is_array($targetSections)) {
            foreach ($targetSections as $item) {
                if (is_array($item) && !empty($item['section_id']) && !empty($item['subject_id'])) {
                    $sec = (int)$item['section_id'];
                    $sub = (int)$item['subject_id'];
                    $targets["{$sec}-{$sub}"] = ['section_id' => $sec, 'subject_id' => $sub];
                } elseif (is_string($item) && strpos($item, '-') !== false) {
                    $parts = explode('-', $item);
                    $sec = (int)($parts[0] ?? 0);
                    $sub = (int)($parts[1] ?? 0);
                    if ($sec > 0 && $sub > 0) {
                        $targets["{$sec}-{$sub}"] = ['section_id' => $sec, 'subject_id' => $sub];
                    }
                }
            }
        }

        return array_values($targets);
    }

    /**
     * Check if a Senior High School subject belongs to an inactive semester.
     */
    private function checkShsSemesterActive(PDO $db, int $subjectId): void {
        $stmt = $db->prepare("
            SELECT s.semester, gl.category as grade_category
            FROM subjects s
            JOIN grade_levels gl ON s.grade_level_id = gl.id
            WHERE s.id = :id
        ");
        $stmt->execute(['id' => $subjectId]);
        $sub = $stmt->fetch();

        if ($sub && $sub['grade_category'] === 'SHS' && !empty($sub['semester']) && $sub['semester'] !== 'Full Year') {
            $sy = $db->query("SELECT active_semester FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
            if ($sy && !empty($sy['active_semester']) && $sub['semester'] !== $sy['active_semester']) {
                Response::error("Cannot post or modify LMS content for {$sub['semester']} subjects. This academic term has concluded and is now in Read-Only archive mode.", 403);
            }
        }
    }

    /**
     * Filter targets to strictly active semester classes only
     */
    private function filterActiveTargets(PDO $db, array $targets): array {
        $sy = $db->query("SELECT active_semester FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
        $activeSemester = $sy['active_semester'] ?? '1st Semester';

        $stmt = $db->prepare("
            SELECT s.id, s.semester, gl.category as grade_category
            FROM subjects s
            JOIN grade_levels gl ON s.grade_level_id = gl.id
            WHERE s.id = :id
        ");

        $filtered = [];
        foreach ($targets as $tgt) {
            $subId = (int)($tgt['subject_id'] ?? 0);
            if (!$subId) continue;

            $stmt->execute(['id' => $subId]);
            $sub = $stmt->fetch();

            if ($sub && $sub['grade_category'] === 'SHS' && !empty($sub['semester']) && $sub['semester'] !== 'Full Year') {
                if ($sub['semester'] !== $activeSemester) {
                    // Drop inactive semester targets
                    continue;
                }
            }
            $filtered[] = $tgt;
        }

        return $filtered;
    }

    /**
     * Save / Update a Class Announcement (Supports Multi-Section Broadcast)
     */
    public function saveAnnouncement(): void {
        $user = Auth::requireRole(['teacher'], false);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = (int)($input['id'] ?? 0);
        $sectionId = (int)($input['section_id'] ?? 0);
        $subjectId = (int)($input['subject_id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        $isPinned = !empty($input['is_pinned']) ? 1 : 0;
        $targetSections = $input['target_sections'] ?? null;

        if (!$sectionId || !$subjectId || !$title || !$content) {
            Response::error('Section, subject, title, and message content are required.');
        }

        $db = Database::getConnection();
        $this->checkShsSemesterActive($db, $subjectId);

        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE lms_announcements 
                SET title = :title, content = :content, is_pinned = :pinned 
                WHERE id = :id
            ");
            $stmt->execute(['title' => $title, 'content' => $content, 'pinned' => $isPinned, 'id' => $id]);
            Response::success('Announcement updated successfully');
        } else {
            $targets = $this->parseTargetSections($targetSections, $sectionId, $subjectId);
            $targets = $this->filterActiveTargets($db, $targets);
            if (empty($targets)) {
                Response::error('Cannot post announcement: all selected class sections belong to an inactive semester.', 403);
            }
            $stmt = $db->prepare("
                INSERT INTO lms_announcements (section_id, subject_id, teacher_id, title, content, is_pinned, created_at)
                VALUES (:sec_id, :sub_id, :tid, :title, :content, :pinned, NOW())
            ");
            $count = 0;
            foreach ($targets as $tgt) {
                $stmt->execute([
                    'sec_id'  => $tgt['section_id'],
                    'sub_id'  => $tgt['subject_id'],
                    'tid'     => $user['id'],
                    'title'   => $title,
                    'content' => $content,
                    'pinned'  => $isPinned
                ]);
                $count++;
            }
            $msg = $count > 1 
                ? "Announcement broadcasted to {$count} class sections." 
                : "Announcement posted to class stream.";
            Response::success($msg, ['count' => $count]);
        }
    }

    /**
     * Delete a Class Announcement
     */
    public function deleteAnnouncement(): void {
        $user = Auth::requireRole(['teacher'], false);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? 0);

        if (!$id) {
            Response::error('Announcement ID required.');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT subject_id FROM lms_announcements WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $ann = $stmt->fetch();
        if ($ann) {
            $this->checkShsSemesterActive($db, (int)$ann['subject_id']);
        }

        $stmt = $db->prepare("DELETE FROM lms_announcements WHERE id = :id");
        $stmt->execute(['id' => $id]);
        Response::success('Announcement deleted');
    }

    /**
     * Upload a Learning Module / Handout (Supports Multi-Section Collective Distribution)
     */
    public function uploadModule(): void {
        $user = Auth::requireRole(['teacher'], false);
        $input = $_POST;

        $sectionId = (int)($input['section_id'] ?? 0);
        $subjectId = (int)($input['subject_id'] ?? 0);
        $quarter = trim($input['quarter'] ?? '1st Quarter');
        $weekLabel = trim($input['week_label'] ?? 'Week 1');
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $externalUrl = trim($input['external_url'] ?? '');
        $targetSections = $input['target_sections'] ?? null;

        if (!$sectionId || !$subjectId || !$title) {
            Response::error('Section, subject, and module title are required.');
        }

        $db = Database::getConnection();
        $this->checkShsSemesterActive($db, $subjectId);

        $filePath = null;
        $fileName = null;
        $fileSizeKb = 0;

        // Process File Upload if provided
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file'];
            $maxBytes = 15 * 1024 * 1024; // 15MB limit
            if ($file['size'] > $maxBytes) {
                Response::error('File size exceeds 15MB limit.');
            }

            $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'jpg', 'jpeg', 'png', 'webp', 'txt'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions)) {
                Response::error('Invalid file type. Allowed: PDF, Word, PowerPoint, Excel, ZIP, JPG, PNG, WEBP, TXT.');
            }

            $uploadDir = __DIR__ . '/../uploads/lms/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $safeFilename = 'mod_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $safeFilename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $filePath = 'uploads/lms/' . $safeFilename;
                $fileName = $file['name'];
                $fileSizeKb = (int)round($file['size'] / 1024);
            } else {
                Response::error('Failed to store uploaded file on server.');
            }
        }

        if (!$filePath && !$externalUrl) {
            Response::error('Please upload a file or provide an external reference link.');
        }

        $targets = $this->parseTargetSections($targetSections, $sectionId, $subjectId);
        $targets = $this->filterActiveTargets($db, $targets);
        if (empty($targets)) {
            Response::error('Cannot upload module: all selected class sections belong to an inactive semester.', 403);
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO lms_modules (section_id, subject_id, teacher_id, quarter, week_label, title, description, file_path, file_name, file_size_kb, external_url, created_at)
            VALUES (:sec_id, :sub_id, :tid, :quarter, :week, :title, :desc, :fpath, :fname, :fsize, :url, NOW())
        ");

        $count = 0;
        foreach ($targets as $tgt) {
            $stmt->execute([
                'sec_id'    => $tgt['section_id'],
                'sub_id'    => $tgt['subject_id'],
                'tid'       => $user['id'],
                'quarter'   => $quarter,
                'week'      => $weekLabel,
                'title'     => $title,
                'desc'      => $description,
                'fpath'     => $filePath,
                'fname'     => $fileName,
                'fsize'     => $fileSizeKb,
                'url'       => $externalUrl ?: null
            ]);
            $count++;
        }

        $msg = $count > 1 
            ? "Learning module distributed to {$count} class sections." 
            : "Learning module uploaded successfully.";
        Response::success($msg, ['count' => $count]);
    }

    /**
     * Delete a Learning Module
     */
    public function deleteModule(): void {
        $user = Auth::requireRole(['teacher'], false);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? 0);

        if (!$id) {
            Response::error('Module ID is required.');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT subject_id, file_path FROM lms_modules WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $module = $stmt->fetch();

        if ($module) {
            $this->checkShsSemesterActive($db, (int)$module['subject_id']);
            if (!empty($module['file_path'])) {
                // Check if another module row is still reusing this shared file
                $refCheck = $db->prepare("SELECT COUNT(*) FROM lms_modules WHERE file_path = :fpath AND id != :id");
                $refCheck->execute(['fpath' => $module['file_path'], 'id' => $id]);
                $otherRefs = (int)$refCheck->fetchColumn();

                if ($otherRefs === 0) {
                    $physicalPath = __DIR__ . '/../' . $module['file_path'];
                    if (file_exists($physicalPath)) {
                        @unlink($physicalPath);
                    }
                }
            }
        }

        $db->prepare("DELETE FROM lms_modules WHERE id = :id")->execute(['id' => $id]);
        Response::success('Module removed successfully');
    }

    /**
     * Save / Update an Assignment (Supports Multi-Section Broadcast)
     */
    public function saveAssignment(): void {
        $user = Auth::requireRole(['teacher'], false);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = (int)($input['id'] ?? 0);
        $sectionId = (int)($input['section_id'] ?? 0);
        $subjectId = (int)($input['subject_id'] ?? 0);
        $quarter = trim($input['quarter'] ?? '1st Quarter');
        $title = trim($input['title'] ?? '');
        $instructions = trim($input['instructions'] ?? '');
        $taskType = trim($input['task_type'] ?? 'Written Work');
        $status = strtolower(trim($input['status'] ?? 'published'));
        if (!in_array($status, ['draft', 'published'], true)) {
            $status = 'published';
        }
        $submissionFormat = trim($input['submission_format'] ?? 'standard');
        $maxScore = (int)($input['max_score'] ?? 50);
        $dueDate = trim($input['due_date'] ?? '');
        $targetSections = $input['target_sections'] ?? null;
        $quizQuestions = $input['quiz_questions'] ?? null;

        if (!$sectionId || !$subjectId) {
            Response::error('Section and subject are required.');
        }

        if ($status === 'published') {
            if (!$title || !$dueDate) {
                Response::error('Assignment Title and Due Date are required to publish to learners.');
            }
        } else {
            // For Draft: provide friendly defaults if left empty by teacher
            if (empty($title)) {
                $title = 'Untitled Draft Task';
            }
            if (empty($dueDate)) {
                $dueDate = date('Y-m-d 23:59:59', strtotime('+7 days'));
            }
        }

        $quizJson = null;
        if ($submissionFormat === 'quiz') {
            $decoded = is_string($quizQuestions) ? json_decode($quizQuestions, true) : (is_array($quizQuestions) ? $quizQuestions : []);
            if ($status === 'published' && empty($decoded)) {
                Response::error('Please create at least one question (Multiple Choice, Identification, or Essay) before publishing the interactive quiz.');
            }

            // Auto-calculate maximum score from question points and normalize question IDs
            $computedMaxScore = 0;
            if (!empty($decoded)) {
                foreach ($decoded as $idx => &$q) {
                    if (empty($q['id'])) {
                        $q['id'] = 'q_' . ($idx + 1) . '_' . bin2hex(random_bytes(3));
                    }
                    $pts = max(1, (int)($q['points'] ?? 1));
                    $q['points'] = $pts;
                    $computedMaxScore += $pts;
                }
                unset($q);
            }

            $maxScore = $computedMaxScore > 0 ? $computedMaxScore : $maxScore;
            $quizJson = !empty($decoded) ? json_encode($decoded) : null;
        }

        $db = Database::getConnection();
        $this->ensureQuizSchema($db);
        $this->checkShsSemesterActive($db, $subjectId);

        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE lms_assignments 
                SET quarter = :quarter, title = :title, instructions = :inst, 
                    task_type = :ttype, status = :status, submission_format = :sformat, quiz_questions = :qquestions,
                    max_score = :max_score, due_date = :due 
                WHERE id = :id
            ");
            $stmt->execute([
                'quarter'   => $quarter,
                'title'     => $title,
                'inst'      => $instructions,
                'ttype'     => $taskType,
                'status'    => $status,
                'sformat'   => $submissionFormat,
                'qquestions'=> $quizJson,
                'max_score' => $maxScore,
                'due'       => $dueDate,
                'id'        => $id
            ]);
            $msg = $status === 'draft' ? 'Task saved as draft.' : 'Assignment updated successfully.';
            Response::success($msg);
        } else {
            $targets = $this->parseTargetSections($targetSections, $sectionId, $subjectId);
            $targets = $this->filterActiveTargets($db, $targets);
            if (empty($targets)) {
                Response::error('Cannot create assignment: all selected class sections belong to an inactive semester.', 403);
            }
            $stmt = $db->prepare("
                INSERT INTO lms_assignments (section_id, subject_id, teacher_id, quarter, title, instructions, task_type, status, submission_format, quiz_questions, max_score, due_date, created_at)
                VALUES (:sec_id, :sub_id, :tid, :quarter, :title, :inst, :ttype, :status, :sformat, :qquestions, :max_score, :due, NOW())
            ");
            $count = 0;
            $firstId = 0;
            foreach ($targets as $tgt) {
                $stmt->execute([
                    'sec_id'    => $tgt['section_id'],
                    'sub_id'    => $tgt['subject_id'],
                    'tid'       => $user['id'],
                    'quarter'   => $quarter,
                    'title'     => $title,
                    'inst'      => $instructions,
                    'ttype'     => $taskType,
                    'status'    => $status,
                    'sformat'   => $submissionFormat,
                    'qquestions'=> $quizJson,
                    'max_score' => $maxScore,
                    'due'       => $dueDate
                ]);
                if ($count === 0) {
                    $firstId = (int)$db->lastInsertId();
                }
                $count++;
            }

            if ($status === 'draft') {
                $msg = $count > 1 
                    ? "Task saved as draft across {$count} sections (hidden from learners)." 
                    : "Task saved as draft (hidden from learners until published).";
            } else {
                $msg = $count > 1 
                    ? ($submissionFormat === 'quiz' ? "Interactive quiz published to {$count} class sections." : "Assignment task published to {$count} class sections.")
                    : ($submissionFormat === 'quiz' ? "Interactive online quiz published successfully." : "Assignment published to class.");
            }
            Response::success($msg, ['id' => $firstId, 'count' => $count, 'status' => $status]);
        }
    }

    /**
     * Publish a Draft Assignment Immediately
     */
    public function publishAssignment(): void {
        $user = Auth::requireRole(['teacher'], false);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['assignment_id'] ?? 0);

        if (!$id) {
            Response::error('Assignment ID is required.', 400);
        }

        $db = Database::getConnection();
        $this->ensureQuizSchema($db);

        $stmt = $db->prepare("SELECT * FROM lms_assignments WHERE id = :id AND teacher_id = :tid");
        $stmt->execute(['id' => $id, 'tid' => $user['id']]);
        $asg = $stmt->fetch();

        if (!$asg) {
            Response::error('Assignment draft not found or unauthorized.', 404);
        }

        if (empty($asg['title']) || $asg['title'] === 'Untitled Draft Task') {
            Response::error('Please provide a specific Title before publishing this draft to students.', 422);
        }
        if (empty($asg['due_date'])) {
            Response::error('Please set a valid Due Date & Time before publishing.', 422);
        }
        if ($asg['submission_format'] === 'quiz') {
            $questions = json_decode($asg['quiz_questions'] ?? '[]', true);
            if (empty($questions)) {
                Response::error('Please add at least one question before publishing the interactive quiz.', 422);
            }
        }

        $updateStmt = $db->prepare("UPDATE lms_assignments SET status = 'published' WHERE id = :id AND teacher_id = :tid");
        $updateStmt->execute(['id' => $id, 'tid' => $user['id']]);

        Response::success('Task officially published and released to all class learners.');
    }

    /**
     * Delete an Assignment
     */
    public function deleteAssignment(): void {
        $user = Auth::requireRole(['teacher'], false);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? 0);

        if (!$id) {
            Response::error('Assignment ID is required.');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT subject_id FROM lms_assignments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $asg = $stmt->fetch();
        if ($asg) {
            $this->checkShsSemesterActive($db, (int)$asg['subject_id']);
        }

        // Remove submissions files
        $subStmt = $db->prepare("SELECT submission_file FROM lms_submissions WHERE assignment_id = :id");
        $subStmt->execute(['id' => $id]);
        $submissions = $subStmt->fetchAll();
        foreach ($submissions as $s) {
            if (!empty($s['submission_file'])) {
                $p = __DIR__ . '/../' . $s['submission_file'];
                if (file_exists($p)) @unlink($p);
            }
        }

        $db->prepare("DELETE FROM lms_submissions WHERE assignment_id = :id")->execute(['id' => $id]);
        $db->prepare("DELETE FROM lms_assignments WHERE id = :id")->execute(['id' => $id]);

        Response::success('Assignment and submissions deleted');
    }

    /**
     * Submit an Assignment (Learner Endpoint)
     */
    public function submitAssignment(): void {
        $user = Auth::requireRole(['student']);
        $rawJson = json_decode(file_get_contents('php://input'), true);
        $input = is_array($rawJson) ? $rawJson : $_POST;

        $assignmentId = (int)($input['assignment_id'] ?? 0);
        $submissionText = trim($input['submission_text'] ?? '');

        if (!$assignmentId) {
            Response::error('Assignment ID is required.');
        }

        $db = Database::getConnection();
        $asgStmt = $db->prepare("
            SELECT asg.*, sub.semester as subject_semester, gl.category as grade_category
            FROM lms_assignments asg
            JOIN subjects sub ON asg.subject_id = sub.id
            JOIN grade_levels gl ON sub.grade_level_id = gl.id
            WHERE asg.id = :id
        ");
        $asgStmt->execute(['id' => $assignmentId]);
        $assignment = $asgStmt->fetch();

        if (!$assignment) {
            Response::error('Assignment not found.', 404);
        }

        // Restrict submissions if this subject is from an inactive SHS semester
        if ($assignment['grade_category'] === 'SHS' && !empty($assignment['subject_semester'])) {
            $activeSy = $db->query("SELECT active_semester FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
            if ($activeSy && !empty($activeSy['active_semester']) && $assignment['subject_semester'] !== $activeSy['active_semester'] && $assignment['subject_semester'] !== 'Full Year') {
                Response::error("Submissions are closed for {$assignment['subject_semester']} subjects. This academic term has concluded and is now in Read-Only archive mode.", 403);
            }
        }

        // Determine if late
        $isLate = (strtotime(date('Y-m-d H:i:s')) > strtotime($assignment['due_date']));
        $status = $isLate ? 'Late' : 'Submitted';

        $isQuiz = ($assignment['submission_format'] ?? 'standard') === 'quiz';
        $quizAnswersJson = null;
        $autoGradedScore = null;
        $evalBreakdown = null;
        $score = null;
        $teacherFeedback = null;
        $hasEssay = false;

        if ($isQuiz) {
            $studentAnswers = $input['quiz_answers'] ?? [];
            if (is_string($studentAnswers)) {
                $studentAnswers = json_decode($studentAnswers, true) ?: [];
            }
            if (!is_array($studentAnswers)) {
                $studentAnswers = [];
            }

            $origQuestions = !empty($assignment['quiz_questions']) 
                ? (json_decode($assignment['quiz_questions'], true) ?: []) 
                : [];

            if (empty($origQuestions)) {
                Response::error('This quiz has no registered questions.', 400);
            }

            $autoGradedScore = 0;
            $evalBreakdown = [];

            foreach ($origQuestions as $q) {
                $qid = $q['id'];
                $type = $q['type'] ?? 'multiple_choice';
                $pts = (int)($q['points'] ?? 1);
                $studentAnswer = trim((string)($studentAnswers[$qid] ?? ''));
                $isCorrect = false;
                $earnedPts = 0;

                if ($type === 'multiple_choice') {
                    $correctAnswer = trim((string)($q['correct_answer'] ?? ''));
                    if ($studentAnswer !== '' && strcasecmp($studentAnswer, $correctAnswer) === 0) {
                        $isCorrect = true;
                        $earnedPts = $pts;
                        $autoGradedScore += $pts;
                    }
                } elseif ($type === 'identification') {
                    $correctAnswer = trim((string)($q['correct_answer'] ?? ''));
                    $normStudent = preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower($studentAnswer));
                    $normCorrect = preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower($correctAnswer));
                    if ($normStudent !== '' && $normStudent === $normCorrect) {
                        $isCorrect = true;
                        $earnedPts = $pts;
                        $autoGradedScore += $pts;
                    }
                } elseif ($type === 'essay') {
                    $hasEssay = true;
                    $earnedPts = null; // Pending teacher evaluation
                }

                $evalBreakdown[$qid] = [
                    'question'        => $q['question'] ?? '',
                    'type'            => $type,
                    'options'         => $q['options'] ?? [],
                    'student_answer'  => $studentAnswer,
                    'correct_answer'  => ($type !== 'essay' ? ($q['correct_answer'] ?? '') : null),
                    'points_possible' => $pts,
                    'points_earned'   => $earnedPts,
                    'is_correct'      => $isCorrect
                ];
            }

            $quizAnswersJson = json_encode($evalBreakdown);
            
            if (!$hasEssay) {
                $status = 'Graded';
                $score = $autoGradedScore;
                $teacherFeedback = "Auto-graded online quiz: {$autoGradedScore} / {$assignment['max_score']} pts.";
            } else {
                $status = $isLate ? 'Late' : 'Submitted';
                $score = null; // Strictly NULL so score is hidden until teacher evaluates the essay!
                $teacherFeedback = "Objective items recorded ({$autoGradedScore} pts). Essay answer(s) submitted for teacher manual evaluation.";
            }

            $submissionText = "Submitted online interactive quiz ({$assignment['title']})";
        }

        $filePath = null;
        if (!$isQuiz && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file'];
            $maxBytes = 15 * 1024 * 1024; // 15MB
            if ($file['size'] > $maxBytes) {
                Response::error('File exceeds 15MB limit.');
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'jpg', 'jpeg', 'png'];
            if (!in_array($ext, $allowedExtensions)) {
                Response::error('Invalid file format.');
            }

            $uploadDir = __DIR__ . '/../uploads/lms/submissions/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $safeFilename = 'sub_' . $user['id'] . '_' . bin2hex(random_bytes(6)) . '_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $safeFilename)) {
                $filePath = 'uploads/lms/submissions/' . $safeFilename;
            }
        }

        // Check if existing submission
        $checkStmt = $db->prepare("SELECT id, submission_file, status, score FROM lms_submissions WHERE assignment_id = :asg_id AND student_id = :sid");
        $checkStmt->execute(['asg_id' => $assignmentId, 'sid' => $user['id']]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            // Guard: Once graded by teacher, student cannot edit or overwrite submitted work
            if ($existing['status'] === 'Graded' || $existing['score'] !== null) {
                Response::error('This assignment has already been evaluated and graded by the teacher. Submissions are finalized and cannot be modified.', 403);
            }

            // Delete old file if new one uploaded
            if ($filePath && !empty($existing['submission_file'])) {
                $oldP = __DIR__ . '/../' . $existing['submission_file'];
                if (file_exists($oldP)) @unlink($oldP);
            }

            $updateStmt = $db->prepare("
                UPDATE lms_submissions 
                SET submission_file = COALESCE(:fpath, submission_file),
                    submission_text = :stext,
                    quiz_answers = COALESCE(:qanswers, quiz_answers),
                    auto_graded_score = COALESCE(:ascore, auto_graded_score),
                    score = COALESCE(:score, score),
                    teacher_feedback = COALESCE(:feedback, teacher_feedback),
                    status = :status,
                    submitted_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([
                'fpath'    => $filePath,
                'stext'    => $submissionText,
                'qanswers' => $quizAnswersJson,
                'ascore'   => $autoGradedScore,
                'score'    => $score,
                'feedback' => $teacherFeedback,
                'status'   => $status,
                'id'       => $existing['id']
            ]);
            Response::success($isQuiz ? 'Quiz answers submitted and graded successfully!' : 'Submission updated successfully', [
                'is_quiz'        => $isQuiz,
                'score'          => $score,
                'max_score'      => $assignment['max_score'],
                'has_essay'      => $hasEssay,
                'eval_breakdown' => $evalBreakdown
            ]);
        } else {
            if (!$isQuiz && !$filePath && !$submissionText) {
                Response::error('Please upload a file or write a response before submitting.');
            }

            $insertStmt = $db->prepare("
                INSERT INTO lms_submissions (assignment_id, student_id, submission_file, submission_text, quiz_answers, auto_graded_score, score, teacher_feedback, status, submitted_at)
                VALUES (:asg_id, :sid, :fpath, :stext, :qanswers, :ascore, :score, :feedback, :status, NOW())
            ");
            $insertStmt->execute([
                'asg_id'   => $assignmentId,
                'sid'      => $user['id'],
                'fpath'    => $filePath,
                'stext'    => $submissionText,
                'qanswers' => $quizAnswersJson,
                'ascore'   => $autoGradedScore,
                'score'    => $score,
                'feedback' => $teacherFeedback,
                'status'   => $status
            ]);
            Response::success($isQuiz ? 'Quiz answers submitted and graded successfully!' : 'Assignment submitted successfully', [
                'is_quiz'        => $isQuiz,
                'score'          => $score,
                'max_score'      => $assignment['max_score'],
                'has_essay'      => $hasEssay,
                'eval_breakdown' => $evalBreakdown
            ]);
        }
    }

    /**
     * Retrieve all Submissions for an Assignment (Teacher Grading Desk)
     */
    public function getAssignmentSubmissions(): void {
        $user = Auth::requireRole(['teacher'], false);
        $assignmentId = (int)($_GET['assignment_id'] ?? 0);

        if (!$assignmentId) {
            Response::error('Assignment ID is required.');
        }

        $db = Database::getConnection();

        $asgStmt = $db->prepare("
            SELECT asg.*, sec.name as section_name, sub.name as subject_name
            FROM lms_assignments asg
            JOIN sections sec ON asg.section_id = sec.id
            JOIN subjects sub ON asg.subject_id = sub.id
            WHERE asg.id = :id
        ");
        $asgStmt->execute(['id' => $assignmentId]);
        $assignment = $asgStmt->fetch();

        if (!$assignment) {
            Response::error('Assignment not found.', 404);
        }

        // Fetch all officially enrolled students in the section + their submission (LEFT JOIN)
        $studStmt = $db->prepare("
            SELECT u.id as student_id, u.student_id as official_student_no,
                   p.first_name, p.last_name, p.middle_name, p.gender,
                   sub.id as submission_id, sub.submission_file, sub.submission_text,
                   sub.quiz_answers, sub.auto_graded_score,
                   sub.score, sub.teacher_feedback, sub.status as submission_status,
                   sub.submitted_at, sub.graded_at
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            LEFT JOIN user_profiles p ON u.id = p.user_id
            LEFT JOIN lms_submissions sub ON (sub.assignment_id = :asg_id AND sub.student_id = u.id)
            WHERE e.section_id = :sec_id AND e.status IN ('Officially Enrolled', 'Enrolled')
            ORDER BY p.last_name ASC, p.first_name ASC
        ");
        $studStmt->execute(['asg_id' => $assignmentId, 'sec_id' => $assignment['section_id']]);
        $students = $studStmt->fetchAll();

        foreach ($students as &$s) {
            if (!empty($s['quiz_answers'])) {
                $s['quiz_answers'] = json_decode($s['quiz_answers'], true) ?: [];
            } else {
                $s['quiz_answers'] = null;
            }
        }
        unset($s);

        if (!empty($assignment['quiz_questions'])) {
            $assignment['quiz_questions'] = json_decode($assignment['quiz_questions'], true) ?: [];
        } else {
            $assignment['quiz_questions'] = [];
        }

        Response::success('Submissions retrieved', [
            'assignment' => $assignment,
            'roster'     => $students
        ]);
    }

    /**
     * Grade a Student Submission
     */
    public function gradeSubmission(): void {
        $user = Auth::requireRole(['teacher'], false);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $submissionId = (int)($input['submission_id'] ?? 0);
        $score = isset($input['score']) ? (float)$input['score'] : null;
        $feedback = trim($input['teacher_feedback'] ?? '');
        $quizAnswers = $input['quiz_answers'] ?? null;
        $quizAnswersJson = is_array($quizAnswers) ? json_encode($quizAnswers) : (is_string($quizAnswers) ? $quizAnswers : null);

        if (!$submissionId) {
            Response::error('Submission ID is required.');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE lms_submissions 
            SET score = :score,
                teacher_feedback = :feedback,
                quiz_answers = COALESCE(:qanswers, quiz_answers),
                status = 'Graded',
                graded_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            'score'    => $score,
            'feedback' => $feedback,
            'qanswers' => $quizAnswersJson,
            'id'       => $submissionId
        ]);

        Response::success('Submission graded successfully and score released to student');
    }

    /**
     * Self-migrating database schema helper for online hosting (InfinityFree / MySQL)
     */
    private function ensureQuizSchema(PDO $db): void {
        try {
            $c1 = $db->query("SHOW COLUMNS FROM lms_assignments LIKE 'submission_format'")->fetch();
            if (!$c1) {
                $db->exec("ALTER TABLE lms_assignments ADD COLUMN submission_format ENUM('standard', 'quiz') NOT NULL DEFAULT 'standard' AFTER task_type");
            }
            $c2 = $db->query("SHOW COLUMNS FROM lms_assignments LIKE 'quiz_questions'")->fetch();
            if (!$c2) {
                $db->exec("ALTER TABLE lms_assignments ADD COLUMN quiz_questions LONGTEXT NULL AFTER submission_format");
            }
            $c3 = $db->query("SHOW COLUMNS FROM lms_submissions LIKE 'quiz_answers'")->fetch();
            if (!$c3) {
                $db->exec("ALTER TABLE lms_submissions ADD COLUMN quiz_answers LONGTEXT NULL AFTER submission_text");
            }
            $c4 = $db->query("SHOW COLUMNS FROM lms_submissions LIKE 'auto_graded_score'")->fetch();
            if (!$c4) {
                $db->exec("ALTER TABLE lms_submissions ADD COLUMN auto_graded_score DECIMAL(5,2) NULL AFTER quiz_answers");
            }
            $c5 = $db->query("SHOW COLUMNS FROM lms_assignments LIKE 'status'")->fetch();
            if (!$c5) {
                $db->exec("ALTER TABLE lms_assignments ADD COLUMN status ENUM('draft', 'published') NOT NULL DEFAULT 'published' AFTER task_type");
            }
        } catch (\Exception $e) {
            error_log("[LMS-Quiz] Schema ensure error: " . $e->getMessage());
        }
    }
}
