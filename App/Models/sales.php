<?php
require_once __DIR__ . '/../Core/Conexion.php';
require_once __DIR__ . '/inventory.php';
require_once __DIR__ . '/cashRegister.php';

class Sales {
    private $db;
    private $inventoryModel;
    private $cashRegister;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->inventoryModel = new Inventory();
        $this->cashRegister = new CashRegister();
    }

    // ELIMINADO: createSaleWithDetails - Reemplazado por flujo: createPendingSale() + addOrUpdateProductToSale() + completeSale()

    // ELIMINADO: createSalesDetail() - Lógica integrada en addOrUpdateProductToSale()

    public function getSaleById($idVenta) {
        $sql = "SELECT v.*, t.numero AS mesa_numero, u.nombre AS usuario_nombre 
                FROM ventas v 
                LEFT JOIN mesas t ON v.idMesa = t.idMesa 
                LEFT JOIN usuarios u ON v.idUsuario = u.idUsuario 
                WHERE v.idVenta = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idVenta]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSaleDetails($idVenta) {
        $sql = "SELECT dv.idDetalleVenta, dv.idProducto, COALESCE(p.nombre, 'Producto') AS producto_nombre,
                       p.imagen AS producto_imagen,
                       dv.cantidad, dv.precioUnitario, dv.subTotal
                FROM detalle_venta dv
                LEFT JOIN productos p ON dv.idProducto = p.idProducto
                WHERE dv.idVenta = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idVenta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Totales de rentabilidad de un rango de fechas en UNA sola consulta.
     *
     * - totalVentas: lo facturado (solo ventas completadas; las anuladas no cuentan).
     * - totalCostos: costo de la mercancía vendida, usando el costo promedio
     *   ponderado de cada producto (de historial_precio_compra). Si un producto
     *   nunca se ha comprado, se cae al precioCompra del producto, y si tampoco
     *   hay, cuenta 0 (mejor 0 que inventar un costo).
     *
     * Los "montos manuales" (detalle sin idProducto) suman a las ventas pero no
     * tienen costo asociado, que es el comportamiento correcto.
     */
    public function getProfitabilityTotals($fechaDesde, $fechaHasta) {
        $sql = "SELECT
                    COALESCE((
                        SELECT SUM(v.total)
                        FROM ventas v
                        WHERE v.estado = 'completada'
                          AND DATE(v.fechaVenta) BETWEEN ? AND ?
                    ), 0) AS totalVentas,
                    COALESCE((
                        SELECT SUM(dv.cantidad * COALESCE(cp.costoPromedio, p.precioCompra, 0))
                        FROM detalle_venta dv
                        INNER JOIN ventas v2 ON v2.idVenta = dv.idVenta
                        LEFT JOIN productos p ON p.idProducto = dv.idProducto
                        LEFT JOIN (
                            SELECT h.idProducto,
                                   SUM(h.cantidad * h.precioUnitario) / NULLIF(SUM(h.cantidad), 0) AS costoPromedio
                            FROM historial_precio_compra h
                            GROUP BY h.idProducto
                        ) cp ON cp.idProducto = dv.idProducto
                        WHERE v2.estado = 'completada'
                          AND DATE(v2.fechaVenta) BETWEEN ? AND ?
                          AND dv.idProducto IS NOT NULL
                    ), 0) AS totalCostos";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fechaDesde, $fechaHasta, $fechaDesde, $fechaHasta]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'totalVentas' => (float)($row['totalVentas'] ?? 0),
            'totalCostos' => (float)($row['totalCostos'] ?? 0),
        ];
    }

    public function filter($filter) {
        $sql = "SELECT * FROM ventas WHERE date(fechaVenta) = CURDATE()";
        $result = $sql . $filter;
        $stmt = $this->db->query($result);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        return $this->get();
    }

    public function getByDay() {
        return $this->get(" WHERE DATE(fechaVenta) = CURDATE()");
    }

    public function getSalesByDate($fecha) {
        $sql = "SELECT * FROM ventas WHERE DATE(fechaVenta) = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fecha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWithPagination($filterQuery = "", $offset = 0, $limit = 10) {
        $sql = "SELECT * FROM ventas ";
        $sql .= $filterQuery;
        $sql .= " ORDER BY fechaVenta DESC LIMIT $offset, $limit";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Igual que getWithPagination pero con placeholders (seguro contra
     * inyección). $whereSql debe empezar con " WHERE ..." y $params son sus
     * valores. offset/limit se castean a entero.
     */
    public function getPaginatedFiltered($whereSql, array $params, $offset = 0, $limit = 10) {
        $offset = max(0, (int) $offset);
        $limit = max(1, (int) $limit);
        $sql = "SELECT * FROM ventas" . $whereSql
             . " ORDER BY fechaVenta DESC LIMIT {$offset}, {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Totales del conjunto filtrado, versión con placeholders (segura).
     * Espejo de getTotalsWithFilters pero para consultas preparadas.
     */
    public function getTotalsFiltered($whereSql, array $params) {
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN estado='completada' THEN total ELSE 0 END), 0) AS totalVendido,
                    COALESCE(SUM(CASE WHEN estado='completada' THEN 1 ELSE 0 END), 0)     AS cantidadVentas,
                    COALESCE(SUM(CASE WHEN estado='cancelada'  THEN total ELSE 0 END), 0) AS totalAnulado,
                    COALESCE(SUM(CASE WHEN estado='cancelada'  THEN 1 ELSE 0 END), 0)     AS cantidadAnuladas,
                    COALESCE(SUM(CASE WHEN estado='completada' AND metodoPago='efectivo'    THEN total ELSE 0 END), 0) AS totalEfectivo,
                    COALESCE(SUM(CASE WHEN estado='completada' AND metodoPago='bancolombia' THEN total ELSE 0 END), 0) AS totalBancolombia,
                    COALESCE(SUM(CASE WHEN estado='completada' AND metodoPago='nequi'       THEN total ELSE 0 END), 0) AS totalNequi,
                    COALESCE(SUM(CASE WHEN estado='completada' AND metodoPago IN ('bancolombia','nequi','transferencia') THEN total ELSE 0 END), 0) AS totalTransferencia
                FROM ventas" . $whereSql;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'totalVendido'       => (float)($row['totalVendido'] ?? 0),
            'cantidadVentas'     => (int)($row['cantidadVentas'] ?? 0),
            'totalAnulado'       => (float)($row['totalAnulado'] ?? 0),
            'cantidadAnuladas'   => (int)($row['cantidadAnuladas'] ?? 0),
            'totalEfectivo'      => (float)($row['totalEfectivo'] ?? 0),
            'totalBancolombia'   => (float)($row['totalBancolombia'] ?? 0),
            'totalNequi'         => (float)($row['totalNequi'] ?? 0),
            'totalTransferencia' => (float)($row['totalTransferencia'] ?? 0),
        ];
    }

    /** Conteo con los mismos filtros preparados. */
    public function countFiltered($whereSql, array $params) {
        $sql = "SELECT COUNT(*) AS total FROM ventas" . $whereSql;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function countWithFilters($filterQuery = "") {
        $sql = "SELECT COUNT(*) as total FROM ventas";
        $sql .= $filterQuery;
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * Totales de TODO el conjunto filtrado (no solo la página visible).
     *
     * Recibe el MISMO $filterQuery (" WHERE ...") que la paginación, así la
     * fila de totales refleja exactamente lo consultado (día o rango, método,
     * estado, etc.). Las ventas anuladas se totalizan aparte y nunca suman como
     * dinero vendido. Se resuelve en una sola consulta (CASE) por eficiencia.
     */
    public function getTotalsWithFilters($filterQuery = "") {
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN estado='completada' THEN total ELSE 0 END), 0) AS totalVendido,
                    COALESCE(SUM(CASE WHEN estado='completada' THEN 1 ELSE 0 END), 0)     AS cantidadVentas,
                    COALESCE(SUM(CASE WHEN estado='cancelada'  THEN total ELSE 0 END), 0) AS totalAnulado,
                    COALESCE(SUM(CASE WHEN estado='cancelada'  THEN 1 ELSE 0 END), 0)     AS cantidadAnuladas,
                    COALESCE(SUM(CASE WHEN estado='completada' AND metodoPago='efectivo'    THEN total ELSE 0 END), 0) AS totalEfectivo,
                    COALESCE(SUM(CASE WHEN estado='completada' AND metodoPago='bancolombia' THEN total ELSE 0 END), 0) AS totalBancolombia,
                    COALESCE(SUM(CASE WHEN estado='completada' AND metodoPago='nequi'       THEN total ELSE 0 END), 0) AS totalNequi,
                    COALESCE(SUM(CASE WHEN estado='completada' AND metodoPago IN ('bancolombia','nequi','transferencia') THEN total ELSE 0 END), 0) AS totalTransferencia
                FROM ventas";
        $sql .= $filterQuery;
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'totalVendido'       => (float)($row['totalVendido'] ?? 0),
            'cantidadVentas'     => (int)($row['cantidadVentas'] ?? 0),
            'totalAnulado'       => (float)($row['totalAnulado'] ?? 0),
            'cantidadAnuladas'   => (int)($row['cantidadAnuladas'] ?? 0),
            'totalEfectivo'      => (float)($row['totalEfectivo'] ?? 0),
            'totalBancolombia'   => (float)($row['totalBancolombia'] ?? 0),
            'totalNequi'         => (float)($row['totalNequi'] ?? 0),
            'totalTransferencia' => (float)($row['totalTransferencia'] ?? 0),
        ];
    }

    public function get($consult = null) {
        $sql = "SELECT * FROM ventas";
        if ($consult) {
            $sql .= " " . $consult;
        }
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener totales del día (para reportes)
     */
    public function getTodayTotals() {
        $sql = "SELECT 
                    COUNT(*) as totalVentas,
                    COALESCE(SUM(total), 0) as totalMonto,
                    COALESCE(SUM(CASE WHEN tipoVenta = 'detallada' THEN total ELSE 0 END), 0) as totalDetalladas,
                    COALESCE(SUM(CASE WHEN tipoVenta = 'rapida' THEN total ELSE 0 END), 0) as totalRapidas
                FROM ventas 
                WHERE DATE(fechaVenta) = CURDATE() AND estado = 'completada'";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener productos más vendidos del día
     */
    public function getTopProductsToday($limit = 10) {
        // El límite se interpola como entero, no como parámetro: PDO envía los
        // parámetros como texto y MariaDB rechaza LIMIT '10' con un error de
        // sintaxis, que dejaba el reporte de más vendidos caído por completo.
        $limit = max(1, min(100, (int) $limit));

        $sql = "SELECT
                    p.idProducto,
                    p.nombre,
                    c.nombre as categoria,
                    SUM(dv.cantidad) as totalVendido,
                    SUM(dv.subTotal) as ingresoGenerado
                FROM detalle_venta dv
                INNER JOIN ventas v ON dv.idVenta = v.idVenta
                INNER JOIN productos p ON dv.idProducto = p.idProducto
                LEFT JOIN categorias c ON p.idCategoria = c.idCategoria
                WHERE DATE(v.fechaVenta) = CURDATE() AND v.estado = 'completada'
                GROUP BY p.idProducto, p.nombre, c.nombre
                ORDER BY totalVendido DESC
                LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ventas COMPLETADAS de un rango, para el reporte contable.
     * Incluye método de pago, cantidad de ítems y usuario.
     */
    public function getCompletedByRange($fechaDesde, $fechaHasta) {
        $sql = "SELECT v.idVenta, v.fechaVenta, v.metodoPago, v.tipoVenta, v.total,
                       u.nombre AS usuario,
                       (SELECT COUNT(*) FROM detalle_venta dv WHERE dv.idVenta = v.idVenta) AS items
                FROM ventas v
                LEFT JOIN usuarios u ON u.idUsuario = v.idUsuario
                WHERE v.estado = 'completada' AND DATE(v.fechaVenta) BETWEEN ? AND ?
                ORDER BY v.fechaVenta ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fechaDesde, $fechaHasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ventas ANULADAS de un rango (para dejar constancia en el reporte contable;
     * no suman como ingreso).
     */
    public function getCanceledByRange($fechaDesde, $fechaHasta) {
        $sql = "SELECT v.idVenta, v.fechaVenta, v.metodoPago, v.total, v.descripcion
                FROM ventas v
                WHERE v.estado = 'cancelada' AND DATE(v.fechaVenta) BETWEEN ? AND ?
                ORDER BY v.fechaVenta ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fechaDesde, $fechaHasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener ventas por rango de fechas
     */
    public function getSalesByDateRange($fechaDesde, $fechaHasta) {
        $sql = "SELECT * FROM ventas 
                WHERE DATE(fechaVenta) BETWEEN ? AND ?
                ORDER BY fechaVenta DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fechaDesde, $fechaHasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    // ============================================================================
    // FLUJO GENÉRICO DE VENTAS PENDIENTES (MESAS Y MOSTRADOR)
    // ============================================================================

    /**
     * Crear venta pendiente (vacía, sin productos)
     * Reutilizable para:
     * - Mesas: createPendingSale(idMesa=5, idUsuario=1)
     * - Mostrador/normal: createPendingSale(idMesa=null, idUsuario=1)
     * 
     * Si ya existe venta pendiente para esa mesa, la retorna
     */
    public function createPendingSale($idMesa = null, $idUsuario = null) {
        try {
            // Si es mesa, buscar si ya existe venta pendiente
            if ($idMesa !== null) {
                $sql = "SELECT idVenta FROM ventas 
                        WHERE idMesa = ? AND estado = 'pendiente' 
                        LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$idMesa]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    return $existing['idVenta'];
                }
            }
            
            // Crear nueva venta pendiente
            $tipoVenta = ($idMesa !== null) ? 'mesa' : 'venta';
            $sql = "INSERT INTO ventas 
                    (idMesa, estado, metodoPago, total, idUsuario, tipoVenta, fechaCreacion, fechaActualizacion) 
                    VALUES (?, 'pendiente', '', 0, ?, ?, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idMesa, $idUsuario, $tipoVenta]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error al crear venta pendiente: " . $e->getMessage());
        }
    }

    /**
     * Agregar o actualizar producto en una venta
     * Si el producto ya existe, incrementa cantidad
     * Si no existe, lo agrega
     */
    public function addOrUpdateProductToSale($idVenta, $idProducto, $cantidad, $precioUnitario, $idUsuario = null) {
        // La venta debe existir y seguir abierta. Sin esta comprobación el
        // INSERT fallaba con un error críptico de llave foránea, el front no
        // sabía interpretarlo y la pestaña quedaba inservible ("zombie"):
        // mostraba productos pero no dejaba cobrar ni cerrar.
        $stmtVenta = $this->db->prepare(
            "SELECT estado FROM ventas WHERE idVenta = ? LIMIT 1"
        );
        $stmtVenta->execute([$idVenta]);
        $ventaActual = $stmtVenta->fetch(PDO::FETCH_ASSOC);

        if (!$ventaActual) {
            throw new Exception('Venta no encontrada');
        }
        if (!in_array($ventaActual['estado'], ['pendiente', 'temporal'], true)) {
            throw new Exception('Venta no encontrada o no está pendiente');
        }

        // ---- Validación de cantidad y precio (servidor manda).
        // Va ANTES de abrir la transacción para no dejar rollback huérfano. ----
        $cantidad = Validator::cantidad($cantidad, false, 'La cantidad del producto');

        if ($idProducto !== null && $idProducto !== '') {
            // Producto real: el precio SIEMPRE sale de la BD, no del cliente.
            // Así el total de la venta no se puede manipular ni equivocar.
            $stmtPrecio = $this->db->prepare(
                "SELECT precioVenta FROM productos WHERE idProducto = ? AND estado = 1"
            );
            $stmtPrecio->execute([$idProducto]);
            $prod = $stmtPrecio->fetch(PDO::FETCH_ASSOC);
            if (!$prod) {
                throw new Exception("Producto no encontrado o inactivo: ID {$idProducto}");
            }
            $precioUnitario = Validator::precio($prod['precioVenta'], 0, 'El precio del producto');
        } else {
            // Monto manual (sin producto): se valida el valor recibido.
            $precioUnitario = Validator::precio($precioUnitario, 0.01, 'El monto manual');
        }

        try {
            $this->db->beginTransaction();

            // Buscar si el producto ya existe en esta venta
            $sqlCheck = "SELECT idDetalleVenta, cantidad FROM detalle_venta
                         WHERE idVenta = ? AND idProducto = ?
                         LIMIT 1";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$idVenta, $idProducto]);
            $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            $subtotal = $cantidad * $precioUnitario;
            
            if ($existente) {
                // Actualizar cantidad
                $nuevaCantidad = $existente['cantidad'] + $cantidad;
                
                // Si cantidad <= 0, eliminar el producto
                if ($nuevaCantidad <= 0) {
                    $sqlDelete = "DELETE FROM detalle_venta WHERE idDetalleVenta = ?";
                    $stmtDelete = $this->db->prepare($sqlDelete);
                    $stmtDelete->execute([$existente['idDetalleVenta']]);
                    $idDetalleVenta = $existente['idDetalleVenta'];
                } else {
                    // Si cantidad > 0, actualizar
                    $nuevoSubtotal = $nuevaCantidad * $precioUnitario;
                    
                    $sqlUpdate = "UPDATE detalle_venta 
                                  SET cantidad = ?, subTotal = ? 
                                  WHERE idDetalleVenta = ?";
                    $stmtUpdate = $this->db->prepare($sqlUpdate);
                    $stmtUpdate->execute([$nuevaCantidad, $nuevoSubtotal, $existente['idDetalleVenta']]);
                    
                    $idDetalleVenta = $existente['idDetalleVenta'];
                }
            } else {
                // Insertar nuevo producto
                $sqlInsert = "INSERT INTO detalle_venta (idVenta, idProducto, cantidad, precioUnitario, subTotal) 
                              VALUES (?, ?, ?, ?, ?)";
                $stmtInsert = $this->db->prepare($sqlInsert);
                $stmtInsert->execute([$idVenta, $idProducto, $cantidad, $precioUnitario, $subtotal]);
                $idDetalleVenta = $this->db->lastInsertId();
            }
            
            // Actualizar total de la venta
            $sqlTotal = "SELECT COALESCE(SUM(subTotal), 0) AS total FROM detalle_venta WHERE idVenta = ?";
            $stmtTotal = $this->db->prepare($sqlTotal);
            $stmtTotal->execute([$idVenta]);
            $totalResult = $stmtTotal->fetch(PDO::FETCH_ASSOC);
            
            $sqlUpdateVenta = "UPDATE ventas SET total = ?, fechaActualizacion = NOW() WHERE idVenta = ?";
            $stmtUpdateVenta = $this->db->prepare($sqlUpdateVenta);
            $stmtUpdateVenta->execute([$totalResult['total'], $idVenta]);
            
            $this->db->commit();
            return $idDetalleVenta;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error al agregar/actualizar producto: " . $e->getMessage());
        }
    }

    /**
     * Completar una venta pendiente (GENÉRICO para mesas y mostrador)
     * Valida stock, actualiza inventario, registra en caja, marca como completada
     */
    public function completeSale($idVenta, $metodoPago = 'efectivo') {
        try {
            $this->db->beginTransaction();
            
            // Obtener la venta
            $sqlGetSale = "SELECT idVenta, idMesa, total, idUsuario FROM ventas 
                           WHERE idVenta = ? AND estado = 'pendiente' 
                           LIMIT 1";
            $stmtGetSale = $this->db->prepare($sqlGetSale);
            $stmtGetSale->execute([$idVenta]);
            $venta = $stmtGetSale->fetch(PDO::FETCH_ASSOC);
            
            if (!$venta) {
                throw new Exception("Venta no encontrada o no está pendiente/temporal");
            }
            
            // Validar caja activa
            $cajaActiva = $this->cashRegister->getCajaActiva();
            if (!$cajaActiva) {
                throw new Exception('No hay caja abierta. Abra la caja antes de completar ventas.');
            }

            // Obtener detalles y validar stock
            $detalles = $this->getSaleDetails($venta['idVenta']);

            // No se puede facturar una venta sin productos.
            if (empty($detalles)) {
                throw new Exception('No se puede facturar una venta sin productos');
            }

            foreach ($detalles as $det) {
                // Si el producto es null (monto manual), no validar stock
                if ($det['idProducto'] === null || $det['idProducto'] === '') {
                    continue;
                }
                
                $cantidad = (float)$det['cantidad'];
                $sqlProd = "SELECT manejaStock, nombre FROM productos WHERE idProducto = ?";
                $stmtProd = $this->db->prepare($sqlProd);
                $stmtProd->execute([$det['idProducto']]);
                $producto = $stmtProd->fetch(PDO::FETCH_ASSOC);

                if ($producto && $producto['manejaStock']) {
                    if (!$this->inventoryModel->verificarStock($det['idProducto'], $cantidad)) {
                        throw new Exception('Stock insuficiente para: ' . $producto['nombre']);
                    }
                }
            }

            // Marcar venta como completada.
            //
            // fechaVenta se sobrescribe con NOW() a propósito: hasta aquí traía la
            // hora en que se ABRIÓ la pestaña (la venta nace "pendiente" al crear
            // el carrito), no la del cobro. Con el POS encendido todo el día eso
            // desfasaba las facturas hasta más de una hora y descuadraba el
            // reporte de ventas por hora y los cierres. La venta ocurre cuando se
            // cobra: esa es la hora que debe quedar registrada.
            $sqlUpdateVenta = "UPDATE ventas
                               SET estado = 'completada', metodoPago = ?, fechaVenta = NOW(), fechaActualizacion = NOW(), idCaja = ?
                               WHERE idVenta = ?";
            $stmtUpdateVenta = $this->db->prepare($sqlUpdateVenta);
            $stmtUpdateVenta->execute([$metodoPago, $cajaActiva['idCaja'], $venta['idVenta']]);
            
            // Registrar salida de inventario por cada detalle
            foreach ($detalles as $det) {
                // Si el producto es null (monto manual), no afectar inventario
                if ($det['idProducto'] === null || $det['idProducto'] === '') {
                    continue;
                }
                
                $cantidad = (float)$det['cantidad'];
                $sqlProd = "SELECT manejaStock FROM productos WHERE idProducto = ?";
                $stmtProd = $this->db->prepare($sqlProd);
                $stmtProd->execute([$det['idProducto']]);
                $producto = $stmtProd->fetch(PDO::FETCH_ASSOC);

                if ($producto && $producto['manejaStock']) {
                    $this->inventoryModel->registrarMovimiento(
                        $det['idProducto'],
                        'salida',
                        $cantidad,
                        $venta['idVenta'],
                        'venta',
                        "Venta #{$venta['idVenta']}",
                        isset($venta['idUsuario']) ? (int)$venta['idUsuario'] : null,
                        false
                    );
                }
            }

            // Registrar ingreso en caja.
            // Se pasa el método de pago: solo el efectivo entra al arqueo físico
            // del cajón; Nequi/Bancolombia se registran pero no se cuentan como
            // billetes al cerrar.
            $this->cashRegister->registrarIngresoVenta(
                (int)$venta['idVenta'],
                (float)$venta['total'],
                isset($venta['idUsuario']) ? (int)$venta['idUsuario'] : null,
                $metodoPago
            );

            // Si es mesa, marcar como libre
            if ($venta['idMesa'] !== null && $venta['idMesa'] !== 0) {
                $sqlUpdateMesa = "UPDATE mesas SET estado = 'libre' WHERE idMesa = ?";
                $stmtUpdateMesa = $this->db->prepare($sqlUpdateMesa);
                $stmtUpdateMesa->execute([$venta['idMesa']]);
            }
            
            $this->db->commit();
            return $venta['idVenta'];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error al completar venta: " . $e->getMessage());
        }
    }

    /**
     * CANCELAR venta PENDIENTE (ELIMINA completamente de BD)
     * 
     * Usado cuando:
     * - Usuario cancela venta ANTES de completarla
     * - Venta está en estado 'pendiente'
     * - Se ELIMINA completamente (detalle_venta y ventas)
     * - NO deja rastro en la BD
     * 
     * Diferente de cancelInvoice() que MARCA como cancelada
     */
    public function cancelSale($idVenta) {
        try {
            $this->db->beginTransaction();
            
            // Obtener la venta
            $sqlGetSale = "SELECT idVenta FROM ventas WHERE idVenta = ?";
            $stmtGetSale = $this->db->prepare($sqlGetSale);
            $stmtGetSale->execute([$idVenta]);
            $venta = $stmtGetSale->fetch(PDO::FETCH_ASSOC);
            
            if (!$venta) {
                throw new Exception("Venta no encontrada");
            }
            
            // Eliminar detalles de venta
            $sqlDeleteDetalles = "DELETE FROM detalle_venta WHERE idVenta = ?";
            $stmtDeleteDetalles = $this->db->prepare($sqlDeleteDetalles);
            $stmtDeleteDetalles->execute([$idVenta]);
            
            // Eliminar venta
            $sqlDeleteVenta = "DELETE FROM ventas WHERE idVenta = ?";
            $stmtDeleteVenta = $this->db->prepare($sqlDeleteVenta);
            $stmtDeleteVenta->execute([$idVenta]);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error al cancelar venta: " . $e->getMessage());
        }
    }

    /**
     * Actualizar cantidad de producto en detalle de venta
     */
    public function updateProductQuantity($idDetalleVenta, $cantidad) {
        // Cantidad válida (numérica, > 0 y dentro del tope generoso)
        $cantidad = Validator::cantidad($cantidad, false, 'La cantidad');
        try {

            // Obtener detalles actuales
            $sqlGet = "SELECT idVenta, precioUnitario FROM detalle_venta WHERE idDetalleVenta = ?";
            $stmtGet = $this->db->prepare($sqlGet);
            $stmtGet->execute([$idDetalleVenta]);
            $detalle = $stmtGet->fetch(PDO::FETCH_ASSOC);
            
            if (!$detalle) {
                throw new Exception("Detalle de venta no encontrado");
            }
            
            $subtotal = $cantidad * $detalle['precioUnitario'];
            
            // Actualizar cantidad
            $sqlUpdate = "UPDATE detalle_venta SET cantidad = ?, subTotal = ? WHERE idDetalleVenta = ?";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute([$cantidad, $subtotal, $idDetalleVenta]);
            
            // Actualizar total de venta
            $sqlTotal = "SELECT COALESCE(SUM(subTotal), 0) AS total FROM detalle_venta WHERE idVenta = ?";
            $stmtTotal = $this->db->prepare($sqlTotal);
            $stmtTotal->execute([$detalle['idVenta']]);
            $totalResult = $stmtTotal->fetch(PDO::FETCH_ASSOC);
            
            $sqlUpdateVenta = "UPDATE ventas SET total = ?, fechaActualizacion = NOW() WHERE idVenta = ?";
            $stmtUpdateVenta = $this->db->prepare($sqlUpdateVenta);
            $stmtUpdateVenta->execute([$totalResult['total'], $detalle['idVenta']]);
        } catch (Exception $e) {
            throw new Exception("Error al actualizar cantidad: " . $e->getMessage());
        }
    }

    /**
     * Remover producto de venta
     */
    public function removeProductFromSale($idDetalleVenta) {
        try {
            $this->db->beginTransaction();
            
            // Obtener idVenta
            $sqlGet = "SELECT idVenta FROM detalle_venta WHERE idDetalleVenta = ?";
            $stmtGet = $this->db->prepare($sqlGet);
            $stmtGet->execute([$idDetalleVenta]);
            $detalle = $stmtGet->fetch(PDO::FETCH_ASSOC);
            
            if (!$detalle) {
                throw new Exception("Detalle de venta no encontrado");
            }
            
            // Eliminar detalle
            $sqlDelete = "DELETE FROM detalle_venta WHERE idDetalleVenta = ?";
            $stmtDelete = $this->db->prepare($sqlDelete);
            $stmtDelete->execute([$idDetalleVenta]);
            
            // Actualizar total de venta
            $sqlTotal = "SELECT COALESCE(SUM(subTotal), 0) AS total FROM detalle_venta WHERE idVenta = ?";
            $stmtTotal = $this->db->prepare($sqlTotal);
            $stmtTotal->execute([$detalle['idVenta']]);
            $totalResult = $stmtTotal->fetch(PDO::FETCH_ASSOC);
            
            $sqlUpdateVenta = "UPDATE ventas SET total = ?, fechaActualizacion = NOW() WHERE idVenta = ?";
            $stmtUpdateVenta = $this->db->prepare($sqlUpdateVenta);
            $stmtUpdateVenta->execute([$totalResult['total'], $detalle['idVenta']]);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error al remover producto: " . $e->getMessage());
        }
    }

    public function getActiveSales() {
        $sql = "SELECT v.* FROM ventas v WHERE v.estado = 'pendiente' AND v.idMesa IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    /**
     * Obtener idVenta para una mesa con venta pendiente
     * Retorna idVenta o null si no hay venta pendiente
     */
    public function getVentaByMesa($idMesa) {
        $sql = "SELECT idVenta FROM ventas WHERE idMesa = ? AND estado = 'pendiente' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idMesa]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['idVenta'] : null;
    }

    /**
     * ANULAR venta COMPLETADA (MARCA como cancelada, mantiene registro)
     * 
     * Usado cuando:
     * - Usuario ANULA venta DESPUÉS de completarla
     * - Venta está en estado 'completada'
     * - Se MARCA como 'cancelada' (UPDATE, no DELETE)
     * - MANTIENE el registro para auditoría
     * - Se muestra sello "ANULADO" en factura
     * 
     * Diferente de cancelSale() que ELIMINA completamente
     */
    public function cancelInvoice($idVenta, $observacion = null, $idUsuario = null) {
        try {
            $this->db->beginTransaction();

            // Solo se anulan ventas completadas, y se bloquea la fila para que
            // dos anulaciones simultáneas no devuelvan el stock dos veces.
            $sqlGet = "SELECT idVenta, estado FROM ventas WHERE idVenta = ? FOR UPDATE";
            $stmtGet = $this->db->prepare($sqlGet);
            $stmtGet->execute([$idVenta]);
            $venta = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                throw new Exception('Venta no encontrada');
            }

            if ($venta['estado'] === 'cancelada') {
                throw new Exception('La venta ya está anulada');
            }

            if ($venta['estado'] !== 'completada') {
                throw new Exception('Solo se pueden anular ventas completadas');
            }

            // Marcar como cancelada
            $sql = "UPDATE ventas SET estado = 'cancelada'";
            $params = [];

            if (!empty($observacion)) {
                $sql .= ", descripcion = ?";
                $params[] = $observacion;
            }

            $sql .= " WHERE idVenta = ?";
            $params[] = $idVenta;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            // Devolver al inventario lo que se había descontado.
            // Sin esto el stock queda descuadrado: la venta no ocurrió pero el
            // producto sigue descontado.
            $detalles = $this->getSaleDetails($idVenta);
            foreach ($detalles as $det) {
                // Los montos manuales no tienen producto asociado
                if ($det['idProducto'] === null || $det['idProducto'] === '') {
                    continue;
                }

                $sqlProd = "SELECT manejaStock FROM productos WHERE idProducto = ?";
                $stmtProd = $this->db->prepare($sqlProd);
                $stmtProd->execute([$det['idProducto']]);
                $producto = $stmtProd->fetch(PDO::FETCH_ASSOC);

                if ($producto && $producto['manejaStock']) {
                    $this->inventoryModel->registrarMovimiento(
                        $det['idProducto'],
                        'entrada',
                        (float)$det['cantidad'],
                        $idVenta,
                        'venta',
                        "Anulación venta #{$idVenta}",
                        $idUsuario,
                        false // ya estamos dentro de una transacción
                    );
                }
            }

            // El dinero NO se reversa con un movimiento nuevo: los reportes de
            // caja excluyen las ventas canceladas con EXISTS(...estado='completada'),
            // así queda el rastro de auditoría sin descuadrar los totales.

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Error al anular la venta: ' . $e->getMessage());
        }
    }
}