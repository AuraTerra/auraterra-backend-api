<?php

namespace App\Services;

use App\Repositories\ClimaRepositoryInterface;

class ClimaService
{
    private ClimaRepositoryInterface $climaRepo;

    public function __construct(ClimaRepositoryInterface $climaRepo)
    {
        $this->climaRepo = $climaRepo;
    }

    public function obtenerActual(array $params): array
    {
        // Validar parámetros requeridos
        if (!isset($params['ciudad']) && (!isset($params['lat']) || !isset($params['lon']))) {
            return [
                'error' => true,
                'codigo' => 400,
                'mensaje' => 'Faltan parámetros requeridos (ciudad o lat/lon)'
            ];
        }

        // Delegar la obtención al repositorio sin saber nada de la URL ni de OpenWeather
        $res = $this->climaRepo->obtenerClimaActual($params);

        if (isset($res['error']) && $res['error']) {
            return $res;
        }

        return [
            'error' => false,
            'data' => $res['data'] ?? $res
        ];
    }
}