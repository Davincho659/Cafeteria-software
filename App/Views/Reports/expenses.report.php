
<link rel="stylesheet" href="<?= asset('assets/css/bills.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/flatpick.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/reports.css') ?>">

<div class="container-fluid">

    <!-- ================= FILTROS ================= -->
    <div class="filter-card">
        <h4 class="filter-section-title">🔍 Filtros de búsqueda - Gastos</h4>
        <div id="active-filters" class="active-filters" style="display:none;">
            <div class="active-filters-title">
                Filtros activos:
                <span id="active-filters-list"></span>
            </div>
        </div>
        <input type="text" id="fechas" class="filter-input" name="fechas" style="visibility:hidden; height:0; padding:0; margin:0;" />
        
        <form id="filtrosReporte">
            <div class="row">
                <!-- Tipo de gasto -->
                <div class="col-md-6">
                    <div class="filter-group">
                        <label class="filter-label">Tipo de gasto</label>
                        <select name="tipo" class="filter-select">
                            <option value="">Todos</option>
                            <option value="producto">Producto (Merma/Rotura)</option>
                            <option value="externo">Externo</option>
                        </select>
                    </div>
                </div>

                <!-- Fecha -->
                <div class="col-md-6">
                    <label class="filter-label">Fecha</label>
                    <select name="fecha" id="select" class="filter-select">
                        <option value="<?php echo date('d/m/Y') . ' - ' . date('d/m/Y'); ?>">Hoy</option>
                        <option value="<?php echo date('d/m/Y', strtotime('-1 day')) . ' - ' . date('d/m/Y', strtotime('-1 day')); ?>">Ayer</option>
                        <option value="<?php echo date('d/m/Y', strtotime('first day of this month')) . ' - ' . date('d/m/Y'); ?>">Este mes</option>
                        <option value="custom" id="custom-option">Rango personalizado</option>
                    </select>
                </div>

                <!-- Precio desde -->
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">Precio desde</label>
                        <input type="number" name="precioDesde" class="filter-input" placeholder="$0">
                    </div>
                </div>

                <!-- Precio hasta -->
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">Precio hasta</label>
                        <input type="number" name="precioHasta" class="filter-input" placeholder="$999999">
                    </div>
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
                <div class="kpi-label">Total gastos</div>
                <div class="kpi-value" id="kpi-total-gastos">$0</div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">Cantidad gastos</div>
                <div class="kpi-value" id="kpi-cantidad-gastos">0</div>
            </div>
        </div>
    </div>

    <!-- ================= RESULTADOS ================= -->
    <div class="mt-4">
        <h3 class="filter-section-title">📊 Resultados del reporte</h3>

        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID Gasto</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                </tr>
            </thead>

            <tbody id="tablaResultados">
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        Cargando resultados...
                    </td>
                </tr>
            </tbody>
            <tfoot id="tfootTotales" class="tabla-totales"></tfoot>
        </table>

        <!-- PAGINACIÓN -->
        <div id="paginacion" class="mt-3"></div>
        <br>
    </div>
</div>

<script src="<?= asset('assets/js/flatpick.js') ?>"></script>
<script>
    const tipoReporte = 'expenses';
</script>
<script src="<?= asset('assets/js/admin/reports.js') ?>"></script>
