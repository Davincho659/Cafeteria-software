
<?php require loadView('Layouts/header'); ?>
<link rel="stylesheet" href="assets/css/home.css">

<div class="d-flex justify-content-center align-items-center">
    <div class="container-fluid text-center col-lg-10 col-md-6 mt-5 p-2">
        <h2 class="mb-5">Menú Principal</h2>
        
        <!-- ESTADO DE CAJA -->
        <div class="row justify-content-center mb-4" id="cajaStatusContainer" style="display: none;">
            <div class="col-md-12">
                <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
                    <div>
                        <i class="fa-solid fa-cash-register me-2"></i>
                        <strong>Caja Activa</strong> | Saldo: <span id="saldoCajaActual">$0.00</span>
                    </div>
                    <button class="btn btn-danger btn-sm" onclick="abrirModalCerrarCaja()" id="btnCerrarCaja">
                        <i class="fa-solid fa-lock"></i> Cerrar Caja
                    </button>
                </div>
            </div>
        </div>

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
                    <a href="#" class="text-decoration-none text-dark">
                    <div class="menu-card card-clientes p-5">
                        <i class="fa-solid fa-user fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Clientes</h5>
                    </div>
                    </a>
                </div>

                <div class="col-md-2">
                    <a href="index.php?pg=spend" class="text-decoration-none text-dark">
                    <div class="menu-card card-ventas p-5">
                        <i class="fa-solid fa-comment-dollar fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Gastos</h5>
                        <p class="small">Control de gastos</p>
                    </div>
                    </a>
                </div>
            </div>

                <!-- Fila 2 -->
                <div class="row justify-content-center g-2 mb-2 ">
                <div class="col-md-3">
                    <a href="index.php?pg=inventary" class="text-decoration-none text-dark">
                    <div class="menu-card card-inventario p-5">
                        <i class="fa-solid fa- fa-2xl" style="color: #ffffff;"></i>
                        <h5>Inventario</h5>
                        <p class="small">Control de stock</p>
                    </div>
                    </a>
                </div>

                <div class="col-md-3">
                    <a href="index.php?pg=product" class="text-decoration-none text-dark">
                    <div class="menu-card card-facturas p-5">
                        <br>
                        <i class="fa-solid fa-user fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Administración</h5>
                        <p class="small">Gestión de productos</p>
                    </div>
                    </a>
                </div>

                <div class="col-md-3">
                    <a href="index.php?pg=reports&action=sales" class="text-decoration-none text-dark">
                    <div class="menu-card card-configuracion p-5">
                        <i class="fa-solid fa-chart-line fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Reportes</h5>
                        <p class="small">Entradas y movimientos</p>
                    </div>
                    </a>
                </div>
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
                            <input type="number" 
                                   class="form-control" 
                                   id="saldoRealCierre" 
                                   name="saldoReal" 
                                   step="0.01" 
                                   min="0"
                                   placeholder="0.00" 
                                   required
                                   autocomplete="off">
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