<?php
namespace Services;

use Src\Repositories\ClimaRepositoryInterface;

class ClimaService
{
    private string $apiKey;
    private string $baseUrlActual;
    private string $baseUrlForecast;
    private ClimaRepositoryInterface $climaRepo;

    public function __construct(string $apiKey, string $baseUrlActual, ClimaRepositoryInterface $climaRepo)
    {
        $this->apiKey = $apiKey;
        $this->baseUrlActual = $baseUrlActual;
        $this->baseUrlForecast = str_replace('/weather', '/forecast', $baseUrlActual);
        $this->climaRepo = $climaRepo;
    }

    public function obtenerActual(array $params): array
    {
        $url = $this->baseUrlActual . '?appid=' . $this->apiKey . '&units=metric&lang=es';
        
        if (isset($params['lat'], $params['lon'])) {
            $url .= "&lat={$params['lat']}&lon={$params['lon']}";
        } elseif (isset($params['ciudad'])) {
            $url .= "&q=" . urlencode($params['ciudad']);
        } else {
            return ['error' => true, 'codigo' => 400, 'mensaje' => 'Faltan parámetros'];
        }

        $res = $this->climaRepo->consultarApiExterna($url);
        if ($res['error']) {
            return $res;
        }

        $datos = $res['data'];
        return [
            'error' => false,
            'data' => [
                'temperatura' => $datos['main']['temp'] ?? null,
                'humedad'     => $datos['main']['humidity'] ?? null,
                'viento'      => $datos['wind']['speed'] ?? null,
                'descripcion' => $datos['weather'][0]['description'] ?? null,
                'fuente'      => 'OpenWeatherMap',
                'timestamp'   => date('Y-m-d H:i:s'),
                'ubicacion'   => $datos['name'] ?? 'Desconocida', 
            ]
        ];
    }

    public function obtenerPronostico(array $params): array
    {
        $url = $this->baseUrlForecast . '?appid=' . $this->apiKey . '&units=metric&lang=es';

        if (isset($params['lat'], $params['lon'])) {
            $url .= "&lat={$params['lat']}&lon={$params['lon']}";
        } elseif (isset($params['ciudad'])) {
            $url .= "&q=" . urlencode($params['ciudad']);
        } else {
            return ['error' => true, 'codigo' => 400, 'mensaje' => 'Faltan parámetros'];
        }

        $resultado = $this->climaRepo->consultarApiExterna($url);

        if ($resultado['error']) {
            return $resultado;
        }

        return [
            'error' => false,
            'data' => $resultado['data']['list'] ?? []
        ];
    }
}