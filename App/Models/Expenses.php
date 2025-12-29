<?php
require_once __DIR__ . '/../Core/Conexion.php';
require_once __DIR__ . '/Inventory.php';
require_once __DIR__ . '/cashRegister.php';

class Expenses {
    private $db;
    private $inventoryModel;
    private $cashRegister;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->inventoryModel = new Inventory();
        $this->cashRegister = new CashRegister();
    }

    /**
     * Crear gasto de producto (merma, rotura, vencimiento, etc)
     * Afecta inventario (salida) y caja (egreso)
     */
    public function crearGastoProducto($idProducto, $cantidad, $motivo, $monto = null, $idUsuario = null) {
        try {
            $this->db->beginTransaction();

            // Validar caja activa
            $cajaActiva = $this->cashRegister->getCajaActiva();
            if (!$cajaActiva) {
                throw new Exception('No hay caja abierta. Abra la caja antes de registrar gastos.');
            }

            // Si no se proporciona monto, calcularlo desde precio de costo
            if ($monto === null || $monto <= 0) {
                $sqlProd = "SELECT precioCompra FROM productos WHERE idProducto = ?";
                $stmtProd = $this->db->prepare($sqlProd);
                $stmtProd->execute([$idProducto]);
                $producto = $stmtProd->fetch(PDO::FETCH_ASSOC);
                if (!$producto) {
                    throw new Exception('Producto no encontrado');
                }
                $monto = (float)$cantidad * (float)$producto['precioCompra'];
            }

            // Insertar gasto de producto
            $sql = "INSERT INTO gastos (idProducto, tipo, cantidad, motivo, monto, idUsuario, idCaja) 
                    VALUES (?, 'producto', ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idProducto, $cantidad, $motivo, $monto, $idUsuario, $cajaActiva['idCaja']]);
            $idGasto = $this->db->lastInsertId();

            // Registrar salida en inventario
            $this->inventoryModel->registrarMovimiento(
                $idProducto,
                'salida',
                $cantidad,
                $idGasto,
                'gasto_producto',
                "Gasto: $motivo",
                $idUsuario,
                false // no iniciar transacción nueva
            );

            // Registrar egreso en caja
            $monto = -abs((float)$monto);
            $this->cashRegister->addMovement(
                (int)$cajaActiva['idCaja'],
                'GASTO',
                $monto,
                (string)$idGasto,
                'gasto_producto',
                $idUsuario,
                "Gasto Producto: $motivo"
            );

            $this->db->commit();
            return $idGasto;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Crear gasto externo (servicios, suministros, etc)
     * NO afecta inventario, solo caja
     */
    public function crearGastoExterno($concepto, $monto, $descripcion = null, $idUsuario = null) {
        try {
            $this->db->beginTransaction();

            // Validar caja activa
            $cajaActiva = $this->cashRegister->getCajaActiva();
            if (!$cajaActiva) {
                throw new Exception('No hay caja abierta. Abra la caja antes de registrar gastos.');
            }

            if ($monto <= 0) {
                throw new Exception('El monto debe ser mayor a cero');
            }

            // Insertar gasto externo
            $sql = "INSERT INTO gastos (tipo, concepto, monto, descripcion, idUsuario, idCaja) 
                    VALUES ('externo', ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$concepto, $monto, $descripcion, $idUsuario, $cajaActiva['idCaja']]);
            $idGasto = $this->db->lastInsertId();

            // Registrar egreso en caja
            $monto = -abs((float)$monto);
            $this->cashRegister->addMovement(
                (int)$cajaActiva['idCaja'],
                'GASTO',
                $monto,
                (string)$idGasto,
                'gasto_externo',
                $idUsuario,
                "Gasto Externo: $concepto"
            );

            $this->db->commit();
            return $idGasto;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Obtener gasto por ID
     */
    public function getById($idGasto) {
        $sql = "SELECT g.*, p.nombre AS producto, u.nombre AS usuario
                FROM gastos g
                LEFT JOIN productos p ON g.idProducto = p.idProducto
                LEFT JOIN usuarios u ON g.idUsuario = u.idUsuario
                WHERE g.idGasto = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idGasto]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todos los gastos
     */
    public function getAll() {
        $sql = "SELECT g.*, p.nombre AS producto, u.nombre AS usuario
                FROM gastos g
                LEFT JOIN productos p ON g.idProducto = p.idProducto
                LEFT JOIN usuarios u ON g.idUsuario = u.idUsuario
                ORDER BY g.fechaRegistro DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener gastos filtrados
     */
    public function getFiltered($filtros = []) {
        $sql = "SELECT g.*, p.nombre AS producto, u.nombre AS usuario
                FROM gastos g
                LEFT JOIN productos p ON g.idProducto = p.idProducto
                LEFT JOIN usuarios u ON g.idUsuario = u.idUsuario
                WHERE 1=1";
        
        $params = [];

        if (!empty($filtros['tipo'])) {
            $sql .= " AND g.tipo = ?";
            $params[] = $filtros['tipo'];
        }

        if (!empty($filtros['idProducto'])) {
            $sql .= " AND g.idProducto = ?";
            $params[] = $filtros['idProducto'];
        }

        if (!empty($filtros['fechaDesde'])) {
            $sql .= " AND DATE(g.fechaRegistro) >= ?";
            $params[] = $filtros['fechaDesde'];
        }

        if (!empty($filtros['fechaHasta'])) {
            $sql .= " AND DATE(g.fechaRegistro) <= ?";
            $params[] = $filtros['fechaHasta'];
        }

        if (!empty($filtros['idUsuario'])) {
            $sql .= " AND g.idUsuario = ?";
            $params[] = $filtros['idUsuario'];
        }

        $sql .= " ORDER BY g.fechaRegistro DESC";

        if (!empty($filtros['limit'])) {
            $sql .= " LIMIT " . intval($filtros['limit']);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener gastos del día
     */
    public function getTodayExpenses() {
        return $this->getFiltered(['fechaDesde' => date('Y-m-d'), 'fechaHasta' => date('Y-m-d')]);
    }

    /**
     * Obtener total de gastos por rango de fechas
     */
    public function getTotalByDateRange($fechaDesde, $fechaHasta) {
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN tipo='producto' THEN monto ELSE 0 END), 0) AS totalProductos,
                    COALESCE(SUM(CASE WHEN tipo='externo' THEN monto ELSE 0 END), 0) AS totalExternos,
                    COALESCE(SUM(monto), 0) AS totalGastos
                FROM gastos
                WHERE DATE(fechaRegistro) BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fechaDesde, $fechaHasta]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener total de gastos del día
     */
    public function getTodayTotal() {
        $sql = "SELECT COALESCE(SUM(monto), 0) AS total
                FROM gastos
                WHERE DATE(fechaRegistro) = CURDATE()";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * Obtener gastos por producto (historial de mermas/roturas)
     */
    public function getByProduct($idProducto) {
        return $this->getFiltered(['idProducto' => $idProducto, 'tipo' => 'producto']);
    }

    /**
     * Obtener resumen de gastos por tipo
     */
    public function getResumenPorTipo($fechaDesde = null, $fechaHasta = null) {
        $sql = "SELECT 
                    tipo,
                    COUNT(*) as cantidad,
                    COALESCE(SUM(monto), 0) as total
                FROM gastos
                WHERE 1=1";
        
        $params = [];

        if ($fechaDesde) {
            $sql .= " AND DATE(fechaRegistro) >= ?";
            $params[] = $fechaDesde;
        }

        if ($fechaHasta) {
            $sql .= " AND DATE(fechaRegistro) <= ?";
            $params[] = $fechaHasta;
        }

        $sql .= " GROUP BY tipo ORDER BY total DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
