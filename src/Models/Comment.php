<?php

declare(strict_types=1);

namespace Models;

use Core\Model;

class Comment extends Model
{
    protected static string $table = 'comments';

    public static function getByImage(int $imageId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countSql = "SELECT COUNT(*) FROM comments WHERE image_id = ?";
        $stmt = self::db()->prepare($countSql);
        $stmt->execute([$imageId]);
        $total = (int)$stmt->fetchColumn();
        $totalPages = (int)ceil($total / $perPage);

        // Get comments with user info
        $sql = "
            SELECT
                c.*,
                u.username
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.image_id = ?
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = self::db()->prepare($sql);
        $stmt->execute([$imageId, $perPage, $offset]);

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages
        ];
    }

    public static function addComment(int $imageId, int $userId, string $content): int
    {
        return self::create([
            'image_id' => $imageId,
            'user_id' => $userId,
            'content' => $content
        ]);
    }

    public static function deleteComment(int $commentId, int $userId): bool
    {
        $sql = "DELETE FROM comments WHERE id = ? AND user_id = ?";
        $stmt = self::db()->prepare($sql);
        return $stmt->execute([$commentId, $userId]) && $stmt->rowCount() > 0;
    }

    public static function getCountByImage(int $imageId): int
    {
        $sql = "SELECT COUNT(*) FROM comments WHERE image_id = ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$imageId]);
        return (int)$stmt->fetchColumn();
    }
}
