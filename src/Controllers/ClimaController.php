<?php
declare(strict_types=1);

namespace Src\Controllers;

class ClimaController 
{
    private string $openWeatherKey; 
    private string $weatherApiKey;
    private string $tomorrowKey;

    public function __construct()
    {
        // Carga dinámica de credenciales desde config/config.php
        $config = require __DIR__ . '/../../config/config.php';
        $this->openWeatherKey = $config['api_keys']['openweather'] ?? '';
        $this->weatherApiKey  = $config['api_keys']['weatherapi'] ?? '';
        $this->tomorrowKey    = $config['api_keys']['tomorrow'] ?? '';
    }

    // Retorna las condiciones climáticas del momento promediadas entre 3 APIs
    public function handleClimaActual(): void 
    {
        header('Content-Type: application/json');
        
        $ciudad = $_GET['ciudad'] ?? null;
        $lat = isset($_GET['lat']) && is_numeric($_GET['lat']) ? (float)$_GET['lat'] : null;
        $lon = isset($_GET['lon']) && is_numeric($_GET['lon']) ? (float)$_GET['lon'] : null;

        $ciudadLimpia = 'Crespo';
        if ($ciudad !== null && trim($ciudad) !== '') {
            $partes = explode(',', $ciudad);
            $ciudadLimpia = trim($partes[0]);
        }

        // 1. Consulta OpenWeatherMap
        $dataOW = $this->consultarOpenWeather($ciudadLimpia, $lat, $lon);

        $latReal = $lat ?? ($dataOW['coord']['lat'] ?? -32.029);
        $lonReal = $lon ?? ($dataOW['coord']['lon'] ?? -60.306);

        // 2. Consulta WeatherAPI
        $dataWA = $this->consultarWeatherAPI($ciudadLimpia, $latReal, $lonReal);

        // 3. Consulta Tomorrow.io
        $dataTM = $this->consultarTomorrow($latReal, $lonReal);

        // 4. Normalización y Ensamblado de variables
        $temperaturas = [];
        $humedades    = [];
        $vientos      = []; // Todo en km/h

        if ($dataOW !== null && isset($dataOW['main']['temp'])) {
            $temperaturas[] = (float)$dataOW['main']['temp'];
            $humedades[]    = (float)$dataOW['main']['humidity'];
            // m/s a km/h
            $vientos[]      = (float)$dataOW['wind']['speed'] * 3.6;
        }

        if ($dataWA !== null && isset($dataWA['current']['temp_c'])) {
            $temperaturas[] = (float)$dataWA['current']['temp_c'];
            $humedades[]    = (float)$dataWA['current']['humidity'];
            $vientos[]      = (float)$dataWA['current']['wind_kph'];
        }

        if ($dataTM !== null && isset($dataTM['data']['values']['temperature'])) {
            $temperaturas[] = (float)$dataTM['data']['values']['temperature'];
            $humedades[]    = (float)$dataTM['data']['values']['humidity'];
            // m/s a km/h
            $vientos[]      = (float)$dataTM['data']['values']['windSpeed'] * 3.6;
        }

        // Si fallaron todas las fuentes
        if (empty($temperaturas)) {
            echo json_encode([
                "ok" => false, 
                "error" => "No se pudo obtener respuesta de los servicios meteorológicos."
            ]);
            exit;
        }

        // 5. Cálculo de los promedios
        $tempPromedio    = round(array_sum($temperaturas) / count($temperaturas), 1);
        $humedadPromedio = (int)round(array_sum($humedades) / count($humedades));
        $vientoKmhProm   = round(array_sum($vientos) / count($vientos), 1);
        $vientoMsProm    = round($vientoKmhProm / 3.6, 2);

        $resultado = [
            "ok" => true,
            "data" => [
                "ubicacion"    => $ciudad ?? ($dataOW['name'] ?? $ciudadLimpia),
                "temperatura"  => $tempPromedio,
                "descripcion"  => $dataOW['weather'][0]['description'] ?? ($dataWA['current']['condition']['text'] ?? 'Despejado'),
                "humedad"      => $humedadPromedio,
                "viento"       => $vientoMsProm,
                "viento_kmh"   => $vientoKmhProm,
                "consenso"     => [
                    "fuentes_consultadas" => count($temperaturas),
                    "openweather"        => $dataOW !== null,
                    "weatherapi"         => $dataWA !== null,
                    "tomorrow"           => $dataTM !== null
                ],
                "timestamp"    => date("d/m/Y H:i:s")
            ]
        ];

        echo json_encode($resultado);
        exit;
    }

    public function handleClimaPronostico(): void 
    {
        header('Content-Type: application/json');
        
        $ciudad = $_GET['ciudad'] ?? null;
        $lat = $_GET['lat'] ?? null;
        $lon = $_GET['lon'] ?? null;

        if ($lat !== null && $lon !== null && is_numeric($lat) && is_numeric($lon)) {
            $url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$this->openWeatherKey}&units=metric&lang=es";
        } elseif ($ciudad !== null && trim($ciudad) !== '') {
            $partes = explode(',', $ciudad);
            $ciudadLimpia = trim($partes[0]);
            $url = "https://api.openweathermap.org/data/2.5/forecast?q=" . urlencode($ciudadLimpia) . "&appid={$this->openWeatherKey}&units=metric&lang=es";
        } else {
            $url = "https://api.openweathermap.org/data/2.5/forecast?q=Crespo&appid={$this->openWeatherKey}&units=metric&lang=es";
        }

        $response = @file_get_contents($url);
        
        if ($response === false) {
            echo json_encode([
                "ok" => false, 
                "error" => "No se pudo obtener el pronóstico extendido."
            ]);
            exit;
        }

        $data = json_decode($response, true);

        if (!isset($data['list'])) {
            echo json_encode([
                "ok" => false, 
                "error" => "Estructura de pronóstico no encontrada."
            ]);
            exit;
        }

        echo json_encode([
            "ok" => true,
            "data" => $data['list']
        ]);
        exit;
    }

    private function consultarOpenWeather(string $ciudad, ?float $lat, ?float $lon): ?array
    {
        $url = ($lat !== null && $lon !== null)
            ? "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$this->openWeatherKey}&units=metric&lang=es"
            : "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($ciudad) . "&appid={$this->openWeatherKey}&units=metric&lang=es";

        $res = @file_get_contents($url);
        return $res ? json_decode($res, true) : null;
    }

    private function consultarWeatherAPI(string $ciudad, float $lat, float $lon): ?array
    {
        if (empty($this->weatherApiKey)) return null;
        $url = "http://api.weatherapi.com/v1/current.json?key={$this->weatherApiKey}&q={$lat},{$lon}&lang=es";
        $res = @file_get_contents($url);
        return $res ? json_decode($res, true) : null;
    }

    private function consultarTomorrow(float $lat, float $lon): ?array
    {
        if (empty($this->tomorrowKey)) return null;
        $url = "https://api.tomorrow.io/v4/weather/realtime?location={$lat},{$lon}&apikey={$this->tomorrowKey}";
        $res = @file_get_contents($url);
        return $res ? json_decode($res, true) : null;
    }
}