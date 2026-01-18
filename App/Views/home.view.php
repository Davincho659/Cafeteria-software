<?php require loadView('Layouts/header'); ?>
<link rel="stylesheet" href="assets/css/home.css">


<div class="container-fluid  min-vh-100 p-4">
    <div class="container-fluid min-vh-100 text-center mt-5">
        <h1 class="mb-5">Menú Principal</h1>
        <!-- Fila 1 -->
    
            <div class="row justify-content-center g-2 mb-2">
                    <div class="col-md-5">
                        <a href="?pg=sales" class="text-decoration-none text-dark">
                        <div class="menu-card card-productos p-5">
                            <i class="fa-solid fa-cart-shopping fa-2xl" style="color: #ffffff;"></i><br>
                            <h5>Ventas</h5>
                        </div>
                        </a>
                    </div>

                <div class="col-md-2">
                    <a href="?pg=inventory" class="text-decoration-none text-dark">
                    <div class="menu-card card-clientes p-5">
                        <i class="fa-solid fa-boxes-stacked fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Inventario</h5>
                    </div>
                    </a>
                </div>

                <div class="col-md-2">
                    <a href="?pg=products" class="text-decoration-none text-dark">
                    <div class="menu-card card-ventas p-5">
                        <i class="fa-solid fa-box fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Productos</h5>
                    </div>
                    </a>
                </div>
            </div>

            
            <div class="row justify-content-center g-2 mb-2">
                <div class="col-md-3">
                    <a href="?pg=expenses" class="text-decoration-none text-dark">
                    <div class="menu-card card-inventario p-5 h-100">
                        <i class="fa-solid fa-money-bill-wave fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Gastos</h5>
                    </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="?pg=purchases" class="text-decoration-none text-dark">
                    <div class="menu-card card-ventas p-5">
                        <i class="fa-solid fa-basket-shopping fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Compras</h5>
                    </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="?pg=suppliers" class="text-decoration-none text-dark">
                    <div class="menu-card card-configuracion p-5 h-100">
                        
                        <i class="fa-solid fa-truck fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Proveedores</h5>
                    </div>
                    </a>
                </div>
            </div>
            <div class="row justify-content-center g-2">
                <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin'): ?>
                <div class="col-md-3">
                    <a href="?pg=reports" class="text-decoration-none text-dark">
                    <div class="menu-card card-clientes p-5 h-100">
                        <i class="fa-solid fa-chart-line fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Reportes</h5>
                    </div>
                    </a>
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <a href="?pg=setings" class="text-decoration-none text-dark">
                    <div class="menu-card card-inventario p-5 h-100">
                        <i class="fa-solid fa-gear fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Configuración</h5>
                    </div>
                    </a>
                </div>
            </div>
    </div>
</div>



<script src="assets/js/home.js"></script>

<?php require loadView('Layouts/Footer'); ?>