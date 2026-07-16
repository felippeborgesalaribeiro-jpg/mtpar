<?php

class Database
{
    private static ?PDO $connection = null;
    private static ?string $pathOverride = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $dbPath = self::$pathOverride ?? __DIR__ . '/../../database/mtpar.sqlite';

            self::$connection = new PDO('sqlite:' . $dbPath);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->exec('PRAGMA foreign_keys = ON');
        }

        return self::$connection;
    }

    /**
     * Points future connections at a different SQLite file (used by the test suite
     * to keep tests off the real database) and drops any open connection.
     */
    public static function usePath(string $path): void
    {
        self::$pathOverride = $path;
        self::$connection = null;
    }
}