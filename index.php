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

require_once __DIR__ . '/src/Controllers/AuthController.php';
require_once __DIR__ . '/src/Controllers/ClimaController.php';

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
$path = str_replace(['/auraTerraMayo/public', '/auraTerraMayo'], '', $path);
$path = '/' . ltrim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Interceptor perimetral contra Bots y Cuentas de usuarios suspendidas
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

$authController = new \Src\Controllers\AuthController();
$climaController = new \Src\Controllers\ClimaController();

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
} else {
    http_response_code(404);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Endpoint no encontrado'
    ]);
    exit;
}