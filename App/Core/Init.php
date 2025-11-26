<?php
// Inicialización global: cargar helpers, conexión y configuración
// Asegurar que la sesión esté iniciada aquí para que esté disponible en toda la app
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

require_once __DIR__ . "/Functions.php";
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/Config.php";


