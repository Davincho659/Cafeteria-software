
<link rel="stylesheet" href="assets/css/bills.css">
<link rel="stylesheet" href="assets/css/flatpick.css">
<style>
    .kpi-row { margin-top: 1.5rem; }
    .kpi-card {
        background: #0f172a;
        color: #f8fafc;
        border-radius: 12px;
        padding: 16px 18px;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
        height: 100%;
    }
    .kpi-label { font-size: 0.95rem; opacity: 0.85; letter-spacing: 0.3px; }
    .kpi-value { font-size: 1.6rem; font-weight: 700; margin-top: 4px; }
    @media (max-width: 576px) { .kpi-value { font-size: 1.35rem; } }
</style>

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
                </tr>
            </thead>

            <tbody id="tablaResultados">
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        Cargando resultados...
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- PAGINACIÓN -->
        <div id="paginacion" class="mt-3"></div>
        <br>
    </div>
</div>

<script src="assets/js/flatpick.js"></script>
<script>
    const tipoReporte = 'purchases';
</script>
<script src="assets/js/admin/reports.js"></script>
