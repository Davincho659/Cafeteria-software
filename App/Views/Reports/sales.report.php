<?php

if (!isset($_POST['idVenta'])) {
    $_POST['idVenta'] = '';
}
if (!isset($_POST['precioDesde'])) {
    $_POST['precioDesde'] = '';
}
if (!isset($_POST['precioHasta'])) {
    $_POST['precioHasta'] = '';
}
if (!isset($_POST['fecha'])) {
    $_POST['fecha'] = '';
}
if (!isset($_POST['metodoPago'])) {
    $_POST['metodoPago'] = '';
}

?>

<link rel="stylesheet" href="assets/css/bills.css">
<div class="container-fluid ">
    <div class="filter-card">
        <h4 class="filter-section-title">🔍 Filtros de búsqueda</h4>

        <?php
        $hasActiveFilters = !empty($_POST['idVenta']) || !empty($_POST['precioDesde']) ||
                           !empty($_POST['precioHasta']) || !empty($_POST['fecha']) ||
                           !empty($_POST['metodoPago']);

        if ($hasActiveFilters): ?>
            <div class="active-filters">
                <div class="active-filters-title">Filtros activos:</div>
                <?php if (!empty($_POST['idVenta'])): ?>
                    <span class="filter-badge">ID: <?= htmlspecialchars($_POST['idVenta']) ?></span>
                <?php endif; ?>
                <?php if (!empty($_POST['precioDesde'])): ?>
                    <span class="filter-badge">Desde: $<?= number_format($_POST['precioDesde'], 0, ',', '.') ?></span>
                <?php endif; ?>
                <?php if (!empty($_POST['precioHasta'])): ?>
                    <span class="filter-badge">Hasta: $<?= number_format($_POST['precioHasta'], 0, ',', '.') ?></span>
                <?php endif; ?>
                <?php if (!empty($_POST['fecha'])): ?>
                    <span class="filter-badge">📅 <?= htmlspecialchars($_POST['fecha']) ?></span>
                <?php endif; ?>
                <?php if (!empty($_POST['metodoPago'])): ?>
                    <span class="filter-badge">💳 <?= ucfirst(htmlspecialchars($_POST['metodoPago'])) ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <form id="filtrosReporte" method="POST" action="index.php?pg=reports&action=sales">
            <div class="row">

                <!-- ID Venta -->
                <div class="col-md-3 col-sm-6">
                    <div class="filter-group">
                        <label class="filter-label">ID Venta</label>
                        <input type="number"
                               name="idVenta"
                               class="filter-input"
                               placeholder="Ej: 123"
                               value="<?= htmlspecialchars($_POST['idVenta'] ?? '') ?>">
                    </div>
                </div>

                <!-- Precio desde -->
                <div class="col-md-3 col-sm-6">
                    <div class="filter-group">
                        <label class="filter-label">Precio desde</label>
                        <input type="number"
                               name="precioDesde"
                               class="filter-input"
                               placeholder="$0"
                               value="<?= htmlspecialchars($_POST['precioDesde'] ?? '') ?>">
                    </div>
                </div>

                <!-- Precio hasta -->
                <div class="col-md-3 col-sm-6">
                    <div class="filter-group">
                        <label class="filter-label">Precio hasta</label>
                        <input type="number"
                               name="precioHasta"
                               class="filter-input"
                               placeholder="$999999"
                               value="<?= htmlspecialchars($_POST['precioHasta'] ?? '') ?>">
                    </div>
                </div>

                <!-- Método de pago -->
                <div class="col-md-3 col-sm-6">
                    <div class="filter-group">
                        <label class="filter-label">Método de pago</label>
                        <select name="metodoPago" class="filter-select">
                            <option value="">Todos</option>
                            <option value="efectivo" <?= (isset($_POST['metodoPago']) && $_POST['metodoPago'] === 'efectivo') ? 'selected' : '' ?>>
                                Efectivo
                            </option>
                            <option value="transferencia" <?= (isset($_POST['metodoPago']) && $_POST['metodoPago'] === 'transferencia') ? 'selected' : '' ?>>
                                Transferencia
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Fecha -->
                <div class="col-md-6 col-sm-12">
                    <div class="filter-group">
                        <label class="filter-label">Rango de fecha</label>
                        <select name="fecha" class="filter-select">
                            <option value="">Seleccionar rango</option>
                            <option value="<?php echo date('d/m/Y') . ' - ' . date('d/m/Y'); ?>"
                                    <?= (isset($_POST['fecha']) && $_POST['fecha'] === date('d/m/Y') . ' - ' . date('d/m/Y')) ? 'selected' : '' ?>>
                                Hoy
                            </option>
                            <option value="<?php echo date('d/m/Y', strtotime('-1 day')) . ' - ' . date('d/m/Y', strtotime('-1 day')); ?>"
                                    <?= (isset($_POST['fecha']) && $_POST['fecha'] === date('d/m/Y', strtotime('-1 day')) . ' - ' . date('d/m/Y', strtotime('-1 day'))) ? 'selected' : '' ?>>
                                Ayer
                            </option>
                            <option value="<?php echo date('d/m/Y', strtotime('first day of this month')) . ' - ' . date('d/m/Y'); ?>"
                                    <?php
                                    $thisMonthRange = date('d/m/Y', strtotime('first day of this month')) . ' - ' . date('d/m/Y');
                                    echo (isset($_POST['fecha']) && $_POST['fecha'] === $thisMonthRange) ? 'selected' : '';
                                    ?>>
                                Este mes
                            </option>
                            <option value="<?php echo date('d/m/Y', strtotime('first day of last month')) . ' - ' . date('d/m/Y', strtotime('last day of last month')); ?>"
                                    <?php
                                    $lastMonthRange = date('d/m/Y', strtotime('first day of last month')) . ' - ' . date('d/m/Y', strtotime('last day of last month'));
                                    echo (isset($_POST['fecha']) && $_POST['fecha'] === $lastMonthRange) ? 'selected' : '';
                                    ?>>
                                Mes pasado
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            <!-- Botones -->
            <div class="col-md-6 col-sm-12">
                <div class="filter-group" style="padding-top: 28px;">
                    <button type="submit" class="btn-search">
                        🔍 Consultar
                    </button>
                    <button type="button" class="btn-clear" onclick="limpiarFiltros()">
                        ✕ Limpiar filtros
                    </button>
                    <button type="button" onclick="openAcountingReport()" class="btn btn-info">
                        Reporte Contable
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ============================================================= -->
    <!-- ===============   TABLA DE RESULTADOS   ====================== -->
    <!-- ============================================================= -->

    <div class="col-12 mt-4">
        <h3 class="filter-section-title">📊 Resultados del reporte</h3>
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID Venta</th>
                    <th>Fecha</th>
                    <th>Método Pago</th>
                    <th>Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($resultados)): ?>
                    <?php foreach ($resultados as $fila): ?>
                        <tr>
                            <td><?php echo $fila['idVenta']; ?></td>
                            <td><?php echo date('d/m/Y h:i A', strtotime($fila['fechaVenta'])); ?></td>
                            <td><?php echo $fila['metodoPago']; ?></td>
                            <td>$<?php echo number_format($fila['total'], 0, ',', '.'); ?></td>

                            <td>
                                <button type="button" class="btn btn-sm btn-success" onclick="window.open('factura.php?pg=bill&id=<?php echo $fila['idVenta']; ?>','_blank','width=350,height=900');">
                                    Ver factura
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            No hay resultados para mostrar.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="index.php?pg=reports&action=sales&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
<div id="accountingReportModal" class="modal" onclick="closeReport(event)">
    <div class="accounting-report-content" onclick="event.stopPropagation()">
        <h2>Reporte Contable</h2>
        <iframe id="accountingReportFrame" src="" style="width:100%; height:80vh; border:none;"></iframe>
    </div>
</div>

<script src="assets/js/admin/reports.js"></script>
