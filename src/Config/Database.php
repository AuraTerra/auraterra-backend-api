<?php
declare(strict_types=1);

namespace Src\Config;

use PDO;
use PDOException;

class Database {
    public static function getConnection(): PDO {
        $host    = 'localhost';
        $db      = 'auraterra_db';
        $user    = 'root';
        $pass    = '';
        $charset = 'utf8mb4';

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
                'message' => 'Error de conexión a la base de datos local: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}