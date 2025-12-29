
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
    .kpi-positive { color: #10b981; }
    .kpi-negative { color: #ef4444; }
    @media (max-width: 576px) { .kpi-value { font-size: 1.35rem; } }
</style>

<div class="container-fluid">

    <!-- ================= FILTROS ================= -->
    <div class="filter-card">
        <h4 class="filter-section-title">💰 Análisis de Rentabilidad</h4>
        <p class="text-muted">Ganancia Real = Ventas - Costos Promedio - Gastos</p>
        <div id="active-filters" class="active-filters" style="display:none;">
            <div class="active-filters-title">
                Filtros activos:
                <span id="active-filters-list"></span>
            </div>
        </div>
        <input type="text" id="fechas" class="filter-input" name="fechas" style="visibility:hidden; height:0; padding:0; margin:0;" />
        
        <form id="filtrosReporte">
            <div class="row">
                <!-- Fecha -->
                <div class="col-md-12">
                    <select name="fecha" id="select" class="filter-select">
                        <option value="<?php echo date('d/m/Y') . ' - ' . date('d/m/Y'); ?>">Hoy</option>
                        <option value="<?php echo date('d/m/Y', strtotime('-1 day')) . ' - ' . date('d/m/Y', strtotime('-1 day')); ?>">Ayer</option>
                        <option value="<?php echo date('d/m/Y', strtotime('first day of this month')) . ' - ' . date('d/m/Y'); ?>">Este mes</option>
                        <option value="<?php echo date('d/m/Y', strtotime('first day of last month')) . ' - ' . date('d/m/Y', strtotime('last day of last month')); ?>">Mes pasado</option>
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
        <div class="col-12 col-md-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">💵 Total Ventas</div>
                <div class="kpi-value" id="kpi-total-ventas">$0</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">📦 Costos Promedio</div>
                <div class="kpi-value text-warning" id="kpi-total-costos">$0</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">💸 Gastos</div>
                <div class="kpi-value text-danger" id="kpi-total-gastos">$0</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">💰 Ganancia Real</div>
                <div class="kpi-value" id="kpi-ganancia-real">$0</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">📊 Margen %</div>
                <div class="kpi-value" id="kpi-margen">0%</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">🛒 Total Compras</div>
                <div class="kpi-value text-info" id="kpi-total-compras">$0</div>
            </div>
        </div>
    </div>

    <!-- ================= ANÁLISIS DETALLADO ================= -->
    <div class="mt-4">
        <h3 class="filter-section-title">📊 Análisis Detallado</h3>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Concepto</th>
                        <th>Monto</th>
                        <th>% del Total</th>
                    </tr>
                </thead>
                <tbody id="tablaResultados">
                    <tr>
                        <td colspan="3" class="text-center text-muted">
                            Cargando análisis...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <br>
    </div>
</div>

<script src="assets/js/flatpick.js"></script>
<script>
    const tipoReporte = 'profitability';
</script>
<script src="assets/js/admin/reports.js"></script>
