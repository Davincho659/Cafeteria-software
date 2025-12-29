<?php
require_once __DIR__ . '/../Models/Sales.php';

// Validar id de venta
$idVenta = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($idVenta <= 0) {
    http_response_code(400);
    echo "<p>ID de venta inválido.</p>";
    exit;
}

$salesModel = new Sales();
$venta = $salesModel->getSaleById($idVenta);
if (!$venta) {
    http_response_code(404);
    echo "<p>Venta no encontrada.</p>";
    exit;
}

$detalles = $salesModel->getSaleDetails($idVenta);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Factura #<?php echo $idVenta; ?></title>
    <link rel="stylesheet" href="assets/css/reports.css">
</head>
<body class="bill-container">
    <br>
    <div class="bill-header">
        <h2>Cafetería Bello Horizonte</h2>
        <img src="assets/img/logo.jpg" alt="Logo" class="bill-logo">
        <h3>Factura #<?php echo $idVenta; ?></h3>
        <p>Fecha de creación: <b><?php echo date('d-m-Y h:i A', strtotime($venta['fechaVenta'])); ?></b></p>
    </div>

    <table class="table factura-table">
        <thead>
            <tr>
                <th style="width: 60%;">Producto</th>
                <th style="width: 20%;">Cantidad</th>
                <th style="width: 20%;">Total</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach($detalles as $d): ?>
            <tr>
                <td><?php echo $d['producto_nombre']; ?></td>

                <td class="text-center">
                    <?php echo $d['cantidad']; ?>
                </td>

                <td class="text-right">
                    <b>$</b> <?php echo number_format($d['subTotal'], 0, ',', '.'); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <h3>Total: $<?php echo number_format($venta['total']); ?></h3>

    <button class="btn-print" onclick="window.print()">Imprimir</button>

</body>
</html>
