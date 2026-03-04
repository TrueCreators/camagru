<?php

declare(strict_types=1);

namespace Services;

use Core\Mailer;
use Models\User;
use Models\Image;

/**
 * Email notification service
 */
class EmailService
{
    /**
     * Send comment notification to image owner
     */
    public static function notifyCommentOnImage(int $imageId, int $commenterId): bool
    {
        // Get image with owner info
        $image = Image::getWithDetails($imageId);
        if ($image === null) {
            return false;
        }

        // Get image owner
        $owner = User::find($image['user_id']);
        if ($owner === null) {
            return false;
        }

        // Don't notify if owner commented on their own image
        if ($owner['id'] === $commenterId) {
            return true;
        }

        // Check if owner wants notifications
        if (!$owner['notify_comments']) {
            return true;
        }

        // Get commenter info
        $commenter = User::find($commenterId);
        if ($commenter === null) {
            return false;
        }

        // Send notification
        return Mailer::sendCommentNotification(
            $owner['email'],
            $owner['username'],
            $commenter['username'],
            (string)$imageId
        );
    }
}
