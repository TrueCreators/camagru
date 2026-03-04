<?php

declare(strict_types=1);

namespace Services;

use Core\Mailer;
use Models\User;
use Models\Image;

/**
 * Сервис уведомлений по электронной почте
 */
class EmailService
{
    /**
     * Отправляет уведомление о комментарии владельцу изображения
     */
    public static function notifyCommentOnImage(int $imageId, int $commenterId): bool
    {
        // Получение изображения с информацией о владельце
        $image = Image::getWithDetails($imageId);
        if ($image === null) {
            return false;
        }

        // Получение владельца изображения
        $owner = User::find($image['user_id']);
        if ($owner === null) {
            return false;
        }

        // Не отправлять уведомление, если владелец прокомментировал своё изображение
        if ($owner['id'] === $commenterId) {
            return true;
        }

        // Проверка, включены ли у владельца уведомления
        if (!$owner['notify_comments']) {
            return true;
        }

        // Получение информации о комментаторе
        $commenter = User::find($commenterId);
        if ($commenter === null) {
            return false;
        }

        // Отправка уведомления
        return Mailer::sendCommentNotification(
            $owner['email'],
            $owner['username'],
            $commenter['username'],
            (string)$imageId
        );
    }
}
