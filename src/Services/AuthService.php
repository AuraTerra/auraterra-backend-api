<?php
declare(strict_types=1);

namespace src\Services;

// Carga física obligatoria del modelo para evitar "Class not found"
require_once __DIR__ . '/../Models/Usuario.php';

use src\Models\Usuario;
use src\Repositories\UsuarioRepositoryInterface;

class AuthService {
    private UsuarioRepositoryInterface $usuarioRepository;

    public function __construct(UsuarioRepositoryInterface $usuarioRepository) {
        $this->usuarioRepository = $usuarioRepository;
    }

    private function encontrarUsuario(string $email): mixed {
        /** @var mixed $repo */
        $repo = $this->usuarioRepository;

        if (method_exists($repo, 'obtenerPorEmail')) {
            return $repo->obtenerPorEmail($email);
        }
        if (method_exists($repo, 'buscarPorEmail')) {
            return $repo->buscarPorEmail($email);
        }
        if (method_exists($repo, 'findByEmail')) {
            return $repo->findByEmail($email);
        }
        return null;
    }

    public function autenticarUsuario(string $email, string $password): mixed {
        $usuario = $this->encontrarUsuario($email);

        if ($usuario === null || empty($usuario)) {
            return null; // El usuario no existe en la BD
        }

        // Obtener el hash almacenado, sea objeto o array asociativo
        $hashAlmacenado = '';
        if (is_object($usuario) && method_exists($usuario, 'getPassword')) {
            $hashAlmacenado = $usuario->getPassword();
        } elseif (is_array($usuario) && isset($usuario['password'])) {
            $hashAlmacenado = $usuario['password'];
        }

        // Validación estricta con Bcrypt
        if (!empty($hashAlmacenado) && password_verify($password, $hashAlmacenado)) {
            // Si vino como array asociativo desde PDO::FETCH_ASSOC, lo convertimos a la entidad Usuario
            if (is_array($usuario)) {
                return new Usuario(
                    isset($usuario['id']) ? (int)$usuario['id'] : null,
                    $usuario['nombre'] ?? 'Usuario',
                    $usuario['email'] ?? $email,
                    $usuario['password'] ?? '',
                    $usuario['rol'] ?? 'agricultor',
                    $usuario['estado'] ?? 'prueba'
                );
            }
            return $usuario;
        }

        return null;
    }

    public function registrarUsuario(string $nombre, string $email, string $password, string $rol = 'agricultor'): array {
        $existente = $this->encontrarUsuario($email);
        if ($existente !== null) {
            return [
                'success' => false,
                'message' => 'El correo electrónico ya se encuentra registrado.',
                'code'    => 409
            ];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $usuario = new Usuario(null, $nombre, $email, $passwordHash, $rol, 'prueba');

        /** @var mixed $repo */
        $repo = $this->usuarioRepository;
        $guardado = false;

        if (method_exists($repo, 'guardar')) {
            $guardado = (bool)$repo->guardar($usuario);
        } elseif (method_exists($repo, 'crear')) {
            $guardado = (bool)$repo->crear($usuario);
        } elseif (method_exists($repo, 'save')) {
            $guardado = (bool)$repo->save($usuario);
        }

        if ($guardado) {
            return [
                'success' => true,
                'message' => 'Usuario registrado exitosamente en período de prueba.',
                'code'    => 201,
                'data'    => [
                    'nombre' => $nombre,
                    'email'  => $email,
                    'rol'    => $rol,
                    'estado' => 'prueba'
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'No se pudo guardar el usuario en la base de datos.',
            'code'    => 500
        ];
    }
}