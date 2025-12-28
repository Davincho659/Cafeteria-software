<?php

require_once __DIR__ . '/../Core/Conexion.php';

/**
 * Modelo Tables - Gestiona mesas del sistema
 */
class Tables {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Obtener todas las mesas
     * @return array Lista de mesas
     */
    public function getAll() {
        $query = "SELECT idMesa, nombre, numero, estado FROM mesas ORDER BY numero ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    v.idVenta,
                    v.total,
                    v.fechaCreacion,
                    v.fechaActualizacion,
                    COUNT(dv.idDetalleVenta) AS cantidadProductos,
                    SUM(dv.cantidad) AS cantidadItems
                FROM mesas m
                LEFT JOIN ventas v ON m.idMesa = v.idMesa AND v.estado = 'pendiente'
                LEFT JOIN detalle_venta dv ON v.idVenta = dv.idVenta
                GROUP BY m.idMesa, m.numero, m.nombre, m.estado, v.idVenta, v.total, v.fechaCreacion, v.fechaActualizacion
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
    public function create($nombre, $numero, $estado = 'libre') {
        $query = "INSERT INTO mesas (nombre, numero, estado) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$nombre, $numero, $estado]);
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