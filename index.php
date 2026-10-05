<?php
declare(strict_types=1);

// 🌐 CONFIGURACIÓN CORS (Permite peticiones desde la Web y la App Móvil)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
} 

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$dirAlmacenamientoLimiter = __DIR__ . '/storage/rate_limiter';

// 1. Inclusión de componentes arquitectónicos
require_once __DIR__ . '/src/Config/Database.php';

require_once __DIR__ . '/src/Repositories/UsuarioRepositoryInterface.php';
require_once __DIR__ . '/src/Repositories/UsuarioRepository.php';

require_once __DIR__ . '/src/Services/AuthService.php';

require_once __DIR__ . '/src/Controllers/AuthController.php';
require_once __DIR__ . '/src/Controllers/ClimaController.php';

// 2. Conexión centralizada a la base de datos PDO
$pdo = \Src\Config\Database::getConnection();

// 3. Inyección de dependencias
$usuarioRepo    = new \Src\Repositories\UsuarioRepository($pdo);
$authService    = new \Src\Services\AuthService($usuarioRepo);
$authController = new \Src\Controllers\AuthController($authService);

// Controlador de Clima (Consenso multianálisis: OpenWeather + WeatherAPI + Tomorrow.io)
$climaController = new \Src\Controllers\ClimaController();

class RateLimiter {
    private string $storageDir; 
    private int $maxRequests; 
    private int $windowSeconds; 
    private int $blockDuration;

    public function __construct(string $storageDir, int $maxRequests = 8, int $windowSeconds = 10, int $blockDuration = 60) {
        $this->storageDir = rtrim($storageDir, '/'); 
        $this->maxRequests = $maxRequests; 
        $this->windowSeconds = $windowSeconds; 
        $this->blockDuration = $blockDuration;
        if (!is_dir($this->storageDir)) { 
            mkdir($this->storageDir, 0755, true); 
        }
    }

    public function getClientIP(): string {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { 
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]); 
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function isBlocked(string $ip): bool {
        $blockFile = $this->storageDir . '/blocked_' . md5($ip) . '.json';
        if (!file_exists($blockFile)) return false;
        $data = json_decode(file_get_contents($blockFile), true);
        if ($data['blocked_until'] > time()) return true;
        @unlink($blockFile); 
        return false;
    }

    public function check(string $ip): bool {
        if ($this->isBlocked($ip)) return false;
        $logFile = $this->storageDir . '/log_' . md5($ip) . '.json'; 
        $now = time(); 
        $windowStart = $now - $this->windowSeconds;
        $log = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : ['requests' => []];
        $log['requests'] = array_filter($log['requests'], fn($ts) => $ts > $windowStart);
        
        if (count($log['requests']) >= $this->maxRequests) {
            $blockData = ['ip' => $ip, 'blocked_at' => $now, 'blocked_until' => $now + $this->blockDuration];
            file_put_contents($this->storageDir . '/blocked_' . md5($ip) . '.json', json_encode($blockData, JSON_PRETTY_PRINT));
            @unlink($logFile); 
            return false;
        }
        
        $log['requests'][] = $now; 
        file_put_contents($logFile, json_encode($log)); 
        return true;
    }
}

$limiter = new RateLimiter($dirAlmacenamientoLimiter, 8, 10, 60);
$clientIP = $limiter->getClientIP();

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$path = str_replace(['/auraterra-backend-api', '/auraTerraMayo/public', '/auraTerraMayo'], '', $path);
$path = '/' . ltrim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Interceptor perimetral contra Bots y Cuentas suspendidas
if (!$limiter->check($clientIP) || isset($_GET['error_suspension_manual']) || (isset($_SESSION['user_estado']) && $_SESSION['user_estado'] === 'suspendido')) {
    http_response_code(429);
    echo json_encode([
        'status'  => 'error',
        'code'    => 429,
        'message' => 'Acceso Restringido: Se detectó comportamiento automatizado (Bot) o la cuenta se encuentra suspendida.',
        'soporte' => 'AuraTerraClima@hotmail.com'
    ]);
    exit;
}

// 🚦 Enrutador REST API
if ($path === '/' || $path === '/index.php' || $path === '') {
    http_response_code(200);
    echo json_encode([
        'status'  => 'online',
        'service' => 'AuraTerra Backend API',
        'version' => '1.0.0'
    ]);
    exit;
}

if ($path === '/login') {
    if ($method === 'POST') {
        $authController->handleLoginPost();
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Use POST.']);
    }
} elseif ($path === '/register') {
    if ($method === 'POST') {
        $authController->handleOpenRegisterPost();
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Use POST.']);
    }
} elseif ($path === '/logout') {
    $authController->handleLogout();
} elseif ($path === '/clima/actual') {
    $climaController->handleClimaActual();
} elseif ($path === '/clima/pronostico') {
    $climaController->handleClimaPronostico();
} elseif ($path === '/registrar_click') {
    if ($method === 'POST') {
        $componente = trim($_POST['componente'] ?? 'Acción General');
        $usuarioNombre = $_SESSION['user_nombre'] ?? 'Usuario';
        try {
            $stmt = $pdo->prepare("INSERT INTO telemetria_clicks (usuario, ip_origen, componente_clickeado, fecha_hora) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$usuarioNombre, $clientIP, $componente]);
            echo json_encode(['success' => true, 'status' => 'ok']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
} elseif ($path === '/admin/telemetria') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        // Aseguramos columnas necesarias sin interrumpir ejecución
        try {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS estado VARCHAR(50) DEFAULT 'prueba'");
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        } catch (\Exception $ignored) {}

        // 1. Suspender automáticamente cuentas de prueba con más de 7 días
        try {
            $pdo->exec("UPDATE usuarios 
                        SET estado = 'suspendido' 
                        WHERE estado = 'prueba' 
                        AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
        } catch (\Exception $ignored) {}

        // 2. Ranking de consultas más frecuentes
        $ranking = [];
        try {
            $stmtRanking = $pdo->query("SELECT componente_clickeado, COUNT(*) as total FROM telemetria_clicks GROUP BY componente_clickeado ORDER BY total DESC LIMIT 5");
            $ranking = $stmtRanking->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $ignored) {}

        // 3. Últimos eventos
        $ultimos = [];
        try {
            $stmtClicks = $pdo->query("SELECT usuario, componente_clickeado, fecha_hora FROM telemetria_clicks ORDER BY id DESC LIMIT 10");
            $ultimos = $stmtClicks->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $ignored) {}

        // 4. Listado seguro de usuarios y cálculo de días restantes
        $stmtUsuarios = $pdo->query("SELECT id, nombre, email, rol, estado, created_at FROM usuarios ORDER BY id DESC");
        $filasUsuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $usuarios = [];
        $ahora = time();
        foreach ($filasUsuarios as $u) {
            $fechaCreacion = !empty($u['created_at']) ? strtotime($u['created_at']) : $ahora;
            $diasTranscurridos = (int)floor(($ahora - $fechaCreacion) / 86400);
            $diasRestantes = max(0, 7 - $diasTranscurridos);

            $usuarios[] = [
                'id'             => (int)$u['id'],
                'nombre'         => $u['nombre'] ?? 'Sin Nombre',
                'email'          => $u['email'] ?? '',
                'rol'            => $u['rol'] ?? 'agricultor',
                'estado'         => $u['estado'] ?? 'prueba',
                'dias_restantes' => $diasRestantes
            ];
        }

        echo json_encode([
            "status"   => "ok",
            "ranking"  => $ranking,
            "ultimos"  => $ultimos,
            "usuarios" => $usuarios
        ]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;

} elseif ($path === '/admin/cambiar_estado') {
    header('Content-Type: application/json; charset=utf-8');
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Método no permitido"]);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $userId = (int)($input['user_id'] ?? 0);
    $nuevoEstado = trim($input['estado'] ?? '');

    if ($userId > 0 && in_array($nuevoEstado, ['activo', 'prueba', 'suspendido'])) {
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevoEstado, $userId]);
            echo json_encode(["status" => "ok", "message" => "Estado actualizado con éxito"]);
        } catch (\Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Datos inválidos"]);
    }
    exit;
} else {
    http_response_code(404);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Endpoint no encontrado'
    ]);
    exit;
}