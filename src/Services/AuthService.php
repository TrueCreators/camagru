<?php

declare(strict_types=1);

namespace Services;

use Core\Session;
use Core\Mailer;
use Models\User;

/**
 * Сервис аутентификации
 */
class AuthService
{
    /**
     * Регистрирует нового пользователя
     */
    public static function register(string $username, string $email, string $password): array
    {
        // Проверка, существует ли адрес электронной почты
        if (User::emailExists($email)) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        // Проверка, существует ли имя пользователя
        if (User::usernameExists($username)) {
            return ['success' => false, 'error' => 'Username already taken'];
        }

        // Создание пользователя
        $userId = User::createUser($username, $email, $password);
        $user = User::find($userId);

        // Отправка письма для подтверждения
        Mailer::sendVerificationEmail($email, $username, $user['verification_token']);

        return [
            'success' => true,
            'message' => 'Registration successful. Please check your email to verify your account.'
        ];
    }

    /**
     * Выполняет вход пользователя
     */
    public static function login(string $emailOrUsername, string $password): array
    {
        // Поиск пользователя по адресу электронной почты или имени пользователя
        $user = User::findByEmail($emailOrUsername);
        if ($user === null) {
            $user = User::findByUsername($emailOrUsername);
        }

        if ($user === null) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Проверка пароля
        if (!User::verifyPassword($user, $password)) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Проверка подтверждения адреса электронной почты
        if (!$user['is_verified']) {
            return ['success' => false, 'error' => 'Please verify your email before logging in'];
        }

        // Установка сессии
        Session::setUser([
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ]);

        return ['success' => true, 'user' => $user];
    }

    /**
     * Выполняет выход пользователя
     */
    public static function logout(): void
    {
        Session::logout();
    }

    /**
     * Проверяет токен подтверждения адреса электронной почты
     */
    public static function verifyEmail(string $token): array
    {
        $user = User::findByVerificationToken($token);

        if ($user === null) {
            return ['success' => false, 'error' => 'Invalid or expired verification token'];
        }

        User::verify($user['id']);

        return ['success' => true, 'message' => 'Email verified successfully. You can now log in.'];
    }

    /**
     * Запрашивает сброс пароля
     */
    public static function requestPasswordReset(string $email): array
    {
        $user = User::findByEmail($email);

        // Всегда возвращать успех, чтобы не допустить перебор адресов электронной почты
        if ($user === null) {
            return ['success' => true, 'message' => 'If an account exists with this email, you will receive a password reset link.'];
        }

        $token = User::setResetToken($user['id']);
        Mailer::sendPasswordResetEmail($email, $user['username'], $token);

        return ['success' => true, 'message' => 'If an account exists with this email, you will receive a password reset link.'];
    }

    /**
     * Сбрасывает пароль по токену
     */
    public static function resetPassword(string $token, string $newPassword): array
    {
        $user = User::findByResetToken($token);

        if ($user === null) {
            return ['success' => false, 'error' => 'Invalid or expired reset token'];
        }

        User::resetPassword($user['id'], $newPassword);

        return ['success' => true, 'message' => 'Password reset successfully. You can now log in.'];
    }

    /**
     * Возвращает текущего аутентифицированного пользователя
     */
    public static function getCurrentUser(): ?array
    {
        if (!Session::isLoggedIn()) {
            return null;
        }

        $userId = Session::getUserId();
        return User::find($userId);
    }

    /**
     * Обновляет профиль текущего пользователя
     */
    public static function updateProfile(array $data): array
    {
        $userId = Session::getUserId();
        if ($userId === null) {
            return ['success' => false, 'error' => 'Not authenticated'];
        }

        $user = User::find($userId);

        // Проверка уникальности адреса электронной почты при изменении
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            if (User::emailExists($data['email'], $userId)) {
                return ['success' => false, 'error' => 'Email already in use'];
            }
        }

        // Проверка уникальности имени пользователя при изменении
        if (isset($data['username']) && $data['username'] !== $user['username']) {
            if (User::usernameExists($data['username'], $userId)) {
                return ['success' => false, 'error' => 'Username already taken'];
            }
        }

        User::updateProfile($userId, $data);

        // Обновление сессии
        $updatedUser = User::find($userId);
        Session::setUser([
            'id' => $updatedUser['id'],
            'username' => $updatedUser['username'],
            'email' => $updatedUser['email']
        ]);

        return ['success' => true, 'user' => $updatedUser];
    }

    /**
     * Меняет пароль
     */
    public static function changePassword(string $currentPassword, string $newPassword): array
    {
        $userId = Session::getUserId();
        if ($userId === null) {
            return ['success' => false, 'error' => 'Not authenticated'];
        }

        $user = User::find($userId);

        if (!User::verifyPassword($user, $currentPassword)) {
            return ['success' => false, 'error' => 'Current password is incorrect'];
        }

        User::updatePassword($userId, $newPassword);

        return ['success' => true, 'message' => 'Password changed successfully'];
    }
}
