<?php
require_once __DIR__ . '/../Core/Conexion.php';

/**
 * ============================================================================
 * CLOSINGS - Cierres financieros y de stock (día / mes / año)
 * ============================================================================
 * Todas las consultas trabajan sobre un rango de fechas [desde, hasta] para
 * que el mismo código sirva para el cierre diario, mensual y anual.
 *
 * REGLA IMPORTANTE: las ventas con estado 'cancelada' NUNCA suman como
 * ingreso. Se reportan aparte para poder auditarlas.
 * ============================================================================
 */
class Closings {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Resumen financiero del periodo.
     */
    public function getResumen($desde, $hasta) {
        // --- Ventas completadas y anuladas en una sola pasada ---
        $sqlVentas = "SELECT
                COALESCE(SUM(CASE WHEN estado='completada' THEN total ELSE 0 END), 0) AS totalVendido,
                COALESCE(SUM(CASE WHEN estado='completada' THEN 1 ELSE 0 END), 0)     AS cantidadVentas,
                COALESCE(SUM(CASE WHEN estado='cancelada'  THEN total ELSE 0 END), 0) AS totalAnulado,
                COALESCE(SUM(CASE WHEN estado='cancelada'  THEN 1 ELSE 0 END), 0)     AS cantidadAnuladas,
                COALESCE(SUM(CASE WHEN estado='completada' AND metodoPago='efectivo'    THEN total ELSE 0 END), 0) AS totalEfectivo,
                COALESCE(SUM(CASE WHEN estado='completada' AND metodoPago='bancolombia' THEN total ELSE 0 END), 0) AS totalBancolombia,
                COALESCE(SUM(CASE WHEN estado='completada' AND metodoPago='nequi'       THEN total ELSE 0 END), 0) AS totalNequi,
                -- 'transferencias' = Bancolombia + Nequi + histórico 'transferencia'
                COALESCE(SUM(CASE WHEN estado='completada' AND metodoPago IN ('bancolombia','nequi','transferencia') THEN total ELSE 0 END), 0) AS totalTransferencia
            FROM ventas
            WHERE DATE(fechaVenta) BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sqlVentas);
        $stmt->execute([$desde, $hasta]);
        $ventas = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // --- Costo de la mercancía vendida ---
        // Se usa el costo promedio histórico de compra; si el producto nunca se
        // ha comprado, se cae al precioCompra registrado en el producto.
        $sqlCosto = "SELECT COALESCE(SUM(dv.cantidad * COALESCE(cp.costoPromedio, p.precioCompra, 0)), 0) AS costoVentas
            FROM detalle_venta dv
            INNER JOIN ventas v ON v.idVenta = dv.idVenta
            LEFT JOIN productos p ON p.idProducto = dv.idProducto
            LEFT JOIN (
                SELECT idProducto, SUM(cantidad * precioUnitario) / NULLIF(SUM(cantidad), 0) AS costoPromedio
                FROM historial_precio_compra
                GROUP BY idProducto
            ) cp ON cp.idProducto = dv.idProducto
            WHERE v.estado = 'completada' AND DATE(v.fechaVenta) BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sqlCosto);
        $stmt->execute([$desde, $hasta]);
        $costoVentas = (float)($stmt->fetchColumn() ?: 0);

        // --- Compras del periodo ---
        $sqlCompras = "SELECT COALESCE(SUM(total), 0) AS totalCompras, COUNT(*) AS cantidadCompras
            FROM compras WHERE DATE(fechaCompra) BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sqlCompras);
        $stmt->execute([$desde, $hasta]);
        $compras = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // --- Gastos del periodo ---
        $sqlGastos = "SELECT COALESCE(SUM(monto), 0) AS totalGastos, COUNT(*) AS cantidadGastos
            FROM gastos WHERE DATE(fechaRegistro) BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sqlGastos);
        $stmt->execute([$desde, $hasta]);
        $gastos = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalVendido = (float)($ventas['totalVendido'] ?? 0);
        $cantidadVentas = (int)($ventas['cantidadVentas'] ?? 0);
        $totalGastos = (float)($gastos['totalGastos'] ?? 0);

        $utilidadBruta = $totalVendido - $costoVentas;
        $utilidadNeta  = $utilidadBruta - $totalGastos;

        return [
            'desde'              => $desde,
            'hasta'              => $hasta,
            'totalVendido'       => $totalVendido,
            'cantidadVentas'     => $cantidadVentas,
            'ticketPromedio'     => $cantidadVentas > 0 ? $totalVendido / $cantidadVentas : 0,
            'totalAnulado'       => (float)($ventas['totalAnulado'] ?? 0),
            'cantidadAnuladas'   => (int)($ventas['cantidadAnuladas'] ?? 0),
            'totalEfectivo'      => (float)($ventas['totalEfectivo'] ?? 0),
            'totalBancolombia'   => (float)($ventas['totalBancolombia'] ?? 0),
            'totalNequi'         => (float)($ventas['totalNequi'] ?? 0),
            'totalTransferencia' => (float)($ventas['totalTransferencia'] ?? 0),
            'costoVentas'        => $costoVentas,
            'totalCompras'       => (float)($compras['totalCompras'] ?? 0),
            'cantidadCompras'    => (int)($compras['cantidadCompras'] ?? 0),
            'totalGastos'        => $totalGastos,
            'cantidadGastos'     => (int)($gastos['cantidadGastos'] ?? 0),
            'utilidadBruta'      => $utilidadBruta,
            'utilidadNeta'       => $utilidadNeta,
            'margenPorcentaje'   => $totalVendido > 0 ? ($utilidadNeta / $totalVendido) * 100 : 0,
        ];
    }

    /**
     * Serie temporal para ver la evolución dentro del periodo.
     *
     * @param string $granularidad 'dia' | 'mes'
     */
    public function getSerie($desde, $hasta, $granularidad = 'dia') {
        // El formato se elige aquí (lista blanca), nunca desde la petición.
        $formato = ($granularidad === 'mes') ? '%Y-%m' : '%Y-%m-%d';

        $sql = "SELECT
                    DATE_FORMAT(v.fechaVenta, '{$formato}') AS periodo,
                    COALESCE(SUM(CASE WHEN v.estado='completada' THEN v.total ELSE 0 END), 0) AS totalVendido,
                    COALESCE(SUM(CASE WHEN v.estado='completada' THEN 1 ELSE 0 END), 0)       AS cantidadVentas
                FROM ventas v
                WHERE DATE(v.fechaVenta) BETWEEN ? AND ?
                GROUP BY periodo
                ORDER BY periodo ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cajas abiertas/cerradas en el periodo, con el descuadre de cada una.
     * Es el corazón del "cierre de caja": permite ver si faltó o sobró dinero.
     */
    public function getCajas($desde, $hasta) {
        // Al cerrar la caja se cuenta el dinero FÍSICO del cajón. Por eso el
        // arqueo debe compararse solo contra el efectivo: lo cobrado por Nequi o
        // Bancolombia nunca entró al cajón, entró a la cuenta bancaria.
        //
        // Antes se comparaba el efectivo contado contra el total de movimientos
        // (efectivo + transferencias), así que el reporte siempre reportaba un
        // faltante exactamente igual al total transferido, aunque la caja
        // estuviera perfecta.
        $ventaValida = "EXISTS (SELECT 1 FROM ventas v WHERE v.idVenta = mc.referencia AND v.estado = 'completada')";
        $esEfectivo  = "(mc.metodoPago IS NULL OR mc.metodoPago = 'efectivo')";

        $sql = "SELECT
                    c.idCaja,
                    c.fechaApertura,
                    c.fechaCierre,
                    c.estado,
                    c.saldoInicial,
                    c.saldoReal,
                    c.notas,
                    u.nombre AS usuario,

                    -- Movimientos que SÍ pasaron por el cajón (efectivo)
                    COALESCE(SUM(CASE
                        WHEN mc.tipo_movimiento = 'VENTA' AND {$ventaValida} AND {$esEfectivo}
                            THEN mc.monto
                        WHEN mc.tipo_movimiento <> 'VENTA' AND {$esEfectivo}
                            THEN mc.monto
                        ELSE 0 END), 0) AS movimientosEfectivo,

                    -- Cobrado por transferencia (Nequi/Bancolombia): va a la cuenta,
                    -- se muestra aparte para poder confrontarlo con el banco.
                    COALESCE(SUM(CASE
                        WHEN mc.tipo_movimiento = 'VENTA' AND {$ventaValida} AND NOT {$esEfectivo}
                            THEN mc.monto
                        ELSE 0 END), 0) AS totalTransferencias,

                    -- Total del turno, sin importar el medio de pago
                    COALESCE(SUM(CASE
                        WHEN mc.tipo_movimiento = 'VENTA' AND {$ventaValida} THEN mc.monto
                        WHEN mc.tipo_movimiento <> 'VENTA' THEN mc.monto
                        ELSE 0 END), 0) AS totalMovimientos
                FROM cajas c
                LEFT JOIN usuarios u ON u.idUsuario = c.idUsuario
                LEFT JOIN movimientos_caja mc ON mc.idCaja = c.idCaja
                WHERE DATE(c.fechaApertura) BETWEEN ? AND ?
                GROUP BY c.idCaja, c.fechaApertura, c.fechaCierre, c.estado,
                         c.saldoInicial, c.saldoReal, c.notas, u.nombre
                ORDER BY c.fechaApertura ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$desde, $hasta]);
        $cajas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cajas as &$caja) {
            // Lo que debe haber en el cajón al contar los billetes.
            $caja['efectivoEsperado'] = (float)$caja['saldoInicial'] + (float)$caja['movimientosEfectivo'];

            // Se conserva el nombre anterior por compatibilidad con la vista,
            // pero ahora significa "efectivo esperado", que es contra lo que
            // realmente se cuenta.
            $caja['saldoCalculado'] = $caja['efectivoEsperado'];

            // Total del turno incluyendo transferencias (informativo).
            $caja['totalTurno'] = (float)$caja['saldoInicial'] + (float)$caja['totalMovimientos'];

            $caja['diferencia'] = ($caja['saldoReal'] === null)
                ? null
                : (float)$caja['saldoReal'] - $caja['efectivoEsperado'];
        }

        return $cajas;
    }

    /**
     * Productos más vendidos del periodo (solo ventas completadas).
     *
     * Los "montos manuales" (líneas sin idProducto) quedan FUERA: no son un
     * producto del catálogo, solo una cifra que se cobra suelta. Si se
     * incluyeran, encabezarían el ranking y ensuciarían la lectura de qué se
     * vende de verdad. Su dinero sí cuenta en el total de ventas.
     */
    public function getTopProductos($desde, $hasta, $limit = 10) {
        $limit = max(1, min(100, (int)$limit)); // se interpola: forzar entero seguro

        $sql = "SELECT
                    p.idProducto,
                    p.nombre AS nombre,
                    cat.nombre AS categoria,
                    SUM(dv.cantidad)  AS cantidadVendida,
                    SUM(dv.subTotal)  AS totalVendido
                FROM detalle_venta dv
                INNER JOIN ventas v ON v.idVenta = dv.idVenta
                INNER JOIN productos p  ON p.idProducto = dv.idProducto
                LEFT JOIN categorias cat ON cat.idCategoria = p.idCategoria
                WHERE v.estado = 'completada'
                  AND DATE(v.fechaVenta) BETWEEN ? AND ?
                  AND dv.idProducto IS NOT NULL
                GROUP BY p.idProducto, p.nombre, cat.nombre
                ORDER BY totalVendido DESC
                LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Foto del inventario al momento de consultar (valorización y faltantes).
     */
    public function getStockResumen($stockMinimo = 5) {
        $stockMinimo = (float)$stockMinimo;

        $sql = "SELECT
                    COUNT(*) AS productosConStock,
                    COALESCE(SUM(stockActual * precioCompra), 0) AS valorCosto,
                    COALESCE(SUM(stockActual * precioVenta), 0)  AS valorVenta,
                    COALESCE(SUM(CASE WHEN stockActual <= 0 THEN 1 ELSE 0 END), 0) AS productosAgotados,
                    COALESCE(SUM(CASE WHEN stockActual > 0 AND stockActual <= ? THEN 1 ELSE 0 END), 0) AS productosBajos
                FROM vista_stock_actual";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$stockMinimo]);
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Detalle de los que hay que reponer
        $sqlDetalle = "SELECT producto, categoria, stockActual, unidadAbreviatura, precioCompra
                       FROM vista_stock_actual
                       WHERE stockActual <= ?
                       ORDER BY stockActual ASC
                       LIMIT 50";
        $stmt = $this->db->prepare($sqlDetalle);
        $stmt->execute([$stockMinimo]);
        $resumen['detalleReponer'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $resumen;
    }

    /**
     * Últimas ventas completadas (para el dashboard).
     */
    public function getVentasRecientes($limit = 8) {
        $limit = max(1, min(50, (int)$limit));
        $sql = "SELECT v.idVenta, v.total, v.metodoPago, v.fechaVenta,
                       u.nombre AS usuario,
                       (SELECT COUNT(*) FROM detalle_venta dv WHERE dv.idVenta = v.idVenta) AS items
                FROM ventas v
                LEFT JOIN usuarios u ON u.idUsuario = v.idUsuario
                WHERE v.estado = 'completada'
                ORDER BY v.fechaVenta DESC
                LIMIT {$limit}";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ventas agrupadas por hora del día (solo completadas).
     * Sirve para saber las horas pico y organizar personal y preparación.
     * Devuelve siempre las 24 horas (rellena con 0 las que no tienen ventas).
     */
    public function getVentasPorHora($desde, $hasta) {
        $sql = "SELECT HOUR(fechaVenta) AS hora,
                       COUNT(*) AS cantidadVentas,
                       COALESCE(SUM(total), 0) AS totalVendido
                FROM ventas
                WHERE estado = 'completada' AND DATE(fechaVenta) BETWEEN ? AND ?
                GROUP BY HOUR(fechaVenta)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$desde, $hasta]);

        // Indexar por hora para rellenar las 24
        $porHora = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $porHora[(int)$row['hora']] = $row;
        }

        $resultado = [];
        for ($h = 0; $h < 24; $h++) {
            $resultado[] = [
                'hora'           => $h,
                'cantidadVentas' => isset($porHora[$h]) ? (int)$porHora[$h]['cantidadVentas'] : 0,
                'totalVendido'   => isset($porHora[$h]) ? (float)$porHora[$h]['totalVendido'] : 0.0,
            ];
        }
        return $resultado;
    }

    /**
     * Movimientos de inventario del periodo (trazabilidad de entradas/salidas).
     */
    public function getMovimientosInventario($desde, $hasta) {
        $sql = "SELECT
                    tipoMovimiento,
                    COUNT(*) AS cantidadMovimientos,
                    COALESCE(SUM(cantidad), 0) AS unidades
                FROM inventario
                WHERE DATE(fechaMovimiento) BETWEEN ? AND ?
                GROUP BY tipoMovimiento";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
