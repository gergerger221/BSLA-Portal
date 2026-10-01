<?php
// backend/helpers/Mailer.php
namespace App\Helpers;

use App\Config\MailConfig;
use App\Config\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    /**
     * Send Generic Email via PHPMailer SMTP
     * 
     * @param string $toEmail Recipient email
     * @param string $toName Recipient full name
     * @param string $subject Subject line
     * @param string $htmlBody HTML content
     * @param string $altBody Plain text fallback
     * @return array ['success' => bool, 'message' => string]
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody = ''): array {
        // Validate destination email format
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            self::logMail($toEmail, $subject, 'FAILED', 'Invalid recipient email format: ' . $toEmail);
            return ['success' => false, 'message' => 'Invalid email address format.'];
        }

        $config = MailConfig::get();

        // If mailer is disabled in config, record in logs and return mock success safely
        if (empty($config['enabled'])) {
            self::logMail($toEmail, $subject, 'SKIPPED', 'SMTP sending disabled in MailConfig.php (Simulated Success)');
            return [
                'success' => true, 
                'message' => 'SMTP is in simulated mode. Enable live SMTP in backend/config/MailConfig.php when ready.',
                'simulated' => true
            ];
        }

        // Require vendor autoloader if not already loaded
        $autoloader = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }

        $mail = new PHPMailer(true);

        try {
            // Server configuration
            $mail->SMTPDebug = $config['debug'] ?? 0;
            $mail->isSMTP();
            $mail->Host       = $config['host'];
            $mail->SMTPAuth   = $config['auth'];
            $mail->Username   = $config['username'];
            $mail->Password   = $config['password'];
            $mail->SMTPSecure = $config['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $config['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 10; // 10 seconds timeout

            // Sender & Recipient
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($toEmail, $toName);
            if (!empty($config['reply_to'])) {
                $mail->addReplyTo($config['reply_to'], $config['from_name']);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = !empty($altBody) ? $altBody : strip_tags($htmlBody);

            $mail->send();
            self::logMail($toEmail, $subject, 'SENT', 'Email successfully delivered via SMTP server.');

            return ['success' => true, 'message' => 'Email sent successfully via SMTP.'];
        } catch (\Throwable $e) {
            $errorDetails = $mail->ErrorInfo ?: $e->getMessage();
            self::logMail($toEmail, $subject, 'FAILED', $errorDetails);
            
            // Fail-safe: Log error without crashing the application flow
            return ['success' => false, 'message' => 'Failed to dispatch email: ' . $errorDetails];
        }
    }

    /**
     * 1. Send Application Received Notification Email (On Applicant Registration)
     */
    public static function sendApplicantRegistration(array $applicantData): array {
        $name = trim(($applicantData['first_name'] ?? '') . ' ' . ($applicantData['last_name'] ?? ''));
        $email = $applicantData['email'] ?? '';
        $appNo = $applicantData['application_no'] ?? 'Pending';
        $username = $applicantData['username'] ?? '';

        $subject = "Admission Application Received: {$appNo} - Biringan Science & Leadership Academy";

        $html = self::wrapTemplate("
            <h2 style='color: #0c2340; margin-top: 0;'>Welcome to Biringan Science and Leadership Academy!</h2>
            <p>Dear <strong>{$name}</strong>,</p>
            <p>Your temporary admission account has been successfully created for <strong>School Year 2026–2027</strong>.</p>
            
            <div style='background-color: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 18px; margin: 20px 0;'>
                <p style='margin: 0 0 8px 0;'><strong>Application Reference No:</strong> <span style='font-family: monospace; font-size: 16px; color: #0c2340;'>{$appNo}</span></p>
                <p style='margin: 0 0 8px 0;'><strong>Registered Email:</strong> {$email}</p>
                <p style='margin: 0;'><strong>Temporary Username:</strong> <span style='font-family: monospace; color: #b45309;'>{$username}</span></p>
            </div>

            <h3 style='color: #0c2340;'>Next Steps to Complete Admission:</h3>
            <ol style='padding-left: 20px; line-height: 1.6; color: #334155;'>
                <li>Log in to your <strong>Admission Portal</strong>.</li>
                <li>Enter your 12-digit DepEd LRN and personal demographics.</li>
                <li>Upload digital copies of your <strong>PSA Birth Certificate</strong> and <strong>SF9 Form 138 (Report Card)</strong>.</li>
                <li>Await Registrar document verification and Section assignment.</li>
            </ol>

            <p style='margin-top: 24px;'>
                <a href='http://localhost/sia-project2/frontend/dist/index.html#/login' style='background-color: #0c2340; color: #93c5fd; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; display: inline-block;'>Access Admission Dashboard &rarr;</a>
            </p>
        ");

        return self::send($email, $name, $subject, $html);
    }

    /**
     * 2. Send Registrar Approval & Assessment Ready Notification Email
     */
    public static function sendRegistrarApproval(array $applicantData, array $assessmentData): array {
        $name = trim(($applicantData['first_name'] ?? '') . ' ' . ($applicantData['last_name'] ?? ''));
        $email = $applicantData['email'] ?? '';
        $studentNo = $applicantData['student_no'] ?? '';
        $sectionName = $applicantData['section_name'] ?? 'Main Section';
        $assNo = $assessmentData['assessment_no'] ?? '';
        $netPayable = number_format((float)($assessmentData['net_amount'] ?? 0), 2);
        $minDownpayment = number_format((float)($assessmentData['min_downpayment'] ?? 3000), 2);

        $subject = "Admission Approved & Assessment Ready - BSLA Admissions";

        $html = self::wrapTemplate("
            <h2 style='color: #059669; margin-top: 0;'>&#10004; Your Admission Has Been Approved!</h2>
            <p>Dear <strong>{$name}</strong>,</p>
            <p>Congratulations! The Registrar has reviewed and authenticated your submitted credentials. You have been officially approved for enrollment at Biringan Science and Leadership Academy.</p>
            
            <div style='background-color: #ecfdf5; border: 2px solid #a7f3d0; border-radius: 12px; padding: 18px; margin: 20px 0;'>
                <p style='margin: 0 0 8px 0;'><strong>Assigned Student ID:</strong> <span style='font-family: monospace; font-size: 16px; color: #065f46;'>{$studentNo}</span></p>
                <p style='margin: 0 0 8px 0;'><strong>Assigned Section:</strong> {$sectionName}</p>
                <p style='margin: 0 0 8px 0;'><strong>Assessment Form No:</strong> {$assNo}</p>
                <p style='margin: 0 0 8px 0;'><strong>Total Net Tuition:</strong> &#8369;{$netPayable}</p>
                <p style='margin: 0;'><strong>Minimum Required Downpayment:</strong> <strong style='color: #065f46;'>&#8369;{$minDownpayment}</strong></p>
            </div>

            <h3 style='color: #0c2340;'>Payment Instructions:</h3>
            <p style='color: #334155; line-height: 1.5;'>
                You may settle your tuition downpayment online via <strong>PayMongo (GCash/Maya/Bank)</strong> or at the campus <strong>Cashier Window</strong> using your printed assessment slip.
            </p>

            <p style='margin-top: 24px;'>
                <a href='http://localhost/sia-project2/frontend/dist/index.html#/admission' style='background-color: #059669; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; display: inline-block;'>Complete Downpayment &rarr;</a>
            </p>
        ");

        return self::send($email, $name, $subject, $html);
    }

    /**
     * 3. Send Official Enrollment Confirmed Email (On Treasury Payment Verification)
     */
    public static function sendOfficialEnrollment(array $studentData, array $orData): array {
        $name = trim(($studentData['first_name'] ?? '') . ' ' . ($studentData['last_name'] ?? ''));
        $email = $studentData['email'] ?? '';
        $studentId = $studentData['student_id'] ?? '';
        $orNo = $orData['or_number'] ?? '';
        $amountPaid = number_format((float)($orData['amount_paid'] ?? 0), 2);

        $subject = "Official Certificate of Registration (COR) - BSLA Admissions";

        $html = self::wrapTemplate("
            <h2 style='color: #0c2340; margin-top: 0;'>&#127891; You Are Officially Enrolled!</h2>
            <p>Dear <strong>{$name}</strong>,</p>
            <p>Your tuition payment of <strong>&#8369;{$amountPaid}</strong> has been verified by the Treasury Department. You are now officially enrolled as a student of Biringan Science and Leadership Academy for <strong>S.Y. 2026–2027</strong>.</p>
            
            <div style='background-color: #eff6ff; border: 2px solid #bfdbfe; border-radius: 12px; padding: 18px; margin: 20px 0;'>
                <p style='margin: 0 0 8px 0;'><strong>Official Student ID:</strong> <span style='font-family: monospace; font-size: 16px; color: #1e3a8a;'>{$studentId}</span></p>
                <p style='margin: 0 0 8px 0;'><strong>Official Receipt (OR) No:</strong> {$orNo}</p>
                <p style='margin: 0;'><strong>Enrollment Status:</strong> <span style='color: #059669; font-weight: bold;'>OFFICIALLY ENROLLED</span></p>
            </div>

            <h3 style='color: #0c2340;'>Student Portal Access:</h3>
            <p style='color: #334155; line-height: 1.5;'>
                You can now log in to the <strong>Student Portal</strong> using your permanent Student ID (<code>{$studentId}</code>) or registered email to view your class timetable, official schedule, and Certificate of Registration (COR).
            </p>

            <p style='margin-top: 24px;'>
                <a href='http://localhost/sia-project2/frontend/dist/index.html#/login' style='background-color: #1e3a8a; color: #93c5fd; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; display: inline-block;'>Sign In to Student Portal &rarr;</a>
            </p>
        ");

        return self::send($email, $name, $subject, $html);
    }

    /**
     * Wrap HTML in an institutional school email template
     */
    private static function wrapTemplate(string $innerContent): string {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Biringan Science and Leadership Academy</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif;'>
            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='table-layout: fixed;'>
                <tr>
                    <td align='center' style='padding: 30px 15px;'>
                        <table border='0' cellpadding='0' cellspacing='0' width='600' style='background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;'>
                            <!-- Header Banner -->
                            <tr>
                                <td style='background: linear-gradient(135deg, #0c2340, #163860); padding: 25px 30px; text-align: center; border-bottom: 4px solid #3b82f6;'>
                                    <h1 style='color: #ffffff; font-size: 19px; font-weight: 900; margin: 0; letter-spacing: 0.5px; font-family: Georgia, serif;'>BIRINGAN SCIENCE AND LEADERSHIP ACADEMY</h1>
                                    <p style='color: #93c5fd; font-size: 11px; font-weight: bold; text-transform: uppercase; margin: 4px 0 0 0; letter-spacing: 1px;'>Junior & Senior High School • \"Innovating for the Nation\" (DepEd ID: 405621)</p>
                                </td>
                            </tr>
                            <!-- Body Content -->
                            <tr>
                                <td style='padding: 35px 30px; font-size: 14px; line-height: 1.6; color: #1e293b;'>
                                    {$innerContent}
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style='background-color: #0f172a; padding: 20px 30px; text-align: center; color: #94a3b8; font-size: 11px; line-height: 1.5;'>
                                    <p style='margin: 0 0 4px 0;'><strong>Biringan Science and Leadership Academy (BSLA)</strong></p>
                                    <p style='margin: 0 0 8px 0;'>Academic Boulevard, Biringan City, Samar, Eastern Visayas • Tel: (055) 888-7766</p>
                                    <p style='margin: 0; color: #64748b;'>This is an automated institutional notification from the SIA Enrollment System.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }

    /**
     * Audit log helper for mail events
     */
    private static function logMail(string $recipient, string $subject, string $status, string $details): void {
        try {
            $db = Database::getConnection();
            Auth::logAudit('EMAIL_DISPATCH', "[{$status}] To: {$recipient} | Subject: {$subject} | Details: {$details}");
        } catch (\Throwable $e) {
            // Ignore DB log failures
        }
    }
}
