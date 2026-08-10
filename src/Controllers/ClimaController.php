<?php
declare(strict_types=1);

namespace Src\Controllers;

class ClimaController 
{
    private string $apiKey = '3dbd3ceab1f4f0c1727abc805e731d13'; 

    //Retorna las condiciones climáticas del momento.
    public function handleClimaActual(): void 
    {
        header('Content-Type: application/json');
        
        $ciudad = $_GET['ciudad'] ?? null;
        $lat = $_GET['lat'] ?? null;
        $lon = $_GET['lon'] ?? null;

    
        if ($lat !== null && $lon !== null && is_numeric($lat) && is_numeric($lon)) {
            $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$this->apiKey}&units=metric&lang=es";
        } elseif ($ciudad !== null && trim($ciudad) !== '') {
            $partes = explode(',', $ciudad);
            $ciudadLimpia = trim($partes[0]);
            $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($ciudadLimpia) . "&appid={$this->apiKey}&units=metric&lang=es";
        } else {
            $url = "https://api.openweathermap.org/data/2.5/weather?q=Crespo&appid={$this->apiKey}&units=metric&lang=es";
        }

        //Alm lo que dice OpenWeather en $response. @ evita que PHP imprima una advertencia horrible si el servidor se cae
        $response = @file_get_contents($url);
        
        if ($response === false) {
            echo json_encode([
                "ok" => false, 
                "error" => "No se pudo obtener respuesta del servicio meteorológico externo."
            ]);
            exit;
        }

        $data = json_decode($response, true);
        if (!isset($data['main'])) {
            echo json_encode([
                "ok" => false, 
                "error" => "La localidad ingresada no pudo ser procesada por OpenWeather."
            ]);
            exit;
        }

        $resultado = [
            "ok" => true,
            "data" => [
                "ubicacion" => $ciudad ?? $data['name'], //si uso gps usa data y pone el nombre
                "temperatura" => $data['main']['temp'],
                "descripcion" => $data['weather'][0]['description'] ?? 'Despejado',
                "humedad" => $data['main']['humidity'] ?? 0,
                "viento" => $data['wind']['speed'] ?? 0,
                "timestamp" => date("d/m/Y H:i:s")
            ]
        ];

        echo json_encode($resultado);
        exit;
    }

    //Retorna la tendencia extendida de las próximas horas y días
    public function handleClimaPronostico(): void 
    {
        header('Content-Type: application/json');
        
        $ciudad = $_GET['ciudad'] ?? null;
        $lat = $_GET['lat'] ?? null;
        $lon = $_GET['lon'] ?? null;

        if ($lat !== null && $lon !== null && is_numeric($lat) && is_numeric($lon)) {
            $url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$this->apiKey}&units=metric&lang=es";
        } elseif ($ciudad !== null && trim($ciudad) !== '') {
            $partes = explode(',', $ciudad);
            $ciudadLimpia = trim($partes[0]);
            $url = "https://api.openweathermap.org/data/2.5/forecast?q=" . urlencode($ciudadLimpia) . "&appid={$this->apiKey}&units=metric&lang=es";
        } else {
            $url = "https://api.openweathermap.org/data/2.5/forecast?q=Crespo&appid={$this->apiKey}&units=metric&lang=es";
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
}