<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../App/Core/Init.php';

$pg = isset($_GET['pg']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['pg']) : 'home';
$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['action']) : null;

// Nombre esperado de la clase de controlador
$controllerClass = ucfirst(strtolower($pg)) . 'Controller';
$controllerFile = __DIR__ . '/../App/Controllers/' . $controllerClass . '.php';

if ($action === null) {
    // Cargar la vista directamente cuando no hay action
    $viewFile = __DIR__ . '/../App/Views/' . strtolower($pg) . '.view.php';

    if (file_exists($viewFile)) {
        require_once $viewFile;
        exit;
    }

    // Si no existe la vista solicitada, mostrar home por defecto
    $default = __DIR__ . '/../App/Views/home.view.php';
    if (file_exists($default)) {
        require_once $default;
        exit;
    }

    // Fallback: mensaje simple si falta la vista
    http_response_code(404);
    echo 'Página no encontrada.';
    exit;
}

// Si se solicitó una action, intentar delegar al controlador correspondiente
if (file_exists($controllerFile)) {
    require_once $controllerFile;

    if (class_exists($controllerClass)) {
        $controllerInstance = new $controllerClass();
        
        if (method_exists($controllerInstance, $action)) {
            $controllerInstance->$action();
        }
    } else {
        echo "Error: Clase $controllerClass no encontrada";
    }
} else {
    require_once __DIR__ . "/../App/Controllers/HomeController.php";
}


