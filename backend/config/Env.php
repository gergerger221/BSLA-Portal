<?php
// backend/config/Env.php
namespace App\Config;

/**
 * Simple .env file loader for environment variable management.
 * Replaces vlucas/phpdotenv to avoid composer dependency issues.
 */
class Env {
    private static bool $loaded = false;

    /**
     * Load environment variables from a .env file.
     */
    public static function load(string $path = null): void {
        if (self::$loaded) return;

        $path = $path ?? __DIR__ . '/../.env';
        if (!file_exists($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments
            if (str_starts_with($line, '#') || str_starts_with($line, ';')) continue;
            // Must contain =
            if (!str_contains($line, '=')) continue;

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            // Only set if not already defined by the real environment
            if (!isset($_ENV[$key]) && getenv($key) === false) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        self::$loaded = true;
    }

    /**
     * Get an environment variable with a default fallback.
     */
    public static function get(string $key, mixed $default = null): mixed {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
