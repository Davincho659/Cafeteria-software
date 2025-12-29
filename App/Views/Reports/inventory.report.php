
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
    .alert-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .alert-critical { background-color: #fee2e2; color: #991b1b; }
    .alert-warning { background-color: #fef3c7; color: #92400e; }
    @media (max-width: 576px) { .kpi-value { font-size: 1.35rem; } }
</style>

<div class="container-fluid">

    <!-- ================= HEADER ================= -->
    <div class="filter-card">
        <h4 class="filter-section-title">📦 Reporte de Inventario</h4>
        <p class="text-muted">Alertas de stock negativo y productos con stock bajo</p>

        <div id="loading-message" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando datos de inventario...</p>
        </div>
    </div>

    <!-- KPIs dinámicos -->
    <div class="row g-3 kpi-row" id="kpi-container" style="display:none;">
        <div class="col-12 col-md-4 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">⚠️ Alertas Críticas</div>
                <div class="kpi-value text-danger" id="kpi-alertas">0</div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">📉 Stock Bajo</div>
                <div class="kpi-value text-warning" id="kpi-stock-bajo">0</div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">💰 Valor Inventario</div>
                <div class="kpi-value text-success" id="kpi-valor-inventario">$0</div>
            </div>
        </div>
    </div>

    <!-- ================= ALERTAS CRÍTICAS ================= -->
    <div class="mt-4" id="alertas-section" style="display:none;">
        <h3 class="filter-section-title">🚨 Alertas Críticas (Stock Negativo)</h3>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-danger">
                    <tr>
                        <th>Producto</th>
                        <th>Stock Actual</th>
                        <th>Última Actualización</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody id="tabla-alertas">
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            Sin alertas críticas
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================= STOCK BAJO ================= -->
    <div class="mt-4" id="stock-bajo-section" style="display:none;">
        <h3 class="filter-section-title">⚠️ Productos con Stock Bajo</h3>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-warning">
                    <tr>
                        <th>Producto</th>
                        <th>Stock Actual</th>
                        <th>Stock Mínimo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody id="tabla-stock-bajo">
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            Todos los productos con stock adecuado
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <br>
    </div>
</div>

<script>
    const tipoReporte = 'inventoryReport';
</script>
<script src="assets/js/admin/reports.js"></script>
