<?php
namespace Validators;

class ClimaValidator
{
    public static function validarActual(array $params): array
    {
        $errors = [];
        $data = [];
        $lat = isset($params['lat']) ? trim($params['lat']) : null;
        $lon = isset($params['lon']) ? trim($params['lon']) : null;
        $ciudad = isset($params['ciudad']) ? trim($params['ciudad']) : null;

        if (($lat === null || $lon === null) && empty($ciudad)) {
            $errors['general'] = 'Debe proporcionar lat+lon o una ciudad';
        }
        if ($lat !== null) {
            if (!is_numeric($lat)) $errors['lat'] = 'Latitud debe ser un número';
            else {
                $latFloat = (float)$lat; 
                if ($latFloat < -90 || $latFloat > 90) $errors['lat'] = 'Latitud entre -90 y 90';
                else $data['lat'] = $latFloat;
            }
        }
        if ($lon !== null) {
            if (!is_numeric($lon)) $errors['lon'] = 'Longitud debe ser un número';
            else {
                $lonFloat = (float)$lon;
                if ($lonFloat < -180 || $lonFloat > 180) $errors['lon'] = 'Longitud entre -180 y 180';
                else $data['lon'] = $lonFloat;
            }
        }
        if (!empty($ciudad)) {
            if (strlen($ciudad) > 100) $errors['ciudad'] = 'Ciudad max 100 caracteres';
            else $data['ciudad'] = htmlspecialchars($ciudad, ENT_QUOTES, 'UTF-8');
        }
        return ['valid' => empty($errors), 'errors' => $errors, 'data' => $data];
    }
}