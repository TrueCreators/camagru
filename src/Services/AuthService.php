<?php

declare(strict_types=1);

namespace Services;

use Core\Session;
use Core\Mailer;
use Models\User;

/**
 * Authentication service
 */
class AuthService
{
    /**
     * Register new user
     */
    public static function register(string $username, string $email, string $password): array
    {
        // Check if email already exists
        if (User::emailExists($email)) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        // Check if username already exists
        if (User::usernameExists($username)) {
            return ['success' => false, 'error' => 'Username already taken'];
        }

        // Create user
        $userId = User::createUser($username, $email, $password);
        $user = User::find($userId);

        // Send verification email
        Mailer::sendVerificationEmail($email, $username, $user['verification_token']);

        return [
            'success' => true,
            'message' => 'Registration successful. Please check your email to verify your account.'
        ];
    }

    /**
     * Login user
     */
    public static function login(string $emailOrUsername, string $password): array
    {
        // Find user by email or username
        $user = User::findByEmail($emailOrUsername);
        if ($user === null) {
            $user = User::findByUsername($emailOrUsername);
        }

        if ($user === null) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Verify password
        if (!User::verifyPassword($user, $password)) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Check if verified
        if (!$user['is_verified']) {
            return ['success' => false, 'error' => 'Please verify your email before logging in'];
        }

        // Set session
        Session::setUser([
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ]);

        return ['success' => true, 'user' => $user];
    }

    /**
     * Logout user
     */
    public static function logout(): void
    {
        Session::logout();
    }

    /**
     * Verify email token
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
     * Request password reset
     */
    public static function requestPasswordReset(string $email): array
    {
        $user = User::findByEmail($email);

        // Always return success to prevent email enumeration
        if ($user === null) {
            return ['success' => true, 'message' => 'If an account exists with this email, you will receive a password reset link.'];
        }

        $token = User::setResetToken($user['id']);
        Mailer::sendPasswordResetEmail($email, $user['username'], $token);

        return ['success' => true, 'message' => 'If an account exists with this email, you will receive a password reset link.'];
    }

    /**
     * Reset password with token
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
     * Get current authenticated user
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
     * Update current user's profile
     */
    public static function updateProfile(array $data): array
    {
        $userId = Session::getUserId();
        if ($userId === null) {
            return ['success' => false, 'error' => 'Not authenticated'];
        }

        $user = User::find($userId);

        // Check email uniqueness if changed
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            if (User::emailExists($data['email'], $userId)) {
                return ['success' => false, 'error' => 'Email already in use'];
            }
        }

        // Check username uniqueness if changed
        if (isset($data['username']) && $data['username'] !== $user['username']) {
            if (User::usernameExists($data['username'], $userId)) {
                return ['success' => false, 'error' => 'Username already taken'];
            }
        }

        User::updateProfile($userId, $data);

        // Update session
        $updatedUser = User::find($userId);
        Session::setUser([
            'id' => $updatedUser['id'],
            'username' => $updatedUser['username'],
            'email' => $updatedUser['email']
        ]);

        return ['success' => true, 'user' => $updatedUser];
    }

    /**
     * Change password
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
