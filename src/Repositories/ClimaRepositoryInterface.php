<?php
declare(strict_types=1);

namespace Src\Repositories;

interface ClimaRepositoryInterface
{
    public function consultarApiExterna(string $url): array;
}