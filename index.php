<?php
declare(strict_types=1);

// 🌐 CONFIGURACIÓN CORS (Permite peticiones desde Web y Mobile)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
} 

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$dirAlmacenamientoLimiter = __DIR__ . '/storage/rate_limiter';

// Inclusión de componentes
require_once __DIR__ . '/src/Config/Database.php';
require_once __DIR__ . '/src/Models/Usuario.php'; // 👈 Aseguramos que la clase siempre exista en memoria
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
$climaController = new \Src\Controllers\ClimaController();

class RateLimiter {
    private string $storageDir; 
    private int $maxRequests; 
    private int $windowSeconds; 
    private int $blockDuration;

    public function __construct(string $storageDir, int $maxRequests = 50, int $windowSeconds = 10, int $blockDuration = 60) {
        $this->storageDir = rtrim($storageDir, '/'); 
        $this->maxRequests = $maxRequests; 
        $this->windowSeconds = $windowSeconds; 
        $this->blockDuration = $blockDuration;
        if (!is_dir($this->storageDir)) { 
            @mkdir($this->storageDir, 0777, true); 
        }
    }

    public function getClientIP(): string {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { 
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]); 
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function check(string $ip): bool {
        if (!is_writable($this->storageDir)) return true;
        $logFile = $this->storageDir . '/log_' . md5($ip) . '.json'; 
        $now = time(); 
        $windowStart = $now - $this->windowSeconds;
        $log = file_exists($logFile) ? @json_decode(@file_get_contents($logFile), true) : ['requests' => []];
        if (!is_array($log) || !isset($log['requests'])) $log = ['requests' => []];
        $log['requests'] = array_filter($log['requests'], fn($ts) => $ts > $windowStart);
        
        if (count($log['requests']) >= $this->maxRequests) {
            return false;
        }
        
        $log['requests'][] = $now; 
        @file_put_contents($logFile, json_encode($log)); 
        return true;
    }
}

$limiter = new RateLimiter($dirAlmacenamientoLimiter, 50, 10, 60);
$clientIP = $limiter->getClientIP();

$rawPath = $_GET['ruta'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = str_replace(['/auraterra-backend-api/index.php', '/auraterra-backend-api', '/index.php'], '', $rawPath);
$path = '/' . ltrim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Interceptor perimetral
if (!$limiter->check($clientIP) || isset($_GET['error_suspension_manual']) || (isset($_SESSION['user_estado']) && $_SESSION['user_estado'] === 'suspendido')) {
    http_response_code(429);
    echo json_encode([
        'status'  => 'error',
        'code'    => 429,
        'message' => 'Acceso Restringido: Demasiadas solicitudes o cuenta suspendida.'
    ]);
    exit;
}

// 🚦 Enrutador REST API
if ($path === '/' || $path === '') {
    http_response_code(200);
    echo json_encode([
        'status'  => 'online',
        'service' => 'AuraTerra Backend API (Local)',
        'version' => '1.0.0'
    ]);
    exit;
}

if ($path === '/login') {
    if ($method === 'POST') {
        $authController->handleLoginPost();
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    }
} elseif ($path === '/register') {
    if ($method === 'POST') {
        $authController->handleOpenRegisterPost();
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    }
} elseif ($path === '/logout') {
    $authController->handleLogout();
} elseif ($path === '/clima/actual') {
    $climaController->handleClimaActual();
} elseif ($path === '/clima/pronostico') {
    $climaController->handleClimaPronostico();
} elseif ($path === '/registrar_click') {
    if ($method === 'POST') {
        $componente = trim($_POST['componente'] ?? 'Acción');
        $usuario = $_SESSION['user_nombre'] ?? 'Usuario Local';
        try {
            $stmt = $pdo->prepare("INSERT INTO telemetria_clicks (usuario, ip_origen, componente_clickeado, fecha_hora) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$usuario, $clientIP, $componente]);
            echo json_encode(['success' => true, 'status' => 'ok']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
} elseif ($path === '/admin/telemetria') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        try {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS estado VARCHAR(50) DEFAULT 'prueba'");
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        } catch (\Exception $ignored) {}

        // Suspender cuentas de prueba de más de 7 días
        try {
            $pdo->exec("UPDATE usuarios SET estado = 'suspendido' WHERE estado = 'prueba' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
        } catch (\Exception $ignored) {}

        // Ranking (Top 15)
        $stmtRanking = $pdo->query("SELECT componente_clickeado, COUNT(*) as total FROM telemetria_clicks GROUP BY componente_clickeado ORDER BY total DESC LIMIT 15");
        $ranking = $stmtRanking ? ($stmtRanking->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        // Últimos 50 eventos
        $stmtClicks = $pdo->query("SELECT usuario, componente_clickeado, fecha_hora FROM telemetria_clicks ORDER BY id DESC LIMIT 50");
        $ultimos = $stmtClicks ? ($stmtClicks->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        // Usuarios y cálculo de prueba
        $stmtUsuarios = $pdo->query("SELECT id, nombre, email, rol, estado, created_at FROM usuarios ORDER BY id DESC");
        $filasUsuarios = $stmtUsuarios ? ($stmtUsuarios->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

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
    echo json_encode(['status' => 'error', 'message' => 'Ruta no encontrada: ' . $path]);
    exit;
}