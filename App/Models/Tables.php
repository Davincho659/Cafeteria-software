<?php

require_once __DIR__ . '/../Core/Conexion.php';

/**
 * Modelo Tables - Gestiona mesas/mesas del sistema
 * 
 * Nota: Las mesas solo almacenan su estado en BD.
 * Los productos asignados a mesas se manejan en memoria (frontend) 
 * igual que las ventas (venta1, venta2, etc.)
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
     * Actualizar estado de una mesa (si lo deseas persistir en BD)
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
     * Eliminar una mesa
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $query = "DELETE FROM mesas WHERE idMesa = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }
}