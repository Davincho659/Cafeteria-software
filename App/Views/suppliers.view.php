<?php require loadView('Layouts/header'); ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-dark">👥 Gestión de Proveedores</h3>
            <p class="text-muted mb-0">Administra la información de tus proveedores</p>
        </div>
    </div>

    <div class="row">
        
        <!-- Formulario -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold text-primary mb-3">
                        <i class="fa-solid fa-user-plus"></i> 
                        <span id="formTitle">Registrar Proveedor</span>
                    </h5>

                    <form id="supplierForm">
                        <input type="hidden" id="supplierId">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre *</label>
                            <input type="text" id="supplierName" class="form-control" 
                                   placeholder="Nombre del proveedor" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <input type="tel" id="supplierPhone" class="form-control" 
                                   placeholder="Número de teléfono">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" id="btnSaveSupplier" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Guardar
                            </button>
                            <button type="button" id="btnCancelEdit" class="btn btn-outline-secondary" 
                                    style="display: none;">
                                <i class="fa-solid fa-times"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Listado -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">📋 Lista de Proveedores</h5>
                        <button id="btnRefreshSuppliers" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-rotate"></i> Actualizar
                        </button>
                    </div>

                    <!-- Buscador -->
                    <div class="input-group mb-3">
                        <input type="text" id="searchSupplier" class="form-control" 
                               placeholder="Buscar proveedor...">
                        <span class="input-group-text">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                    <th class="text-center" width="150">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="suppliersTable">
                                <!-- Se llena con JS -->
                            </tbody>
                        </table>
                    </div>

                    <div id="emptyState" class="text-center py-5" style="display: none;">
                        <i class="fa-solid fa-users-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay proveedores registrados</p>
                        <button class="btn btn-primary btn-sm" onclick="document.getElementById('supplierName').focus()">
                            <i class="fa-solid fa-plus"></i> Agregar Primer Proveedor
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require loadView('Layouts/footer'); ?>
<script src="assets/js/admin/suppliers.js"></script>