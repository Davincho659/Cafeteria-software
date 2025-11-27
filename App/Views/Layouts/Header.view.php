<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=esc(APP_NAME)?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/theme-safe.css">
    <!-- Dropzone (CDN) -->
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" />
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    
</head>
<body class="use-theme">
    <header>
        <?php if ($_GET['pg'] == 'home' || $_GET['pg'] == 'adminHome'): ?>
            <nav class="navbar navbar-expand-lg cafe-navbar" style="min-width:350px;">
                <div class="container-fluid">
                    <a class="navbar-brand brand-badge" href="index.php?pg=<?php echo $backPage; ?>">
                        <img src="assets/img/logo.jpg" alt="Logo" class="cafe-logo me-2">
                        <div>
                            <div class="name"><?=esc(APP_NAME)?></div>
                            <div class="tagline text-muted">La Casa del Pastel</div>
                        </div>
                    </a>
                    <?php
                        $backPage = 'home';
                        if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
                            $backPage = 'adminHome';
                        }
                    ?>
                    
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li class="nav-item me-2">
                                    <a class="nav-link cafe-navlink" href="index.php?pg=sales">Ventas</a>
                                </li>
                                <li class="nav-item me-3">
                                    <a class="nav-link cafe-navlink" href="index.php?pg=inventory">Inventario</a>
                                </li>
                                <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
                                <li class="nav-item me-3">
                                    <a class="nav-link" href="index.php?pg=admin">Administración</a>
                                </li>
                                <?php endif; ?>
                                <li class="nav-item me-3">
                                    <a class="nav-link" href="index.php?pg=sales">Reportes</a>
                                </li>
                                <li class="nav-item mt-2">
                                    <span class="navbar-text me-3 ">
                                        Bienvenido, <strong><?=esc($_SESSION['username'])?></strong>
                                    </span>
                                </li>
                                <li class="nav-item me-3">
                                    <a class="btn btn-outline-cafe" href="index.php?pg=login&action=logout">
                                        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item">
                                    <a class="btn btn-outline-cafe" href="index.php?pg=login">
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
                <a class="btn btn-primary ms-5 " href="index.php?pg=mesas">Ver mesas <i class="fa-solid fa-utensils"></i></a>
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

    
        