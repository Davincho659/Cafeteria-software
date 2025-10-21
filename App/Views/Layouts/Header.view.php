<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=esc(APP_NAME)?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
</head>
    <thead>
        <?php if($controller != "login" && $controller != "sales"): ?>
        <?php require loadView('Layouts/nav'); ?>
        <?php elseif($controller == "sales"): ?>
            <nav class="navbar navbar-expand-lg bg-body-tertiary" style="min-width:350px;">
                <a href="index.php?pg=home"><button class="btn btn-secondary ms-2"><i class="fa-solid fa-chevron-left"></i></button></a>
                <button class="btn btn-primary ms-2 ">Añadir a cliente</button>
                <button class="btn btn-secondary ms-2">Añadir a mesa</button>
            </nav>
        <?php endif; ?>

    </thead>
    <tbody>
    <div class="container-fluid" style="min-width:350px;">

    
        