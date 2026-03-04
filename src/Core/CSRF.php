<?php

declare(strict_types=1);

namespace Core;

/**
 * CSRF Protection
 */
class CSRF
{
    private const TOKEN_NAME = '_csrf_token';
    private const TOKEN_LENGTH = 32;

    public static function generate(): string
    {
        Session::start();

        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        Session::set(self::TOKEN_NAME, $token);

        return $token;
    }

    public static function getToken(): string
    {
        Session::start();

        $token = Session::get(self::TOKEN_NAME);

        if ($token === null) {
            $token = self::generate();
        }

        return $token;
    }

    public static function validate(?string $token): bool
    {
        if ($token === null) {
            return false;
        }

        Session::start();
        $storedToken = Session::get(self::TOKEN_NAME);

        if ($storedToken === null) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    public static function getInputField(): string
    {
        $token = self::getToken();
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars(self::TOKEN_NAME, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }

    public static function getTokenFromRequest(): ?string
    {
        // Check POST data first
        if (isset($_POST[self::TOKEN_NAME])) {
            return $_POST[self::TOKEN_NAME];
        }

        // Check $_SERVER (FastCGI converts headers to HTTP_*)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        // Check headers (for AJAX requests)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['X-CSRF-Token'])) {
                return $headers['X-CSRF-Token'];
            }
            if (isset($headers['x-csrf-token'])) {
                return $headers['x-csrf-token'];
            }
        }

        return null;
    }

    public static function validateRequest(): bool
    {
        return self::validate(self::getTokenFromRequest());
    }
}
