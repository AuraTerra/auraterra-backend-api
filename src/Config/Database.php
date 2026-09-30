<?php
declare(strict_types=1);

namespace Src\Config;

use PDO;
use PDOException;

class Database {
    public static function getConnection(): PDO {
        $host    = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $db      = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'auraterra_db';
        $user    = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $pass    = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
        $charset = $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error de conexión a la base de datos'
            ]);
            exit;
        }
    }
}