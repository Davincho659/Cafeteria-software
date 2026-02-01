
<link rel="stylesheet" href="assets/css/bills.css">
<link rel="stylesheet" href="assets/css/flatpick.css">
<link rel="stylesheet" href="assets/css/reports.css">


<div class="container-fluid">

    <!-- ================= FILTROS ================= -->
    <div class="filter-card">
        <h4 class="filter-section-title">🔍 Filtros de búsqueda</h4>
        <div id="active-filters" class="active-filters" ">
            <div class="active-filters-title">
                Filtros activos:
                <span id="active-filters-list"></span>
            </div>
        </div>
        <input
                        type="text"
                        id="fechas"
                        class="filter-input"
                        name="fechas"
                        style="visibility:hidden; height:0; padding:0; margin:0;"
                    />
        <form id="filtrosReporte">

            <div class="row">

                <!-- ID Venta -->
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">ID Venta</label>
                        <input type="number" name="idVenta" class="filter-input" placeholder="Ej: 123">
                    </div>
                </div>

                <!-- Precio desde -->
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">Precio desde</label>
                        <input type="number" name="precioDesde" class="filter-input" placeholder="$0">
                    </div>
                </div>

                <!-- Precio hasta -->
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">Precio hasta</label>
                        <input type="number" name="precioHasta" class="filter-input" placeholder="$999999">
                    </div>
                </div>

                <!-- Método de pago -->
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">Método de pago</label>
                        <select name="metodoPago" class="filter-select">
                            <option value="">Todos</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
                    </div>
                </div>
                
                <!-- Fecha -->
                 <div class="col-md-6">
                    <select name="fecha" id="select" class="filter-select"> 
                        <option value="<?php echo date('d/m/Y') . ' - ' . date('d/m/Y'); ?>" <?= (isset($_POST['fecha']) && $_POST['fecha'] === date('d/m/Y') . ' - ' . date('d/m/Y')) ? 'selected' : '' ?>> Hoy </option>
                        <option value="<?php echo date('d/m/Y', strtotime('-1 day')) . ' - ' . date('d/m/Y', strtotime('-1 day')); ?>" <?= (isset($_POST['fecha']) && $_POST['fecha'] === date('d/m/Y', strtotime('-1 day')) . ' - ' . date('d/m/Y', strtotime('-1 day'))) ? 'selected' : '' ?>> Ayer </option> 
                        <option value="<?php echo date('d/m/Y', strtotime('first day of this month')) . ' - ' . date('d/m/Y'); ?>" <?php $thisMonthRange = date('d/m/Y', strtotime('first day of this month')) . ' - ' . date('d/m/Y'); echo (isset($_POST['fecha']) && $_POST['fecha'] === $thisMonthRange) ? 'selected' : ''; ?>> Este mes </option> 
                        <option value="custom" id="custom-option">Rango personalizado</option>
                    </select>

                    <!-- Input REAL pero invisible visualmente -->
                    
                </div>

            </div>

            <!-- BOTONES -->
            <div class="mt-3">
                <button type="submit" class="btn-search">🔍 Consultar</button>
                <button type="button" class="btn-clear" onclick="limpiarFiltros()">✕ Limpiar</button>
            </div>

        </form>
        
    </div>
    <!-- KPIs dinámicos -->
        <div class="row g-3 kpi-row" id="kpi-container">
            <div class="col-12 col-md-4 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Total vendido</div>
                    <div class="kpi-value" id="kpi-total-vendido">$0</div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Total de ventas</div>
                    <div class="kpi-value" id="kpi-total-ventas">0</div>
                </div>
            </div>
        </div>

    <!-- ================= RESULTADOS ================= -->

    <div class="mt-4">
        <h3 class="filter-section-title">📊 Resultados del reporte</h3>

        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID Venta</th>
                    <th>Fecha</th>
                    <th>Método Pago</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody id="tablaResultados">
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        Cargando resultados...
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- PAGINACIÓN -->
        <div id="paginacion" class="mt-3"></div>
        <br>
    </div>
    
    <!-- Modal de Detalles de Venta -->
    <div id="saleDetailModalContainer">
        <div class="modal fade" id="saleDetailModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Detalles de la venta #<span id="saleDetailId"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div id="saleDetailLoader" class="text-center py-4 d-none">
                            <div class="spinner-border text-info" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2 text-muted">Cargando detalles...</p>
                        </div>

                        <div id="saleDetailError" class="alert alert-danger d-none" role="alert"></div>

                        <div id="saleDetailObservacion" class="alert alert-warning d-none" role="alert">
                            <strong>Motivo de cancelación:</strong>
                            <p id="observacionText" style="margin-top: 8px;"></p>
                        </div>

                        <div id="saleDetailContent">
                            <table class="table table-sm table-striped table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center" style="width:120px">Cantidad</th>
                                        <th style="width:180px">Precio unitario</th>
                                        <th style="width:180px">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="saleDetailTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Sin datos</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-end mt-2">
                                <div>
                                    <strong>Total:</strong>
                                    $<span id="saleDetailTotal">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-success" id="saleDetailPrintBtn">Ver factura</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<script src="assets/js/flatpick.js"></script>

<script>
// Definir tipo de reporte para reports.js
const tipoReporte = 'sales';

// ========================
// MODAL: Detalles de venta
// ========================
window.detail = function(idVenta) {
    try {
        const modalEl = document.getElementById('saleDetailModal');
        const saleIdEl = document.getElementById('saleDetailId');
        const loaderEl = document.getElementById('saleDetailLoader');
        const errorEl = document.getElementById('saleDetailError');
        const tbodyEl = document.getElementById('saleDetailTableBody');
        const totalEl = document.getElementById('saleDetailTotal');
        const printBtn = document.getElementById('saleDetailPrintBtn');

        if (!modalEl || !saleIdEl || !loaderEl || !errorEl || !tbodyEl || !totalEl || !printBtn) {
            console.error('[SALES REPORT] Elementos del modal no encontrados');
            return;
        }

        // Preparar estado inicial
        saleIdEl.textContent = String(idVenta);
        loaderEl.classList.remove('d-none');
        errorEl.classList.add('d-none');
        
        const observacionEl = document.getElementById('saleDetailObservacion');
        if (observacionEl) {
            observacionEl.classList.add('d-none');
        }
        
        tbodyEl.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Cargando...</td></tr>';
        totalEl.textContent = '0';
        printBtn.onclick = () => window.open('?pg=bill&id=' + idVenta, '_blank', 'width=350,height=900');

        // Mostrar modal con Bootstrap (igual que daily.report.php)
        const bs = window.bootstrap;
        if (!bs || typeof bs.Modal !== 'function') {
            console.error('[SALES REPORT] Bootstrap Modal no está disponible');
            return;
        }

        const modal = bs.Modal.getOrCreateInstance(modalEl);

        // Asegurar limpieza cuando se cierra
        if (!modalEl.dataset.cleanupBound) {
            modalEl.addEventListener('hidden.bs.modal', () => {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                modalEl.style.display = '';
                modalEl.classList.remove('show');
            });
            modalEl.dataset.cleanupBound = 'true';
        }

        modal.show();

        // Fetch de detalles de la venta
        fetch('?pg=sales&action=GetSale&id=' + encodeURIComponent(idVenta), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            const detalles = data?.data?.detalles || data?.detalles || data?.data?.productos || [];
            tbodyEl.innerHTML = '';

            // Mostrar observación si la venta está cancelada
            const venta = data?.data?.venta;
            if (venta && venta.estado === 'cancelada' && venta.descripcion) {
                const obs = document.getElementById('saleDetailObservacion');
                if (obs) {
                    obs.classList.remove('d-none');
                    document.getElementById('observacionText').textContent = venta.descripcion;
                }
            }

            if (!Array.isArray(detalles) || detalles.length === 0) {
                tbodyEl.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Sin productos</td></tr>';
                return;
            }

            let total = 0;
            detalles.forEach(d => {
                const nombre = d.producto_nombre || d.nombre || 'Producto';
                const qty = parseInt(d.cantidad || 0, 10);
                const unit = parseFloat((d.precioUnitario ?? d.precioVenta ?? d.precio ?? 0));
                const subtotal = parseFloat((d.subTotal ?? d.precioTotal ?? (qty * unit)));
                total += (isNaN(subtotal) ? 0 : subtotal);

                tbodyEl.innerHTML += `
                    <tr>
                        <td>${nombre}</td>
                        <td class="text-center">${isNaN(qty) ? 0 : qty}</td>
                        <td>$${Number(isNaN(unit) ? 0 : unit).toLocaleString('es-CO')}</td>
                        <td>$${Number(isNaN(subtotal) ? 0 : subtotal).toLocaleString('es-CO')}</td>
                    </tr>`;
            });
            totalEl.textContent = Number(total).toLocaleString('es-CO');
        })
        .catch(err => {
            console.error('[SALES REPORT] Error detalles:', err);
            errorEl.textContent = 'No se pudieron cargar los detalles: ' + err.message;
            errorEl.classList.remove('d-none');
            tbodyEl.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error cargando detalles</td></tr>';
        })
        .finally(() => {
            loaderEl.classList.add('d-none');
        });
    } catch (error) {
        console.error('[SALES REPORT] Error mostrando detalles:', error);
    }
};
</script>
<script src="assets/js/admin/reports.js"></script>