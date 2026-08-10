<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Sin sesión activa']);
    exit;
} //por si un atacante intenta hacer un click sin estar logueado, no se le permite

$userId = $_SESSION['user_id'];
$usuarioNombre = $_SESSION['user_nombre'] ?? 'Usuario';
$componente = trim($_POST['componente'] ?? 'Acción General');
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

//Si el usuario hace 3 clicks en menos de 3 segundos, se considera un bot y se suspende la cuenta automáticamente
if (!isset($_SESSION['click_count'])) {
    $_SESSION['click_count'] = 1;
    $_SESSION['last_click_time'] = time();
} else {
    $currentTime = time();
    if (($currentTime - $_SESSION['last_click_time']) <= 3) {
        $_SESSION['click_count']++;
    } else {
        $_SESSION['click_count'] = 1;
    }
    $_SESSION['last_click_time'] = $currentTime;
}
if ($_SESSION['click_count'] >= 3) {
    try {
        $pdo = new \PDO("mysql:host=localhost;dbname=auraterra_db;charset=utf8", "root", "");
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("UPDATE usuarios SET estado = 'suspendido', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
        
        $_SESSION = [];
        session_destroy();
        
        http_response_code(429);
        echo json_encode(['status' => 'suspended', 'message' => 'Protección Anti-Bot activada de forma física.']);
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

//Si no es boot
try {
    $pdo = new \PDO("mysql:host=localhost;dbname=auraterra_db;charset=utf8", "root", "");
    $stmt = $pdo->prepare("INSERT INTO telemetria_clicks (usuario, ip_origen, componente_clickeado, fecha_hora) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$usuarioNombre, $ip, $componente]);
    
    echo json_encode(['success' => true, 'status' => 'ok']);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;