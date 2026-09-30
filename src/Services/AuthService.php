<?php
declare(strict_types=1);

namespace Src\Services;

use Src\Repositories\UsuarioRepositoryInterface;

class AuthService
{
    private UsuarioRepositoryInterface $usuarioRepo;

    public function __construct(UsuarioRepositoryInterface $usuarioRepo)
    {
        $this->usuarioRepo = $usuarioRepo;
    }

    public function registrarUsuario(string $nombre, string $email, string $password, string $rol): array
    {
        if ($this->usuarioRepo->countByEmail($email) > 0) {
            return ['success' => false, 'message' => 'El correo electrónico ya se encuentra registrado.', 'code' => 400];
        }

        $passHash = password_hash($password, PASSWORD_DEFAULT);
        $exito = $this->usuarioRepo->create([
            'nombre'   => $nombre,
            'email'    => $email,
            'password' => $passHash,
            'rol'      => $rol,
            'estado'   => 'prueba'
        ]);

        if (!$exito) {
            return ['success' => false, 'message' => 'No se pudo procesar el registro.', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => '¡Te has registrado con éxito!',
            'data'    => ['nombre' => $nombre, 'email' => $email, 'rol' => $rol],
            'code'    => 201
        ];
    }

    public function autenticarUsuario(string $email, string $password): array
    {
        $user = $this->usuarioRepo->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Credenciales incorrectas: Correo o clave inválidos.', 'code' => 401];
        }

        if ($user['estado'] === 'suspendido') {
            return ['success' => false, 'message' => 'La cuenta de usuario se encuentra suspendida.', 'code' => 403];
        }

        return [
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'user'    => [
                'id'     => $user['id'],
                'nombre' => $user['nombre'],
                'email'  => $user['email'],
                'rol'    => $user['rol'],
                'estado' => $user['estado']
            ],
            'code'    => 200
        ];
    }
}