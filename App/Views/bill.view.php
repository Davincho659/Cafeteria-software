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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @media print {
            body {
                width: 80mm;
                margin: 0;
                padding: 0;
            }
            .btn-print {
                display: none;
            }
        }
        
        body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 5px;
            background: white;
            color: black;
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding: 5px 0;
            margin-bottom: 10px;
        }
        
        .receipt-header h2 {
            font-size: 14px;
            font-weight: bold;
            margin: 3px 0;
            line-height: 1.2;
        }
        
        .receipt-header .logo {
            width: 40px;
            height: auto;
            margin: 5px 0;
        }
        
        .receipt-header h3 {
            font-size: 12px;
            font-weight: bold;
            margin: 3px 0;
        }
        
        .receipt-header p {
            font-size: 11px;
            margin: 2px 0;
        }
        
        .receipt-info {
            font-size: 11px;
            margin: 5px 0;
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }
        
        .factura-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11px;
        }
        
        .factura-table th {
            background: transparent;
            font-weight: bold;
            border-bottom: 1px dashed #000;
            padding: 3px 2px;
            text-align: left;
        }
        
        .factura-table td {
            border-bottom: 1px solid #eee;
            padding: 3px 2px;
            font-size: 11px;
        }
        
        .product-name {
            width: 50%;
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .product-qty {
            width: 20%;
            text-align: center;
        }
        
        .product-total {
            width: 30%;
            text-align: right;
        }
        
        .total-section {
            border-top: 2px dashed #000;
            border-bottom: 2px dashed #000;
            text-align: right;
            padding: 8px 0;
            margin: 10px 0;
            font-size: 13px;
            font-weight: bold;
        }
        
        .total-amount {
            font-size: 16px;
            font-weight: bold;
            padding: 5px 0;
        }
        
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #000;
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
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }
        
        .btn-print:hover {
            background: #333;
        }

        /* Sello ANULADO */
        .sello-anulado-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            z-index: 999;
            pointer-events: none;
        }

        .sello-anulado {
            font-size: 48px;
            font-weight: bold;
            color: rgba(220, 53, 69, 0.3);
            border: 3px solid rgba(220, 53, 69, 0.3);
            padding: 10px 20px;
            letter-spacing: 3px;
            transform: rotate(0deg);
            text-transform: uppercase;
        }

        @media print {
            .sello-anulado {
                color: rgba(220, 53, 69, 0.15);
                border: 3px solid rgba(220, 53, 69, 0.15);
            }
        }
        
    </style>

</head>
<body>
    <div class="factura-contenedor" id="factura">
        <div class="receipt-header">
            <h2>Cafetería Bello Horizonte</h2>
            <img src="assets/img/logo.jpg" alt="Logo" class="logo">
            <h3>FACTURA #<?php echo str_pad($idVenta, 6, '0', STR_PAD_LEFT); ?></h3>
        </div>

        <div class="receipt-info">
            <p><?php echo date('d/m/Y H:i', strtotime($venta['fechaVenta'])); ?></p>
        </div>

        <table class="factura-table">
            <thead>
                <tr>
                    <th class="product-name">Producto</th>
                    <th class="product-qty">Cant.</th>
                    <th class="product-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($detalles as $d): ?>
                <tr>
                    <td class="product-name"><?php echo substr($d['producto_nombre'], 0, 20); ?></td>
                    <td class="product-qty"><?php echo $d['cantidad']; ?></td>
                    <td class="product-total">$<?php echo number_format($d['subTotal'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-section">
            <div>TOTAL FACTURA</div>
            <div class="total-amount">$<?php echo number_format($venta['total'], 0, ',', '.'); ?></div>
        </div>

        <div class="footer">
            <p>¡Gracias por su compra!</p>
            <p>Vuelva pronto</p>
        </div>
        
        <!-- Sello ANULADO si la venta está cancelada -->
        <?php if ($venta['estado'] === 'cancelada'): ?>
        <div class="sello-anulado-container">
            <div class="sello-anulado">ANULADO</div>
        </div>
        <?php endif; ?>
        
        <button class="btn-print" onclick="window.print()">Imprimir Factura</button>
    </div>
</body>
</html>
