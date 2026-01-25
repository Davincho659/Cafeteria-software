<?php require loadView('Layouts/header'); ?>
<link rel="stylesheet" href="assets/css/home.css">

<div class="container-fluid min-vh-100 p-4">
    <div class="container-fluid min-vh-100 mt-4">
        <div class="col-lg-12 col-md-8 mx-auto mb-4">
            <div class="position-relative mb-4" id="cajaStatusContainer" style="display: none;">
                <h1 class="mb-0 text-center">Menú Principal</h1>
                <button class="btn btn-danger btn-sm position-absolute top-0 end-0 p-2" onclick="abrirModalCerrarCaja()" id="btnCerrarCaja">
                    <i class="fa-solid fa-lock"></i> Cerrar Caja
                </button>
            </div>
        </div>
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
                <div class="col-md-3">
                    <a href="?pg=tables" class="text-decoration-none text-dark">
                    <div class="menu-card card-mesas p-5 h-100">
                        <i class="fa-solid fa-chair fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Mesas</h5>
                    </div>
                    </a>
            </div>
    </div>
</div>

<!-- MODAL CERRAR CAJA -->
<div class="modal fade" id="modalCerrarCaja" tabindex="-1" aria-labelledby="modalCerrarCajaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalCerrarCajaLabel">
                    <i class="fa-solid fa-lock"></i> Cerrar Caja
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCerrarCaja">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i> 
                        <strong>¡Atención!</strong> Esta acción cerrará la caja y no podrás realizar más operaciones hasta que se abra nuevamente.
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-3 text-muted">Resumen del Día</h6>
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td><strong>Saldo Inicial:</strong></td>
                                        <td class="text-end" id="resumenSaldoInicial">$0.00</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td><i class="fa-solid fa-arrow-up text-success"></i> Ingresos (Ventas):</td>
                                        <td class="text-end" id="resumenIngresos">$0.00</td>
                                    </tr>
                                    <tr class="table-danger">
                                        <td><i class="fa-solid fa-arrow-down text-danger"></i> Egresos (Compras/Gastos):</td>
                                        <td class="text-end" id="resumenEgresos">$0.00</td>
                                    </tr>
                                    <tr class="table-info">
                                        <td><strong>Saldo Calculado:</strong></td>
                                        <td class="text-end"><strong id="resumenSaldoCalculado">$0.00</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="saldoRealCierre" class="form-label">
                            <strong>Saldo Real en Caja:</strong>
                            <span class="text-muted">(Cuenta el dinero físico)</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">$</span>
                            <input type="text" 
                                   class="form-control" 
                                   id="saldoRealCierre" 
                                   name="saldoReal" 
                                   placeholder="0" 
                                   required>
                        </div>
                    </div>

                    <div id="diferenciaCaja" class="alert" style="display: none;">
                        <strong>Diferencia:</strong> <span id="diferenciaMonto">$0.00</span>
                    </div>

                    <div class="mb-3">
                        <label for="notasCierre" class="form-label">Notas (opcional)</label>
                        <textarea class="form-control" id="notasCierre" name="notas" rows="2" placeholder="Observaciones del cierre..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmarCierre">
                        <i class="fa-solid fa-lock"></i> Confirmar Cierre
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/home.js"></script>

<?php require loadView('Layouts/Footer'); ?>