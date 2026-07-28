<?php require loadView('Layouts/header'); ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-dark">🔑 Gestión de Usuarios</h3>
            <p class="text-muted mb-0">Crea las cuentas del personal y define qué puede ver cada uno</p>
        </div>
    </div>

    <div class="row">

        <!-- Formulario -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold text-primary mb-3">
                        <i class="fa-solid fa-user-plus"></i>
                        <span id="formTitle">Nuevo Usuario</span>
                    </h5>

                    <form id="userForm">
                        <input type="hidden" id="userId">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre de usuario *</label>
                            <input type="text" id="userName" class="form-control"
                                   placeholder="Ej: Maria" maxlength="50" autocomplete="off" required>
                            <small class="text-muted">Es el nombre con el que inicia sesión.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                PIN <span id="pinRequiredMark">*</span>
                            </label>
                            <input type="password" id="userPin" class="form-control"
                                   placeholder="Mínimo 4 dígitos" maxlength="20" autocomplete="new-password">
                            <small class="text-muted" id="pinHelp">Mínimo 4 caracteres.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Rol *</label>
                            <select id="userRole" class="form-select">
                                <option value="empleado">Empleado — vende y usa mesas</option>
                                <option value="admin">Administrador — acceso total</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" id="btnSaveUser" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Guardar
                            </button>
                            <button type="button" id="btnCancelEdit" class="btn btn-outline-secondary"
                                    style="display:none;">
                                <i class="fa-solid fa-times"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="alert alert-info mt-3 small">
                <i class="fa-solid fa-circle-info"></i>
                <strong>Sobre los PIN:</strong> se guardan cifrados, por eso no se pueden
                consultar. Si alguien lo olvida, escribe uno nuevo al editarlo.
            </div>
        </div>

        <!-- Listado -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">📋 Usuarios del sistema</h5>
                        <button id="btnRefreshUsers" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-rotate"></i> Actualizar
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <tr><td colspan="3" class="text-center text-muted py-4">Cargando…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require loadView('Layouts/footer'); ?>
<script src="<?= asset('assets/js/admin/users.js') ?>"></script>
