<?php

declare(strict_types=1);

namespace Core;

use PDO;

/**
 * Базовая модель с общими операциями базы данных
 */
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    protected static function db(): PDO
    {
        return Database::getInstance();
    }

    public static function find(int $id): ?array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function findBy(string $column, mixed $value): ?array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE {$column} = ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function all(string $orderBy = null, string $direction = 'ASC'): array
    {
        $sql = "SELECT * FROM " . static::$table;

        if ($orderBy) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY {$orderBy} {$direction}";
        }

        $stmt = self::db()->query($sql);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO " . static::$table . " ({$columns}) VALUES ({$placeholders})";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_values($data));

        return (int)self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = ?";
        }

        $sql = "UPDATE " . static::$table . " SET " . implode(', ', $setParts) .
            " WHERE " . static::$primaryKey . " = ?";

        $values = array_values($data);
        $values[] = $id;

        $stmt = self::db()->prepare($sql);
        return $stmt->execute($values);
    }

    public static function delete(int $id): bool
    {
        $sql = "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
        $stmt = self::db()->prepare($sql);
        return $stmt->execute([$id]);
    }

    public static function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) FROM " . static::$table;

        if (!empty($conditions)) {
            $whereParts = [];
            foreach (array_keys($conditions) as $column) {
                $whereParts[] = "{$column} = ?";
            }
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_values($conditions));

        return (int)$stmt->fetchColumn();
    }

    public static function exists(array $conditions): bool
    {
        return self::count($conditions) > 0;
    }

    public static function where(array $conditions, string $orderBy = null, string $direction = 'ASC'): array
    {
        $sql = "SELECT * FROM " . static::$table;

        $whereParts = [];
        foreach (array_keys($conditions) as $column) {
            $whereParts[] = "{$column} = ?";
        }
        $sql .= " WHERE " . implode(' AND ', $whereParts);

        if ($orderBy) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY {$orderBy} {$direction}";
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_values($conditions));

        return $stmt->fetchAll();
    }

    public static function paginate(int $page = 1, int $perPage = 10, string $orderBy = null, string $direction = 'DESC'): array
    {
        $offset = ($page - 1) * $perPage;
        $total = self::count();
        $totalPages = (int)ceil($total / $perPage);

        $sql = "SELECT * FROM " . static::$table;

        if ($orderBy) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY {$orderBy} {$direction}";
        }

        $sql .= " LIMIT ? OFFSET ?";

        $stmt = self::db()->prepare($sql);
        $stmt->execute([$perPage, $offset]);

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages
        ];
    }
}
