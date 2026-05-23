<?php

namespace App\Database;

use Dotenv\Dotenv;
use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;

    private function __construct()
    {
        
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = 'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=utf8mb4';
                self::$instance = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new PDOException('Database connection failed: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
