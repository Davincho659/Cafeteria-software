
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
    .ranking-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        font-weight: 700;
        font-size: 0.9rem;
    }
    .ranking-1 { background: linear-gradient(135deg, #ffd700, #ffed4e); color: #000; }
    .ranking-2 { background: linear-gradient(135deg, #c0c0c0, #e8e8e8); color: #000; }
    .ranking-3 { background: linear-gradient(135deg, #cd7f32, #e5a77c); color: #fff; }
    .ranking-other { background: #475569; color: #fff; }
    @media (max-width: 576px) { .kpi-value { font-size: 1.35rem; } }
</style>

<div class="container-fluid">

    <!-- ================= HEADER ================= -->
    <div class="filter-card">
        <h4 class="filter-section-title">🏆 Productos Más Vendidos</h4>
        <p class="text-muted">Top de productos más vendidos del día actual</p>

        <form id="filtrosReporte">
            <div class="row">
                <div class="col-md-6">
                    <div class="filter-group">
                        <label class="filter-label">Cantidad de productos</label>
                        <select name="limit" class="filter-select">
                            <option value="5">Top 5</option>
                            <option value="10" selected>Top 10</option>
                            <option value="20">Top 20</option>
                            <option value="50">Top 50</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn-search">🔍 Consultar</button>
            </div>
        </form>
    </div>

    <!-- KPIs dinámicos -->
    <div class="row g-3 kpi-row" id="kpi-container" style="display:none;">
        <div class="col-12 col-md-4 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">🥇 Producto Top</div>
                <div class="kpi-value" id="kpi-top-producto" style="font-size: 1.2rem;">-</div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">📦 Unidades Vendidas</div>
                <div class="kpi-value" id="kpi-total-unidades">0</div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">💰 Ingresos Totales</div>
                <div class="kpi-value text-success" id="kpi-total-ingresos">$0</div>
            </div>
        </div>
    </div>

    <!-- ================= RANKING ================= -->
    <div class="mt-4" id="ranking-section">
        <h3 class="filter-section-title">📊 Ranking de Productos</h3>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 60px;">Pos.</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Unidades Vendidas</th>
                        <th>Ingresos Generados</th>
                    </tr>
                </thead>
                <tbody id="tablaResultados">
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            Cargando ranking...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <br>
    </div>
</div>

<script>
    const tipoReporte = 'topProducts';
</script>
<script src="assets/js/admin/reports.js"></script>
