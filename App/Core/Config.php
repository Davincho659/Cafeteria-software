<?php
namespace App\Core;

defined('ROOT_PATH') || define('ROOT_PATH', defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(dirname(__DIR__)));
defined('APP_PATH') || define('APP_PATH', ROOT_PATH . '/App');
define('APP_NAME', 'Cafeteria');

$isInfinityFree = isset($_SERVER['DOCUMENT_ROOT'])
	&& strpos((string) $_SERVER['DOCUMENT_ROOT'], 'infinityfree.com') !== false;

$defaultHost = $isInfinityFree ? '' : '127.0.0.1';
$defaultName = $isInfinityFree ? '' : 'cafeteria_software';
$defaultUser = $isInfinityFree ? '' : 'root';

// Configuracion de base de datos (permite override por variables de entorno)
defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: $defaultHost);
defined('DB_PORT') || define('DB_PORT', getenv('DB_PORT') ?: '3306');
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: $defaultName);
defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: $defaultUser);
defined('DB_PASS') || define('DB_PASS', getenv('DB_PASS') ?: '');
defined('DB_CHARSET') || define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
defined('DB_PERSISTENT') || define('DB_PERSISTENT', filter_var(getenv('DB_PERSISTENT') ?: 'false', FILTER_VALIDATE_BOOLEAN));
