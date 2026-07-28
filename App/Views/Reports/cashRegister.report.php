
<link rel="stylesheet" href="<?= asset('assets/css/bills.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/flatpick.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/reports.css') ?>">

<div class="container-fluid">

    <!-- ================= RESUMEN CAJA ================= -->
    <div class="filter-card">
        <h4 class="filter-section-title">💵 Resumen de Caja Actual</h4>
        <p class="text-muted">Movimientos de la caja activa del día</p>

        <div id="loading-message" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando resumen de caja...</p>
        </div>

        <div id="no-caja-message" class="alert alert-warning d-none" role="alert">
            ⚠️ No hay una caja abierta actualmente.
        </div>
    </div>

    <!-- KPIs dinámicos -->
    <div class="row g-3 kpi-row" id="kpi-container" style="display:none;">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">💰 Monto Apertura</div>
                <div class="kpi-value" id="kpi-monto-apertura">$0</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">✅ Total Ingresos</div>
                <div class="kpi-value text-success" id="kpi-total-ingresos">$0</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">❌ Total Egresos</div>
                <div class="kpi-value text-danger" id="kpi-total-egresos">$0</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-label">💵 Efectivo en Caja</div>
                <div class="kpi-value text-primary" id="kpi-efectivo-caja">$0</div>
            </div>
        </div>
    </div>

    <!-- ================= DESGLOSE DE MOVIMIENTOS ================= -->
    <div class="mt-4" id="desglose-section" style="display:none;">
        <h3 class="filter-section-title">📊 Desglose de Movimientos</h3>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">💚 Ingresos</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tbody id="tabla-ingresos">
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Sin ingresos</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">❤️ Egresos</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tbody id="tabla-egresos">
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Sin egresos</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <br>
    </div>
</div>

<script>
    const tipoReporte = 'cashRegister';
</script>
<script src="<?= asset('assets/js/admin/reports.js') ?>"></script>
