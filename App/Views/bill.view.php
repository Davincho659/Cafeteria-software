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
    <style>
        body {
            font-family: Arial, sans-serif;
            width: 100mm;
            margin: 0 auto;
        }
        h2, h3 {
            text-align: center;
            margin: 0;
        }
        .facture-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .factura-table th {
            background: #f2f2f2;
            font-weight: bold;
            border-bottom: 2px solid #ddd !important;
            text-align: center;
        }

        .factura-table td {
            border-bottom: 1px solid #eee !important;
            padding: 6px 4px !important;
            font-size: 14px;
        }
        .text-center {
            text-align: center !important;
        }
        .text-right {
            text-align: right !important;
        }
        .btn-print {
            margin-top: 10px;
            width: 100%;
            padding: 10px;
            background: black;
            color: white;
            border: none;
        }
    </style>

</head>
<body>
    <br>
    <h2>Cafetería Bello Horizonte</h2>
    <center><img src="assets/img/logo.jpg" alt="Logo" class="cafe-logo me-2" style="width:50px;height:auto;">
    <h3>Factura #<?php echo $idVenta; ?></h3>
    <p>Fecha de creación: <b><?php echo date('d-m-Y H:i', strtotime($venta['fechaVenta'])); ?></b></p></center>

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
