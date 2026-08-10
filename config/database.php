<?php
require_once 'C:/x/htdocs/auraTerraMayo/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule; 

$config = require __DIR__ . '/config.php'; 

$capsule = new Capsule; 

$capsule->addConnection($config['database']);

$capsule->setAsGlobal();
$capsule->bootEloquent(); 