<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\DatabaseConfig;
use PDO;
use PDOException;
use RuntimeException;

class DatabaseConnection
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}
    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize singleton");
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }

    private static function createConnection(): PDO
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s',
                DatabaseConfig::HOST,
                DatabaseConfig::DBNAME
            );

            return new PDO(
                $dsn,
                DatabaseConfig::USER,
                DatabaseConfig::PASSWORD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }
}
