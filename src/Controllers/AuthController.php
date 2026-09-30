<?php
declare(strict_types=1); 

namespace Src\Controllers;

use Src\Services\AuthService;

class AuthController {
    private AuthService $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    private function jsonResponse(array $data, int $statusCode = 200): void {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    public function handleOpenRegisterPost(): void {
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

        $res = $this->authService->registrarUsuario($nombre, $email, $password, $rol);
        $this->jsonResponse(['status' => $res['success'] ? 'success' : 'error', 'message' => $res['message'], 'data' => $res['data'] ?? null], $res['code']);
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

        $res = $this->authService->autenticarUsuario($email, $password);
        if ($res['success']) {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['user_id']     = $res['user']['id'];
            $_SESSION['user_nombre'] = $res['user']['nombre'];
            $_SESSION['user_email']  = $res['user']['email'];
            $_SESSION['user_rol']    = $res['user']['rol'];
            $_SESSION['user_estado'] = $res['user']['estado'];

            $this->jsonResponse([
                'status'  => 'success',
                'message' => $res['message'],
                'user'    => $res['user']
            ], 200);
        }

        $this->jsonResponse([
            'status'  => 'error',
            'message' => $res['message']
        ], $res['code']);
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