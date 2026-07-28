<?php
/**
 * Cuerpo compartido de los cierres (diario / mensual / anual).
 *
 * Espera definidas antes de incluirse:
 *   $cierreTipo   'closingDaily' | 'closingMonthly' | 'closingYearly'
 *   $cierreTitulo Título visible
 *   $cierreIcono  Emoji del encabezado
 */
$cierreTipo   = $cierreTipo   ?? 'closingDaily';
$cierreTitulo = $cierreTitulo ?? 'Cierre';
$cierreIcono  = $cierreIcono  ?? '📅';
?>
<link rel="stylesheet" href="<?= asset('assets/css/bills.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/reports.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/closings.css') ?>">

<div class="container-fluid closing-report">

    <!-- ================= SELECTOR DE PERIODO ================= -->
    <div class="filter-card no-print">
        <h4 class="filter-section-title"><?= $cierreIcono ?> <?= esc($cierreTitulo) ?></h4>
        <p class="text-muted">Resumen financiero y de inventario del periodo</p>

        <form id="closingForm" class="row g-3 align-items-end">
            <?php if ($cierreTipo === 'closingDaily'): ?>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="closing-fecha">Fecha</label>
                    <input type="date" class="form-control" id="closing-fecha" name="fecha"
                           value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                </div>
            <?php elseif ($cierreTipo === 'closingMonthly'): ?>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="closing-mes">Mes</label>
                    <input type="month" class="form-control" id="closing-mes" name="mes"
                           value="<?= date('Y-m') ?>" max="<?= date('Y-m') ?>">
                </div>
            <?php else: ?>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="closing-anio">Año</label>
                    <select class="form-select" id="closing-anio" name="anio">
                        <?php $anioActual = (int) date('Y');
                        for ($a = $anioActual; $a >= $anioActual - 5; $a--): ?>
                            <option value="<?= $a ?>"><?= $a ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Consultar</button>
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                    🖨️ Imprimir
                </button>
            </div>
        </form>
    </div>

    <div id="closing-loading" class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-2">Calculando cierre...</p>
    </div>

    <div id="closing-error" class="alert alert-danger d-none"></div>

    <div id="closing-content" style="display:none;">

        <h3 class="closing-print-title" id="closing-titulo"></h3>

        <!-- ================= KPIs PRINCIPALES ================= -->
        <div class="row g-3 kpi-row">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">💰 Total vendido</div>
                    <div class="kpi-value text-success" id="c-total-vendido">$0</div>
                    <div class="kpi-sub" id="c-cantidad-ventas">0 ventas</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">📈 Utilidad neta</div>
                    <div class="kpi-value" id="c-utilidad-neta">$0</div>
                    <div class="kpi-sub" id="c-margen">Margen 0%</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">🧾 Ticket promedio</div>
                    <div class="kpi-value" id="c-ticket-promedio">$0</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">🚫 Anulado (no cuenta)</div>
                    <div class="kpi-value text-danger" id="c-total-anulado">$0</div>
                    <div class="kpi-sub" id="c-cantidad-anuladas">0 facturas</div>
                </div>
            </div>
        </div>

        <!-- ================= ESTADO DE RESULTADOS ================= -->
        <div class="row g-3 mt-1">
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header card-header-cafe">
                        <h5 class="mb-0 text-white">💵 Resultado del periodo</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0 closing-table">
                            <tbody>
                                <tr>
                                    <td>Ventas (completadas)</td>
                                    <td class="text-end text-success" id="r-ventas">$0</td>
                                </tr>
                                <tr>
                                    <td>− Costo de lo vendido</td>
                                    <td class="text-end text-danger" id="r-costo">$0</td>
                                </tr>
                                <tr class="table-secondary fw-bold">
                                    <td>= Utilidad bruta</td>
                                    <td class="text-end" id="r-utilidad-bruta">$0</td>
                                </tr>
                                <tr>
                                    <td>− Gastos</td>
                                    <td class="text-end text-danger" id="r-gastos">$0</td>
                                </tr>
                                <tr class="table-success fw-bold">
                                    <td>= Utilidad neta</td>
                                    <td class="text-end" id="r-utilidad-neta">$0</td>
                                </tr>
                                <tr class="closing-note">
                                    <td>Compras del periodo (salida de caja)</td>
                                    <td class="text-end" id="r-compras">$0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header card-header-cafe">
                        <h5 class="mb-0 text-white">💳 Cómo pagaron</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0 closing-table">
                            <tbody>
                                <tr>
                                    <td>💵 Efectivo</td>
                                    <td class="text-end" id="p-efectivo">$0</td>
                                </tr>
                                <tr>
                                    <td>🏦 Bancolombia</td>
                                    <td class="text-end" id="p-bancolombia">$0</td>
                                </tr>
                                <tr>
                                    <td>📱 Nequi</td>
                                    <td class="text-end" id="p-nequi">$0</td>
                                </tr>
                                <tr class="table-secondary fw-bold">
                                    <td>= Total transferencias</td>
                                    <td class="text-end" id="p-transferencia">$0</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="p-3">
                            <div class="closing-bar">
                                <div class="closing-bar-fill" id="p-barra-efectivo"></div>
                            </div>
                            <small class="text-muted" id="p-proporcion">—</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= CIERRE DE CAJA ================= -->
        <div class="mt-4">
            <h3 class="filter-section-title">🗄️ Cajas del periodo</h3>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Caja</th>
                            <th>Responsable</th>
                            <th>Apertura</th>
                            <th>Cierre</th>
                            <th class="text-end">Base inicial</th>
                            <th class="text-end" title="Lo que debe haber en el cajón: base + ventas en efectivo − salidas">Efectivo esperado</th>
                            <th class="text-end" title="Dinero contado físicamente al cerrar">Contado</th>
                            <th class="text-end">Diferencia</th>
                            <th class="text-end" title="Cobrado por Nequi/Bancolombia: NO está en el cajón, está en la cuenta bancaria">🏦 Transferencias</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-cajas">
                        <tr><td colspan="9" class="text-center text-muted">Sin cajas en el periodo</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= EVOLUCIÓN ================= -->
        <div class="mt-4">
            <h3 class="filter-section-title">📊 Evolución de las ventas</h3>
            <div id="closing-serie" class="closing-serie">
                <p class="text-muted">Sin datos en el periodo</p>
            </div>
        </div>

        <!-- ================= TOP PRODUCTOS + STOCK ================= -->
        <div class="row g-3 mt-1">
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header card-header-cafe">
                        <h5 class="mb-0 text-white">🏆 Productos más vendidos</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0 closing-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-end">Cant.</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-top-productos">
                                <tr><td colspan="3" class="text-center text-muted">Sin ventas</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header card-header-cafe">
                        <h5 class="mb-0 text-white">📦 Inventario (hoy)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="mini-kpi">
                                    <span class="mini-kpi-label">Valor a costo</span>
                                    <span class="mini-kpi-value" id="s-valor-costo">$0</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-kpi">
                                    <span class="mini-kpi-label">Valor a venta</span>
                                    <span class="mini-kpi-value" id="s-valor-venta">$0</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-kpi">
                                    <span class="mini-kpi-label">Agotados</span>
                                    <span class="mini-kpi-value text-danger" id="s-agotados">0</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-kpi">
                                    <span class="mini-kpi-label">Stock bajo</span>
                                    <span class="mini-kpi-value text-warning" id="s-bajos">0</span>
                                </div>
                            </div>
                        </div>

                        <h6 class="mb-2">Hay que reponer</h6>
                        <div class="table-responsive closing-scroll">
                            <table class="table table-sm mb-0 closing-table">
                                <tbody id="tabla-reponer">
                                    <tr><td colspan="2" class="text-center text-muted">Todo con stock suficiente</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <br>
    </div>
</div>

<script>
    const cierreTipo = <?= json_encode($cierreTipo) ?>;
</script>
<script src="<?= asset('assets/js/admin/closings.js') ?>"></script>
