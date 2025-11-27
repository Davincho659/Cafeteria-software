
<?php require loadView('Layouts/header'); ?>
<link rel="stylesheet" href="assets/css/home.css">

<div class="d-flex justify-content-center align-items-center">
    <div class="container-fluid text-center col-lg-10 col-md-6 mt-5 p-2">
        <h2 class="mb-5">Menú Principal</h2>
        <!-- Fila 1 -->
    
            <div class="row justify-content-center g-2 mb-2">
                <div class="col-md-5">
                    <a href="index.php?pg=sales" class="text-decoration-none text-dark">
                    <div class="menu-card card-productos p-5">
                        <i class="fa-solid fa-cart-shopping fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Ventas</h5>
                    </div>
                    </a>
                </div>

                <div class="col-md-2">
                    <a href="clientes.php" class="text-decoration-none text-dark">
                    <div class="menu-card card-clientes p-5">
                        <i class="fa-solid fa-user fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Clientes</h5>
                    </div>
                    </a>
                </div>

                <div class="col-md-2">
                    <a href="index.php?pg=sales" class="text-decoration-none text-dark">
                    <div class="menu-card card-ventas p-5">
                        <i class="fa-solid fa-product fa-2xl" style="color: #ffffff;"></i>
                        <h5>Productos</h5>
                    </div>
                    </a>
                </div>
            </div>

                <!-- Fila 2 -->
                <div class="row justify-content-center g-2 mb-2 ">
                <div class="col-md-3">
                    <a href="inventario.php" class="text-decoration-none text-dark">
                    <div class="menu-card card-inventario p-5">
                        <i class="fa-solid fa- fa-2xl" style="color: #ffffff;"></i>
                        <h5>Inventario</h5>
                        <p class="small">Control de stock</p>
                    </div>
                    </a>
                </div>

                <div class="col-md-3">
                    <a href="index.php?pg=admin" class="text-decoration-none text-dark">
                    <div class="menu-card card-facturas p-5">
                        <br>
                        <i class="fa-solid fa-user fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Administración</h5>
                        <p class="small">Emitir comprobantes</p>
                    </div>
                    </a>
                </div>

                <div class="col-md-3">
                    <a href="configuracion.php" class="text-decoration-none text-dark">
                    <div class="menu-card card-configuracion p-5">
                        <h5>Configuración</h5>
                        <p class="small">Ajustes del sistema</p>
                    </div>
                    </a>
                </div>
            </div>
        
    </div>
</div>


<script src="assets/js/home.js"></script>

<?php require loadView('Layouts/Footer'); ?>