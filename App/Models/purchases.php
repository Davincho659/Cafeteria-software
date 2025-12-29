<?php
require_once __DIR__ . '/../Core/Conexion.php';
require_once __DIR__ . '/Inventory.php';
require_once __DIR__ . '/cashRegister.php';

class Purchases {
    private $db;
    private $inventoryModel;
    private $cashRegister;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->inventoryModel = new Inventory();
        $this->cashRegister = new CashRegister();
    }

    /**
     * Crear compra (detallada o rápida)
     * @param int $idProveedor - ID del proveedor
     * @param float $total - Total de la compra
     * @param string $tipoCompra - 'detallada' o 'rapida'
     * @param string $descripcion - Descripción (requerida para compras rápidas)
     * @param int $idUsuario - ID del usuario que registra
     * @return int - ID de la compra creada
     */
    public function createPurchase($idProveedor, $total, $tipoCompra = 'detallada', $descripcion = null, $idUsuario = null) {
        // Versión usada para compras rápidas (sin detalles). Maneja su propia transacción.
        try {
            $this->db->beginTransaction();

            $cajaActiva = $this->cashRegister->getCajaActiva();
            if (!$cajaActiva) {
                throw new Exception('No hay caja abierta. Abra la caja antes de registrar compras.');
            }

            $sql = "INSERT INTO compras (idProveedor, total, tipoCompra, descripcion, idUsuario, idCaja) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idProveedor, $total, $tipoCompra, $descripcion, $idUsuario, $cajaActiva['idCaja']]);

            $idCompra = $this->db->lastInsertId();
            $this->cashRegister->registrarEgresoCompra((int)$idCompra, (float)$total, $idUsuario);

            $this->db->commit();
            return $idCompra;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Crear compra con detalles en una sola transacción
     */
    public function createPurchaseWithDetails($idProveedor, array $productos, $idUsuario = null) {
        try {
            $this->db->beginTransaction();

            $cajaActiva = $this->cashRegister->getCajaActiva();
            if (!$cajaActiva) {
                throw new Exception('No hay caja abierta. Abra la caja antes de registrar compras.');
            }

            // Calcular total servidor-side
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
            }

            if ($total <= 0) {
                throw new Exception('El total calculado es inválido');
            }

            // Insertar compra
            $sql = "INSERT INTO compras (idProveedor, total, tipoCompra, descripcion, idUsuario, idCaja) 
                    VALUES (?, ?, 'detallada', NULL, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idProveedor, $total, $idUsuario, $cajaActiva['idCaja']]);
            $idCompra = $this->db->lastInsertId();

            // Movimiento de caja
            $this->cashRegister->registrarEgresoCompra((int)$idCompra, (float)$total, $idUsuario);

            // Detalles + inventario + historial
            foreach ($productos as $item) {
                $this->createPurchaseDetailInternal(
                    $idCompra,
                    $item['idProducto'],
                    (float)$item['cantidad'],
                    (float)$item['precioUnitario'],
                    $idUsuario,
                    false // no transaction inside
                );
            }

            $this->db->commit();
            return $idCompra;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Crear detalle de compra Y actualizar inventario
     */
    public function createPurchaseDetail($idCompra, $idProducto, $cantidad, $precioUnitario, $idUsuario = null) {
        // Método público si se requiere uso aislado; maneja su propia transacción.
        return $this->createPurchaseDetailInternal($idCompra, $idProducto, $cantidad, $precioUnitario, $idUsuario, true);
    }

    private function createPurchaseDetailInternal($idCompra, $idProducto, $cantidad, $precioUnitario, $idUsuario, $useTransaction) {
        try {
            if ($useTransaction) {
                $this->db->beginTransaction();
            }

            $subtotal = $cantidad * $precioUnitario;

            $sql = "INSERT INTO detalle_compra (idCompra, idProducto, cantidad, precioUnitario, subtotal) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idCompra, $idProducto, $cantidad, $precioUnitario, $subtotal]);

            $idProveedor = $this->getProveedorByCompra($idCompra);
            if ($idProveedor) {
                $sqlHist = "INSERT INTO historial_precio_compra (idProducto, idProveedor, idCompra, precioUnitario, cantidad)
                            VALUES (?, ?, ?, ?, ?)";
                $stmtHist = $this->db->prepare($sqlHist);
                $stmtHist->execute([$idProducto, $idProveedor, $idCompra, $precioUnitario, $cantidad]);
            }

            $sqlCheck = "SELECT manejaStock FROM productos WHERE idProducto = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$idProducto]);
            $producto = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($producto && $producto['manejaStock']) {
                $this->inventoryModel->registrarMovimiento(
                    $idProducto,
                    'entrada',
                    $cantidad,
                    $idCompra,
                    'compra',
                    "Compra #$idCompra",
                    $idUsuario,
                    false // no iniciar transacción nueva
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

    /**
     * Obtener todas las compras
     */
    public function getAll() {
        $sql = "SELECT c.*, p.nombre AS proveedor 
                FROM compras c
                LEFT JOIN proveedores p ON c.idProveedor = p.idProveedor
                ORDER BY c.fechaCompra DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener compras por fecha
     */
    public function getByDate($fecha) {
        $sql = "SELECT c.*, p.nombre AS proveedor 
            FROM compras c
            LEFT JOIN proveedores p ON c.idProveedor = p.idProveedor
            WHERE DATE(c.fechaCompra) = ?
            ORDER BY c.fechaCompra DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fecha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener compra por ID con detalles
     */
    public function getById($idCompra) {
        $sql = "SELECT c.*, p.nombre AS proveedor, p.telefono AS proveedor_telefono,
                       u.nombre AS usuario
                FROM compras c
                LEFT JOIN proveedores p ON c.idProveedor = p.idProveedor
                LEFT JOIN usuarios u ON c.idUsuario = u.idUsuario
                WHERE c.idCompra = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idCompra]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener proveedor de una compra (para historial de precios)
     */
    private function getProveedorByCompra($idCompra) {
        $sql = "SELECT idProveedor FROM compras WHERE idCompra = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idCompra]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['idProveedor'] : null;
    }

    /**
     * Obtener detalles de una compra
     */
    public function getDetails($idCompra) {
        $sql = "SELECT dc.*, p.nombre AS producto 
                FROM detalle_compra dc
                INNER JOIN productos p ON dc.idProducto = p.idProducto
                WHERE dc.idCompra = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idCompra]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener compras con filtros
     */
    public function getFiltered($filtros = []) {
        $sql = "SELECT c.*, p.nombre AS nombreProveedor 
                FROM compras c
                LEFT JOIN proveedores p ON c.idProveedor = p.idProveedor
                WHERE 1=1";
        
        $params = [];

        if (!empty($filtros['idProveedor'])) {
            $sql .= " AND c.idProveedor = ?";
            $params[] = $filtros['idProveedor'];
        }

        if (!empty($filtros['tipoCompra'])) {
            $sql .= " AND c.tipoCompra = ?";
            $params[] = $filtros['tipoCompra'];
        }

        if (!empty($filtros['fechaDesde'])) {
            $sql .= " AND DATE(c.fechaCompra) >= ?";
            $params[] = $filtros['fechaDesde'];
        }

        if (!empty($filtros['fechaHasta'])) {
            $sql .= " AND DATE(c.fechaCompra) <= ?";
            $params[] = $filtros['fechaHasta'];
        }

        $sql .= " ORDER BY c.fechaCompra DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener total de compras por rango de fechas
     */
    public function getTotalByDateRange($fechaDesde, $fechaHasta) {
        $sql = "SELECT COALESCE(SUM(total), 0) AS total
                FROM compras
                WHERE DATE(fechaCompra) BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fechaDesde, $fechaHasta]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * Obtener compras del día
     */
    public function getTodayPurchases() {
        return $this->getByDate(date('Y-m-d'));
    }

    /**
     * Obtener total de compras del día
     */
    public function getTodayTotal() {
        $sql = "SELECT COALESCE(SUM(total), 0) AS total
                FROM compras
                WHERE DATE(fechaCompra) = CURDATE()";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}