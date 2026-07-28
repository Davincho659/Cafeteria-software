<link rel="stylesheet" href="<?= asset('assets/css/bills.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/flatpick.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/reports.css') ?>">

<div class="container-fluid">

    <!-- ================= HEADER ================= -->
    <div class="filter-card">
        <h4 class="filter-section-title">📦 Reporte de Inventario - Control Completo</h4>
        <p class="text-muted">Gestiona el stock de productos, alertas y movimientos de inventario</p>
    </div>

    <!-- KPIs dinámicos -->
    <div class="row g-3 kpi-row" id="kpi-container">
        <div class="col-12 col-md-4 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">💳 Valor de Compra</div>
                <div class="kpi-value text-primary" id="kpi-valor-compra">$0</div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">📊 Valor de Venta</div>
                <div class="kpi-value text-success" id="kpi-valor-venta">$0</div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">💰 Ganancia Potencial</div>
                <div class="kpi-value text-warning" id="kpi-ganancia">$0</div>
            </div>
        </div>
    </div>

    <!-- ================= TABS ================= -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="stock-tab" data-bs-toggle="tab" 
                    data-bs-target="#stock" type="button" role="tab">
                📦 Stock Actual
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="movements-tab" data-bs-toggle="tab" 
                    data-bs-target="#movements" type="button" role="tab">
                📋 Movimientos
            </button>
        </li>
    </ul>

    <div class="tab-content">
        
        <!-- ============== STOCK ACTUAL ============== -->
        <div class="tab-pane fade show active" id="stock" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Stock de Productos</h5>
                        <div>
                            <button id="btnRefreshStock" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-rotate"></i> Actualizar
                            </button>
                        </div>
                    </div>

                    <!-- Buscador -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" id="searchStock" class="form-control" 
                                   placeholder="Buscar producto...">
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-end">P. Compra</th>
                                    <th class="text-end">P. Venta</th>
                                    <th class="text-end">Valor Total</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="stockTable">
                                <!-- Se llena con JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============== MOVIMIENTOS ============== -->
        <div class="tab-pane fade" id="movements" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Historial de Movimientos</h5>

                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="filter-label">Tipo de movimiento</label>
                            <select id="filterMovementType" class="form-select form-select-sm filter-input">
                                <option value="">Todos los tipos</option>
                                <option value="entrada">Entrada</option>
                                <option value="salida">Salida</option>
                                <option value="ajuste">Ajuste</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="filter-label">Fecha inicio</label>
                            <input type="date" id="filterMovementDateFrom" class="form-control form-control-sm filter-input">
                        </div>
                        <div class="col-md-3">
                            <label class="filter-label">Fecha fin</label>
                            <input type="date" id="filterMovementDateTo" class="form-control form-control-sm filter-input">
                        </div>
                        <div class="col-md-3">
                            <button id="btnFilterMovements" class="btn btn-sm btn-primary w-100 filter-input">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-center">Stock Anterior</th>
                                    <th class="text-center">Stock Actual</th>
                                    <th>Referencia</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody id="movementsTable">
                                <!-- Se llena con JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Modal Ajustar Stock -->
<div class="modal fade" id="modalAdjustStock" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajustar Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="adjustStockForm">
                    <input type="hidden" id="adjustProductId">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Producto</label>
                        <input type="text" id="adjustProductName" class="form-control" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Stock Actual</label>
                        <input type="text" id="adjustCurrentStock" class="form-control" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevo Stock *</label>
                        <input type="number" id="adjustNewStock" class="form-control" 
                               min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción *</label>
                        <textarea id="adjustDescription" class="form-control" rows="3" 
                                  placeholder="Ej: Corrección por inventario físico" required></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check"></i> Ajustar Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Historial -->
<div class="modal fade" id="modalViewHistory" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Historial del Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="productHistoryContent">
                <!-- Se llena con JS -->
            </div>
        </div>
    </div>
</div>
<script>
    const tipoReporte = 'inventory';
</script>
<script src="<?= asset('assets/js/admin/reports.js') ?>"></script>
<script src="<?= asset('assets/js/admin/inventory.js') ?>"></script>

