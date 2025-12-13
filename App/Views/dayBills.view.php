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

<link rel="stylesheet" href="assets/css/bills.css">
<div class="container-fluid mt-4">
    <form id="filtrosReporte" method="POST" action="index.php?pg=reports&action=dayBills" class="row">

        <!-- ID Venta -->
        <div class="col-12 mb-3">
            <label class="form-label">ID Venta</label>
            <input type="number" name="idVenta" class="form-control"
                value="<?php echo isset($_POST['idVenta']) ? $_POST['idVenta'] : ''; ?>">
        </div>

        <h4 class="card-title">Filtro de búsqueda</h4>

        <div class="col-11">
            <table class="table">
                <thead>
                    <tr class="filters">

                        <!-- Precio desde -->
                        <th>
                            <label>Precio desde</label>
                            <input type="number" name="precioDesde" class="form-control mt-2"
                                value="<?php echo $_POST['precioDesde'] ?? ''; ?>"
                                style="border: #bababa 1px solid; color:#000;">
                        </th>

                        <!-- Precio hasta -->
                        <th>
                            <label>Precio hasta</label>
                            <input type="number" name="precioHasta" class="form-control mt-2"
                                value="<?php echo $_POST['precioHasta'] ?? ''; ?>"
                                style="border: #bababa 1px solid; color:#000;">
                        </th>

                        <th>
                            <label>Hora desde</label>
                            <input type="time" name="horaDesde" class="form-control mt-2"
                                value="<?php echo $_POST['horaDesde'] ?? ''; ?>"
                                style="border: #bababa 1px solid; color:#000;">
                        </th>

                        <!-- Hora hasta -->
                        <th>
                            <label>Hora hasta</label>
                            <input type="time" name="horaHasta" class="form-control mt-2"
                                value="<?php echo $_POST['horaHasta'] ?? ''; ?>"
                                style="border: #bababa 1px solid; color:#000;">
                        </th>

                        <!-- Método de pago -->
                        <th>
                            <label>Método de pago</label>
                            <select name="metodoPago" class="form-control mt-2"
                                    style="border: #bababa 1px solid; color:#000;">

                                <?php if (!empty($_POST['metodoPago'])) { ?>
                                    <option value="<?php echo $_POST['metodoPago']; ?>">
                                        <?php echo $_POST['metodoPago']; ?>
                                    </option>
                                <?php } ?>

                                <option value="">Todos</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </th>

                    </tr>
                </thead>
            </table>
        </div>

        <div class="col-12 mt-3">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <button type="reset" class="btn btn-secondary">Limpiar</button>
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
</script>

<?php 
if (!isset($_GET['ajax']) || $_GET['ajax'] != '1') {
    require loadView('Layouts/footer');  
} 
?>
