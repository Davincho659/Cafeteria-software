<?php

function show($data) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

function loadView($view) {
    if(file_exists("../App/Views/{$view}.view.php")) {
        return "../App/Views/{$view}.view.php";
    } else {
        echo "Vista no encontrada";
    }
    
}

function esc($str) {
    return htmlspecialchars($str);
}