<?php require loadView('Layouts/header'); ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-dark">📦 Registro de Compras</h3>
            <p class="text-muted mb-0">Registra compras detalladas o rápidas a proveedores</p>
        </div>
    </div>

    <!-- Pestañas -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="detailed-tab" data-bs-toggle="tab" 
                    data-bs-target="#detailed" type="button" role="tab">
                📋 Compra Detallada
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="quick-tab" data-bs-toggle="tab" 
                    data-bs-target="#quick" type="button" role="tab">
                ⚡ Compra Rápida
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" 
                    data-bs-target="#history" type="button" role="tab">
                📊 Historial
            </button>
        </li>
    </ul>

    <div class="tab-content">
        
        <!-- ============== COMPRA DETALLADA ============== -->
        <div class="tab-pane fade show active" id="detailed" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="fw-bold text-primary mb-4">Seleccionar Productos</h5>

                            <!-- Buscador -->
                            <div class="input-group mb-3">
                                <input type="text" id="searchProduct" class="form-control" 
                                       placeholder="Buscar producto...">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>
                            </div>

                            <!-- Lista de productos -->
                            <div class="row" id="productsList" style="max-height: 500px; overflow-y: auto;">
                                <!-- Se llena con JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="fw-bold text-success mb-3">Resumen de Compra</h5>

                            <!-- Proveedor -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Proveedor *</label>
                                <select id="supplierSelect" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                </select>
                            </div>

                            <!-- Productos seleccionados -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Productos</label>
                                <div id="selectedProducts" style="max-height: 300px; overflow-y: auto;">
                                    <p class="text-muted text-center">
                                        <small>No hay productos seleccionados</small>
                                    </p>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="border-top pt-3">
                                <h4 class="fw-bold text-end">
                                    Total: $<span id="purchaseTotal">0.00</span>
                                </h4>
                            </div>

                            <!-- Botones -->
                            <div class="d-grid gap-2 mt-3">
                                <button id="btnSaveDetailedPurchase" class="btn btn-success">
                                    <i class="fa-solid fa-check"></i> Registrar Compra
                                </button>
                                <button id="btnClearPurchase" class="btn btn-outline-secondary">
                                    <i class="fa-solid fa-trash"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============== COMPRA RÁPIDA ============== -->
        <div class="tab-pane fade" id="quick" role="tabpanel">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="fw-bold text-warning mb-4">
                                ⚡ Registro Rápido de Compra
                            </h5>
                            <p class="text-muted small mb-4">
                                Usa esta opción cuando no puedas ingresar los productos uno por uno
                            </p>

                            <form id="quickPurchaseForm">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Proveedor
                                    </label>
                                    <select id="quickSupplierSelect" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Descripción *</label>
                                    <textarea id="quickDescription" class="form-control" rows="3" 
                                              placeholder="Ej: Compra de mercado surtido" required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Total de la compra *</label>
                                    <input type="number" id="quickTotal" class="form-control" 
                                           step="0.01" min="0" placeholder="0.00" required>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fa-solid fa-bolt"></i> Registrar Compra Rápida
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============== HISTORIAL ============== -->
        <div class="tab-pane fade" id="history" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">📊 Historial de Compras</h5>
                        <button id="btnRefreshHistory" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-rotate"></i> Actualizar
                        </button>
                    </div>

                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select id="filterSupplier" class="form-select form-select-sm">
                                <option value="">Todos los proveedores</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterType" class="form-select form-select-sm">
                                <option value="">Todos los tipos</option>
                                <option value="detallada">Detallada</option>
                                <option value="rapida">Rápida</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="filterDateFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="filterDateTo" class="form-control form-control-sm">
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th>Tipo</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="purchasesHistory">
                                <!-- Se llena con JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Ver Detalle -->
<div class="modal fade" id="modalViewPurchase" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Compra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="purchaseDetailContent">
                <!-- Se llena con JS -->
            </div>
        </div>
    </div>
</div>

<?php require loadView('Layouts/footer'); ?>
<script src="assets/js/admin/purchases.js"></script>