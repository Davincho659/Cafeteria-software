<?php
require_once __DIR__ . '/../Core/Conexion.php';
require_once __DIR__ . '/Inventory.php';
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

    /**
     * Crear venta rápida/detallada con sus detalles en una sola transacción
     */
    public function createSaleWithDetails($metodoPago, array $productos, $idUsuario, $idMesa = null, $tipoVenta = 'detallada', $descripcion = null) {
        try {
            $this->db->beginTransaction();

            // Validar caja activa
            $cajaActiva = $this->cashRegister->getCajaActiva();
            if (!$cajaActiva) {
                throw new Exception('No hay caja abierta. Abra la caja antes de registrar ventas.');
            }

            // Calcular total servidor-side y validar stock
            $total = 0;
            foreach ($productos as $item) {
                $cantidad = isset($item['cantidad']) ? (float)$item['cantidad'] : 0;
                $precio = isset($item['precioUnitario']) ? (float)$item['precioUnitario'] : 0;
                if ($cantidad <= 0) {
                    throw new Exception('Cantidad inválida para un producto');
                }
                if ($precio < 0) {
                    throw new Exception('Precio inválido para un producto');
                }
                $total += $cantidad * $precio;

                // Validar stock si aplica
                $sqlProd = "SELECT manejaStock, nombre FROM productos WHERE idProducto = ?";
                $stmtProd = $this->db->prepare($sqlProd);
                $stmtProd->execute([$item['idProducto']]);
                $producto = $stmtProd->fetch(PDO::FETCH_ASSOC);
                if ($producto && $producto['manejaStock']) {
                    if (!$this->inventoryModel->verificarStock($item['idProducto'], $cantidad)) {
                        throw new Exception('Stock insuficiente para: ' . $producto['nombre']);
                    }
                }
            }

            if ($total <= 0) {
                throw new Exception('El total calculado es inválido');
            }

            // Insertar venta
            $sqlVenta = "INSERT INTO ventas (idMesa, estado, metodoPago, total, idUsuario, tipoVenta, descripcion, idCaja) 
                         VALUES (?, 'completada', ?, ?, ?, ?, ?, ?)";
            $stmtVenta = $this->db->prepare($sqlVenta);
            $stmtVenta->execute([$idMesa, $metodoPago, $total, $idUsuario, $tipoVenta, $descripcion, $cajaActiva['idCaja']]);
            $idVenta = $this->db->lastInsertId();

            // Movimiento de caja
            $this->cashRegister->registrarIngresoVenta((int)$idVenta, (float)$total, $idUsuario);

            // Detalles + inventario (sin abrir transacción interna)
            foreach ($productos as $item) {
                $this->createSalesDetailInternal(
                    $idVenta,
                    null,
                    $item['idProducto'],
                    (float)$item['cantidad'],
                    (float)$item['precioUnitario'],
                    $idUsuario,
                    false // no iniciar transacción nueva
                );
            }

            $this->db->commit();
            return $idVenta;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Crear detalle de venta Y actualizar inventario
     */
    public function createSalesDetail($idVenta, $idDetalleVenta, $idProducto, $cantidad, $precioUnitario, $idUsuario = null) {
        // Método público si se requiere uso aislado; maneja su propia transacción.
        return $this->createSalesDetailInternal($idVenta, $idDetalleVenta, $idProducto, $cantidad, $precioUnitario, $idUsuario, true);
    }

    private function createSalesDetailInternal($idVenta, $idDetalleVenta, $idProducto, $cantidad, $precioUnitario, $idUsuario, $useTransaction) {
        try {
            if ($useTransaction) {
                $this->db->beginTransaction();
            }

            $subtotal = $cantidad * $precioUnitario;

            $sql = "INSERT INTO detalle_venta (idVenta, idDetalleVenta, idProducto, cantidad, precioUnitario, subTotal) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idVenta, $idDetalleVenta, $idProducto, $cantidad, $precioUnitario, $subtotal]);

            $sqlCheck = "SELECT manejaStock, nombre FROM productos WHERE idProducto = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$idProducto]);
            $producto = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($producto && $producto['manejaStock']) {
                if (!$this->inventoryModel->verificarStock($idProducto, $cantidad)) {
                    throw new Exception('Stock insuficiente para: ' . $producto['nombre']);
                }

                $this->inventoryModel->registrarMovimiento(
                    $idProducto,
                    'salida',
                    $cantidad,
                    $idVenta,
                    'venta',
                    "Venta #$idVenta",
                    $idUsuario,
                    false // no abrir nueva transacción
                );
            }

            if ($useTransaction) {
                $this->db->commit();
            }
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            if ($useTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

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
        $sql = "SELECT dv.idDetalleVenta, dv.idProducto, p.nombre AS producto_nombre,
                       p.imagen AS producto_imagen,
                       dv.cantidad, dv.precioUnitario, dv.subTotal
                FROM detalle_venta dv
                LEFT JOIN productos p ON dv.idProducto = p.idProducto
                WHERE dv.idVenta = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idVenta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function countWithFilters($filterQuery = "") {
        $sql = "SELECT COUNT(*) as total FROM ventas";
        $sql .= $filterQuery;
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
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
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
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
    // MÉTODOS CRÍTICOS PARA FLUJO DE MESAS (ANTES FALTABAN)
    // ============================================================================

    /**
     * Obtener o crear venta para una mesa
     * Si ya existe una venta pendiente para la mesa, la retorna
     * Si no existe, crea una nueva
     */
    public function getOrCreateTableSale($idMesa, $idUsuario = null) {
        try {
            // Buscar si ya existe una venta pendiente para esta mesa
            $sql = "SELECT idVenta FROM ventas 
                    WHERE idMesa = ? AND estado = 'pendiente' 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idMesa]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['idVenta'];
            }
            
            // Si no existe, crear una nueva venta
            $sql = "INSERT INTO ventas (idMesa, estado, metodoPago, total, idUsuario, tipoVenta, fechaCreacion, fechaActualizacion) 
                    VALUES (?, 'pendiente', '', 0, ?, 'mesa', NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idMesa, $idUsuario]);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error al crear/obtener venta de mesa: " . $e->getMessage());
        }
    }

    /**
     * Agregar o actualizar producto en una venta
     * Si el producto ya existe, incrementa cantidad
     * Si no existe, lo agrega
     */
    public function addOrUpdateProductToSale($idVenta, $idProducto, $cantidad, $precioUnitario, $idUsuario = null) {
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
                $nuevoSubtotal = $nuevaCantidad * $precioUnitario;
                
                $sqlUpdate = "UPDATE detalle_venta 
                              SET cantidad = ?, subTotal = ? 
                              WHERE idDetalleVenta = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute([$nuevaCantidad, $nuevoSubtotal, $existente['idDetalleVenta']]);
                
                $idDetalleVenta = $existente['idDetalleVenta'];
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
     * Completar venta de mesa
     * Marca la venta como completada
     */
    public function completeTableSale($idMesa, $metodoPago = 'efectivo') {
        try {
            $this->db->beginTransaction();
            
            // Obtener la venta pendiente de la mesa
            $sqlGetSale = "SELECT idVenta, total, idUsuario FROM ventas 
                           WHERE idMesa = ? AND estado = 'pendiente' 
                           LIMIT 1";
            $stmtGetSale = $this->db->prepare($sqlGetSale);
            $stmtGetSale->execute([$idMesa]);
            $venta = $stmtGetSale->fetch(PDO::FETCH_ASSOC);
            
            if (!$venta) {
                throw new Exception("No hay venta pendiente para esta mesa");
            }
            
            // Validar caja activa
            $cajaActiva = $this->cashRegister->getCajaActiva();
            if (!$cajaActiva) {
                throw new Exception('No hay caja abierta. Abra la caja antes de completar ventas.');
            }

            // Traer detalles para afectar inventario y validar stock
            $detalles = $this->getSaleDetails($venta['idVenta']);
            foreach ($detalles as $det) {
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

            // Marcar venta como completada
            $sqlUpdateVenta = "UPDATE ventas 
                               SET estado = 'completada', metodoPago = ?, fechaActualizacion = NOW(), idCaja = ? 
                               WHERE idVenta = ?";
            $stmtUpdateVenta = $this->db->prepare($sqlUpdateVenta);
            $stmtUpdateVenta->execute([$metodoPago, $cajaActiva['idCaja'], $venta['idVenta']]);
            
            // Registrar salida de inventario por cada detalle
            foreach ($detalles as $det) {
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

            // Registrar ingreso en caja (movimiento VENTA)
            $this->cashRegister->registrarIngresoVenta(
                (int)$venta['idVenta'],
                (float)$venta['total'],
                isset($venta['idUsuario']) ? (int)$venta['idUsuario'] : null
            );
            
            $this->db->commit();
            return $venta['idVenta'];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error al completar venta: " . $e->getMessage());
        }
    }

    /**
     * Cancelar venta de mesa
     * Elimina la venta y sus detalles
     */
    public function cancelTableSale($idMesa) {
        try {
            $this->db->beginTransaction();
            
            // Obtener la venta pendiente
            $sqlGetSale = "SELECT idVenta FROM ventas 
                           WHERE idMesa = ? AND estado = 'pendiente' 
                           LIMIT 1";
            $stmtGetSale = $this->db->prepare($sqlGetSale);
            $stmtGetSale->execute([$idMesa]);
            $venta = $stmtGetSale->fetch(PDO::FETCH_ASSOC);
            
            if ($venta) {
                // Eliminar detalles de venta
                $sqlDeleteDetalles = "DELETE FROM detalle_venta WHERE idVenta = ?";
                $stmtDeleteDetalles = $this->db->prepare($sqlDeleteDetalles);
                $stmtDeleteDetalles->execute([$venta['idVenta']]);
                
                // Eliminar venta
                $sqlDeleteVenta = "DELETE FROM ventas WHERE idVenta = ?";
                $stmtDeleteVenta = $this->db->prepare($sqlDeleteVenta);
                $stmtDeleteVenta->execute([$venta['idVenta']]);
            }
            
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
        try {
            if ($cantidad <= 0) {
                throw new Exception("La cantidad debe ser mayor a 0");
            }
            
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
}