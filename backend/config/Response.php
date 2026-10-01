<?php
// backend/config/Response.php
namespace App\Config;

class Response {
    public static function json(bool $success, string $message, $data = null, int $statusCode = 200): void {
        // Allow CORS for local Vue development and XAMPP (VULN-04 fix: restrict origins)
        if (!headers_sent()) {
            $allowedOrigins = array_map('trim', explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:5173,http://localhost:5174,http://localhost'));
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (in_array($origin, $allowedOrigins, true)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Credentials: true');
            } elseif (empty($origin)) {
                header('Access-Control-Allow-Origin: http://localhost');
            }
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            http_response_code($statusCode);
        }

        // Clear any previous output or stray PHP warning buffer to prevent JSON corruption
        if (ob_get_length()) {
            ob_clean();
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $message, int $statusCode = 400, $data = null): void {
        self::json(false, $message, $data, $statusCode);
    }

    public static function success(string $message = 'Success', $data = null, int $statusCode = 200): void {
        self::json(true, $message, $data, $statusCode);
    }
}
