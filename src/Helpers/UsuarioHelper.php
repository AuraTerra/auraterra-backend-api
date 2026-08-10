<?php
namespace Helpers;

use Models\Usuario; 

class UsuarioHelper
{
    public static function guardarUsuario($email, $nombre, $passwordHash)
    {
        $existe = Usuario::where('email', $email)->first();
        if ($existe) {
            return false; 
        }

        $nuevo = Usuario::create([
            'email'    => $email,
            'nombre'   => $nombre,
            'password' => $passwordHash,
            'rol'      => 'usuario' 
        ]);

        return $nuevo ? true : false;
    }

    public static function autenticar($email, $password)
    {
        $usuario = Usuario::where('email', $email)->first();

        if ($usuario && password_verify($password, $usuario->password)) {
            return [
                'id'     => $usuario->id,
                'email'  => $usuario->email,
                'nombre' => $usuario->nombre
            ];
        }

        return null; // Credenciales incorrectas
    }
}