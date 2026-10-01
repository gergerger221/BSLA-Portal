<?php
// backend/controllers/AdminController.php
namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;
use App\Helpers\Auth;
use PDO;

class AdminController {
    /**
     * Get System Dashboard Stats.
     */
    public function getDashboardStats(): void {
        Auth::requireRole(['admin', 'coordinator']);
        $db = Database::getConnection();

        $applicantCount = $db->query("SELECT COUNT(*) FROM admission_applications")->fetchColumn();
        $pendingApps = $db->query("SELECT COUNT(*) FROM admission_applications WHERE status IN ('Pending', 'Under Review')")->fetchColumn();
        $enrolledJHS = $db->query("SELECT COUNT(*) FROM enrollments e JOIN grade_levels gl ON e.grade_level_id = gl.id WHERE e.status = 'Officially Enrolled' AND gl.category = 'JHS'")->fetchColumn();
        $enrolledSHS = $db->query("SELECT COUNT(*) FROM enrollments e JOIN grade_levels gl ON e.grade_level_id = gl.id WHERE e.status = 'Officially Enrolled' AND gl.category = 'SHS'")->fetchColumn();
        $totalCollected = $db->query("SELECT COALESCE(SUM(amount_paid), 0) FROM payments")->fetchColumn();
        $staffCount = $db->query("SELECT COUNT(*) FROM users WHERE role_id IN (1, 2, 3, 4, 5, 6)")->fetchColumn();

        $activeSy = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();

        // Recent audit logs
        $logs = $db->query("
            SELECT al.*, u.username
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.id DESC
            LIMIT 50
        ")->fetchAll();

        Response::success('Dashboard statistics loaded', [
            'total_applicants' => (int)$applicantCount,
            'pending_review'   => (int)$pendingApps,
            'enrolled_jhs'     => (int)$enrolledJHS,
            'enrolled_shs'     => (int)$enrolledSHS,
            'total_revenue'    => (float)$totalCollected,
            'total_staff'      => (int)$staffCount,
            'active_school_year' => $activeSy,
            'recent_logs'      => $logs
        ]);
    }

    /**
     * Get all School Years & Toggle School Year Lock.
     */
    public function getSchoolYears(): void {
        Auth::requireRole(['admin', 'coordinator']);
        $db = Database::getConnection();
        $schoolYears = $db->query("SELECT * FROM school_years ORDER BY id DESC")->fetchAll();
        Response::success('School years loaded', $schoolYears);
    }

    public function toggleSchoolYearLock(): void {
        $user = Auth::requireRole(['admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['school_year_id'] ?? $input['id'] ?? $_GET['school_year_id'] ?? $_GET['id'] ?? 0);

        if (!$id) {
            Response::error('School year ID is required.');
        }

        $db = Database::getConnection();
        $sy = $db->prepare("SELECT is_locked, is_active, name, lifecycle_stage FROM school_years WHERE id = :id");
        $sy->execute(['id' => $id]);
        $row = $sy->fetch();

        if (!$row) {
            Response::error('School year not found.');
        }

        $newLock = $row['is_locked'] ? 0 : 1;
        
        // Calculate dynamic lifecycle stage
        $newStage = 'Planning';
        if (!empty($row['is_active'])) {
            $newStage = 'Active';
        } else {
            $newStage = $newLock ? 'Planning' : 'Early Admission';
        }

        $stmt = $db->prepare("UPDATE school_years SET is_locked = :lock, lifecycle_stage = :stage WHERE id = :id");
        $stmt->execute([
            'lock'  => $newLock,
            'stage' => $newStage,
            'id'    => $id
        ]);

        $actionText = $newLock ? "LOCKED (Admission & Intake closed)" : ($row['is_active'] ? "UNLOCKED (Active Admission open)" : "UNLOCKED (Early Registration & Intake open)");
        Auth::logAudit('SCHOOL_YEAR_LOCK', "School Year {$row['name']} intake was {$actionText}", $user['id']);

        Response::success("School Year {$row['name']} intake is now {$actionText}", [
            'is_locked'       => $newLock,
            'lifecycle_stage' => $newStage
        ]);
    }

    public function toggleSchoolYearSemester(): void {
        $user = Auth::requireRole(['admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['school_year_id'] ?? $input['id'] ?? $_GET['school_year_id'] ?? $_GET['id'] ?? 0);

        if (!$id) {
            Response::error('School year ID is required.');
        }

        $db = Database::getConnection();
        $sy = $db->prepare("SELECT active_semester, name FROM school_years WHERE id = :id");
        $sy->execute(['id' => $id]);
        $row = $sy->fetch();

        if (!$row) {
            Response::error('School year not found.');
        }

        $newSemester = ($row['active_semester'] === '1st Semester') ? '2nd Semester' : '1st Semester';

        $stmt = $db->prepare("UPDATE school_years SET active_semester = :sem WHERE id = :id");
        $stmt->execute(['sem' => $newSemester, 'id' => $id]);

        Auth::logAudit('SEMESTER_SWITCHED', "Switched {$row['name']} active term to {$newSemester}", $user['id']);

        Response::success("Switched {$row['name']} to {$newSemester}", [
            'active_semester' => $newSemester
        ]);
    }

    public function toggleCurriculumLock(): void {
        $user = Auth::requireRole(['admin', 'coordinator']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['school_year_id'] ?? 0);

        if (!$id) {
            Response::error('School year ID is required.');
        }

        $db = Database::getConnection();
        $sy = $db->prepare("SELECT curriculum_locked, is_active, name FROM school_years WHERE id = :id");
        $sy->execute(['id' => $id]);
        $row = $sy->fetch();

        if (!$row) {
            Response::error('School year not found.');
        }

        $newLock = !empty($row['curriculum_locked']) ? 0 : 1;
        $declAt = $newLock ? date('Y-m-d H:i:s') : null;

        $stmt = $db->prepare("
            UPDATE school_years 
            SET curriculum_locked = :lock, 
                curriculum_declared_at = :decl_at, 
                curriculum_declared_by = :uid 
            WHERE id = :id
        ");
        $stmt->execute([
            'lock'    => $newLock,
            'decl_at' => $declAt,
            'uid'     => $user['id'],
            'id'      => $id
        ]);

        $actionText = $newLock ? "OFFICIALLY DECLARED & LOCKED" : "UNLOCKED (DRAFT SETUP MODE)";
        Auth::logAudit('CURRICULUM_LOCK_TOGGLED', "School Year {$row['name']} curriculum was {$actionText} by {$user['username']}", $user['id']);

        Response::success("School year curriculum is now {$actionText}", [
            'curriculum_locked'      => $newLock,
            'curriculum_declared_at' => $declAt
        ]);
    }

    public function saveSchoolYear(): void {
        $user = Auth::requireRole(['admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $code = trim($input['code'] ?? '');
        $name = trim($input['name'] ?? '');
        $startDate = trim($input['start_date'] ?? '');
        $endDate = trim($input['end_date'] ?? '');
        $activeSemester = trim($input['active_semester'] ?? '1st Semester');
        $isActive = !empty($input['is_active']) ? 1 : 0;
        $isLocked = isset($input['is_locked']) ? (int)$input['is_locked'] : 1;
        $curriculumLocked = isset($input['curriculum_locked']) ? (int)$input['curriculum_locked'] : 0;
        
        $lifecycleStage = $isActive ? 'Active' : ($isLocked ? 'Planning' : 'Early Admission');

        if (!$code) {
            Response::error('School Year Code is required (e.g. 2028-2029).');
        }

        if (!$name) {
            $name = "School Year {$code}";
        }

        // Auto-calculate standard DepEd calendar dates if omitted
        if (!$startDate || !$endDate) {
            if (preg_match('/^(\d{4})-(\d{4})$/', $code, $matches)) {
                $startDate = $startDate ?: "{$matches[1]}-08-01";
                $endDate = $endDate ?: "{$matches[2]}-05-31";
            } else {
                $currentYear = (int)date('Y');
                $startDate = $startDate ?: "{$currentYear}-08-01";
                $nextYear = $currentYear + 1;
                $endDate = $endDate ?: "{$nextYear}-05-31";
            }
        }

        $db = Database::getConnection();

        // Check duplicate code
        if ($id) {
            $dup = $db->prepare("SELECT id FROM school_years WHERE code = :code AND id != :id");
            $dup->execute(['code' => $code, 'id' => $id]);
        } else {
            $dup = $db->prepare("SELECT id FROM school_years WHERE code = :code");
            $dup->execute(['code' => $code]);
        }
        if ($dup->fetch()) {
            Response::error("School Year code '{$code}' already exists.");
        }

        if ($isActive) {
            $db->exec("UPDATE school_years SET is_active = 0, lifecycle_stage = 'Archived'");
        }

        if ($id) {
            $stmt = $db->prepare("
                UPDATE school_years 
                SET code = :code, name = :name, start_date = :start_date, end_date = :end_date, 
                    active_semester = :sem, is_active = :is_active, lifecycle_stage = :stage
                WHERE id = :id
            ");
            $stmt->execute([
                'code'       => $code,
                'name'       => $name,
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'sem'        => $activeSemester,
                'is_active'  => $isActive,
                'stage'      => $lifecycleStage,
                'id'         => $id
            ]);
            Auth::logAudit('SCHOOL_YEAR_UPDATED', "School Year {$name} was updated", $user['id']);
            Response::success("School Year '{$name}' updated successfully.");
        } else {
            $stmt = $db->prepare("
                INSERT INTO school_years (code, name, start_date, end_date, active_semester, lifecycle_stage, is_active, is_locked, curriculum_locked, created_at)
                VALUES (:code, :name, :start_date, :end_date, :sem, :stage, :is_active, :is_locked, :curriculum_locked, NOW())
            ");
            $stmt->execute([
                'code'              => $code,
                'name'              => $name,
                'start_date'        => $startDate,
                'end_date'          => $endDate,
                'sem'               => $activeSemester,
                'stage'             => $lifecycleStage,
                'is_active'         => $isActive,
                'is_locked'         => $isLocked,
                'curriculum_locked' => $curriculumLocked
            ]);
            Auth::logAudit('SCHOOL_YEAR_CREATED', "New School Year {$name} ({$code}) created", $user['id']);
            Response::success("School Year '{$name}' created successfully.");
        }
    }

    /**
     * Delete Draft / Standby School Year (Protected Safe-Delete)
     */
    public function deleteSchoolYear(): void {
        $user = Auth::requireRole(['admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['school_year_id'] ?? $input['id'] ?? 0);

        if (!$id) {
            Response::error('School Year ID is required.');
        }

        $db = Database::getConnection();
        $sy = $db->prepare("SELECT * FROM school_years WHERE id = :id");
        $sy->execute(['id' => $id]);
        $row = $sy->fetch();

        if (!$row) {
            Response::error('School Year not found.');
        }

        if (!empty($row['is_active'])) {
            Response::error("Cannot delete the Active School Year. Please activate another school year before deleting.");
        }

        // Integrity Checks: Check if any dependent records exist
        $enrollmentCount = (int)$db->query("SELECT COUNT(*) FROM enrollments WHERE school_year_id = {$id}")->fetchColumn();
        $recordsCount = (int)$db->query("SELECT COUNT(*) FROM student_records WHERE school_year_id = {$id}")->fetchColumn();
        $gradesCount = (int)$db->query("SELECT COUNT(*) FROM student_grades WHERE school_year_id = {$id}")->fetchColumn();
        $appsCount = (int)$db->query("SELECT COUNT(*) FROM admission_applications WHERE school_year_id = {$id}")->fetchColumn();
        $paymentsCount = (int)$db->query("SELECT COUNT(*) FROM payments p JOIN enrollments e ON p.enrollment_id = e.id WHERE e.school_year_id = {$id}")->fetchColumn();

        $totalDependencies = $enrollmentCount + $recordsCount + $gradesCount + $appsCount + $paymentsCount;

        if ($totalDependencies > 0) {
            Response::error("Cannot delete School Year '{$row['name']}' because it contains {$enrollmentCount} enrollees, {$recordsCount} student records, and {$gradesCount} grades. Historical academic cycles must remain archived to protect DepEd permanent records (SF10).");
        }

        $db->beginTransaction();
        try {
            // Delete any draft subjects assigned to this school year
            $stmtSub = $db->prepare("DELETE FROM subjects WHERE school_year_id = :id");
            $stmtSub->execute(['id' => $id]);

            // Delete school year
            $stmtSy = $db->prepare("DELETE FROM school_years WHERE id = :id");
            $stmtSy->execute(['id' => $id]);

            $db->commit();

            Auth::logAudit('SCHOOL_YEAR_DELETED', "Deleted draft school year {$row['name']} ({$row['code']})", $user['id']);

            Response::success("School Year '{$row['name']}' was deleted successfully.");
        } catch (\Exception $e) {
            $db->rollBack();
            Response::error("Failed to delete school year: " . $e->getMessage());
        }
    }

    /**
     * Pre-Flight Rollover Diagnostics: Verify readiness before activating a new School Year.
     */
    public function getRolloverPreflightCheck(): void {
        Auth::requireRole(['admin']);
        $db = Database::getConnection();
        $targetSyId = (int)($_GET['target_school_year_id'] ?? 0);

        if (!$targetSyId) {
            Response::error('Target School Year ID is required.');
        }

        $targetSy = $db->query("SELECT * FROM school_years WHERE id = {$targetSyId}")->fetch();
        if (!$targetSy) {
            Response::error('Target School Year not found.');
        }

        $currentSy = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
        $currentSyId = $currentSy ? (int)$currentSy['id'] : 0;

        // 1. Target Curriculum Readiness
        $targetSubjectsCount = (int)$db->query("SELECT COUNT(*) FROM subjects WHERE school_year_id = {$targetSyId}")->fetchColumn();
        $targetApprovedCount = (int)$db->query("SELECT COUNT(*) FROM subjects WHERE school_year_id = {$targetSyId} AND approval_status = 'Approved'")->fetchColumn();
        $targetSectionsCount = (int)$db->query("SELECT COUNT(*) FROM sections WHERE is_active = 1")->fetchColumn();

        // 2. Current SY Students & Enrollees
        $currentEnrolledCount = 0;
        if ($currentSyId) {
            $currentEnrolledCount = (int)$db->query("SELECT COUNT(*) FROM enrollments WHERE school_year_id = {$currentSyId} AND status = 'Officially Enrolled'")->fetchColumn();
        }

        // 3. Promotion Eligibility Breakdown
        $eligibleForPromotion = 0;
        $eligibleForGraduation = 0;
        if ($currentSyId) {
            $eligibleForPromotion = (int)$db->query("SELECT COUNT(*) FROM enrollments e JOIN grade_levels gl ON e.grade_level_id = gl.id WHERE e.school_year_id = {$currentSyId} AND e.status = 'Officially Enrolled' AND gl.sequence_order < 6")->fetchColumn();
            $eligibleForGraduation = (int)$db->query("SELECT COUNT(*) FROM enrollments e JOIN grade_levels gl ON e.grade_level_id = gl.id WHERE e.school_year_id = {$currentSyId} AND e.status = 'Officially Enrolled' AND gl.sequence_order = 6")->fetchColumn();
        } else {
            $eligibleForPromotion = (int)$db->query("SELECT COUNT(*) FROM student_records sr JOIN grade_levels gl ON sr.grade_level_id = gl.id WHERE gl.sequence_order < 6")->fetchColumn();
            $eligibleForGraduation = (int)$db->query("SELECT COUNT(*) FROM student_records sr JOIN grade_levels gl ON sr.grade_level_id = gl.id WHERE gl.sequence_order = 6")->fetchColumn();
        }

        Response::success('Pre-flight diagnostics retrieved', [
            'target_school_year'     => $targetSy,
            'current_school_year'    => $currentSy,
            'target_subjects_count'  => $targetSubjectsCount,
            'target_approved_count'  => $targetApprovedCount,
            'target_sections_count'  => $targetSectionsCount,
            'current_enrolled_count' => $currentEnrolledCount,
            'eligible_promotion'     => $eligibleForPromotion,
            'eligible_graduation'    => $eligibleForGraduation,
            'curriculum_ready'       => $targetSubjectsCount > 0 && $targetApprovedCount > 0
        ]);
    }

    /**
     * Execute Safe Assisted School Year Rollover with optional student mass-progression.
     */
    public function executeSchoolYearRollover(): void {
        $user = Auth::requireRole(['admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $targetSyId = (int)($input['target_school_year_id'] ?? 0);
        $autoPromote = !empty($input['auto_promote_students']);
        $archivePrev = !empty($input['archive_previous_sy']);

        if (!$targetSyId) {
            Response::error('Target School Year ID is required.');
        }

        $db = Database::getConnection();
        $targetSy = $db->query("SELECT * FROM school_years WHERE id = {$targetSyId}")->fetch();
        if (!$targetSy) {
            Response::error('Target School Year not found.');
        }

        $currentSy = $db->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
        $currentSyId = $currentSy ? (int)$currentSy['id'] : 0;
        $currentSyName = $currentSy ? $currentSy['name'] : 'Previous School Year';

        $db->beginTransaction();
        try {
            // 1. Archive current active school year
            if ($archivePrev) {
                $db->exec("UPDATE school_years SET is_active = 0, lifecycle_stage = 'Archived', is_locked = 1 WHERE is_active = 1");
            } else {
                $db->exec("UPDATE school_years SET is_active = 0 WHERE is_active = 1");
            }

            // 2. Set target school year as Active
            $stmt = $db->prepare("UPDATE school_years SET is_active = 1, is_locked = 0, lifecycle_stage = 'Active' WHERE id = :id");
            $stmt->execute(['id' => $targetSyId]);

            $promotedCount = 0;
            $graduatedCount = 0;

            // 3. Execute Student Mass-Promotion if requested
            if ($autoPromote && $currentSyId) {
                // Fetch all enrolled students in the current active school year
                $enrolledStudents = $db->query("SELECT e.*, gl.sequence_order FROM enrollments e JOIN grade_levels gl ON e.grade_level_id = gl.id WHERE e.school_year_id = {$currentSyId} AND e.status = 'Officially Enrolled'")->fetchAll();

                $updateSrGrad = $db->prepare("UPDATE student_records SET promotion_status = 'Graduated' WHERE student_id = :student_id AND school_year_id = :sy_id");
                $updateSrPromote = $db->prepare("UPDATE student_records SET promotion_status = 'Promoted' WHERE student_id = :student_id AND school_year_id = :sy_id");

                foreach ($enrolledStudents as $st) {
                    if ((int)$st['sequence_order'] === 6) { // Grade 12 -> Graduated
                        $updateSrGrad->execute(['student_id' => $st['student_id'], 'sy_id' => $currentSyId]);
                        $graduatedCount++;
                    } else {
                        $updateSrPromote->execute(['student_id' => $st['student_id'], 'sy_id' => $currentSyId]);
                        $promotedCount++;
                    }
                }
            }

            $db->commit();

            Auth::logAudit(
                'SCHOOL_YEAR_ROLLOVER_EXECUTED',
                "Transitioned active academic cycle from {$currentSyName} to {$targetSy['name']}. (Promoted: {$promotedCount} students, Graduated: {$graduatedCount} Grade 12 students) by {$user['username']}",
                $user['id']
            );

            Response::success("School Year '{$targetSy['name']}' is now the Active Academic Cycle." . ($autoPromote ? " ({$promotedCount} students promoted, {$graduatedCount} students graduated)" : ""), [
                'active_school_year' => $targetSy['name'],
                'promoted_count'    => $promotedCount,
                'graduated_count'   => $graduatedCount
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            Response::error('School Year rollover failed: ' . $e->getMessage());
        }
    }

    public function setActiveSchoolYear(): void {
        $this->executeSchoolYearRollover();
    }

    /**
     * Get list of users / staff accounts.
     */
    public function getUsers(): void {
        Auth::requireRole(['admin']);
        $db = Database::getConnection();

        $roleSlug = $_GET['role'] ?? '';
        $sql = "
            SELECT u.id, u.role_id, u.username, u.email, u.student_id, u.status, u.created_at,
                   r.name as role_name, r.slug as role_slug,
                   p.first_name, p.middle_name, p.last_name, p.contact_number
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN user_profiles p ON u.id = p.user_id
            WHERE 1=1
        ";
        $params = [];
        if ($roleSlug) {
            $sql .= " AND r.slug = :role";
            $params['role'] = $roleSlug;
        }
        $sql .= " ORDER BY u.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        $roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

        Response::success('Users loaded', [
            'users' => $users,
            'roles' => $roles
        ]);
    }

    /**
     * Create or edit a Staff User account.
     */
    public function saveUser(): void {
        $admin = Auth::requireRole(['admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $roleId = (int)($input['role_id'] ?? 2);
        $username = trim($input['username'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = trim($input['password'] ?? '');
        $firstName = trim($input['first_name'] ?? '');
        $lastName = trim($input['last_name'] ?? '');
        $contactNumber = trim($input['contact_number'] ?? '');
        $status = $input['status'] ?? 'Active';

        if (!$username || !$email || !$firstName || !$lastName) {
            Response::error('Username, email, first name, and last name are required.');
        }

        $db = Database::getConnection();

        if ($id) {
            // Update user
            $sql = "UPDATE users SET role_id = :role_id, username = :username, email = :email, status = :status";
            $params = [
                'role_id'  => $roleId,
                'username' => $username,
                'email'    => $email,
                'status'   => $status,
                'id'       => $id
            ];
            if ($password) {
                $sql .= ", password = :password";
                $params['password'] = password_hash($password, PASSWORD_BCRYPT);
            }
            $sql .= " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            // Update profile
            $upProf = $db->prepare("
                UPDATE user_profiles SET
                    first_name = :first_name, last_name = :last_name, contact_number = :contact
                WHERE user_id = :id
            ");
            $upProf->execute([
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'contact'    => $contactNumber,
                'id'         => $id
            ]);

            Auth::logAudit('USER_UPDATED', "Updated user {$username}", $admin['id']);
            Response::success('User account updated successfully');
        } else {
            // Create user
            if (!$password) {
                Response::error('Password is required for new accounts.');
            }
            $db->beginTransaction();
            try {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $ins = $db->prepare("
                    INSERT INTO users (role_id, username, email, password, status)
                    VALUES (:role_id, :username, :email, :password, :status)
                ");
                $ins->execute([
                    'role_id'  => $roleId,
                    'username' => $username,
                    'email'    => $email,
                    'password' => $hashed,
                    'status'   => $status
                ]);
                $newId = (int)$db->lastInsertId();

                $prof = $db->prepare("
                    INSERT INTO user_profiles (user_id, first_name, last_name, contact_number)
                    VALUES (:user_id, :first_name, :last_name, :contact)
                ");
                $prof->execute([
                    'user_id'    => $newId,
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'contact'    => $contactNumber
                ]);

                $db->commit();
                Auth::logAudit('USER_CREATED', "Created user {$username}", $admin['id']);
                Response::success('User account created successfully', ['id' => $newId], 201);
            } catch (\Exception $e) {
                $db->rollBack();
                Response::error('Failed to create user: ' . $e->getMessage());
            }
        }
    }
}
