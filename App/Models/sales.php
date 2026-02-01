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
                throw new Exception("Venta no encontrada o no está pendiente");
            }
            
            // Validar caja activa
            $cajaActiva = $this->cashRegister->getCajaActiva();
            if (!$cajaActiva) {
                throw new Exception('No hay caja abierta. Abra la caja antes de completar ventas.');
            }

            // Obtener detalles y validar stock
            $detalles = $this->getSaleDetails($venta['idVenta']);
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

            // Marcar venta como completada
            $sqlUpdateVenta = "UPDATE ventas 
                               SET estado = 'completada', metodoPago = ?, fechaActualizacion = NOW(), idCaja = ? 
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

            // Registrar ingreso en caja
            $this->cashRegister->registrarIngresoVenta(
                (int)$venta['idVenta'],
                (float)$venta['total'],
                isset($venta['idUsuario']) ? (int)$venta['idUsuario'] : null
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
     * CANCELAR venta PENDIENTE/TEMPORAL (ELIMINA completamente de BD)
     * 
     * Usado cuando:
     * - Usuario cancela venta ANTES de completarla
     * - Venta está en estado 'pendiente' o 'temporal'
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
    public function cancelInvoice($idVenta) {
        $sql = "UPDATE ventas SET estado = 'cancelada' Where idVenta = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idVenta]);
    }
}