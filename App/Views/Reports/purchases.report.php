
<link rel="stylesheet" href="assets/css/bills.css">
<link rel="stylesheet" href="assets/css/flatpick.css">
<link rel="stylesheet" href="assets/css/reports.css">

<div class="container-fluid">

    <!-- ================= FILTROS ================= -->
    <div class="filter-card">
        <h4 class="filter-section-title">🔍 Filtros de búsqueda - Compras</h4>
        <div id="active-filters" class="active-filters" style="display:none;">
            <div class="active-filters-title">
                Filtros activos:
                <span id="active-filters-list"></span>
            </div>
        </div>
        <input type="text" id="fechas" class="filter-input" name="fechas" style="visibility:hidden; height:0; padding:0; margin:0;" />
        
        <form id="filtrosReporte">
            <div class="row">
                <!-- Proveedor -->
                <div class="col-md-4">
                    <div class="filter-group">
                        <label class="filter-label">Proveedor</label>
                        <select name="idProveedor" class="filter-select">
                            <option value="">Todos</option>
                            <?php
                            require_once __DIR__ . '/../../Models/suppliers.php';
                            $suppliersModel = new Suppliers();
                            $suppliers = $suppliersModel->getAll();
                            foreach ($suppliers as $supplier) {
                                echo '<option value="' . $supplier['idProveedor'] . '">' . htmlspecialchars($supplier['nombre']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Tipo de compra -->
                <div class="col-md-4">
                    <div class="filter-group">
                        <label class="filter-label">Tipo de compra</label>
                        <select name="tipoCompra" class="filter-select">
                            <option value="">Todos</option>
                            <option value="detallada">Detallada</option>
                            <option value="rapida">Rápida</option>
                        </select>
                    </div>
                </div>

                <!-- Fecha -->
                <div class="col-md-4">
                    <label class="filter-label">Fecha</label>
                    <select name="fecha" id="select" class="filter-select">
                        <option value="<?php echo date('d/m/Y') . ' - ' . date('d/m/Y'); ?>">Hoy</option>
                        <option value="<?php echo date('d/m/Y', strtotime('-1 day')) . ' - ' . date('d/m/Y', strtotime('-1 day')); ?>">Ayer</option>
                        <option value="<?php echo date('d/m/Y', strtotime('first day of this month')) . ' - ' . date('d/m/Y'); ?>">Este mes</option>
                        <option value="custom" id="custom-option">Rango personalizado</option>
                    </select>
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
                <div class="kpi-label">Total comprado</div>
                <div class="kpi-value" id="kpi-total-comprado">$0</div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">Total de compras</div>
                <div class="kpi-value" id="kpi-total-compras">0</div>
            </div>
        </div>
    </div>

    <!-- ================= RESULTADOS ================= -->
    <div class="mt-4">
        <h3 class="filter-section-title">📊 Resultados del reporte</h3>

        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID Compra</th>
                    <th>Proveedor</th>
                    <th>Fecha</th>
                    <th>Tipo</th>
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

    <!-- Modal de Detalles de Compra -->
    <div id="purchaseDetailModalContainer">
        <div class="modal fade" id="purchaseDetailModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Detalles de la compra #<span id="purchaseDetailId"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div id="purchaseDetailLoader" class="text-center py-4 d-none">
                            <div class="spinner-border text-info" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2 text-muted">Cargando detalles...</p>
                        </div>

                        <div id="purchaseDetailError" class="alert alert-danger d-none" role="alert"></div>

                        <div class="mb-3" id="purchaseDetailMeta">
                            <div><strong>Proveedor:</strong> <span id="purchaseDetailProveedor">-</span></div>
                            <div><strong>Fecha:</strong> <span id="purchaseDetailFecha">-</span></div>
                            <div><strong>Tipo:</strong> <span id="purchaseDetailTipo">-</span></div>
                        </div>

                        <div id="purchaseDetailContent">
                            <table class="table table-sm table-striped table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center" style="width:120px">Cantidad</th>
                                        <th style="width:180px">Precio unitario</th>
                                        <th style="width:180px">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="purchaseDetailTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Sin datos</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-end mt-2">
                                <div>
                                    <strong>Total:</strong>
                                    $<span id="purchaseDetailTotal">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/flatpick.js"></script>
<script>
    const tipoReporte = 'purchases';

    // ========================
    // MODAL: Detalles de compra
    // ========================
    window.purchaseDetail = function(idCompra) {
        const modalEl = document.getElementById('purchaseDetailModal');
        const idEl = document.getElementById('purchaseDetailId');
        const loaderEl = document.getElementById('purchaseDetailLoader');
        const errorEl = document.getElementById('purchaseDetailError');
        const tbodyEl = document.getElementById('purchaseDetailTableBody');
        const totalEl = document.getElementById('purchaseDetailTotal');
        const proveedorEl = document.getElementById('purchaseDetailProveedor');
        const fechaEl = document.getElementById('purchaseDetailFecha');
        const tipoEl = document.getElementById('purchaseDetailTipo');

        if (!modalEl || !idEl || !loaderEl || !errorEl || !tbodyEl || !totalEl) {
            console.error('[PURCHASES REPORT] Elementos del modal no encontrados');
            return;
        }

        idEl.textContent = String(idCompra);
        loaderEl.classList.remove('d-none');
        errorEl.classList.add('d-none');
        tbodyEl.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Cargando...</td></tr>';
        totalEl.textContent = '0';
        if (proveedorEl) proveedorEl.textContent = '-';
        if (fechaEl) fechaEl.textContent = '-';
        if (tipoEl) tipoEl.textContent = '-';

        const bs = window.bootstrap;
        if (!bs || typeof bs.Modal !== 'function') {
            console.error('[PURCHASES REPORT] Bootstrap Modal no está disponible');
            return;
        }

        const modal = bs.Modal.getOrCreateInstance(modalEl);
        modal.show();

        fetch('?pg=purchases&action=getPurchase&id=' + encodeURIComponent(idCompra), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            const compra = data?.data?.compra || null;
            const detalles = data?.data?.detalles || [];

            if (compra) {
                if (proveedorEl) proveedorEl.textContent = compra.proveedor || 'Sin proveedor';
                if (fechaEl) fechaEl.textContent = compra.fechaCompra ? new Date(compra.fechaCompra).toLocaleString('es-CO') : '-';
                if (tipoEl) tipoEl.textContent = compra.tipoCompra || '-';
            }

            tbodyEl.innerHTML = '';

            if (!Array.isArray(detalles) || detalles.length === 0) {
                tbodyEl.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Sin productos</td></tr>';
                return;
            }

            let total = 0;
            detalles.forEach(d => {
                const nombre = d.producto || 'Producto';
                const qty = parseInt(d.cantidad || 0, 10);
                const unit = parseFloat(d.precioUnitario || 0);
                const subtotal = parseFloat(d.subtotal || (qty * unit));
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
            console.error('[PURCHASES REPORT] Error detalles:', err);
            errorEl.textContent = 'No se pudieron cargar los detalles: ' + err.message;
            errorEl.classList.remove('d-none');
            tbodyEl.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error cargando detalles</td></tr>';
        })
        .finally(() => {
            loaderEl.classList.add('d-none');
        });
    };
</script>
<script src="assets/js/admin/reports.js"></script>
