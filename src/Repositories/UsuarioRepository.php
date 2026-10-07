<?php
declare(strict_types=1);

namespace src\Repositories;

require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/UsuarioRepositoryInterface.php';

use PDO;
use src\Models\Usuario; // 👈 ESTA LÍNEA ES LA QUE FALTA

class UsuarioRepository implements UsuarioRepositoryInterface {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // 1. Método exigido por la interfaz
    public function findByEmail(string $email): ?Usuario {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Usuario(
            isset($row['id']) ? (int)$row['id'] : null,
            $row['nombre'] ?? '',
            $row['email'] ?? '',
            $row['password'] ?? '',
            $row['rol'] ?? 'agricultor',
            $row['estado'] ?? 'prueba'
        );
    }

    // 2. Método exigido por la interfaz
    public function create(Usuario $usuario): bool {
        return $this->guardar($usuario);
    }

    // 3. Método exigido por la interfaz
    public function countByEmail(string $email): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return (int)$stmt->fetchColumn();
    }

    // --- Métodos de compatibilidad adicionales ---

    public function obtenerPorEmail(string $email): ?Usuario {
        return $this->findByEmail($email);
    }

    public function guardar(Usuario $usuario): bool {
        try {
            $sql = "INSERT INTO usuarios (nombre, email, password, rol, estado, created_at) 
                    VALUES (:nombre, :email, :password, :rol, :estado, NOW())";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':nombre'   => $usuario->getNombre(),
                ':email'    => $usuario->getEmail(),
                ':password' => $usuario->getPassword(),
                ':rol'      => $usuario->getRol(),
                ':estado'   => $usuario->getEstado()
            ]);
        } catch (\PDOException $e) {
            try {
                $sqlFallback = "INSERT INTO usuarios (nombre, email, password, rol, estado) 
                                VALUES (:nombre, :email, :password, :rol, :estado)";
                $stmtFallback = $this->db->prepare($sqlFallback);
                return $stmtFallback->execute([
                    ':nombre'   => $usuario->getNombre(),
                    ':email'    => $usuario->getEmail(),
                    ':password' => $usuario->getPassword(),
                    ':rol'      => $usuario->getRol(),
                    ':estado'   => $usuario->getEstado()
                ]);
            } catch (\PDOException $ex) {
                return false;
            }
        }
    }
}