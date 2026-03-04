<?php

declare(strict_types=1);

namespace Models;

use Core\Model;

class Like extends Model
{
    protected static string $table = 'likes';

    public static function toggle(int $imageId, int $userId): array
    {
        // Проверка, существует ли лайк
        $sql = "SELECT id FROM likes WHERE image_id = ? AND user_id = ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$imageId, $userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Удаление лайка
            self::delete($existing['id']);
            $liked = false;
        } else {
            // Добавление лайка
            self::create([
                'image_id' => $imageId,
                'user_id' => $userId
            ]);
            $liked = true;
        }

        // Получение нового количества
        $count = self::getCountByImage($imageId);

        return [
            'liked' => $liked,
            'count' => $count
        ];
    }

    public static function hasLiked(int $imageId, int $userId): bool
    {
        $sql = "SELECT COUNT(*) FROM likes WHERE image_id = ? AND user_id = ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$imageId, $userId]);
        return $stmt->fetchColumn() > 0;
    }

    public static function getCountByImage(int $imageId): int
    {
        $sql = "SELECT COUNT(*) FROM likes WHERE image_id = ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$imageId]);
        return (int)$stmt->fetchColumn();
    }

    public static function getLikedImageIds(int $userId): array
    {
        $sql = "SELECT image_id FROM likes WHERE user_id = ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'image_id');
    }
}
