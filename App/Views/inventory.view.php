<?php require loadView('Layouts/header'); ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-dark">📊 Control de Inventario</h3>
            <p class="text-muted mb-0">Gestiona el stock de productos con control de inventario</p>
        </div>
    </div>

    <!-- Cards de resumen -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Valor Compra</h6>
                            <h3 class="fw-bold text-primary mb-0">
                                $<span id="totalValueCost">0.00</span>
                            </h3>
                        </div>
                        <div class="text-primary">
                            <i class="fa-solid fa-dollar-sign fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Valor Venta</h6>
                            <h3 class="fw-bold text-success mb-0">
                                $<span id="totalValueSale">0.00</span>
                            </h3>
                        </div>
                        <div class="text-success">
                            <i class="fa-solid fa-chart-line fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Ganancia Potencial</h6>
                            <h3 class="fw-bold text-warning mb-0">
                                $<span id="potentialProfit">0.00</span>
                            </h3>
                        </div>
                        <div class="text-warning">
                            <i class="fa-solid fa-sack-dollar fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="stock-tab" data-bs-toggle="tab" 
                    data-bs-target="#stock" type="button" role="tab">
                📦 Stock Actual
            </button>
        </li>
        
        <li class="nav-item" role="presentation">
            <button class="nav-link position-relative" id="alerts-tab" data-bs-toggle="tab" 
                    data-bs-target="#alerts" type="button" role="tab">
                ⚠️ Alertas
                <span id="inventoryAlertBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;"></span>
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

                    <!-- Buscador + filtro por tipo -->
                    <div class="row mb-3 g-2">
                        <div class="col-md-6">
                            <input type="text" id="searchStock" class="form-control"
                                   placeholder="Buscar producto...">
                        </div>
                        <div class="col-md-6">
                            <!-- Separar mercancía de materia prima: el dueño necesita
                                 ver sus insumos (maíz, café, aceite) por aparte. -->
                            <div class="btn-group w-100" role="group" aria-label="Filtrar por tipo">
                                <input type="radio" class="btn-check" name="filtroTipo" id="filtroTodos" value="" checked>
                                <label class="btn btn-outline-secondary" for="filtroTodos">Todos</label>

                                <input type="radio" class="btn-check" name="filtroTipo" id="filtroVenta" value="venta">
                                <label class="btn btn-outline-secondary" for="filtroVenta">
                                    <i class="fa-solid fa-tag"></i> De venta
                                </label>

                                <input type="radio" class="btn-check" name="filtroTipo" id="filtroInsumo" value="insumo">
                                <label class="btn btn-outline-secondary" for="filtroInsumo">
                                    <i class="fa-solid fa-wheat-awn"></i> Insumos
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>Producto</th>
                                    <th>Tipo</th>
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

        <!-- ============== ALERTAS ============== -->
        <div class="tab-pane fade" id="alerts" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Alertas de Stock Negativo</h5>
                        <div>
                            <button id="btnRefreshAlerts" class="btn btn-sm btn-outline-danger">
                                <i class="fa-solid fa-triangle-exclamation"></i> Actualizar
                            </button>
                        </div>
                    </div>

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
                            <tbody id="alertsTable">
                                <!-- Se llena con JS -->
                            </tbody>
                        </table>
                    </div>
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

<!-- ========================================================================
     Modal REGISTRAR CONSUMO (insumos)
     Distinto del ajuste: aquí se anota cuánto SE SACÓ (ej. 5 kg de maíz para
     los fritos), no cuánto quedó. Así el encargado no hace restas mentales y
     queda registrado en qué se gastó la materia prima.
     ======================================================================== -->
<div class="modal fade" id="modalConsumption" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fa-solid fa-utensils"></i> Registrar consumo de insumo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="consumptionForm">
                    <input type="hidden" id="consumeProductId">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Insumo</label>
                        <input type="text" id="consumeProductName" class="form-control" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Disponible ahora</label>
                        <input type="text" id="consumeCurrentStock" class="form-control" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">¿Cuánto se usó? *</label>
                        <div class="input-group">
                            <input type="number" id="consumeQuantity" class="form-control"
                                   min="0" step="0.01" placeholder="Ej: 5" required>
                            <span class="input-group-text" id="consumeUnit">u</span>
                        </div>
                        <small class="text-muted">Escribe la cantidad que sacaste, no la que queda.</small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">¿En qué se usó? *</label>
                        <input type="text" id="consumeDescription" class="form-control"
                               placeholder="Ej: Fritos del día" maxlength="255" required>
                    </div>

                    <div class="alert alert-light border small" id="consumeCostPreview" style="display:none;">
                        Costo aproximado del consumo: <strong id="consumeCostValue">$0</strong>
                    </div>

                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-warning fw-semibold">
                            <i class="fa-solid fa-check"></i> Registrar consumo
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

<?php require loadView('Layouts/footer'); ?>
<script src="<?= asset('assets/js/admin/inventory.js') ?>"></script>