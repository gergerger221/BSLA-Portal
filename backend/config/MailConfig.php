<?php
// backend/config/MailConfig.php
namespace App\Config;

class MailConfig {
    /**
     * Get Mail & SMTP Configuration
     * Reads from environment variables (.env) — NEVER hardcode credentials here.
     * (VULN-01 fix: credentials moved to .env)
     */
    public static function get(): array {
        return [
            // Switch to true to send live emails over SMTP
            'enabled'    => filter_var(Env::get('SMTP_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),
            
            // SMTP Server Connection
            'host'       => Env::get('SMTP_HOST', 'smtp.gmail.com'),
            'port'       => (int) Env::get('SMTP_PORT', '587'),
            'encryption' => Env::get('SMTP_ENCRYPTION', 'tls'),
            'auth'       => filter_var(Env::get('SMTP_AUTH', 'true'), FILTER_VALIDATE_BOOLEAN),
            
            // SMTP Credentials (loaded from .env)
            'username'   => Env::get('SMTP_USERNAME', ''),
            'password'   => Env::get('SMTP_PASSWORD', ''),
            
            // Institutional Sender Information
            'from_email' => Env::get('SMTP_FROM_EMAIL', ''),
            'from_name'  => Env::get('SMTP_FROM_NAME', 'School Admissions'),
            'reply_to'   => Env::get('SMTP_REPLY_TO', ''),
            
            // SMTP Debug level (0 = off, 1 = client commands, 2 = client + server)
            'debug'      => (int) Env::get('SMTP_DEBUG', '0')
        ];
    }
}
