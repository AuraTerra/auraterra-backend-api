<?php
declare(strict_types=1);

namespace Src\Repositories;

class OpenWeatherRepository implements ClimaRepositoryInterface
{
    public function consultarApiExterna(string $url): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $respuesta = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error     = curl_error($ch);
        curl_close($ch);

        if ($respuesta === false || $error) {
            return ['error' => true, 'codigo' => 500, 'mensaje' => 'Error de conexión externa: ' . $error];
        }

        if ($httpCode !== 200) {
            return ['error' => true, 'codigo' => $httpCode, 'mensaje' => 'Error externo HTTP ' . $httpCode];
        }

        $datos = json_decode((string)$respuesta, true);

        if (!$datos || (isset($datos['cod']) && $datos['cod'] != 200)) {
            return ['error' => true, 'codigo' => 500, 'mensaje' => $datos['message'] ?? 'Respuesta inválida de la API'];
        }

        return ['error' => false, 'data' => $datos];
    }
}