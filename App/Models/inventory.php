<?php
require_once __DIR__ . '/../Core/Conexion.php';

class Inventory {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Registrar movimiento de inventario (entrada, salida o ajuste)
     */
    public function registrarMovimiento($idProducto, $tipoMovimiento, $cantidad, $referencia = null, $tipoReferencia = null, $descripcion = null, $idUsuario = null, $useTransaction = true) {
        try {
            if ($useTransaction) {
                $this->db->beginTransaction();
            }

            // Obtener stock actual
            $stockAnterior = $this->obtenerStockActual($idProducto);

            // Calcular nuevo stock
            if ($tipoMovimiento === 'entrada') {
                $stockActual = $stockAnterior + $cantidad;
            } elseif ($tipoMovimiento === 'salida') {
                $stockActual = $stockAnterior - $cantidad;
                // NO bloquear si queda negativo; lo registramos con alerta
            } else { // ajuste
                $stockActual = $cantidad; // En ajuste, la cantidad ES el nuevo stock
            }

            // Flag si resultó stock negativo
            $tieneAlerta = $stockActual < 0 ? 1 : 0;

            // Insertar movimiento (con flag de alerta si es negativo)
            $sql = "INSERT INTO inventario (idProducto, tipoMovimiento, cantidad, stockAnterior, stockActual, referencia, tipoReferencia, descripcion, idUsuario, tieneAlerta) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $idProducto,
                $tipoMovimiento,
                $cantidad,
                $stockAnterior,
                $stockActual,
                $referencia,
                $tipoReferencia,
                $descripcion,
                $idUsuario,
                $tieneAlerta
            ]);

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
     * Obtener stock actual de un producto
     * Retorna DECIMAL para soportar kg, L
     */
    public function obtenerStockActual($idProducto) {
        $sql = "SELECT stockActual FROM inventario 
                WHERE idProducto = ? 
                ORDER BY fechaMovimiento DESC, idInventario DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idProducto]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? floatval($result['stockActual']) : 0.0;
    }

    /**
     * Obtener stock de todos los productos
     */
    public function obtenerStockGeneral() {
        $sql = "SELECT * FROM vista_stock_actual ORDER BY producto ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener productos con stock bajo (menos del límite especificado)
     */
    public function obtenerStockBajo($limite = 10) {
        $sql = "SELECT p.nombre AS producto, 
                       COALESCE((SELECT stockActual FROM inventario WHERE idProducto = p.idProducto ORDER BY fechaMovimiento DESC LIMIT 1), 0) AS stockActual,
                       ? AS stockMinimo
                FROM productos p
                WHERE p.manejaStock = TRUE
                HAVING stockActual > 0 AND stockActual < ?
                ORDER BY stockActual ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limite, $limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener historial de movimientos de un producto
     */
    public function obtenerHistorialProducto($idProducto, $limit = 50) {
        $sql = "SELECT i.*, u.nombre AS usuario 
                FROM inventario i
                LEFT JOIN usuarios u ON i.idUsuario = u.idUsuario
                WHERE i.idProducto = ?
                ORDER BY i.fechaMovimiento DESC, i.idInventario DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idProducto, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todos los movimientos (con filtros opcionales)
     */
    public function obtenerMovimientos($filtros = []) {
        $sql = "SELECT i.*, p.nombre AS producto, u.nombre AS usuario
                FROM inventario i
                INNER JOIN productos p ON i.idProducto = p.idProducto
                LEFT JOIN usuarios u ON i.idUsuario = u.idUsuario
                WHERE 1=1";
        
        $params = [];

        if (!empty($filtros['idProducto'])) {
            $sql .= " AND i.idProducto = ?";
            $params[] = $filtros['idProducto'];
        }

        if (!empty($filtros['tipoMovimiento'])) {
            $sql .= " AND i.tipoMovimiento = ?";
            $params[] = $filtros['tipoMovimiento'];
        }

        if (!empty($filtros['fechaDesde'])) {
            $sql .= " AND DATE(i.fechaMovimiento) >= ?";
            $params[] = $filtros['fechaDesde'];
        }

        if (!empty($filtros['fechaHasta'])) {
            $sql .= " AND DATE(i.fechaMovimiento) <= ?";
            $params[] = $filtros['fechaHasta'];
        }

        $sql .= " ORDER BY i.fechaMovimiento DESC, i.idInventario DESC";

        if (!empty($filtros['limit'])) {
            $sql .= " LIMIT " . intval($filtros['limit']);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si un producto tiene stock suficiente
     * Retorna true si hay stock, pero ahora permite venta aunque sea negativo (con alerta registrada)
     */
    public function verificarStock($idProducto, $cantidadRequerida) {
        // Siempre retorna true; stock negativo se registra con alerta y se permite la venta
        return true;
    }

    /**
     * Ajuste manual de stock (corrección)
     */
    public function ajustarStock($idProducto, $nuevoStock, $descripcion, $idUsuario = null) {
        return $this->registrarMovimiento(
            $idProducto,
            'ajuste',
            $nuevoStock, // En ajuste, cantidad = nuevo stock total
            null,
            'ajuste_manual',
            $descripcion,
            $idUsuario
        );
    }

    /**
     * Obtener valor total del inventario
     */
    public function obtenerValorInventario() {
        $sql = "SELECT 
                    SUM(stockActual * precioCompra) AS valorCompra,
                    SUM(stockActual * precioVenta) AS valorVenta
                FROM vista_stock_actual";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener alertas de stock (movimientos con stock negativo)
     */
    public function obtenerAlertas($limit = 100) {
        $sql = "SELECT i.*, p.nombre AS producto, u.nombre AS usuario
                FROM inventario i
                INNER JOIN productos p ON i.idProducto = p.idProducto
                LEFT JOIN usuarios u ON i.idUsuario = u.idUsuario
                WHERE i.tieneAlerta = 1
                ORDER BY i.fechaMovimiento DESC, i.idInventario DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener alertas de un producto específico
     */
    public function obtenerAlertasProducto($idProducto) {
        $sql = "SELECT i.*, u.nombre AS usuario
                FROM inventario i
                LEFT JOIN usuarios u ON i.idUsuario = u.idUsuario
                WHERE i.idProducto = ? AND i.tieneAlerta = 1
                ORDER BY i.fechaMovimiento DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idProducto]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}