<?php
require_once __DIR__ . '/../Core/Conexion.php';

/**
 * Modelo Tables - Gestiona mesas del sistema
 */
class Tables {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->ensurePositionColumns();
    }

    /**
     * Asegura que existan las columnas de posición (plano del salón).
     * Se crean solas la primera vez para no requerir migración manual.
     * posX / posY se guardan como porcentaje (0-100) del tablero, así el plano
     * se ve igual en cualquier tamaño de pantalla.
     */
    private function ensurePositionColumns() {
        try {
            if (!$this->db->query("SHOW COLUMNS FROM mesas LIKE 'posX'")->fetch()) {
                $this->db->exec("ALTER TABLE mesas
                    ADD COLUMN posX DECIMAL(6,2) NOT NULL DEFAULT 0,
                    ADD COLUMN posY DECIMAL(6,2) NOT NULL DEFAULT 0");
            }
            if (!$this->db->query("SHOW COLUMNS FROM mesas LIKE 'tipo'")->fetch()) {
                $this->db->exec("ALTER TABLE mesas
                    ADD COLUMN tipo ENUM('mesa','barra') NOT NULL DEFAULT 'mesa'");
            }
        } catch (Exception $e) {
            // Si falla (permisos), el resto del sistema sigue funcionando.
        }
    }

    /**
     * Obtener todas las mesas
     * @return array Lista de mesas
     */
    public function getAll() {
        $query = "SELECT idMesa, nombre, numero, estado, posX, posY, tipo FROM mesas ORDER BY numero ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guardar posiciones (plano del salón) en lote.
     * @param array $posiciones  [ ['idMesa'=>1,'posX'=>12.5,'posY'=>40], ... ]
     */
    public function savePositions(array $posiciones) {
        $stmt = $this->db->prepare("UPDATE mesas SET posX = ?, posY = ? WHERE idMesa = ?");
        $this->db->beginTransaction();
        try {
            foreach ($posiciones as $p) {
                $x = max(0, min(100, (float) ($p['posX'] ?? 0)));
                $y = max(0, min(100, (float) ($p['posY'] ?? 0)));
                $id = (int) ($p['idMesa'] ?? 0);
                if ($id > 0) {
                    $stmt->execute([$x, $y, $id]);
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Obtener todas las mesas con información de ventas activas
     * Esta es la función que usa el sistema de ventas
     * @return array Lista de mesas con datos de ventas activas
     */
    public function getAllWithActiveSales() {
        $query = "SELECT
                    m.idMesa,
                    m.numero AS numeroMesa,
                    m.nombre AS nombreMesa,
                    m.estado AS estadoMesa,
                    m.posX,
                    m.posY,
                    m.tipo,
                    v.idVenta,
                    v.total,
                    v.fechaCreacion,
                    v.fechaActualizacion,
                    COUNT(dv.idDetalleVenta) AS cantidadProductos,
                    SUM(dv.cantidad) AS cantidadItems
                FROM mesas m
                LEFT JOIN ventas v ON m.idMesa = v.idMesa AND v.estado = 'pendiente'
                LEFT JOIN detalle_venta dv ON v.idVenta = dv.idVenta
                GROUP BY m.idMesa, m.numero, m.nombre, m.estado, m.posX, m.posY, m.tipo, v.idVenta, v.total, v.fechaCreacion, v.fechaActualizacion
                ORDER BY m.numero ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener una mesa por ID
     * @param int $id
     * @return array|null Datos de la mesa o null si no existe
     */
    public function getById($id) {
        $query = "SELECT idMesa, nombre, numero, estado FROM mesas WHERE idMesa = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener mesa con información de venta activa
     * @param int $id
     * @return array|null
     */
    public function getByIdWithSale($id) {
        $query = "SELECT 
                    m.idMesa,
                    m.numero AS numeroMesa,
                    m.nombre AS nombreMesa,
                    m.estado AS estadoMesa,
                    v.idVenta,
                    v.total,
                    v.fechaCreacion,
                    COUNT(dv.idDetalleVenta) AS cantidadProductos
                FROM mesas m
                LEFT JOIN ventas v ON m.idMesa = v.idMesa AND v.estado = 'pendiente'
                LEFT JOIN detalle_venta dv ON v.idVenta = dv.idVenta
                WHERE m.idMesa = ?
                GROUP BY m.idMesa, m.numero, m.nombre, m.estado, v.idVenta, v.total, v.fechaCreacion";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener mesas por estado
     * @param string $estado ('libre', 'ocupada')
     * @return array Lista de mesas
     */
    public function getByState($estado) {
        $query = "SELECT idMesa, nombre, numero, estado FROM mesas WHERE estado = ? ORDER BY numero ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$estado]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener solo mesas ocupadas (con ventas activas)
     * @return array
     */
    public function getOccupied() {
        $query = "SELECT 
                    m.idMesa,
                    m.numero AS numeroMesa,
                    m.nombre AS nombreMesa,
                    v.idVenta,
                    v.total,
                    COUNT(dv.idDetalleVenta) AS cantidadProductos
                FROM mesas m
                INNER JOIN ventas v ON m.idMesa = v.idMesa AND v.estado = 'pendiente'
                LEFT JOIN detalle_venta dv ON v.idVenta = dv.idVenta
                GROUP BY m.idMesa, m.numero, m.nombre, v.idVenta, v.total
                ORDER BY m.numero ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener solo mesas libres (sin ventas activas)
     * @return array
     */
    public function getAvailable() {
        $query = "SELECT m.idMesa, m.numero AS numeroMesa, m.nombre AS nombreMesa, m.estado
                FROM mesas m
                LEFT JOIN ventas v ON m.idMesa = v.idMesa AND v.estado = 'pendiente'
                WHERE v.idVenta IS NULL
                ORDER BY m.numero ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar estado de una mesa
     * @param int $id
     * @param string $estado ('libre', 'ocupada')
     * @return bool
     */
    public function updateState($id, $estado) {
        $query = "UPDATE mesas SET estado = ? WHERE idMesa = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$estado, $id]);
    }

    /**
     * Crear una nueva mesa
     * @param string $nombre
     * @param int $numero
     * @param string $estado
     * @return int ID de la mesa creada
     */
    public function create($nombre, $numero, $estado = 'libre', $tipo = 'mesa') {
        $tipo = in_array($tipo, ['mesa', 'barra'], true) ? $tipo : 'mesa';
        $query = "INSERT INTO mesas (nombre, numero, estado, tipo) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$nombre, $numero, $estado, $tipo]);
        return $this->db->lastInsertId();
    }

    /**
     * Actualizar información de una mesa
     * @param int $id
     * @param string $nombre
     * @param int $numero
     * @return bool
     */
    public function update($id, $nombre, $numero) {
        $query = "UPDATE mesas SET nombre = ?, numero = ? WHERE idMesa = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$nombre, $numero, $id]);
    }

    /**
     * Eliminar una mesa
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $query = "DELETE FROM mesas WHERE idMesa = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    /**
     * Verificar si una mesa tiene venta activa
     * @param int $idMesa
     * @return bool
     */
    public function hasActiveSale($idMesa) {
        $query = "SELECT COUNT(*) as total 
                FROM ventas 
                WHERE idMesa = ? AND estado = 'pendiente'";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idMesa]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }

    /**
     * Obtener estadísticas de mesas
     * @return array
     */
    public function getStatistics() {
        $query = "SELECT 
                    COUNT(*) as totalMesas,
                    SUM(CASE WHEN estado = 'libre' THEN 1 ELSE 0 END) as mesasLibres,
                    SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as mesasOcupadas,
                    (SELECT COUNT(*) FROM ventas WHERE estado = 'pendiente') as ventasActivas
                FROM mesas";
        $stmt = $this->db->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}