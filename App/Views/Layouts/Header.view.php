<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=esc(APP_NAME)?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/theme-safe.css">
    <link rel="stylesheet" href="assets/css/sweetalert2.min.css">
    <!-- Dropzone (CDN) 
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" />
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>-->
    
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="?pg=home">
            <img src="assets/img/logo.jpg" alt="Logo" class="cafe-logo me-2"> La casa del pastel
        </a>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                
                <!-- Home -->
                <li class="nav-item">
                    <a class="nav-link <?= ($pg ?? '') === 'home' ? 'active' : '' ?>" href="?pg=home">
                        <i class="fa-solid fa-house"></i> Inicio
                    </a>
                </li>
                
                <!-- Ventas -->
                <li class="nav-item">
                    <a class="nav-link <?= ($pg ?? '') === 'sales' ? 'active' : '' ?>" href="?pg=sales">
                        <i class="fa-solid fa-cash-register"></i> Ventas
                    </a>
                </li>
                
                <!-- Compras -->
                <li class="nav-item">
                    <a class="nav-link <?= ($pg ?? '') === 'purchases' ? 'active' : '' ?>" href="?pg=purchases">
                        <i class="fa-solid fa-shopping-cart"></i> Compras
                    </a>
                </li>
                
                <!-- Inventario -->
                <li class="nav-item">
                    <a class="nav-link <?= ($pg ?? '') === 'inventory' ? 'active' : '' ?>" href="?pg=inventory">
                        <i class="fa-solid fa-boxes-stacked"></i> Inventario
                    </a>
                </li>

                <!-- Gastos -->
                <li class="nav-item">
                    <a class="nav-link <?= ($pg ?? '') === 'expenses' ? 'active' : '' ?>" href="?pg=expenses">
                        <i class="fa-solid fa-money-bill-trend-up"></i> Gastos
                    </a>
                </li>
                
                <!-- Productos -->
                <li class="nav-item">
                    <a class="nav-link <?= ($pg ?? '') === 'product' ? 'active' : '' ?>" href="?pg=product">
                        <i class="fa-solid fa-box"></i> Productos
                    </a>
                </li>
                
                <!-- Proveedores -->
                <li class="nav-item">
                    <a class="nav-link <?= ($pg ?? '') === 'suppliers' ? 'active' : '' ?>" href="?pg=suppliers">
                        <i class="fa-solid fa-truck"></i> Proveedores
                    </a>
                </li>
                
                <!-- Reportes -->
                <li class="nav-item">
                    <a class="nav-link <?= ($pg ?? '') === 'reports' ? 'active' : '' ?>" href="?pg=reports">
                        <i class="fa-solid fa-chart-line"></i> Reportes
                    </a>
                </li>
                
            </ul>
            
            <!-- Usuario y Logout -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-user"></i> 
                        <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?>
                        <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin'): ?>
                            <span class="badge bg-warning text-dark">Admin</span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text">
                                <small class="text-muted">
                                    Sesión iniciada: <?= date('H:i', $_SESSION['login_time'] ?? time()) ?>
                                </small>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="?pg=logout">
                                <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Scripts globales (auth helper) -->
<script src="assets/js/auth-helper.js"></script>