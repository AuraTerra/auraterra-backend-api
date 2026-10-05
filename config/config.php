<?php
return [
    'api_keys' => [
        'openweather' => '3dbd3ceab1f4f0c1727abc805e731d13',
        'weatherapi'  => '7d29ff90718f425686c190703260410',
        'tomorrow'    => 'F8ET2u6glBi7KWC47epo3wIuLL7KRkc0',
    ], 
    'api_urls' => [
        'openweather'          => 'https://api.openweathermap.org/data/2.5/weather',
        'openweather_forecast' => 'https://api.openweathermap.org/data/2.5/forecast',
        'weatherapi'           => 'http://api.weatherapi.com/v1/current.json',
        'tomorrow'             => 'https://api.tomorrow.io/v4/weather/realtime',
    ],
    'database' => [
        'driver'    => 'mysql',
        'host'      => 'localhost',
        'database'  => 'auraterra_db',
        'username'  => 'root',      // Usuario por defecto de XAMPP
        'password'  => '',          // Clave vacía por defecto de XAMPP
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ]
];