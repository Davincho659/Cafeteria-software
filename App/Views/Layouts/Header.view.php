<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=esc(APP_NAME)?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <!-- Dropzone (CDN) -->
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" />
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    
</head>
<body>
    <header>
        <?php if ($_GET['pg'] == 'home' || $_GET['pg'] == 'adminHome'): ?>
            <nav class="navbar navbar-expand-lg bg-body-tertiary" style="min-width:350px; height:80px;">
                <div class="container-fluid">
                    <img src="assets/img/coffee.jpg" alt="Logo" width="70" height="60" class="me-2">
                    <?php
                        
                        $backPage = 'home';
                        if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
                            $backPage = 'adminHome';
                        }
                    ?>
                    <a class="navbar-brand" href="index.php?pg=<?php echo $backPage; ?>"><h4><?=esc(APP_NAME)?></h4></a>
                    
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?pg=sales">Ventas</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?pg=inventory">Inventario</a>
                                </li>
                                <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?pg=admin">Administración</a>
                                </li>
                                <?php endif; ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?pg=sales">Reportes</a>
                                </li>
                                <li class="nav-item">
                                    <span class="navbar-text me-3">
                                        Bienvenido, <strong><?=esc($_SESSION['username'])?></strong>
                                    </span>
                                </li>
                                <li class="nav-item">
                                    <a class="btn btn-outline-danger btn-sm" href="index.php?pg=login&action=logout">
                                        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item">
                                    <a class="btn btn-outline-primary btn-sm" href="index.php?pg=login">
                                        <i class="fas fa-sign-in-alt"></i> Ingresar
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </nav>
        <?php elseif($_GET['pg'] == 'sales'): ?>
            <nav class="navbar navbar-expand-lg bg-body-tertiary" style="min-width:350px;">
                <?php
                    
                    $backPage = 'home';
                    if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
                        $backPage = 'adminHome';
                    }
                ?>
                <a href="index.php?pg=<?php echo $backPage; ?>"><button class="btn btn-secondary ms-2"><i class="fa-solid fa-chevron-left"></i></button></a>
                <button class="btn btn-primary ms-2 ">Añadir a cliente</button>
                <button class="btn btn-secondary ms-2">Añadir a mesa</button>
            </nav>
        <?php elseif($_GET['pg'] == 'admin'): ?>
            <nav class="navbar navbar-expand-lg bg-body-tertiary" style="min-width:350px;">
                <?php
                    
                    $backPage = 'home';
                    if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
                        $backPage = 'adminHome';
                    }
                ?>
                <a href="index.php?pg=<?php echo $backPage; ?>"><button class="btn btn-secondary ms-2"><i class="fa-solid fa-chevron-left"></i></button></a>
            </nav>
        <?php endif; ?>
    </header>
</body>
</html>

<!-- AQUÍ COMIENZA EL CONTENIDO PRINCIPAL -->
<div class="container-fluid" style="min-width:350px;">

    
        