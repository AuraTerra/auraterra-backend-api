<?php
declare(strict_types=1);

namespace src\Controllers;

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
        $this->jsonResponse([
            'status'  => $res['success'] ? 'success' : 'error',
            'message' => $res['message'],
            'data'    => $res['data'] ?? null
        ], $res['code']);
    }

    public function handleLoginPost(): void {
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'El correo y la contraseña son requeridos'
            ], 400);
        }

        try {
            // Autenticación estricta delegada a AuthService
            $usuario = $this->authService->autenticarUsuario($email, $password);

            // 🛑 1. VALIDACIÓN DE CREDENCIALES: Si no coincide el correo o el hash, rechazo inmediato
            if ($usuario === null) {
                $this->jsonResponse([
                    'status'  => 'error',
                    'message' => 'Credenciales inválidas. Verifica tu correo y contraseña.'
                ], 401);
            }

            // Extracción segura de datos del modelo o array
            $idUsuario     = is_object($usuario) && method_exists($usuario, 'getId')     ? $usuario->getId()     : ($usuario['id'] ?? 0);
            $nombreUsuario = is_object($usuario) && method_exists($usuario, 'getNombre') ? $usuario->getNombre() : ($usuario['nombre'] ?? 'Usuario');
            $emailUsuario  = is_object($usuario) && method_exists($usuario, 'getEmail')  ? $usuario->getEmail()  : ($usuario['email'] ?? $email);
            $rolUsuario    = is_object($usuario) && method_exists($usuario, 'getRol')    ? $usuario->getRol()    : ($usuario['rol'] ?? 'agricultor');
            
            // Lectura de estado
            $estadoUsuario = 'prueba';
            if (is_object($usuario) && method_exists($usuario, 'getEstado')) {
                $estadoUsuario = $usuario->getEstado();
            } elseif (is_array($usuario) && isset($usuario['estado'])) {
                $estadoUsuario = $usuario['estado'];
            }

            // 🛑 2. CONTROL DE SUSPENSIÓN: Si está suspendido, no permite continuar hacia el 2FA
            if ($estadoUsuario === 'suspendido') {
                $this->jsonResponse([
                    'status'  => 'suspended',
                    'message' => 'Tu período de prueba ha expirado o tu cuenta se encuentra suspendida. Por favor, contáctanos a soporte@auraterra.com para reactivar tu plan.'
                ], 403);
            }

            // 3. PERSISTENCIA DE SESIÓN EN PHP (por compatibilidad)
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }

            $_SESSION['user_id']     = $idUsuario;
            $_SESSION['user_nombre'] = $nombreUsuario;
            $_SESSION['user_rol']    = $rolUsuario;
            $_SESSION['user_estado'] = $estadoUsuario;

            // 4. RESPUESTA EXITOSA PARA DESPLEGAR EL MODAL 2FA
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Autenticación exitosa',
                'user'    => [
                    'id'     => $idUsuario,
                    'nombre' => $nombreUsuario,
                    'email'  => $emailUsuario,
                    'rol'    => $rolUsuario,
                    'estado' => $estadoUsuario
                ]
            ], 200);

        } catch (\Throwable $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Error durante el login: ' . $e->getMessage()
            ], 500);
        }
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