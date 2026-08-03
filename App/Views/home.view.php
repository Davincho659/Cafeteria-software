<?php require loadView('Layouts/header'); ?>
<link rel="stylesheet" href="<?= asset('assets/css/Home.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/calculator.css') ?>">

<div class="container-fluid min-vh-100 p-4">
    <div class="container-fluid min-vh-100 mt-4">
        <div class="col-lg-12 col-md-8 mx-auto mb-4">
            <!-- El botón cambia según haya caja abierta o no: se puede entrar al
                 sistema sin abrirla y abrirla después desde aquí. -->
            <div class="position-relative mb-4" id="cajaStatusContainer" style="display: none;">
                <h1 class="mb-0 text-center">Menú Principal</h1>
                <button class="btn btn-danger btn-sm position-absolute top-0 end-0 p-2" onclick="abrirModalCerrarCaja()" id="btnCerrarCaja" style="display:none;">
                    <i class="fa-solid fa-lock"></i> Cerrar Caja
                </button>
                <button class="btn btn-success btn-sm position-absolute top-0 end-0 p-2" onclick="abrirModalAbrirCaja()" id="btnAbrirCaja" style="display:none;">
                    <i class="fa-solid fa-lock-open"></i> Abrir Caja
                </button>
            </div>
        </div>
        <?php $esAdmin = (($_SESSION['usuario_rol'] ?? '') === 'admin'); ?>

        <!-- Accesos para todo el personal -->
        <div class="row justify-content-center g-3 mb-3">
            <div class="col-6 col-md-3">
                <a href="?pg=sales" class="text-decoration-none text-dark">
                    <div class="menu-card card-productos p-5 h-100">
                        <i class="fa-solid fa-cart-shopping fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Ventas</h5>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?pg=tables" class="text-decoration-none text-dark">
                    <div class="menu-card card-mesas p-5 h-100">
                        <i class="fa-solid fa-chair fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Mesas</h5>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?pg=purchases" class="text-decoration-none text-dark">
                    <div class="menu-card card-ventas p-5 h-100">
                        <i class="fa-solid fa-basket-shopping fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Compras</h5>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?pg=product" class="text-decoration-none text-dark">
                    <div class="menu-card card-productos p-5 h-100">
                        <i class="fa-solid fa-box fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Productos</h5>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?pg=suppliers" class="text-decoration-none text-dark">
                    <div class="menu-card card-configuracion p-5 h-100">
                        <i class="fa-solid fa-truck fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Proveedores</h5>
                    </div>
                </a>
            </div>

        <?php if ($esAdmin): ?>
            <div class="col-6 col-md-3">
                <a href="?pg=dashboard" class="text-decoration-none text-dark">
                    <div class="menu-card card-clientes p-5 h-100">
                        <i class="fa-solid fa-gauge-high fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Dashboard</h5>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?pg=reports" class="text-decoration-none text-dark">
                    <div class="menu-card card-ventas p-5 h-100">
                        <i class="fa-solid fa-chart-line fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Reportes</h5>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?pg=inventory" class="text-decoration-none text-dark">
                    <div class="menu-card card-inventario p-5 h-100">
                        <i class="fa-solid fa-boxes-stacked fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Inventario</h5>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?pg=expenses" class="text-decoration-none text-dark">
                    <div class="menu-card card-inventario p-5 h-100">
                        <i class="fa-solid fa-money-bill-wave fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Gastos</h5>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?pg=settings" class="text-decoration-none text-dark">
                    <div class="menu-card card-clientes p-5 h-100">
                        <i class="fa-solid fa-gear fa-2xl" style="color: #ffffff;"></i><br>
                        <h5>Configuración</h5>
                    </div>
                </a>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL ABRIR CAJA (el mismo que aparece al iniciar sesión) -->
<div class="modal fade" id="modalAbrirCaja" tabindex="-1" aria-labelledby="modalAbrirCajaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAbrirCajaLabel">
                    <i class="fa-solid fa-cash-register"></i> Apertura de Caja
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formAbrirCajaHome">
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Ingresa el monto inicial de la caja para poder empezar a cobrar.
                    </p>
                    <div class="mb-3">
                        <label for="montoInicialHome" class="form-label">Monto Inicial</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control" id="montoInicialHome"
                                   name="montoInicial" placeholder="0" required>
                        </div>
                        <small class="form-text text-muted">Formato: 1.000.000</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnConfirmarAbrirCaja">
                        <i class="fa-solid fa-lock-open"></i> Abrir Caja
                    </button>
                </div>
            </form>
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
                                        <td><strong>Base inicial:</strong></td>
                                        <td class="text-end" id="resumenSaldoInicial">$0.00</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td><i class="fa-solid fa-money-bill-wave text-success"></i> Ventas en efectivo:</td>
                                        <td class="text-end" id="resumenVentasEfectivo">$0.00</td>
                                    </tr>
                                    <tr class="table-danger">
                                        <td><i class="fa-solid fa-arrow-down text-danger"></i> Salidas en efectivo:</td>
                                        <td class="text-end" id="resumenEgresos">$0.00</td>
                                    </tr>
                                    <tr class="table-info">
                                        <td><strong>Debe haber en el cajón:</strong></td>
                                        <td class="text-end"><strong id="resumenSaldoCalculado">$0.00</strong></td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Las transferencias NO están en el cajón: se muestran
                                 aparte para que el cajero no crea que le falta plata. -->
                            <div class="alert alert-secondary py-2 mb-0 small" id="bloqueTransferencias" style="display:none;">
                                <i class="fa-solid fa-mobile-screen"></i>
                                <strong>Transferencias (Nequi / Bancolombia):</strong>
                                <span id="resumenVentasTransferencia">$0.00</span><br>
                                <span class="text-muted">Ese dinero está en el banco, no en el cajón. No lo cuentes.</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="saldoRealCierre" class="form-label">
                            <strong>¿Cuánto contaste en el cajón?</strong>
                            <span class="text-muted d-block small">Cuenta los billetes y monedas y escribe el total</span>
                        </label>
                        <!-- readonly + click abre la calculadora táctil: el POS se usa
                             con el dedo en una pantalla sin teclado. -->
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">$</span>
                            <input type="text"
                                   class="form-control"
                                   id="saldoRealCierre"
                                   name="saldoReal"
                                   placeholder="Toca para contar"
                                   readonly
                                   style="cursor:pointer; background:#fff;"
                                   onclick="openCashCountCalc()"
                                   required>
                            <button type="button" class="btn btn-outline-secondary" onclick="openCashCountCalc()">
                                <i class="fa-solid fa-calculator"></i>
                            </button>
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

<!-- ====================================================================
     CALCULADORA TÁCTIL PARA CONTAR EL EFECTIVO DEL CIERRE
     El POS se maneja con el dedo en una pantalla táctil sin teclado, así que
     el monto contado se digita aquí. Incluye atajos por denominación de
     billete para sumar rápido lo que hay en el cajón.
     ==================================================================== -->
<div id="cashCountOverlay" class="calculator-overlay above-modal" onclick="closeCashCountCalc(event)">
    <div class="calculator-popup" onclick="event.stopPropagation()">
        <div class="text-center mb-2">
            <h5 class="mb-0">💵 Contar efectivo</h5>
            <small class="text-muted">Toca los billetes o escribe el total</small>
        </div>

        <div class="calculator-display" id="cashCountDisplay">$0</div>

        <!-- Suma rápida por denominación (pesos colombianos) -->
        <div class="calc-quick">
            <button type="button" class="calc-quick-btn" onclick="addCashCountAmount(100000)">+100.000</button>
            <button type="button" class="calc-quick-btn" onclick="addCashCountAmount(50000)">+50.000</button>
            <button type="button" class="calc-quick-btn" onclick="addCashCountAmount(20000)">+20.000</button>
            <button type="button" class="calc-quick-btn" onclick="addCashCountAmount(10000)">+10.000</button>
            <button type="button" class="calc-quick-btn" onclick="addCashCountAmount(5000)">+5.000</button>
            <button type="button" class="calc-quick-btn" onclick="addCashCountAmount(2000)">+2.000</button>
            <button type="button" class="calc-quick-btn" onclick="addCashCountAmount(1000)">+1.000</button>
            <button type="button" class="calc-quick-btn" onclick="addCashCountAmount(500)">+500</button>
            <button type="button" class="calc-quick-btn" onclick="addCashCountAmount(100)">+100</button>
        </div>

        <div class="calculator-grid">
            <button type="button" class="calc-btn" onclick="addCashCountDigit('1')">1</button>
            <button type="button" class="calc-btn" onclick="addCashCountDigit('2')">2</button>
            <button type="button" class="calc-btn" onclick="addCashCountDigit('3')">3</button>
            <button type="button" class="calc-btn" onclick="addCashCountDigit('4')">4</button>
            <button type="button" class="calc-btn" onclick="addCashCountDigit('5')">5</button>
            <button type="button" class="calc-btn" onclick="addCashCountDigit('6')">6</button>
            <button type="button" class="calc-btn" onclick="addCashCountDigit('7')">7</button>
            <button type="button" class="calc-btn" onclick="addCashCountDigit('8')">8</button>
            <button type="button" class="calc-btn" onclick="addCashCountDigit('9')">9</button>
            <button type="button" class="calc-btn zero" onclick="addCashCountDigit('0')">0</button>
            <button type="button" class="calc-btn" onclick="deleteCashCountDigit()">
                <i class="fa-solid fa-left-long fa-lg"></i>
            </button>
        </div>

        <div class="calc-actions">
            <button type="button" class="calc-action-btn borrar" onclick="clearCashCountCalc()">Borrar</button>
            <button type="button" class="calc-action-btn cancelar" onclick="closeCashCountCalc()">Cancelar</button>
            <button type="button" class="calc-action-btn confirmar" onclick="confirmCashCount()">OK</button>
        </div>
    </div>
</div>

<script src="<?= asset('assets/js/home.js') ?>"></script>

<?php require loadView('Layouts/Footer'); ?>