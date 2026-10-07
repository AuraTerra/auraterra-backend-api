<?php
declare(strict_types=1);

namespace src\Repositories;

require_once __DIR__ . '/../Models/Usuario.php';

use src\Models\Usuario;

interface UsuarioRepositoryInterface {
    public function findByEmail(string $email): ?Usuario;
    public function create(Usuario $usuario): bool;
    public function countByEmail(string $email): int;
}