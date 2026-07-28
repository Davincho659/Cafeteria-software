<?php require loadView('Layouts/header'); ?>
<link rel="stylesheet" href="<?= asset('assets/css/sales.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/tables-board.css') ?>">

<div class="d-flex inline-block tab-container" data-user-id="<?= $_SESSION['usuario_id']  ?>" >
    <ul class="nav nav-tabs" id="ventasTabs">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#venta1" id="">Venta 1</a>
        </li>
        <!-- botón fijo para agregar nueva pestaña; siempre debe quedar al final -->
        <li class="nav-item" id="addTabItem">
            <i id="nuevaVenta" class="fa-solid fa-plus fa-2xl" style="color: #26c418ff; padding-top: 20px; padding-left: 15px;"></i>
        </li>
        <li class="nav-item ms-auto d-flex align-items-center gap-2 me-2">
            <button class="btn btn-ver-mesas" onclick="openTablesBoard()">
                <i class="fa-solid fa-chair"></i> Ver mesas
            </button>
            <button class="btn btn-info btn-sm" onclick="openDailyReportModal()">
                <i class="fa-solid fa-file-invoice"></i> Ver facturas Hoy
            </button>
        </li>
    </ul>
</div>

<!-- ========================================== -->
<!-- TABLERO DE MESAS (navegador, solo lectura) -->
<!-- ========================================== -->
<div id="tablesBoardOverlay" class="table-overlay" onclick="if(event.target===this) closeTablesBoard()">
    <div class="board-popup" onclick="event.stopPropagation()">
        <div class="board-popup-header">
            <h2><i class="fa-solid fa-chair"></i> <span id="tablesBoardTitle">Mesas del salón</span></h2>
            <i class="fa-solid fa-circle-xmark board-close" onclick="closeTablesBoard()" title="Cerrar"></i>
        </div>
        <div class="board-popup-body">
            <div class="board-canvas" id="salesBoardCanvas"></div>
        </div>
        <div class="board-popup-footer">
            <div class="board-legend">
                <span><span class="dot dot-libre"></span> Libre</span>
                <span><span class="dot dot-ocupada"></span> Ocupada</span>
            </div>
            <small class="text-muted" id="tablesBoardHint">Toca una mesa para abrir su cuenta</small>
        </div>
    </div>
</div>


<div class="container-fluid app-root pos-layout">
  <div class="row pos-row">
        <div class="col-2 border-end mt-3 pos-col-categories" id="categorias">
            <h2 class="mt-2 mb-5">Categorías</h2>
            <nav class="categorias-nav" id="categoriasNav">
                <!-- Las categorías se cargan dinámicamente aquí -->
            </nav>
        </div>
        <div class="col-7 p-0 pos-col-products" id="productos">
            <div class="input-group mb-3 mt-3">
                <h2 class="mt-2" id="prueba">Productos</h2>
                <input type="text" class="form-control ms-4"  placeholder="Buscar" id="search" style="max-width: 700px;">
                <button class="btn btn-outline-secondary" type="submit" id="button-addon2">
                <span class="input-group-text" id="basic-addon1"><i class="fa-solid fa-magnifying-glass"></i></span></button>
            </div>
            <div class="d-flex pos-products-wrap">
                <div id="productosContainer" class="productosContainer">
                    <h5>cargando productos...</h5>
                </div>
            </div>
        </div>
        <div id="calculatorOverlay" class="calculator-overlay" onclick="closeCalculator(event)">
            <div class="calculator-popup" onclick="event.stopPropagation()">
                <div class="calculator-display" id="calculatorDisplay">0</div>
                <div class="calculator-grid">
                    <button class="calc-btn" onclick="addNumber('1')">1</button>
                    <button class="calc-btn" onclick="addNumber('2')">2</button>
                    <button class="calc-btn" onclick="addNumber('3')">3</button>
                    <button class="calc-btn" onclick="addNumber('4')">4</button>
                    <button class="calc-btn" onclick="addNumber('5')">5</button>
                    <!-- Corregir error tipográfico sclass por class -->
                    <button class="calc-btn" onclick="addNumber('6')">6</button>
                    <button class="calc-btn" onclick="addNumber('7')">7</button>
                    <button class="calc-btn" onclick="addNumber('8')">8</button>
                    <button class="calc-btn" onclick="addNumber('9')">9</button>
                    <button class="calc-btn zero" onclick="addNumber('0')">0</button>
                    <button class="calc-btn" onclick="deleteLast()"><i class="fa-solid fa-left-long fa-lg"></i></button>
                </div>
                <div class="calc-actions" >
                    <button class="calc-action-btn borrar" onclick="clearCalculator()">Borrar</button>
                    <button class="calc-action-btn cancelar" onclick="closeCalculator()">Cancelar</button>
                    <button class="calc-action-btn confirmar" onclick="confirmQuantity()">OK</button>
                </div>
            </div>
        </div>
        <div id="tableOverlay" class="table-overlay" onclick="closeTable(event)">
            <div class="table-popup" onclick="event.stopPropagation()">
                <div class="table-popup-header">
                    <h2>Mesas</h2>
                    <i onclick="closeTable()" class="fa-solid fa-circle-xmark fa-xl" style="color: #ff0000; cursor: pointer;"></i>
                </div>
                <div class="tableContainer" id="tableContainer">

                </div>
            </div>
        </div>
        <!-- Modal Monto Manual -->
        <div id="manualAmountOverlay" class="calculator-overlay" onclick="closeManualAmount(event)">
            <div class="calculator-popup" onclick="event.stopPropagation()">
                <div class="manual-amount-header">
                    <h5>💰 Agregar Monto Manual</h5>
                </div>
                <div class="calculator-display" id="manualAmountDisplay">$0</div>
                <div class="calculator-grid">
                    <button class="calc-btn" onclick="addManualDigit('1')">1</button>
                    <button class="calc-btn" onclick="addManualDigit('2')">2</button>
                    <button class="calc-btn" onclick="addManualDigit('3')">3</button>
                    <button class="calc-btn" onclick="addManualDigit('4')">4</button>
                    <button class="calc-btn" onclick="addManualDigit('5')">5</button>
                    <button class="calc-btn" onclick="addManualDigit('6')">6</button>
                    <button class="calc-btn" onclick="addManualDigit('7')">7</button>
                    <button class="calc-btn" onclick="addManualDigit('8')">8</button>
                    <button class="calc-btn" onclick="addManualDigit('9')">9</button>
                    <button class="calc-btn zero" onclick="addManualDigit('0')">0</button>
                    <button class="calc-btn" onclick="deleteManualDigit()"><i class="fa-solid fa-left-long fa-lg"></i></button>
                </div>
                <div class="calc-actions">
                    <button class="calc-action-btn borrar" onclick="clearManualAmount()">Borrar</button>
                    <button class="calc-action-btn cancelar" onclick="closeManualAmount()">Cancelar</button>
                    <button class="calc-action-btn confirmar" onclick="confirmManualAmount()">OK</button>
                </div>
            </div>
        </div>

        <!-- Modal Calculadora de Efectivo Recibido (táctil) -->
        <div id="cashCalcOverlay" class="calculator-overlay cash-calc-overlay" onclick="closeCashCalc(event)">
            <div class="calculator-popup" onclick="event.stopPropagation()">
                <div class="manual-amount-header">
                    <h5>💵 ¿Con cuánto paga?</h5>
                </div>
                <div class="calculator-display" id="cashCalcDisplay">$0</div>
                <div class="calculator-grid">
                    <button class="calc-btn" onclick="addCashDigit('1')">1</button>
                    <button class="calc-btn" onclick="addCashDigit('2')">2</button>
                    <button class="calc-btn" onclick="addCashDigit('3')">3</button>
                    <button class="calc-btn" onclick="addCashDigit('4')">4</button>
                    <button class="calc-btn" onclick="addCashDigit('5')">5</button>
                    <button class="calc-btn" onclick="addCashDigit('6')">6</button>
                    <button class="calc-btn" onclick="addCashDigit('7')">7</button>
                    <button class="calc-btn" onclick="addCashDigit('8')">8</button>
                    <button class="calc-btn" onclick="addCashDigit('9')">9</button>
                    <button class="calc-btn zero" onclick="addCashDigit('0')">0</button>
                    <button class="calc-btn" onclick="deleteCashDigit()"><i class="fa-solid fa-left-long fa-lg"></i></button>
                </div>
                <div class="calc-actions">
                    <button class="calc-action-btn borrar" onclick="clearCashCalc()">Borrar</button>
                    <button class="calc-action-btn cancelar" onclick="closeCashCalc()">Cancelar</button>
                    <button class="calc-action-btn confirmar" onclick="confirmCashCalc()">OK</button>
                </div>
            </div>
        </div>
        <!-- Modal de confirmación de venta -->
        <div id="saleConfirmationOverlay" class="table-overlay" onclick="closeSaleConfirmation(event)">
            <div class="sale-confirm" onclick="event.stopPropagation()">
                <div class="sale-confirm-header">
                    <h2>Confirmar venta</h2>
                    <i onclick="closeSaleConfirmation()" class="fa-solid fa-circle-xmark" title="Cerrar"></i>
                </div>

                <div class="sale-confirm-body">
                    <div class="sale-confirm-total">
                        <span class="sale-confirm-total-label">Total a cobrar</span>
                        <span id="saleTotalValue" class="sale-confirm-total-value">$ 0.00</span>
                    </div>

                    <div class="sale-confirm-section-title">Método de pago</div>
                    <div class="sale-confirm-methods">
                        <button id="salePaymentEfectivo" type="button" class="payment-btn"
                                onclick="selectPaymentMethod(this,'efectivo')">
                            <i class="fa-solid fa-money-bill-wave"></i>
                            <span>Efectivo</span>
                        </button>
                        <button id="salePaymentBancolombia" type="button" class="payment-btn"
                                onclick="selectPaymentMethod(this,'bancolombia')">
                            <i class="fa-solid fa-building-columns"></i>
                            <span>Bancolombia</span>
                        </button>
                        <button id="salePaymentNequi" type="button" class="payment-btn"
                                onclick="selectPaymentMethod(this,'nequi')">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                            <span>Nequi</span>
                        </button>
                    </div>

                    <!-- Efectivo recibido + devuelta (solo visible al pagar en efectivo) -->
                    <div id="cashChangeBox" class="cash-change-box" style="display:none;">
                        <div class="sale-confirm-section-title mb-1">¿Con cuánto paga?</div>
                        <button type="button" class="cash-received-display" onclick="openCashCalc()">
                            <span class="cash-prefix">$</span>
                            <span id="cashReceivedDisplay">0</span>
                            <i class="fa-solid fa-calculator"></i>
                        </button>
                        <div class="cash-quick">
                            <button type="button" class="cash-quick-btn" onclick="setCashReceived(10000)">$10.000</button>
                            <button type="button" class="cash-quick-btn" onclick="setCashReceived(20000)">$20.000</button>
                            <button type="button" class="cash-quick-btn" onclick="setCashReceived(50000)">$50.000</button>
                            <button type="button" class="cash-quick-btn" onclick="setCashReceived(100000)">$100.000</button>
                        </div>
                        <div id="cashChangeContainer" class="cash-change-result" style="display:none;">
                            <span id="cashChangeLabel">Devuelta</span>
                            <strong id="cashChangeValue">$ 0</strong>
                        </div>
                    </div>
                </div>

                <div class="sale-confirm-footer">
                    <button type="button" class="btn btn-secondary btn-lg" onclick="closeSaleConfirmation()">
                        Cancelar
                    </button>
                    <button type="button" id="saleConfirmBtn" class="btn btn-success btn-lg" onclick="confirmSalePayment()">
                        <i class="fa-solid fa-check"></i> Confirmar
                    </button>
                </div>
            </div>
        </div>
        <div class="col-3 border-start bg-light ps-1 pos-col-cart">

            <!-- Carritos por pestaña -->
            <div id="ventasContent" class="tab-content">
                <div class="tab-pane fade show active" id="venta1">
                    <div id="carrito-venta1">

                        <!-- Header fijo -->
                        <div class="cart-header cart-header-venta">
                            <span class="ch-icon"><i class="fa-solid fa-cash-register"></i></span>
                            <span class="ch-title">Venta 1</span>
                            <span class="ch-badge" id="ventasCount-venta1">0</span>
                        </div>

                        <!-- Lista de productos con scroll interno -->
                        <div id="productos-carrito-venta1" class="cart-scroll"></div>

                        <!-- Total y botones fijos abajo -->
                        <div class="cart-footer">
                            <div id="total-carrito-venta1">
                                <center><h1>Total: <strong>$<span id="total-venta1">0.00</span></strong></h1></center>
                            </div>
                            <button id="btn-procesar-venta-venta1" class="btn btn-primary btn-lg w-100 mb-2" 
                                    onclick="saleConfirmationModal('venta1', null)" role="button">
                                Procesar Venta <i class="fa-solid fa-cash-register"></i>
                            </button>
                            <button id="btn-agregar-mesa-venta1" class="btn btn-secondary btn-lg w-100" 
                                    onclick="openTableSelectionModal(event)" role="button">
                                Agregar a Mesa <i class="fa-solid fa-utensils"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-lg w-100 mt-2" onclick="clearCart('venta1')">
                                Limpiar carrito <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- OVERLAY: Reporte de Facturas Diarias -->
<!-- ========================================== -->
<div id="dailyReportOverlay" class="table-overlay" onclick="closeDailyReport(event)">
    <div class="daily-report-popup" onclick="event.stopPropagation()">
        <div class="daily-report-header">
            <h2 style="margin:0">
                <i class="fa-solid fa-file-invoice"></i> Facturas del Día
            </h2>
            <i onclick="closeDailyReport()" class="fa-solid fa-circle-xmark fa-xl" style="color: #ff0000; cursor:pointer"></i>
        </div>
        <div id="dailyReportContent" class="daily-report-body">
            <div class="text-center py-5">
                <div class="spinner-border text-info" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-3 text-muted">Cargando reporte...</p>
            </div>
        </div>
    </div>
</div>

    <script src="<?= asset('assets/js/sales/products.js') ?>" defer></script>
    <script src="<?= asset('assets/js/sales/cart.js') ?>" defer></script>
    <script src="<?= asset('assets/js/sales/tabs.js') ?>" defer></script>
    <script src="<?= asset('assets/js/sales/tables.js') ?>" defer></script>
    <script src="<?= asset('assets/js/sales/Sales.js') ?>" defer></script>
    <script src="<?= asset('assets/js/sales/barcode.js') ?>" defer></script>
    <script src="<?= asset('assets/js/tables-board.js') ?>" defer></script>

    <!-- SortableJS: Librería para drag & drop de tabs -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <!-- ============================================================
         TABLERO NAVEGADOR DE MESAS (botón "Ver mesas")
         Muestra el plano del salón; al tocar una mesa abre su carrito.
         Las mesas NO usan pestañas visibles (se navegan desde aquí).
         ============================================================ -->
    <script>
    // modo 'open'     → tocar una mesa abre su carrito (botón "Ver mesas").
    // modo 'transfer' → tocar una mesa LIBRE pasa el carrito actual a esa mesa
    //                   (botón "Agregar a Mesa", cuando hay productos).
    async function openTablesBoard(mode) {
        mode = mode || 'open';
        const overlay = document.getElementById('tablesBoardOverlay');
        const canvas = document.getElementById('salesBoardCanvas');
        const titulo = document.getElementById('tablesBoardTitle');
        if (titulo) titulo.textContent = mode === 'transfer' ? 'Pasar a mesa' : 'Mesas del salón';
        const hint = document.getElementById('tablesBoardHint');
        if (hint) hint.textContent = mode === 'transfer'
            ? 'Toca una mesa libre para pasarle el pedido'
            : 'Toca una mesa para abrir su cuenta';
        try {
            const data = await fetchJson('?pg=sales&action=GetTables');
            if (!data.success) throw new Error(data.error || 'Error');
            const mesas = data.data.map(function (m) {
                return {
                    idMesa: m.idMesa,
                    numero: m.numeroMesa,
                    nombre: m.nombreMesa,
                    tipo: m.tipo,
                    estado: m.estadoMesa,
                    posX: m.posX,
                    posY: m.posY,
                    total: m.total,
                    idVenta: m.idVenta
                };
            });
            const handler = mode === 'transfer' ? handleTransferToTable : openTableFromBoard;
            TablesBoard.render(canvas, mesas, { onTableClick: handler });
            overlay.classList.add('active');
        } catch (e) {
            Swal.fire('Error', 'No se pudieron cargar las mesas: ' + e.message, 'error');
        }
    }

    // Transfiere el carrito actual a una mesa LIBRE (desde "Agregar a Mesa").
    function handleTransferToTable(mesa) {
        const ocupada = mesa.estado === 'ocupada' || mesa.idVenta;
        if (ocupada) {
            Swal.fire({ icon: 'error', title: 'Mesa ocupada', text: 'Elige una mesa libre para pasarle el pedido.', timer: 1600, showConfirmButton: false });
            return;
        }
        closeTablesBoard();
        // Reutiliza el flujo probado de transferencia (pasa los productos y
        // deja la venta vacía).
        openOrTransferToTable(mesa.idMesa, mesa.numero);
    }

    function closeTablesBoard() {
        const ov = document.getElementById('tablesBoardOverlay');
        if (ov) ov.classList.remove('active');
    }

    async function openTableFromBoard(mesa) {
        const idMesa = mesa.idMesa;
        const numero = mesa.numero;
        const tabId = 'mesa-' + idMesa;
        // Etiqueta para el banner del carrito: nombre si tiene, o "Barra/Mesa N".
        const etiqueta = mesa.nombre && String(mesa.nombre).trim()
            ? mesa.nombre
            : (mesa.tipo === 'barra' ? 'Barra ' + numero : 'Mesa ' + numero);
        closeTablesBoard();

        // Si ya existe la pestaña (oculta) de la mesa, solo mostrarla.
        if (document.getElementById(tabId)) {
            const link = document.querySelector('#ventasTabs a[href="#' + tabId + '"]');
            if (link) showTab(link);
            switchToTableCart(tabId, idMesa);
            setActiveCart(tabId);
            return;
        }

        try {
            if (mesa.idVenta) {
                // Mesa ocupada sin pestaña local: abrir su venta existente.
                activeTables[idMesa] = { idMesa: idMesa, idVenta: mesa.idVenta, numero: numero, total: mesa.total || 0 };
                createTableTab(idMesa, numero, mesa.idVenta, true, etiqueta);
                const det = await fetchJson('?pg=sales&action=GetSale&id=' + mesa.idVenta);
                if (det.success) loadTableProducts(tabId, (det.data && det.data.detalles) || []);
            } else {
                // Mesa libre: crear una venta nueva vacía para la mesa.
                const resp = await fetchJson('?pg=sales&action=transferProductsToTable', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idMesa: idMesa, productos: [] })
                });
                if (!resp.success) { Swal.fire('Error', resp.error || 'No se pudo abrir la mesa', 'error'); return; }
                const idVenta = resp.data.idVenta;
                activeTables[idMesa] = { idMesa: idMesa, idVenta: idVenta, numero: numero, total: 0 };
                createTableTab(idMesa, numero, idVenta, true, etiqueta);
                loadTableProducts(tabId, []);
            }
        } catch (e) {
            Swal.fire('Error', 'No se pudo abrir la mesa: ' + e.message, 'error');
        }
    }
    </script>

    <!-- ============================================================
         CARRITO MÓVIL: barra-resumen deslizable (bottom sheet)
         Solo actúa en pantallas de celular; en escritorio no interfiere.
         ============================================================ -->
    <script>
    (function () {
        const cart = document.querySelector('.pos-col-cart');
        if (!cart) return;

        const esMovil = () => window.matchMedia('(max-width: 768px)').matches;

        // Barra-resumen (handle) que asoma en el borde inferior
        const handle = document.createElement('div');
        handle.className = 'cart-mobile-handle';
        handle.innerHTML = `
            <span class="cmh-icon"><i class="fa-solid fa-cart-shopping"></i>
                <span class="cmh-count" id="cmhCount">0</span>
            </span>
            <span class="cmh-label">Ver pedido</span>
            <span class="cmh-total" id="cmhTotal">$0</span>
            <i class="fa-solid fa-chevron-up cmh-chevron"></i>`;
        cart.insertBefore(handle, cart.firstChild);

        // Fondo oscuro
        const backdrop = document.createElement('div');
        backdrop.className = 'cart-sheet-backdrop';
        document.body.appendChild(backdrop);

        function abrir() {
            if (!esMovil()) return;
            cart.classList.add('cart-open');
            backdrop.classList.add('show');
        }
        function cerrar() {
            cart.classList.remove('cart-open');
            backdrop.classList.remove('show');
        }
        function toggle() {
            cart.classList.contains('cart-open') ? cerrar() : abrir();
        }

        handle.addEventListener('click', toggle);
        backdrop.addEventListener('click', cerrar);

        // Sincroniza cantidad y total desde la pestaña de venta activa.
        function activo(sel) {
            const pane = document.querySelector('#ventasContent .tab-pane.active');
            return pane ? pane.querySelector(sel) : null;
        }
        function sync() {
            // El span del total es #total-<tab>; hay que excluir el contenedor
            // #total-carrito-<tab> que también empieza por "total-".
            const totalEl = activo('[id^="total-"]:not([id^="total-carrito"])');
            const countEl = activo('[id^="ventasCount-"]');
            const cmhTotal = document.getElementById('cmhTotal');
            const cmhCount = document.getElementById('cmhCount');
            if (cmhTotal) cmhTotal.textContent = '$' + (totalEl ? totalEl.textContent.trim() : '0');
            if (cmhCount) cmhCount.textContent = countEl ? countEl.textContent.trim() : '0';
        }
        // Chequeo ligero: refleja los cambios del carrito sin acoplarse a su lógica.
        setInterval(sync, 400);
        sync();

        // Al cambiar de pestaña, cerrar la hoja para evitar confusión.
        document.getElementById('ventasTabs')?.addEventListener('click', () => {
            if (esMovil()) cerrar();
        });

        // Si se pasa a escritorio, limpiar estado móvil.
        window.addEventListener('resize', () => { if (!esMovil()) cerrar(); });
    })();
    </script>
<?php require loadView('Layouts/Footer'); ?>
