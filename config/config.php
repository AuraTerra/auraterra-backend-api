<?php
return [
    'api_keys' => [
        'openweather' => '3dbd3ceab1f4f0c1727abc805e731d13',
    ], 
    'api_urls' => [
        'openweather' => 'https://api.openweathermap.org/data/2.5/weather',
        'openweather_forecast' => 'https://api.openweathermap.org/data/2.5/forecast', // La sumamos para el pronóstico extendido
    ],
    'database' => [
        'driver'    => 'mysql',
        'host'      => 'localhost',
        'database'  => 'auraterra_db',
        'username'  => 'root',      // El usuario por defecto de XAMPP
        'password'  => '',          // La clave por defecto de XAMPP (vacía)
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ]
];