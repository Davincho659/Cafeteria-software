<?php 
if (!isset($_GET['ajax']) || $_GET['ajax'] != '1') {
    require loadView('Layouts/header'); 
}


if (!isset($_POST['idVenta'])) {
    $_POST['idVenta'] = '';
}
if (!isset($_POST['precioDesde'])) {
    $_POST['precioDesde'] = '';
}
if (!isset($_POST['precioHasta'])) {
    $_POST['precioHasta'] = '';
}
if (!isset($_POST['horaDesde'])) $_POST['horaDesde'] = '';
if (!isset($_POST['horaHasta'])) $_POST['horaHasta'] = '';
if (!isset($_POST['metodoPago'])) {
    $_POST['metodoPago'] = '';
}

?>

<link rel="stylesheet" href="<?= asset('assets/css/bills.css') ?>">
<div class="container-fluid mt-4">
    <div class="filter-card">
    <h4 class="filter-section-title">🔍 Filtros de búsqueda</h4>

    <?php
    $hasActiveFilters =
        !empty($_POST['idVenta']) ||
        !empty($_POST['precioDesde']) ||
        !empty($_POST['precioHasta']) ||
        !empty($_POST['horaDesde']) ||
        !empty($_POST['horaHasta']) ||
        !empty($_POST['metodoPago']);
    ?>

    <?php if ($hasActiveFilters): ?>
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

            <?php if (!empty($_POST['horaDesde'])): ?>
                <span class="filter-badge">🕒 Desde <?= htmlspecialchars($_POST['horaDesde']) ?></span>
            <?php endif; ?>

            <?php if (!empty($_POST['horaHasta'])): ?>
                <span class="filter-badge">🕒 Hasta <?= htmlspecialchars($_POST['horaHasta']) ?></span>
            <?php endif; ?>

            <?php if (!empty($_POST['metodoPago'])): ?>
                <span class="filter-badge">💳 <?= ucfirst(htmlspecialchars($_POST['metodoPago'])) ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <form id="filtrosReporte" method="POST" action="index.php?pg=reports&action=dayBills" class="row">

        <!-- ID Venta -->
        <div class="col-12 mb-3">
            <label class="form-label">ID Venta</label>
            <input type="number" name="idVenta" class="form-control"
                value="<?= htmlspecialchars($_POST['idVenta']) ?>">
        </div>

        <div class="col-12">
            <table class="table">
                <thead>
                    <tr class="filters">

                        <!-- Precio desde -->
                        <th>
                            <label>Precio desde</label>
                            <input type="number" name="precioDesde" class="form-control mt-2"
                                value="<?= htmlspecialchars($_POST['precioDesde']) ?>">
                        </th>

                        <!-- Precio hasta -->
                        <th>
                            <label>Precio hasta</label>
                            <input type="number" name="precioHasta" class="form-control mt-2"
                                value="<?= htmlspecialchars($_POST['precioHasta']) ?>">
                        </th>

                        <!-- Hora desde -->
                        <th>
                            <label>Hora desde</label>
                            <input type="time" name="horaDesde" class="form-control mt-2"
                                value="<?= htmlspecialchars($_POST['horaDesde']) ?>">
                        </th>

                        <!-- Hora hasta -->
                        <th>
                            <label>Hora hasta</label>
                            <input type="time" name="horaHasta" class="form-control mt-2"
                                value="<?= htmlspecialchars($_POST['horaHasta']) ?>">
                        </th>

                        <!-- Método de pago -->
                        <th>
                            <label>Método de pago</label>
                            <select name="metodoPago" class="form-control mt-2">
                                <option value="">Todos</option>
                                <option value="efectivo" <?= $_POST['metodoPago'] === 'efectivo' ? 'selected' : '' ?>>
                                    Efectivo
                                </option>
                                <option value="transferencia" <?= $_POST['metodoPago'] === 'transferencia' ? 'selected' : '' ?>>
                                    Transferencia
                                </option>
                            </select>
                        </th>

                    </tr>
                </thead>
            </table>
        </div>

        <!-- Botones -->
        <div class="col-12 mt-3">
            <button type="submit" class="btn-search">🔍 Consultar</button>
            <button type="button" class="btn-clear" onclick="limpiarFiltros()">✕ Limpiar filtros</button>
        </div>

    </form>
    </div>


        <div class="col-12 mt-3">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <button type="button"  class="btn btn-secondary" onclick="limpiarFiltros()">Limpiar filtros</button>
        </div>

    </form>


    <!-- ============================================================= -->
    <!-- ===============   TABLA DE RESULTADOS   ====================== -->
    <!-- ============================================================= -->

    <div class="col-12 mt-4">
        <h4 class="card-title">
                    Resultados del reporte 
                    (<?php echo $paginacion['totalRegistros']; ?> registros totales - 
                    Mostrando página <?php echo $paginacion['paginaActual']; ?> de <?php echo $paginacion['totalPaginas']; ?>)
                </h4>
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
                                <button type="button" class="btn btn-sm btn-info" onclick="window.open('factura.php?pg=bill&id=<?php echo $fila['idVenta']; ?>','_blank','width=350,height=900');">
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

        <!-- CONTROLES DE PAGINACIÓN -->
        <?php if ($paginacion['totalPaginas'] > 1): ?>
            <nav aria-label="Paginación de reportes">
                <ul class="pagination justify-content-center">
                    
                    <!-- Botón anterior -->
                    <li class="page-item <?php echo ($paginacion['paginaActual'] <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pg=reports&action=dayBills&page=<?php echo $paginacion['paginaActual'] - 1; ?>" 
                           onclick="enviarFormConPagina(<?php echo $paginacion['paginaActual'] - 1; ?>); return false;">
                            « Anterior
                        </a>
                    </li>

                    <?php 
                    // Mostrar números de página
                    $rango = 2; // Cuántas páginas mostrar a cada lado
                    $inicio = max(1, $paginacion['paginaActual'] - $rango);
                    $fin = min($paginacion['totalPaginas'], $paginacion['paginaActual'] + $rango);
                    
                    // Primera página si no está en el rango
                    if ($inicio > 1) {
                        echo '<li class="page-item"><a class="page-link" href="#" onclick="enviarFormConPagina(1); return false;">1</a></li>';
                        if ($inicio > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Páginas en el rango
                    for ($i = $inicio; $i <= $fin; $i++): 
                    ?>
                        <li class="page-item <?php echo ($i == $paginacion['paginaActual']) ? 'active' : ''; ?>">
                            <a class="page-link" href="?pg=reports&action=dayBills&page=<?php echo $i; ?>" 
                               onclick="enviarFormConPagina(<?php echo $i; ?>); return false;">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; 
                    
                    // Última página si no está en el rango
                    if ($fin < $paginacion['totalPaginas']) {
                        if ($fin < $paginacion['totalPaginas'] - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="#" onclick="enviarFormConPagina(' . $paginacion['totalPaginas'] . '); return false;">' . $paginacion['totalPaginas'] . '</a></li>';
                    }
                    ?>

                    <!-- Botón siguiente -->
                    <li class="page-item <?php echo ($paginacion['paginaActual'] >= $paginacion['totalPaginas']) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pg=reports&action=dayBills&page=<?php echo $paginacion['paginaActual'] + 1; ?>" 
                           onclick="enviarFormConPagina(<?php echo $paginacion['paginaActual'] + 1; ?>); return false;">
                            Siguiente »
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script>
// Función para mantener los filtros al cambiar de página
function enviarFormConPagina(pagina) {
    const form = document.getElementById('filtrosReporte');
    const url = new URL(form.action);
    url.searchParams.set('page', pagina);
    form.action = url.toString();
    form.submit();
}
function limpiarFiltros() {
    // Limpiar el formulario
    document.getElementById('filtrosReporte').reset();
    
    // Recargar la página sin parámetros POST (GET limpio)
    window.location.href = 'index.php?pg=reports&action=dayBills';
}
</script>

<?php 
if (!isset($_GET['ajax']) || $_GET['ajax'] != '1') {
    require loadView('Layouts/footer');  
} 
?>
