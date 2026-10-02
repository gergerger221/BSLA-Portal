<?php
// backend/helpers/Auth.php
namespace App\Helpers;

use App\Config\Database;
use App\Config\Env;
use App\Config\Response;
use PDO;

class Auth {
    /**
     * Generates a base64 session token with expiry.
     * (VULN-07 fix: tokens now have a configurable TTL)
     */
    public static function generateToken(int $userId): string {
        $token = bin2hex(random_bytes(32));
        $expiryHours = (int) Env::get('TOKEN_EXPIRY_HOURS', '24');
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiryHours * 3600));

        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET remember_token = :token, token_expires_at = :expires WHERE id = :id");
        $stmt->execute(['token' => $token, 'expires' => $expiresAt, 'id' => $userId]);
        return $token;
    }

    /**
     * Extracts and validates user from Authorization header.
     * (VULN-08 fix: removed URL query parameter token fallback)
     * (VULN-07 fix: checks token expiry)
     */
    public static function user(): ?array {
        $token = self::extractBearerToken();
        if (!$token) {
            return null;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.role_id, u.username, u.email, u.student_id, u.status,
                   r.name as role_name, r.slug as role_slug,
                   p.first_name, p.middle_name, p.last_name, p.suffix, p.gender, p.contact_number
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN user_profiles p ON u.id = p.user_id
            WHERE u.remember_token = :token 
              AND u.status = 'Active'
              AND (u.token_expires_at IS NULL OR u.token_expires_at > NOW())
            LIMIT 1
        ");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Extracts token from all possible standard, FastCGI, and custom request headers.
     */
    public static function extractBearerToken(): ?string {
        $candidateHeaders = [];

        if (function_exists('getallheaders')) {
            $candidateHeaders = array_merge($candidateHeaders, getallheaders() ?: []);
        }
        if (function_exists('apache_request_headers')) {
            $candidateHeaders = array_merge($candidateHeaders, apache_request_headers() ?: []);
        }

        // Case-insensitive header lookups
        $headerMap = [];
        foreach ($candidateHeaders as $k => $v) {
            $headerMap[strtolower($k)] = $v;
        }

        $rawToken = $headerMap['authorization'] 
            ?? $headerMap['x-auth-token'] 
            ?? $headerMap['x-authorization']
            ?? $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? $_SERVER['HTTP_X_AUTH_TOKEN']
            ?? $_SERVER['HTTP_X_AUTHORIZATION']
            ?? '';

        if (empty($rawToken)) {
            return null;
        }

        // Check if prefixed with "Bearer "
        if (preg_match('/Bearer\s+(\S+)/i', $rawToken, $matches)) {
            return trim($matches[1]);
        }

        // Raw hex token format check (64 hex characters or valid non-space token string)
        $clean = trim($rawToken);
        if (!empty($clean) && !str_contains($clean, ' ')) {
            return $clean;
        }

        return null;
    }

    /**
     * Ensures the request is authenticated.
     */
    public static function requireAuth(): array {
        $user = self::user();
        if (!$user) {
            Response::error('Unauthorized. Please login to continue.', 401);
        }
        return $user;
    }

    /**
     * Ensures the authenticated user has one of the allowed roles.
     * By default, $allowAdmin allows super admin to oversee administrative functions.
     * Set $allowAdmin to false for strictly personal/instructional roles (e.g. Teacher faculty loads).
     */
    public static function requireRole(array $allowedRoles, bool $allowAdmin = true): array {
        $user = self::requireAuth();
        $isAdminBypass = $allowAdmin && ($user['role_slug'] === 'admin');
        if (!in_array($user['role_slug'], $allowedRoles) && !$isAdminBypass) {
            Response::error('Forbidden: You do not have permission to access this resource.', 403);
        }
        return $user;
    }

    /**
     * VULN-06 fix: Check and enforce login rate limiting.
     * Returns true if the IP is allowed to attempt login, false if rate-limited.
     */
    public static function checkRateLimit(string $identity): bool {
        $db = Database::getConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $windowSeconds = 30;
        $maxAttempts = 5;

        // Count recent failed attempts from this IP in the last 30 seconds
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM login_attempts 
            WHERE ip_address = :ip 
              AND attempted_at > DATE_SUB(NOW(), INTERVAL :window SECOND)
              AND was_successful = 0
        ");
        $stmt->execute(['ip' => $ip, 'window' => $windowSeconds]);
        $failCount = (int) $stmt->fetchColumn();

        return $failCount < $maxAttempts;
    }

    /**
     * Record a login attempt (successful or failed).
     */
    public static function recordLoginAttempt(string $identity, bool $success): void {
        try {
            $db = Database::getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Unknown';

            $stmt = $db->prepare("
                INSERT INTO login_attempts (ip_address, username_attempted, was_successful, user_agent, attempted_at)
                VALUES (:ip, :identity, :success, :ua, NOW())
            ");
            $stmt->execute([
                'ip'       => $ip,
                'identity' => substr($identity, 0, 255),
                'success'  => $success ? 1 : 0,
                'ua'       => substr($ua, 0, 512)
            ]);

            // Clean up old records (older than 24 hours) periodically
            if (rand(1, 100) <= 5) {
                $db->exec("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            }
        } catch (\Exception $e) {
            // Don't break login flow if rate-limiting table is missing
            error_log("[SIA-Auth] Rate limit record failed: " . $e->getMessage());
        }
    }

    /**
     * Logs an action into the audit_logs table.
     */
    public static function logAudit(string $action, string $details = '', ?int $userId = null): void {
        try {
            $db = Database::getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Unknown';
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent)
                VALUES (:user_id, :action, :details, :ip, :ua)
            ");
            $stmt->execute([
                'user_id' => $userId,
                'action'  => $action,
                'details' => $details,
                'ip'      => $ip,
                'ua'      => $ua
            ]);
        } catch (\Exception $e) {
            // Ignore audit logging errors to not break main transaction
        }
    }
}
