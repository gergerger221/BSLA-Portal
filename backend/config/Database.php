<?php
// backend/config/Database.php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $db = new self();
            self::$instance = $db->connect();
        }
        return self::$instance;
    }

    private function connect(): PDO {
        // Load from environment variables with fallback defaults (VULN-01 companion fix)
        $host    = Env::get('DB_HOST', '127.0.0.1');
        $dbName  = Env::get('DB_NAME', 'sia_highschool_db');
        $user    = Env::get('DB_USERNAME', 'root');
        $pass    = Env::get('DB_PASSWORD', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // VULN-14 fix: Log full details, return generic message to client
            error_log("[SIA-DB] Connection failed: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            $isDevMode = ($_ENV['APP_ENV'] ?? 'production') === 'development';
            $msg = $isDevMode
                ? 'Database connection failed: ' . $e->getMessage() . '. Please ensure MySQL is running in XAMPP and `sia_highschool_db` database is imported.'
                : 'A database connectivity error occurred. Please try again later or contact the administrator.';
            echo json_encode([
                'success' => false,
                'message' => $msg
            ]);
            exit;
        }
    }
}
