<?php
declare(strict_types=1); 
namespace Src\Controllers;

class AuthController {
    private \PDO $pdo;

    public function __construct() {
        $host = 'localhost'; $db = 'auraterra_db'; $user = 'root'; $pass = ''; $charset = 'utf8mb4';
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset"; 
        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new \PDO($dsn, $user, $pass, $options); 
            $this->autoRepararEstructuraDb($this->pdo);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Error crítico de base de datos'], 500);
        }
    }

    private function autoRepararEstructuraDb(\PDO $pdo): void {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = 'admin@auraterra.com'");
            $stmt->execute();
            if ((int)$stmt->fetchColumn() === 0) {
                $passHash = password_hash('Admin123!', PASSWORD_DEFAULT);
                $stmtInsert = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES ('Administradores', 'admin@auraterra.com', ?, 'admin', 'activo')");
                $stmtInsert->execute([$passHash]);
            }
        } catch (\Exception $e) {}
    }

    /**
     * Helper centralizado para emitir respuestas únicamente en formato JSON.
     */
    private function jsonResponse(array $data, int $statusCode = 200): void {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    public function handleOpenRegisterPost(): void {
        // En un backend API REST, es preferible capturar JSON del body (o $_POST como fallback)
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $nombre   = trim($input['nombre'] ?? '');
        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $rol      = $input['rol'] ?? 'agricultor';

        if (empty($nombre) || empty($email) || empty($password)) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Todos los campos requeridos deben completarse.'
            ], 400);
        }

        try {
            $passHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES (?, ?, ?, ?, 'prueba')");
            $stmt->execute([$nombre, $email, $passHash, $rol]);
            
            $this->jsonResponse([
                'status'  => 'success',
                'message' => '¡Te has registrado con éxito!',
                'data'    => [
                    'email' => $email,
                    'nombre' => $nombre,
                    'rol' => $rol
                ]
            ], 201);
        } catch (\PDOException $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'El usuario ya existe o la solicitud no se pudo procesar.'
            ], 400);
        }
    }
    
    public function handleLoginPost(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Correo y contraseña requeridos.'
            ], 400);
        }

        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['estado'] === 'suspendido') {
                $this->jsonResponse([
                    'status'  => 'error',
                    'message' => 'La cuenta de usuario se encuentra suspendida.'
                ], 403);
            }

            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }

            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'];
            $_SESSION['user_email']  = $user['email'];
            $_SESSION['user_rol']    = $user['rol'];
            $_SESSION['user_estado'] = $user['estado'];

            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Inicio de sesión exitoso',
                'user'    => [
                    'id'     => $user['id'],
                    'nombre' => $user['nombre'],
                    'email'  => $user['email'],
                    'rol'    => $user['rol']
                ]
            ], 200);
        }

        $this->jsonResponse([
            'status'  => 'error',
            'message' => 'Credenciales incorrectas: Correo o clave inválidos.'
        ], 401);
    }

    public function handleLogout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
        @session_destroy();

        $this->jsonResponse([
            'status'  => 'success',
            'message' => 'Sesión cerrada correctamente'
        ], 200);
    }
}