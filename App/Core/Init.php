<?php
// Inicialización global: cargar helpers, conexión y configuración

// Zona horaria del negocio. Va aquí (y no solo en Index.php) para que cualquier
// script que use el núcleo — cron de respaldos, reportes por consola — escriba
// y muestre las horas de Colombia. El php.ini del servidor suele traer otra.
date_default_timezone_set('America/Bogota');

// -------------------------------------------------------------------------
// Entorno y manejo de errores.	
// En producción (APP_ENV=production, se define en el servidor/Docker) NO se
// muestran errores al usuario: se registran en un archivo de log. Mostrar
// errores crudos en internet filtra rutas y detalles internos.
// En local (XAMPP) se muestran para poder depurar.
// -------------------------------------------------------------------------
$__isProd = getenv('APP_ENV') === 'production';
error_reporting(E_ALL);
if ($__isProd) {
	ini_set('display_errors', '0');
	ini_set('log_errors', '1');
	$__logDir = dirname(__DIR__, 2) . '/storage/logs';
	if (!is_dir($__logDir)) {
		@mkdir($__logDir, 0775, true);
	}
	if (is_dir($__logDir) && is_writable($__logDir)) {
		ini_set('error_log', $__logDir . '/php-error.log');
	}
} else {
	ini_set('display_errors', '1');
}

// -------------------------------------------------------------------------
// Cookie de sesión endurecida. Debe configurarse ANTES de session_start().
//  - httponly: el JavaScript no puede leer la cookie (defensa ante XSS).
//  - samesite=Lax: no se envía en peticiones cross-site (defensa ante CSRF).
//  - secure: solo por HTTPS. Se activa solo si la conexión es https para no
//    romper el desarrollo local por http.
// El POS está encendido todo el día, así que la sesión en el servidor vive
// varias horas (gc_maxlifetime) aunque la cookie sea de sesión de navegador.
// -------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
	$__secure = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
		|| (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

	// El POS vive encendido todo el turno y no se puede estar recargando ni
	// volviendo a iniciar sesión a media jornada. Por eso:
	//  - 16 h de vida de sesión, que cubre cualquier turno completo.
	//  - La cookie también dura 16 h (no muere al cerrar el navegador), así el
	//    equipo puede reiniciarse sin sacar al cajero.
	//  - Sesiones propias de la app: si el hosting comparte /tmp con otros
	//    sitios, su recolector de basura podría borrar las nuestras antes.
	$__vida = 57600; // 16 horas

	ini_set('session.use_strict_mode', '1');
	ini_set('session.gc_maxlifetime', (string) $__vida);
	ini_set('session.cookie_lifetime', (string) $__vida);

	$__sesiones = dirname(__DIR__, 2) . '/storage/sessions';
	if (!is_dir($__sesiones)) {
		@mkdir($__sesiones, 0775, true);
	}
	if (is_dir($__sesiones) && is_writable($__sesiones)) {
		session_save_path($__sesiones);
	}

	session_set_cookie_params([
		'lifetime' => $__vida,
		'path' => '/',
		'httponly' => true,
		'samesite' => 'Lax',
		'secure' => $__secure,
	]);
	session_start();
}

// Rutas base portables para entornos tipo InfinityFree/hosting compartido.
if (!defined('CORE_PATH')) {
	define('CORE_PATH', __DIR__);
}
if (!defined('APP_PATH')) {
	define('APP_PATH', dirname(CORE_PATH));
}
if (!defined('PROJECT_ROOT')) {
	define('PROJECT_ROOT', dirname(APP_PATH));
}

$publicCandidates = [
	PROJECT_ROOT . '/Public',
	PROJECT_ROOT . '/public',
	PROJECT_ROOT . '/public_html',
	dirname(PROJECT_ROOT) . '/htdocs',
	dirname(PROJECT_ROOT) . '/public_html',
];

$resolvedPublicPath = null;
foreach ($publicCandidates as $candidate) {
	if (is_dir($candidate)) {
		$resolvedPublicPath = $candidate;
		break;
	}
}

if (!defined('PUBLIC_PATH')) {
	define('PUBLIC_PATH', $resolvedPublicPath ?: (PROJECT_ROOT . '/Public'));
}
if (!defined('VIEWS_PATH')) {
	define('VIEWS_PATH', APP_PATH . '/Views');
}

require_once __DIR__ . "/Functions.php";
require_once __DIR__ . "/Conexion.php";
require_once __DIR__ . "/Config.php";
require_once __DIR__ . "/Validator.php";
require_once __DIR__ . "/Csrf.php";


