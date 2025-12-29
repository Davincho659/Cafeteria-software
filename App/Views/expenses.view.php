<?php require loadView('Layouts/header'); ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-dark">💸 Gestión de Gastos</h3>
            <p class="text-muted mb-0">Registra mermas de productos y gastos externos con trazabilidad</p>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="gasto-producto-tab" data-bs-toggle="tab" data-bs-target="#gasto-producto" type="button" role="tab">
                🧯 Gasto de Producto
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="gasto-externo-tab" data-bs-toggle="tab" data-bs-target="#gasto-externo" type="button" role="tab">
                🧾 Gasto Externo
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="listado-tab" data-bs-toggle="tab" data-bs-target="#listado" type="button" role="tab">
                📋 Listado
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- ============== GASTO PRODUCTO ============== -->
        <div class="tab-pane fade show active" id="gasto-producto" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Registrar Merma / Rotura / Vencimiento</h5>
                    <form id="productExpenseForm" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Producto *</label>
                            <select id="expenseProductSelect" class="form-select" required></select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Cantidad *</label>
                            <input type="number" id="expenseCantidad" class="form-control" min="0.001" step="0.001" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Motivo *</label>
                            <input type="text" id="expenseMotivo" class="form-control" placeholder="Merma / Rotura / Vencimiento" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Monto (opcional)</label>
                            <input type="number" id="expenseMonto" class="form-control" min="0" step="0.01" placeholder="Se calcula por costo si se omite">
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Registrar Gasto de Producto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============== GASTO EXTERNO ============== -->
        <div class="tab-pane fade" id="gasto-externo" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Registrar Gasto Externo</h5>
                    <form id="externalExpenseForm" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Concepto *</label>
                            <input type="text" id="externalConcepto" class="form-control" placeholder="Ej: Limpieza, Mantenimiento" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Monto *</label>
                            <input type="number" id="externalMonto" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Descripción</label>
                            <input type="text" id="externalDescripcion" class="form-control" placeholder="Detalles del gasto">
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Registrar Gasto Externo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============== LISTADO ============== -->
        <div class="tab-pane fade" id="listado" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Listado de Gastos</h5>
                        <button id="btnRefreshExpenses" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-rotate"></i> Actualizar
                        </button>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select id="filterExpenseTipo" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="producto">Producto</option>
                                <option value="externo">Externo</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="filterExpenseDateFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="filterExpenseDateTo" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <button id="btnFilterExpenses" class="btn btn-sm btn-primary w-100">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Producto/Concepto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Monto</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody id="expensesTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require loadView('Layouts/footer'); ?>
<script src="assets/js/admin/expenses.js"></script>
