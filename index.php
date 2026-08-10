<?php
declare(strict_types=1);

// 🌐 CONFIGURACIÓN CORS (Permite peticiones desde la Web y la App Móvil)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

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
    private string $storageDir; private int $maxRequests; private int $windowSeconds; private int $blockDuration;
    public function __construct(string $storageDir, int $maxRequests = 8, int $windowSeconds = 10, int $blockDuration = 60) {
        $this->storageDir = rtrim($storageDir, '/'); $this->maxRequests = $maxRequests; $this->windowSeconds = $windowSeconds; $this->blockDuration = $blockDuration;
        if (!is_dir($this->storageDir)) { mkdir($this->storageDir, 0755, true); }
    }
    public function getClientIP(): string {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]); }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    public function isBlocked(string $ip): bool {
        $blockFile = $this->storageDir . '/blocked_' . md5($ip) . '.json';
        if (!file_exists($blockFile)) return false;
        $data = json_decode(file_get_contents($blockFile), true);
        if ($data['blocked_until'] > time()) return true;
        @unlink($blockFile); return false;
    }
    public function check(string $ip): bool {
        if ($this->isBlocked($ip)) return false;
        $logFile = $this->storageDir . '/log_' . md5($ip) . '.json'; $now = time(); $windowStart = $now - $this->windowSeconds;
        $log = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : ['requests' => []];
        $log['requests'] = array_filter($log['requests'], fn($ts) => $ts > $windowStart);
        if (count($log['requests']) >= $this->maxRequests) {
            $blockData = ['ip' => $ip, 'blocked_at' => $now, 'blocked_until' => $now + $this->blockDuration];
            file_put_contents($this->storageDir . '/blocked_' . md5($ip) . '.json', json_encode($blockData, JSON_PRETTY_PRINT));
            @unlink($logFile); return false;
        }
        $log['requests'][] = $now; file_put_contents($logFile, json_encode($log)); return true;
    }
}

$limiter = new RateLimiter($dirAlmacenamientoLimiter, 8, 10, 60);
$clientIP = $limiter->getClientIP();

$uri = $_SERVER['REQUEST_URI'] ?? '/'; //Le saca lo del error y le deja la url limpia de nuevo
$path = parse_url($uri, PHP_URL_PATH);
$path = str_replace(['/auraTerraMayo/public', '/auraTerraMayo'], '', $path);
$path = '/' . ltrim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Interceptor perimetral contra Bots y Cuentas de usuarios suspendidas
if (!$limiter->check($clientIP) || isset($_GET['error_suspension_manual']) || (isset($_SESSION['user_estado']) && $_SESSION['user_estado'] === 'suspendido')) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Acceso Restringido - AuraTerra</title>
        <style>
            body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
            .card-suspension { background: white; padding: 45px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.06); text-align: center; max-width: 480px; border-top: 6px solid #e53e3e; }
            h1 { color: #e53e3e; font-size: 1.7rem; margin-bottom: 12px; font-weight: 800; }
            p { color: #4a5568; font-size: 1.05rem; line-height: 1.6; margin-bottom: 20px; }
            .contacto-soporte { background: #fff5f5; color: #c53030; padding: 14px; border-radius: 8px; font-weight: bold; font-size: 1rem; border: 1px solid #fed7d7; }
        </style>
    </head>
    <body>
        <div class="card-suspension">
            <h1>🛡️ Cuenta Temporalmente Suspendida</h1>
            <p>El sistema de seguridad de AuraTerra ha bloqueado este acceso por detectar comportamiento automatizado de ráfagas (Bot) o inactividad prolongada mayor a 3 meses.</p>
            <div class="contacto-soporte">Soporte Técnico: AuraTerraClima@hotmail.com</div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$authController = new \Src\Controllers\AuthController();
$climaController = new \Src\Controllers\ClimaController();

if ($method === 'GET' && ($path === '/' || $path === '/index.php' || $path === '')) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>AuraTerra - Infraestructura Climática</title>
        <style>
            * { box-sizing: border-box; margin:0; padding:0; font-family: 'Segoe UI', sans-serif; }
            body { background: linear-gradient(135deg, #f4f7f6 0%, #e9eff1 100%); color: #2d3748; line-height: 1.6; scroll-behavior: smooth; }
            .hero { text-align: center; padding: 60px 20px; background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
            .logo-header-clean { background-color: #2c3e50; color: #ffffff; font-weight: 900; font-size: 2.2rem; padding: 12px 28px; border-radius: 10px; display: inline-block; margin-bottom: 20px; letter-spacing: 1px; }
            .logo-header-clean span { color: #27ae60; }
            .hero p { font-size: 1.15rem; color: #4a5568; max-width: 700px; margin: 0 auto 10px; }
            .actions-row { display: flex; gap: 15px; justify-content: center; margin-top: 15px; }
            .btn { padding: 14px 32px; border-radius: 8px; font-weight: bold; text-decoration: none; font-size: 1.05rem; transition: transform 0.2s; display: inline-flex; align-items: center; gap: 8px; }
            .btn:hover { transform: translateY(-2px); }
            .btn-primary { background: #3182ce; color: white; }
            .btn-secondary { background: #27ae60; color: white; }
            .btn-jump-home { background: none; border: none; color: #3182ce; font-weight: bold; font-size: 1.05rem; cursor: pointer; margin-top: 15px; outline: none; }
            .section { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
            .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 25px; }
            .card { background: white; padding: 30px; border-radius: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; border-top: 4px solid #3182ce; }
            .card h3 { color: #2b6cb0; margin-bottom: 12px; font-size: 1.3rem; }
            .filosofia-box { background: linear-gradient(135deg, #ebf8ff 0%, #fff5f5 100%); padding: 35px; border-radius: 14px; border-left: 6px solid #3182ce; }
            footer { text-align: center; padding: 30px; color: #a0aec0; font-size: 0.95rem; border-top: 1px solid #e2e8f0; margin-top: 50px; background: white; }
        </style>
    </head>
    <body>
        <div class="hero">
            <div class="logo-header-clean">Aura<span>Terra</span></div>
            <p style="font-style: italic; color:#718096; font-size: 1.2rem;">"Los 21gr del timo que acompañan el éxito de la organización de tu actividad al aire libre"</p>
            <div class="actions-row">
                <a href="/auraTerraMayo/login" class="btn btn-primary">Iniciar Sesión 👤</a>
                <a href="/auraTerraMayo/register" class="btn btn-secondary">Registrarse 🌾</a>
            </div>
            <button class="btn-jump-home" id="btnSaltarFilosofiaHome">Descubrir los secretos de AuraTerra 🔽</button>
        </div>
        <div class="section" id="seccionFilosofiaDescubrir">
            <div class="filosofia-box">
                <h3 style="color:#2c5282; margin-bottom:12px;">✨ El Secreto detrás de nuestro Eslogan: El Timo, el Aura y la Felicidad</h3>
                <p style="font-size:1.1rem; color:#2d3748; text-align:justify; line-height: 1.6;">
                    En la antigua medicina, el alma pesa exactamente 21 gramos, y el timo es el centro operativo de la felicidad. AuraTerra unifica la atmósfera terrestre con tu paz interior (Aura), asegurando que el clima juegue a tu favor para mantener tus actividades a salvo.
                </p>
            </div>
        </div>
        <div class="section">
            <div class="grid">
                <div class="card" style="border-top-color: #38a169;">
                    <h3>🌾 AuraTerra Agroclimatológica</h3>
                    <p>Mapeo perimetral para pulverizaciones legales en Entre Ríos, resguardando la implantación de Trigo o Arveja.</p>
                </div>
                <div class="card" style="border-top-color: #3182ce;">
                    <h3>🎪 AuraTerra Events</h3>
                    <p>Predicción logística a 72 horas para carpas, sonido exterior y cálculo preciso de la Hora Dorada lumínica.</p>
                </div>
            </div>
        </div>
        <footer>Folmer, Javier | Gareis, Soledad | Godoy, Tamara &copy; 2026</footer>
        <script>
            document.getElementById('btnSaltarFilosofiaHome').addEventListener('click', function() {
                document.getElementById('seccionFilosofiaDescubrir').scrollIntoView({ behavior: 'smooth' });
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

if ($path === '/registrar_click') { require_once __DIR__ . '/registrar_click.php'; }
elseif ($path === '/login') { $method === 'POST' ? $authController->handleLoginPost() : $authController->handleLoginGet(); }
elseif ($path === '/register') { $method === 'POST' ? $authController->handleOpenRegisterPost() : $authController->handleRegisterGet(); }
elseif ($path === '/logout') { $authController->handleLogout(); }
elseif ($path === '/clima/actual') { $climaController->handleClimaActual(); }
elseif ($path === '/clima/pronostico') { $climaController->handleClimaPronostico(); }
else { require_once __DIR__ . '/dashboard.php'; exit; }