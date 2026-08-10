<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? 'Anónimo');
    $ciudadBuscada = trim($_POST['ciudad_buscada'] ?? '');
    $elemento = trim($_POST['elemento'] ?? 'Búsqueda Climática Directa');
    $coords = trim($_POST['coords'] ?? 'X: N/A, Y: N/A');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    if ($ciudadBuscada === '') {
        exit;
    }

    try {
        $pdo = new \PDO("mysql:host=localhost;dbname=auraterra_db;charset=utf8", "root", "");
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS telemetria_clicks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario VARCHAR(100),
            ip_origen VARCHAR(45),
            componente_clickeado VARCHAR(255),
            coordenadas VARCHAR(100),
            fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;");

        $stmt = $pdo->prepare("INSERT INTO telemetria_clicks (usuario, ip_origen, componente_clickeado, coordenadas) VALUES (?, ?, ?, ?)");
        $stmt->execute([$usuario, $ip, "Buscó Ciudad: " . $ciudadBuscada, $coords]);
        
        echo json_encode(["status" => "ok"]);
    } catch(\Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}