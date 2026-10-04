<?php
namespace App\Repositories;

interface ClimaRepositoryInterface
{
    public function obtenerClimaActual(array $params): array;
}