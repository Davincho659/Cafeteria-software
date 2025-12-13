<?php 
if (!isset($_GET['ajax']) || $_GET['ajax'] != '1') {
    require loadView('Layouts/header'); 
}
?>

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
if (!isset($_POST['fechaDesde'])) {
    $_POST['fechaDesde'] = '';
}
if (!isset($_POST['fechaHasta'])) {
    $_POST['fechaHasta'] = '';
}
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

                        <!-- Fecha desde -->
                        <th>
                            <label>Fecha desde</label>
                            <input type="date" name="fechaDesde" class="form-control mt-2"
                                value="<?php echo $_POST['fechaDesde'] ?? ''; ?>"
                                style="border: #bababa 1px solid; color:#000;">
                        </th>

                        <!-- Fecha hasta -->
                        <th>
                            <label>Fecha hasta</label>
                            <input type="date" name="fechaHasta" class="form-control mt-2"
                                value="<?php echo $_POST['fechaHasta'] ?? ''; ?>"
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
        <h4 class="card-title">Resultados del reporte (<?php echo count($resultados); ?> registros)</h4>

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
                            <td><?php echo $fila['fechaVenta']; ?></td>
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
    </div>
</div>

