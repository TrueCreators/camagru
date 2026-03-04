<?php

declare(strict_types=1);

namespace Core;

/**
 * View Helper Functions
 */
class View
{
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function e(string $value): string
    {
        return self::escape($value);
    }

    public static function csrf(): string
    {
        return CSRF::getInputField();
    }

    public static function csrfToken(): string
    {
        return CSRF::getToken();
    }

    public static function url(string $path = ''): string
    {
        require_once dirname(__DIR__, 2) . '/config/config.php';
        return \Config::getAppUrl() . '/' . ltrim($path, '/');
    }

    public static function asset(string $path): string
    {
        return self::url($path);
    }

    public static function isLoggedIn(): bool
    {
        return Session::isLoggedIn();
    }

    public static function user(): ?array
    {
        return Session::getUser();
    }

    public static function flash(string $key): mixed
    {
        return Session::getFlash($key);
    }

    public static function hasFlash(string $key): bool
    {
        return Session::hasFlash($key);
    }

    public static function old(string $key, string $default = ''): string
    {
        $old = Session::getFlash('old');
        return self::escape($old[$key] ?? $default);
    }

    public static function error(string $key): ?string
    {
        $errors = Session::getFlash('errors');
        return isset($errors[$key]) ? $errors[$key][0] : null;
    }

    public static function errors(): array
    {
        return Session::getFlash('errors') ?? [];
    }

    public static function hasError(string $key): bool
    {
        $errors = Session::get('_flash')['errors'] ?? [];
        return isset($errors[$key]);
    }

    public static function formatDate(string $date, string $format = 'M d, Y'): string
    {
        return date($format, strtotime($date));
    }

    public static function timeAgo(string $datetime): string
    {
        $time = strtotime($datetime);
        $diff = time() - $time;

        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return self::formatDate($datetime);
        }
    }

    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . $suffix;
    }
}
