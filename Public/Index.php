<?php

require __DIR__ . '/../vendor/autoload.php';
require "../App/Core/Init.php";

$controller = isset($_GET["pg"]) ? $_GET["pg"] : "home";
$action = isset($_GET["action"]) ? $_GET["action"] : "index";


$controllerClass = ucfirst(strtolower($controller)) . "Controller";


$controllerFile = __DIR__ . "/../App/Controllers/" . $controllerClass . ".php";


if (file_exists($controllerFile)) {
    
    require_once $controllerFile;
    
    if (class_exists($controllerClass)) {
        
        $controller2 = new $controllerClass();

        if (method_exists($controller2, $action)) {

            $controller2->$action();
            
        } else {
            // Método no existe
            http_response_code(404);
            echo "Acción '$action' no encontrada";
        }
        
    } else {
        // Clase no existe
        http_response_code(500);
        echo "Error: Clase $controllerClass no encontrada";
    }
    
} else {
    // Archivo no existe - Cargar página 404
    http_response_code(404);
    require_once __DIR__ . "/../App/Controllers/HomeController.php";
    $controller = new HomeController();
    $controller->error404();
}


