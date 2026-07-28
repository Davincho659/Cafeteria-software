<?php
require_once __DIR__ . '/../Models/sales.php';
require_once __DIR__ . '/../Models/Settings.php';

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

// Datos del negocio desde Configuración (no quemados en el código): así el
// mismo sistema sirve para cualquier negocio sin tocar el ticket.
try {
    $cfg = (new Settings())->getAll();
} catch (Throwable $e) {
    $cfg = [];
}
$negocio  = $cfg['nombre_negocio']  ?? 'POS';
$logo     = $cfg['logo']            ?? 'logo.jpg';
$nit      = trim((string)($cfg['nit']       ?? ''));
$direccion= trim((string)($cfg['direccion'] ?? ''));
$telefono = trim((string)($cfg['telefono']  ?? ''));
$msgPie   = $cfg['mensaje_factura'] ?? '¡Gracias por su compra!';
$msgPie2  = $cfg['mensaje_pie']     ?? 'Vuelva pronto';

// Nombres legibles de los métodos de pago
$metodos = [
    'efectivo'      => 'Efectivo',
    'bancolombia'   => 'Bancolombia',
    'nequi'         => 'Nequi',
    'transferencia' => 'Transferencia',
];
$metodoPago = $metodos[$venta['metodoPago'] ?? ''] ?? ucfirst((string)($venta['metodoPago'] ?? ''));

// Total de artículos (unidades vendidas), dato estándar en un tiquete
$totalItems = 0;
foreach ($detalles as $d) {
    $totalItems += (float)$d['cantidad'];
}

/** Formatea pesos sin decimales: 12.500 */
function tk_money($n) {
    return number_format((float)$n, 0, ',', '.');
}
/** Cantidad: entera si es entera (2), decimal si no (0,5 kg) */
function tk_qty($n) {
    $n = (float)$n;
    return ($n == floor($n)) ? (string)(int)$n : rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
}

// Con ?print=1 se abre directamente el diálogo de impresión.
$autoPrint = isset($_GET['print']) && $_GET['print'] === '1';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #<?php echo $idVenta; ?></title>
    <style>
        /* =====================================================================
           TIQUETE TÉRMICO 80mm
           ---------------------------------------------------------------------
           El área imprimible real de una impresora de 80mm es ~72mm (el resto
           es margen mecánico del papel). Por eso el ancho es 72mm y NO 80mm:
           si se usa 80mm el texto se sale y la tirilla sale descuadrada.
           @page margin:0 quita los márgenes que el navegador agrega por defecto,
           que era lo que empujaba el contenido y lo desalineaba al imprimir.
           ===================================================================== */
        @page {
            size: 80mm auto;   /* rollo continuo: alto automático */
            margin: 0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            /* Monoespaciada: es la única forma de que las columnas queden
               alineadas en una impresora térmica. */
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.35;
            color: #000;
            background: #fff;
            width: 72mm;
            margin: 0 auto;
            padding: 2mm;
            -webkit-font-smoothing: none;
        }

        /* En pantalla se ve como un papelito centrado sobre fondo gris */
        @media screen {
            html { background: #9e9e9e; padding: 16px 0; }
            body { box-shadow: 0 2px 12px rgba(0,0,0,.35); padding: 4mm 3mm; }
        }

        .center { text-align: center; }
        .right  { text-align: right; }
        .bold   { font-weight: bold; }

        /* Separadores: guiones reales imprimen mejor que border en térmicas */
        .sep {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .sep-strong { border-top: 2px solid #000; margin: 6px 0; }

        .logo { max-width: 26mm; height: auto; margin: 0 auto 4px; display: block; filter: grayscale(1) contrast(1.4); }

        .negocio { font-size: 15px; font-weight: bold; line-height: 1.2; }
        .datos-negocio { font-size: 11px; }

        .doc-title { font-size: 13px; font-weight: bold; margin-top: 4px; }

        /* Meta (fecha, cajero, mesa): etiqueta a la izquierda, valor a la derecha */
        .meta { font-size: 11px; }
        .meta-row { display: flex; justify-content: space-between; gap: 4px; }
        .meta-row span:last-child { text-align: right; }

        /* ---- Tabla de productos ----
           Se usa una fila de 2 líneas por producto:
             línea 1: nombre completo (puede envolver, NO se corta)
             línea 2: "cant x precio"        subtotal (alineado a la derecha)
           Así caben nombres largos sin descuadrar las columnas. */
        .items { width: 100%; margin: 4px 0; }
        .item { margin-bottom: 5px; }
        .item-nombre { word-wrap: break-word; overflow-wrap: break-word; }
        .item-calc {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            font-size: 11px;
        }
        .item-calc .precio-unit { white-space: nowrap; }
        .item-calc .subtotal { white-space: nowrap; font-weight: bold; }

        .totales { font-size: 12px; }
        .total-row { display: flex; justify-content: space-between; gap: 6px; }
        .total-final {
            display: flex;
            justify-content: space-between;
            font-size: 16px;
            font-weight: bold;
            padding: 4px 0;
        }

        .footer { font-size: 11px; margin-top: 6px; }
        .anulado-txt {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            border: 2px solid #000;
            padding: 4px;
            margin: 6px 0;
            letter-spacing: 3px;
        }

        /* Botones: solo en pantalla, nunca en el papel */
        .receipt-actions { display: flex; gap: 6px; margin-top: 12px; }
        .btn-print, .btn-close {
            flex: 1; padding: 12px 10px; border: none;
            cursor: pointer; font-size: 12px; font-weight: bold; font-family: inherit;
        }
        .btn-print { background: #000; color: #fff; }
        .btn-close { background: #ddd; color: #000; }
        .hint { text-align: center; font-size: 10px; color: #666; margin-top: 6px; }

        @media print {
            html { background: #fff; padding: 0; }
            body { box-shadow: none; padding: 0 2mm; }
            .receipt-actions, .hint { display: none !important; }
            /* Corte limpio: evita que la última línea quede en otra tirilla */
            .cut-space { height: 10mm; }
        }
    </style>
</head>
<body>

    <!-- ENCABEZADO DEL NEGOCIO (desde Configuración) -->
    <div class="center">
        <?php if (!empty($logo)): ?>
            <img src="<?= asset('assets/img/' . $logo) ?>" alt="" class="logo">
        <?php endif; ?>
        <div class="negocio"><?= esc($negocio) ?></div>
        <div class="datos-negocio">
            <?php if ($nit !== ''): ?><div>NIT: <?= esc($nit) ?></div><?php endif; ?>
            <?php if ($direccion !== ''): ?><div><?= esc($direccion) ?></div><?php endif; ?>
            <?php if ($telefono !== ''): ?><div>Tel: <?= esc($telefono) ?></div><?php endif; ?>
        </div>
        <div class="doc-title">FACTURA DE VENTA</div>
        <div class="bold">N° <?= str_pad($idVenta, 6, '0', STR_PAD_LEFT) ?></div>
    </div>

    <div class="sep"></div>

    <!-- DATOS DE LA VENTA -->
    <div class="meta">
        <div class="meta-row">
            <span>Fecha:</span>
            <span><?= date('d/m/Y', strtotime($venta['fechaVenta'])) ?></span>
        </div>
        <div class="meta-row">
            <span>Hora:</span>
            <span><?= date('h:i A', strtotime($venta['fechaVenta'])) ?></span>
        </div>
        <?php if (!empty($venta['usuario_nombre'])): ?>
        <div class="meta-row">
            <span>Atendido por:</span>
            <span><?= esc($venta['usuario_nombre']) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($venta['mesa_numero'])): ?>
        <div class="meta-row">
            <span>Mesa:</span>
            <span>N° <?= esc($venta['mesa_numero']) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="sep"></div>

    <!-- PRODUCTOS -->
    <div class="items">
        <?php foreach ($detalles as $d): ?>
            <div class="item">
                <div class="item-nombre"><?= esc($d['producto_nombre']) ?></div>
                <div class="item-calc">
                    <span class="precio-unit">
                        <?= tk_qty($d['cantidad']) ?> x <?= tk_money($d['precioUnitario']) ?>
                    </span>
                    <span class="subtotal">$<?= tk_money($d['subTotal']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="sep"></div>

    <!-- TOTALES -->
    <div class="totales">
        <div class="total-row">
            <span>Artículos:</span>
            <span><?= tk_qty($totalItems) ?></span>
        </div>
    </div>

    <div class="sep-strong"></div>

    <div class="total-final">
        <span>TOTAL</span>
        <span>$<?= tk_money($venta['total']) ?></span>
    </div>

    <div class="sep-strong"></div>

    <div class="totales">
        <div class="total-row">
            <span>Forma de pago:</span>
            <span class="bold"><?= esc($metodoPago) ?></span>
        </div>
    </div>

    <?php if (($venta['estado'] ?? '') === 'cancelada'): ?>
        <div class="anulado-txt">** ANULADO **</div>
    <?php endif; ?>

    <div class="sep"></div>

    <!-- PIE -->
    <div class="footer center">
        <div class="bold"><?= esc($msgPie) ?></div>
        <?php if ($msgPie2 !== ''): ?><div><?= esc($msgPie2) ?></div><?php endif; ?>
        <div style="margin-top:6px; font-size:10px;">
            <?= date('d/m/Y H:i', strtotime($venta['fechaVenta'])) ?> · #<?= $idVenta ?>
        </div>
    </div>

    <div class="cut-space"></div>

    <!-- Controles (no se imprimen) -->
    <div class="receipt-actions">
        <button class="btn-print" onclick="window.print()">Imprimir</button>
        <button class="btn-close" onclick="window.close()">Cerrar</button>
    </div>
    <p class="hint">Enter = imprimir &nbsp;·&nbsp; Esc = cerrar</p>

    <script>
        // La ventana se abre enfocada para poder imprimir sin usar el mouse:
        // es clave en hora pico, donde se cobra con el teclado o la pantalla táctil.
        window.focus();

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === 'p' || e.key === 'P') {
                e.preventDefault();
                window.print();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                window.close();
            }
        });

        <?php if ($autoPrint): ?>
        // Se espera a que carguen el logo y las fuentes para que el ticket
        // no salga cortado en la impresora térmica.
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 250);
        });
        <?php endif; ?>
    </script>
</body>
</html>
