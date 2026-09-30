<?php
declare(strict_types=1);

namespace Src\Repositories;

interface UsuarioRepositoryInterface
{
    public function findByEmail(string $email): ?array;
    public function create(array $data): bool;
    public function countByEmail(string $email): int;
}