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
            <button class="nav-link" id="movements-tab" data-bs-toggle="tab" 
                    data-bs-target="#movements" type="button" role="tab">
                📋 Movimientos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="alerts-tab" data-bs-toggle="tab" 
                    data-bs-target="#alerts" type="button" role="tab">
                ⚠️ Alertas
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
                            <select id="filterMovementType" class="form-select form-select-sm">
                                <option value="">Todos los tipos</option>
                                <option value="entrada">Entrada</option>
                                <option value="salida">Salida</option>
                                <option value="ajuste">Ajuste</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="filterMovementDateFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="filterMovementDateTo" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <button id="btnFilterMovements" class="btn btn-sm btn-primary w-100">
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
<script src="assets/js/admin/inventory.js"></script>