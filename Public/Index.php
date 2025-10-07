<?php

require "../App/Core/init.php";

$controller = isset($_GET["pg"]) ? $_GET["pg"] : "Home";
$controller = strtolower($controller);

if (file_exists("../App/Controllers/" . $controller . "Controller.php")) {
    require("../App/Controllers/" . $controller . "Controller.php"); // <-- si es necesario cambiar por "include"
} else {

    require("../App/Controllers/HomeController.php"); // <-- si es necesario cambiar por "include"
    echo "Esa pagina no existe";
};

