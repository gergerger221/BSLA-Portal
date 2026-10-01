<?php
// backend/controllers/AuthController.php
namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;
use App\Helpers\Auth;
use App\Helpers\Mailer;
use PDO;

class AuthController {
    /**
     * User Login (Supports staff, student, and temp applicant)
     */
    public function login(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $identity = trim($input['username'] ?? $input['email'] ?? '');
        $password = trim($input['password'] ?? '');

        if (!$identity || !$password) {
            Response::error('Please provide your username/email and password.');
        }

        // Rate limiting — block after 5 failed attempts per IP within 30 seconds
        if (!Auth::checkRateLimit($identity)) {
            Response::error('Too many failed login attempts. Please wait 30 seconds before trying again.', 429);
        }

        $portalType = trim($input['portal_type'] ?? '');
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.*, r.name as role_name, r.slug as role_slug,
                   p.first_name, p.middle_name, p.last_name, p.contact_number
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN user_profiles p ON u.id = p.user_id
            LEFT JOIN admission_applications aa ON (u.id = aa.user_id OR (u.student_id IS NOT NULL AND u.student_id = aa.student_no))
            WHERE u.username = :ident1 
               OR u.email = :ident2 
               OR u.student_id = :ident3
               OR aa.application_no = :ident4
               OR aa.lrn = :ident5
               OR u.id IN (SELECT student_id FROM enrollments WHERE lrn = :ident6)
               OR (r.slug = :ident7 AND r.slug IN ('coordinator', 'registrar', 'treasury', 'records', 'teacher', 'admin'))
            ORDER BY (
                CASE 
                    WHEN :p_type = 'student' AND r.slug = 'student' THEN 1
                    WHEN :p_type2 = 'applicant' AND r.slug = 'applicant' THEN 1
                    WHEN u.username = :ident8 THEN 1
                    ELSE 2
                END
            ) ASC, u.id ASC
            LIMIT 1
        ");
        $stmt->execute([
            'ident1'  => $identity,
            'ident2'  => $identity,
            'ident3'  => $identity,
            'ident4'  => $identity,
            'ident5'  => $identity,
            'ident6'  => $identity,
            'ident7'  => strtolower($identity),
            'ident8'  => $identity,
            'p_type'  => $portalType,
            'p_type2' => $portalType
        ]);
        $user = $stmt->fetch();

        $isPasswordValid = false;
        if ($user) {
            $isPasswordValid = password_verify($password, $user['password']);

            // Student Portal login resilience: support default password (student's last name) or linked applicant password
            if (!$isPasswordValid && $user['role_slug'] === 'student') {
                $trimmedInput = trim($password);
                $lastName = trim($user['last_name'] ?? '');

                // 1. Check if input matches student's last name case-insensitively (e.g. TING, Ting, ting)
                if (!empty($lastName) && strcasecmp($trimmedInput, $lastName) === 0) {
                    $isPasswordValid = true;
                    // Auto-sync password hash to standard bcrypt for future logins
                    $newHash = password_hash($trimmedInput, PASSWORD_BCRYPT);
                    $db->prepare("UPDATE users SET password = :pw WHERE id = :id")->execute(['pw' => $newHash, 'id' => $user['id']]);
                }

                // 2. Check if input matches the linked applicant account password
                if (!$isPasswordValid && !empty($user['student_id'])) {
                    $appUserStmt = $db->prepare("
                        SELECT u.password FROM users u
                        JOIN admission_applications aa ON u.id = aa.user_id
                        WHERE aa.student_no = :sno LIMIT 1
                    ");
                    $appUserStmt->execute(['sno' => $user['student_id']]);
                    $origApplicant = $appUserStmt->fetch();
                    if ($origApplicant && password_verify($password, $origApplicant['password'])) {
                        $isPasswordValid = true;
                        $newHash = password_hash($password, PASSWORD_BCRYPT);
                        $db->prepare("UPDATE users SET password = :pw WHERE id = :id")->execute(['pw' => $newHash, 'id' => $user['id']]);
                    }
                }
            }
        }

        if (!$user || !$isPasswordValid) {
            Auth::recordLoginAttempt($identity, false);
            Response::error('Invalid username or password.', 401);
        }

        // Record successful login attempt
        Auth::recordLoginAttempt($identity, true);

        if ($user['status'] !== 'Active') {
            Response::error('Your account is ' . strtolower($user['status']) . '. Please contact the registrar.', 403);
        }

        // STRICT SERVER-SIDE PORTAL SEPARATION ENFORCEMENT
        $portalType = trim($input['portal_type'] ?? '');
        $staffRoles = ['admin', 'coordinator', 'registrar', 'treasury', 'records', 'teacher', 'scheduler'];

        if ($portalType === 'student') {
            if ($user['role_slug'] !== 'student') {
                if (in_array($user['role_slug'], $staffRoles)) {
                    Response::error('This login is for enrolled students only. Please use the Staff Login.', 403);
                } elseif ($user['role_slug'] === 'applicant') {
                    Response::error('This login is for officially enrolled students. If you are an applicant, please check your application status or continue your admission procedure.', 403);
                } else {
                    Response::error('This login is for enrolled students only. Please use the Staff Login.', 403);
                }
            }
        } elseif ($portalType === 'staff') {
            if (!in_array($user['role_slug'], $staffRoles)) {
                if ($user['role_slug'] === 'student') {
                    Response::error('This portal is for authorized faculty and staff personnel only. Students must log in via the Student Portal.', 403);
                } elseif ($user['role_slug'] === 'applicant') {
                    Response::error('This portal is for authorized faculty and staff personnel only. Applicants must log in via the Admission Portal.', 403);
                } else {
                    Response::error('This portal is for authorized faculty and staff personnel only.', 403);
                }
            }
        } elseif ($portalType === 'applicant') {
            if ($user['role_slug'] !== 'applicant') {
                if ($user['role_slug'] === 'student') {
                    Response::error('Your enrollment has already been approved! Please sign in using the Student Portal.', 403);
                } elseif (in_array($user['role_slug'], $staffRoles)) {
                    Response::error('This portal is for student applicants. Please use the Staff Login.', 403);
                }
            }
        }

        $token = Auth::generateToken((int)$user['id']);
        Auth::logAudit('LOGIN', "User {$user['username']} logged in", (int)$user['id']);

        unset($user['password'], $user['remember_token']);
        $user['token'] = $token;

        Response::success('Login successful', $user);
    }

    /**
     * Register a Temporary Admission Account for Applicants
     * Home Page > Admission > (Temp Admission Account)
     */
    public function registerApplicant(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $firstName = trim($input['first_name'] ?? '');
        $lastName = trim($input['last_name'] ?? '');
        $middleName = trim($input['middle_name'] ?? '');
        $email = trim($input['email'] ?? '');
        $rawContact = trim($input['contact_number'] ?? '');
        $contactNumber = preg_replace('/\D/', '', $rawContact);
        $password = trim($input['password'] ?? '');
        $passwordConfirmation = trim($input['password_confirmation'] ?? '');

        if (!$firstName || !$lastName || !$email || !$password) {
            Response::error('First name, last name, email, and password are required.');
        }

        if (strlen($password) < 6) {
            Response::error('Password must be at least 6 characters long.');
        }

        if (!empty($passwordConfirmation) && $password !== $passwordConfirmation) {
            Response::error('Passwords do not match. Please re-enter your password.');
        }

        if (!preg_match('/^09\d{9}$/', $contactNumber)) {
            Response::error('Must be an 11-digit Philippine mobile number starting with 09.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email address format.');
        }

        $db = Database::getConnection();

        // Check if optional LRN passed at registration is unique
        $rawLrn = trim($input['lrn'] ?? '');
        $cleanLrn = preg_replace('/\D/', '', $rawLrn);
        if (!empty($cleanLrn)) {
            if (strlen($cleanLrn) !== 12) {
                Response::error('DepEd Learner Reference Number (LRN) must be exactly 12 numeric digits.');
            }
            $chkLrn = $db->prepare("
                SELECT id FROM admission_applications WHERE lrn = :lrn1
                UNION
                SELECT id FROM enrollments WHERE lrn = :lrn2
                LIMIT 1
            ");
            $chkLrn->execute(['lrn1' => $cleanLrn, 'lrn2' => $cleanLrn]);
            if ($chkLrn->fetch()) {
                Response::error('This LRN is already registered in the system. Please verify your LRN.');
            }
        }

        // Check if email already exists
        $check = $db->prepare("SELECT id FROM users WHERE email = :email");
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            Response::error('An account with this email address already exists. Please login instead.');
        }

        // Generate a username for the applicant
        $baseUser = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName . '.' . $lastName));
        $username = $baseUser . rand(100, 999);

        // Get applicant role_id
        $roleStmt = $db->query("SELECT id FROM roles WHERE slug = 'applicant' LIMIT 1");
        $role = $roleStmt->fetch();
        $roleId = $role ? (int)$role['id'] : 8;

        $db->beginTransaction();
        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $userStmt = $db->prepare("
                INSERT INTO users (role_id, username, email, password, status)
                VALUES (:role_id, :username, :email, :password, 'Active')
            ");
            $userStmt->execute([
                'role_id'  => $roleId,
                'username' => $username,
                'email'    => $email,
                'password' => $hashedPassword
            ]);
            $userId = (int)$db->lastInsertId();

            $profStmt = $db->prepare("
                INSERT INTO user_profiles (user_id, first_name, middle_name, last_name, contact_number)
                VALUES (:user_id, :first_name, :middle_name, :last_name, :contact_number)
            ");
            $profStmt->execute([
                'user_id'        => $userId,
                'first_name'     => $firstName,
                'middle_name'    => $middleName,
                'last_name'      => $lastName,
                'contact_number' => $contactNumber
            ]);

            // Generate Application Reference Number (e.g. ADM-2026-0001)
            $appCountStmt = $db->query("SELECT COUNT(*) FROM admission_applications");
            $nextAppNum = (int)$appCountStmt->fetchColumn() + 1;
            $appNo = 'ADM-' . date('Y') . '-' . str_pad((string)$nextAppNum, 4, '0', STR_PAD_LEFT);

            // Fetch active School Year
            $syStmt = $db->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1");
            $activeSy = $syStmt->fetch();
            $syId = $activeSy ? (int)$activeSy['id'] : 1;

            // Create initial pending application record
            $appStmt = $db->prepare("
                INSERT INTO admission_applications (
                    application_no, user_id, first_name, middle_name, last_name,
                    gender, birthdate, contact_number, email, address_barangay, address_city,
                    address_province, guardian_name, guardian_relationship, guardian_contact,
                    school_year_id, grade_level_id, status
                ) VALUES (
                    :app_no, :user_id, :first_name, :middle_name, :last_name,
                    'Male', '2010-01-01', :contact_number, :email, 'Barangay 1', 'Manila',
                    'Metro Manila', 'Guardian', 'Parent', :guardian_contact,
                    :sy_id, NULL, 'Pending'
                )
            ");
            $appStmt->execute([
                'app_no'           => $appNo,
                'user_id'          => $userId,
                'first_name'       => $firstName,
                'middle_name'      => $middleName,
                'last_name'        => $lastName,
                'contact_number'   => $contactNumber,
                'email'            => $email,
                'guardian_contact' => $contactNumber,
                'sy_id'            => $syId
            ]);

            $token = Auth::generateToken($userId);
            $db->commit();

            Auth::logAudit('APPLICANT_REGISTER', "New applicant registered: {$appNo} ({$email})", $userId);

            // Fail-safe asynchronous/isolated SMTP email notification
            Mailer::sendApplicantRegistration([
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'email'          => $email,
                'username'       => $username,
                'application_no' => $appNo
            ]);

            Response::success('Admission account created successfully!', [
                'user_id'        => $userId,
                'username'       => $username,
                'email'          => $email,
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'role_slug'      => 'applicant',
                'role_name'      => 'Applicant',
                'application_no' => $appNo,
                'token'          => $token
            ], 201);
        } catch (\Exception $e) {
            $db->rollBack();
            Response::error('Failed to create account: ' . $e->getMessage());
        }
    }

    /**
     * Get Current Authenticated User Session
     */
    public function me(): void {
        $user = Auth::requireAuth();
        $db = Database::getConnection();

        // If applicant, attach their application details
        if ($user['role_slug'] === 'applicant') {
            $appStmt = $db->prepare("SELECT * FROM admission_applications WHERE user_id = :user_id LIMIT 1");
            $appStmt->execute(['user_id' => $user['id']]);
            $user['application'] = $appStmt->fetch() ?: null;
        }

        // If student, attach their enrollment details
        if ($user['role_slug'] === 'student') {
            $enrStmt = $db->prepare("
                SELECT e.*, g.name as grade_name, s.name as strand_name, sec.name as section_name
                FROM enrollments e
                JOIN grade_levels g ON e.grade_level_id = g.id
                LEFT JOIN strands s ON e.strand_id = s.id
                JOIN sections sec ON e.section_id = sec.id
                WHERE e.student_id = :user_id
                ORDER BY e.id DESC LIMIT 1
            ");
            $enrStmt->execute(['user_id' => $user['id']]);
            $user['enrollment'] = $enrStmt->fetch() ?: null;
        }

        Response::success('User authenticated', $user);
    }

    /**
     * Logout
     */
    public function logout(): void {
        $user = Auth::user();
        if ($user) {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE users SET remember_token = NULL WHERE id = :id");
            $stmt->execute(['id' => $user['id']]);
            Auth::logAudit('LOGOUT', "User logged out", (int)$user['id']);
        }
        Response::success('Logged out successfully');
    }

    /**
     * Get Current SMTP Configuration & Status for Testing Simulator
     */
    public function getSmtpConfig(): void {
        // VULN-02 fix: Require admin authentication to view SMTP configuration
        Auth::requireRole(['admin']);
        $config = \App\Config\MailConfig::get();
        // Mask password for security
        $config['password_set'] = !empty($config['password']);
        $config['password'] = !empty($config['password']) ? '••••••••••••••••' : '(Not configured)';

        // Fetch recent mail audit logs
        $db = Database::getConnection();
        $logs = $db->query("
            SELECT * FROM audit_logs 
            WHERE action = 'EMAIL_DISPATCH' 
            ORDER BY id DESC LIMIT 15
        ")->fetchAll();

        Response::success('SMTP configuration loaded', [
            'config' => $config,
            'recent_logs' => $logs
        ]);
    }

    /**
     * Test SMTP Email Dispatch Simulation
     */
    public function testSmtp(): void {
        // VULN-02 fix: Require admin authentication to send test SMTP emails
        Auth::requireRole(['admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $type = $input['type'] ?? 'registration'; // registration, approval, enrollment, custom
        $recipientEmail = trim($input['recipient_email'] ?? '');
        $recipientName = trim($input['recipient_name'] ?? 'Test Recipient');

        if (!$recipientEmail || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            Response::error('Please provide a valid recipient email address.');
        }

        $result = [];

        switch ($type) {
            case 'registration':
                $result = Mailer::sendApplicantRegistration([
                    'first_name'     => $input['first_name'] ?? 'Juan',
                    'last_name'      => $input['last_name'] ?? 'Dela Cruz',
                    'email'          => $recipientEmail,
                    'username'       => $input['username'] ?? 'juandelacruz123',
                    'application_no' => $input['application_no'] ?? ('ADM-' . date('Y') . '-9999')
                ]);
                break;

            case 'approval':
                $result = Mailer::sendRegistrarApproval([
                    'first_name'   => $input['first_name'] ?? 'Juan',
                    'last_name'    => $input['last_name'] ?? 'Dela Cruz',
                    'email'        => $recipientEmail,
                    'student_no'   => $input['student_no'] ?? (date('Y') . '-SHS-0099'),
                    'section_name' => $input['section_name'] ?? '11 - STEM Einstein'
                ], [
                    'assessment_no'   => $input['assessment_no'] ?? ('ASS-' . date('Y') . '-0099'),
                    'net_amount'      => (float)($input['net_amount'] ?? 12500.00),
                    'min_downpayment' => 3000.00
                ]);
                break;

            case 'enrollment':
                $result = Mailer::sendOfficialEnrollment([
                    'first_name' => $input['first_name'] ?? 'Juan',
                    'last_name'  => $input['last_name'] ?? 'Dela Cruz',
                    'email'      => $recipientEmail,
                    'student_id' => $input['student_id'] ?? (date('Y') . '-SHS-0099')
                ], [
                    'or_number'   => $input['or_number'] ?? ('OR-' . date('Y') . '-0099'),
                    'amount_paid' => (float)($input['amount_paid'] ?? 3000.00)
                ]);
                break;

            case 'custom':
            default:
                $subject = $input['subject'] ?? 'SMTP Test Ping - BSLA Admissions';
                $body = $input['body'] ?? '<p>This is an automated SMTP test email from the Biringan Science and Leadership Academy (BSLA) Enrollment System.</p>';
                $result = Mailer::send($recipientEmail, $recipientName, $subject, $body);
                break;
        }

        Response::success('SMTP Test Triggered Successfully', $result);
    }
}
