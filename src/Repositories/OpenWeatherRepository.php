<?php

namespace App\Repositories;

class OpenWeatherRepository implements ClimaRepositoryInterface
{
    private string $apiKey;
    private string $baseUrlActual;

    public function __construct(string $apiKey, string $baseUrlActual)
    {
        $this->apiKey = $apiKey;
        $this->baseUrlActual = $baseUrlActual;
    }

    public function obtenerClimaActual(array $params): array
    {
        // El repositorio construye la URL según las necesidades de OpenWeather
        $url = $this->baseUrlActual . '?appid=' . $this->apiKey . '&units=metric&lang=es';

        if (isset($params['lat']) && isset($params['lon'])) {
            $url .= "&lat={$params['lat']}&lon={$params['lon']}";
        } elseif (isset($params['ciudad'])) {
            $url .= "&q=" . urlencode($params['ciudad']);
        }

        return $this->consultarApiExterna($url);
    }

    private function consultarApiExterna(string $url): array
    {
        $response = @file_get_contents($url);

        if ($response === false) {
            return [
                'error' => true,
                'codigo' => 500,
                'mensaje' => 'Error al consultar el servicio meteorológico externo'
            ];
        }

        return [
            'error' => false,
            'data' => json_decode($response, true)
        ];
    }
}