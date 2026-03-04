<?php

declare(strict_types=1);

namespace Models;

use Core\Model;
use Core\Database;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return self::findBy('email', $email);
    }

    public static function findByUsername(string $username): ?array
    {
        return self::findBy('username', $username);
    }

    public static function findByVerificationToken(string $token): ?array
    {
        return self::findBy('verification_token', $token);
    }

    public static function findByResetToken(string $token): ?array
    {
        $sql = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function createUser(string $username, string $email, string $password): int
    {
        $verificationToken = bin2hex(random_bytes(32));

        return self::create([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'verification_token' => $verificationToken,
            'is_verified' => 0
        ]);
    }

    public static function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password_hash']);
    }

    public static function verify(int $userId): bool
    {
        return self::update($userId, [
            'is_verified' => 1,
            'verification_token' => null
        ]);
    }

    public static function setResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        self::update($userId, [
            'reset_token' => $token,
            'reset_token_expires' => $expires
        ]);

        return $token;
    }

    public static function resetPassword(int $userId, string $newPassword): bool
    {
        return self::update($userId, [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'reset_token' => null,
            'reset_token_expires' => null
        ]);
    }

    public static function updateProfile(int $userId, array $data): bool
    {
        $allowedFields = ['username', 'email', 'notify_comments'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        return self::update($userId, $updateData);
    }

    public static function updatePassword(int $userId, string $newPassword): bool
    {
        return self::update($userId, [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
    }

    public static function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
        $params = [$email];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    }

    public static function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $params = [$username];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    }
}
