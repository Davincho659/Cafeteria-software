<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../App/Core/Init.php';

$pg = isset($_GET["pg"]) ? $_GET["pg"] : "home";
$action = isset($_GET["action"]) ? $_GET["action"] : null;



$controllerClass = ucfirst(strtolower($pg)) . "Controller";


$controllerFile = __DIR__ . "/../App/Controllers/" . $controllerClass . ".php";

if ($action === null) {
    // Buscar la vista directamente
    $viewFile = "../App/views/". strtolower($pg) . ".view.php";
    
    if (file_exists($viewFile)) {
        require_once $viewFile;
    } else {
        require_once "../App/views/home.view.php";;
    }
    exit; // Terminar aquí, no ejecutar más código
}


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


