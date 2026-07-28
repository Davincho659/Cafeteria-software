<?php
require_once __DIR__ . '/../Core/Conexion.php';

class Inventory {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->ensureSchema();
    }

    /**
     * Migración automática (mismo patrón que Settings/Tables).
     * Agrega 'consumo' a tipoReferencia para poder distinguir el gasto de
     * insumos (ej. sacar 5 kg de maíz para los fritos) de una venta o de una
     * corrección de conteo. Sin esto no hay trazabilidad de en qué se va la
     * materia prima.
     */
    private function ensureSchema() {
        try {
            $col = $this->db->query("SHOW COLUMNS FROM inventario LIKE 'tipoReferencia'")->fetch(PDO::FETCH_ASSOC);
            if ($col && strpos((string)$col['Type'], 'consumo') === false) {
                $this->db->exec("ALTER TABLE inventario
                    MODIFY COLUMN tipoReferencia
                    ENUM('compra','venta','ajuste_manual','consumo') DEFAULT NULL");
            }
        } catch (Exception $e) {
            // Si falla (permisos), el resto del inventario sigue funcionando.
        }
    }

    /**
     * Registrar movimiento de inventario (entrada, salida o ajuste)
     */
    public function registrarMovimiento($idProducto, $tipoMovimiento, $cantidad, $referencia = null, $tipoReferencia = null, $descripcion = null, $idUsuario = null, $useTransaction = true) {
        try {
            if ($useTransaction) {
                $this->db->beginTransaction();
            }

            // Obtener tipo de unidad del producto
            $sqlUnidad = "SELECT um.tipo FROM productos p 
                         JOIN unidades_medida um ON p.idUnidadBase = um.idUnidad 
                         WHERE p.idProducto = ?";
            $stmtUnidad = $this->db->prepare($sqlUnidad);
            $stmtUnidad->execute([$idProducto]);
            $unidad = $stmtUnidad->fetch(PDO::FETCH_ASSOC);
            $tipoUnidad = $unidad ? $unidad['tipo'] : 'unidad';

            // Formatear cantidad según tipo de unidad
            $cantidadFormato = $this->formatearCantidad($cantidad, $tipoUnidad);

            // Obtener stock actual
            $stockAnterior = $this->obtenerStockActual($idProducto);

            // Calcular nuevo stock
            if ($tipoMovimiento === 'entrada') {
                $stockActual = $stockAnterior + $cantidadFormato;
                $tieneAlerta = 0;
            } elseif ($tipoMovimiento === 'salida') {
                // Permitir stock negativo - restar cantidad completa
                $stockActual = $stockAnterior - $cantidadFormato;

                // Alerta cuando el stock llega a 0 o menos
                $tieneAlerta = ($stockActual < 0) ? 1 : 0;
            } else { // ajuste
                $stockActual = $cantidadFormato; // En ajuste, la cantidad ES el nuevo stock
                $tieneAlerta = $stockActual < 0 ? 1 : 0;
            }

            // Formatear también el stock para mantener consistencia
            $stockActualFormato = $this->formatearCantidad($stockActual, $tipoUnidad);
            $stockAnteriorFormato = $this->formatearCantidad($stockAnterior, $tipoUnidad);

            // Insertar movimiento (con flag de alerta si es negativo)
            $sql = "INSERT INTO inventario (idProducto, tipoMovimiento, cantidad, stockAnterior, stockActual, referencia, tipoReferencia, descripcion, idUsuario, tieneAlerta) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $idProducto,
                $tipoMovimiento,
                $cantidadFormato,
                $stockAnteriorFormato,
                $stockActualFormato,
                $referencia,
                $tipoReferencia,
                $descripcion,
                $idUsuario,
                $tieneAlerta
            ]);

            // Si el stock vuelve a ser positivo, eliminar registros de alerta anteriores
            if ($stockActualFormato > 0) {
                $sqlClear = "DELETE FROM inventario WHERE idProducto = ? AND tieneAlerta = 1";
                $stmtClear = $this->db->prepare($sqlClear);
                $stmtClear->execute([$idProducto]);
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
     * Formatear cantidad según tipo de unidad
     * Unidades → entero
     * Peso/Volumen → decimal (máximo 2 decimales)
     */
    private function formatearCantidad($cantidad, $tipoUnidad) {
        $normalizado = is_string($cantidad) ? str_replace(',', '.', $cantidad) : $cantidad;
        $numero = (float)$normalizado;
        
        if ($tipoUnidad === 'unidad') {
            // Redondear a entero
            return round($numero, 0);
        } else {
            // Peso/Volumen: redondear a 2 decimales
            return round($numero, 2);
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
        // Se acompaña la vista con el tipo del producto (venta / insumo) para
        // poder separar la mercancía que se vende de la materia prima.
        $sql = "SELECT v.*, COALESCE(p.tipo, 'venta') AS tipo
                FROM vista_stock_actual v
                LEFT JOIN productos p ON p.idProducto = v.idProducto
                ORDER BY v.producto ASC";
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
        $limit = max(1, (int)$limit);
        $sql = "SELECT i.*, u.nombre AS usuario, p.idUnidadBase, um.tipo AS unidadTipo
                FROM inventario i
                LEFT JOIN usuarios u ON i.idUsuario = u.idUsuario
                LEFT JOIN productos p ON i.idProducto = p.idProducto
                LEFT JOIN unidades_medida um ON p.idUnidadBase = um.idUnidad
                WHERE i.idProducto = ?
                ORDER BY i.fechaMovimiento DESC, i.idInventario DESC
            LIMIT $limit";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idProducto]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todos los movimientos (con filtros opcionales)
     */
    public function obtenerMovimientos($filtros = []) {
        $sql = "SELECT i.*, p.nombre AS producto, u.nombre AS usuario, p.idUnidadBase, um.tipo AS unidadTipo
                FROM inventario i
                INNER JOIN productos p ON i.idProducto = p.idProducto
                LEFT JOIN usuarios u ON i.idUsuario = u.idUsuario
                LEFT JOIN unidades_medida um ON p.idUnidadBase = um.idUnidad
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
     * Registrar CONSUMO de un insumo (salida por uso en producción).
     *
     * Es distinto del ajuste: aquí se indica CUÁNTO se sacó (ej. "5 kg de maíz
     * para los fritos"), no cuánto quedó. El ajuste sirve para corregir el
     * conteo; el consumo deja la trazabilidad de en qué se gastó el insumo.
     *
     * @param float  $cantidad    cantidad consumida (siempre positiva)
     * @param string $descripcion en qué se usó
     */
    public function registrarConsumo($idProducto, $cantidad, $descripcion, $idUsuario = null) {
        $cantidad = abs((float) $cantidad);
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad consumida debe ser mayor que cero');
        }

        return $this->registrarMovimiento(
            $idProducto,
            'salida',
            $cantidad,
            null,
            'consumo',
            $descripcion,
            $idUsuario
        );
    }

    /**
     * Valor del consumo de insumos en un rango de fechas: cuánto dinero se
     * gastó realmente en materia prima, valorado al costo promedio de compra.
     * Es el dato que permite saber si el insumo se está yendo más rápido de lo
     * que debería.
     */
    public function obtenerConsumoValorizado($fechaDesde, $fechaHasta) {
        $sql = "SELECT
                    i.idProducto,
                    p.nombre AS producto,
                    um.abreviatura AS unidad,
                    SUM(i.cantidad) AS cantidadConsumida,
                    COALESCE(cp.costoPromedio, p.precioCompra, 0) AS costoUnitario,
                    SUM(i.cantidad) * COALESCE(cp.costoPromedio, p.precioCompra, 0) AS valorConsumido
                FROM inventario i
                INNER JOIN productos p ON p.idProducto = i.idProducto
                LEFT JOIN unidades_medida um ON um.idUnidad = p.idUnidadBase
                LEFT JOIN (
                    SELECT h.idProducto,
                           SUM(h.cantidad * h.precioUnitario) / NULLIF(SUM(h.cantidad), 0) AS costoPromedio
                    FROM historial_precio_compra h
                    GROUP BY h.idProducto
                ) cp ON cp.idProducto = i.idProducto
                WHERE i.tipoMovimiento = 'salida'
                  AND i.tipoReferencia = 'consumo'
                  AND DATE(i.fechaMovimiento) BETWEEN ? AND ?
                GROUP BY i.idProducto, p.nombre, um.abreviatura, cp.costoPromedio, p.precioCompra
                ORDER BY valorConsumido DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fechaDesde, $fechaHasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
     * Obtener alertas de stock (productos con stock actual < 0) - TIEMPO REAL
     * Solo muestra productos que AHORA tienen stock negativo
     */
    public function obtenerAlertas($limit = 100) {
        $limit = max(1, (int)$limit);
        $sql = "SELECT 
                    v.idProducto,
                    v.producto,
                    v.categoria,
                    v.stockActual,
                    v.unidadTipo,
                    v.fechaUltimoMovimiento AS fechaMovimiento,
                    v.precioCompra,
                    v.precioVenta,
                    i.cantidad,
                    i.stockAnterior,
                    i.tipoMovimiento,
                    i.descripcion,
                    i.referencia,
                    u.nombre AS usuario
                FROM vista_stock_actual v
                LEFT JOIN inventario i ON i.idInventario = (
                    SELECT i2.idInventario
                    FROM inventario i2
                    WHERE i2.idProducto = v.idProducto
                    ORDER BY i2.fechaMovimiento DESC, i2.idInventario DESC
                    LIMIT 1
                )
                LEFT JOIN usuarios u ON i.idUsuario = u.idUsuario
                WHERE v.stockActual < 0
                ORDER BY v.stockActual ASC, v.fechaUltimoMovimiento DESC
                LIMIT $limit";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
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