<?php require loadView('Layouts/header'); ?>
<link rel="stylesheet" href="<?= asset('assets/css/tables-board.css') ?>">
<style>
    .tipo-choice { cursor: pointer; }
    .tipo-choice input { display: none; }
    .tipo-choice span {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 12px; border: 2px solid var(--border); border-radius: 10px;
        font-weight: 600; color: var(--brown-dark); transition: all .15s ease;
    }
    .tipo-choice input:checked + span {
        background: var(--brown-dark); color: #fff; border-color: var(--brown-dark);
    }
</style>

<div class="container-fluid py-4">
    <!-- HEADER -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        🪑 Gestión de Mesas
                    </h3>
                    <p class="text-muted mb-0">
                        Administra las mesas disponibles para el sistema de ventas.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-lg" onclick="openLayoutBoard()">
                        <i class="fa-solid fa-vector-square"></i> Organizar salón
                    </button>
                    <button class="btn btn-primary btn-lg" onclick="openTableModal()">
                        <i class="fa-solid fa-plus"></i> Agregar Mesa
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ESTADÍSTICAS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Total Mesas</h6>
                            <h2 class="mb-0 fw-bold" id="statTotalTables">0</h2>
                        </div>
                        <i class="fa-solid fa-chair fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Mesas Libres</h6>
                            <h2 class="mb-0 fw-bold" id="statAvailableTables">0</h2>
                        </div>
                        <i class="fa-solid fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Mesas Ocupadas</h6>
                            <h2 class="mb-0 fw-bold" id="statOccupiedTables">0</h2>
                        </div>
                        <i class="fa-solid fa-utensils fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Ventas Activas</h6>
                            <h2 class="mb-0 fw-bold" id="statActiveSales">0</h2>
                        </div>
                        <i class="fa-solid fa-cash-register fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE MESAS -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">📋 Lista de Mesas</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="loadTables()">
                                <i class="fa-solid fa-rotate"></i> Actualizar
                            </button>
                        </div>
                    </div>

                    <!-- Buscador y filtros -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" id="searchTable" class="form-control" 
                                       placeholder="Buscar mesa por número o nombre...">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select id="filterStatus" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="libre">Disponibles</option>
                                <option value="ocupada">Ocupadas</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th width="80">ID</th>
                                    <th width="100">Número</th>
                                    <th>Nombre</th>
                                    <th width="120" class="text-center">Estado</th>
                                    <th width="150" class="text-center">Venta Activa</th>
                                    <th width="180" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablesTableBody">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                        <p class="mt-2 mb-0">Cargando mesas...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Estado vacío -->
                    <div id="emptyState" class="text-center py-5" style="display: none;">
                        <i class="fa-solid fa-chair fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-3">No hay mesas registradas</p>
                        <button class="btn btn-primary" onclick="openTableModal()">
                            <i class="fa-solid fa-plus"></i> Agregar Primera Mesa
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Agregar/Editar Mesa -->
<div class="modal fade" id="tableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tableModalTitle">
                    <i class="fa-solid fa-plus"></i> Agregar Mesa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="tableForm">
                <div class="modal-body">
                    <input type="hidden" id="tableId">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo</label>
                        <div class="d-flex gap-2">
                            <label class="tipo-choice flex-fill">
                                <input type="radio" name="tableTipo" value="mesa" checked>
                                <span><i class="fa-solid fa-utensils"></i> Mesa</span>
                            </label>
                            <label class="tipo-choice flex-fill">
                                <input type="radio" name="tableTipo" value="barra">
                                <span><i class="fa-solid fa-martini-glass"></i> Barra</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Número <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="tableNumber" class="form-control"
                               placeholder="Ej: 1, 2, 3..." required min="1">
                        <small class="text-muted">Número único para identificar la mesa o barra</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Nombre/Ubicación <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="tableName" class="form-control" 
                               placeholder="Ej: Mesa Principal, Terraza, VIP..." required>
                        <small class="text-muted">Descripción o ubicación de la mesa</small>
                    </div>

                    <div class="alert alert-info mb-0">
                        <i class="fa-solid fa-info-circle"></i>
                        <small>Las mesas nuevas se crean en estado <strong>disponible</strong> por defecto.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSaveTable">
                        <i class="fa-solid fa-check"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============ MODAL: Organizar salón (tablero editable) ============ -->
<div class="modal fade" id="layoutBoardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border:none;background:transparent;">
            <div class="board-popup">
                <div class="board-popup-header">
                    <h2><i class="fa-solid fa-vector-square"></i> Organizar salón</h2>
                    <i class="fa-solid fa-circle-xmark board-close" data-bs-dismiss="modal" title="Cerrar"></i>
                </div>
                <div class="board-popup-body">
                    <p class="text-muted mb-2">
                        Arrastra cada mesa para ubicarla igual que en el salón real. Al guardar,
                        esta distribución se verá en la vista de Ventas.
                    </p>
                    <div class="board-canvas" id="layoutCanvas"></div>
                </div>
                <div class="board-popup-footer">
                    <div class="board-legend">
                        <span><span class="dot dot-libre"></span> Libre</span>
                        <span><span class="dot dot-ocupada"></span> Ocupada</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" id="btnSaveLayout">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar distribución
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('assets/js/tables-board.js') ?>"></script>
<script src="<?= asset('assets/js/admin/tables.js') ?>"></script>
<script>
(function () {
    var modalEl = document.getElementById('layoutBoardModal');
    var canvas = document.getElementById('layoutCanvas');
    var bsModal = null;

    window.openLayoutBoard = async function () {
        try {
            const resp = await fetch('?pg=tables&action=getTables');
            const data = await resp.json();
            if (!data.success) throw new Error(data.error || 'Error');
            // Traer también estado/total de ocupación (para colorear)
            let mesas = data.data;
            try {
                const occ = await (await fetch('?pg=sales&action=GetTables')).json();
                if (occ.success) {
                    const mapa = {};
                    occ.data.forEach(function (o) { mapa[o.idMesa] = o; });
                    mesas = mesas.map(function (m) {
                        const o = mapa[m.idMesa] || {};
                        return Object.assign({}, m, { estado: o.estadoMesa || m.estado, total: o.total || 0 });
                    });
                }
            } catch (e) { /* si falla, se muestran igual */ }

            TablesBoard.render(canvas, mesas, { editable: true });
            bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        } catch (e) {
            Swal.fire('Error', 'No se pudieron cargar las mesas: ' + e.message, 'error');
        }
    };

    document.getElementById('btnSaveLayout').addEventListener('click', async function () {
        const btn = this;
        btn.disabled = true;
        const posiciones = TablesBoard.getPositions(canvas);
        try {
            const resp = await fetch('?pg=tables&action=savePositions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ posiciones })
            });
            const data = await resp.json();
            if (data.success) {
                await Swal.fire({ icon: 'success', title: 'Guardado', text: 'Distribución del salón guardada', timer: 1300, showConfirmButton: false });
                if (bsModal) bsModal.hide();
            } else {
                Swal.fire('Error', data.error || 'No se pudo guardar', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Fallo de conexión', 'error');
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>

<?php require loadView('Layouts/Footer'); ?>
