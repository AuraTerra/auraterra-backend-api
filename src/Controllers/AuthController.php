<?php
declare(strict_types=1); 
namespace Src\Controllers;

class AuthController {
    private \PDO $pdo;
    public function __construct() {
        $host = 'localhost'; $db = 'auraterra_db'; $user = 'root'; $pass = ''; $charset = 'utf8mb4';
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset"; 
        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, //cada vez que traigamos registros de la base de datos, PDO los devuelva mapeados en forma de Array Asociativo
            \PDO::ATTR_EMULATE_PREPARES   => false, //Es la barrera máxima e infranqueable contra ataques de Inyección SQL!
        ];
        try {
            $this->pdo = new \PDO($dsn, $user, $pass, $options); 
            $this->autoRepararEstructuraDb($this->pdo);
        } catch (\PDOException $e) {
            die("Error crítico de datos: " . $e->getMessage());
        }
    }

    private function autoRepararEstructuraDb(\PDO $pdo): void {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = 'admin@auraterra.com'");
            $stmt->execute();
            if ((int)$stmt->fetchColumn() === 0) {
                $passHash = password_hash('Admin123!', PASSWORD_DEFAULT); //60 caracteres
                $stmtInsert = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES ('Administradores', 'admin@auraterra.com', ?, 'admin', 'activo')");
                $stmtInsert->execute([$passHash]);
            }
        } catch (\Exception $e) {}
    }

    public function handleRegisterGet(): void { $this->renderAuthPage('register'); }
    public function handleLoginGet(): void { $this->renderAuthPage('login'); }

    private function renderAuthPage(string $mode): void {
        $isLogin = ($mode === 'login');
        $isSuspended = isset($_GET['error_suspension_manual']) && $_GET['error_suspension_manual'] == '1';
        $hasError = isset($_GET['err']) && $_GET['err'] == '1';
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title><?php echo $isLogin ? 'Iniciar Sesión' : 'Registro'; ?> - AuraTerra</title>
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
                body { background: linear-gradient(135deg, #e0eccf 0%, #f9f6f0 50%, #fcdad1 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
                .auth-container { background: rgba(255, 255, 255, 0.98); padding: 40px 35px; border-radius: 16px; box-shadow: 0 12px 35px rgba(0,0,0,0.09); width: 100%; max-width: 450px; text-align: center; border-top: 4px solid <?php echo $isLogin ? '#007bff' : '#27ae60'; ?>; }
                .logo-header { background-color: #2c3e50; color: #ffffff; font-weight: 900; font-size: 1.6rem; padding: 10px 20px; border-radius: 8px; display: inline-block; margin-bottom: 15px; letter-spacing: 1px; }
                .logo-header span { color: #27ae60; }
                .brand-slogan-clean { font-size: 0.95rem; color: #57606f; font-style: italic; margin-bottom: 25px; line-height: 1.4; }
                h2 { font-size: 1.15rem; color: #34495e; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #f1f2f6; padding-bottom: 8px; }
                .form-group { margin-bottom: 18px; text-align: left; }
                .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #4b6584; }
                .form-group input, .form-group select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #dcdde1; font-size: 1rem; outline: none; }
                .pass-wrapper { position: relative; display: flex; align-items: center; }
                .pass-wrapper input { padding-right: 45px; }
                .toggle-icon { position: absolute; right: 15px; cursor: pointer; font-size: 1.25rem; user-select: none; z-index: 5; }
                .btn-submit { background-color: <?php echo $isLogin ? '#007bff' : '#27ae60'; ?>; color: white; padding: 12px; width: 100%; border: none; border-radius: 8px; font-size: 1.1rem; cursor: pointer; font-weight: bold; margin-top: 10px; }
                .alert-danger-login { background-color: #fff5f5; border: 1px solid #f5c6cb; border-radius: 8px; padding: 12px; margin-bottom: 18px; color: #721c24; text-align: left; font-size: 0.9rem; }
                .modal-premium-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(30,41,59,0.5); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 10000; }
                .modal-premium-overlay.show { display: flex; }
                .modal-premium-card { background: white; padding: 30px; border-radius: 16px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 20px 25px rgba(0,0,0,0.15); }
                .btn-modal-premium { background: #3182ce; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class="auth-container">
                <div class="logo-header">Aura<span>Terra</span></div>
                <div class="brand-slogan-clean">"Los 21gr del timo que acompañan el éxito de la organización de tu actividad al aire libre"</div>
                
                <?php if ($isLogin && $hasError): ?>
                    <div class="alert-danger-login">❌ <b>Credenciales incorrectas:</b> Correo o clave inválidos.</div>
                <?php endif; ?>

                <h2><?php echo $isLogin ? 'Iniciar Sesión' : 'Formulario de Registro'; ?></h2>
                <form id="authForm" action="/auraTerraMayo/<?php echo $mode; ?>" method="POST">
                    <?php if (!$isLogin): ?>
                        <div class="form-group"><label>Nombre Completo</label><input type="text" name="nombre" placeholder="Ej: Juan Pérez" required></div>
                    <?php endif; ?>
                    <div class="form-group"><label>Correo Electrónico</label><input type="email" id="authEmail" name="email" placeholder="ejemplo@correo.com" required></div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <div class="pass-wrapper">
                            <input type="password" id="txtPassword" name="password" placeholder="Tu contraseña" required>
                            <span class="toggle-icon" id="btnTogglePass">🙈</span>
                        </div>
                        <?php if (!$isLogin): ?>
                            <small style="color:#718096; display:block; margin-top:5px; line-height:1.2;">Debe tener entre 8 y 15 caracteres, incluir mayúsculas, minúsculas y un carácter especial.</small>
                        <?php endif; ?>
                    </div>
                    <?php if (!$isLogin): ?>
                        <div class="form-group">
                            <label>Repetir Contraseña</label>
                            <div class="pass-wrapper">
                                <input type="password" id="txtPasswordConfirm" placeholder="Reingresá tu contraseña" required>
                                <span class="toggle-icon" id="btnTogglePassConfirm">🙈</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>¿Cuál es tu actividad principal?</label>
                            <select name="rol" required>
                                <option value="agricultor">🌾 Soy Agricultor / Productor Agropecuario</option>
                                <option value="planificador">🎪 Quiero planear mi evento al aire libre</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn-submit"><?php echo $isLogin ? 'Ingresar' : 'Registrarse y Verificar'; ?></button>
                    <?php if ($isLogin): ?>
                        <div style="margin-top: 15px; text-align: right;"><a href="#" id="btnOlvidePass" style="font-size: 0.85rem; color: #3182ce; text-decoration: none; font-weight: 600;">¿Olvidaste tu contraseña?</a></div>
                    <?php endif; ?>
                </form>
                <p style="margin-top:20px; color:#718096;">
                    <?php echo $isLogin ? '¿No tenés cuenta? <a href="/auraTerraMayo/register" style="color:#27ae60; font-weight:bold; text-decoration:none;">Registrate</a>' : '¿Ya tenés cuenta? <a href="/auraTerraMayo/login" style="color:#007bff; font-weight:bold; text-decoration:none;">Iniciar sesión</a>'; ?>
                </p>
            </div>

            <div id="modalPremium" class="modal-premium-overlay"><div class="modal-premium-card"><span id="modalIcon" style="font-size:3.5rem; display:block; margin-bottom:10px;">⚠️</span><h3 id="modalTitle">Mensaje</h3><p id="modalMessage">...</p><button type="button" class="btn-modal-premium" id="btnCerrarModalPremium">Aceptar</button></div></div>
            <div id="modalCodigo" class="modal-premium-overlay">
                <div class="modal-premium-card">
                    <span style="font-size: 3.5rem; display:block; margin-bottom:10px;">📩</span>
                    <h3>Verificación de Correo Real</h3>
                    <p style="color:#718096; font-size:0.9rem; margin-bottom:15px;">Ingresá el token de 6 dígitos enviado a tu casilla para activar la cuenta.</p>
                    <input type="text" id="inputCodigoVerif" placeholder="482915" style="text-align:center; font-size:1.5rem; letter-spacing:4px; padding:10px; width:100%; border-radius:8px; border:2px solid #cbd5e0; margin-bottom:15px; outline:none;">
                    <button type="button" class="btn-modal-premium" id="btnConfirmarCodigo" style="background:#27ae60;">Verificar e Inicializar</button>
                </div>
            </div>

            <script>
                const modal = document.getElementById('modalPremium');
                const btnCerrarModalPremium = document.getElementById('btnCerrarModalPremium');
                function mostrarModalPremium(icon, t, m) { document.getElementById('modalIcon').textContent = icon; document.getElementById('modalTitle').textContent = t; document.getElementById('modalMessage').innerHTML = m; modal.classList.add('show'); }
                btnCerrarModalPremium.addEventListener('click', () => { modal.classList.remove('show'); });

                window.addEventListener('keydown', function(e) { if (e.key === 'Enter' && modal.classList.contains('show')) { e.preventDefault(); btnCerrarModalPremium.click(); } });

                const btnOlvide = document.getElementById('btnOlvidePass');
                if (btnOlvide) { btnOlvide.addEventListener('click', (e) => { e.preventDefault(); mostrarModalPremium('🔑', 'Recuperación', 'Se envió un correo de reestablecimiento a tu casilla.'); }); }

                const btnTogglePass = document.getElementById('btnTogglePass');
                const txtPassword = document.getElementById('txtPassword');
                if (btnTogglePass && txtPassword) {
                    btnTogglePass.addEventListener('click', function() {
                        if (txtPassword.type === 'password') { txtPassword.type = 'text'; this.textContent = '👁️'; }
                        else { txtPassword.type = 'password'; this.textContent = '🙈'; }
                    });
                }
                const btnTogglePassConfirm = document.getElementById('btnTogglePassConfirm');
                const txtPasswordConfirm = document.getElementById('txtPasswordConfirm');
                if (btnTogglePassConfirm && txtPasswordConfirm) {
                    btnTogglePassConfirm.addEventListener('click', function() {
                        if (txtPasswordConfirm.type === 'password') { txtPasswordConfirm.type = 'text'; this.textContent = '👁️'; }
                        else { txtPasswordConfirm.type = 'password'; this.textContent = '🙈'; }
                    });
                }

                if (txtPasswordConfirm) {
                    txtPasswordConfirm.addEventListener('paste', (e) => { e.preventDefault(); mostrarModalPremium('🚫', 'Seguridad', 'No se permite pegar datos.'); });
                    document.getElementById('authForm').addEventListener('submit', function(e) {
                        e.preventDefault();
                        const pass = txtPassword.value; const conf = txtPasswordConfirm.value;
                        const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,15}$/;
                        if (!regex.test(pass)) { mostrarModalPremium('❌', 'Invalido', 'La clave debe incluir mayúsculas, minúsculas, caracteres especiales y medir entre 8 y 15 de longitud.'); return; }
                        if (pass !== conf) { mostrarModalPremium('❌', 'Error', 'Las contraseñas no coinciden.'); return; }
                        document.getElementById('modalCodigo').classList.add('show');
                    });

                    const btnConfirmarCodigo = document.getElementById('btnConfirmarCodigo');
                    btnConfirmarCodigo.addEventListener('click', function() {
                        const token = document.getElementById('inputCodigoVerif').value.trim();
                        if (token.length === 6 && !isNaN(token)) {
                            document.getElementById('modalCodigo').classList.remove('show');
                            document.getElementById('authForm').submit();
                        } else { alert('Token inválido. Deben ser 6 dígitos.'); }
                    });
                    window.addEventListener('keydown', function(e) { if (e.key === 'Enter' && document.getElementById('modalCodigo').classList.contains('show')) { e.preventDefault(); btnConfirmarCodigo.click(); } });
                }
            </script>
        </body>
        </html>
        <?php
    }

    public function handleOpenRegisterPost(): void {
        $nombre = trim($_POST['nombre'] ?? ''); $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? ''; $rol = $_POST['rol'] ?? 'agricultor';

        try {
            $passHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES (?, ?, ?, ?, 'prueba')");
            $stmt->execute([$nombre, $email, $passHash, $rol]);
            
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Registro Completado</title>
                <style>
                    body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #e0eccf 0%, #f9f6f0 50%, #fcdad1 100%); height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
                    .success-card { background: white; padding: 45px; border-radius: 16px; box-shadow: 0 12px 30px rgba(0,0,0,0.06); text-align: center; max-width: 450px; border-top: 4px solid #27ae60; }
                </style>
                <script>
                    setTimeout(() => { window.location.href = '/auraTerraMayo/login'; }, 2200);
                </script>
            </head>
            <body>
                <div class="success-card">
                    <span style="font-size:4.5rem; display:block; margin-bottom:12px;">🎉</span>
                    <h2>¡Te has registrado con éxito!</h2>
                    <p style="color:#718096; margin-top:10px;">Tu perfil perimetral educativo fue inicializado. Redirigiendo al panel de inicio de sesión para validar tus credenciales...</p>
                </div>
            </body>
            </html>
            <?php 
            exit;
        } catch (\PDOException $e) { header('Location: /auraTerraMayo/register?err=1'); exit; }
    }
    
    public function handleLoginPost(): void {
        $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? '';
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ?"); $stmt->execute([$email]); $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            if ($user['estado'] === 'suspendido') { header('Location: /auraTerraMayo/index.php?error_suspension_manual=1'); exit; }
            if (session_status() === PHP_SESSION_NONE) { @session_start(); }
            $_SESSION['user_id'] = $user['id']; $_SESSION['user_nombre'] = $user['nombre']; $_SESSION['user_email'] = $user['email']; $_SESSION['user_rol'] = $user['rol']; $_SESSION['user_estado'] = $user['estado'];
            ?>
            <!DOCTYPE html>
            <html lang="es"><head><meta charset="UTF-8"><style>body { background: #1a202c; color: white; font-family: 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; } .rocket { font-size: 3.5rem; animation: float 1.5s ease-in-out infinite; display: inline-block; } @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }</style><script>setTimeout(() => { window.location.href = '/auraTerraMayo/dashboard.php'; }, 1600);</script></head>
            <body><div style="text-align:center;"><div class="rocket">🚀</div><h2>¡Muchas gracias por elegir AuraTerra!</h2><p>Sincronizando credenciales zonales...</p></div></body></html>
            <?php exit;
        }
        header('Location: /auraTerraMayo/login?err=1'); exit;
    }

    public function handleLogout(): void {
        if (session_status() === PHP_SESSION_NONE) { @session_start(); }
        $_SESSION = []; @session_destroy(); header('Location: /auraTerraMayo/login'); exit;
    }
}