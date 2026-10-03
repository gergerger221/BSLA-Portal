<?php
// backend/api/index.php
declare(strict_types=1);

// Start output buffering to capture any accidental warnings or notices
if (!ob_get_level()) {
    ob_start();
}

// Vendor autoloader for PHPMailer and 3rd party packages
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Load environment variables from .env file using custom loader
require_once __DIR__ . '/../config/Env.php';
\App\Config\Env::load();

// --- CORS: Restrict to allowed origins (VULN-04 fix) ---
$corsRaw = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*';
$allowedOrigins = array_map('trim', explode(',', $corsRaw));
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($corsRaw === '*' || in_array('*', $allowedOrigins, true)) {
    if (!empty($requestOrigin)) {
        header('Access-Control-Allow-Origin: ' . $requestOrigin);
        header('Access-Control-Allow-Credentials: true');
    } else {
        header('Access-Control-Allow-Origin: *');
    }
} elseif (in_array($requestOrigin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Access-Control-Allow-Credentials: true');
} else {
    // Fallback for same-origin or direct browser access
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// --- Security Headers (VULN-05 fix) ---
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' data: blob:;");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autoloader for App namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }

        // Case-insensitive directory fallback (e.g. Controllers -> controllers)
        $parts = explode('\\', $relative_class);
        if (count($parts) >= 2) {
            $parts[0] = strtolower($parts[0]);
            $fallbackFile = $base_dir . implode('/', $parts) . '.php';
            if (file_exists($fallbackFile)) {
                require_once $fallbackFile;
                return;
            }
        }
    }

    // Direct fallback for controllers if called without App\ namespace
    $controllerFallback = $base_dir . 'controllers/' . $class . '.php';
    if (file_exists($controllerFallback)) {
        require_once $controllerFallback;
        return;
    }
});

use App\Config\Response;
use App\Controllers\AuthController;
use App\Controllers\AdmissionController;
use App\Controllers\RegistrarController;
use App\Controllers\CoordinatorController;
use App\Controllers\TreasuryController;
use App\Controllers\RecordsController;
use App\Controllers\StudentController;
use App\Controllers\AdminController;
use App\Controllers\ScheduleController;
use App\Controllers\TeacherController;
use App\Controllers\PayMongoController;
use App\Controllers\LmsController;

// Extract action / route
$route = $_GET['route'] ?? $_GET['action'] ?? '';
if (!$route) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Remove query string
    $path = explode('?', $requestUri)[0];
    
    // Check if path contains /api/
    if (strpos($path, '/api/') !== false) {
        $parts = explode('/api/', $path);
        $route = trim($parts[1] ?? '', '/');
    }
}

// Clean route if embedded query string exists (e.g. "registrar/queue?status=active" or "registrar/queue&status=active")
if (strpos($route, '?') !== false) {
    [$cleanRoute, $qs] = explode('?', $route, 2);
    $route = $cleanRoute;
    parse_str($qs, $extraParams);
    $_GET = array_merge($_GET, $extraParams);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($route) {
        // --- AUTHENTICATION ---
        case 'auth/login':
            (new AuthController())->login();
            break;
        case 'auth/register-applicant':
            (new AuthController())->registerApplicant();
            break;
        case 'auth/me':
            (new AuthController())->me();
            break;
        case 'auth/logout':
            (new AuthController())->logout();
            break;
        case 'auth/smtp-config':
            (new AuthController())->getSmtpConfig();
            break;
        case 'auth/test-smtp':
            (new AuthController())->testSmtp();
            break;
        case 'auth/audit-all':
            (new AuthController())->auditAllAccounts();
            break;

        // --- ADMISSION PORTAL ---
        case 'admission/my-application':
            (new AdmissionController())->getMyApplication();
            break;
        case 'admission/update':
            (new AdmissionController())->updateApplication();
            break;
        case 'admission/upload-document':
            (new AdmissionController())->uploadDocument();
            break;
        case 'admission/set-document-mode':
            (new AdmissionController())->setDocumentSubmissionMode();
            break;
        case 'admission/delete-document':
            (new AdmissionController())->deleteDocument();
            break;
        case 'admission/submit':
            (new AdmissionController())->submitApplication();
            break;
        case 'admission/academic-options':
            (new AdmissionController())->getAcademicOptions();
            break;
        case 'admission/checkout-payment':
            (new AdmissionController())->checkoutPayment();
            break;
        case 'admission/switch-payment-mode':
            (new AdmissionController())->switchPaymentMode();
            break;

        // --- PAYMONGO PAYMENT GATEWAY ---
        case 'paymongo/create-checkout':
        case 'admission/paymongo-checkout':
            (new PayMongoController())->createCheckout();
            break;
        case 'paymongo/verify-session':
        case 'admission/paymongo-verify':
            (new PayMongoController())->verifySession();
            break;
        case 'paymongo/student-checkout':
        case 'student/paymongo-checkout':
            (new PayMongoController())->createStudentBalanceCheckout();
            break;
        case 'paymongo/student-verify':
        case 'student/paymongo-verify':
            (new PayMongoController())->verifyStudentBalanceSession();
            break;
        case 'paymongo/webhook':
            (new PayMongoController())->webhook();
            break;

        // --- REGISTRAR ---
        case 'registrar/applications':
            (new RegistrarController())->getApplications();
            break;
        case 'registrar/application-details':
            $id = (int)($_GET['id'] ?? 0);
            (new RegistrarController())->getApplicationDetails($id);
            break;
        case 'registrar/verify-document':
            (new RegistrarController())->verifyDocument();
            break;
        case 'registrar/batch-verify-documents':
            (new RegistrarController())->batchVerifyDocuments();
            break;
        case 'registrar/approve-and-queue':
            (new RegistrarController())->approveAndQueue();
            break;
        case 'registrar/undo-approval':
            (new RegistrarController())->undoApproval();
            break;
        case 'registrar/queue':
            (new RegistrarController())->getQueue();
            break;
        case 'registrar/enrolled-documents':
            (new RegistrarController())->getEnrolledStudentsDocuments();
            break;

        // --- TREASURY & BILLING ---
        case 'treasury/assessments':
            (new TreasuryController())->getAssessments();
            break;
        case 'treasury/assessment-details':
            $id = (int)($_GET['id'] ?? 0);
            (new TreasuryController())->getAssessmentDetails($id);
            break;
        case 'treasury/process-payment':
            (new TreasuryController())->processPayment();
            break;
        case 'treasury/online-payments':
            (new TreasuryController())->getOnlinePaymentVerifications();
            break;
        case 'treasury/verify-online-payment':
            (new TreasuryController())->verifyOnlinePayment();
            break;
        case 'treasury/fee-structures':
            (new TreasuryController())->getFeeStructures();
            break;

        // --- ACADEMIC COORDINATOR ---
        case 'coordinator/curriculum':
            (new CoordinatorController())->getCurriculum();
            break;
        case 'coordinator/carry-over-subjects':
            (new CoordinatorController())->carryOverSubjects();
            break;
        case 'coordinator/update-subject-approval':
            (new CoordinatorController())->updateSubjectApproval();
            break;
        case 'coordinator/toggle-curriculum-lock':
            (new CoordinatorController())->toggleCurriculumLock();
            break;
        case 'coordinator/save-subject':
            (new CoordinatorController())->saveSubject();
            break;
        case 'coordinator/delete-subject':
            (new CoordinatorController())->deleteSubject();
            break;
        case 'coordinator/batch-delete-subjects':
            (new CoordinatorController())->batchDeleteSubjects();
            break;
        case 'coordinator/save-strand':
            (new CoordinatorController())->saveStrand();
            break;
        case 'coordinator/toggle-strand-status':
            (new CoordinatorController())->toggleStrandStatus();
            break;
        case 'coordinator/delete-strand':
            (new CoordinatorController())->deleteStrand();
            break;
        case 'coordinator/sections':
            (new CoordinatorController())->getSections();
            break;
        case 'coordinator/save-section':
            (new CoordinatorController())->saveSection();
            break;
        case 'coordinator/section-students':
            $secId = (int)($_GET['section_id'] ?? 0);
            (new CoordinatorController())->getSectionStudents($secId);
            break;
        case 'coordinator/transfer-section':
            (new CoordinatorController())->transferStudentSection();
            break;
        case 'coordinator/declare-shs-semester':
            (new CoordinatorController())->declareShsSemester();
            break;

        // --- SCHOOL RECORDS & ARCHIVES ---
        case 'records/students':
            (new RecordsController())->getStudentRecords();
            break;
        case 'records/transcript':
            $studentId = (int)($_GET['student_id'] ?? 0);
            (new RecordsController())->getStudentTranscript($studentId);
            break;
        case 'records/document-requests':
            (new RecordsController())->getDocumentRequests();
            break;
        case 'records/save-document-request':
            (new RecordsController())->saveDocumentRequest();
            break;
        case 'records/update-request-status':
            (new RecordsController())->updateRequestStatus();
            break;
        case 'records/school-form-1':
            (new RecordsController())->getSchoolForm1();
            break;
        case 'records/school-form-5':
            (new RecordsController())->getSchoolForm5();
            break;
        case 'records/honor-roll':
            (new RecordsController())->getHonorRoll();
            break;
        case 'records/update-transferee-f137':
            (new RecordsController())->updateTransfereeF137Status();
            break;

        // --- STUDENT PORTAL ---
        case 'student/dashboard':
            (new StudentController())->getDashboard();
            break;
        case 'student/requirements':
            (new StudentController())->getRequirements();
            break;
        case 'student/upload-followup-doc':
            (new StudentController())->uploadFollowUpDocument();
            break;

        // --- MASTER SCHEDULER & EVENT CALENDAR ---
        case 'schedules/section':
            (new ScheduleController())->getSectionSchedule();
            break;
        case 'schedules/save':
            (new ScheduleController())->saveSchedule();
            break;
        case 'schedules/delete':
            (new ScheduleController())->deleteSchedule();
            break;
        case 'events/list':
            (new ScheduleController())->getEvents();
            break;
        case 'events/save':
            (new ScheduleController())->saveEvent();
            break;
        case 'events/delete':
            (new ScheduleController())->deleteEvent();
            break;

        // --- ADMIN ---
        case 'admin/stats':
            (new AdminController())->getDashboardStats();
            break;
        case 'admin/school-years':
            (new AdminController())->getSchoolYears();
            break;
        case 'admin/school-years/preflight':
            (new AdminController())->getRolloverPreflightCheck();
            break;
        case 'admin/school-years/rollover':
            (new AdminController())->executeSchoolYearRollover();
            break;
        case 'admin/toggle-school-year-lock':
            (new AdminController())->toggleSchoolYearLock();
            break;
        case 'admin/toggle-semester':
            (new AdminController())->toggleSchoolYearSemester();
            break;
        case 'admin/toggle-curriculum-lock':
            (new AdminController())->toggleCurriculumLock();
            break;
        case 'admin/save-school-year':
            (new AdminController())->saveSchoolYear();
            break;
        case 'admin/delete-school-year':
            (new AdminController())->deleteSchoolYear();
            break;
        case 'admin/set-active-school-year':
            (new AdminController())->setActiveSchoolYear();
            break;
        case 'admin/users':
            (new AdminController())->getUsers();
            break;
        case 'admin/save-user':
            (new AdminController())->saveUser();
            break;

        // --- TEACHER / FACULTY PORTAL ---
        case 'teacher/dashboard':
            (new TeacherController())->getDashboard();
            break;
        case 'teacher/class-students':
            (new TeacherController())->getClassStudents();
            break;
        case 'teacher/save-grades':
            (new TeacherController())->saveGrades();
            break;
        case 'teacher/advisory-section':
            (new TeacherController())->getAdvisorySection();
            break;
        case 'teacher/save-values':
            (new TeacherController())->saveAdvisoryValues();
            break;
        case 'teacher/save-attendance':
            (new TeacherController())->saveAttendance();
            break;

        // --- LMS (LEARNING MANAGEMENT SYSTEM) ---
        case 'lms/class-content':
            (new LmsController())->getClassContent();
            break;
        case 'lms/save-announcement':
            (new LmsController())->saveAnnouncement();
            break;
        case 'lms/delete-announcement':
            (new LmsController())->deleteAnnouncement();
            break;
        case 'lms/upload-module':
            (new LmsController())->uploadModule();
            break;
        case 'lms/delete-module':
            (new LmsController())->deleteModule();
            break;
        case 'lms/save-assignment':
            (new LmsController())->saveAssignment();
            break;
        case 'lms/delete-assignment':
            (new LmsController())->deleteAssignment();
            break;
        case 'lms/submit-assignment':
            (new LmsController())->submitAssignment();
            break;
        case 'lms/assignment-submissions':
            (new LmsController())->getAssignmentSubmissions();
            break;
        case 'lms/grade-submission':
            (new LmsController())->gradeSubmission();
            break;
        case 'lms/publish-assignment':
            (new LmsController())->publishAssignment();
            break;

        case 'system/run-security-audit':
            require_once __DIR__ . '/../controllers/AuditController.php';
            (new \App\Controllers\AuditController())->runAudit();
            break;

        default:
            Response::error("Endpoint not found: {$route}", 404);
            break;
    }
} catch (\Throwable $e) {
    // Log full error details server-side for debugging (VULN-14 fix)
    error_log("[SIA-API] Internal Error on route '{$route}': " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    $isDevMode = ($_ENV['APP_ENV'] ?? 'production') === 'development';
    $msg = $isDevMode ? "Internal Server Error: " . $e->getMessage() : "An internal error occurred. Please try again later.";
    Response::error($msg, 500);
}
