<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple email sending using PHP mail()
 */
class Mailer
{
    private string $to;
    private string $subject;
    private string $body;
    private array $headers = [];
    private bool $isHtml = true;

    public function __construct()
    {
        require_once dirname(__DIR__, 2) . '/config/config.php';

        $fromEmail = \Config::get('MAIL_FROM', 'noreply@camagru.local');
        $fromName = \Config::get('MAIL_FROM_NAME', 'Camagru');

        $this->headers = [
            'From' => "{$fromName} <{$fromEmail}>",
            'Reply-To' => $fromEmail,
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0'
        ];
    }

    public function to(string $email): self
    {
        $this->to = $email;
        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function html(bool $isHtml = true): self
    {
        $this->isHtml = $isHtml;
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): bool
    {
        if (empty($this->to) || empty($this->subject) || empty($this->body)) {
            return false;
        }

        if ($this->isHtml) {
            $this->headers['Content-Type'] = 'text/html; charset=UTF-8';
        } else {
            $this->headers['Content-Type'] = 'text/plain; charset=UTF-8';
        }

        $headerString = '';
        foreach ($this->headers as $name => $value) {
            $headerString .= "{$name}: {$value}\r\n";
        }

        return mail($this->to, $this->subject, $this->body, $headerString);
    }

    public static function sendVerificationEmail(string $email, string $username, string $token): bool
    {
        require_once dirname(__DIR__, 2) . '/config/config.php';

        $appUrl = \Config::getAppUrl();
        $appName = \Config::getAppName();
        $verifyUrl = "{$appUrl}/verify?token={$token}";

        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 6px; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Welcome to {$appName}!</h2>
                <p>Hi {$username},</p>
                <p>Thank you for registering. Please click the button below to verify your email address:</p>
                <p><a href='{$verifyUrl}' class='button'>Verify Email</a></p>
                <p>Or copy and paste this link into your browser:</p>
                <p>{$verifyUrl}</p>
                <div class='footer'>
                    <p>If you didn't create an account, you can safely ignore this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return (new self())
            ->to($email)
            ->subject("{$appName} - Verify Your Email")
            ->body($body)
            ->send();
    }

    public static function sendPasswordResetEmail(string $email, string $username, string $token): bool
    {
        require_once dirname(__DIR__, 2) . '/config/config.php';

        $appUrl = \Config::getAppUrl();
        $appName = \Config::getAppName();
        $resetUrl = "{$appUrl}/reset-password?token={$token}";

        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 6px; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Password Reset Request</h2>
                <p>Hi {$username},</p>
                <p>We received a request to reset your password. Click the button below to set a new password:</p>
                <p><a href='{$resetUrl}' class='button'>Reset Password</a></p>
                <p>Or copy and paste this link into your browser:</p>
                <p>{$resetUrl}</p>
                <p>This link will expire in 1 hour.</p>
                <div class='footer'>
                    <p>If you didn't request a password reset, you can safely ignore this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return (new self())
            ->to($email)
            ->subject("{$appName} - Password Reset")
            ->body($body)
            ->send();
    }

    public static function sendCommentNotification(string $email, string $username, string $commenterName, string $imageId): bool
    {
        require_once dirname(__DIR__, 2) . '/config/config.php';

        $appUrl = \Config::getAppUrl();
        $appName = \Config::getAppName();
        $imageUrl = "{$appUrl}/gallery#{$imageId}";

        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 6px; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>New Comment on Your Photo</h2>
                <p>Hi {$username},</p>
                <p><strong>{$commenterName}</strong> commented on your photo.</p>
                <p><a href='{$imageUrl}' class='button'>View Comment</a></p>
                <div class='footer'>
                    <p>You can disable comment notifications in your profile settings.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return (new self())
            ->to($email)
            ->subject("{$appName} - New Comment on Your Photo")
            ->body($body)
            ->send();
    }
}
