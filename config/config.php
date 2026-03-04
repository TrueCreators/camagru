<?php

declare(strict_types=1);

/**
 * Application Configuration
 * Loads environment variables and provides config access
 */

class Config
{
    private static array $config = [];
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = dirname(__DIR__) . '/.env';

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) {
                    continue;
                }

                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);

                    // Remove quotes if present
                    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                        (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                        $value = substr($value, 1, -1);
                    }

                    self::$config[$key] = $value;
                    $_ENV[$key] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        return self::$config[$key] ?? $_ENV[$key] ?? $default;
    }

    public static function isDebug(): bool
    {
        return self::get('APP_DEBUG', 'false') === 'true';
    }

    public static function getAppUrl(): string
    {
        return rtrim(self::get('APP_URL', 'http://localhost:8080'), '/');
    }

    public static function getAppName(): string
    {
        return self::get('APP_NAME', 'Camagru');
    }

    public static function getDbConfig(): array
    {
        return [
            'host' => self::get('DB_HOST', 'mysql'),
            'port' => self::get('DB_PORT', '3306'),
            'name' => self::get('DB_NAME', 'camagru'),
            'user' => self::get('DB_USER', 'camagru_user'),
            'password' => self::get('DB_PASSWORD', 'camagru_password'),
        ];
    }

    public static function getSecret(): string
    {
        return self::get('APP_SECRET', 'default_secret_key');
    }
}
