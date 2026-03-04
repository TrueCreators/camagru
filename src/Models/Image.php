<?php

declare(strict_types=1);

namespace Models;

use Core\Model;

class Image extends Model
{
    protected static string $table = 'images';

    public static function getByUser(int $userId, string $orderBy = 'created_at', string $direction = 'DESC'): array
    {
        return self::where(['user_id' => $userId], $orderBy, $direction);
    }

    public static function getWithDetails(int $imageId): ?array
    {
        $sql = "
            SELECT
                i.*,
                u.username,
                (SELECT COUNT(*) FROM likes WHERE image_id = i.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE image_id = i.id) as comments_count
            FROM images i
            JOIN users u ON i.user_id = u.id
            WHERE i.id = ?
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->execute([$imageId]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public static function getAllWithDetails(int $page = 1, int $perPage = 5, ?int $currentUserId = null): array
    {
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countSql = "SELECT COUNT(*) FROM images";
        $total = (int)self::db()->query($countSql)->fetchColumn();
        $totalPages = (int)ceil($total / $perPage);

        // Get images with details
        $sql = "
            SELECT
                i.*,
                u.username,
                (SELECT COUNT(*) FROM likes WHERE image_id = i.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE image_id = i.id) as comments_count
        ";

        if ($currentUserId !== null) {
            $sql .= ", (SELECT COUNT(*) FROM likes WHERE image_id = i.id AND user_id = ?) as user_liked";
        }

        $sql .= "
            FROM images i
            JOIN users u ON i.user_id = u.id
            ORDER BY i.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = self::db()->prepare($sql);

        if ($currentUserId !== null) {
            $stmt->execute([$currentUserId, $perPage, $offset]);
        } else {
            $stmt->execute([$perPage, $offset]);
        }

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages
        ];
    }

    public static function getUserImagesWithDetails(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countSql = "SELECT COUNT(*) FROM images WHERE user_id = ?";
        $stmt = self::db()->prepare($countSql);
        $stmt->execute([$userId]);
        $total = (int)$stmt->fetchColumn();
        $totalPages = (int)ceil($total / $perPage);

        // Get images with details
        $sql = "
            SELECT
                i.*,
                u.username,
                (SELECT COUNT(*) FROM likes WHERE image_id = i.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE image_id = i.id) as comments_count
            FROM images i
            JOIN users u ON i.user_id = u.id
            WHERE i.user_id = ?
            ORDER BY i.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->execute([$userId, $perPage, $offset]);

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages
        ];
    }

    public static function deleteImage(int $imageId, int $userId): bool
    {
        // Only delete if the image belongs to the user
        $sql = "DELETE FROM images WHERE id = ? AND user_id = ?";
        $stmt = self::db()->prepare($sql);
        return $stmt->execute([$imageId, $userId]) && $stmt->rowCount() > 0;
    }

    public static function belongsToUser(int $imageId, int $userId): bool
    {
        $sql = "SELECT COUNT(*) FROM images WHERE id = ? AND user_id = ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$imageId, $userId]);
        return $stmt->fetchColumn() > 0;
    }
}
