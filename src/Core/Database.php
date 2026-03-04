<?php

declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;

/**
 * Одиночка подключения к базе данных на PDO
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    private static function connect(): void
    {
        require_once dirname(__DIR__, 2) . '/config/config.php';

        $config = \Config::getDbConfig();

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['name']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            self::$instance = new PDO($dsn, $config['user'], $config['password'], $options);
        } catch (PDOException $e) {
            if (\Config::isDebug()) {
                throw new PDOException("Database connection failed: " . $e->getMessage());
            }
            throw new PDOException("Database connection failed");
        }
    }

    public static function closeConnection(): void
    {
        self::$instance = null;
    }
}
