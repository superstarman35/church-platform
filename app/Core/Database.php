<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = (string) Config::get('database.host');
        $port = (int) Config::get('database.port', 3306);
        $database = (string) Config::get('database.database');
        $charset = (string) Config::get('database.charset', 'utf8mb4');
        $username = (string) Config::get('database.username');
        $password = (string) Config::get('database.password');

        if ($database === '' || $username === '' || $password === '') {
            throw new RuntimeException('Database environment variables are not configured.');
        }

        try {
            self::$connection = new PDO(
                "mysql:host={$host};port={$port};dbname={$database};charset={$charset}",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('Unable to connect to the database.', 0, $exception);
        }

        return self::$connection;
    }

    public static function resetForTests(): void
    {
        self::$connection = null;
    }
}
