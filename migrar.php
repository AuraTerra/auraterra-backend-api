<?php
declare(strict_types=1);

$host = 'localhost';
$db   = 'auraterra_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Modo de error: Excepciones
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Modo de obtención de datos: Asociativo
        PDO::ATTR_EMULATE_PREPARES   => false, //Desactiva emulación de consultas preparadas
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "⚡ Conectado a la infraestructura de datos de AuraTerra...<br>";

    $sqlUsuarios = "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        rol VARCHAR(50) NOT NULL DEFAULT 'agricultor',
        estado VARCHAR(50) NOT NULL DEFAULT 'prueba',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sqlUsuarios);
    echo "✅ Tabla 'usuarios' verificada y sincronizada de forma perimetral.<br>";

    $sqlTelemetria = "CREATE TABLE IF NOT EXISTS telemetria_clicks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario VARCHAR(100) NOT NULL,
        ip_origen VARCHAR(50) NOT NULL,
        componente_clickeado VARCHAR(255) NOT NULL,
        fecha_hora DATETIME NOT NULL
    ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sqlTelemetria);
    echo "✅ Tabla 'telemetria_clicks' establecida para auditoría de interacciones.<br>";

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = 'admin@auraterra.com'");
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $passHash = password_hash('Admin123!', PASSWORD_DEFAULT);
        $stmtInsert = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES ('Administradores', 'admin@auraterra.com', ?, 'admin', 'activo')");
        $stmtInsert->execute([$passHash]);
        echo "🚀 Cuenta de rescate administrativo inyectada con éxito (`admin@auraterra.com`).<br>";
    }

    echo "<br>🎉 <b>¡Migración finalizada con éxito absoluto!</b>";

} catch (PDOException $e) {
    die("<br>❌ Error crítico en los hilos de migración: " . $e->getMessage());
}