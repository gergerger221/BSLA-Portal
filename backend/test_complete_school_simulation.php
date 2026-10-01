<?php
/**
 * COMPLETE SENIOR HIGH SCHOOL ENROLLMENT SYSTEM SIMULATION
 * Comprehensive End-to-End Operational Lifecycle & Multi-Role Verification
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/Database.php';

$baseUrl = 'http://localhost/enrollment_system/sia-project/backend/api/index.php';

function apiCall(string $route, string $method = 'GET', array $data = [], ?string $token = null): array {
    global $baseUrl;
    $url = $baseUrl . '?route=' . $route;
    
    $headers = [
        "Content-Type: application/json",
        "Accept: application/json"
    ];
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }
    
    $options = [
        'http' => [
            'method'        => $method,
            'header'        => implode("\r\n", $headers) . "\r\n",
            'ignore_errors' => true,
            'timeout'       => 10
        ]
    ];
    if (in_array($method, ['POST', 'PUT', 'DELETE']) && !empty($data)) {
        $options['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    $httpCode = 500;
    if (isset($http_response_header[0]) && preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $matches)) {
        $httpCode = (int)$matches[1];
    }
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true) ?: [],
        'raw'  => $response
    ];
}

function userLogin(string $username, string $password = 'password123'): ?string {
    $res = apiCall('auth/login', 'POST', ['username' => $username, 'password' => $password]);
    return $res['body']['data']['token'] ?? null;
}

$passCount = 0;
$failCount = 0;
$logHistory = [];

function recordTest(bool $condition, string $stepName, string $details = ''): bool {
    global $passCount, $failCount, $logHistory;
    if ($condition) {
        $passCount++;
        echo "  [\033[32mPASS\033[0m] {$stepName}\n";
        $logHistory[] = ['status' => 'PASS', 'step' => $stepName, 'details' => $details];
        return true;
    } else {
        $failCount++;
        echo "  [\033[31mFAIL\033[0m] {$stepName} - {$details}\n";
        $logHistory[] = ['status' => 'FAIL', 'step' => $stepName, 'details' => $details];
        return false;
    }
}

echo "=========================================================================================\n";
echo "        COMPLETE SENIOR HIGH SCHOOL ENROLLMENT SYSTEM SIMULATION (REAL-WORLD E2E)       \n";
echo "=========================================================================================\n\n";

$db = App\Config\Database::getConnection();

// --- 0. DISCOVERY OF ALL ROLES & USERS ---
echo "--- STEP 0: System Discovery of Roles, Accounts & Portals ---\n";
$roles = $db->query("SELECT id, name, slug, description FROM roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "Discovered " . count($roles) . " System Roles:\n";
foreach ($roles as $r) {
    echo "  - [Role #{$r['id']}] {$r['name']} (slug: '{$r['slug']}')\n";
}

// Authenticate all known base role accounts
$authTokens = [
    'admin'       => userLogin('admin'),
    'coordinator' => userLogin('maria_coordinator'),
    'registrar'   => userLogin('maria_registrar'),
    'treasury'    => userLogin('maria_treasury'),
    'records'     => userLogin('maria_records'),
    'teacher'     => userLogin('prof_delacruz')
];

foreach ($authTokens as $roleName => $tok) {
    recordTest(!empty($tok), "Authentication for {$roleName}", "Obtained Bearer token");
}

// --- STEP 1: SUPER ADMINISTRATOR WORKFLOW ---
echo "\n--- STEP 1: Super Administrator Operations ---\n";
$adminStats = apiCall('admin/stats', 'GET', [], $authTokens['admin']);
recordTest($adminStats['code'] === 200 && isset($adminStats['body']['data']['total_applicants']), "Admin Dashboard Analytics Loaded", "Applicants: " . ($adminStats['body']['data']['total_applicants'] ?? 0));

$adminUsers = apiCall('admin/users', 'GET', [], $authTokens['admin']);
recordTest($adminUsers['code'] === 200 && !empty($adminUsers['body']['data']['users']), "Admin User Roster Loaded", "Total users: " . count($adminUsers['body']['data']['users'] ?? []));

// Super Admin verifies active school year
$syRes = apiCall('admin/school-years', 'GET', [], $authTokens['admin']);
recordTest($syRes['code'] === 200 && !empty($syRes['body']['data']), "Admin Active School Year Verified", "Active SY ID: " . ($syRes['body']['data'][0]['id'] ?? 1));

// --- STEP 2: ACADEMIC COORDINATOR SETUP (Curriculum, Subjects, Sections, Timetables) ---
echo "\n--- STEP 2: Academic Coordinator Curriculum & Timetable Engine ---\n";
$currRes = apiCall('coordinator/curriculum', 'GET', [], $authTokens['coordinator']);
recordTest($currRes['code'] === 200 && !empty($currRes['body']['data']['strands']), "Coordinator Curriculum Overview Loaded", "Strands count: " . count($currRes['body']['data']['strands'] ?? []));

// 2a. Coordinator creates a new STEM specialized subject
$uniqueSubCode = 'SIM-STEM-' . rand(100, 999);
$subCreate = apiCall('coordinator/save-subject', 'POST', [
    'code'           => $uniqueSubCode,
    'title'          => 'Robotics and Applied Artificial Intelligence',
    'description'    => 'Introductory AI, machine learning and robotics laboratory for SHS STEM.',
    'category'       => 'SHS Specialized',
    'grade_level_id' => 5, // Grade 11
    'strand_id'      => 1, // STEM
    'semester'       => '1st Semester',
    'lecture_hours'  => 3.0,
    'lab_hours'      => 2.0,
    'units'          => 1.0
], $authTokens['coordinator']);
recordTest($subCreate['code'] === 200 || $subCreate['code'] === 201, "Coordinator Created Specialized Subject ({$uniqueSubCode})", $subCreate['body']['message'] ?? '');
$newSubjectId = (int)($subCreate['body']['data']['id'] ?? $db->lastInsertId());

// 2b. Coordinator creates a new Section
$uniqueSecName = 'Grade 11 - Faraday ' . rand(10, 99);
$secCreate = apiCall('coordinator/save-section', 'POST', [
    'name'           => $uniqueSecName,
    'grade_level_id' => 5, // Grade 11
    'strand_id'      => 1, // STEM
    'max_capacity'   => 45,
    'room'           => 'Innovation Lab 301',
    'adviser_id'     => 6  // Prof. Dela Cruz
], $authTokens['coordinator']);
recordTest($secCreate['code'] === 200 || $secCreate['code'] === 201, "Coordinator Created Section ({$uniqueSecName})", $secCreate['body']['message'] ?? '');
$newSectionId = (int)($secCreate['body']['data']['id'] ?? $db->lastInsertId());

// 2c. Coordinator builds schedule item for this section
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$freeDay = $days[array_rand($days)];
$startHour = rand(7, 15);
$timeStart = sprintf('%02d:00:00', $startHour);
$timeEnd = sprintf('%02d:30:00', $startHour + 1);
$conflictStart = sprintf('%02d:15:00', $startHour);
$conflictEnd = sprintf('%02d:45:00', $startHour + 1);
$uniqueRoom = 'Lab ' . rand(100, 999);

$schCreate = apiCall('schedules/save', 'POST', [
    'section_id'  => $newSectionId,
    'subject_id'  => $newSubjectId,
    'semester'    => '1st Semester',
    'teacher_id'  => 6,
    'day_of_week' => $freeDay,
    'time_start'  => $timeStart,
    'time_end'    => $timeEnd,
    'room'        => $uniqueRoom
], $authTokens['coordinator']);
recordTest($schCreate['code'] === 200, "Coordinator Created Timetable Schedule ({$timeStart}-{$timeEnd} {$freeDay})", $schCreate['body']['message'] ?? '');

// 2d. Coordinator Conflict Detection Test (Negative scenario: Section collision)
$conflictTest = apiCall('schedules/save', 'POST', [
    'section_id'  => $newSectionId,
    'subject_id'  => $newSubjectId,
    'semester'    => '1st Semester',
    'teacher_id'  => 7,
    'day_of_week' => $freeDay,
    'time_start'  => $conflictStart, // Overlaps with timeStart - timeEnd
    'time_end'    => $conflictEnd,
    'room'        => $uniqueRoom
], $authTokens['coordinator']);
recordTest($conflictTest['code'] === 400, "Scheduler Conflict Engine Rejected Section Schedule Overlap", $conflictTest['body']['message'] ?? '');

// 2e. Coordinator publishes a school milestone event
$eventCreate = apiCall('events/save', 'POST', [
    'title'           => 'SHS STEM Science & Robotics Congress ' . date('Y'),
    'description'     => 'Annual science exhibition and robotics competition.',
    'event_category'  => 'Academic',
    'start_date'      => date('Y-m-d', strtotime('+7 days')),
    'end_date'        => date('Y-m-d', strtotime('+8 days')),
    'location'        => 'University Auditorium',
    'target_audience' => 'All',
    'is_published'    => 1
], $authTokens['coordinator']);
recordTest($eventCreate['code'] === 200, "Coordinator Published School-Wide Academic Event", $eventCreate['body']['message'] ?? '');

// --- STEP 3: APPLICANT REGISTRATION & ADMISSION PROCEDURE ---
echo "\n--- STEP 3: Admission Applicant Onboarding, Form Submission & Uploads ---\n";
$simEmail = 'applicant.sim.' . time() . '@example.com';
$simPassword = 'password123';

// 3a. Self-register applicant
$regRes = apiCall('auth/register-applicant', 'POST', [
    'first_name'     => 'Julian',
    'middle_name'    => 'Reyes',
    'last_name'      => 'Velasquez',
    'email'          => $simEmail,
    'contact_number' => '09179876543',
    'password'       => $simPassword
]);
recordTest($regRes['code'] === 201 && !empty($regRes['body']['data']['token']), "New Applicant Self-Registration Successful ({$simEmail})", "App No: " . ($regRes['body']['data']['application_no'] ?? 'N/A'));
$applicantToken = $regRes['body']['data']['token'] ?? null;
$applicantUserId = (int)($regRes['body']['data']['user_id'] ?? 0);

// 3b. Applicant retrieves initial Draft profile
$myAppRes = apiCall('admission/my-application', 'GET', [], $applicantToken);
recordTest($myAppRes['code'] === 200 && $myAppRes['body']['data']['status'] === 'Pending' || $myAppRes['body']['data']['status'] === 'Draft', "Applicant Retrieved Draft Profile", "Status: " . ($myAppRes['body']['data']['status'] ?? ''));
$appId = (int)($myAppRes['body']['data']['id'] ?? 0);

// 3c. Applicant fills out multi-step enrollment form
$updateApp = apiCall('admission/update', 'POST', [
    'applicant_type'        => 'New Student',
    'lrn'                   => '123456789012',
    'first_name'            => 'Julian',
    'middle_name'           => 'Reyes',
    'last_name'             => 'Velasquez',
    'suffix'                => '',
    'gender'                => 'Male',
    'birthdate'             => '2009-05-14',
    'birthplace'            => 'Quezon City',
    'civil_status'          => 'Single',
    'nationality'           => 'Filipino',
    'religion'              => 'Catholic',
    'contact_number'        => '09179876543',
    'address_street'        => '123 Katipunan Ave',
    'address_barangay'      => 'Loyola Heights',
    'address_city'          => 'Quezon City',
    'address_province'      => 'Metro Manila',
    'address_zip'           => '1108',
    'guardian_name'         => 'Elena Velasquez',
    'guardian_relationship' => 'Mother',
    'guardian_contact'      => '09181234567',
    'guardian_occupation'   => 'Engineer',
    'last_school_attended'  => 'St. Jude Catholic School',
    'last_school_type'      => 'Private',
    'last_school_year'      => '2025-2026',
    'last_grade_completed'  => 'Grade 10',
    'grade_level_id'        => 5, // Grade 11
    'track_id'              => 1, // Academic Track
    'strand_id'             => 1, // STEM
    'voucher_status'        => 'None'
], $applicantToken);
recordTest($updateApp['code'] === 200, "Applicant Saved Form (Grade 11 STEM Selection)", $updateApp['body']['message'] ?? '');

// 3d. Applicant attempts submission without requirements (Negative test)
$preSubmit = apiCall('admission/submit', 'POST', [], $applicantToken);
recordTest($preSubmit['code'] === 400, "Submission Blocked When Mandatory Credentials Missing", $preSubmit['body']['message'] ?? '');

// 3e. Simulate Document Uploads for all mandatory requirements
$reqDocs = [
    'PSA Birth Certificate',
    'SF9 / Form 138 (Report Card)',
    'Certificate of Good Moral Character',
    '2x2 ID Picture',
    'Certificate of JHS Completion'
];

$docIds = [];
$mockUploadDir = __DIR__ . '/uploads/admission_docs';
if (!is_dir($mockUploadDir)) {
    mkdir($mockUploadDir, 0777, true);
}

foreach ($reqDocs as $docType) {
    $mockFileName = 'sim_' . time() . '_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $docType)) . '.pdf';
    $mockPath = 'uploads/admission_docs/' . $mockFileName;
    file_put_contents(__DIR__ . '/' . $mockPath, '%PDF-1.4 Mock Requirement Content for ' . $docType);
    
    $insDoc = $db->prepare("
        INSERT INTO admission_documents (application_id, document_type, file_path, original_filename, file_size, status)
        VALUES (:app_id, :doc_type, :file_path, :orig_name, 1024, 'Pending')
    ");
    $insDoc->execute([
        'app_id'    => $appId,
        'doc_type'  => $docType,
        'file_path' => $mockPath,
        'orig_name' => $docType . '.pdf'
    ]);
    $docIds[$docType] = (int)$db->lastInsertId();
}
recordTest(count($docIds) === 5, "Applicant Uploaded All 5 Required Credentials", "Documents stored in DB");

// 3f. Applicant Submits Completed Application
$subAppRes = apiCall('admission/submit', 'POST', [], $applicantToken);
recordTest($subAppRes['code'] === 200, "Applicant Submitted Application for Registrar Review (Status: Under Review)", $subAppRes['body']['message'] ?? '');

// --- STEP 4: REGISTRAR EVALUATION, DOCUMENT VERIFICATION & SECTION ALLOCATION ---
echo "\n--- STEP 4: Registrar Audit, Document Verification & Section Assignment ---\n";
// 4a. Registrar views applications
$regApps = apiCall('registrar/applications', 'GET', [], $authTokens['registrar']);
recordTest($regApps['code'] === 200, "Registrar Retrieved Applications Queue", "Applications in queue: " . count($regApps['body']['data'] ?? []));

// 4b. Registrar retrieves specific application details
$appDetail = apiCall("registrar/application-details&id={$appId}", 'GET', [], $authTokens['registrar']);
recordTest($appDetail['code'] === 200 && !empty($appDetail['body']['data']['documents']), "Registrar Inspected Applicant Documents & Available Sections", "Applicant: " . ($appDetail['body']['data']['first_name'] ?? '') . " " . ($appDetail['body']['data']['last_name'] ?? ''));

// 4c. Registrar verifies all uploaded documents
foreach ($docIds as $dType => $dId) {
    $vRes = apiCall('registrar/verify-document', 'POST', [
        'document_id'        => $dId,
        'status'             => 'Verified',
        'verification_notes' => 'Authentic and stamped by Registrar.'
    ], $authTokens['registrar']);
    recordTest($vRes['code'] === 200, "Registrar Verified Document: {$dType}", $vRes['body']['message'] ?? '');
}

// 4d. Registrar approves applicant and places them in enrollment queue for target section
$queueRes = apiCall('registrar/approve-and-queue', 'POST', [
    'application_id' => $appId,
    'section_id'     => $newSectionId,
    'remarks'        => 'All credentials verified. Enrolled into Grade 11 STEM.'
], $authTokens['registrar']);
recordTest($queueRes['code'] === 200, "Registrar Approved Applicant & Generated Assessment Ticket", "Assigned Student No: " . ($queueRes['body']['data']['student_no'] ?? 'N/A') . " | Assessment No: " . ($queueRes['body']['data']['assessment_no'] ?? 'N/A'));
$generatedStudentNo = $queueRes['body']['data']['student_no'] ?? null;
$generatedAssNo = $queueRes['body']['data']['assessment_no'] ?? null;

// --- STEP 5: TREASURY & CASHIER PAYMENT PROCESSING ---
echo "\n--- STEP 5: Treasury Assessment Inquiry & Official Receipt Processing ---\n";
// 5a. Treasury queries billing queue
$treasuryList = apiCall('treasury/assessments', 'GET', [], $authTokens['treasury']);
recordTest($treasuryList['code'] === 200, "Treasury Retrieved Assessment Queue", "Pending assessments: " . count($treasuryList['body']['data'] ?? []));

// 5b. Find assessment ID created for our applicant
$assRow = $db->query("SELECT * FROM student_assessments WHERE enrollment_id IN (SELECT id FROM enrollments WHERE application_id = {$appId}) LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$assessmentId = (int)($assRow['id'] ?? 0);
recordTest($assessmentId > 0, "Treasury Located Assessment Record #{$assessmentId}", "Net Payable: PHP " . ($assRow['net_payable'] ?? 0));

// 5c. Treasury inspects assessment details & fee structure
$assDetail = apiCall("treasury/assessment-details&id={$assessmentId}", 'GET', [], $authTokens['treasury']);
recordTest($assDetail['code'] === 200 && !empty($assDetail['body']['data']['assessment_no']), "Treasury Inspected Fee Structure & Downpayment Schedule", "Total Assessed: PHP " . ($assDetail['body']['data']['total_assessed'] ?? 0));

// 5d. Treasury processes payment and issues Official Receipt
$payRes = apiCall('treasury/process-payment', 'POST', [
    'assessment_id'  => $assessmentId,
    'amount_paid'    => 5000.00,
    'payment_method' => 'Cash',
    'reference_no'   => 'CSH-TX-' . time(),
    'remarks'        => 'Initial tuition downpayment for 1st Semester enrollment.'
], $authTokens['treasury']);
recordTest($payRes['code'] === 200, "Treasury Issued Official Receipt (Status: Officially Enrolled)", "OR Number: " . ($payRes['body']['data']['or_number'] ?? 'N/A'));
$issuedOrNumber = $payRes['body']['data']['or_number'] ?? null;

// --- STEP 6: OFFICIAL STUDENT ACCOUNT & STUDENT PORTAL ---
echo "\n--- STEP 6: Official Student Account Login & Portal Exploration ---\n";
// 6a. Student logs in using official student number / credentials
$studentNo = $payRes['body']['data']['student_no'] ?? $queueRes['body']['data']['student_no'] ?? null;
$studentPassword = $payRes['body']['data']['default_password'] ?? 'VELASQUEZ';

$studentLoginRes = apiCall('auth/login', 'POST', [
    'username' => $studentNo,
    'password' => $studentPassword
]);
$studentToken = $studentLoginRes['body']['data']['token'] ?? null;
$studentUserId = (int)($studentLoginRes['body']['data']['id'] ?? $payRes['body']['data']['student_user_id'] ?? 0);
recordTest(!empty($studentToken), "Enrolled Student Logged In via Student Number ({$studentNo})", "Role: " . ($studentLoginRes['body']['data']['role_name'] ?? ''));

// 6b. Student loads complete Student Dashboard (Profile, Timetable, Balance, Calendar)
$studDash = apiCall('student/dashboard', 'GET', [], $studentToken);
recordTest($studDash['code'] === 200 && !empty($studDash['body']['data']['enrollment']), "Student Dashboard Loaded with Section & Timetable", "Section: " . ($studDash['body']['data']['enrollment']['section_name'] ?? 'N/A'));

// 6c. Student verifies enrolled subjects match section curriculum
$enrolledSubs = $studDash['body']['data']['subjects'] ?? [];
recordTest(count($enrolledSubs) > 0, "Student Enrolled Subjects Automatically Linked to Timetable", "Subjects count: " . count($enrolledSubs));

// 6d. Student submits a formal Document Request (DepEd Form 137 / Certificate of Enrollment)
$docReqRes = apiCall('records/save-document-request', 'POST', [
    'document_type' => 'Certificate of Enrollment',
    'purpose'       => 'Scholarship Application & Financial Assistance',
    'copies'        => 2,
    'remarks'       => 'Please expedite for CHED submission.'
], $studentToken);
recordTest($docReqRes['code'] === 200, "Student Submitted Document Request (Cert of Enrollment)", "Control No: " . ($docReqRes['body']['data']['control_number'] ?? 'N/A'));
$docReqId = (int)($docReqRes['body']['data']['id'] ?? 0);

// --- STEP 7: SCHOOL RECORDS CUSTODIAN & DEPED REPORTING ---
echo "\n--- STEP 7: School Records Custodian Processing & Official DepEd Reports ---\n";
// 7a. Records officer retrieves document requests
$recReqs = apiCall('records/document-requests', 'GET', [], $authTokens['records']);
recordTest($recReqs['code'] === 200, "Records Custodian Retrieved Document Requests Queue", "Pending requests: " . count($recReqs['body']['data'] ?? []));

// 7b. Records officer releases requested document
$relRes = apiCall('records/update-request-status', 'POST', [
    'id'      => $docReqId,
    'status'  => 'Released',
    'remarks' => 'Printed on security paper and dry-sealed.'
], $authTokens['records']);
recordTest($relRes['code'] === 200, "Records Custodian Released Document Request #{$docReqId}", $relRes['body']['message'] ?? '');

// 7c. Records officer queries Student Academic Transcript
$transRes = apiCall("records/transcript&student_id={$studentUserId}", 'GET', [], $authTokens['records']);
recordTest($transRes['code'] === 200 && !empty($transRes['body']['data']['student_number']), "Records Custodian Loaded DepEd SF10 Permanent Record", "Student: " . ($transRes['body']['data']['first_name'] ?? '') . " " . ($transRes['body']['data']['last_name'] ?? ''));

// 7d. Records officer generates official DepEd School Form 1 (SF1 - Master Register)
$sf1Res = apiCall("records/school-form-1&section_id={$newSectionId}", 'GET', [], $authTokens['records']);
recordTest($sf1Res['code'] === 200 && isset($sf1Res['body']['data']['students']), "Records Custodian Generated DepEd School Form 1 (SF1)", "Enrolled in section: " . count($sf1Res['body']['data']['students'] ?? []));

// 7e. Records officer generates official DepEd School Form 5 (SF5 - Promotion & Proficiency)
$sf5Res = apiCall("records/school-form-5&section_id={$newSectionId}", 'GET', [], $authTokens['records']);
recordTest($sf5Res['code'] === 200 && isset($sf5Res['body']['data']['records']), "Records Custodian Generated DepEd School Form 5 (SF5)", "Promotion records: " . count($sf5Res['body']['data']['records'] ?? []));

// --- STEP 8: PENETRATION & AUTHORIZATION BYPASS RESISTANCE ---
echo "\n--- STEP 8: Multi-Role Authorization & IDOR Penetration Testing ---\n";
// 8a. Student attempting administrative access
$sec1 = apiCall('admin/users', 'GET', [], $studentToken);
recordTest($sec1['code'] === 403, "Student Blocked From Admin Module (403 Forbidden)", "Got HTTP {$sec1['code']}");

// 8b. Teacher attempting payment clearance
$sec2 = apiCall('treasury/process-payment', 'POST', ['assessment_id' => 1, 'amount_paid' => 500], $authTokens['teacher']);
recordTest($sec2['code'] === 403, "Teacher Blocked From Treasury Payment Clearance (403 Forbidden)", "Got HTTP {$sec2['code']}");

// 8c. Applicant attempting section approval
$sec3 = apiCall('registrar/approve-and-queue', 'POST', ['application_id' => 1], $applicantToken);
recordTest($sec3['code'] === 403, "Applicant Blocked From Self-Approving Enrollment Queue (403 Forbidden)", "Got HTTP {$sec3['code']}");

// 8d. IDOR test: Student A querying Student B's transcript
$otherStudentId = (int)$db->query("SELECT id FROM users WHERE role_id = 7 AND id != {$studentUserId} LIMIT 1")->fetchColumn();
if ($otherStudentId) {
    $sec4 = apiCall("records/transcript&student_id={$otherStudentId}", 'GET', [], $studentToken);
    recordTest($sec4['code'] === 403, "Student A Blocked From Accessing Student B's Transcript (IDOR 403)", "Got HTTP {$sec4['code']}");
}

// 8e. IDOR test: Applicant querying another student's billing assessment
$sec5 = apiCall("treasury/assessment-details&id=1", 'GET', [], $applicantToken);
recordTest($sec5['code'] === 403, "Applicant Blocked From Viewing Another Student's Assessment (IDOR 403)", "Got HTTP {$sec5['code']}");

echo "\n=========================================================================================\n";
echo " SIMULATION SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "=========================================================================================\n";
